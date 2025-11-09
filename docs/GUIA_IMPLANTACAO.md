# Guia de Implantação – MailJZTech

## Pré-requisitos

- PHP 8.x
- MySQL/MariaDB
- Composer
- Servidor web (Apache/Nginx) apontando para `public/`

## Passos

1. Clonar repositório
2. `composer install`
3. Configurar `.env`
4. Executar DDL em `SQL/DDL_MAILJZTECH.sql`
5. Configurar VirtualHost / Server block para `public/`
6. Ajustar permissões de `logs/` (gravável)
7. Acessar `http://host/login` e concluir fluxo de 2FA

## Variáveis .env (exemplo)

```env
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=mailjz
DB_USER=root
DB_PASS=senha
BASE_DIR=/
SMTP_HOST=smtp.seudominio.com
SMTP_PORT=587
EMAIL_API=contato@seudominio.com
SENHA_EMAIL_API=senha_app
TOKEN_JV=token_fixo_opcional
```

## Produção

- Forçar HTTPS
- Rotacionar tokens/API Keys periodicamente
- Ativar logs de execução de SQL complexos apenas para diagnóstico
- Monitorar tamanho de `logs/app.log` (criar job de rotação)

## Backup & Recuperação

- Dump diário das tabelas principais (`sistemas`, `emails`, `emails_logs`, `usuarios`)
- Armazenar fora do servidor de aplicação

## Observabilidade

- Métricas básicas podem ser extraídas de `/statsEmails` e agregadas externamente.

