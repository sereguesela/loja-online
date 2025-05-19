from flask import Blueprint, render_template, redirect, url_for, flash, request, current_app
from flask_login import login_required, current_user
from sqlalchemy import desc
from datetime import datetime

from models import db, Product, Category, Order, OrderItem, Message, User
from forms import MessageForm, OrderPaymentNotificationForm
from utils import save_image, get_admin_users

cliente_bp = Blueprint('cliente', __name__)

@cliente_bp.route('/dashboard')
@login_required
def dashboard():
    """Painel principal do cliente"""
    orders = Order.query.filter_by(user_id=current_user.id).order_by(desc(Order.created_at)).all()
    messages = Message.query.filter_by(receiver_id=current_user.id).order_by(desc(Message.timestamp)).all()
    
    return render_template('cliente/dashboard.html', 
                          title='Minha Conta',
                          orders=orders,
                          messages=messages)

@cliente_bp.route('/comprar/<int:product_id>', methods=['GET', 'POST'])
@login_required
def comprar(product_id):
    """Processa a compra de um produto"""
    product = Product.query.get_or_404(product_id)
    
    # Verifica se já existe um pedido pendente para este produto
    existing_order = Order.query.filter_by(
        user_id=current_user.id,
        status='pendente'
    ).join(OrderItem).filter(
        OrderItem.product_id == product_id
    ).first()
    
    if existing_order:
        flash('Você já tem um pedido pendente para este produto!', 'warning')
        return redirect(url_for('cliente.pedido', order_id=existing_order.id))
    
    # Cria um novo pedido
    order = Order(
        user_id=current_user.id,
        status='pendente',
        total_amount=product.price
    )
    
    db.session.add(order)
    db.session.flush()  # Para obter o ID do pedido
    
    # Adiciona o item ao pedido
    order_item = OrderItem(
        order_id=order.id,
        product_id=product_id,
        quantity=1,
        price=product.price
    )
    
    db.session.add(order_item)
    db.session.commit()
    
    flash('Pedido criado com sucesso! Prossiga para o pagamento.', 'success')
    return redirect(url_for('cliente.pedido', order_id=order.id))

@cliente_bp.route('/pedido/<int:order_id>', methods=['GET', 'POST'])
@login_required
def pedido(order_id):
    """Exibe os detalhes de um pedido e opções de pagamento"""
    order = Order.query.filter_by(id=order_id, user_id=current_user.id).first_or_404()
    notification_form = OrderPaymentNotificationForm()
    
    # Chave PIX fixa do sistema
    pix_key = current_app.config['PIX_KEY']
    
    if request.method == 'POST' and notification_form.validate_on_submit():
        # Cliente confirma que fez o pagamento
        order.payment_notification = True
        order.updated_at = datetime.utcnow()
        db.session.commit()
        
        # Cria uma mensagem automática para notificar o admin
        admin_users = get_admin_users()
        if admin_users:
            for admin in admin_users:
                message = Message(
                    content=f"O cliente {current_user.username} confirmou o pagamento do pedido #{order.id} no valor de R$ {order.total_amount:.2f}. Por favor, verifique o recebimento e atualize o status do pedido.",
                    sender_id=current_user.id,
                    receiver_id=admin.id,
                    order_id=order.id,
                    is_read=False
                )
                db.session.add(message)
            
            db.session.commit()
        
        flash('Notificação de pagamento enviada com sucesso! Aguarde a confirmação.', 'success')
        return redirect(url_for('cliente.pedido', order_id=order.id))
    
    return render_template('cliente/pedido.html', 
                          title=f'Pedido #{order.id}',
                          order=order,
                          pix_key=pix_key,
                          notification_form=notification_form)

@cliente_bp.route('/pedidos')
@login_required
def pedidos():
    """Lista todos os pedidos do cliente"""
    status_filter = request.args.get('status', '')
    
    query = Order.query.filter_by(user_id=current_user.id).order_by(desc(Order.created_at))
    
    if status_filter:
        query = query.filter_by(status=status_filter)
    
    orders = query.all()
    
    return render_template('cliente/pedidos.html', 
                          title='Meus Pedidos',
                          orders=orders,
                          status_filter=status_filter)

@cliente_bp.route('/chat')
@login_required
def chat():
    """Interface de chat para comunicação com admin"""
    # Obter todas as conversas com admins
    admin_users = get_admin_users()
    admin_ids = [admin.id for admin in admin_users]
    
    # Mensagens enviadas para administradores
    sent_messages = Message.query.filter_by(sender_id=current_user.id).filter(Message.receiver_id.in_(admin_ids)).all()
    
    # Mensagens recebidas de administradores
    received_messages = Message.query.filter_by(receiver_id=current_user.id).filter(Message.sender_id.in_(admin_ids)).all()
    
    # Mapeamento de IDs de admin para nomes
    admins = {admin.id: admin.username for admin in admin_users}
    
    # Se um pedido específico foi selecionado
    selected_order_id = request.args.get('order_id', type=int)
    
    # Se um admin específico foi selecionado
    selected_admin_id = request.args.get('admin_id', type=int)
    if selected_admin_id is None and admin_users:
        # Selecionar o primeiro admin por padrão
        selected_admin_id = admin_users[0].id
    
    if selected_admin_id:
        # Obter histórico de mensagens com este admin
        messages = Message.query.filter(
            ((Message.sender_id == current_user.id) & (Message.receiver_id == selected_admin_id)) |
            ((Message.sender_id == selected_admin_id) & (Message.receiver_id == current_user.id))
        ).order_by(Message.timestamp).all()
        
        # Se há um pedido específico, filtrar apenas mensagens relacionadas
        if selected_order_id:
            messages = [msg for msg in messages if msg.order_id == selected_order_id]
            
        # Marcar mensagens como lidas
        for msg in messages:
            if msg.receiver_id == current_user.id and not msg.is_read:
                msg.is_read = True
        
        db.session.commit()
        
        form = MessageForm()
    else:
        messages = []
        form = None
    
    return render_template('cliente/chat_cliente.html',
                          title='Chat com Suporte',
                          admins=admins,
                          selected_admin_id=selected_admin_id,
                          selected_order_id=selected_order_id,
                          messages=messages,
                          form=form)

@cliente_bp.route('/chat/enviar/<int:admin_id>', methods=['POST'])
@login_required
def enviar_mensagem(admin_id):
    """Envia uma mensagem para um admin"""
    form = MessageForm()
    order_id = request.args.get('order_id', type=int)
    
    if form.validate_on_submit():
        message = Message(
            content=form.content.data,
            sender_id=current_user.id,
            receiver_id=admin_id,
            order_id=order_id,
            is_read=False
        )
        
        db.session.add(message)
        db.session.commit()
        
        flash('Mensagem enviada!', 'success')
    
    return redirect(url_for('cliente.chat', admin_id=admin_id, order_id=order_id))

@cliente_bp.route('/nova-mensagem', methods=['GET', 'POST'])
@login_required
def nova_mensagem():
    """Cria uma nova mensagem para o suporte"""
    form = MessageForm()
    admin_users = get_admin_users()
    
    if not admin_users:
        flash('Não há administradores disponíveis no momento.', 'warning')
        return redirect(url_for('cliente.dashboard'))
    
    if form.validate_on_submit():
        # Envia a mensagem para o primeiro admin disponível
        message = Message(
            content=form.content.data,
            sender_id=current_user.id,
            receiver_id=admin_users[0].id,
            is_read=False
        )
        
        db.session.add(message)
        db.session.commit()
        
        flash('Mensagem enviada com sucesso!', 'success')
        return redirect(url_for('cliente.chat'))
    
    return render_template('cliente/nova_mensagem.html',
                          title='Nova Mensagem',
                          form=form) 