<?php

/**
 * Classe responsável pelo controle da página inicial
 * Autor: Joaosn
 * Data de início: 23/05/2023
 */

namespace src\controllers;

use \core\Controller as ctrl;

class HomeController extends ctrl
{

    /**
     * Exibe uma mensagem de boas-vindas e o status da API na página inicial
     */
    public function index()
    {
        ctrl::response(['API' => 'ClickExpress'], 200);
    }
}
