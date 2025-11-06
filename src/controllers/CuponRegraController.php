<?php

namespace src\controllers;

use \core\Controller as ctrl;
use \src\handlers\CuponRegra as CuponRegraHelper;
use Exception;

class CuponRegraController extends ctrl
{
    /**
     * Campos obrigatórios para adicionar uma Regra de Cupom.
     */
    const ADDCAMPOS = [
        'valor', 'descricao', 'quantidade_pedidos'
    ];

    /**
     * Campos obrigatórios para editar/excluir uma Regra de Cupom.
     */
    const EDITCAMPOS = [
        'idcuponregra'
    ];

    /**
     * Retorna todas as regras de cupons.
     */
    public function getCuponRegras()
    {
        $regras = CuponRegraHelper::getCuponRegras();
        ctrl::response($regras, 200);
    }

    /**
     * Retorna uma Regra de Cupom específica.
     * 
     * @param array $args Array contendo o ID da Regra.
     */
    public function getCuponRegraById($args)
    {
        $regra = CuponRegraHelper::getCuponRegraById($args['idcuponregra']);
        if (!$regra) {
            ctrl::response('Regra de cupom não encontrada', 404);
        }
        ctrl::response($regra, 200);
    }

    /**
     * Adiciona uma nova Regra de Cupom.
     */
    public function addCuponRegra()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::ADDCAMPOS);
            $regra = CuponRegraHelper::addCuponRegra($data);
            ctrl::response($regra, 201);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Edita uma Regra de Cupom existente.
     */
    public function editCuponRegra()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::EDITCAMPOS);
            $regra = CuponRegraHelper::editCuponRegra($data);
            ctrl::response($regra, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Deleta uma Regra de Cupom.
     */
    public function deleteCuponRegra()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::EDITCAMPOS);
            $regra = CuponRegraHelper::deleteCuponRegra($data);
            ctrl::response($regra, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
}
