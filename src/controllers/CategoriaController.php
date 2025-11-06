<?php

/**
 * Classe CategoriaController
 * Controlador de Categorias responsável por gerenciar operações relacionadas a categorias de produtos.
 * 
 * @author ClickExpress
 * @since 28/10/2025
 */

namespace src\controllers;

use \core\Controller as ctrl;
use \src\handlers\Categoria as CategoriaHelp;
use Exception;

class CategoriaController extends ctrl
{
    /**
     * Campos obrigatórios para adicionar uma Categoria.
     */
    const ADDCAMPOS = [
        'idempresa', 'descricao', 'imprimir'
    ];

    /**
     * Campos obrigatórios para editar/excluir uma Categoria.
     */
    const EDITCAMPOS = [
        'idempresa', 'idcategoria'
    ];


    /**
     * Retorna todas as categorias de uma empresa.
     * 
     * @param array $args Array contendo o ID da empresa.
     */
    public function getCategorias($args)
    {
        $categorias = CategoriaHelp::getCategorias($args['idempresa']);
        ctrl::response($categorias, 200);
    }

    /**
     * Retorna uma categoria específica de uma empresa.
     * 
     * @param array $args Array contendo o ID da empresa e o ID da categoria.
     */
    public function getCategoriaById($args)
    {
        $categoria = CategoriaHelp::getCategoriaById($args['idempresa'], $args['idcategoria']);
        if (!$categoria) {
            ctrl::response('Categoria não encontrada', 404);
        }
        ctrl::response($categoria, 200);
    }

    /**
     * Adiciona uma nova Categoria
     */
    public function addCategoria()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::ADDCAMPOS);
            $categoria = CategoriaHelp::addCategoria($data);
            ctrl::response($categoria, 201);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Edita uma Categoria existente.
     */
    public function editCategoria()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::EDITCAMPOS);
            $categoria = CategoriaHelp::editCategoria($data);
            ctrl::response($categoria, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Deleta uma Categoria 
     */
    public function deleteCategoria()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::EDITCAMPOS);
            $categoria = CategoriaHelp::deleteCategoria($data);
            ctrl::response($categoria, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
}
