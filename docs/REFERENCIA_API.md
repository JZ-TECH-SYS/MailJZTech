# Referência da API – MailJZTech

Todas as respostas seguem o formato:

```json
{ "result": <dados|mensagem>, "error": false }
```

Autenticação para rotas privadas: header `Authorization: Bearer <token>`.

## Autenticação e 2FA

- POST `/login`
- POST `/iniciar-2fa`
- POST `/confirmar-2fa`
- POST `/verificar-2fa`
- POST `/verificar-2fa-backup`
- GET `/sair` (privado)

## Dashboard

- GET `/dashboard` (privado)
- GET `/api/dashboard/stats` (privado)

## Logs

- GET `/logs` (privado)
- GET `/api/logs/listar` (privado)
- GET `/api/logs/detalhe/{id}` (privado)

## Sistemas

- GET `/sistemas` (privado)
- GET `/criar-sistema` (privado)
- GET `/editar-sistema/{idsistema}` (privado)
- POST `/criarSistema` (privado)
- PUT `/atualizarSistema/{idsistema}` (privado)
- DELETE `/deletarSistema/{idsistema}` (privado)
- GET `/listarSistemas` (privado)
- GET `/obterSistema/{idsistema}` (privado)
- POST `/regenerarChaveApi/{idsistema}` (privado)

## E-mails

- GET `/emails` (privado)
- POST `/sendEmail`
- GET `/listarEmails` (privado)
- GET `/detalheEmail/{idemail}` (privado)
- GET `/statsEmails` (privado)
- POST `/testarEmail` (privado)
- GET `/validarConfigEmail`

## Exemplos de Requisição

### cURL – Enviar E-mail

```bash
curl -X POST http://localhost:8050/sendEmail \
  -H "Authorization: Bearer <TOKEN_AQUI>" \
  -H "Content-Type: application/json" \
  -d '{
    "destinatario": "usuario@example.com",
    "assunto": "Olá!",
    "corpo_html": "<h1>Bem-vindo!</h1>",
    "cc": ["cc@example.com"],
    "bcc": ["bcc@example.com"]
  }'
```

### JavaScript Fetch – Enviar E-mail

```js
await fetch('http://localhost:8050/sendEmail', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer <TOKEN_AQUI>',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    destinatario: 'usuario@example.com',
    assunto: 'Olá!',
    corpo_html: '<h1>Bem-vindo!</h1>',
    cc: ['cc@example.com'],
    bcc: ['bcc@example.com']
  })
});
```

### PowerShell – Enviar E-mail

```powershell
$headers = @{ Authorization = 'Bearer <TOKEN_AQUI>'; 'Content-Type' = 'application/json' }
$body = @{ destinatario='usuario@example.com'; assunto='Olá!'; corpo_html='<h1>Bem-vindo!</h1>' } | ConvertTo-Json
Invoke-RestMethod -Method Post -Uri 'http://localhost:8050/sendEmail' -Headers $headers -Body $body
```
