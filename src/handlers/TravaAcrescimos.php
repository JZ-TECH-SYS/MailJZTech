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

use src\models\Produto_acrescimos as ProdutoAcrescimosModel;
use core\Database as db;
use Exception;

class TravaAcrescimos
{
    /**
     * Obtém todos os itens Limitados 
     * 
     * @param int $idempresa O ID da empresa
     * @return array|null Um array com os itens do menu ou null se não houver nenhum item registrado
     */
    public static function getLimitProdutos($idempresa){
        $produtos = ProdutoAcrescimosModel::getLimitados($idempresa);
        return $produtos;
    }

    /**
 * ADD um novo item no trava acrescimos
 */
public static function addLimitProduto($data){
    try{
        db::getInstance()->beginTransaction();

        // Obter todos os vínculos existentes para o conjunto de produtos e acréscimos.
        $existingLinks = ProdutoAcrescimosModel::select()
            ->whereIn('idproduto', $data['idproduto'])
            ->whereIn('idacrescimo', $data['idacrescimo'])
            ->get();

         
        $existingPairs = [];
        foreach ($existingLinks as $link) {
            $existingPairs[] = $link['idproduto'] . '-' . $link['idacrescimo'];
        }

        foreach ($data['idproduto'] as $produto) {
            foreach ($data['idacrescimo'] as $acrescimo) {
                if (!in_array($produto . '-' . $acrescimo, $existingPairs)) {
                    ProdutoAcrescimosModel::insert([
                        'idempresa' => $data['idempresa'],
                        'idproduto' => $produto,
                        'idacrescimo' => $acrescimo
                    ])->execute();
                }
            }
        }

        db::getInstance()->commit();
        return true;
    } catch (Exception $e) {
        db::getInstance()->rollback();  
        throw new Exception($e->getMessage());
    } 
}

    
    /**
     * DELETE um item do trava acrescimos
     */
    public static function deleteLimitProduto($data){
        try{
            db::getInstance()->beginTransaction();
                ProdutoAcrescimosModel::delete()->whereIn('idparmsacrescimo',$data['idparmsacrescimo'])->execute();
            db::getInstance()->commit();
            return true;
           }catch(Exception $e){
               db::getInstance()->rollback();  
               throw new Exception($e->getMessage());
           } 
    }

}
