# MailJZTech – Documentação Básica da API

Objetivo: acesso rápido à API. Só dois arquivos são mantidos:

1. Este `INDEX.md` (visão rápida)
2. `REFERENCIA_API.md` (detalhe completo de cada endpoint)

## Autenticação

Use header:

```http
Authorization: Bearer <sua_chave_api>
Content-Type: application/json
```

Chave é emitida ao criar o sistema no painel. 2FA obrigatório para acesso ao painel web.

## Fluxo Simplificado

Cliente → Controller → Handler → Service (SMTP) → grava sucesso em `emails_enviados`.

Sem gravação de e-mail se falhar; apenas log mínimo de erro.

## Endpoints Principais

| Ação | Método | Caminho |
|------|--------|---------|
| Enviar e-mail | POST | /sendEmail |
| Listar e-mails | GET | /listarEmails |
| Detalhe e-mail | GET | /detalheEmail?idemail=ID |
| Testar SMTP | POST | /api/emails/testar |
| Logs recentes | GET | /api/logs/recentes |
| Login | POST | /login |

Mais exemplos e parâmetros: ver `REFERENCIA_API.md`.

## Exemplo Rápido (cURL)

```bash
curl -X POST http://localhost:8050/sendEmail \
	-H "Authorization: Bearer SUA_CHAVE" \
	-H "Content-Type: application/json" \
	-d '{
		"destinatario": "usuario@example.com",
		"assunto": "Bem-vindo",
		"corpo_html": "<h1>Olá</h1><p>Teste</p>"
	}'
```

Resposta (200):

```json
{
	"result": {
		"idemail": 42,
		"status": "enviado",
		"mensagem": "E-mail enviado com sucesso"
	},
	"error": false
}
```

## Status Possíveis

| status | significado |
|--------|-------------|
| enviado | SMTP OK e persistido |
| erro | falha no envio |

## Erros Comuns

| Código | Motivo | Correção |
|--------|--------|----------|
| 400 | Campo obrigatório faltando | Verifique JSON |
| 401 | Token inválido/ausente | Ajustar header Authorization |
| 500 | Falha interna SMTP | Conferir credenciais .env |

## Setup Rápido

```bash
composer install
cp .env.example .env
mysql -u root -p < SQL/DDL_MAILJZTECH.sql
php -S localhost:8050 -t public
```

## Convenções Essenciais

| Regra | Descrição |
|-------|-----------|
| Controller → Handler | Nunca chama Model direto |
| SQL pesado | Colocar em `SQL/*.sql` e usar switchParams |
| Logs | Registrar só sucesso/erro crítico |

## Atualização

Última revisão: 09/11/2025

