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

use src\models\Pedido_venda_item_acrescimos as PedidoVendaItemAcrescimoModel;
use src\models\Pedido_venda_item as PedidoVendaItemModel;
use core\Controller as ctrl;
use Exception;
use src\models\Pedido_venda_item_gratis;
use src\models\Produtos as ProdutosModel;

class PedidoVendaItemAcrescimo
{

    /**
     * Adiciona um acréscimo a um item do pedido de venda.
     * A função insere um novo registro de acréscimo na tabela 'PedidoVendaItemAcrescimo', 
     * com os dados fornecidos no array '$data'. Verifica a existência do item e atualiza o acréscimo 
     * se já houver um registro existente. Retorna os dados atualizados do pedido de venda após a inserção 
     * ou atualização do acréscimo do item.
     *
     * @param array $data Um array associativo contendo os dados necessários para criar um novo acréscimo de item.
     * @return array Retorna os dados atualizados do pedido de venda após a inserção ou atualização do acréscimo do item.
     */
    public static function addPedidoVendItemAcrescimo($data, $retornar = true)
    {
        // Verifica a existência do item no pedido de venda
        PV::validaEstoque($data['idempresa'], $data['idproduto'], $data['quantidade']);
        $produto = ProdutosModel::select(['preco', 'preco_custo'])
            ->where('idempresa', $data['idempresa'])
            ->where('idproduto', $data['idproduto'])
            ->one();
        $precoUnitario = isset($produto['preco']) ? (float)$produto['preco'] : 0.0;
        $custoUnitario = isset($produto['preco_custo']) ? (float)$produto['preco_custo'] : 0.0;

        $isItem = PedidoVendaItemModel::select()
            ->where('idempresa', $data['idempresa'])
            ->where('idpedidovenda', $data['idpedidovenda'])
            ->where('idpedido_item', $data['idpedido_item'])
            ->count();
        if ($isItem == 0) {
            throw new Exception('Item não encontrado');
        }

        // Verifica a existência de um registro de acréscimo para o item e atualiza a quantidade se já existir
        $isItemAcrescimo = PedidoVendaItemAcrescimoModel::select()
            ->where('idempresa', $data['idempresa'])
            ->where('idpedidovenda', $data['idpedidovenda'])
            ->where('idpedido_item', $data['idpedido_item'])
            ->where('idproduto', $data['idproduto'])
            ->one();
            
        if (!empty($isItemAcrescimo) && $isItemAcrescimo['idpedido_acrescimo'] > 0) {
            $updateData = [
                'quantidade'     => $data['quantidade'] + $isItemAcrescimo['quantidade']
            ];
            if (empty($isItemAcrescimo['preco_unitario'])) {
                $updateData['preco_unitario'] = $precoUnitario;
            }
            if (empty($isItemAcrescimo['custo_unitario'])) {
                $updateData['custo_unitario'] = $custoUnitario;
            }
            $pedidoItemAcrescimo = PedidoVendaItemAcrescimoModel::update($updateData)
                ->where('idempresa', $data['idempresa'])
                ->where('idpedidovenda', $data['idpedidovenda'])
                ->where('idpedido_item', $data['idpedido_item'])
                ->where('idproduto', $data['idproduto'])
                ->execute();
        } else { // Insere um novo registro de acréscimo
            $pedidoItemAcrescimo = PedidoVendaItemAcrescimoModel::insert([
                'idpedidovenda'   => $data['idpedidovenda'],
                'idpedido_item'   => $data['idpedido_item'],
                'idempresa'       => $data['idempresa'],
                'idproduto'       => $data['idproduto'],
                'quantidade'      => $data['quantidade'],
                'preco_unitario'  => $precoUnitario,
                'custo_unitario'  => $custoUnitario
            ])->execute();
        }

        $pedidoItemAcrescimo =  $isItemAcrescimo['idpedido_acrescimo'] ?? $pedidoItemAcrescimo;
        $acress = PedidoVendaItemAcrescimoModel::select()
            ->where('idempresa', $data['idempresa'])
            ->where('idpedidovenda', $data['idpedidovenda'])
            ->where('idpedido_acrescimo', $pedidoItemAcrescimo)
        ->one();
        if(!empty($acress)){
            $is_item_gratis = Pedido_venda_item_gratis::select()
            ->where('idempresa', $data['idempresa'])
            ->where('idpedidovenda', $data['idpedidovenda'])
            ->where('iditem', $acress['idpedido_acrescimo'])
            ->where('idproduto', $data['idproduto'])
            ->where('idtipo', 2) //acrescimo
            ->one();
        }

        if( isset($data['gratis']) && !empty($data['gratis']) && $data['gratis'] > 0 && !empty($is_item_gratis) ){
            $freeData = [
                'quantidade' => $data['gratis'] + $is_item_gratis['quantidade']
            ];
            if (empty($is_item_gratis['preco_unitario'])) {
                $freeData['preco_unitario'] = $precoUnitario;
            }
            if (empty($is_item_gratis['custo_unitario'])) {
                $freeData['custo_unitario'] = $custoUnitario;
            }
            $free = Pedido_venda_item_gratis::update($freeData)
            ->where('idempresa', $data['idempresa'])
            ->where('idpedidovenda', $data['idpedidovenda'])
            ->where('iditem', $acress['idpedido_acrescimo'])
            ->where('idproduto', $data['idproduto'])
            ->where('idtipo', 2) //acrescimo
            ->execute();
        }else{
            if( isset($data['gratis']) && !empty($data['gratis']) && $data['gratis'] > 0 ){
                $free = [
                    'idempresa'     => $data['idempresa'],
                    'idpedidovenda' => $data['idpedidovenda'],
                    'iditem'        => $acress['idpedido_acrescimo'],
                    'idproduto'     => $data['idproduto'],
                    'quantidade'    => $data['gratis'],
                    'idtipo'        => 2, //acrescimo
                    'preco_unitario'=> $precoUnitario,
                    'custo_unitario'=> $custoUnitario
                ];
                PedidoVendaItemGratis::addPedidoVendaItemGratis($free);
            }
        }

        // Retorna os dados atualizados do pedido de venda
        $retornar = $retornar ? PV::getPedidoVendas(1, $data['idempresa'], $data['idpedidovenda'],null)[0] : $pedidoItemAcrescimo;
        return $retornar;
    }


    /**
     * Edita um acréscimo de um item no pedido de venda, atualizando os dados conforme necessário.
     * A função busca o acréscimo do item existente com base nos IDs fornecidos e, em seguida,
     * atualiza os campos 'idproduto' e 'quantidade' com os valores fornecidos.
     * Se os valores não forem fornecidos, os campos serão mantidos inalterados.
     * Depois que o acréscimo do item for atualizado, a função retorna os dados atualizados do pedido de venda.
     *
     * @param array $data Um array associativo contendo os IDs e os valores a serem atualizados.
     * @return array Retorna os dados atualizados do pedido de venda após a atualização do acréscimo do item.
     */
    public static function editPedidoVendaItemAcrescimo($data, $retornar = true)
    {
        $pedidoItemAcrescimo = PV::getPedidoItemAcrescimos($data['idempresa'], null, $data['idpedidovenda'], $data['idpedido_acrescimo']);
        if (empty($pedidoItemAcrescimo)) {
            throw new Exception('ItemAcrescimo No Pedido não encontrado');
        }
        if ($data['quantidade'] > $pedidoItemAcrescimo[0]['quantidade'] && $data['idproduto'] == $pedidoItemAcrescimo[0]['idproduto']) {
            PV::validaEstoque($data['idempresa'], $data['idproduto'], $data['quantidade']);
        }

        $precoUnitario = isset($pedidoItemAcrescimo[0]['preco_unitario']) ? (float)$pedidoItemAcrescimo[0]['preco_unitario'] : (float)($pedidoItemAcrescimo[0]['preco'] ?? 0);
        $custoUnitario = isset($pedidoItemAcrescimo[0]['custo_unitario']) ? (float)$pedidoItemAcrescimo[0]['custo_unitario'] : 0.0;
        $produtoAtual = (int)$pedidoItemAcrescimo[0]['idproduto'];
        $produtoAlvo  = !empty($data['idproduto']) ? (int)$data['idproduto'] : $produtoAtual;

        if ($produtoAlvo !== $produtoAtual) {
            $produto = ProdutosModel::select(['preco', 'preco_custo'])
                ->where('idempresa', $data['idempresa'])
                ->where('idproduto', $produtoAlvo)
                ->one();
            $precoUnitario = isset($produto['preco']) ? (float)$produto['preco'] : 0.0;
            $custoUnitario = isset($produto['preco_custo']) ? (float)$produto['preco_custo'] : 0.0;
        } elseif (empty($pedidoItemAcrescimo[0]['preco_unitario'])) {
            $produto = ProdutosModel::select(['preco', 'preco_custo'])
                ->where('idempresa', $data['idempresa'])
                ->where('idproduto', $produtoAtual)
                ->one();
            $precoUnitario = isset($produto['preco']) ? (float)$produto['preco'] : $precoUnitario;
            $custoUnitario = isset($produto['preco_custo']) ? (float)$produto['preco_custo'] : $custoUnitario;
        }

        PedidoVendaItemAcrescimoModel::update([
            'idproduto'        => !empty($data['idproduto'])            ? $data['idproduto']            : $pedidoItemAcrescimo[0]['idproduto'],
            'quantidade'       => !empty($data['quantidade'])           ? $data['quantidade']           : $pedidoItemAcrescimo[0]['quantidade'],
            'preco_unitario'   => $precoUnitario,
            'custo_unitario'   => $custoUnitario
        ])
            ->where('idempresa',     $data['idempresa'])
            ->where('idpedidovenda', $data['idpedidovenda'])
            ->where('idpedido_item', $data['idpedido_item'])
            ->where('idpedido_acrescimo', $data['idpedido_acrescimo'])
            ->execute();
        
        $free = Pedido_venda_item_gratis::select()->where('idempresa', $data['idempresa'])
        ->where('idpedidovenda', $data['idpedidovenda'])
        ->where('iditem', $data['idpedido_acrescimo'])
        ->one();
        if( isset($data['gratis']) && !empty($data['gratis']) && $data['gratis'] > 0 && !empty($free) ){
            $freeUpdate = [
                'quantidade'   =>  $data['gratis'] + $free['quantidade']
            ];
            if (empty($free['preco_unitario'])) {
                $freeUpdate['preco_unitario'] = $precoUnitario;
            }
            if (empty($free['custo_unitario'])) {
                $freeUpdate['custo_unitario'] = $custoUnitario;
            }
            Pedido_venda_item_gratis::update($freeUpdate)
            ->where('idempresa', $data['idempresa'])
            ->where('idpedidovenda', $data['idpedidovenda'])
            ->where('iditem', $data['idpedido_acrescimo'])
            ->where('idproduto', $data['idproduto'])
            ->where('idtipo', 2) //acrescimo
            ->execute();
        }else if(isset($data['gratis']) && !empty($data['gratis']) && $data['gratis'] > 0){
            $free = [
                'idempresa'     => $data['idempresa'],
                'idpedidovenda' => $data['idpedidovenda'],
                'iditem'        => $data['idpedido_acrescimo'],
                'idproduto'     => $data['idproduto'],
                'quantidade'    => $data['gratis'],
                'idtipo'        => 2, //acrescimo
                'preco_unitario'=> $precoUnitario,
                'custo_unitario'=> $custoUnitario
            ];
            PedidoVendaItemGratis::addPedidoVendaItemGratis($free);
        }

        $result = $retornar ? PV::getPedidoVendas(1, $data['idempresa'], $data['idpedidovenda'],null)[0] : $pedidoItemAcrescimo[0];
        return $result;
    }


    /**
     * Deleta um acréscimo de item do pedido de venda.
     * A função busca o acréscimo do item existente com base nos IDs fornecidos e, em seguida,
     * remove o registro da tabela 'PedidoVendaItemAcrescimo'.
     * Depois que o acréscimo do item for removido, a função retorna os dados atualizados do pedido de venda.
     *
     * @param array $data Um array associativo contendo os IDs necessários para localizar o acréscimo do item a ser deletado.
     * @return array Retorna os dados atualizados do pedido de venda após a remoção do acréscimo do item.
     */

    public static function deletePedidoVendaItemAcrescimo($data,$retornar = true)
    {
        $pedidoItemAcrescimo = PV::getPedidoItemAcrescimos($data['idempresa'], null, $data['idpedidovenda'], $data['idpedido_acrescimo']);
        if (empty($pedidoItemAcrescimo)) {
            throw new Exception('Pedido não encontrado');
        }

        PedidoVendaItemAcrescimoModel::delete()
            ->where('idempresa', $data['idempresa'])
            ->where('idpedidovenda', $data['idpedidovenda'])
            ->where('idpedido_item', $data['idpedido_item'])
            ->where('idpedido_acrescimo', $data['idpedido_acrescimo'])
            ->execute();
        
        Pedido_venda_item_gratis::delete()
            ->where('idempresa', $data['idempresa'])
            ->where('idpedidovenda', $data['idpedidovenda'])
            ->where('iditem', $data['idpedido_acrescimo'])
            ->where('idproduto', $data['idproduto'])
            ->where('idtipo', 2) //acrescimo
            ->execute();

        $result = $retornar ? PV::getPedidoVendas(1, $data['idempresa'], $data['idpedidovenda'],null)[0] : $data['idpedido_acrescimo'];
        return $result;
    }
}
