<?php

namespace src\controllers;

use \core\Controller as ctrl;
use \src\handlers\Usuario as UsuarioHandler;

class LogsController extends ctrl
{
    private $usuarioHandler;

    public function __construct()
    {
        $this->usuarioHandler = new UsuarioHandler();
    }

    /**
     * Renderiza a página de logs
     * GET /logs
     */
    public function index()
    {
        if (!$this->usuarioHandler->checkLogin()) {
            $this->redirect('/');
        }

        $this->render('logs');
    }
}
