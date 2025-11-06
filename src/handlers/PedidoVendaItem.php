<?php

/**
 * desc: Helper de pedidos de venda onde faço manipulações de dados
 * @autor: joaosn
 * @dateInico: 23/05/2023
 */

namespace src\handlers;

use src\models\Pedido_venda_item as PedidoVendaItemModel;
use src\models\Produtos as ProdutosModel;
use src\handlers\PedidoVenda as PV;
use core\Controller as ctrl;
use Exception;

class PedidoVendaItem
{

    /**
     * Função: Adicionar um item ao pedido de venda
     * Descrição: Esta função adiciona um item a um pedido de venda existente ou atualiza a quantidade de um item já existente no pedido.
     * 
     * @param array $data Um array associativo contendo os dados do item a ser adicionado ou atualizado.
     *                   Espera-se que inclua os seguintes campos:
     *                   - idempresa: ID da empresa
     *                   - idpedidovenda: ID do pedido de venda
     *                   - idproduto: ID do produto
     *                   - quantidade: Quantidade do produto a ser adicionada
     *                   - obs (opcional): Observações sobre o item
     * 
     * @return array Retorna o pedido de venda atualizado com o novo item adicionado ou a quantidade atualizada.
     */
    public static function addPedidoVendItem($data, $retorno = true)
    {
        PV::validaEstoque($data['idempresa'], $data['idproduto'], $data['quantidade']);
        $produto = ProdutosModel::select(['preco', 'preco_custo'])
            ->where('idempresa', $data['idempresa'])
            ->where('idproduto', $data['idproduto'])
            ->one();

        $precoUnitario = isset($produto['preco']) ? (float)$produto['preco'] : 0.0;
        $custoUnitario = isset($produto['preco_custo']) ? (float)$produto['preco_custo'] : 0.0;

        // Verificar se o item já existe no pedido
        /*$isPedidoItme = PedidoVendaItemModel::select()->where('idempresa', $data['idempresa'])->where('idpedidovenda', $data['idpedidovenda'])->where('idproduto', $data['idproduto'])->one();

        // Se o item já existe, atualizar a quantidade
        if (!empty($isPedidoItme) && $isPedidoItme['idpedido_item'] > 0) {
            $idpedidoitem = PedidoVendaItemModel::update([
                'quantidade' => $isPedidoItme['quantidade'] + $data['quantidade']
            ])
                ->where('idempresa', $data['idempresa'])
                ->where('idpedidovenda', $data['idpedidovenda'])
                ->where('idproduto', $data['idproduto'])
                ->execute();
        } else {*/
            // Caso contrário, adicionar o novo item ao pedido
            $idpedidoitem = PedidoVendaItemModel::insert([
                'idpedidovenda'   => $data['idpedidovenda'],
                'idempresa'       => $data['idempresa'],
                'idproduto'       => $data['idproduto'],
                'quantidade'      => $data['quantidade'],
                'obs'             => !empty($data['obs']) ?  $data['obs'] : null,
                'preco_unitario'  => $precoUnitario,
                'custo_unitario'  => $custoUnitario
            ])->execute();
        //}
        // Retorna o pedido de venda atualizado
        $retorno = $retorno ? PV::getPedidoVendas(1, $data['idempresa'], $data['idpedidovenda'],null)[0] : $idpedidoitem;
        return $retorno;
    }


    /**
     * desc: edita um item de pedido de venda
     * @param array $data Dados do item a ser editado
     * @return array Retorna o pedido de venda atualizado
     */
    public static function editPedidoVendaItem($data,$retorno = true)
    {
        $pedidoItem = PV::getPedidoItem($data['idempresa'], null, $data['idpedidovenda'], $data['idpedido_item']);
        if (empty($pedidoItem)) {
            throw new Exception('Item do pedido não encontrado');
        }
        $precoUnitario = isset($pedidoItem[0]['preco_unitario']) ? (float)$pedidoItem[0]['preco_unitario'] : (float)($pedidoItem[0]['preco'] ?? 0);
        $custoUnitario = isset($pedidoItem[0]['custo_unitario']) ? (float)$pedidoItem[0]['custo_unitario'] : 0.0;

        $produtoAtual = (int)$pedidoItem[0]['idproduto'];
        $produtoAlvo  = !empty($data['idproduto']) ? (int)$data['idproduto'] : $produtoAtual;

        if ($produtoAlvo !== $produtoAtual) {
            $produto = ProdutosModel::select(['preco', 'preco_custo'])
                ->where('idempresa', $data['idempresa'])
                ->where('idproduto', $produtoAlvo)
                ->one();
            $precoUnitario = isset($produto['preco']) ? (float)$produto['preco'] : 0.0;
            $custoUnitario = isset($produto['preco_custo']) ? (float)$produto['preco_custo'] : 0.0;
        } elseif (empty($pedidoItem[0]['preco_unitario'])) {
            // Corrige registros antigos que ainda não tinham snapshot
            $produto = ProdutosModel::select(['preco', 'preco_custo'])
                ->where('idempresa', $data['idempresa'])
                ->where('idproduto', $produtoAtual)
                ->one();
            $precoUnitario = isset($produto['preco']) ? (float)$produto['preco'] : $precoUnitario;
            $custoUnitario = isset($produto['preco_custo']) ? (float)$produto['preco_custo'] : $custoUnitario;
        }

        if ($data['quantidade'] > $pedidoItem[0]['quantidade'] && $data['idproduto'] == $pedidoItem[0]['idproduto']) {
            PV::validaEstoque($data['idempresa'], $data['idproduto'], $data['quantidade']);
        }

        PedidoVendaItemModel::update([
            'idproduto'       => !empty($data['idproduto'])  ? $data['idproduto']  : $pedidoItem[0]['idproduto'],
            'quantidade'      => !empty($data['quantidade']) ? $data['quantidade'] : $pedidoItem[0]['quantidade'],
            'obs'             => !empty($data['obs'])        ? $data['obs']        : $pedidoItem[0]['obs'],
            'preco_unitario'  => $precoUnitario,
            'custo_unitario'  => $custoUnitario
        ])
            ->where('idempresa',     $data['idempresa'])
            ->where('idpedidovenda', $data['idpedidovenda'])
            ->where('idpedido_item', $data['idpedido_item'])
            ->execute();
 
        $result = $retorno ? PV::getPedidoVendas(1, $data['idempresa'], $data['idpedidovenda'],null)[0] : $data['idpedido_item'];
        return $result;
    }

    /**
     * desc: deleta um item de pedido de venda 
     * @param array $data Dados do item a ser deletado
     * @return array Retorna o pedido de venda atualizado
     */
    public static function deletePedidoVendaItem($data,$retorno = true)
    {
        $pedidoItem = PV::getPedidoItem($data['idempresa'], null, $data['idpedidovenda'], $data['idpedido_item']);
        if (empty($pedidoItem)) {
            throw new Exception('Item do pedido não encontrado');
        }

        PedidoVendaItemModel::delete()
            ->where('idempresa',     $data['idempresa'])
            ->where('idpedidovenda', $data['idpedidovenda'])
            ->where('idpedido_item', $data['idpedido_item'])
            ->execute();

        $result = $retorno ? PV::getPedidoVendas(1, $data['idempresa'], $data['idpedidovenda'],null)[0] : $data['idpedido_item'];    
        return $result;
    }
}
