# 📚 PADRÕES E INSTRUÇÕES DO PROJETO MAILJZTECH

**Data:** 09/11/2025  
**Versão:** 1.0

---

## 🎯 REGRAS FUNDAMENTAIS

### ❌ NUNCA FAÇA ISSO:
1. **Controllers chamando Models diretamente** - SEMPRE use Handlers
2. **Nomes de Models diferentes das tabelas** - Model DEVE ter o nome EXATO da tabela
3. **Tipos de log que não existem no ENUM** - Use APENAS os 6 tipos definidos no DDL
4. **Criar código sem verificar a estrutura existente** - SEMPRE analise antes
5. **Ignorar a arquitetura MVC** - Siga SEMPRE o fluxo correto

---

## 🏗️ ARQUITETURA MVC - FLUXO OBRIGATÓRIO

### ✅ Fluxo Correto:
```
Cliente/API Request
    ↓
📄 Controller (recebe request, valida auth, valida inputs)
    ↓
🔧 Handler (validação de negócio, orquestração, logs)
    ↓
📧 Service (opcional - serviços externos: SMTP, 2FA, etc)
    ↓
💾 Model (CRUD - acesso direto ao banco de dados)
    ↓
🗄️ Database (PostgreSQL/MySQL)
```

### 📝 Responsabilidades de Cada Camada:

#### **Controller** (`src/controllers/`)
- ✅ Recebe requisições HTTP (GET, POST, PUT, DELETE)
- ✅ Valida autenticação/autorização
- ✅ Valida campos obrigatórios do request
- ✅ Chama Handler correspondente
- ✅ Retorna resposta HTTP (JSON)
- ❌ **NUNCA** chama Models diretamente
- ❌ **NUNCA** tem lógica de negócio complexa

**Exemplo:**
```php
public function sendEmail()
{
    $dados = ctrl::getBody(true);
    ctrl::verificarCamposVazios($dados, ['idsistema', 'destinatario']);
    
    // ✅ Chama Handler
    $resultado = EmailsHandler::enviar($idsistema, $idusuario, $dados);
    
    ctrl::response($resultado, 200);
}
```

#### **Handler** (`src/handlers/`)
- ✅ Validação de regras de negócio
- ✅ Orquestração de múltiplas operações
- ✅ Criação de logs detalhados
- ✅ Chama Services (se necessário)
- ✅ Chama Models para CRUD
- ✅ Trata erros e exceções
- ❌ **NUNCA** acessa diretamente o banco (usa Models)

**Exemplo:**
```php
public static function enviar($idsistema, $idusuario, $dados)
{
    // Validação de negócio
    if (empty($dados['destinatario'])) {
        Emails_logs::criar(null, $idsistema, $idusuario, 'erro', 'Destinatário obrigatório');
        return ['sucesso' => false];
    }
    
    // ✅ Chama Model
    $idemail = Emails_enviados::criar($dados);
    
    // ✅ Chama Service
    $resultado = EmailService::sendEmail(...);
    
    return $resultado;
}
```

#### **Service** (`src/handlers/service/`)
- ✅ Integração com serviços externos (SMTP, APIs, 2FA)
- ✅ Lógica complexa reutilizável
- ✅ Operações assíncronas
- ✅ Pode chamar Models
- ✅ Cria logs de suas operações

**Exemplo:**
```php
public static function sendEmail($idsistema, $destinatario, ...)
{
    $mail = new PHPMailer();
    
    // Configura SMTP
    $mail->isSMTP();
    $mail->Host = Config::SMTP_HOST;
    
    // ✅ Log antes de enviar
    Emails_logs::criar($idemail, $idsistema, $idusuario, 'envio', 'Conectando SMTP...');
    
    // Envia
    $mail->send();
    
    // ✅ Atualiza Model
    Emails_enviados::atualizarStatus($idemail, 'enviado');
}
```

#### **Model** (`src/models/`)
- ✅ Acesso DIRETO ao banco de dados (CRUD)
- ✅ Nome DEVE ser igual ao nome da tabela (primeira letra maiúscula)
- ✅ Métodos estáticos para operações
- ❌ **NUNCA** tem lógica de negócio
- ❌ **NUNCA** chama outros Models diretamente

**Exemplo:**
```php
class Emails_enviados extends Model
{
    protected static $table = 'emails_enviados';
    
    public static function criar($dados)
    {
        return self::insert($dados);
    }
    
    public static function atualizarStatus($idemail, $status)
    {
        return self::update(['status' => $status])
                   ->where('idemail', $idemail)
                   ->execute();
    }
}
```

---

## 📊 ESTRUTURA DE BANCO DE DADOS

### ✅ Tabelas e Models:

| Tabela | Model | Propósito |
|--------|-------|-----------|
| `emails_enviados` | `Emails_enviados.php` | E-mails enviados/tentados |
| `emails_logs` | `Emails_logs.php` | Logs de todas operações |
| `usuarios` | `Usuarios.php` | Usuários do sistema |
| `sistemas` | `Sistemas.php` | Sistemas integrados |

### ⚠️ ENUM tipos_log (APENAS ESTES 6 VALORES):

```sql
ENUM('envio', 'criacao', 'atualizacao', 'erro', 'autenticacao', 'validacao')
```

#### Quando usar cada tipo:

| Tipo | Uso |
|------|-----|
| `envio` | Qualquer operação de envio de e-mail (iniciado, SMTP, enviando, enviado) |
| `criacao` | Criação de registros no banco |
| `atualizacao` | Atualização de registros |
| `erro` | Erros, exceções, falhas |
| `autenticacao` | Login, 2FA, verificações de usuário |
| `validacao` | Validação de dados, testes, configurações |

#### ✅ Exemplos CORRETOS:
```php
// SMTP conectando
Emails_logs::criar($idemail, $idsistema, $idusuario, 'envio', 'Conectando ao servidor SMTP...');

// Validação de dados
Emails_logs::criar(null, $idsistema, $idusuario, 'validacao', 'Validando dados do destinatário');

// Registro criado
Emails_logs::criar($idemail, $idsistema, $idusuario, 'criacao', 'Registro de e-mail criado com sucesso');

// Erro de envio
Emails_logs::criar($idemail, $idsistema, $idusuario, 'erro', 'Falha ao conectar SMTP: ' . $e->getMessage());
```

#### ❌ Exemplos ERRADOS:
```php
// ❌ Tipo não existe no ENUM
Emails_logs::criar($idemail, $idsistema, $idusuario, 'smtp_conectando', '...');

// ❌ Tipo não existe
Emails_logs::criar($idemail, $idsistema, $idusuario, 'iniciado', '...');

// ❌ Tipo não existe
Emails_logs::criar($idemail, $idsistema, $idusuario, 'teste_iniciado', '...');
```

---

## 📁 ESTRUTURA DE PASTAS

```
MailJZTech/
├── core/                      # Classes base do framework
│   ├── Auth.php               # Autenticação
│   ├── Controller.php         # Controller base
│   ├── Database.php           # Conexão BD
│   ├── Model.php              # Model base
│   ├── Request.php            # HTTP Request
│   ├── Router.php             # Roteamento
│   └── RouterBase.php         # Base de rotas
│
├── src/
│   ├── Config.php             # Configurações gerais
│   ├── Env.php                # Variáveis de ambiente
│   ├── routes.php             # Definição de rotas
│   │
│   ├── controllers/           # 📄 Controllers (recebem requests)
│   │   ├── DashboardController.php
│   │   ├── EmailController.php
│   │   ├── LogsController.php
│   │   ├── LoginController.php
│   │   └── ...
│   │
│   ├── handlers/              # 🔧 Handlers (lógica de negócio)
│   │   ├── Emails.php
│   │   ├── Logs.php
│   │   ├── Usuarios.php
│   │   ├── Sistemas.php
│   │   └── service/           # 📧 Services (serviços externos)
│   │       ├── EmailService.php
│   │       └── TwoFactorAuthService.php
│   │
│   ├── models/                # 💾 Models (acesso ao BD)
│   │   ├── Emails_enviados.php
│   │   ├── Emails_logs.php
│   │   ├── Usuarios.php
│   │   └── Sistemas.php
│   │
│   └── views/                 # 🖼️ Views (HTML/PHP)
│       ├── pages/
│       │   ├── dashboard/
│       │   ├── emails/
│       │   ├── logs/
│       │   └── login/
│       └── partials/
│           ├── header.php
│           └── footer.php
│
├── public/                    # Assets públicos
│   ├── assets/
│   │   ├── css/
│   │   └── js/
│   └── index.php              # Entry point
│
├── docs/                      # 📚 Documentação
│   ├── PADROES_PROJETO.md     # ← VOCÊ ESTÁ AQUI
│   ├── CORRECAO_ARQUITETURA.md
│   └── ...
│
└── SQL/                       # Scripts SQL
    └── DDL_MAILJZTECH.sql
```

---

## 🎨 PADRÕES DE CÓDIGO

### 🔹 Nomenclatura

#### Classes:
```php
// ✅ PascalCase
class EmailController extends Controller { }
class Emails_enviados extends Model { }
class EmailService { }
```

#### Métodos:
```php
// ✅ camelCase
public function sendEmail() { }
public static function enviar() { }
private function validarDados() { }
```

#### Variáveis:
```php
// ✅ camelCase
$idsistema = 1;
$emailData = [];
$resultado = EmailsHandler::enviar(...);
```

#### Constantes:
```php
// ✅ SNAKE_CASE_UPPER
const SMTP_HOST = 'smtp.gmail.com';
const SMTP_PORT = 587;
```

### 🔹 Estrutura de Controller

```php
<?php

namespace src\controllers;

use core\Controller as ctrl;
use src\handlers\Emails as EmailsHandler;

class EmailController extends ctrl
{
    /**
     * Descrição do método
     * GET|POST /rota
     *
     * @return void
     */
    public function nomeMetodo()
    {
        try {
            // 1. Obter dados do request
            $dados = ctrl::getBody(true);
            
            // 2. Validar campos obrigatórios
            ctrl::verificarCamposVazios($dados, ['campo1', 'campo2']);
            
            // 3. Obter usuário da sessão
            $idusuario = $_SESSION['user']['idusuario'] ?? 0;
            
            // 4. Chamar Handler (NUNCA Model diretamente)
            $resultado = EmailsHandler::metodo($dados);
            
            // 5. Retornar resposta
            ctrl::response($resultado, 200);
            
        } catch (\Exception $e) {
            ctrl::log("Erro em nomeMetodo: " . $e->getMessage());
            ctrl::rejectResponse($e);
        }
    }
}
```

### 🔹 Estrutura de Handler

```php
<?php

namespace src\handlers;

use src\models\Emails_enviados;
use src\models\Emails_logs;
use src\handlers\service\EmailService;

class Emails
{
    /**
     * Descrição do método
     *
     * @param int $idsistema
     * @param int $idusuario
     * @param array $dados
     * @return array
     */
    public static function enviar($idsistema, $idusuario, $dados)
    {
        // 1. LOG: Operação iniciada
        Emails_logs::criar(null, $idsistema, $idusuario, 'validacao', 'Iniciando validação...');
        
        // 2. Validação de negócio
        if (empty($dados['destinatario'])) {
            Emails_logs::criar(null, $idsistema, $idusuario, 'erro', 'Destinatário obrigatório');
            return ['sucesso' => false, 'mensagem' => 'Destinatário obrigatório'];
        }
        
        // 3. Criar registro (Model)
        $idemail = Emails_enviados::criar($dados);
        Emails_logs::criar($idemail, $idsistema, $idusuario, 'criacao', 'Registro criado');
        
        // 4. Chamar Service
        $resultado = EmailService::sendEmail($idsistema, $dados, $idemail, $idusuario);
        
        // 5. LOG: Resultado
        if ($resultado['sucesso']) {
            Emails_logs::criar($idemail, $idsistema, $idusuario, 'envio', 'E-mail enviado com sucesso');
        }
        
        return $resultado;
    }
}
```

### 🔹 Estrutura de Model

```php
<?php

namespace src\models;

use core\Model;

class Emails_enviados extends Model
{
    protected static $table = 'emails_enviados';
    protected static $primaryKey = 'idemail';
    
    /**
     * Cria um novo e-mail
     *
     * @param array $dados
     * @return int ID do e-mail criado
     */
    public static function criar($dados)
    {
        return self::insert([
            'idsistema' => $dados['idsistema'],
            'idusuario' => $dados['idusuario'],
            'destinatario' => $dados['destinatario'],
            'assunto' => $dados['assunto'],
            'corpo_html' => $dados['corpo_html'],
            'status' => 'pendente',
            'data_criacao' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Atualiza status do e-mail
     *
     * @param int $idemail
     * @param string $status
     * @return bool
     */
    public static function atualizarStatus($idemail, $status)
    {
        return self::update(['status' => $status, 'data_envio' => date('Y-m-d H:i:s')])
                   ->where('idemail', $idemail)
                   ->execute();
    }
}
```

---

## 🎯 SISTEMA DE LOGS

### Quando criar logs:

1. **✅ Sempre registrar:**
   - Início de operações importantes
   - Validações (sucesso/erro)
   - Criação de registros
   - Envio de e-mails
   - Erros e exceções
   - Autenticação/autorização

2. **❌ Não registrar:**
   - Leitura simples de dados (GET)
   - Operações triviais
   - Loops internos

### Estrutura de Log:

```php
Emails_logs::criar(
    $idemail,           // int|null - ID do e-mail (null se não existe ainda)
    $idsistema,         // int - ID do sistema
    $idusuario,         // int - ID do usuário
    'tipo_log',         // string - ENUM: envio|criacao|atualizacao|erro|autenticacao|validacao
    'Mensagem descritiva do que aconteceu',  // string - Mensagem clara
    [                   // array|null - Dados adicionais (JSON)
        'campo1' => 'valor1',
        'campo2' => 'valor2'
    ]
);
```

---

## 🚀 ROTAS E ENDPOINTS

### Padrão de Rotas:

```php
// src/routes.php

// GET
Router::get('/api/emails/listar', 'EmailController@listarEmails');

// POST
Router::post('/api/emails/send', 'EmailController@sendEmail');

// PUT
Router::put('/api/emails/atualizar', 'EmailController@atualizarEmail');

// DELETE
Router::delete('/api/emails/deletar', 'EmailController@deletarEmail');
```

### Padrão de Endpoints:

```
GET    /api/emails/listar              - Lista todos
GET    /api/emails/obter?id=1          - Obtém um específico
POST   /api/emails/criar               - Cria novo
PUT    /api/emails/atualizar           - Atualiza existente
DELETE /api/emails/deletar?id=1        - Deleta
```

---

## 🖼️ FRONTEND (VIEWS)

### Estrutura de View:

```php
<!-- src/views/pages/emails/listar.php -->

<?php include __DIR__ . '/../../partials/header.php'; ?>

<div class="container">
    <h1>Listagem de E-mails</h1>
    
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Destinatário</th>
                <th>Assunto</th>
                <th>Status</th>
                <th>Data</th>
            </tr>
        </thead>
        <tbody id="emails-lista">
            <!-- JavaScript vai preencher aqui -->
        </tbody>
    </table>
</div>

<script src="/assets/js/emails.js"></script>

<?php include __DIR__ . '/../../partials/footer.php'; ?>
```

### JavaScript (AJAX):

```javascript
// public/assets/js/emails.js

async function listarEmails() {
    try {
        const response = await fetch('/api/emails/listar?idsistema=1&limite=50');
        const data = await response.json();
        
        if (data.emails) {
            renderEmails(data.emails);
        }
    } catch (error) {
        console.error('Erro ao listar e-mails:', error);
    }
}

function renderEmails(emails) {
    const tbody = document.getElementById('emails-lista');
    tbody.innerHTML = '';
    
    emails.forEach(email => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${email.idemail}</td>
            <td>${email.destinatario}</td>
            <td>${email.assunto}</td>
            <td><span class="badge bg-${email.status === 'enviado' ? 'success' : 'danger'}">${email.status}</span></td>
            <td>${formatDate(email.data_criacao)}</td>
        `;
        tbody.appendChild(tr);
    });
}
```

---

## 🛠️ MÉTODO RENDER() INTELIGENTE

### Como usar no Controller:

```php
// Renderiza automaticamente a view correta
public function index()
{
    // Busca: src/views/pages/emails/index.php
    $this->_render('emails');
}

public function listar()
{
    // Busca: src/views/pages/emails/listar.php
    $this->_render('emails/listar');
}
```

### Lógica do _render():

```php
protected function _render($view, $data = [])
{
    $viewPath = __DIR__ . '/../views/pages/' . $view . '.php';
    
    // Se não encontrar, tenta view/index.php
    if (!file_exists($viewPath)) {
        $viewPath = __DIR__ . '/../views/pages/' . $view . '/index.php';
    }
    
    if (file_exists($viewPath)) {
        extract($data);
        require $viewPath;
    } else {
        throw new \Exception("View não encontrada: " . $view);
    }
}
```

---

## ✅ CHECKLIST ANTES DE CRIAR CÓDIGO

### Antes de criar um Controller:
- [ ] O Handler correspondente existe?
- [ ] Os Models necessários existem?
- [ ] O Service é necessário?
- [ ] Quais validações são necessárias?
- [ ] Quais logs devem ser criados?

### Antes de criar um Handler:
- [ ] Quais Models vou precisar?
- [ ] Preciso de algum Service?
- [ ] Quais validações de negócio são necessárias?
- [ ] Em quais pontos vou criar logs?
- [ ] Quais são os possíveis erros?

### Antes de criar um Model:
- [ ] O nome do Model é EXATAMENTE igual à tabela?
- [ ] Quais são as colunas da tabela?
- [ ] Qual é a chave primária?
- [ ] Quais métodos CRUD são necessários?

### Antes de criar logs:
- [ ] O tipo está no ENUM? (envio, criacao, atualizacao, erro, autenticacao, validacao)
- [ ] A mensagem é descritiva?
- [ ] Preciso de dados adicionais (JSON)?
- [ ] Tenho o idemail (ou null)?

---

## 🎓 BOAS PRÁTICAS

### ✅ SEMPRE:
- Seguir o fluxo: Controller → Handler → Service → Model
- Usar try/catch em Controllers
- Criar logs em pontos importantes
- Validar dados antes de processar
- Retornar arrays padronizados: `['sucesso' => bool, 'mensagem' => string, 'dados' => array]`
- Comentar código complexo
- Usar tipos nos ENUMs corretos

### ❌ NUNCA:
- Controller chamar Model diretamente
- Model ter lógica de negócio
- Usar tipos de log que não existem no ENUM
- Criar código sem analisar a estrutura
- Ignorar tratamento de erros
- Deixar senhas/tokens no código (usar .env)

---

## 📚 DOCUMENTAÇÃO COMPLEMENTAR

- `CORRECAO_ARQUITETURA.md` - Correções implementadas
- `DDL_MAILJZTECH.sql` - Estrutura do banco de dados
- `README.md` - Informações gerais do projeto

---

**✅ Seguindo estas instruções, o projeto terá:**
- Arquitetura MVC limpa e organizada
- Código padronizado e legível
- Logs completos e rastreáveis
- Manutenibilidade alta
- Escalabilidade garantida

**Implementado em: 09/11/2025**
**Autor: MailJZTech Development Team**
