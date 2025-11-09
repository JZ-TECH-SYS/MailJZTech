# MailJZTech – Guia de Padrões para Código e Arquitetura

Objetivo: manter consistência no MVC do projeto, padronizar como escrever controllers, handlers, models e services, e quando usar Query Builder vs SQL complexo (Database::switchParams).

## REGRA CRÍTICA: DOCUMENTAÇÃO
**NÃO CRIAR DOCUMENTAÇÃO** automaticamente. Apenas criar arquivos .md se o usuário EXPLICITAMENTE pedir documentação. Foco em código funcional, não em docs.

## Arquitetura e Pastas
- MVC simples:
  - Controllers: `src/controllers/*` (instanciados pelo router; métodos de ação públicos)
  - Views: `src/views/pages/*` e parciais em `src/views/partials/*` (render via `core\\Controller::render`)
  - Models: `src/models/*` (base `core\\Model` com Hydrahon)
  - Handlers (regras de negócio): `src/handlers/*`
  - Services: `src/handlers/service/*` (utilidades externas, e-mail, 2FA etc.)
  - SQLs complexos: `SQL/*.sql` (consumidos por `core\\Database::switchParams`)
  - Core: `core/*` (Router, Controller, Model, Database, Auth, Request)

## Regras de Estilo e Convenções
- Métodos estáticos (regra do time):
  - Controllers: podem ser de instância (padrão atual) – mantenha assim.
  - Handlers: usar métodos estáticos sempre que possível (ex.: `Handler::acao(...)`).
  - Models: preferir métodos estáticos para operações (já exposto por `core\\Model`: `select`, `insert`, `update`, `delete`). Se criar helpers adicionais, escreva-os como `public static function ...`.
  - Services: usar métodos estáticos (ex.: `EmailService::sendEmail`, `TwoFactorAuthService::verifyCode`).
  - Observação: o código legado possui métodos não estáticos; em novos códigos siga o padrão estático e, quando editar, migre com cuidado sem quebrar usos.
- Namespaces:
  - Use `src\\handlers\\service` para services. Evite namespaces divergentes (ex.: padronize `App\\Handlers\\Service` → `src\\handlers\\service`).
- Nomes de classes/arquivos: PascalCase, arquivo = nome da classe.
- Tabelas: `core\\Model::getTableName()` usa o nome da classe em minúsculo. Garanta que o nome da tabela corresponda (ex.: classe `Usuario` → tabela `usuario`). Se divergir, sobrescreva `getTableName()` no model.

## Rotas e Autenticação
- Definição de rotas (`src/routes.php`):
  - `$router->get('/path', 'Controller@acao', $privado = false)`
  - Suporta `get|post|put|delete` e parâmetros em `{param}`.
- Rotas privadas (`$privado = true`) exigem Bearer Token em `Authorization`. Tokens fixos em `Config::TOKEN_JV` são aceitos. Caso contrário, sessão + validação em `core\\Auth`.

## Controllers (core\\Controller)
- Renderização de páginas: `$this->render('nome_view');` e parciais via `$render('header')` dentro da view.
- Corpo JSON: `ctrl::getBody($valida = true)` – lança exception se vazio quando `$valida = true`.
- Validação: `ctrl::verificarCamposVazios($dados, ['campo1','campo2'])` – lança exception se faltar/estiver vazio.
- Respostas JSON:
  - Sucesso: `ctrl::response($payload, 200)`
  - Erro: `ctrl::rejectResponse($exception)` (status 400)
  - Formato: `{ result: <dados>, error: <bool> }`
- Logs de app: `ctrl::log($conteudo)` grava em `../logs/app.log`.

### Regra Estrita de Arquitetura (OBRIGATÓRIA)
- Controller NUNCA chama Model diretamente. Controller → Handler → (Service) → Model.
- Controller pode validar inputs e permissões; qualquer consulta ou CRUD deve ser delegado ao Handler.
- Se o Controller precisar validar existência de entidades (ex.: idsistema), use um método do Handler (ex.: `SistemasHandler::existeId(...)`).

### Esqueleto de ação
try/catch obrigatórios envolvendo leitura de body, validação e resposta.

## Banco de Dados
### Query Builder (simples)
- Use `core\\Model` (Hydrahon) para CRUD e consultas simples:
  - Seleção múltipla: `Model::select(['col1','col2'])->where('col', $val)->orderBy('col','DESC')->get();`
  - Seleção única: `Model::select()->where('id', $id)->one();`
  - Insert: `Model::insert($dados)->execute();` (retorna ID para inserts)
  - Update: `Model::update($dados)->where('id', $id)->execute();`
  - Delete: `Model::delete()->where('id', $id)->execute();`

### SQL Complexo (JOINs, agregações, períodos)
- Use `core\\Database::switchParams(array $params, string $sqlNome, bool $exec = true, bool $log = false)`:
  - Lê `../SQL/$sqlNome.sql` e substitui `:param` pelos valores informados.
  - Importante: para strings/datas inclua as aspas no valor do parâmetro (ex.: `'2025-01-31'`). Para números, passe sem aspas.
  - Retorno: array com chaves `retorno` (dados ou SQL gerado quando `$exec=false`) e `error`.
  - Ative `$log=true` para registrar SQL e retorno em `../logs/execYYYY-mm-dd-sql.txt`.
- Quando usar: relatórios, estatísticas, JOINs complexos, filtros avançados de datas (vide `EmailLogs::obterRecentes`, `EmailLogs::obterPorPeriodo`, `Emails::obterEstatisticas`).

## Handlers (Regras de Negócio)
- Centralizam validações e orquestram models/services. Expor métodos estáticos quando possível.
- Não renderizam views; retornam dados para o controller responder.
- Sem acesso direto a superglobais; passar dados como parâmetros.
- Devem prover helpers simples para validações comuns usadas por controllers (ex.: `existeId`, `listarTodos`, `obterPorId`).

## Services
- Email: `src\\handlers\\service\\EmailService::sendEmail(...)` (estático). Usa PHPMailer com credenciais em `src\\Config`.
  - Registra o e-mail em tabela antes de enviar; atualiza status e cria logs (`EmailLogs`).
  - `validateEmailConfiguration()` para checagens de setup.
- 2FA (TOTP): `src\\handlers\\service\\TwoFactorAuthService` (estático)
  - `generateSecret()`, `generateQRCode($email, $secret)`, `verifyCode($secret, $code)`, `generateBackupCodes()`
  - Padrão: 6 dígitos, período de 30s, janela ±1.
  - Padronize o namespace do arquivo para `src\\handlers\\service` (caso encontre `App\\Handlers\\Service`, ajuste para o padrão do projeto).

## Padrão de Respostas da API
- Sucesso: `ctrl::response([...], 200|201|204)`
- Erros de validação/negócio: `ctrl::rejectResponse($e)` (400). Para 401/403/404, use `ctrl::response($msg, <status>)`.
- Estrutura consistente: `{ result: <dados ou mensagem>, error: false|true }`.

## Boas Práticas
- Sempre try/catch nas actions de controller; nunca dar `echo` direto.
- Sanitização/validação de entrada nos controllers; regras de domínio nos handlers.
- Preferir Query Builder para CRUD e `switchParams` para relatórios/consultas pesadas.
- Em `switchParams`, garanta aspas para strings e datas; números sem aspas.
- Logue operações relevantes com `ctrl::log()` ou `$log=true` no `switchParams` quando útil.
- Evite acoplamento entre camadas; controllers não devem conhecer SQL.

## Blueprint rápido (novo recurso)
1) Model (estático quando criar helpers):
   - `class Produtos extends Model { public static function getAtivos(){ return self::select()->where('status','ativo')->get(); } }`
2) Handler (estático):
   - `class ProdutosHandler { public static function listar(){ return Produtos::getAtivos(); } }`
3) Controller:
  - `public function listarProdutos(){ try { $dados = ProdutosHandler::listar(); ctrl::response($dados,200); } catch(\\Exception $e){ ctrl::rejectResponse($e); } }`
  - `// ERRADO: Produtos::select()->get(); // Controller NUNCA chama Model`
4) Rota:
   - `$router->get('/api/produtos','ProdutosController@listarProdutos', true);`

## Notas de Migração (legado → estático)
- Ao converter métodos de models/handlers/services para estáticos:
  - Atualize chamadas em toda a base.
  - Remova estados internos. Passe dependências como parâmetros.
  - Para models, continue usando a API estática do `core\\Model` (já compatível).