# MailJZTech – Serviço de Envio de E-mail com API REST

Plataforma robusta de envio de e-mails com painel web, API REST, autenticação por token, 2FA obrigatório e histórico completo de operações.

## 🚀 Quick Start

```bash
# 1. Clonar
git clone https://github.com/JZ-TECH-SYS/MailJZTech.git
cd MailJZTech

# 2. Instalar dependências
composer install

# 3. Configurar .env (ver docs/CONFIGURACAO_GITHUB_SECRETS.md)
cp .env.example .env
# Editar .env com suas credenciais

# 4. Criar banco de dados
mysql -u root -p < SQL/DDL_MAILJZTECH.sql

# 5. Iniciar servidor
php -S localhost:8050 -t public
```

Acesse: **http://localhost:8050**

## 📚 Documentação

Toda documentação está em `docs/`. Comece por aqui:

| Documento | Conteúdo |
|-----------|----------|
| [📘 docs/REFERENCIA_API.md](docs/REFERENCIA_API.md) | **⭐ Comece aqui!** Endpoints, exemplos de uso |
| [docs/VISAO_GERAL.md](docs/VISAO_GERAL.md) | Arquitetura e como o sistema funciona |
| [docs/GUIA_IMPLANTACAO.md](docs/GUIA_IMPLANTACAO.md) | Deploy em produção |
| [docs/BACKUP_AUTOMATIZADO.md](docs/BACKUP_AUTOMATIZADO.md) | Sistema de backup MySQL → GCS com retenção |
| [docs/CONFIGURACAO_GITHUB_SECRETS.md](docs/CONFIGURACAO_GITHUB_SECRETS.md) | Variáveis de ambiente e CI/CD |
| [docs/INDEX.md](docs/INDEX.md) | Índice completo da documentação |

## ✨ Características

- ✅ **API REST** com autenticação por Bearer Token
- ✅ **2FA obrigatório** (TOTP com Authenticator app)
- ✅ **Envio de e-mails** (HTML, texto, CC/BCC, anexos)
- ✅ **Dashboard responsivo** para gerenciamento
- ✅ **Histórico completo** de envios e logs
- ✅ **Múltiplos sistemas** com chave API individual
- ✅ **Backup automatizado** (MySQL → GCS com compressão e retenção)
- ✅ **CI/CD automático** (GitHub Actions → FTP)

## 🏗️ Estrutura

```
core/               # Framework base (Router, Controller, Model, Auth)
src/
  ├── controllers/  # Lógica de requisição
  ├── models/       # Acesso ao banco (Hydrahon Query Builder)
  ├── handlers/     # Regras de negócio
  ├── views/        # Templates PHP + Bootstrap
  └── routes.php    # Definição de rotas
public/
  └── assets/       # CSS, JS (Bootstrap, Charts)
SQL/                # Scripts DDL e queries complexas
docs/               # Documentação (centralizada)
```

## 🔐 Autenticação

- **Rotas privadas**: Exigem `Authorization: Bearer <token>` no header
- **2FA**: Obrigatório no painel web (fluxo TOTP)
- **Session**: Mantida via cookie de sessão
- **Configuração**: Tokens via `.env` ou banco de dados

## 💡 Exemplo de Requisição

```bash
# Enviar e-mail
curl -X POST http://localhost:8050/sendEmail \
  -H "Authorization: Bearer <seu-token>" \
  -H "Content-Type: application/json" \
  -d '{
    "destinatario": "usuario@example.com",
    "assunto": "Olá!",
    "corpo_html": "<h1>Bem-vindo!</h1>"
  }'
```

Ver mais exemplos em [docs/REFERENCIA_API.md](docs/REFERENCIA_API.md).

## 🛠️ Desenvolvimento

1. **Controllers**: `src/controllers/` com try/catch
2. **Models**: Herdam de `core\Model` (Hydrahon)
3. **Handlers**: Regras de negócio em `src/handlers/`
4. **Services**: E-mail, 2FA em `src/handlers/service/`
5. **Respostas**: Padrão `{ result: <dados>, error: false|true }`

## 📋 Variáveis de Ambiente

Configuradas em `.env` ou via **GitHub Secrets** (ver [docs/CONFIGURACAO_GITHUB_SECRETS.md](docs/CONFIGURACAO_GITHUB_SECRETS.md)):

```env
DB_HOST=localhost
DB_USER=root
DB_PASS=senha
SMTP_HOST=smtp.seu-dominio.com
TOKEN_JV=seu-token-fixo
```

## 🚀 Deploy

O projeto usa **GitHub Actions** para deploy automático. Cada push para `main`:

1. Gera `.env` dinamicamente com os secrets
2. Faz upload via FTP para o servidor
3. Exclui `.git` e `.github` do deploy

Veja: [docs/CONFIGURACAO_GITHUB_SECRETS.md](docs/CONFIGURACAO_GITHUB_SECRETS.md)

## 📞 Suporte

- Documentação: consulte o índice em [docs/INDEX.md](docs/INDEX.md)
- Contato: <contato@jztech.com.br>
- Issues: GitHub Repository

## 📄 Licença

Propriedade da **JZ Tech Systems**

---

**Versão**: 1.0.1 | **Data**: Novembro 2025 | **Desenvolvido com ❤️ por JZ Tech Systems**

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

Documentação consolidada (PT-BR) na pasta `docs/`:

- [docs/VISAO_GERAL.md](docs/VISAO_GERAL.md): visão de arquitetura e fluxos.
- [docs/REFERENCIA_API.md](docs/REFERENCIA_API.md): endpoints e exemplos de requisição.
- [docs/GUIA_IMPLANTACAO.md](docs/GUIA_IMPLANTACAO.md): instalação e práticas de produção.

Arquivos antigos como `API_DOCUMENTATION.md`, `SETUP.md`, `PRODUCTION_GUIDE.md`, `INFRASTRUCTURE.md`, `QUICK_START.md` podem ser descontinuados após validação.

## Licença

Propriedade da **JZ Tech Systems**

## Suporte

Para suporte, entre em contato com: **contato@jztech.com.br**

---

**Desenvolvido com ❤️ por JZ Tech Systems**

Versão: 1.0.1 | Data: Novembro 2025
