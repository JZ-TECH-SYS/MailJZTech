# MailJZTech - Serviço de Envio de E-mail

Um microservice robusto de envio de e-mails com suporte a múltiplos sistemas/clientes, anexos, cópia (CC/BCC), histórico de envios e autenticação de dois fatores (2FA) obrigatória.

## Características

- **API RESTful** simples e direta em PHP puro
- **Autenticação por API Key** para cada sistema
- **2FA Obrigatório** com TOTP (Google/Microsoft Authenticator)
- **Suporte a:**
  - E-mails HTML e texto puro
  - Anexos (múltiplos)
  - Cópia (CC) e Cópia Oculta (BCC)
  - Personalização do nome do remetente
  - E-mail padrão: `contato@jztech.com.br`
- **Histórico completo** de e-mails enviados
- **Logs detalhados** de operações
- **Dashboard responsivo** para gerenciamento de sistemas
- **Documentação integrada** no sistema

## Estrutura do Projeto

```
MailJZTech/
├── core/                        # Framework base PMVC
│   ├── Controller.php           # Classe base de controllers
│   ├── Model.php                # Classe base de models
│   ├── Database.php             # Conexão com banco
│   ├── Router.php               # Sistema de rotas
│   └── Auth.php                 # Autenticação
├── src/
│   ├── controllers/             # Controllers da API
│   │   ├── EmailController.php
│   │   ├── SistemasController.php
│   │   └── LoginController.php
│   ├── models/                  # Models do banco
│   │   ├── Sistemas.php
│   │   ├── Emails.php
│   │   ├── EmailLogs.php
│   │   └── Usuario.php
│   ├── handlers/
│   │   └── service/
│   │       ├── EmailService.php
│   │       └── TwoFactorAuthService.php
│   ├── views/                   # Templates HTML
│   │   ├── pages/
│   │   │   ├── login.php
│   │   │   ├── dashboard.php
│   │   │   ├── sistemas.php
│   │   │   ├── criar_sistema.php
│   │   │   ├── editar_sistema.php
│   │   │   ├── emails.php
│   │   │   ├── logs.php
│   │   │   ├── documentacao.php
│   │   │   ├── configurar_2fa.php
│   │   │   └── verificar_2fa.php
│   │   └── partials/
│   │       ├── header.php
│   │       └── footer.php
│   ├── Config.php               # Configurações
│   ├── Env.php                  # Carregamento de .env
│   └── routes.php               # Definição de rotas
├── public/
│   ├── index.php                # Entry point
│   └── assets/
│       ├── css/
│       │   ├── bootstrap.min.css
│       │   └── custom.css
│       └── js/
│           ├── bootstrap.bundle.min.js
│           ├── chart.js
│           └── custom.js
├── SQL/
│   ├── DDL_MAILJZTECH.sql       # Tabelas específicas
│   └── ALTER_2FA.sql            # Alterações para 2FA
├── composer.json                # Dependências PHP
├── .env                         # Variáveis de ambiente
├── .htaccess                    # Configuração Apache
├── .gitignore
├── SETUP.md                     # Guia de instalação
├── API_DOCUMENTATION.md         # Documentação da API
├── 2FA_IMPLEMENTATION.md        # Documentação de 2FA
└── README.md
```

## Pré-requisitos

- **PHP 7.4+** (ou superior)
- **MySQL 5.7+** ou **MariaDB 10.3+**
- **Composer** (para dependências PHP)
- **Apache** com módulo `mod_rewrite` ativado (ou outro servidor web com suporte a rewrite)

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

### 3. Configurar variáveis de ambiente

```bash
cp .env.example .env
# Editar .env com suas configurações
```

**Variáveis importantes:**

```env
# Banco de dados
DB_HOST=localhost
DB_USER=root
DB_PASS=sua_senha
DB_NAME=mailjztech

# SMTP (para envio de e-mails)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=seu_email@gmail.com
SMTP_PASS=sua_senha_app

# E-mail padrão
EMAIL_PADRAO=contato@jztech.com.br
NOME_PADRAO=MailJZTech

# Segurança
JWT_SECRET=sua_chave_secreta_aqui
```

### 4. Criar banco de dados

```bash
mysql -u root -p < SQL/DDL_MAILJZTECH.sql
```

Se você já tem um banco com tabelas de usuários, execute apenas as alterações de 2FA:

```bash
mysql -u root -p seu_banco < SQL/ALTER_2FA.sql
```

### 5. Iniciar o servidor de desenvolvimento

```bash
# Opção 1: PHP built-in server
php -S localhost:8000 -t public

# Opção 2: Apache (configure o DocumentRoot para a pasta 'public')
# Acesse: http://localhost/MailJZTech
```

Acesse: **http://localhost:8000**

## Uso da API

### Autenticação

Todas as requisições devem incluir a chave de API no header:

```bash
Authorization: Bearer sua_chave_api_aqui
```

### Endpoints Principais

#### 1. Enviar E-mail

**POST** `/sendEmail`

```bash
curl -X POST http://localhost:8000/sendEmail \
  -H "Authorization: Bearer sua_chave_api" \
  -H "Content-Type: application/json" \
  -d '{
    "destinatario": "usuario@example.com",
    "assunto": "Olá!",
    "corpo_html": "<h1>Bem-vindo!</h1>",
    "cc": ["cc@example.com"],
    "bcc": ["bcc@example.com"]
  }'
```

**Resposta (200):**

```json
{
  "result": {
    "idemail": 1,
    "status": "enviado",
    "mensagem": "E-mail enviado com sucesso"
  },
  "error": false
}
```

#### 2. Listar E-mails

**GET** `/listarEmails?limite=50&pagina=1`

```bash
curl -X GET "http://localhost:8000/listarEmails" \
  -H "Authorization: Bearer sua_chave_api"
```

#### 3. Criar Sistema

**POST** `/criarSistema`

```bash
curl -X POST http://localhost:8000/criarSistema \
  -H "Content-Type: application/json" \
  -d '{
    "nome": "Meu Sistema",
    "descricao": "Descrição do sistema",
    "nome_remetente": "Meu Sistema"
  }'
```

Para mais endpoints, consulte a documentação integrada no dashboard.

## Autenticação de Dois Fatores (2FA)

O MailJZTech implementa 2FA **obrigatório** usando TOTP:

### Fluxo de Login

1. **Primeiro Login** - Usuário faz login com email/senha
2. **Modal 2FA** - Aparece modal na mesma página para escanear QR Code
3. **Configuração** - Escaneia com Google/Microsoft Authenticator
4. **Verificação** - Insere código de 6 dígitos
5. **Acesso** - 2FA ativado permanentemente, acessa dashboard

### Logins Subsequentes

- Sempre solicita código TOTP (6 dígitos)
- Fallback: Usar códigos de backup se perder autenticador
- 2FA não pode ser desativado (obrigatório)

Para detalhes técnicos, consulte: [2FA_IMPLEMENTATION.md](2FA_IMPLEMENTATION.md)

## Dashboard

Acesse o dashboard em: **http://localhost:8000/dashboard**

### Funcionalidades

- **Dashboard** - Estatísticas e gráficos de envios
- **Sistemas** - Gerenciar sistemas/clientes
- **E-mails** - Histórico de envios
- **Logs** - Acompanhamento de operações
- **Documentação** - Guia de uso da API integrado

## Desenvolvimento

### Adicionar novo endpoint

1. Criar método no controller em `src/controllers/`
2. Adicionar rota em `src/routes.php`
3. Implementar lógica em `src/handlers/service/`
4. Atualizar views conforme necessário

### Padrão de Response

```php
// Sucesso
$this->response(['mensagem' => 'Operação realizada'], 200);

// Erro
$this->rejectResponse(new Exception('Mensagem de erro'));
```

### Padrão PMVC

O projeto segue o padrão **PMVC** (Presentation-Model-View-Controller):

- **Controllers** (`src/controllers/`) - Lógica de requisição
- **Models** (`src/models/`) - Acesso ao banco de dados
- **Views** (`src/views/`) - Templates HTML
- **Services** (`src/handlers/service/`) - Lógica de negócio

## Banco de Dados

### Tabelas Principais

- **usuarios** - Usuários do sistema
- **sistemas** - Sistemas/clientes cadastrados
- **emails_enviados** - Histórico de e-mails
- **emails_logs** - Logs detalhados de operações

## Documentação

- **[SETUP.md](SETUP.md)** - Guia detalhado de instalação
- **[API_DOCUMENTATION.md](API_DOCUMENTATION.md)** - Documentação completa da API
- **[2FA_IMPLEMENTATION.md](2FA_IMPLEMENTATION.md)** - Detalhes técnicos de 2FA
- **Dashboard** - Documentação integrada no sistema

## Licença

Propriedade da **JZ Tech Systems**

## Suporte

Para suporte, entre em contato com: **contato@jztech.com.br**

---

**Desenvolvido com ❤️ por JZ Tech Systems**

Versão: 1.0.0 | Data: Novembro 2025
