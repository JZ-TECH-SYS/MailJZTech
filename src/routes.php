<?php
use core\Router;
$router = new Router();

// ==========================================
// MailJZTech - Rotas da API de E-mail
// ==========================================

// ==========================================
// AUTENTICAÇÃO (Views - GET)
// ==========================================
$router->get('/', 'LoginController@index');
$router->get('/login', 'LoginController@index');

// ==========================================
// AUTENTICAÇÃO (API - POST)
// ==========================================
$router->post('/login', 'LoginController@verificarLogin');
$router->post('/iniciar-2fa', 'LoginController@iniciarDoisFatores');
$router->post('/confirmar-2fa', 'LoginController@confirmarDoisFatores');
$router->post('/verificar-2fa', 'LoginController@verificarDoisFatores');
$router->post('/verificar-2fa-backup', 'LoginController@verificarDoisFatoresBackup');
$router->get('/sair', 'LoginController@logout', true);
$router->get('/validaToken', 'LoginController@validaToken');

// ==========================================
// CONFIGURAÇÃO DE 2FA (Views - GET)
// ==========================================
$router->get('/configurar-2fa', 'LoginController@paginaConfigurar2FA', true);
$router->get('/verificar-2fa', 'LoginController@paginaVerificar2FA');

// ==========================================
// DASHBOARD (Views - GET)
// ==========================================
$router->get('/dashboard', 'DashboardController@index', true);

// ==========================================
// DASHBOARD (API - GET)
// ==========================================
$router->get('/api/dashboard/stats', 'DashboardController@obterEstatisticas', true);

// ==========================================
// LOGS (Views - GET)
// ==========================================
$router->get('/logs', 'LogsController@index', true);

// ==========================================
// LOGS (API - GET)
// ==========================================
$router->get('/api/logs/listar', 'LogsController@listar', true);
$router->get('/api/logs/detalhe/{id}', 'LogsController@detalhe', true);

// ==========================================
// DOCUMENTAÇÃO (Views - GET)
// ==========================================
$router->get('/documentacao', 'DocumentacaoController@index', true);

// ==========================================
// SISTEMAS (Views - GET)
// ==========================================
$router->get('/sistemas', 'SistemasController@index', true);
$router->get('/criar-sistema', 'SistemasController@paginaCriar', true);
$router->get('/editarsistema/{idsistema}', 'SistemasController@paginaEditar', true); // legado sem hífen
$router->get('/editar-sistema/{idsistema}', 'SistemasController@paginaEditar', true); // novo com hífen (correto para links atuais)

// ==========================================
// SISTEMAS (API - POST/PUT/DELETE)
// ==========================================
$router->post('/criarSistema', 'SistemasController@criarSistema', true);
$router->put('/atualizarSistema/{idsistema}', 'SistemasController@atualizarSistema', true);
$router->delete('/deletarSistema/{idsistema}', 'SistemasController@deletarSistema', true);
$router->get('/listarSistemas', 'SistemasController@listarSistemas', true);
$router->get('/obterSistema/{idsistema}', 'SistemasController@obterSistema', true);
$router->post('/regenerarChaveApi/{idsistema}', 'SistemasController@regenerarChaveApi', true);

// ==========================================
// E-MAILS (Views - GET)
// ==========================================
$router->get('/emails', 'EmailController@index', true);

// ==========================================
// E-MAILS (API - POST/GET)
// ==========================================
$router->post('/sendEmail', 'EmailController@sendEmail');
$router->get('/listarEmails', 'EmailController@listarEmails', true);
$router->get('/detalheEmail/{idemail}', 'EmailController@detalheEmail', true);
$router->get('/statsEmails', 'EmailController@statsEmails', true);
$router->post('/testarEmail', 'EmailController@testarEmail', true);
$router->get('/validarConfigEmail', 'EmailController@validarConfigEmail');

// ==========================================
// FIM DAS ROTAS
// ==========================================
