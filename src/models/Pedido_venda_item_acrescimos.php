<?php

namespace src\models;

use core\Database;
use \core\Model;
use PDO;;

/**
 * Classe modelo para a tabela 'pedido_venda_item_acrescimos' do banco de dados.
 */
class Pedido_venda_item_acrescimos extends Model
{
    const SQLALTERAQUANRIA = "
         update pedido_venda_item_acrescimos
            set quantidade = :quantidade 
         where idpedido_acrescimo = :idpedido_acrescimo
           and idpedido_item      = :idpedido_item
           and idempresa          = :idempresa
    ";
    public static function atualizaQTfast($data)
    {
        $sql = Database::getInstance()->prepare(self::SQLALTERAQUANRIA);
        $sql->bindValue(':quantidade', $data['quantidade']);
        $sql->bindValue(':idpedido_acrescimo', $data['idpedido_acrescimo']);
        $sql->bindValue(':idpedido_item', $data['idpedido_item']);
        $sql->bindValue(':idempresa', $data['idempresa']);
        $sql->bindValue(':idproduto', $data['idproduto']);
        $sql->bindValue(':idpedidovenda', $data['idpedidovenda']);
        $sql->execute();
    }
}
