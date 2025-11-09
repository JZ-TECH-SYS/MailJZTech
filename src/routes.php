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
$router->get('/sair', 'LoginController@logout', true);
$router->get('/validaToken', 'LoginController@validaToken');
$router->post('/login', 'LoginController@verificarLogin');

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
// BACKUPS (Views - GET)
// ==========================================
$router->get('/backup', 'BackupController@index', true);
$router->get('/backup/logs/{id}', 'BackupController@logs', true);

// ==========================================
// BACKUPS (API - CRUD Configurações)
// ==========================================
$router->get('/api/backup/configuracoes', 'BackupController@listar', true);
$router->get('/api/backup/configuracoes/{id}', 'BackupController@obter', true);
$router->post('/api/backup/configuracoes', 'BackupController@criar', true);
$router->put('/api/backup/configuracoes/{id}', 'BackupController@atualizar', true);
$router->delete('/api/backup/configuracoes/{id}', 'BackupController@excluir', true);

// ==========================================
// BACKUPS (API - Execução)
// ==========================================
$router->post('/api/backup/executar/{id}', 'BackupController@executarManual', true);
$router->post('/api/backup/executar', 'BackupController@executarCron', true); // Cron (requer TOKEN_JV)
$router->post('/api/backup/limpar-antigos/{id}', 'BackupController@limparAntigos', true);

// ==========================================
// BACKUPS (API - Consultas)
// ==========================================
$router->get('/api/backup/logs/{id}', 'BackupController@obterLogs', true);
$router->get('/api/backup/estatisticas', 'BackupController@obterEstatisticas', true);

// ==========================================
// FIM DAS ROTAS
// ==========================================
