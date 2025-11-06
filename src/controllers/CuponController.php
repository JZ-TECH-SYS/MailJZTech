<?php

namespace src\controllers;

use \core\Controller as ctrl;
use \src\handlers\Cupon as CuponHelper;
use Exception;

class CuponController extends ctrl
{
    /**
     * Campos obrigatórios para adicionar um Cupom.
     */
    const ADDCAMPOS = [
        'idempresa', 'valor','descricao'
    ];

    /**
     * Campos obrigatórios para editar/excluir um Cupom.
     */
    const EDITCAMPOS = [
        'idempresa', 'idcupon'
    ];

    /**
     * Retorna todos os cupons de uma empresa.
     * 
     * @param array $args Array contendo o ID da empresa.
     */
    public function getCupons($args)
    {
        $cupons = CuponHelper::getCupons($args['idempresa']);
        ctrl::response($cupons, 200);
    }

    /**
     * Retorna um Cupom específico de uma empresa.
     * 
     * @param array $args Array contendo o ID da empresa e o ID do Cupom.
     */
    public function getCuponById($args)
    {
        $cupom = CuponHelper::getCuponById($args['idempresa'], $args['idcupon']);
        if (!$cupom) {
            ctrl::response('Cupom não encontrado', 404);
        }
        ctrl::response($cupom, 200);
    }

    /**
     * Adiciona um novo Cupom.
     */
    public function addCupon()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::ADDCAMPOS);
            $data['idusuario'] = ctrl::getUsuario();
            $data['idempresa'] = ctrl::getEmpresa();
            $cupom = CuponHelper::addCupon($data);
            ctrl::response($cupom, 201);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Edita um Cupom existente.
     */
    public function editCupon()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::EDITCAMPOS);
            $data['idusuario'] = ctrl::getUsuario();
            $data['idempresa'] = ctrl::getEmpresa();
            $cupom = CuponHelper::editCupon($data);
            ctrl::response($cupom, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Deleta um Cupom.
     */
    public function deleteCupon()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::EDITCAMPOS);
            $cupom = CuponHelper::deleteCupon($data);
            ctrl::response($cupom, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
}
