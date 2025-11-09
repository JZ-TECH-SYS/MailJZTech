# Sistema de Backup Automatizado - MailJZTech

Sistema completo de backup automatizado de bancos de dados MySQL com upload para Google Cloud Storage.

## 📋 Índice

- [Instalação](#instalação)
- [Configuração](#configuração)
- [Uso](#uso)
- [Automação (Cron)](#automação-cron)
- [API Endpoints](#api-endpoints)
- [Arquitetura](#arquitetura)

---

## 🚀 Instalação

### 1. Executar DDL

Execute o script SQL para criar as tabelas necessárias:

```bash
mysql -u root -p mailjztech < SQL/DDL_BACKUP.sql
```

### 2. Verificar Dependências

As seguintes dependências já devem estar instaladas:
- `google/cloud-storage` (Google Cloud Storage Client)
- `mysqldump` (MySQL Client Tools)

### 3. Configurar Variáveis de Ambiente

Edite o arquivo `.env` e certifique-se de que as seguintes variáveis estão configuradas:

```env
# Credenciais MySQL para realizar dumps
USER_MASTER_DB="seu_usuario_mysql"
PASS_MASTER_DB="sua_senha_mysql"

# Configurações de conexão (se diferente do padrão)
DB_HOST="localhost"
DB_PORT="3306"
```

### 4. Configurar Google Cloud Storage

- Certifique-se de que o arquivo `src/handlers/service/bkp.json` contém as credenciais do Google Cloud
- O bucket padrão é `dbjztech` (pode ser alterado nas configurações)

---

## ⚙️ Configuração

### 1. Acessar Interface Web

Acesse: `http://seu-dominio.com/backup`

### 2. Criar Nova Configuração

Clique em **"Nova Configuração"** e preencha:

- **Nome do Banco**: Nome exato do banco MySQL (ex: `mailjztech`)
- **Bucket GCS**: Nome do bucket no Google Cloud (padrão: `dbjztech`)
- **Pasta Base**: Pasta raiz no bucket para organizar backups (ex: `mailjztech_prod`)
- **Retenção (dias)**: Quantidade de dias para manter backups (padrão: 7)
- **Ativo**: Marque para habilitar backups automáticos

### 3. Testar Backup Manual

Após criar a configuração, clique no botão **▶️ (Play)** na listagem para executar um backup teste.

---

## 📖 Uso

### Interface Web

#### Dashboard de Backups

- **URL**: `/backup`
- **Funcionalidades**:
  - Visualizar estatísticas gerais
  - Listar configurações de backup
  - Criar/Editar/Excluir configurações
  - Executar backups manuais

#### Logs de Execução

- **URL**: `/backup/logs/{id}`
- **Funcionalidades**:
  - Visualizar histórico de execuções
  - Ver detalhes de erros
  - Executar backup manual do banco específico

### Via API

Veja seção [API Endpoints](#api-endpoints) abaixo.

---

## ⏰ Automação (Cron)

### Configurar Crontab

Para executar backups automaticamente todos os dias às **3h da manhã**:

#### Linux/Mac

```bash
# Editar crontab
crontab -e

# Adicionar linha (backups diários às 3h)
0 3 * * * curl -X POST https://seu-dominio.com/backup/executar -H "Authorization: Bearer SEU_TOKEN_JV" >> /var/log/backup-cron.log 2>&1
```

#### Windows (Agendador de Tarefas)

1. Abrir **Agendador de Tarefas**
2. Criar **Nova Tarefa Básica**
3. **Disparador**: Diariamente às 3:00
4. **Ação**: Iniciar programa
   - **Programa**: `curl`
   - **Argumentos**: `-X POST https://seu-dominio.com/backup/executar -H "Authorization: Bearer SEU_TOKEN_JV"`

### Obter TOKEN_JV

O token está definido no arquivo `.env`:

```env
TOKEN_JV="sua_chave_secreta_aqui"
```

### Logs de Execução

Os logs podem ser visualizados:
- **Interface Web**: `/backup` e `/backup/logs/{id}`
- **Banco de Dados**: Tabela `backup_execucao_log`

---

## 🔌 API Endpoints

### CRUD de Configurações

#### Listar Configurações
```http
GET /backup/configuracoes
Authorization: Bearer {token}
```

#### Obter Configuração Específica
```http
GET /backup/configuracoes/{id}
Authorization: Bearer {token}
```

#### Criar Configuração
```http
POST /backup/configuracoes
Authorization: Bearer {token}
Content-Type: application/json

{
  "nome_banco": "mailjztech",
  "bucket_nome": "dbjztech",
  "pasta_base": "mailjztech_prod",
  "retencao_dias": 7,
  "ativo": 1
}
```

#### Atualizar Configuração
```http
PUT /backup/configuracoes/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "retencao_dias": 10,
  "ativo": 1
}
```

#### Excluir Configuração
```http
DELETE /backup/configuracoes/{id}
Authorization: Bearer {token}
```

### Execução de Backups

#### Executar Backup Manual (Banco Específico)
```http
POST /backup/executar/{id}
Authorization: Bearer {token}
```

#### Executar Todos os Backups (Cron)
```http
POST /backup/executar
Authorization: Bearer {token}
```

### Consultas

#### Obter Logs de um Banco
```http
GET /backup/logs/{id}?limite=50&detalhado=true
Authorization: Bearer {token}
```

#### Obter Estatísticas (Dashboard)
```http
GET /backup/estatisticas
Authorization: Bearer {token}
```

---

## 🏗️ Arquitetura

### Estrutura de Pastas

```
MailJZTech/
├── SQL/
│   ├── DDL_BACKUP.sql                          # DDL das tabelas
│   ├── backup_logs_obter_por_config.sql        # SQL complexo (logs)
│   └── backup_estatisticas.sql                 # SQL complexo (dashboard)
│
├── src/
│   ├── models/
│   │   ├── BackupBancoConfig.php               # Model de configurações
│   │   └── BackupExecucaoLog.php               # Model de logs
│   │
│   ├── handlers/
│   │   ├── BackupConfig.php                    # Handler CRUD (estático)
│   │   ├── BackupExecucao.php                  # Handler de execução (estático)
│   │   └── service/
│   │       ├── BackupService.php               # Service de backup (estático)
│   │       └── GoogleCloud.php                 # Service GCS (existente)
│   │
│   ├── controllers/
│   │   └── BackupController.php                # Controller (API + Views)
│   │
│   ├── views/
│   │   └── pages/
│   │       ├── backup.php                      # Página principal
│   │       └── backup_logs.php                 # Página de logs
│   │
│   └── routes.php                              # Rotas registradas
│
└── public/
    └── assets/
        └── js/
            └── backup.js                        # JavaScript front-end
```

### Fluxo de Execução

```
┌─────────────────┐
│  Cron / Manual  │
└────────┬────────┘
         │
         ▼
┌─────────────────────────┐
│  BackupController       │
│  @executarCron()        │
└────────┬────────────────┘
         │
         ▼
┌─────────────────────────┐
│  BackupExecucao         │
│  ::executarTodos()      │
└────────┬────────────────┘
         │
         ▼
┌─────────────────────────┐
│  BackupService          │
│  1. gerarDumpMySQL()    │
│  2. comprimirArquivo()  │
│  3. calcularChecksum()  │
│  4. uploadParaGCS()     │
│  5. limparAntigos()     │
└────────┬────────────────┘
         │
         ▼
┌─────────────────────────┐
│  Google Cloud Storage   │
│  bucket/pasta/arquivo   │
└─────────────────────────┘
```

### Formato de Arquivos no GCS

```
bucket: dbjztech
  └── pasta_base/              (ex: mailjztech_prod)
      └── 2025/
          └── 11/
              └── 09/
                  ├── backup-20251109-030000.sql.gz
                  ├── backup-20251109-153045.sql.gz
                  └── ...
```

---

## 🔒 Segurança

### Validações Implementadas

1. **Nome do Banco**: Apenas caracteres alfanuméricos, `_` e `-`
2. **Autenticação**: Todas as rotas exigem autenticação (sessão ou TOKEN_JV)
3. **Sanitização**: Escapamento de comandos shell no mysqldump
4. **Permissões**: Apenas usuários autenticados podem gerenciar backups

### Boas Práticas

- **Nunca** exponha o TOKEN_JV publicamente
- Mantenha o arquivo `bkp.json` (credenciais GCS) fora do controle de versão
- Revise logs regularmente para identificar falhas
- Configure alertas por e-mail para backups com erro

---

## 📊 Monitoramento

### Métricas Disponíveis

- Total de bancos configurados
- Total de backups realizados
- Taxa de sucesso/erro
- Espaço total utilizado (MB)
- Último backup executado

### Logs

**Tabela**: `backup_execucao_log`

**Campos importantes**:
- `status`: `running`, `success`, `error`, `pruned`
- `mensagem_erro`: Detalhes do erro (se houver)
- `gcs_objeto`: Caminho do arquivo no GCS
- `checksum_sha256`: Hash para verificação de integridade

---

## 🐛 Troubleshooting

### Erro: "mysqldump não encontrado"

**Solução**: Instale o MySQL Client ou configure o PATH:

```bash
# Linux (Debian/Ubuntu)
sudo apt-get install mysql-client

# Linux (CentOS/RHEL)
sudo yum install mysql

# Mac
brew install mysql-client

# Windows: Adicionar ao PATH o diretório bin do MySQL
```

### Erro: "Credenciais MySQL não configuradas"

**Solução**: Verifique o arquivo `.env`:

```env
USER_MASTER_DB="seu_usuario"
PASS_MASTER_DB="sua_senha"
```

### Erro: "Objeto não encontrado no GCS"

**Solução**: Verifique as credenciais do Google Cloud em `src/handlers/service/bkp.json` e se o bucket existe.

### Backups não executam automaticamente

**Solução**: Verifique:
1. Crontab está configurado corretamente
2. TOKEN_JV está correto no comando curl
3. Logs do sistema (`/var/log/cron` no Linux)
4. Configurações estão com `ativo = 1`

---

## 📞 Suporte

Para dúvidas ou problemas:
- **Logs da Aplicação**: `logs/app.log`
- **Logs SQL**: `logs/exec{data}-sql.txt`
- **Logs de Backup**: Interface web `/backup` ou tabela `backup_execucao_log`

---

## 🎉 Pronto!

O sistema de backup está configurado e pronto para uso. Acesse `/backup` para começar a gerenciar seus backups automatizados! 🚀
