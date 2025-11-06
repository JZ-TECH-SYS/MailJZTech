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
use \src\handlers\TravaAcrescimos as TravaAcrescimosHelp;

class TravaAcrescimoController extends ctrl
{
    /**
     * Campos obrigatórios para adicionar uma Menu.
     */
    const ADDCAMPOS = [
        'idempresa', 'idproduto','idacrescimo'
    ];

    /**
     * Campos obrigatórios para editar/excluir uma Menu.
     */
    const DELETECAMPOS = [
        'idempresa', 'idparmsacrescimo'
    ];

    /**
     * Retorna todos os Produto com acrescimos vinculados  de uma empresa.
     */
    public function getTravaProdutos($args){
        $pessoa = TravaAcrescimosHelp::getLimitProdutos($args['idempresa']);
        ctrl::response($pessoa, 200);
    }

    /**
     * vinculas produtos no menu
     */
    public function addTravaProduto(){
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::ADDCAMPOS);
            $pessoa = TravaAcrescimosHelp::addLimitProduto($data);
            ctrl::response($pessoa, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /*
    * Deleta vinculo de produtos no menu
    */
    public function deleteTravaProduto(){
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::DELETECAMPOS);
            $pessoa = TravaAcrescimosHelp::deleteLimitProduto($data);
            ctrl::response($pessoa, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
}
