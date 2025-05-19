# Site de Vendas com Flask e Mercado Pago

Um site completo de vendas online desenvolvido com Flask, que permite a gestão de produtos, processamento de pedidos e pagamentos via PIX utilizando o Mercado Pago.

## Funcionalidades

- 🔐 **Sistema de autenticação** para administradores e clientes
- 🏪 **Catálogo de produtos** organizados por categoria
- 🛒 **Sistema de compras** com status de pedidos
- 💰 **Integração com PIX** para pagamentos
- 💬 **Chat interno** entre administradores e clientes
- 📊 **Painel administrativo** para gerenciamento de produtos e pedidos

## Tecnologias Utilizadas

- **Backend**: Flask, SQLAlchemy, Flask-Login
- **Frontend**: Bootstrap 5, FontAwesome
- **Banco de Dados**: SQLite (pode ser substituído por MySQL ou PostgreSQL)
- **Pagamentos**: Mercado Pago (PIX)

## Instalação

1. Clone o repositório:
```bash
git clone <seu-repositorio>
cd site-vendas
```

2. Crie um ambiente virtual:
```bash
python -m venv venv
source venv/bin/activate  # Linux/Mac
venv\Scripts\activate     # Windows
```

3. Instale as dependências:
```bash
pip install -r requirements.txt
```

4. Configure as variáveis de ambiente:
- Copie o arquivo `env.example` para `.env`
- Preencha as variáveis necessárias no arquivo `.env`

5. Inicialize o banco de dados:
```bash
flask db upgrade
```

## Deploy no Render

1. Crie uma conta no [Render](https://render.com)

2. Conecte seu repositório GitHub ao Render

3. Crie um novo Web Service:
   - Selecione seu repositório
   - Nome: `site-vendas` (ou outro de sua preferência)
   - Runtime: Python 3
   - Build Command: `pip install -r requirements.txt`
   - Start Command: `gunicorn wsgi:app`

4. Configure as variáveis de ambiente no Render:
   - `SECRET_KEY`
   - `DATABASE_URL`
   - `MP_ACCESS_TOKEN` (MercadoPago)
   - `MP_PUBLIC_KEY` (MercadoPago)
   - `PIX_KEY`
   - `WTF_CSRF_SECRET_KEY`

5. Deploy:
   - O Render irá automaticamente fazer o deploy quando você enviar alterações para a branch principal

## Desenvolvimento Local

Para rodar o projeto localmente:

```bash
flask run
```

O site estará disponível em `http://localhost:5000`

## Estrutura do Projeto

```
project_root/
│
├── app.py                    # Arquivo principal da aplicação Flask
├── templates/                # Diretório para os templates HTML
│   ├── admin/                # Templates específicos para área administrativa
│   └── cliente/              # Templates específicos para área do cliente
│
├── static/                   # Arquivo para CSS, JS, imagens
│   ├── css/
│   ├── js/
│   └── uploads/              # Imagens de produtos enviadas
│
├── models.py                 # Modelos do banco de dados
├── routes/                   # Blueprints e rotas da aplicação
├── forms.py                  # Definição de formulários Flask-WTF
├── utils.py                  # Funções auxiliares e de segurança
└── config.py                 # Configurações da aplicação
```

## Usuário Padrão

Para facilitar o primeiro acesso, o sistema cria automaticamente um usuário administrador:

- **Email**: admin@example.com
- **Senha**: admin123

*Lembre-se de alterar estas credenciais em ambiente de produção!*

## Contribuições

Contribuições são bem-vindas! Sinta-se à vontade para abrir issues ou enviar pull requests.

## Licença

Este projeto está licenciado sob a licença MIT - veja o arquivo LICENSE.md para detalhes. 