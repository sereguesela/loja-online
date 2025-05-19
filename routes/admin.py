from flask import Blueprint, render_template, redirect, url_for, flash, request, current_app
from flask_login import login_required, current_user
from sqlalchemy import desc

from models import db, Product, Category, Order, Message, User
from forms import ProductForm, CategoryForm, MessageForm
from utils import admin_required, save_image
import os

admin_bp = Blueprint('admin', __name__)

@admin_bp.route('/dashboard')
@login_required
@admin_required
def dashboard():
    """Painel principal do administrador"""
    # Contadores para o dashboard
    total_pedidos = Order.query.count()
    pedidos_pendentes = Order.query.filter_by(status='pendente').count()
    total_produtos = Product.query.count()
    mensagens_novas = Message.query.filter_by(receiver_id=current_user.id, is_read=False).count()
    
    # Últimos pedidos
    ultimos_pedidos = Order.query.order_by(desc(Order.created_at)).limit(5).all()
    
    # Mensagens recentes
    mensagens_recentes = Message.query.filter_by(
        receiver_id=current_user.id
    ).order_by(desc(Message.timestamp)).limit(5).all()
    
    return render_template('admin/dashboard.html', 
                          title='Dashboard Administrativo',
                          total_pedidos=total_pedidos,
                          pedidos_pendentes=pedidos_pendentes,
                          total_produtos=total_produtos,
                          mensagens_novas=mensagens_novas,
                          ultimos_pedidos=ultimos_pedidos,
                          mensagens_recentes=mensagens_recentes)

@admin_bp.route('/produtos')
@login_required
@admin_required
def produtos():
    """Lista todos os produtos para gerenciamento"""
    products = Product.query.order_by(Product.created_at.desc()).all()
    form = ProductForm()
    return render_template('admin/produtos.html', 
                          title='Gerenciar Produtos',
                          products=products,
                          form=form)

@admin_bp.route('/produto/novo', methods=['GET', 'POST'])
@login_required
@admin_required
def novo_produto():
    """Criar um novo produto"""
    form = ProductForm()
    form.category_id.choices = [(c.id, c.name) for c in Category.query.all()]
    
    if form.validate_on_submit():
        filename = None
        if form.image.data:
            filename = save_image(form.image.data, folder='uploads')
        
        product = Product(
            title=form.title.data,
            description=form.description.data,
            price=form.price.data,
            category_id=form.category_id.data,
            image_filename=filename,
            external_link=form.external_link.data
        )
        
        db.session.add(product)
        db.session.commit()
        
        flash('Produto criado com sucesso!', 'success')
        return redirect(url_for('admin.produtos'))
    
    return render_template('admin/criar_produto.html', 
                          title='Novo Produto',
                          form=form)

@admin_bp.route('/produto/editar/<int:product_id>', methods=['GET', 'POST'])
@login_required
@admin_required
def editar_produto(product_id):
    """Editar um produto existente"""
    product = Product.query.get_or_404(product_id)
    form = ProductForm(obj=product)
    form.category_id.choices = [(c.id, c.name) for c in Category.query.all()]
    
    if form.validate_on_submit():
        product.title = form.title.data
        product.description = form.description.data
        product.price = form.price.data
        product.category_id = form.category_id.data
        product.external_link = form.external_link.data
        
        if form.image.data:
            # Exclui a imagem antiga se existir
            if product.image_filename:
                try:
                    os.remove(os.path.join(current_app.config['UPLOAD_FOLDER'], product.image_filename))
                except:
                    pass
            
            # Salva a nova imagem
            filename = save_image(form.image.data, folder='uploads')
            product.image_filename = filename
        
        db.session.commit()
        flash('Produto atualizado com sucesso!', 'success')
        return redirect(url_for('admin.produtos'))
    
    return render_template('admin/editar_produto.html', 
                          title='Editar Produto',
                          form=form,
                          product=product)

@admin_bp.route('/produto/excluir/<int:product_id>', methods=['POST'])
@login_required
@admin_required
def excluir_produto(product_id):
    """Excluir um produto"""
    product = Product.query.get_or_404(product_id)
    
    # Exclui a imagem associada, se existir
    if product.image_filename:
        try:
            os.remove(os.path.join(current_app.config['UPLOAD_FOLDER'], product.image_filename))
        except:
            pass
    
    db.session.delete(product)
    db.session.commit()
    
    flash('Produto excluído com sucesso!', 'success')
    return redirect(url_for('admin.produtos'))

@admin_bp.route('/categorias')
@login_required
@admin_required
def categorias():
    """Lista todas as categorias para gerenciamento"""
    categories = Category.query.all()
    form = CategoryForm()
    
    return render_template('admin/categorias.html', 
                          title='Gerenciar Categorias',
                          categories=categories,
                          form=form)

@admin_bp.route('/categoria/nova', methods=['POST'])
@login_required
@admin_required
def nova_categoria():
    """Criar uma nova categoria"""
    form = CategoryForm()
    
    if form.validate_on_submit():
        category = Category(name=form.name.data)
        db.session.add(category)
        db.session.commit()
        
        flash('Categoria criada com sucesso!', 'success')
    
    return redirect(url_for('admin.categorias'))

@admin_bp.route('/categoria/excluir/<int:category_id>', methods=['POST'])
@login_required
@admin_required
def excluir_categoria(category_id):
    """Excluir uma categoria"""
    category = Category.query.get_or_404(category_id)
    
    # Verifica se existem produtos nesta categoria
    if Product.query.filter_by(category_id=category_id).first():
        flash('Não é possível excluir uma categoria que contém produtos!', 'danger')
    else:
        db.session.delete(category)
        db.session.commit()
        flash('Categoria excluída com sucesso!', 'success')
    
    return redirect(url_for('admin.categorias'))

@admin_bp.route('/pedidos')
@login_required
@admin_required
def pedidos():
    """Lista todos os pedidos para gerenciamento"""
    status_filter = request.args.get('status', '')
    cliente_id = request.args.get('cliente_id', type=int)
    
    query = Order.query.order_by(desc(Order.created_at))
    
    if status_filter:
        query = query.filter_by(status=status_filter)
    
    if cliente_id:
        query = query.filter_by(user_id=cliente_id)
    
    orders = query.all()
    
    return render_template('admin/pedidos.html', 
                          title='Gerenciar Pedidos',
                          orders=orders,
                          status_filter=status_filter)

@admin_bp.route('/pedido/<int:order_id>')
@login_required
@admin_required
def pedido_detalhes(order_id):
    """Exibe os detalhes de um pedido específico"""
    order = Order.query.get_or_404(order_id)
    
    # Busca mensagens relacionadas a este pedido
    messages = Message.query.filter(
        ((Message.sender_id == current_user.id) & (Message.receiver_id == order.customer.id)) |
        ((Message.sender_id == order.customer.id) & (Message.receiver_id == current_user.id))
    ).order_by(Message.timestamp).all()
    
    # Cria o formulário de mensagem
    form = MessageForm()
    
    return render_template('admin/pedido_detalhes.html', 
                          title=f'Pedido #{order.id}',
                          order=order,
                          messages=messages,
                          form=form)

@admin_bp.route('/pedido/atualizar-status/<int:order_id>', methods=['POST'])
@login_required
@admin_required
def atualizar_status_pedido(order_id):
    """Atualiza o status de um pedido"""
    order = Order.query.get_or_404(order_id)
    new_status = request.form.get('status')
    
    if new_status in ['pendente', 'aprovado', 'rejeitado', 'entregue']:
        order.status = new_status
        db.session.commit()
        flash(f'Status do pedido atualizado para {new_status}!', 'success')
    else:
        flash('Status inválido!', 'danger')
    
    return redirect(url_for('admin.pedido_detalhes', order_id=order.id))

@admin_bp.route('/chat')
@login_required
@admin_required
def chat():
    """Interface de chat para comunicação com clientes"""
    # Obter todas as conversas únicas
    sent_messages = Message.query.filter_by(sender_id=current_user.id).all()
    received_messages = Message.query.filter_by(receiver_id=current_user.id).all()
    
    users_sent_to = {msg.receiver_id: User.query.get(msg.receiver_id).username for msg in sent_messages}
    users_received_from = {msg.sender_id: User.query.get(msg.sender_id).username for msg in received_messages}
    
    # Combinar todos os usuários com quem houve comunicação
    users = {**users_sent_to, **users_received_from}
    
    # Remover admins da lista de conversas
    users = {uid: username for uid, username in users.items() if not User.query.get(uid).is_admin}
    
    # Se um usuário específico foi selecionado
    selected_user_id = request.args.get('user_id', type=int)
    selected_order_id = request.args.get('order_id', type=int)
    
    if selected_user_id:
        selected_user = User.query.get_or_404(selected_user_id)
        
        # Obter histórico de mensagens com este usuário
        messages = Message.query.filter(
            ((Message.sender_id == current_user.id) & (Message.receiver_id == selected_user_id)) |
            ((Message.sender_id == selected_user_id) & (Message.receiver_id == current_user.id))
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
        selected_user = None
        messages = []
        form = None
    
    return render_template('admin/chat_admin.html',
                          title='Chat com Clientes',
                          users=users,
                          selected_user=selected_user,
                          selected_order_id=selected_order_id,
                          messages=messages,
                          form=form)

@admin_bp.route('/chat/enviar/<int:user_id>', methods=['POST'])
@login_required
@admin_required
def enviar_mensagem(user_id):
    """Envia uma mensagem para um cliente"""
    form = MessageForm()
    order_id = request.args.get('order_id', type=int)
    
    if form.validate_on_submit():
        message = Message(
            content=form.content.data,
            sender_id=current_user.id,
            receiver_id=user_id,
            order_id=order_id,
            is_read=False
        )
        
        db.session.add(message)
        db.session.commit()
        
        flash('Mensagem enviada!', 'success')
    
    return redirect(url_for('admin.chat', user_id=user_id, order_id=order_id))

@admin_bp.route('/clientes')
@login_required
@admin_required
def clientes():
    """Lista todos os clientes cadastrados"""
    # Busca todos os usuários que não são admin
    clientes = User.query.filter_by(is_admin=False).order_by(User.username).all()
    
    return render_template('admin/clientes.html', 
                          title='Gerenciar Clientes',
                          clientes=clientes) 