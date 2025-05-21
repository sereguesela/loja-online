# Loja Online PHP

Sistema completo de loja online desenvolvido em PHP com MySQL.

## Funcionalidades

- 🔐 **Sistema de autenticação** para administradores e clientes
- 🏪 **Catálogo de produtos** organizados por categoria
- 🛒 **Sistema de compras** com status de pedidos
- 💰 **Pagamentos via PIX**
- 💬 **Chat interno** entre administradores e clientes
- 📊 **Painel administrativo** para gerenciamento de produtos e pedidos

## Requisitos

- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Apache com mod_rewrite habilitado
- Extensões PHP:
  - PDO
  - PDO_MYSQL
  - GD (para manipulação de imagens)
  - mbstring
  - json

## Instalação Local

1. Clone o repositório:
```bash
git clone <seu-repositorio>
cd loja-online-php
```

2. Configure o banco de dados:
- Crie um banco de dados MySQL
- Importe o arquivo `config/init.sql`
- Copie `config/database.example.php` para `config/database.php`
- Configure as credenciais do banco em `config/database.php`

3. Configure as permissões:
```bash
chmod 755 -R .
chmod 777 -R uploads/
```

4. Configure o servidor web:
- Aponte o DocumentRoot para a pasta do projeto
- Certifique-se que o mod_rewrite está habilitado
- Permita o uso de .htaccess

## Instalação em Hospedagem Compartilhada

1. Faça upload dos arquivos:
- Faça upload de todos os arquivos para sua hospedagem
- Coloque os arquivos na pasta pública (geralmente `public_html`)

2. Configure o banco de dados:
- Crie um banco de dados no painel de controle da hospedagem
- Importe o arquivo `config/init.sql` usando phpMyAdmin
- Configure as credenciais em `config/database.php`

3. Ajuste as permissões:
- Pasta `uploads`: 777
- Demais arquivos e pastas: 755
- Arquivos de configuração: 644

## Configuração do Administrador

O sistema criará automaticamente um usuário administrador:
- Email: sereguesela@gmail.com
- A senha será gerada automaticamente e mostrada no primeiro acesso

## Estrutura de Arquivos

```
loja-online-php/
├── admin/           # Área administrativa
├── cliente/         # Área do cliente
├── assets/         # Arquivos estáticos (CSS, JS)
├── config/         # Configurações
├── includes/       # Arquivos incluídos
├── uploads/        # Upload de imagens
└── vendor/         # Dependências (se houver)
```

## Hospedagem Recomendada

O sistema foi projetado para funcionar em qualquer hospedagem PHP compartilhada. Recomendamos:

- [Hostgator](https://www.hostgator.com.br)
- [Locaweb](https://www.locaweb.com.br)
- [HostPapa](https://www.hostpapa.com.br)
- [GoDaddy](https://br.godaddy.com)

Todas essas hospedagens oferecem:
- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Certificado SSL gratuito
- Painel de controle cPanel

## Segurança

O sistema inclui várias medidas de segurança:
- Proteção contra CSRF
- Senhas criptografadas com bcrypt
- Proteção contra SQL Injection usando PDO
- Validação de entrada
- Headers de segurança
- Proteção de arquivos sensíveis

## Contribuição

Contribuições são bem-vindas! Sinta-se à vontade para abrir issues ou enviar pull requests.

## Licença

Este projeto está licenciado sob a licença MIT - veja o arquivo LICENSE.md para detalhes. 