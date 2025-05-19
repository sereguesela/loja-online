import os
from flask import Flask, render_template, flash, redirect, url_for, request
from flask_login import LoginManager, login_user, logout_user, login_required, current_user
from urllib.parse import urlparse
from flask_wtf.csrf import CSRFProtect

from models import db, User, Product, Category, Order, OrderItem, Message
from forms import LoginForm, RegistrationForm, ProductForm, CategoryForm, MessageForm, OrderPaymentNotificationForm
from utils import save_image, admin_required, format_currency, get_order_status_label
from config import Config

def create_app(config_class=Config):
    app = Flask(__name__)
    app.config.from_object(config_class)
    
    # Inicializa as extensões
    db.init_app(app)
    csrf = CSRFProtect(app)  # Adiciona proteção CSRF
    
    login_manager = LoginManager()
    login_manager.init_app(app)
    login_manager.login_view = 'auth.login'
    login_manager.login_message = 'Por favor, faça login para acessar esta página.'
    login_manager.login_message_category = 'info'
    
    @login_manager.user_loader
    def load_user(user_id):
        return User.query.get(int(user_id))
    
    # Cria as tabelas do banco de dados
    with app.app_context():
        db.create_all()
        # Cria um usuário admin se não existir
        if not User.query.filter_by(is_admin=True).first():
            admin = User(username='admin', email='admin@example.com', is_admin=True)
            admin.set_password('admin123')
            db.session.add(admin)
            db.session.commit()
    
    # Registra os blueprints
    from routes.main import main_bp
    from routes.auth import auth_bp
    from routes.admin import admin_bp
    from routes.cliente import cliente_bp
    
    app.register_blueprint(main_bp)
    app.register_blueprint(auth_bp, url_prefix='/auth')
    app.register_blueprint(admin_bp, url_prefix='/admin')
    app.register_blueprint(cliente_bp, url_prefix='/cliente')
    
    # Filtros personalizados para Jinja2
    app.jinja_env.filters['format_currency'] = format_currency
    app.jinja_env.filters['get_order_status_label'] = get_order_status_label
    
    @app.errorhandler(404)
    def page_not_found(e):
        return render_template('404.html'), 404
    
    @app.errorhandler(500)
    def internal_server_error(e):
        return render_template('500.html'), 500
    
    return app

if __name__ == '__main__':
    app = create_app()
    app.run() 