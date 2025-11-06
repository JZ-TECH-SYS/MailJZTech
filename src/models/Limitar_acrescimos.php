<?php

namespace src\models;

use core\Database;
use \core\Model;
use  \src\handlers\Produtos as ProdHelp;
use PDO;

/**
 * Classe modelo para a tabela 'Menu' do banco de dados.
 */
class Limitar_acrescimos extends Model
{
    public static function getLimitados($idempresa){
        $sql = '
            select
                 pm.idempresa
                ,pm.idparmsacrescimo
                ,pm.quantidade
                ,pm.idproduto
                ,pm.tipo_produto as tipo_produto_limit
            from limitar_acrescimos pm 
            inner join produtos p   on p.idempresa = pm.idempresa and p.idproduto = pm.idproduto
            where pm.idempresa = :idempresa
        ';
        $sql = Database::getInstance()->prepare($sql);
        $sql->bindValue(':idempresa', $idempresa);
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
    
        $acres = []; // nova array
    
        if(!empty($result)){
            foreach($result as $value){
                $produto = ProdHelp::getProdutosById($value['idempresa'],$value['idproduto']);
                $produto['idparmsacrescimo'] = $value['idparmsacrescimo']; // adiciona o idparmsacrescimo ao produto
                $produto['tipo_produto_limit'] = $value['tipo_produto_limit']; // adiciona o tipo_produto trava ao produto
    
                if (isset($acres[$value['quantidade']])) {
                    // se o menu já existe, adiciona o produto à lista de produtos
                    $acres[$value['quantidade']]['produtos'][] = $produto;
                } else {
                    // se o menu não existe, cria uma nova entrada na array
                    $acres[$value['quantidade']] = [
                        'idempresa'  => $value['idempresa'],
                        'quantidade' => $value['quantidade'],
                        'produtos'   => [$produto], // inicia a lista de produtos com o produto atual
                    ];
                }
            }
        }
         
        //print_r($acres);die;
        return $acres;
    }
}
