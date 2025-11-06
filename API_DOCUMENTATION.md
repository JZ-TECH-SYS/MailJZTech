# MailJZTech - Documentação da API

## Visão Geral

MailJZTech é um microservice de envio de e-mails robusto e simples. Todos os e-mails são enviados a partir do endereço padrão `contato@jztech.com.br`, mas o nome do remetente pode ser personalizado por sistema.

## Autenticação

Todas as requisições à API devem incluir a chave de API no header `Authorization`:

```bash
Authorization: Bearer sua_chave_api_aqui
```

## Endpoints da API

### 1. Enviar E-mail

**POST** `/sendEmail`

Envia um e-mail através da API.

**Headers:**
```
Authorization: Bearer {chave_api}
Content-Type: application/json
```

**Body:**
```json
{
  "destinatario": "usuario@example.com",
  "assunto": "Assunto do e-mail",
  "corpo_html": "<h1>Olá!</h1><p>Bem-vindo!</p>",
  "corpo_texto": "Olá! Bem-vindo!",
  "cc": ["cc@example.com"],
  "bcc": ["bcc@example.com"],
  "anexos": [
    {
      "nome": "documento.pdf",
      "caminho": "/path/to/file.pdf"
    }
  ]
}
```

**Campos:**
- `destinatario` (obrigatório): E-mail de destino
- `assunto` (obrigatório): Assunto do e-mail
- `corpo_html` (obrigatório): Corpo em HTML
- `corpo_texto` (opcional): Corpo em texto puro (fallback)
- `cc` (opcional): Array ou string com e-mails em cópia
- `bcc` (opcional): Array ou string com e-mails em cópia oculta
- `anexos` (opcional): Array com objetos contendo `nome` e `caminho`

**Resposta (200):**
```json
{
  "result": {
    "mensagem": "E-mail enviado com sucesso",
    "idemail": 1,
    "status": "enviado"
  },
  "error": false
}
```

**Resposta (400):**
```json
{
  "result": {
    "mensagem": "Erro ao enviar e-mail",
    "idemail": null,
    "erro": true
  },
  "error": true
}
```

### 2. Listar E-mails

**GET** `/listarEmails?limite=50&pagina=1`

Lista todos os e-mails enviados pelo sistema.

**Headers:**
```
Authorization: Bearer {chave_api}
```

**Parâmetros:**
- `limite` (opcional): Quantidade de registros por página (padrão: 50)
- `pagina` (opcional): Número da página (padrão: 1)

**Resposta (200):**
```json
{
  "result": {
    "emails": [
      {
        "idemail": 1,
        "idsistema": 1,
        "destinatario": "usuario@example.com",
        "assunto": "Assunto",
        "status": "enviado",
        "data_envio": "2025-11-06 10:30:00",
        "data_criacao": "2025-11-06 10:30:00"
      }
    ],
    "total": 100,
    "pagina": 1,
    "limite": 50,
    "paginas_totais": 2
  },
  "error": false
}
```

### 3. Obter Detalhes do E-mail

**GET** `/detalheEmail?idemail=1`

Obtém informações detalhadas de um e-mail específico.

**Headers:**
```
Authorization: Bearer {chave_api}
```

**Parâmetros:**
- `idemail` (obrigatório): ID do e-mail

**Resposta (200):**
```json
{
  "result": {
    "idemail": 1,
    "idsistema": 1,
    "destinatario": "usuario@example.com",
    "cc": "[\"cc@example.com\"]",
    "bcc": "[\"bcc@example.com\"]",
    "assunto": "Assunto",
    "corpo_html": "<h1>Olá!</h1>",
    "corpo_texto": "Olá!",
    "anexos": null,
    "status": "enviado",
    "mensagem_erro": null,
    "data_envio": "2025-11-06 10:30:00",
    "data_criacao": "2025-11-06 10:30:00",
    "data_atualizacao": "2025-11-06 10:30:00"
  },
  "error": false
}
```

### 4. Obter Estatísticas

**GET** `/statsEmails`

Obtém estatísticas de e-mails do sistema.

**Headers:**
```
Authorization: Bearer {chave_api}
```

**Resposta (200):**
```json
{
  "result": {
    "total": 100,
    "enviados": 95,
    "erros": 3,
    "pendentes": 2
  },
  "error": false
}
```

### 5. Testar Configuração de E-mail

**POST** `/testarEmail`

Envia um e-mail de teste para validar a configuração.

**Body:**
```json
{
  "email_teste": "seu@email.com"
}
```

**Resposta (200):**
```json
{
  "result": {
    "mensagem": "E-mail de teste enviado com sucesso",
    "status": "enviado"
  },
  "error": false
}
```

### 6. Validar Configuração de E-mail

**GET** `/validarConfigEmail`

Valida se a configuração SMTP está correta.

**Resposta (200):**
```json
{
  "result": {
    "mensagem": "Configuração de e-mail válida",
    "status": "ok"
  },
  "error": false
}
```

## Endpoints de Gerenciamento de Sistemas (Admin)

### 1. Listar Sistemas

**GET** `/listarSistemas`

Lista todos os sistemas cadastrados.

**Headers:**
```
Authorization: Bearer {chave_api_admin}
```

**Resposta (200):**
```json
{
  "result": [
    {
      "idsistema": 1,
      "nome": "Meu Sistema",
      "descricao": "Descrição",
      "nome_remetente": "Meu Sistema",
      "email_remetente": "contato@jztech.com.br",
      "chave_api": "abc123...",
      "ativo": 1,
      "data_criacao": "2025-11-06 10:00:00",
      "data_atualizacao": "2025-11-06 10:00:00"
    }
  ],
  "error": false
}
```

### 2. Criar Sistema

**POST** `/criarSistema`

Cria um novo sistema.

**Headers:**
```
Authorization: Bearer {chave_api_admin}
Content-Type: application/json
```

**Body:**
```json
{
  "nome": "Meu Sistema",
  "descricao": "Descrição do sistema",
  "nome_remetente": "Meu Sistema"
}
```

**Resposta (201):**
```json
{
  "result": {
    "mensagem": "Sistema criado com sucesso",
    "nome": "Meu Sistema",
    "chave_api": "abc123def456...",
    "aviso": "Guarde a chave de API em local seguro. Você não poderá vê-la novamente."
  },
  "error": false
}
```

### 3. Atualizar Sistema

**PUT** `/atualizarSistema`

Atualiza um sistema existente.

**Body:**
```json
{
  "idsistema": 1,
  "nome": "Novo Nome",
  "descricao": "Nova descrição",
  "nome_remetente": "Novo Nome",
  "ativo": true
}
```

**Resposta (200):**
```json
{
  "result": {
    "mensagem": "Sistema atualizado com sucesso"
  },
  "error": false
}
```

### 4. Deletar Sistema

**DELETE** `/deletarSistema?idsistema=1`

Deleta um sistema (soft delete).

**Resposta (200):**
```json
{
  "result": {
    "mensagem": "Sistema deletado com sucesso"
  },
  "error": false
}
```

### 5. Regenerar Chave de API

**POST** `/regenerarChaveApi`

Gera uma nova chave de API para um sistema.

**Body:**
```json
{
  "idsistema": 1
}
```

**Resposta (200):**
```json
{
  "result": {
    "mensagem": "Chave de API regenerada com sucesso",
    "chave_api": "nova_chave_aqui...",
    "aviso": "A chave anterior não funcionará mais. Atualize sua integração com a nova chave."
  },
  "error": false
}
```

## Exemplos de Uso

### cURL

```bash
# Enviar e-mail
curl -X POST http://localhost:8000/sendEmail \
  -H "Authorization: Bearer sua_chave_api" \
  -H "Content-Type: application/json" \
  -d '{
    "destinatario": "usuario@example.com",
    "assunto": "Olá!",
    "corpo_html": "<h1>Bem-vindo!</h1>"
  }'

# Listar e-mails
curl -X GET "http://localhost:8000/listarEmails?limite=50&pagina=1" \
  -H "Authorization: Bearer sua_chave_api"
```

### JavaScript/Node.js

```javascript
const apiKey = 'sua_chave_api_aqui';
const apiUrl = 'http://localhost:8000';

// Enviar e-mail
fetch(`${apiUrl}/sendEmail`, {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${apiKey}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    destinatario: 'usuario@example.com',
    assunto: 'Olá!',
    corpo_html: '<h1>Bem-vindo!</h1>'
  })
})
.then(response => response.json())
.then(data => console.log(data));
```

### Python

```python
import requests

api_key = 'sua_chave_api_aqui'
api_url = 'http://localhost:8000'

headers = {
    'Authorization': f'Bearer {api_key}',
    'Content-Type': 'application/json'
}

data = {
    'destinatario': 'usuario@example.com',
    'assunto': 'Olá!',
    'corpo_html': '<h1>Bem-vindo!</h1>'
}

response = requests.post(f'{api_url}/sendEmail', headers=headers, json=data)
print(response.json())
```

## Códigos de Status HTTP

- **200**: Sucesso
- **201**: Criado com sucesso
- **400**: Erro na requisição (dados inválidos)
- **401**: Não autorizado (chave de API inválida)
- **404**: Recurso não encontrado
- **500**: Erro interno do servidor

## Tratamento de Erros

Todas as respostas de erro seguem o padrão:

```json
{
  "result": "Descrição do erro",
  "error": true
}
```

## Limites e Considerações

- Tamanho máximo de anexo: Depende da configuração do servidor
- Limite de taxa: Não implementado (você pode adicionar conforme necessário)
- Retenção de dados: E-mails são mantidos indefinidamente (você pode adicionar limpeza automática)

## Suporte

Para suporte, entre em contato com: contato@jztech.com.br
