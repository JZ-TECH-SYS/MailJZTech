<?php

namespace src\controllers;

use \core\Controller as ctrl;

class LogsController extends ctrl
{
    /**
     * Renderiza a página de logs
     * GET /logs (privado = true)
     */
    public function index()
    {
        $this->render('logs');
    }
}
