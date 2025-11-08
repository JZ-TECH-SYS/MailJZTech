# Configuração de Secrets no GitHub

Para que o deploy automático gere `.env` corretamente, adicione estes secrets no GitHub:

Caminho: **Settings > Secrets and variables > Actions > New repository secret**

## Secrets obrigatórios

### Credenciais FTP (para deploy)

- `FTP_SERVER`: ex. `ftp.mailjztech.com`
- `FTP_USERNAME`: ex. `seu_usuario_ftp`
- `FTP_PASSWORD`: sua senha FTP

### Configuração de Banco de Dados

- `DB_DRIVER`: `mysql`
- `DB_HOST`: ex. `mysql.mailjztech.com`
- `DB_PORT`: `3306`
- `DB_DATABASE`: ex. `mailjz_prod`
- `DB_USER`: ex. `mailjz_user`
- `DB_PASS`: senha do banco

### Configuração de E-mail (SMTP)

- `EMAIL_API`: ex. `contato@mailjztech.com`
- `SENHA_EMAIL_API`: senha SMTP (da conta de e-mail)
- `SMTP_HOST`: ex. `smtp.hostinger.com`
- `SMTP_PORT`: `587` ou `465`

### Configuração da Aplicação

- `BASE_DIR`: `/` ou caminho customizado
- `FRONT_URL`: URL do front-end (ex. `https://mailjztech.com`)
- `TOKEN_JV`: token fixo para Bearer (ex. `gerar_com_uuid()`)

### Credenciais Adicionais (se aplicável)

- `USER_MASTER_DB`: usuário master do banco
- `PASS_MASTER_DB`: senha master do banco

## Processo

1. Após adicionar os secrets no GitHub, faça um **push** para a branch `main`.
2. O workflow `deploy.yml` irá:
   - Gerar `.env` dinamicamente com os valores dos secrets
   - Fazer upload do projeto completo para o servidor FTP
   - Excluir `.git` e `.github` do upload

3. Na primeira execução em produção, execute manualmente:

```bash
composer install
mysql -u $DB_USER -p$DB_PASS $DB_DATABASE < SQL/DDL_MAILJZTECH.sql
```

## Segurança

- O `.env` **nunca** é commitado (protegido por `.gitignore`)
- Os secrets são encriptados no GitHub
- Cada deploy gera um `.env` novo baseado nos secrets atuais
- Não adicione `.env` local em controle de versão

## Testar localmente

Para testar o workflow sem fazer deploy real, edite `.github/workflows/deploy.yml` e substitua:

```yaml
- name: Upload do projeto completo
  uses: SamKirkland/FTP-Deploy-Action@v4.3.4
```

por:

```yaml
- name: Listar arquivos (teste)
  run: ls -la
```

Depois revert para o código original.
