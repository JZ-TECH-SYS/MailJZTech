<?php

/**
 * Classe PedidoVendaItemAcrescimo: um helper para manipular dados 
 * relacionados aos acréscimos de itens de pedidos de venda.
 * Esta classe contém métodos para adicionar, editar e remover acréscimos de itens nos pedidos de venda.
 * 
 * @author João Silva by joaosn
 * @since 23/05/2023
 */

namespace src\handlers;

use src\handlers\PedidoVenda as PV;

use src\models\Pedido_venda_item_gratis;
use src\models\Produtos as ProdutosModel;
use Exception;

class PedidoVendaItemGratis
{
    public static function addPedidoVendaItemGratis($data)
    {
        $precoUnitario = $data['preco_unitario'] ?? null;
        $custoUnitario = $data['custo_unitario'] ?? null;

        if ($precoUnitario === null || $custoUnitario === null) {
            $produto = ProdutosModel::select(['preco', 'preco_custo'])
                ->where('idempresa', $data['idempresa'])
                ->where('idproduto', $data['idproduto'])
                ->one();
            $precoUnitario = $precoUnitario ?? (isset($produto['preco']) ? (float)$produto['preco'] : 0.0);
            $custoUnitario = $custoUnitario ?? (isset($produto['preco_custo']) ? (float)$produto['preco_custo'] : 0.0);
        }

        Pedido_venda_item_gratis::insert([
            'idempresa'      => $data['idempresa'],
            'idpedidovenda'  => $data['idpedidovenda'],
            'iditem'         => $data['iditem'],
            'idproduto'      => $data['idproduto'],
            'quantidade'     => $data['quantidade'],
            'idtipo'         => $data['idtipo'],
            'preco_unitario' => $precoUnitario,
            'custo_unitario' => $custoUnitario
        ])->execute();
    }
   
}
