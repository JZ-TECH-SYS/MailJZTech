<?php

namespace src\controllers;

use \core\Controller as ctrl;

class DocumentacaoController extends ctrl
{
    /**
     * Renderiza a página de documentação
     * GET /documentacao (privado = true)
     */
    public function index()
    {
        $this->render('documentacao');
    }
}
