<?php

namespace src\controllers;

use \core\Controller as ctrl;

class ErrorController extends ctrl
{
    /**
     * Renderiza a página de documentação
     * GET /documentacao (privado = true)
     */
    public function index()
    {
        ctrl::response('Página não encontrada', 404);
    }
}
