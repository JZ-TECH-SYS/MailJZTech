<?php

/**
 * Classe responsável pelo controle de erros, como páginas não encontradas
 * Autor: Joaosn
 * Data de início: 23/05/2023
 */

namespace src\controllers;

use \core\Controller;

class ErrorController extends Controller
{

    public function index()
    {
        Controller::response(['msg' => '404 - Pagina não Encontrada!'], 400);
    }
}
