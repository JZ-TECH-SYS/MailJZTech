<?php

namespace src\controllers;

use core\Controller as ctrl;
use Exception;
use src\handlers\RelatorioVendaCusto;

class RelatorioVendaCustoController extends ctrl
{
    public function getRelatorioVendaCusto($args)
    {
        try {
            $relatorio = RelatorioVendaCusto::getRelatorioVendaCusto(
                (int)$args['idempresa'],
                $args['dataini'],
                $args['datafim']
            );
            ctrl::response($relatorio, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    public function getRelatorioVendaCustoDia($args)
    {
        try {
            $detalhe = RelatorioVendaCusto::getDetalheDia(
                (int)$args['idempresa'],
                $args['dia']
            );
            ctrl::response($detalhe, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
}
