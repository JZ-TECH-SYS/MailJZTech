<?php
use core\Router;
$router = new Router();

// ==========================================
// MailJZTech - Rotas da API de E-mail
// ==========================================

// Rota principal
$router->get('/', 'HomeController@index');

// ==========================================
// AUTENTICAÇÃO (Login, Logout, 2FA)
// ==========================================
$router->post('/login', 'LoginController@verificarLogin');
$router->post('/confirmar-2fa', 'LoginController@confirmarDoisFatores');
$router->get('/sair', 'LoginController@logout', true);
$router->get('/validaToken', 'LoginController@validaToken');

// ==========================================
// API DE ENVIO DE E-MAILS
// ==========================================

// Enviar e-mail
$router->post('/sendEmail', 'EmailController@sendEmail');

// Listar e-mails enviados
$router->get('/listarEmails', 'EmailController@listarEmails');

// Detalhes de um e-mail específico
$router->get('/detalheEmail/{idemail}', 'EmailController@detalheEmail');

// Estatísticas de envios
$router->get('/statsEmails', 'EmailController@statsEmails');

// Testar configuração de e-mail
$router->post('/testarEmail', 'EmailController@testarEmail');

// Validar configuração de e-mail
$router->get('/validarConfigEmail', 'EmailController@validarConfigEmail');

// ==========================================
// GERENCIAMENTO DE SISTEMAS/CLIENTES
// ==========================================

// Listar todos os sistemas
$router->get('/listarSistemas', 'SistemasController@listarSistemas', true);

// Obter detalhes de um sistema
$router->get('/obterSistema/{idsistema}', 'SistemasController@obterSistema', true);

// Criar novo sistema
$router->post('/criarSistema', 'SistemasController@criarSistema', true);

// Atualizar sistema
$router->put('/atualizarSistema/{idsistema}', 'SistemasController@atualizarSistema', true);

// Deletar sistema
$router->delete('/deletarSistema/{idsistema}', 'SistemasController@deletarSistema', true);

// Regenerar chave de API
$router->post('/regenerarChaveApi/{idsistema}', 'SistemasController@regenerarChaveApi', true);

// ==========================================
// DASHBOARD E LOGS
// ==========================================

// Dashboard (página principal)
$router->get('/dashboard', 'DashboardController@index', true);

// Listar logs
$router->get('/logs', 'LogsController@listar', true);

// Detalhes de um log
$router->get('/logs/{idlog}', 'LogsController@detalhe', true);

// ==========================================
// DOCUMENTAÇÃO
// ==========================================

// Página de documentação da API
$router->get('/documentacao', 'DocumentacaoController@index', true);

// ==========================================
// PÁGINAS DO SISTEMA
// ==========================================

// Página de sistemas (CRUD)
$router->get('/sistemas', 'SistemasController@pagina', true);

// Página de criar sistema
$router->get('/criar-sistema', 'SistemasController@paginaCriar', true);

// Página de editar sistema
$router->get('/editar-sistema/{idsistema}', 'SistemasController@paginaEditar', true);

// Página de histórico de e-mails
$router->get('/emails', 'EmailController@pagina', true);

// Página de configuração de 2FA
$router->get('/configurar-2fa', 'LoginController@paginaConfigurar2FA', true);

// Página de verificação de 2FA
$router->get('/verificar-2fa', 'LoginController@paginaVerificar2FA', true);

// ==========================================
// FIM DAS ROTAS
// ==========================================
