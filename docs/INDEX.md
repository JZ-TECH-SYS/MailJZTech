# MailJZTech – Documentação da API

Documentação essencial focada em como usar a API de envio de e-mails.

## 📖 Documentos Disponíveis

| Documento | Descrição |
|-----------|-----------|
| [REFERENCIA_API.md](REFERENCIA_API.md) | **✨ Comece aqui!** Endpoints, exemplos de código |
| [VISAO_GERAL.md](VISAO_GERAL.md) | Arquitetura e como o sistema funciona |
| [GUIA_IMPLANTACAO.md](GUIA_IMPLANTACAO.md) | Como fazer deploy em produção |
| [CONFIGURACAO_GITHUB_SECRETS.md](CONFIGURACAO_GITHUB_SECRETS.md) | Configurar variáveis de ambiente |
| [MIGRACAO_LOGS.md](MIGRACAO_LOGS.md) | Migração do tipo de log (se necessário) |

## � Quick Start

```bash
# 1. Instalar
composer install

# 2. Configurar banco
mysql -u root -p < SQL/DDL_MAILJZTECH.sql

# 3. Configurar .env
cp .env.example .env
# Edite o .env com suas credenciais

# 4. Rodar
php -S localhost:8050 -t public
```

## 📬 Como Enviar E-mail

```bash
curl -X POST http://localhost:8050/sendEmail \
  -H "Authorization: Bearer sua_chave_api" \
  -H "Content-Type: application/json" \
  -d '{
    "destinatario": "usuario@example.com",
    "assunto": "Teste",
    "corpo_html": "<h1>Olá!</h1><p>E-mail de teste</p>"
  }'
```

**Resposta:**

```json
{
  "result": {
    "idemail": 123,
    "status": "enviado",
    "mensagem": "E-mail enviado com sucesso"
  },
  "error": false
}
```

## � Principais Endpoints

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/sendEmail` | Enviar e-mail |
| GET | `/listarEmails` | Listar histórico de e-mails |
| POST | `/login` | Autenticar usuário |
| GET | `/dashboard` | Painel de controle |

## 📚 Mais Informações

- Veja todos os endpoints e exemplos em [REFERENCIA_API.md](REFERENCIA_API.md)
- Entenda a arquitetura em [VISAO_GERAL.md](VISAO_GERAL.md)
- Deploy em produção: [GUIA_IMPLANTACAO.md](GUIA_IMPLANTACAO.md)

---
Atualizado: 09/11/2025

