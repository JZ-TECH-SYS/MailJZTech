<?php

/**
 * Classe MenuController
 * Controlador de Menu responsável por gerenciar operações relacionadas a menu.
 * 
 * @author João Silva
 * @since 23/05/2023
 */

namespace src\controllers;

use \core\Controller as ctrl;
use Exception;
use \src\handlers\LimitarAcrescimos as limitAcrescimoHelp;

class LimitarAcrescimoController extends ctrl
{
    /**
     * Campos obrigatórios para adicionar uma Menu.
     */
    const ADDCAMPOS = [
        'idempresa', 'quantidade','idparmsacrescimo'
    ];

    /**
     * Campos obrigatórios para editar/excluir uma Menu.
     */
    const DELETECAMPOS = [
        'idempresa', 'idparmsacrescimo'
    ];

    /**
     * Retorna todos os Menus com Produtos de uma empresa.
     */
    public function getLimitProdutos($args){
        $pessoa = limitAcrescimoHelp::getLimitProdutos($args['idempresa']);
        ctrl::response($pessoa, 200);
    }

    /**
     * vinculas produtos no menu
     */
    public function addLimitProduto(){
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::ADDCAMPOS);
            $pessoa = limitAcrescimoHelp::addLimitProduto($data);
            ctrl::response($pessoa, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /*
    * Deleta vinculo de produtos no menu
    */
    public function deleteLimitProduto(){
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::DELETECAMPOS);
            $pessoa = limitAcrescimoHelp::deleteLimitProduto($data);
            ctrl::response($pessoa, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
}
