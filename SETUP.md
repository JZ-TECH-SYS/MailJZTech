# MailJZTech - Guia de Setup

## Pré-requisitos

- PHP 7.4+
- MySQL 5.7+
- Composer
- Git

## Instalação

### 1. Clonar o repositório

```bash
git clone https://github.com/JZ-TECH-SYS/MailJZTech.git
cd MailJZTech
```

### 2. Instalar dependências PHP

```bash
composer install
```

### 3. Configurar banco de dados

Crie um banco de dados MySQL:

```bash
mysql -u root -p < SQL/DDL_MAILJZTECH.sql
```

### 4. Configurar variáveis de ambiente

Copie o arquivo `.env.example` para `.env` e configure:

```bash
cp .env.example .env
```

Edite o arquivo `.env` com suas configurações SMTP:

```env
# Email Configuration
EMAIL_API=contato@jztech.com.br
SENHA_EMAIL_API=sua_senha_app_aqui
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587

# Database
DB_HOST=localhost
DB_USER=root
DB_PASSWORD=sua_senha
DB_NAME=mailjztech
```

### 5. Iniciar o servidor

```bash
php -S localhost:8000 -t public
```

A API estará disponível em `http://localhost:8000`

## Estrutura do Projeto

```
MailJZTech/
├── api/
│   ├── core/                    # Framework base
│   ├── src/
│   │   ├── controllers/         # Controllers da API
│   │   ├── models/              # Models do banco
│   │   ├── handlers/service/    # Serviços de negócio
│   │   └── routes.php           # Definição de rotas
│   ├── public/
│   │   └── index.php            # Entry point
│   ├── SQL/
│   │   └── DDL_MAILJZTECH.sql   # Schema do banco
│   ├── composer.json            # Dependências PHP
│   └── .env                     # Variáveis de ambiente
└── README.md
```

## Autenticação por API Key

Todos os endpoints da API (exceto login) requerem autenticação por API Key.

### Obter uma chave de API

1. Crie um sistema através do endpoint `/criarSistema` (requer login)
2. A chave será gerada automaticamente
3. Use a chave no header `Authorization: Bearer {chave_api}`

### Exemplo de requisição

```bash
curl -X POST http://localhost:8000/sendEmail \
  -H "Authorization: Bearer sua_chave_api_aqui" \
  -H "Content-Type: application/json" \
  -d '{
    "destinatario": "usuario@example.com",
    "assunto": "Olá!",
    "corpo_html": "<h1>Bem-vindo!</h1>"
  }'
```

## Endpoints Principais

### Envio de E-mails

- **POST** `/sendEmail` - Enviar e-mail
- **GET** `/listarEmails` - Listar e-mails
- **GET** `/detalheEmail` - Detalhes de um e-mail
- **GET** `/statsEmails` - Estatísticas

### Gerenciamento de Sistemas (Admin)

- **GET** `/listarSistemas` - Listar sistemas
- **POST** `/criarSistema` - Criar novo sistema
- **PUT** `/atualizarSistema` - Atualizar sistema
- **DELETE** `/deletarSistema` - Deletar sistema
- **POST** `/regenerarChaveApi` - Gerar nova chave de API

## Configuração SMTP

### Gmail

1. Ative a autenticação de dois fatores
2. Gere uma senha de app em https://myaccount.google.com/apppasswords
3. Use a senha de app no `.env`

```env
EMAIL_API=seu_email@gmail.com
SENHA_EMAIL_API=sua_senha_app_16_caracteres
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
```

### Outros provedores

Configure conforme as instruções do seu provedor SMTP.

## Documentação da API

Veja `API_DOCUMENTATION.md` para documentação completa de todos os endpoints.

## Suporte

Para suporte, entre em contato com: contato@jztech.com.br
