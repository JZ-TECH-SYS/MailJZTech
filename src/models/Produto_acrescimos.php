<?php

namespace src\models;

use core\Database;
use \core\Model;
use PDO;

/**
 * Classe modelo para a tabela 'Produto_acrescimos' do banco de dados.
 */
class Produto_acrescimos extends Model
{
    public static function getLimitados($idempresa){
        $sql = "
        SELECT
            pm.idempresa,
            pm.idproduto,
            p.nome,
            JSON_ARRAYAGG(
                json_object(
                    'idparmsacrescimo', vinculo.idparmsacrescimo,
                    'idacrescimo', vinculo.idacrescimo,
                    'nome', vinculo.nome,
                    'idempresa', vinculo.idempresa
                )
            ) AS vinculados_json
        FROM
            produto_acrescimos pm 
        INNER JOIN
            produtos p ON (p.idproduto, p.idempresa) = (pm.idproduto, pm.idempresa)
        LEFT JOIN (
            SELECT 
                pm2.idparmsacrescimo,
                p2.idproduto AS idacrescimo,
                pm2.idproduto,
                p2.nome,
                p2.idempresa
            FROM
                produtos p2 
            LEFT JOIN
                produto_acrescimos pm2 ON (pm2.idacrescimo, pm2.idempresa) = (p2.idproduto, p2.idempresa)
        ) AS vinculo ON vinculo.idempresa = pm.idempresa
        AND vinculo.idproduto = pm.idproduto 
        AND vinculo.idacrescimo = pm.idacrescimo
        WHERE
            pm.idempresa = :idempresa
        GROUP BY
            pm.idempresa, pm.idproduto, p.nome
       ";
        $sql = Database::getInstance()->prepare($sql);
        $sql->bindValue(':idempresa', $idempresa);
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        
        $acres = array_map(function($item) {
            $decodedVinculados = json_decode($item['vinculados_json'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $decodedVinculados = json_decode(stripslashes($item['vinculados_json']), true);
            }
            if ($decodedVinculados === null) {
                $decodedVinculados = [];
            }
            $item['vinculados'] = $decodedVinculados;
            unset($item['vinculados_json']);
            return $item;
        }, $result);
        
        return $acres;
    }


    public static function getByIdTrava($idempresa,$idproduto){
        $sql = Database::getInstance()->prepare("
            select
                GROUP_CONCAT( pm.idacrescimo) as idacrescimos     
            from produto_acrescimos pm 
            where pm.idempresa = :idempresa
            and pm.idproduto = :idproduto   
        ");
        $sql->bindValue(':idempresa', $idempresa);
        $sql->bindValue(':idproduto', $idproduto);
        $sql->execute();
        $res = $sql->fetch(PDO::FETCH_ASSOC);
        return (isset($res['idacrescimos'])) ? array_map('intval', explode(',', $res['idacrescimos'])) : false;
    }
}
