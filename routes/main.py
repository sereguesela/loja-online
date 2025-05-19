from flask import Blueprint, render_template, request, redirect, url_for, flash, current_app
from models import db, Product, Category
from flask_login import current_user

main_bp = Blueprint('main', __name__)

@main_bp.route('/')
def index():
    """Página inicial do site"""
    categories = Category.query.all()
    featured_products = Product.query.order_by(Product.created_at.desc()).limit(6).all()
    return render_template('index.html', 
                           categories=categories, 
                           featured_products=featured_products,
                           title='Página Inicial')

@main_bp.route('/produtos')
def produtos():
    """Lista todos os produtos disponíveis"""
    page = request.args.get('page', 1, type=int)
    per_page = 12  # Produtos por página
    
    # Filtrar por categoria se especificado
    category_id = request.args.get('categoria', type=int)
    search_query = request.args.get('q', '')
    
    query = Product.query
    
    if category_id:
        query = query.filter_by(category_id=category_id)
    
    if search_query:
        query = query.filter(Product.title.ilike(f'%{search_query}%'))
    
    # Ordenação
    sort_by = request.args.get('sort_by', 'newest')
    if sort_by == 'price_low':
        query = query.order_by(Product.price.asc())
    elif sort_by == 'price_high':
        query = query.order_by(Product.price.desc())
    else:  # newest
        query = query.order_by(Product.created_at.desc())
    
    # Paginação
    products = query.paginate(page=page, per_page=per_page)
    categories = Category.query.all()
    
    return render_template('produtos.html', 
                          products=products,
                          categories=categories,
                          current_category=category_id,
                          search_query=search_query,
                          sort_by=sort_by,
                          title='Produtos')

@main_bp.route('/produto/<int:product_id>')
def produto_detalhes(product_id):
    """Exibe os detalhes de um produto específico"""
    produto = Product.query.get_or_404(product_id)
    produtos_relacionados = Product.query.filter_by(category_id=produto.category_id) \
                                .filter(Product.id != produto.id) \
                                .order_by(db.func.random()) \
                                .limit(4).all()
    
    return render_template('cliente/detalhes_produto.html', 
                          produto=produto,
                          produtos_relacionados=produtos_relacionados,
                          title=produto.title) 