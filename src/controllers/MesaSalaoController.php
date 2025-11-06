<?php

namespace src\controllers;

use \core\Controller as ctrl;
use src\handlers\MesaSalao;

class MesaSalaoController extends ctrl
{
    const ADDCAMPOS = ['idempresa', 'numero_mesa'];
    const EDITCAMPOS = ['idmesa', 'idempresa'];

    /**
     * GET - Buscar todas as mesas
     * @route GET /getMesasSalao/{idempresa}
     */
    public function getMesasSalao($args)
    {
        try {
            $mesas = MesaSalao::getMesas($args['idempresa']);
            ctrl::response($mesas, 200);
        } catch (\Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * GET - Buscar mesas ativas (para autocomplete)
     * @route GET /getMesasSalaoAtivas/{idempresa}
     */
    public function getMesasSalaoAtivas($args)
    {
        try {
            $mesas = MesaSalao::getMesasAtivas($args['idempresa']);
            ctrl::response($mesas, 200);
        } catch (\Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * POST - Criar nova mesa
     * @route POST /addMesaSalao
     */
    public function addMesaSalao()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::ADDCAMPOS);
            $mesa = MesaSalao::criar($data);
            ctrl::response($mesa, 201);
        } catch (\Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * PUT - Editar mesa
     * @route PUT /editMesaSalao
     */
    public function editMesaSalao()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::EDITCAMPOS);
            $mesa = MesaSalao::editar($data);
            ctrl::response($mesa, 200);
        } catch (\Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * DELETE - Deletar mesa
     * @route DELETE /deleteMesaSalao/{idmesa}/{idempresa}
     */
    public function deleteMesaSalao($args)
    {
        try {
            MesaSalao::deletar($args['idmesa'], $args['idempresa']);
            ctrl::response(['message' => 'Mesa excluída com sucesso'], 200);
        } catch (\Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
}
