# 🚀 GUIA RÁPIDO - MAILJZTECH

> **Referência rápida dos padrões do projeto**  
> Para detalhes completos, veja: `PADROES_PROJETO.md`

---

## ⚡ REGRA DE OURO

```
❌ Controller → Model (ERRADO!)
✅ Controller → Handler → Service → Model (CORRETO!)
```

---

## 🏗️ ARQUITETURA EM 3 CAMADAS

```
┌─────────────────────────────────────────┐
│  📱 Cliente/API                         │
└──────────────┬──────────────────────────┘
               ↓
┌──────────────────────────────────────────┐
│  📄 CONTROLLER                           │
│  • Recebe request                        │
│  • Valida auth                           │
│  • Valida inputs                         │
│  • ❌ SEM lógica de negócio             │
│  • ❌ SEM acesso a Models               │
└──────────────┬───────────────────────────┘
               ↓
┌──────────────────────────────────────────┐
│  🔧 HANDLER                              │
│  • Validação de negócio                  │
│  • Orquestração                          │
│  • Logs                                  │
│  • Chama Services/Models                 │
└──────────────┬───────────────────────────┘
               ↓
       ┌───────┴────────┐
       ↓                ↓
┌────────────┐  ┌────────────┐
│ 📧 SERVICE │  │ 💾 MODEL   │
│ • SMTP     │  │ • CRUD     │
│ • 2FA      │  │ • BD       │
│ • APIs     │  │            │
└────────────┘  └────────────┘
```

---

## 📋 CHECKLIST RÁPIDO

### ✅ SEMPRE FAÇA:
- [ ] Siga: Controller → Handler → Service/Model
- [ ] Models com nome EXATO da tabela
- [ ] Use apenas 6 tipos de log do ENUM
- [ ] Crie logs em operações importantes
- [ ] Valide dados antes de processar

### ❌ NUNCA FAÇA:
- [ ] Controller chamar Model diretamente
- [ ] Tipos de log inventados
- [ ] Model com lógica de negócio
- [ ] Código sem try/catch

---

## 📊 TABELAS E MODELS

| Tabela | Model | Importar |
|--------|-------|----------|
| `emails_enviados` | `Emails_enviados` | `use src\models\Emails_enviados;` |
| `emails_logs` | `Emails_logs` | `use src\models\Emails_logs;` |
| `usuarios` | `Usuarios` | `use src\models\Usuarios;` |
| `sistemas` | `Sistemas` | `use src\models\Sistemas;` |

---

## 🎯 ENUM TIPOS DE LOG (APENAS ESTES 6!)

```php
✅ 'envio'         // SMTP, enviando, enviado
✅ 'criacao'       // Criar registros
✅ 'atualizacao'   // Atualizar registros
✅ 'erro'          // Erros, exceções
✅ 'autenticacao'  // Login, 2FA
✅ 'validacao'     // Validações, testes
```

### Exemplos práticos:

```php
// ✅ Conectando SMTP
Emails_logs::criar($idemail, $idsistema, $idusuario, 'envio', 'Conectando ao servidor SMTP...');

// ✅ Validando dados
Emails_logs::criar(null, $idsistema, $idusuario, 'validacao', 'Validando destinatário');

// ✅ Registro criado
Emails_logs::criar($idemail, $idsistema, $idusuario, 'criacao', 'E-mail criado no banco');

// ✅ Erro SMTP
Emails_logs::criar($idemail, $idsistema, $idusuario, 'erro', 'Falha SMTP: ' . $erro);

// ❌ ERRADO!
Emails_logs::criar($idemail, $idsistema, $idusuario, 'smtp_conectando', '...'); // ❌ Não existe!
Emails_logs::criar($idemail, $idsistema, $idusuario, 'iniciado', '...'); // ❌ Não existe!
```

---

## 🎨 CÓDIGO TEMPLATE

### Controller Básico:

```php
<?php
namespace src\controllers;

use core\Controller as ctrl;
use src\handlers\Emails as EmailsHandler; // ✅ Handler, não Model!

class EmailController extends ctrl
{
    public function sendEmail()
    {
        try {
            // 1. Obter dados
            $dados = ctrl::getBody(true);
            
            // 2. Validar
            ctrl::verificarCamposVazios($dados, ['idsistema', 'destinatario']);
            
            // 3. Chamar Handler (NÃO Model!)
            $resultado = EmailsHandler::enviar($idsistema, $idusuario, $dados);
            
            // 4. Retornar
            ctrl::response($resultado, 200);
            
        } catch (\Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
}
```

### Handler Básico:

```php
<?php
namespace src\handlers;

use src\models\Emails_enviados;  // ✅ Agora sim pode chamar Model!
use src\models\Emails_logs;
use src\handlers\service\EmailService;

class Emails
{
    public static function enviar($idsistema, $idusuario, $dados)
    {
        // 1. Log de validação
        Emails_logs::criar(null, $idsistema, $idusuario, 'validacao', 'Iniciando validação...');
        
        // 2. Validar negócio
        if (empty($dados['destinatario'])) {
            Emails_logs::criar(null, $idsistema, $idusuario, 'erro', 'Destinatário obrigatório');
            return ['sucesso' => false];
        }
        
        // 3. Criar no BD (Model)
        $idemail = Emails_enviados::criar($dados);
        Emails_logs::criar($idemail, $idsistema, $idusuario, 'criacao', 'Registro criado');
        
        // 4. Enviar (Service)
        $resultado = EmailService::sendEmail(...);
        
        return $resultado;
    }
}
```

### Model Básico:

```php
<?php
namespace src\models;

use core\Model;

// ✅ Nome EXATO da tabela!
class Emails_enviados extends Model
{
    protected static $table = 'emails_enviados';
    protected static $primaryKey = 'idemail';
    
    public static function criar($dados)
    {
        return self::insert([
            'idsistema' => $dados['idsistema'],
            'destinatario' => $dados['destinatario'],
            // ...
        ]);
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

## 🗺️ ESTRUTURA DE PASTAS

```
src/
├── controllers/        📄 Recebe requests HTTP
│   ├── EmailController.php
│   └── LogsController.php
│
├── handlers/          🔧 Lógica de negócio
│   ├── Emails.php
│   ├── Logs.php
│   └── service/       📧 Serviços externos
│       └── EmailService.php
│
├── models/            💾 Acesso ao BD
│   ├── Emails_enviados.php
│   ├── Emails_logs.php
│   └── Usuarios.php
│
└── views/             🖼️ HTML/PHP
    └── pages/
```

---

## 🔗 IMPORTS CORRETOS

```php
// ❌ ERRADO (Controller importando Model)
use src\models\Emails_enviados;

// ✅ CORRETO (Controller importa Handler)
use src\handlers\Emails as EmailsHandler;
```

```php
// ✅ Handler pode importar tudo
use src\models\Emails_enviados;
use src\models\Emails_logs;
use src\handlers\service\EmailService;
```

---

## 📚 DOCUMENTAÇÃO

| Arquivo | Conteúdo |
|---------|----------|
| `PADROES_PROJETO.md` | Guia completo (LEIA!) |
| `CORRECAO_ARQUITETURA.md` | Correções feitas |
| `DDL_MAILJZTECH.sql` | Estrutura do BD |

---

## 💡 DICAS RÁPIDAS

1. **Dúvida sobre tipo de log?** Use o que mais se encaixa dos 6 ENUMs
2. **Controller ficou grande?** Lógica deve estar no Handler!
3. **Handler ficou grande?** Considere criar um Service
4. **Model com lógica?** Mova para o Handler!

---

**✅ Seguindo este guia = Código limpo e organizado!**

*Atualizado: 09/11/2025*
