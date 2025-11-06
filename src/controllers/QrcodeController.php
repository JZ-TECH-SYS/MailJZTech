<?php

/**
 * Classe responsável pelo controle da página inicial
 * Autor: Joaosn
 * Data de início: 23/05/2023
 */

namespace src\controllers;

use \core\Controller as ctrl;
use Exception;
use src\handlers\QRcode;

class QrcodeController extends ctrl
{
    /**
     * Exibe uma mensagem de boas-vindas e o status da API na página inicial
     */
    public function gerarQRSalao($args)
    {
       $rel = QRcode::getQR($args['idempresa']);
       ctrl::response($rel, 200);
    }

    public function gerarQRMesa($args)
    {
       try {
            if(!isset($args['idmesa'])) {
                throw new \Exception("Mesa não informada");
            }
            $args['idmesa'] = $args['idmesa'] == 0 ? null : $args['idmesa'];
            $rel = QRcode::getQR($args['idempresa'],$args['idmesa']);
            ctrl::response($rel, 200);
        } catch (\Exception $th) {
            ctrl::rejectResponse($th);
       } 
       
    }

}
