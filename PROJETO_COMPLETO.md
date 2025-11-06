# MailJZTech - Microservice de Envio de E-mails

## Visão Geral

**MailJZTech** é um microservice robusto e minimalista de envio de e-mails desenvolvido em PHP, seguindo o padrão arquitetural do ClickExpress. O serviço foi projetado para simplificar o envio de e-mails em múltiplos sistemas através de uma API RESTful com autenticação por chave de API.

### Características Principais

- **API RESTful** com autenticação por API Key
- **Suporte a múltiplos sistemas** com chaves de API individuais
- **E-mail padrão** (`contato@jztech.com.br`) com nome de remetente personalizável
- **Recursos avançados**: CC, BCC, anexos múltiplos, corpo HTML e texto
- **Histórico de envios** com logs detalhados
- **Front-end minimalista** com Bootstrap 5 para gerenciamento
- **Banco de dados** com tabelas de sistemas, e-mails e logs
- **Documentação completa** da API

## Arquitetura

### Estrutura de Diretórios

```
MailJZTech/
├── api/
│   ├── core/                        # Framework base (Controllers, Models, etc)
│   ├── public/
│   │   └── assets/
│   │       ├── css/
│   │       │   ├── bootstrap.min.css
│   │       │   └── custom.css
│   │       └── js/
│   │           ├── bootstrap.bundle.min.js
│   │           └── custom.js
│   ├── src/
│   │   ├── controllers/
│   │   │   ├── EmailController.php
│   │   │   ├── SistemasController.php
│   │   │   └── ... (outros controllers)
│   │   ├── models/
│   │   │   ├── Sistemas.php
│   │   │   ├── Emails.php
│   │   │   ├── EmailLogs.php
│   │   │   └── ... (outros models)
│   │   ├── handlers/service/
│   │   │   ├── EmailService.php
│   │   │   └── ... (outros services)
│   │   ├── views/
│   │   │   ├── pages/
│   │   │   │   ├── sistemas.php
│   │   │   │   ├── criar_sistema.php
│   │   │   │   └── editar_sistema.php
│   │   │   └── partials/
│   │   │       ├── header.php
│   │   │       └── footer.php
│   │   ├── routes.php
│   │   ├── Config.php
│   │   └── Env.php
│   ├── SQL/
│   │   └── DDL_MAILJZTECH.sql
│   ├── composer.json
│   ├── .env
│   ├── .gitignore
│   ├── README.md
│   ├── SETUP.md
│   ├── API_DOCUMENTATION.md
│   └── PROJETO_COMPLETO.md
```

## Componentes Principais

### 1. Banco de Dados

#### Tabela: `sistemas`
Armazena informações dos sistemas que utilizam a API.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `idsistema` | INT | ID único do sistema |
| `nome` | VARCHAR(255) | Nome do sistema |
| `descricao` | TEXT | Descrição do sistema |
| `nome_remetente` | VARCHAR(255) | Nome que aparece como remetente |
| `email_remetente` | VARCHAR(255) | E-mail padrão (contato@jztech.com.br) |
| `chave_api` | VARCHAR(255) | Chave única para autenticação |
| `ativo` | BOOLEAN | Status do sistema |
| `data_criacao` | TIMESTAMP | Data de criação |
| `data_atualizacao` | TIMESTAMP | Data da última atualização |

#### Tabela: `emails_enviados`
Histórico de todos os e-mails enviados.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `idemail` | INT | ID único do e-mail |
| `idsistema` | INT | ID do sistema que enviou |
| `destinatario` | VARCHAR(255) | E-mail de destino |
| `cc` | JSON | E-mails em cópia |
| `bcc` | JSON | E-mails em cópia oculta |
| `assunto` | VARCHAR(255) | Assunto do e-mail |
| `corpo_html` | LONGTEXT | Corpo em HTML |
| `corpo_texto` | LONGTEXT | Corpo em texto puro |
| `anexos` | JSON | Informações dos anexos |
| `status` | VARCHAR(50) | Status (enviado, erro, pendente) |
| `mensagem_erro` | TEXT | Mensagem de erro (se houver) |
| `data_envio` | TIMESTAMP | Data de envio |
| `data_criacao` | TIMESTAMP | Data de criação |
| `data_atualizacao` | TIMESTAMP | Data da última atualização |

#### Tabela: `emails_logs`
Logs detalhados de operações.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `idlog` | INT | ID único do log |
| `idemail` | INT | ID do e-mail relacionado |
| `tipo` | VARCHAR(50) | Tipo de operação |
| `mensagem` | TEXT | Mensagem do log |
| `dados_adicionais` | JSON | Dados extras |
| `data_criacao` | TIMESTAMP | Data de criação |

### 2. API (Controllers)

#### EmailController
Responsável pelo envio e listagem de e-mails.

**Endpoints:**
- `POST /sendEmail` - Enviar novo e-mail
- `GET /listarEmails` - Listar e-mails do sistema
- `GET /detalheEmail` - Obter detalhes de um e-mail
- `GET /statsEmails` - Obter estatísticas
- `POST /testarEmail` - Enviar e-mail de teste

#### SistemasController
Gerenciamento de sistemas (CRUD).

**Endpoints:**
- `GET /listarSistemas` - Listar todos os sistemas
- `POST /criarSistema` - Criar novo sistema
- `PUT /atualizarSistema` - Atualizar sistema
- `DELETE /deletarSistema` - Deletar sistema (soft delete)
- `POST /regenerarChaveApi` - Gerar nova chave de API

### 3. Services

#### EmailService
Serviço responsável pelo envio de e-mails utilizando PHPMailer.

**Funcionalidades:**
- Envio via SMTP
- Suporte a HTML e texto puro
- CC e BCC múltiplos
- Anexos múltiplos
- Logging de operações
- Tratamento de erros

### 4. Front-end

#### Páginas
- **sistemas.php** - Listagem de sistemas com ações (editar, deletar, ver chave)
- **criar_sistema.php** - Formulário para cadastro de novo sistema
- **editar_sistema.php** - Formulário para edição com zona de perigo

#### Componentes Reutilizáveis
- **header.php** - Cabeçalho com navegação responsiva
- **footer.php** - Rodapé com scripts

#### Estilos e Scripts
- **Bootstrap 5** - Framework CSS responsivo
- **custom.css** - Estilos personalizados com tema gradient
- **custom.js** - Funções JavaScript utilitárias
- **Font Awesome 6** - Ícones modernos

## Autenticação

### Fluxo de Autenticação por API Key

1. **Cadastro do Sistema**: Administrador cria novo sistema no front-end
2. **Geração de Chave**: Sistema gera chave de API única e aleatória
3. **Armazenamento**: Chave é armazenada no banco de dados (hash)
4. **Uso**: Sistema externo inclui chave no header `Authorization: Bearer {chave_api}`
5. **Validação**: API valida chave antes de processar requisição

### Exemplo de Requisição Autenticada

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

## Configuração SMTP

### Gmail

1. Ative autenticação de dois fatores
2. Gere senha de app em https://myaccount.google.com/apppasswords
3. Configure no `.env`:

```env
EMAIL_API=seu_email@gmail.com
SENHA_EMAIL_API=sua_senha_app_16_caracteres
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
```

### Outros Provedores

Configure conforme as instruções do seu provedor SMTP no arquivo `.env`.

## Instalação e Setup

### Pré-requisitos
- PHP 7.4+
- MySQL 5.7+
- Composer
- Git

### Passos de Instalação

1. **Clonar repositório**
   ```bash
   git clone https://github.com/JZ-TECH-SYS/MailJZTech.git
   cd MailJZTech
   ```

2. **Instalar dependências**
   ```bash
   composer install
   ```

3. **Criar banco de dados**
   ```bash
   mysql -u root -p < SQL/DDL_MAILJZTECH.sql
   ```

4. **Configurar variáveis de ambiente**
   ```bash
   cp .env.example .env
   ```
   Edite `.env` com suas configurações SMTP e banco de dados.

5. **Iniciar servidor**
   ```bash
   php -S localhost:8000 -t public
   ```

A API estará disponível em `http://localhost:8000`

## Exemplos de Uso

### Enviar E-mail Simples

```bash
curl -X POST http://localhost:8000/sendEmail \
  -H "Authorization: Bearer sua_chave_api" \
  -H "Content-Type: application/json" \
  -d '{
    "destinatario": "usuario@example.com",
    "assunto": "Bem-vindo!",
    "corpo_html": "<h1>Olá!</h1><p>Bem-vindo ao sistema.</p>"
  }'
```

### Enviar E-mail com CC, BCC e Anexo

```bash
curl -X POST http://localhost:8000/sendEmail \
  -H "Authorization: Bearer sua_chave_api" \
  -H "Content-Type: application/json" \
  -d '{
    "destinatario": "usuario@example.com",
    "cc": ["gerente@example.com"],
    "bcc": ["arquivo@example.com"],
    "assunto": "Relatório Mensal",
    "corpo_html": "<h1>Relatório de Vendas</h1>",
    "corpo_texto": "Relatório de Vendas",
    "anexos": [
      {
        "nome": "relatorio.pdf",
        "caminho": "/path/to/relatorio.pdf"
      }
    ]
  }'
```

### Listar E-mails Enviados

```bash
curl -X GET "http://localhost:8000/listarEmails?limite=50&pagina=1" \
  -H "Authorization: Bearer sua_chave_api"
```

### Obter Estatísticas

```bash
curl -X GET http://localhost:8000/statsEmails \
  -H "Authorization: Bearer sua_chave_api"
```

## Estrutura de Resposta da API

### Sucesso (200)

```json
{
  "result": {
    "mensagem": "E-mail enviado com sucesso",
    "idemail": 1,
    "status": "enviado"
  },
  "error": false
}
```

### Erro (400+)

```json
{
  "result": "",
  "error": "Descrição do erro"
}
```

## Padrão de Desenvolvimento

O projeto segue o padrão **PMVC (PHP Model View Controller)** com as seguintes convenções:

- **Controllers**: Herdam de `core/Controller.php`
- **Models**: Herdam de `core/Model.php`
- **Views**: Organizadas em `pages/` e `partials/`
- **Services**: Lógica de negócio em `handlers/service/`
- **Resposta**: Sempre em JSON com estrutura `{ result, error }`

## Segurança

### Implementações

- **Autenticação por API Key**: Cada sistema tem chave única
- **Validação de entrada**: Todos os dados são validados
- **Proteção contra SQL Injection**: Uso de prepared statements
- **HTTPS**: Recomendado em produção
- **Rate Limiting**: Pode ser implementado conforme necessário

### Boas Práticas

- Guarde as chaves de API em local seguro
- Regenere chaves periodicamente
- Use HTTPS em produção
- Mantenha dependências atualizadas
- Implemente logging de segurança

## Monitoramento e Logs

O sistema registra:
- Todos os e-mails enviados
- Erros de envio
- Operações de gerenciamento
- Tentativas de acesso não autorizado

Acesse os logs através da tabela `emails_logs` ou do histórico na página de e-mails.

## Roadmap Futuro

- [ ] Dashboard com gráficos de envios
- [ ] Webhooks para notificações
- [ ] Agendamento de e-mails
- [ ] Templates de e-mail
- [ ] Rate limiting por sistema
- [ ] Relatórios avançados
- [ ] Integração com serviços de e-mail (SendGrid, Mailgun)
- [ ] API de webhook para eventos

## Suporte e Contribuição

Para suporte, entre em contato com: **contato@jztech.com.br**

Para contribuir, faça um fork do repositório e envie um pull request.

## Licença

Este projeto é propriedade da **JZ Tech Systems**.

## Repositório

**GitHub**: https://github.com/JZ-TECH-SYS/MailJZTech

## Versão

**Versão Atual**: 1.0.0  
**Data de Lançamento**: 6 de Novembro de 2025

---

**Desenvolvido com ❤️ por JZ Tech Systems**
