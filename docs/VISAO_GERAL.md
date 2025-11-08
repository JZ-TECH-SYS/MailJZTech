# MailJZTech – Visão Geral

Uma plataforma de envio de e-mails com painel web e API REST. Estruturada em PHP (MVC leve), com autenticação por token/Bearer e 2FA obrigatório.

## Arquitetura

- Front-end (Views): PHP + Bootstrap em `src/views`, JS em `public/assets/js`.
- Back-end (API): Controllers em `src/controllers`, regras em `src/handlers`, models em `src/models` usando Hydrahon.
- Serviços: `src/handlers/service` (EmailService, TwoFactorAuthService).
- Roteamento: `core/Router*` e `src/routes.php`.
- Banco: scripts em `SQL/`.
- Logs: `logs/app.log` e logs SQL opcionais via `switchParams`.

## Fluxos Principais

- Login + 2FA (TOTP): páginas `login.php`, `configurar_2fa.php`, `verificar_2fa.php`.
- Gestão de sistemas: `sistemas.php`, criação/edição, chave API.
- Envio de e-mail: endpoint `/sendEmail` + painel de histórico.

## Padrões de Código

- Controllers com try/catch e respostas padronizadas (`Controller::response` e `rejectResponse`).
- Handlers/Services com métodos estáticos; Models preferem API estática de `core/Model`.
- Rotas com path parameters: `/recurso/{id}`.

## Autenticação

- Rotas privadas exigem `Authorization: Bearer <token>`.
- 2FA obrigatório no login de usuários do painel.

## Convenções de Resposta

```json
{ "result": "<dados|mensagem>", "error": false }
```

## Ambiente

- Config via `.env` carregado por `src/Env.php`.
- `public/index.php` é o entrypoint (definir DocumentRoot para `public/`).

