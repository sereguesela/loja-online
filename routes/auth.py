from flask import Blueprint, render_template, redirect, url_for, flash, request
from flask_login import login_user, logout_user, current_user, login_required
from urllib.parse import urlparse

from models import db, User
from forms import LoginForm, RegistrationForm

auth_bp = Blueprint('auth', __name__)

@auth_bp.route('/login', methods=['GET', 'POST'])
def login():
    """Página de login para usuários"""
    if current_user.is_authenticated:
        # Redireciona para a página admin se for admin, ou para página do cliente
        if current_user.is_admin:
            return redirect(url_for('admin.dashboard'))
        return redirect(url_for('cliente.dashboard'))
    
    form = LoginForm()
    if form.validate_on_submit():
        user = User.query.filter_by(email=form.email.data).first()
        if user is None or not user.check_password(form.password.data):
            flash('Email ou senha inválidos', 'danger')
            return redirect(url_for('auth.login'))
        
        login_user(user, remember=form.remember_me.data)
        
        # Redireciona para a próxima página solicitada
        next_page = request.args.get('next')
        if not next_page or urlparse(next_page).netloc != '':
            if user.is_admin:
                next_page = url_for('admin.dashboard')
            else:
                next_page = url_for('cliente.dashboard')
        
        return redirect(next_page)
    
    return render_template('auth/login.html', title='Login', form=form)

@auth_bp.route('/registro', methods=['GET', 'POST'])
def registro():
    """Página de registro para novos usuários"""
    if current_user.is_authenticated:
        return redirect(url_for('main.index'))
    
    form = RegistrationForm()
    if form.validate_on_submit():
        user = User(username=form.username.data, email=form.email.data)
        user.set_password(form.password.data)
        
        # Por padrão, novos usuários são clientes (não admin)
        user.is_admin = False
        
        db.session.add(user)
        db.session.commit()
        
        flash('Conta criada com sucesso! Você já pode fazer login.', 'success')
        return redirect(url_for('auth.login'))
    
    return render_template('auth/registro.html', title='Registro', form=form)

@auth_bp.route('/logout')
@login_required
def logout():
    """Rota para logout de usuários"""
    logout_user()
    flash('Você foi desconectado com sucesso.', 'info')
    return redirect(url_for('main.index')) 