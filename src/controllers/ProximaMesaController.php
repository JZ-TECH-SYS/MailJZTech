<?php
namespace src\controllers;

use \core\Controller as ctrl;
use src\models\Pedido_venda;

class ProximaMesaController extends ctrl
{
    /**
     * GET - Obter próximo número de mesa disponível
     * @route GET /getProximaMesa/{idempresa}
     * 
     * Retorna o maior número de mesa dos pedidos abertos + 1
     * Útil para pré-preencher campo de mesa ao criar nova comanda
     */
    public function getProximaMesa($args)
    {
        try {
            $idempresa = $args['idempresa'];
            
            $proximaMesa = Pedido_venda::getProximaMesa($idempresa);
            
            ctrl::response([
                'proxima_mesa' => $proximaMesa
            ], 200);
        } catch (\Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
}
