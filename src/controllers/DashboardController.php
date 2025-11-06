<?php

namespace src\controllers;

use \core\Controller as ctrl;
use \src\handlers\Usuario as UsuarioHandler;
use Exception;

class DashboardController extends ctrl
{
    private $usuarioHandler;

    public function __construct()
    {
        $this->usuarioHandler = new UsuarioHandler();
    }

    /**
     * Renderiza o dashboard
     * GET /dashboard
     */
    public function index()
    {
        if (!$this->usuarioHandler->checkLogin()) {
            $this->redirect('/');
        }

        $this->render('dashboard');
    }
}
