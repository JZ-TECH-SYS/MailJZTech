<?php

/**
 * Classe helper para gerenciar Menu no sistema
 * 
 * Esta classe fornece métodos para gerenciar menus de um sistema.
 * 
 * Autor: Joaosn
 * Data de Início: 23/05/2023
 */

namespace src\handlers;

use core\Database;
use src\models\Menu as MenuModel;
use src\models\Limitar_acrescimos as LimitaAcrescimosModel;
use core\Database as db;
use Exception;

class LimitarAcrescimos
{
    /**
     * Obtém todos os itens Limitados 
     * 
     * @param int $idempresa O ID da empresa
     * @return array|null Um array com os itens do menu ou null se não houver nenhum item registrado
     */
    public static function getLimitProdutos($idempresa){
        $produtos = LimitaAcrescimosModel::getLimitados($idempresa);
        return $produtos;
    }

    /**
     * ADD um novo item no menu
     */
    public static function addLimitProduto($data){
       try{
        db::getInstance()->beginTransaction();
            foreach($data['idparmsacrescimo'] as $produto){
                LimitaAcrescimosModel::insert([
                    'idempresa'  => $data['idempresa'],
                    'quantidade' => $data['quantidade'],
                    'idproduto'  => $produto,
                    'tipo_produto' => $data['tipo_produto'] ?? 1
                ])->execute();
            }
        db::getInstance()->commit();
        return true;
       }catch(Exception $e){
           db::getInstance()->rollback();  
           throw new Exception($e->getMessage());
       } 
       
    }  
    
    /**
     * DELETE um item do menu
     */
    public static function deleteLimitProduto($data){
        try{
            db::getInstance()->beginTransaction();
                LimitaAcrescimosModel::delete()->whereIn('idparmsacrescimo',$data['idparmsacrescimo'])->execute();
            db::getInstance()->commit();
            return true;
           }catch(Exception $e){
               db::getInstance()->rollback();  
               throw new Exception($e->getMessage());
           } 
    }

}
