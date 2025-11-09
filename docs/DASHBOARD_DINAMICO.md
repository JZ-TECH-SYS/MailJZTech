# Dashboard Dinâmico - MailJZTech

## 📋 Visão Geral

O dashboard foi completamente refeito para seguir o padrão do projeto: **carregamento dinâmico via JavaScript** com atualização automática a cada 30 segundos.

## ✅ O Que Foi Implementado

### 1. **Arquitetura Correta (Controller → Handler → Model)**

#### Handler: `Emails::obterDadosDashboard()`
```php
// src/handlers/Emails.php
public static function obterDadosDashboard($idsistema, $limite = 10)
{
    // Obtém estatísticas via SQL complexo
    $stats = self::obterEstatisticas($idsistema);
    
    // Obtém últimos e-mails via Query Builder
    $ultimosEmails = Emails_enviados::select([...])->get();

    return [
        'estatisticas' => $stats,
        'ultimos_emails' => $ultimosEmails
    ];
}
```

#### Controller: `DashboardController::obterEstatisticas()`
```php
// src/controllers/DashboardController.php
public function obterEstatisticas()
{
    $idsistema = $_SESSION['idsistema'] ?? null;
    
    // ✅ Controller → Handler → Model
    $dados = EmailsHandler::obterDadosDashboard($idsistema, 10);
    
    return self::response([
        'estatisticas' => $dados['estatisticas'],
        'ultimos_emails' => $dados['ultimos_emails']
    ], 200);
}
```

### 2. **JavaScript Puro (dashboard.js)**

Localização: `public/assets/js/dashboard.js`

**Funcionalidades:**
- ✅ Carrega dados da API ao iniciar
- ✅ Atualiza automaticamente a cada 30 segundos
- ✅ Atualiza cards de estatísticas
- ✅ Atualiza tabela de últimos e-mails
- ✅ Gera gráficos com Chart.js
- ✅ Formata datas e valores
- ✅ Previne XSS com escape de HTML

**Principais Funções:**
```javascript
// Inicializa e configura atualização automática
initDashboard()

// Busca dados da API
carregarDadosDashboard()

// Atualiza interface
atualizarEstatisticas(stats)
atualizarTabelaEmails(emails)
atualizarGraficos(stats)
```

### 3. **View Limpa (sem PHP nos dados)**

Arquivo: `src/views/pages/dashboard/index.php`

**Antes (❌ ERRADO):**
```php
<div class="h3 mb-0 text-primary">
    <?php echo $stats['total'] ?? 0; ?>
</div>
```

**Agora (✅ CORRETO):**
```html
<div class="h3 mb-0 text-primary" data-stat="total">
    <div class="spinner-border spinner-border-sm">
        <span class="visually-hidden">Carregando...</span>
    </div>
</div>
```

### 4. **Rota da API**

```php
// src/routes.php
$router->get('/dashboard/stats', 'DashboardController@obterEstatisticas', true);
```

## 🔄 Fluxo de Dados

```
┌─────────────────────────────────────────────────────────────┐
│                      FLUXO DASHBOARD                        │
└─────────────────────────────────────────────────────────────┘

1. Usuário acessa /dashboard
   └─> DashboardController::index()
       └─> Renderiza view (HTML vazio com spinners)

2. JavaScript carrega automaticamente
   └─> dashboard.js executa initDashboard()
       └─> Faz fetch para /dashboard/stats
           └─> DashboardController::obterEstatisticas()
               └─> EmailsHandler::obterDadosDashboard($idsistema)
                   ├─> Emails_enviados::obterEstatisticas() [SQL complexo]
                   └─> Emails_enviados::select() [Query Builder]
                       └─> Retorna JSON com dados

3. JavaScript recebe dados
   └─> Atualiza interface dinamicamente
       ├─> Cards de estatísticas
       ├─> Tabela de e-mails
       └─> Gráficos Chart.js

4. A cada 30 segundos
   └─> Repete passo 2 automaticamente
```

## 📊 Estrutura de Resposta da API

### Endpoint: `GET /dashboard/stats`

**Headers:**
```
Authorization: Bearer <token>
Content-Type: application/json
```

**Resposta (200 OK):**
```json
{
    "result": {
        "estatisticas": {
            "total": 150,
            "enviados": 142,
            "erros": 5,
            "pendentes": 3
        },
        "ultimos_emails": [
            {
                "idemail": 123,
                "destinatario": "usuario@exemplo.com",
                "assunto": "Bem-vindo ao sistema",
                "status": "enviado",
                "data_envio": "2025-11-09 14:30:00",
                "data_criacao": "2025-11-09 14:29:55"
            }
        ]
    },
    "error": false
}
```

**Erro (401 Unauthorized):**
```json
{
    "result": {
        "mensagem": "Sessão inválida. Faça login novamente."
    },
    "error": true
}
```

## 🎨 Componentes Visuais

### Cards de Estatísticas
- **Total de E-mails** - Badge azul com ícone de envelope
- **Enviados** - Badge verde com ícone de check
- **Erros** - Badge vermelho com ícone de X
- **Taxa de Sucesso** - Badge amarelo com ícone de gráfico

### Gráficos
1. **Linha Temporal** - E-mails enviados nos últimos 30 dias
2. **Doughnut** - Distribuição de status (Enviados/Erros/Pendentes)

### Tabela de E-mails
- Destinatário
- Assunto (truncado em 50 caracteres)
- Status com badge colorido
- Data formatada (dd/mm/yyyy HH:mm)
- Botão de ação (Ver detalhes)

## 🔒 Segurança

### Autenticação
- Rota protegida (`$privado = true`)
- Verifica sessão (`$_SESSION['idsistema']`)
- Retorna 401 se não autenticado

### Prevenção XSS
```javascript
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}
```

## 🚀 Como Testar

### 1. Verificar Console do Navegador
```javascript
// Deve aparecer:
🚀 Inicializando Dashboard...
✅ Dashboard inicializado com sucesso
✅ Dados carregados: {estatisticas: {...}, ultimos_emails: [...]}
🔄 Atualizando dados do dashboard...
```

### 2. Verificar Rede (DevTools)
```
Request: GET /dashboard/stats
Status: 200 OK
Response: { result: {...}, error: false }
```

### 3. Verificar Atualização Automática
- Abrir console
- Esperar 30 segundos
- Deve aparecer: `🔄 Atualizando dados do dashboard...`

## 📝 Manutenção

### Alterar Intervalo de Atualização
```javascript
// Em dashboard.js, linha ~20
updateInterval = setInterval(() => {
    carregarDadosDashboard();
}, 30000); // Alterar valor (em milissegundos)
```

### Adicionar Nova Estatística

1. **Backend (Handler):**
```php
// Adicionar no SQL: SQL/emails_obter_estatisticas.sql
SUM(CASE WHEN status = 'agendado' THEN 1 ELSE 0 END) as agendados
```

2. **Frontend (View):**
```html
<div class="h3 mb-0 text-info" data-stat="agendados">
    <div class="spinner-border spinner-border-sm"></div>
</div>
```

3. **Frontend (JS):**
```javascript
// Em dashboard.js, função atualizarEstatisticas()
document.querySelector('[data-stat="agendados"]').textContent = stats.agendados || 0;
```

## 🐛 Troubleshooting

### Dados não aparecem
1. Verificar console do navegador
2. Verificar rota da API está registrada
3. Verificar sessão está ativa (`$_SESSION['idsistema']`)
4. Verificar permissões do usuário

### Gráficos não renderizam
1. Verificar se Chart.js está carregado
2. Verificar se canvas existe no HTML
3. Verificar dados da API

### Atualização automática não funciona
1. Verificar console por erros
2. Verificar se `updateInterval` foi criado
3. Verificar se usuário não saiu da página

## 📚 Referências

- [Chart.js Documentation](https://www.chartjs.org/docs/latest/)
- [Fetch API MDN](https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API)
- [Bootstrap 5 Cards](https://getbootstrap.com/docs/5.0/components/card/)

---

**Autor:** MailJZTech  
**Data:** 09/11/2025  
**Versão:** 1.0.0
