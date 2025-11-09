# 🧪 Guia Rápido: Testando o Fluxo de Logs de E-mails

## Teste Prático Passo-a-Passo

### ✅ Passo 1: Limpar os Logs Anteriores
```sql
DELETE FROM emails_logs;
DELETE FROM emails;
```

### ✅ Passo 2: Enviar um E-mail de Teste

**Via cURL:**
```bash
curl -X POST http://localhost/sendEmail \
  -H "Authorization: Bearer SEU_API_KEY_AQUI" \
  -H "Content-Type: application/json" \
  -d '{
    "destinatario": "seu-email@test.com",
    "assunto": "Teste de Fluxo de Logs",
    "corpo_html": "<h1>Teste do Sistema</h1><p>Se recebeu este e-mail, os logs estão funcionando!</p>",
    "corpo_texto": "Teste do Sistema - Se recebeu este e-mail, os logs estão funcionando!"
  }'
```

**Via Postman:**
1. Novo request: **POST** → `http://localhost/sendEmail`
2. Headers:
   - `Authorization: Bearer SEU_API_KEY_AQUI`
   - `Content-Type: application/json`
3. Body (raw JSON):
```json
{
  "destinatario": "seu-email@test.com",
  "assunto": "Teste de Fluxo de Logs",
  "corpo_html": "<h1>Teste do Sistema</h1><p>Se recebeu este e-mail, os logs estão funcionando!</p>"
}
```

### ✅ Passo 3: Verificar os Logs no Banco de Dados

**Query SQL para visualizar todos os logs:**
```sql
SELECT 
  id_log,
  idemail,
  tipo_log,
  mensagem,
  DATE_FORMAT(data_log, '%H:%i:%s') as hora,
  dados_adicionais
FROM emails_logs
ORDER BY data_log ASC;
```

**Query SQL para contar logs por tipo:**
```sql
SELECT 
  tipo_log,
  COUNT(*) as total
FROM emails_logs
GROUP BY tipo_log
ORDER BY total DESC;
```

**Query SQL para ver apenas os erros:**
```sql
SELECT 
  id_log,
  tipo_log,
  mensagem,
  dados_adicionais
FROM emails_logs
WHERE tipo_log LIKE '%erro%' OR tipo_log LIKE '%exception%'
ORDER BY data_log DESC;
```

### ✅ Passo 4: Visualizar no Dashboard

1. Acesse `http://localhost/logs`
2. Você verá toda a timeline de logs
3. Filtre por `tipo_log` para ver etapas específicas

---

## 📊 Checklist: O que Você Deve Ver

Se o fluxo funcionou corretamente, você terá logs nestas EXATAS ordens:

### ✅ Sequência Esperada (Sucesso):

| # | Tipo Log | Esperado? |
|---|---|---|
| 1 | `validacao` | ✅ Sim |
| 2 | `validacao_iniciada` | ✅ Sim |
| 3 | `validacao_sucesso` | ✅ Sim (se todos os campos OK) |
| 4 | `criacao_registro` | ✅ Sim |
| 5 | `registro_criado` | ✅ Sim |
| 6 | `envio_iniciado` | ✅ Sim |
| 7 | `iniciado` | ✅ Sim |
| 8 | `smtp_conectando` | ✅ Sim |
| 9 | `enviando` | ✅ Sim |
| 10 | `enviado` | ✅ Sim (se SMTP OK) |
| 11 | `fluxo_completo` | ✅ Sim (final) |

**Total esperado: 11 logs**

---

## ❌ Troubleshooting: O que Fazer se der Erro?

### Cenário 1: Poucos logs (menos de 11)
```sql
-- Verifique onde parou:
SELECT tipo_log, COUNT(*) as total FROM emails_logs GROUP BY tipo_log;

-- Se parou em "validacao_erro", verifique:
SELECT * FROM emails_logs WHERE tipo_log = 'validacao_erro';
```

### Cenário 2: Erro na conexão SMTP
```sql
-- Procure por logs de erro:
SELECT mensagem, dados_adicionais 
FROM emails_logs 
WHERE tipo_log IN ('erro', 'smtp_conectando')
ORDER BY data_log DESC 
LIMIT 5;

-- Verifique a configuração em Config.php:
-- SMTP_HOST, SMTP_PORT, EMAIL_API, SENHA_EMAIL_API
```

### Cenário 3: Nenhum log foi criado
```sql
SELECT COUNT(*) as total FROM emails_logs;
-- Se retornar 0, verifique:
-- 1. Se a tabela existe: SHOW TABLES;
-- 2. Se o usuário tem permissão de escrita
-- 3. Se o idusuario está sendo passado corretamente
```

### Cenário 4: E-mail foi criado mas nenhum log
```sql
-- Verifique se o e-mail foi realmente criado:
SELECT * FROM emails ORDER BY idemail DESC LIMIT 1;

-- Mas nenhum log foi criado:
SELECT COUNT(*) FROM emails_logs;

-- Isso indica que logs.php tem um erro (verifique include_path, namespaces, etc)
```

---

## 🔍 SQL Úteis para Debug

### Ver TUDO sobre um envio específico
```sql
SELECT 
  e.idemail,
  e.destinatario,
  e.assunto,
  e.status,
  e.data_criacao,
  COUNT(el.id_log) as total_logs
FROM emails e
LEFT JOIN emails_logs el ON e.idemail = el.idemail
WHERE e.idemail = 123
GROUP BY e.idemail;

-- Depois, ver cada log:
SELECT * FROM emails_logs WHERE idemail = 123 ORDER BY data_log ASC;
```

### Estatísticas de Envio
```sql
SELECT 
  DATE(data_log) as data,
  COUNT(DISTINCT idemail) as emails_enviados,
  COUNT(CASE WHEN tipo_log = 'fluxo_completo' THEN 1 END) as sucesso,
  COUNT(CASE WHEN tipo_log = 'fluxo_erro' THEN 1 END) as erro
FROM emails_logs
GROUP BY DATE(data_log)
ORDER BY data DESC;
```

### Últimos 20 Erros
```sql
SELECT 
  el.id_log,
  el.idemail,
  el.tipo_log,
  el.mensagem,
  el.data_log,
  e.destinatario,
  e.assunto
FROM emails_logs el
LEFT JOIN emails e ON el.idemail = e.idemail
WHERE el.tipo_log IN ('erro', 'validacao_erro', 'fluxo_erro')
ORDER BY el.data_log DESC
LIMIT 20;
```

---

## 🚀 Teste Avançado: Simular Falhas

### Teste 1: Sem Destinatário
```json
{
  "destinatario": "",
  "assunto": "Teste",
  "corpo_html": "<p>Teste</p>"
}
```
**Esperado**: Log com `tipo_log = 'validacao_erro'` indicando destinatário vazio

### Teste 2: Sem Assunto
```json
{
  "destinatario": "teste@test.com",
  "assunto": "",
  "corpo_html": "<p>Teste</p>"
}
```
**Esperado**: Log com `tipo_log = 'validacao_erro'` indicando assunto vazio

### Teste 3: Sem Corpo
```json
{
  "destinatario": "teste@test.com",
  "assunto": "Teste",
  "corpo_html": ""
}
```
**Esperado**: Log com `tipo_log = 'validacao_erro'` indicando corpo vazio

### Teste 4: Credenciais SMTP Inválidas
1. Altere `Config::SENHA_EMAIL_API` para uma senha incorreta
2. Envie um e-mail
3. Você verá logs até `smtp_conectando`, depois um `erro`

---

## 📱 API de Logs (Visualizar pelo Frontend)

### Listar logs de um e-mail específico
```bash
GET /api/logs/listar?idemail=123
Authorization: Bearer SEU_TOKEN
```

### Detalhe de um log específico
```bash
GET /api/logs/detalhe/456
Authorization: Bearer SEU_TOKEN
```

---

## 💾 Exportar Logs para Arquivo

### Via SQL
```sql
SELECT * FROM emails_logs INTO OUTFILE '/tmp/email_logs.csv'
FIELDS TERMINATED BY ','
ENCLOSED BY '"'
LINES TERMINATED BY '\n';
```

### Via cURL (JSON)
```bash
curl -X GET "http://localhost/api/logs/listar?limite=1000" \
  -H "Authorization: Bearer SEU_TOKEN" \
  > email_logs.json
```

---

## ✅ Checklist Final

- [ ] Você conseguiu enviar um e-mail sem erros?
- [ ] Os 11 logs foram criados no banco de dados?
- [ ] O e-mail chegou na caixa de entrada?
- [ ] Você pode visualizar os logs no dashboard `/logs`?
- [ ] A query SQL retorna todos os tipos de log esperados?

Se tudo está verde, seu fluxo de logs está **100% funcional**! 🎉

---

*Última atualização: 09/01/2025*
