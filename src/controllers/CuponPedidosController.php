<?php

namespace src\controllers;

use \core\Controller as ctrl;
use \src\handlers\CuponPedidos as CuponPedidosHelper;
use Exception;

class CuponPedidosController extends ctrl
{
    /**
     * Campos obrigatórios para adicionar um Cupom Pedido.
     */
    const ADDCAMPOS = [
        'idpedidovenda', 'idempresa', 'idcupon'
    ];

    /**
     * Campos obrigatórios para editar/excluir um Cupom Pedido.
     */
    const EDITCAMPOS = [
        'idpedidovenda', 'idempresa', 'idcupon'
    ];

    /**
     * Retorna todos os cupons pedidos de uma empresa.
     * 
     * @param array $args Array contendo o ID da empresa.
     */
    public function getCuponPedidos($args)
    {
        $cuponsPedidos = CuponPedidosHelper::getCuponPedidos($args['idempresa']);
        ctrl::response($cuponsPedidos, 200);
    }

    /**
     * Retorna um Cupom Pedido específico.
     * 
     * @param array $args Array contendo o ID da empresa, ID do pedido e ID do cupom.
     */
    public function getCuponPedidoById($args)
    {
        $cuponPedido = CuponPedidosHelper::getCuponPedidoById($args['idempresa'], $args['idpedidovenda'], $args['idcupon']);
        if (!$cuponPedido) {
            ctrl::response('Cupom Pedido não encontrado', 404);
        }
        ctrl::response($cuponPedido, 200);
    }

    /**
     * Adiciona um novo Cupom Pedido.
     */
    public function addCuponPedido()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::ADDCAMPOS);
            $cuponPedido = CuponPedidosHelper::addCuponPedido($data);
            ctrl::response($cuponPedido, 201);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Edita um Cupom Pedido existente.
     */
    public function editCuponPedido()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::EDITCAMPOS);
            $cuponPedido = CuponPedidosHelper::editCuponPedido($data);
            ctrl::response($cuponPedido, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Deleta um Cupom Pedido.
     */
    public function deleteCuponPedido()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::EDITCAMPOS);
            $cuponPedido = CuponPedidosHelper::deleteCuponPedido($data);
            ctrl::response($cuponPedido, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
}
