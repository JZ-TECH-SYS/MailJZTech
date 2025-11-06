<?php

/**
 * Controlador de Pagamento de Venda
 * Autor: Joaosn
 * Data de início: 23/05/2023
 */

namespace src\controllers;

use \core\Controller as ctrl;
use \src\handlers\PedidoVenda;
use \src\handlers\PedidoVendaItem;
use \src\handlers\PedidoVendaItemAcrescimo;
use \src\handlers\Pagamentos;
use Exception;

class PagamentoController extends ctrl
{
    /**
     * Campos obrigatórios para criar um novo pagamento
     */
    const ADDCAMPOS = [
        'idpedidovenda',
        'idtipopagamento',
        'idempresa',
        'valor'
    ];

    /**
     * Campos obrigatórios para editar e excluir um pagamento
     */
    const EDITCAMPOS = [
        'idempresa',
        'idpagamento',
        'idpedidovenda'
    ];

    const UPDATECAUTCAMPOS = [
        'idpedidovenda',
        'idpagamento',
        'idempresa',
        'cAut'
    ];

    /**
     * Obtém uma lista de pagamentos para um determinado pedido de venda.
     * 
     * @param array $agrs Um array contendo os parâmetros da consulta: ID da empresa e ID do pedido de venda.
     * @return void
     */
    public function getPagamentos($agrs)
    {
        $idempresa = $agrs['idempresa'];
        $idpedidovenda = $agrs['idpedidovenda'];
        $response = Pagamentos::getPagamentos($idempresa, $idpedidovenda);
        ctrl::response($response, 200);
    }

    /**
     * Obtém um pagamento específico com base em seu ID.
     * 
     * @param array $agrs Um array contendo os parâmetros da consulta: ID da empresa, ID do pedido de venda e ID do pagamento.
     * @return void
     */
    public function getPagamentosById($agrs)
    {
        $idempresa = $agrs['idempresa'];
        $idpedidovenda = $agrs['idpedidovenda'];
        $idpagamento = $agrs['idpagamento'];
        $response = Pagamentos::getPagamentosById($idempresa, $idpedidovenda, $idpagamento);
        ctrl::response($response, 200);
    }

    /**
     * Adiciona um novo pagamento ao sistema.
     * 
     * @return void
     */
    public function addPagamento()
    {
        try {
            $body = ctrl::getBody();
            ctrl::verificarCamposVazios($body, self::ADDCAMPOS);
            $response = Pagamentos::addPagamento($body);
            ctrl::response($response, 201);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Edita um pagamento existente no sistema.
     * 
     * @return void
     */
    public function editPagamento()
    {
        try {
            $body = ctrl::getBody();
            ctrl::verificarCamposVazios($body, self::EDITCAMPOS);
            $response = Pagamentos::editPagamento($body);
            ctrl::response($response, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Exclui um pagamento existente no sistema.
     * 
     * @return void
     */
    public function deletePagamento()
    {
        try {
            $body = ctrl::getBody();
            ctrl::verificarCamposVazios($body, self::EDITCAMPOS);
            $response = Pagamentos::deletePagamento($body);
            ctrl::response($response, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Obtem todos os tipos de pagamento cadastrados no sistema.
     */
    public function getMeiosPagamentos($args)
    {
        $response = Pagamentos::getMeiosPagamentos($args['idempresa']);
        ctrl::response($response, 200);
    }

    /**
     * Obtem todos os tipos de pagamento cadastrados no sistema.
     */
    public function getMeiosPagamentosOn($args)
    {
        $response = Pagamentos::getMeiosPagamentos($args['idempresa'], true);
        ctrl::response($response, 200);
    }

     public function updateCAut()
    {
        $body = ctrl::getBody();
        ctrl::verificarCamposVazios($body, self::UPDATECAUTCAMPOS);
        Pagamentos::updateCAut($body);
        ctrl::response(['message' => 'cAut atualizado com sucesso'], 200);
    }

    /**
     * Remove TODOS os pagamentos de um pedido (RESTAURAR TUDO).
     * 
     * @return void
     */
    public function deleteAllPagamentos()
    {
        try {
            $body = ctrl::getBody();
            // Só precisa de idempresa e idpedidovenda
            ctrl::verificarCamposVazios($body, ['idempresa', 'idpedidovenda']);
            $response = Pagamentos::deleteAllPagamentos($body);
            ctrl::response($response, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
}
