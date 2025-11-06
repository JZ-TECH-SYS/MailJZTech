<?php

namespace src\controllers;

use \core\Controller as ctrl;
use \src\handlers\Usuario as UsuarioHandler;

class DocumentacaoController extends ctrl
{
    private $usuarioHandler;

    public function __construct()
    {
        $this->usuarioHandler = new UsuarioHandler();
    }

    /**
     * Renderiza a página de documentação
     * GET /documentacao
     */
    public function index()
    {
        if (!$this->usuarioHandler->checkLogin()) {
            $this->redirect('/');
        }

        $this->render('documentacao');
    }
}
