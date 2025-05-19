import os
from dotenv import load_dotenv
from datetime import timedelta

# Carrega variáveis de ambiente do arquivo .env
load_dotenv()

# Garante que a pasta instance existe
basedir = os.path.abspath(os.path.dirname(__file__))
instance_path = os.path.join(basedir, 'instance')
if not os.path.exists(instance_path):
    os.makedirs(instance_path)

class Config:
    # Configurações básicas
    SECRET_KEY = os.environ.get('SECRET_KEY') or 'uma-chave-super-secreta-e-dificil-de-adivinhar'
    WTF_CSRF_SECRET_KEY = os.environ.get('WTF_CSRF_SECRET_KEY') or 'outra-chave-super-secreta-para-csrf'
    
    # Configurações gerais
    SECRET_KEY = SECRET_KEY
    
    # Configurações do banco de dados
    database_url = os.environ.get('DATABASE_URL')
    if database_url and database_url.startswith('postgres://'):
        database_url = database_url.replace('postgres://', 'postgresql://', 1)
    
    SQLALCHEMY_DATABASE_URI = database_url or \
        'sqlite:///' + os.path.join(instance_path, 'app.db')
    SQLALCHEMY_TRACK_MODIFICATIONS = False
    
    # Configurações do Mercado Pago
    MP_ACCESS_TOKEN = os.environ.get('MP_ACCESS_TOKEN')
    MP_PUBLIC_KEY = os.environ.get('MP_PUBLIC_KEY')
    
    # Chave PIX Fixa
    PIX_KEY = os.environ.get('PIX_KEY') or 'chave-pix-padrao@email.com'
    
    # Configurações de upload
    UPLOAD_FOLDER = os.path.join(basedir, 'static', 'uploads')
    if not os.path.exists(UPLOAD_FOLDER):
        os.makedirs(UPLOAD_FOLDER)
    MAX_CONTENT_LENGTH = 16 * 1024 * 1024  # 16MB max file size
    
    # Configuração de sessão
    PERMANENT_SESSION_LIFETIME = timedelta(days=7)
    
    # Configuração do CSRF
    WTF_CSRF_ENABLED = True
    WTF_CSRF_TIME_LIMIT = 3600  # 1 hora
    
    # Configurações de produção
    SESSION_COOKIE_SECURE = os.environ.get('PRODUCTION', 'false').lower() == 'true'
    SESSION_COOKIE_HTTPONLY = True
    REMEMBER_COOKIE_SECURE = os.environ.get('PRODUCTION', 'false').lower() == 'true'
    REMEMBER_COOKIE_HTTPONLY = True 