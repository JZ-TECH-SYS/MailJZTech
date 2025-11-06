<?php

namespace src\handlers;

use src\models\Cupon_pedidos as CuponPedidosModel;
use Exception;
use core\Database as db;

class CuponPedidos
{
    /**
     * Retorna todos os cupons pedidos de uma empresa.
     *
     * @param int $idempresa O ID da empresa.
     * @return array Lista de cupons pedidos.
     */
    public static function getCuponPedidos($idempresa)
    {
        $cuponsPedidos = CuponPedidosModel::select()
            ->where('idempresa', $idempresa)
            ->execute();
        return $cuponsPedidos;
    }

    /**
     * Retorna um cupom pedido específico.
     *
     * @param int $idempresa O ID da empresa.
     * @param int $idpedidovenda O ID do pedido de venda.
     * @param int $idcupon O ID do cupom.
     * @return array|bool Dados do cupom pedido ou false se não encontrado.
     */
    public static function getCuponPedidoById($idempresa, $idpedidovenda, $idcupon)
    {
        $cuponPedido = CuponPedidosModel::select()
            ->where('idempresa', $idempresa)
            ->where('idpedidovenda', $idpedidovenda)
            ->where('idcupon', $idcupon)
            ->one();
        return $cuponPedido;
    }

    /**
     * Adiciona um novo cupom pedido.
     *
     * @param array $data Dados do cupom pedido a ser adicionado.
     * @return array Dados do cupom pedido recém-criado.
     */
    public static function addCuponPedido($data)
    {
        // Verifica se já existe um registro com o mesmo idempresa e idpedidovenda
        $jaExiste = CuponPedidosModel::select()
            ->where('idempresa', $data['idempresa'])
            ->where('idpedidovenda', $data['idpedidovenda'])
            ->one();

        if (!empty($jaExiste)) {
            throw new Exception('Este pedido já está associado a um cupom');
        }

        try {
            db::getInstance()->beginTransaction();
            $dadosCuponPedido = [
                'idempresa'     => $data['idempresa'],
                'idpedidovenda' => $data['idpedidovenda'],
                'idcupon'       => $data['idcupon']
            ];
            CuponPedidosModel::insert($dadosCuponPedido)->execute();
            db::getInstance()->commit();
        } catch (Exception $e) {
            db::getInstance()->rollBack();
            throw new Exception('Erro ao associar cupom ao pedido. Contate o administrador do sistema!');
        }

        return self::getCuponPedidoById($data['idempresa'], $data['idpedidovenda'], $data['idcupon']);
    }

    /**
     * Edita um cupom pedido existente.
     *
     * @param array $data Dados atualizados do cupom pedido.
     * @return array Dados do cupom pedido atualizado.
     */
    public static function editCuponPedido($data)
    {
        $cupomPedidoExistente = CuponPedidosModel::select()
            ->where('idempresa', $data['idempresa'])
            ->where('idpedidovenda', $data['idpedidovenda'])
            ->where('idcupon', $data['idcupon'])
            ->one();

        if (empty($cupomPedidoExistente)) {
            throw new Exception('Cupom Pedido não encontrado');
        }

        return $cupomPedidoExistente;
    }

    /**
     * Exclui um cupom pedido.
     *
     * @param array $data Dados do cupom pedido a ser excluído.
     * @return array Mensagem de sucesso.
     */
    public static function deleteCuponPedido($data)
    {
        try {
            CuponPedidosModel::delete()
                ->where('idempresa', $data['idempresa'])
                ->where('idpedidovenda', $data['idpedidovenda'])
                ->where('idcupon', $data['idcupon'])
                ->execute();
        } catch (Exception $e) {
            throw new Exception('Erro ao excluir cupom pedido. Contate o administrador do sistema!');
        }

        return ['message' => 'Cupom Pedido excluído com sucesso'];
    }
}
