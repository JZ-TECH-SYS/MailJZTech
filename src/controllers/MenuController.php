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
use \src\handlers\Menu as MenuHelp;
use \src\handlers\Produtos as ProdutosHelp;


class MenuController extends ctrl
{
    /**
     * Campos obrigatórios para adicionar uma Menu.
     */
    const ADDCAMPOS = [
        'idempresa', 'descricao', 'status'
    ];

    /**
     * Campos obrigatórios para editar/excluir uma Menu.
     */
    const EDITCAMPOS = [
        'idempresa', 'idmenu'
    ];

    /**
     * Campos obrigatórios para adicionar uma Menu.
     */
    const ADDVINCULOCAMPOS = [
        'idempresa', 'idmenu','produtos'
    ];

    /**
     * Campos obrigatórios para editar/excluir uma Menu.
     */
    const DELETEVINCULOCAMPOS = [
        'idempresa', 'idparmsmenu'
    ];

    const ORDENACAO_MENUS_CAMPOS = [
        'idempresa', 'menus'
    ];

    const ORDENACAO_PRODUTOS_CAMPOS = [
        'idempresa', 'produtos'
    ];


    /**
     * Retorna todos os Menus de uma empresa.
     * 
     * @param array $args Array contendo o ID da empresa.
     */
    public function getMenus($args)
    {
        $pessoa = MenuHelp::getMenus($args['idempresa']);
        ctrl::response($pessoa, 200);
    }

    /**
     * Retorna um menu específico de uma empresa.
     * 
     * @param array $args Array contendo o ID da empresa e o ID do Pessoa.
     */
    public function getMenuById($args)
    {
        $pessoa = MenuHelp::getMenuById($args['idempresa'], $args['idmenu']);
        if (!$pessoa) {
            ctrl::response('Menu não encontrado', 404);
        }
        ctrl::response($pessoa, 200);
    }

    /**
     * Adiciona um nova Menu
     */
    public function addMenu()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::ADDCAMPOS);
            $pessoa = MenuHelp::addMenu($data);
            ctrl::response($pessoa, 201);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Edita um menu existente.
     */
    public function editMenu()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::EDITCAMPOS);
            $pessoa = MenuHelp::editMenu($data);
            ctrl::response($pessoa, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Deleta uma Menu 
     */
    public function deleteMenu()
    {
        try {
            $data = ctrl::getBody();
           
            ctrl::verificarCamposVazios($data, self::EDITCAMPOS);
            $pessoa = MenuHelp::deleteMenu($data);
            ctrl::response($pessoa, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Retorna todos os Menus com Produtos de uma empresa.
     */
    public function getProdutosMenu($args){
        $produtosMenu = MenuHelp::getProdutoMenu($args['idempresa']);
        ctrl::response($produtosMenu, 200);
    }

    /**
     * Retorna todos os Menus com Produtos de uma empresa.
     */
    public function getProdutosMenuOn($args){
        $produtosMenuON = MenuHelp::getProdutoMenu($args['idempresa']);
        $acrescimos = ProdutosHelp::getProdutos($args['idempresa'],2);
        $acrescimos3 = ProdutosHelp::getProdutos($args['idempresa'],3);
        ctrl::response([
           'produtos_menu'=>$produtosMenuON
          ,'acrescimos'   => array_merge($acrescimos,$acrescimos3)
         ], 200);
    }

    /**
     * vinculas produtos no menu
     */
    public function addProdutoMenu(){
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::ADDVINCULOCAMPOS);
            $menu = MenuHelp::vincularProdutoMenu($data);
            ctrl::response($menu, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /*
    * Deleta vinculo de produtos no menu
    */
    public function deleteProdutoMenu(){
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::DELETEVINCULOCAMPOS);
            $pessoa = MenuHelp::deleteProdutoMenu($data);
            ctrl::response($pessoa, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Atualiza ordenação dos menus
     */
    public function ordenarMenus(){
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::ORDENACAO_MENUS_CAMPOS);
            MenuHelp::ordenarMenus($data['idempresa'], $data['menus']);
            ctrl::response(true, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Retorna as ordens disponíveis dos menus
     */
    public function getMenuOrders()
    {
        try {
            $idempresa = isset($_GET['idempresa']) ? (int)$_GET['idempresa'] : 0;
            $excludeMenuId = isset($_GET['excludeMenuId']) ? (int)$_GET['excludeMenuId'] : null;
            
            if (!$idempresa) {
                throw new Exception('ID da empresa é obrigatório.');
            }
            
            $orders = MenuHelp::getMenuOrders($idempresa, $excludeMenuId);
            ctrl::response($orders, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Atualiza ordenação dos produtos de um menu
     */
    public function ordenarProdutosMenu($args){
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::ORDENACAO_PRODUTOS_CAMPOS);
            MenuHelp::ordenarProdutosMenu($data['idempresa'], $args['idmenu'], $data['produtos']);
            ctrl::response(true, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Faz swap de ordem entre produtos de um menu
     */
    public function swapProdutoMenuOrdem($args){
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, ['idempresa', 'idparmsmenu', 'novaOrdem']);
            
            $result = MenuHelp::swapProdutoMenuOrdem(
                $data['idempresa'], 
                $args['idmenu'], 
                $data['idparmsmenu'], 
                $data['novaOrdem']
            );
            
            ctrl::response($result, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
}
