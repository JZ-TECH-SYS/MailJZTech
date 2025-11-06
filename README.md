# MailJZTech - Serviço de Envio de E-mail

Um serviço robusto de envio de e-mails com suporte a múltiplos sistemas/clientes, anexos, cópia (CC/BCC) e histórico de envios.

## Características

- **API RESTful** simples e direta
- **Autenticação por API Key** para cada sistema
- **Suporte a:**
  - E-mails HTML e texto puro
  - Anexos (múltiplos)
  - Cópia (CC) e Cópia Oculta (BCC)
  - Personalização do nome do remetente
  - E-mail padrão: `contato@gztech.com.br`
- **Histórico completo** de e-mails enviados
- **Logs detalhados** de operações
- **Dashboard minimalista** para gerenciamento de sistemas

## Estrutura do Projeto

```
MailJZTech/
├── api/
│   ├── core/                    # Framework base
│   │   ├── Controller.php       # Classe base de controllers
│   │   ├── Model.php            # Classe base de models
│   │   ├── Database.php         # Conexão com banco
│   │   ├── Router.php           # Sistema de rotas
│   │   └── Auth.php             # Autenticação
│   ├── src/
│   │   ├── controllers/         # Controllers da API
│   │   │   ├── EmailController.php
│   │   │   ├── SistemasController.php
│   │   │   └── ...
│   │   ├── models/              # Models do banco
│   │   │   ├── Sistemas.php
│   │   │   ├── Emails.php
│   │   │   └── ...
│   │   ├── handlers/            # Lógica de negócio
│   │   │   ├── service/
│   │   │   │   └── EmailService.php
│   │   │   └── ...
│   │   ├── Config.php           # Configurações
│   │   ├── Env.php              # Carregamento de .env
│   │   └── routes.php           # Definição de rotas
│   ├── public/
│   │   └── index.php            # Entry point
│   ├── SQL/
│   │   ├── DDL.sql              # Schema do banco
│   │   └── DDL_MAILJZTECH.sql   # Tabelas específicas
│   ├── composer.json            # Dependências PHP
│   ├── .env                     # Variáveis de ambiente
│   └── .htaccess                # Configuração Apache
├── web/                         # Front-end (React/HTML)
│   ├── src/
│   │   ├── pages/
│   │   ├── components/
│   │   └── ...
│   └── ...
└── README.md
```

## Instalação

### Pré-requisitos

- PHP 7.4+
- MySQL 5.7+
- Composer
- Node.js (para o front-end)

### Setup

1. **Clonar o repositório:**
   ```bash
   git clone https://github.com/JZ-TECH-SYS/MailJZTech.git
   cd MailJZTech
   ```

2. **Instalar dependências PHP:**
   ```bash
   cd api
   composer install
   ```

3. **Configurar variáveis de ambiente:**
   ```bash
   cp .env.example .env
   # Editar .env com suas configurações
   ```

4. **Criar banco de dados:**
   ```bash
   mysql -u root -p < SQL/DDL_MAILJZTECH.sql
   ```

5. **Instalar dependências do front-end:**
   ```bash
   cd ../web
   npm install
   # ou
   pnpm install
   ```

6. **Iniciar o servidor de desenvolvimento:**
   ```bash
   # Terminal 1 - API PHP
   cd api
   php -S localhost:8000 -t public

   # Terminal 2 - Front-end
   cd web
   npm run dev
   ```

## Uso da API

### Autenticação

Todas as requisições devem incluir a chave de API no header:

```bash
Authorization: Bearer sua_chave_api_aqui
```

### Endpoints

#### 1. Enviar E-mail

**POST** `/sendEmail`

```json
{
  "destinatario": "usuario@example.com",
  "assunto": "Olá!",
  "corpo_html": "<h1>Bem-vindo!</h1>",
  "corpo_texto": "Bem-vindo!",
  "cc": ["cc@example.com"],
  "bcc": ["bcc@example.com"],
  "anexos": [
    {
      "nome": "documento.pdf",
      "caminho": "/path/to/file.pdf"
    }
  ]
}
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

**Resposta (200):**
```json
{
  "result": [
    {
      "idemail": 1,
      "destinatario": "usuario@example.com",
      "assunto": "Olá!",
      "status": "enviado",
      "data_envio": "2025-11-06 10:30:00"
    }
  ],
  "error": false
}
```

#### 3. Obter Detalhes do E-mail

**GET** `/detalheEmail/{idemail}`

#### 4. Listar Sistemas (Admin)

**GET** `/listarSistemas`

#### 5. Criar Sistema (Admin)

**POST** `/criarSistema`

```json
{
  "nome": "Meu Sistema",
  "descricao": "Descrição do sistema",
  "nome_remetente": "Meu Sistema"
}
```

## Configuração SMTP

Edite o arquivo `.env` com suas credenciais SMTP:

```env
EMAIL_API=seu_email@gmail.com
SENHA_EMAIL_API=sua_senha_app
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
```

## Banco de Dados

### Tabelas Principais

- **sistemas**: Cadastro de sistemas/clientes
- **emails_enviados**: Histórico de e-mails
- **emails_logs**: Logs detalhados

## Desenvolvimento

### Adicionar novo endpoint

1. Criar método no controller em `src/controllers/`
2. Adicionar rota em `src/routes.php`
3. Implementar lógica de negócio em `src/handlers/`
4. Atualizar o front-end conforme necessário

### Padrão de Response

```php
// Sucesso
Controller::response(['mensagem' => 'Operação realizada'], 200);

// Erro
Controller::rejectResponse(new Exception('Mensagem de erro'));
```

## Licença

Propriedade da JZ Tech Systems

## Suporte

Para suporte, entre em contato com: contato@gztech.com.br
