<?php

namespace src\models;

use core\Database;
use \core\Model;
use PDO;

/**
 * Classe modelo para a tabela 'produtos' do banco de dados.
 */
class Produtos extends Model
{
    public static function getProdutosRank($idempresa, $dataini = null, $datafim = null){
        $sql = '
            select
                *
            from (
                select
                    p.idproduto,
                    p.nome,
                    p.tipo_produto,
                    p.preco as valor,
                    p.foto,
                    sum(round(pvi.quantidade, 2)) as quantidade_produtos,
                    count(pv.idpedidovenda) as quantidade_vendas
                from pedido_venda pv 
                inner join pedido_venda_item pvi on (pvi.idempresa = pv.idempresa and pvi.idpedidovenda = pv.idpedidovenda)
                left join produtos p on (p.idproduto = pvi.idproduto and p.idempresa = pvi.idempresa)
                where pv.idsituacao_pedido_venda = 2
                    and pvi.idproduto > 0
                    and pv.idempresa = :idempresa
                    and p.tipo_produto = 1
                    ' . (($dataini && $datafim) ? 'and DATE(pv.data_baixa) BETWEEN :dataini AND :datafim' : '') . '
                group by 1,2,3,4,5 
            
                union all
            
                select
                    p.idproduto,
                    p.nome,
                    p.tipo_produto,
                    p.preco as valor,
                    p.foto,
                    sum(round(pvia.quantidade, 2)) as quantidade_produtos,
                    count(pv.idpedidovenda) as quantidade_vendas
                from pedido_venda pv 
                inner join pedido_venda_item_acrescimos pvia on (pvia.idempresa = pv.idempresa and pvia.idpedidovenda = pv.idpedidovenda)
                left join produtos p on (p.idproduto = pvia.idproduto and p.idempresa = pvia.idempresa)
                where pv.idsituacao_pedido_venda = 2
                    and pvia.idproduto > 0
                    and pv.idempresa = :idempresa
                    and p.tipo_produto = 2
                    ' . (($dataini && $datafim) ? 'and DATE(pv.data_baixa) BETWEEN :dataini AND :datafim' : '') . '
                group by 1,2,3,4,5 
            ) tb
            order by tb.quantidade_vendas desc
            limit 10
        ';
        $sql = Database::getInstance()->prepare($sql);
        $sql->bindValue(':idempresa', $idempresa);
        
        if ($dataini && $datafim) {
            $sql->bindValue(':dataini', $dataini);
            $sql->bindValue(':datafim', $datafim);
        }
        
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public static function getImpostoProduto($idempresa,$idproduto,$idpedidovenda){
        $dados = [];
        $result = Database::switchParams([
            'idempresa' => $idempresa,
            'idproduto' => $idproduto,
            'idpedidovenda' => $idpedidovenda
        ], 'NFE/getImpostoProduto',true,true);

        if(!isset($result['retorno'][0]['impostos_calculados'])){
            throw new \Exception("Não foi possível calcular os impostos para o produto {$idproduto} no pedido {$idpedidovenda}.");
        }
        $dados = json_decode($result['retorno'][0]['impostos_calculados'],true);
        return $dados;
    }
}
