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
```
git clone https://github.com/seu-usuario/site-vendas.git
cd site-vendas
```

2. Crie um ambiente virtual e ative-o:
```
python -m venv venv
# No Windows
venv\Scripts\activate
# No Linux/Mac
source venv/bin/activate
```

3. Instale as dependências:
```
pip install -r requirements.txt
```

4. Configure as variáveis de ambiente (crie um arquivo `.env` na raiz do projeto):
```
SECRET_KEY=sua-chave-secreta
MP_ACCESS_TOKEN=seu-token-do-mercado-pago
MP_PUBLIC_KEY=sua-chave-publica-do-mercado-pago
PIX_KEY=sua-chave-pix
```

5. Inicialize o banco de dados:
```
flask db init
flask db migrate
flask db upgrade
```

6. Execute a aplicação:
```
python app.py
```

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