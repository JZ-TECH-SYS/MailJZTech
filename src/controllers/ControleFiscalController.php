<?php

namespace src\controllers;

use \core\Controller as ctrl;
use Exception;
use src\handlers\ControleFiscal as ControleFiscalHandler;

class ControleFiscalController extends ctrl
{
    // Campos obrigatórios para adicionar uma nova regra fiscal
    const ADDCAMPOS = [
        'idempresa', 'idusuario', 'descricao', 'apartirde'
    ];

    // Campos obrigatórios para editar uma regra fiscal
    const EDITCAMPOS = [
        'idempresa', 'idcontrole_fiscal'
    ];

    /**
     * Busca todas as regras fiscais de uma empresa
     */
    public function regrasFiscais($args)
    {
        try {
            $regras = ControleFiscalHandler::getRegrasFiscais($args['idempresa']);
            ctrl::response($regras, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Busca uma regra fiscal específica por ID
     */
    public function getRegraFiscalById($args)
    {
        try {
            $regra = ControleFiscalHandler::getRegraFiscalById($args['idempresa'], $args['idregra_fiscal']);
            ctrl::response($regra, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Adiciona uma nova regra fiscal
     */
    public function addRegraFiscal()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::ADDCAMPOS);
            $regra = ControleFiscalHandler::addRegraFiscal($data);
            if (!$regra) {
                throw new Exception('Erro ao adicionar regra fiscal');
            }
            ctrl::response($regra, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Edita uma regra fiscal existente
     */
    public function editRegraFiscal()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::EDITCAMPOS);
            $regra = ControleFiscalHandler::editRegraFiscal($data);
            if (!$regra) {
                throw new Exception('Erro ao editar regra fiscal');
            }
            ctrl::response($regra, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Exclui uma regra fiscal
     */
    public function deleteRegraFiscal()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::EDITCAMPOS);
            $regra = ControleFiscalHandler::deleteRegraFiscal($data);
            if (!$regra) {
                throw new Exception('Erro ao excluir regra fiscal');
            }
            ctrl::response($regra, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
}
