# 📧 Fluxo Completo de Logs de E-mails

## Visão Geral

Este documento descreve como o sistema **MailJZTech** agora registra **CADA ETAPA** do fluxo de envio de e-mails em tempo real no banco de dados.

---

## 📊 Etapas do Fluxo com Logs

### 1️⃣ **Validação da Requisição**
- **Onde**: `EmailController@sendEmail()`
- **Tipo de log**: `validacao`
- **Mensagem**: "Iniciando validação de requisição de envio de e-mail"
- **Dados adicionais**: 
  - `destinatario`
  - `assunto`
- **Problema**: Se falhar aqui, a requisição é rejeitada antes de criar qualquer registro

### 2️⃣ **Validação de Campos Obrigatórios**
- **Onde**: `Emails::enviar()` (Handler)
- **Tipos de log**:
  - `validacao_iniciada`: Inicia a validação
  - `validacao_sucesso`: Todos os campos passaram
  - `validacao_erro`: Campo obrigatório faltando

- **Campos validados**:
  - ✅ `destinatario`
  - ✅ `assunto`
  - ✅ `corpo_html` OU `corpo_texto`

### 3️⃣ **Criação do Registro na Base de Dados**
- **Onde**: `Emails::enviar()` (Handler) → `EmailsModel::criar()`
- **Tipos de log**:
  - `criacao_registro`: Iniciando criação
  - `registro_criado`: Sucesso! Registro criado com ID

- **Dados adicionais**:
  ```json
  {
    "idemail": 123,
    "timestamp": "2025-01-09 10:30:45"
  }
  ```

### 4️⃣ **Preparação e Envio via SMTP**
- **Onde**: `EmailService::sendEmail()`
- **Tipos de log**:
  - `iniciado`: Começando a enviar
  - `smtp_conectando`: Conectando ao servidor SMTP
  - `enviando`: Preparado para enviar
  - `enviado`: ✅ E-mail enviado com sucesso
  - `erro`: ❌ Falha ao enviar

- **Detalhes capturados**:
  - Host SMTP
  - Port SMTP
  - Remetente
  - Total de destinatários (To + CC)
  - Message ID
  - Logs de erro (se houver)

### 5️⃣ **Conclusão do Fluxo**
- **Onde**: `Emails::enviar()` (Handler)
- **Tipos de log**:
  - `fluxo_completo`: ✅ Sucesso completo
  - `fluxo_erro`: ❌ Falha no envio
  - `fluxo_exception`: ⚠️ Exceção não tratada

---

## 🔍 Exemplo Prático de Fluxo

### Requisição:
```bash
POST /sendEmail
Authorization: Bearer {api_key}
Content-Type: application/json

{
  "destinatario": "usuario@example.com",
  "assunto": "Bem-vindo!",
  "corpo_html": "<h1>Olá</h1>"
}
```

### Logs Gerados na Tabela `emails_logs`:

| ID | idemail | tipo_log | mensagem | dados_adicionais | data_log |
|---|---|---|---|---|---|
| 1 | NULL | validacao | Iniciando validação... | {"destinatario":"usuario@example.com",...} | 2025-01-09 10:30:45 |
| 2 | NULL | validacao_iniciada | Iniciando validação de dados... | ... | 2025-01-09 10:30:45 |
| 3 | NULL | validacao_sucesso | Todos os campos obrigatórios... | ... | 2025-01-09 10:30:45 |
| 4 | NULL | criacao_registro | Criando registro de e-mail... | ... | 2025-01-09 10:30:45 |
| 5 | 123 | registro_criado | Registro de e-mail criado com sucesso | {"idemail":123,...} | 2025-01-09 10:30:45 |
| 6 | 123 | envio_iniciado | Preparando para enviar... | ... | 2025-01-09 10:30:46 |
| 7 | 123 | iniciado | Iniciando processo de envio... | {"destinatario":"usuario@example.com",...} | 2025-01-09 10:30:46 |
| 8 | 123 | smtp_conectando | Conectando ao servidor SMTP | {"host":"smtp.example.com","port":587} | 2025-01-09 10:30:46 |
| 9 | 123 | enviando | Preparado para enviar via SMTP | {"remetente":"contato@jztech.com.br",...} | 2025-01-09 10:30:46 |
| 10 | 123 | enviado | E-mail enviado com sucesso via SMTP | {"timestamp":"2025-01-09 10:30:47",...} | 2025-01-09 10:30:47 |
| 11 | 123 | fluxo_completo | Fluxo de envio concluído com SUCESSO | {"idemail":123,...} | 2025-01-09 10:30:47 |

---

## ❌ Exemplo com Erro

Se houver falha na conexão SMTP, os logs seriam:

| ID | idemail | tipo_log | mensagem | dados_adicionais |
|---|---|---|---|---|
| ... | ... | ... | ... | ... |
| 8 | 123 | smtp_conectando | Conectando ao servidor SMTP | {...} |
| 9 | 123 | erro | Connection timeout | {"tipo_erro":"Exception","detalhes":"Timeout conectando...","timestamp":"2025-01-09 10:30:50"} |
| 10 | 123 | fluxo_erro | Fluxo de envio concluído com ERRO | {"erro":"Connection timeout","idemail":123} |

---

## 🎯 Tipos de Logs Disponíveis

### Controller (`EmailController`)
- `validacao` - Validação de requisição iniciada

### Handler (`Emails`)
- `validacao_iniciada` - Validação de dados iniciada
- `validacao_sucesso` - Validação passou
- `validacao_erro` - Campo obrigatório faltando
- `criacao_registro` - Criando registro no banco
- `registro_criado` - Registro criado com sucesso
- `envio_iniciado` - Enviando para o serviço
- `fluxo_completo` - Tudo OK! ✅
- `fluxo_erro` - Falha no envio ❌
- `fluxo_exception` - Exceção não tratada ⚠️

### Service (`EmailService`)
- `iniciado` - Iniciando envio
- `smtp_conectando` - Conectando ao SMTP
- `enviando` - Pronto para enviar
- `enviado` - Sucesso! ✅
- `erro` - Falha ❌

### Teste
- `teste_config` - Teste de configuração iniciado
- `teste_iniciado` - Teste iniciado
- `teste_sucesso` - Teste bem-sucedido ✅
- `teste_erro` - Teste falhou ❌
- `teste_exception` - Exceção durante teste ⚠️

---

## 📱 Visualizando os Logs

### Pela API
```bash
GET /api/logs/listar
Authorization: Bearer {token}
```

### Por SQL Direto
```sql
SELECT * FROM emails_logs 
WHERE idsistema = 1 
ORDER BY data_log DESC 
LIMIT 50;
```

### Com Detalhes JSON
```sql
SELECT 
  id_log,
  idemail,
  tipo_log,
  mensagem,
  JSON_EXTRACT(dados_adicionais, '$.destinatario') as destinatario,
  data_log
FROM emails_logs
WHERE idemail = 123
ORDER BY data_log ASC;
```

---

## 🛠️ Estrutura da Tabela `emails_logs`

```sql
CREATE TABLE emails_logs (
    id_log INT PRIMARY KEY AUTO_INCREMENT,
    idemail INT,
    idsistema INT NOT NULL,
    idusuario INT,
    tipo_log VARCHAR(50) NOT NULL,
    mensagem TEXT,
    dados_adicionais JSON,
    ip_origem VARCHAR(45),
    user_agent TEXT,
    data_log TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idemail) REFERENCES emails(idemail),
    FOREIGN KEY (idsistema) REFERENCES sistemas(idsistema),
    INDEX(idemail),
    INDEX(idsistema),
    INDEX(tipo_log),
    INDEX(data_log)
);
```

---

## 🚀 Como Usar na Prática

### 1. Enviar um E-mail
```bash
curl -X POST http://localhost/sendEmail \
  -H "Authorization: Bearer SEU_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "destinatario": "teste@example.com",
    "assunto": "Teste",
    "corpo_html": "<p>Teste de envio</p>"
  }'
```

### 2. Verificar os Logs
```bash
# Todos os logs recentes
curl -X GET "http://localhost/api/logs/listar?limite=50" \
  -H "Authorization: Bearer SEU_TOKEN"

# Logs de um e-mail específico
curl -X GET "http://localhost/api/logs/listar?idemail=123" \
  -H "Authorization: Bearer SEU_TOKEN"
```

### 3. Analisar no Dashboard
Visite `/logs` para visualizar todos os logs com interface amigável.

---

## 💡 Dicas para Troubleshooting

### E-mail não está sendo enviado?
1. Verifique os logs com `tipo_log = 'erro'`
2. Procure por `tipo_log = 'fluxo_erro'` para ver a mensagem de erro
3. Verifique `dados_adicionais` para mais detalhes

### Logs não estão sendo criados?
1. Verifique se a tabela `emails_logs` existe
2. Confirme que `idusuario` está sendo passado corretamente
3. Verifique permissões de escrita no banco de dados

### Como rastrear um envio completo?
```sql
-- Todos os logs de um e-mail específico
SELECT tipo_log, mensagem, data_log, dados_adicionais
FROM emails_logs
WHERE idemail = 123
ORDER BY data_log ASC;
```

---

## 📝 Resumo da Implementação

✅ **Antes**: Apenas log final (enviado ou erro)
✅ **Agora**: Registra CADA etapa do fluxo
✅ **Resultado**: Rastreamento completo e fácil troubleshooting
✅ **Performance**: Mínimo impacto (logs assíncronos quando possível)

---

*Última atualização: 09/01/2025*
