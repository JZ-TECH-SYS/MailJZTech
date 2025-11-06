# ✅ Arquivos Docker Atualizados - MailJZTech

## 📋 Resumo das Mudanças

Os arquivos Docker foram **copiados e adaptados** de outro projeto para o **MailJZTech**.

---

## 📝 Arquivos Modificados

### 1. **`docker.ps1`** (Windows PowerShell)
- ✅ Nome do projeto atualizado: `MailJZTech`
- ✅ Container name: `mailjztech`
- ✅ Porta: `8050`
- ✅ Ícones adicionados (📦, 🚀, ✅, etc)
- ✅ Mensagens em português

### 2. **`docker.sh`** (Linux/Mac Bash)
- ✅ Nome do projeto atualizado: `MailJZTech`
- ✅ Container name: `mailjztech`
- ✅ Porta: `8050`
- ✅ Ícones adicionados (📦, 🚀, ✅, etc)
- ✅ Mensagens em português

### 3. **`docker-compose.yml`**
- ✅ Nome do projeto: `mailjztech`
- ✅ Container name: `mailjztech`
- ✅ Volume corrigido: `.:/var/www/html` (ao invés de `./api`)
- ✅ DB_HOST: `host.docker.internal` (corrigido)
- ✅ DB_DATABASE: `mailjztech`
- ✅ Porta: `8050:80`
- ✅ Ícones nos logs

### 4. **`INFRASTRUCTURE.md`**
- ✅ Documentação atualizada para MailJZTech
- ✅ URLs corrigidas
- ✅ Exemplos de comandos atualizados
- ✅ Configurações .env atualizadas

### 5. **`.env.example`** (NOVO)
- ✅ Criado arquivo de exemplo de configuração
- ✅ Todas as variáveis documentadas
- ✅ Valores padrão para desenvolvimento local

---

## 🚀 Como Usar

### **Opção 1: Rodar Local (Laragon/Apache)**

```powershell
# 1. Copie o .env.example para .env
cp .env.example .env

# 2. Configure o .env com suas credenciais
# DB_HOST=localhost
# DB_PORT=3307
# DB_DATABASE=mailjztech

# 3. Suba apenas o MySQL + phpMyAdmin
cd C:\laragon\www\docker-infra
docker-compose up -d

# 4. Acesse via Laragon
# http://localhost/MailJZTech
```

### **Opção 2: Rodar no Docker**

```powershell
# 1. Copie o .env.example para .env
cp .env.example .env

# 2. Configure o .env (o docker-compose já sobrescreve DB_HOST)

# 3. Suba tudo (infra + API)
.\docker.ps1 start

# 4. Acesse
# http://localhost:8050
```

---

## 📚 Comandos Disponíveis

```powershell
.\docker.ps1 help          # Ver todos os comandos
.\docker.ps1 start         # Iniciar tudo
.\docker.ps1 start-infra   # Apenas MySQL + phpMyAdmin
.\docker.ps1 start-api     # Apenas API
.\docker.ps1 stop          # Parar tudo
.\docker.ps1 status        # Ver status
.\docker.ps1 logs          # Ver logs
.\docker.ps1 shell         # Entrar no container
.\docker.ps1 composer      # Instalar dependências
```

---

## 🌐 URLs

| Serviço | URL | Quando |
|---------|-----|--------|
| **API (Docker)** | http://localhost:8050 | Rodando no Docker |
| **API (Local)** | http://localhost/MailJZTech | Rodando no Laragon |
| **phpMyAdmin** | http://localhost:8090 | Sempre (compartilhado) |

---

## ✅ Próximos Passos

1. ✅ Criar arquivo `.env` baseado no `.env.example`
2. ✅ Configurar credenciais SMTP
3. ✅ Subir infraestrutura Docker
4. ✅ Escolher se vai rodar local ou no Docker
5. ✅ Importar SQL: `mysql -u root -p mailjztech < SQL/DDL_MAILJZTECH.sql`

---

**🎉 Scripts prontos para uso!**
