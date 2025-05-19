import os
import secrets
from PIL import Image
from functools import wraps
from flask import flash, redirect, url_for, current_app
from flask_login import current_user

def save_image(form_image, folder='uploads'):
    """Salva a imagem enviada pelo formulário e retorna o nome do arquivo"""
    if not form_image:
        return None
        
    # Gera um nome de arquivo aleatório para evitar colisões
    random_hex = secrets.token_hex(8)
    _, file_ext = os.path.splitext(form_image.filename)
    image_filename = random_hex + file_ext
    
    # Cria o diretório de uploads se não existir
    upload_path = os.path.join(current_app.root_path, 'static', folder)
    os.makedirs(upload_path, exist_ok=True)
    
    image_path = os.path.join(upload_path, image_filename)
    
    # Redimensiona e salva a imagem
    i = Image.open(form_image)
    max_size = (800, 800)
    i.thumbnail(max_size)
    i.save(image_path)
    
    return image_filename

def admin_required(f):
    """Decorator para rotas que requerem acesso de administrador"""
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if not current_user.is_authenticated or not current_user.is_admin:
            flash('Acesso restrito. Você precisa ser um administrador para acessar esta página.', 'danger')
            return redirect(url_for('main.index'))
        return f(*args, **kwargs)
    return decorated_function

def format_currency(value):
    """Formata um valor como moeda (R$)"""
    if value is None:
        return "R$ 0,00"
    return f"R$ {value:.2f}".replace('.', ',')

def get_order_status_label(status):
    """Retorna uma label HTML com cor de acordo com o status do pedido"""
    status_labels = {
        'pendente': '<span class="badge bg-warning text-dark">Pendente</span>',
        'aprovado': '<span class="badge bg-success">Aprovado</span>',
        'rejeitado': '<span class="badge bg-danger">Rejeitado</span>',
        'entregue': '<span class="badge bg-info">Entregue</span>'
    }
    return status_labels.get(status, f'<span class="badge bg-secondary">{status}</span>')

def get_admin_users():
    """Retorna uma lista com todos os usuários administradores"""
    from models import User
    return User.query.filter_by(is_admin=True).all() 