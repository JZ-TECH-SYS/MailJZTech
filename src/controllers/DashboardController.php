<?php

namespace src\controllers;

use \core\Controller as ctrl;

class DashboardController extends ctrl
{
    /**
     * Renderiza o dashboard
     * GET /dashboard (privado = true)
     */
    public function index()
    {
        $this->render('dashboard');
    }
}
