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
$router->post('/confirmar-2fa', 'LoginController@confirmarDoisFatores');
$router->get('/sair', 'LoginController@logout', true);
$router->get('/validaToken', 'LoginController@validaToken');

// ==========================================
// CONFIGURAÇÃO DE 2FA (Views - GET)
// ==========================================
$router->get('/configurar-2fa', 'LoginController@paginaConfigurar2FA', true);
$router->get('/verificar-2fa', 'LoginController@paginaVerificar2FA', true);

// ==========================================
// DASHBOARD (Views - GET)
// ==========================================
$router->get('/dashboard', 'DashboardController@index', true);

// ==========================================
// LOGS (Views - GET)
// ==========================================
$router->get('/logs', 'LogsController@index', true);

// ==========================================
// DOCUMENTAÇÃO (Views - GET)
// ==========================================
$router->get('/documentacao', 'DocumentacaoController@index', true);

// ==========================================
// SISTEMAS (Views - GET)
// ==========================================
$router->get('/sistemas', 'SistemasController@index', true);
$router->get('/criar-sistema', 'SistemasController@paginaCriar', true);
$router->get('/editar-sistema/{idsistema}', 'SistemasController@paginaEditar', true);

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
