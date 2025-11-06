<?php

/**
 * Controlador de Tipos de Despesa
 * @author: AI Assistant
 * @date: 2025-10-10
 */

namespace src\controllers;

use \core\Controller as ctrl;
use src\models\Tipo_despesa;
use Exception;

class TipoDespesaController extends ctrl
{
    /**
     * Campos obrigatórios para criação
     */
    const ADDCAMPOS = [
        'idempresa',
        'nome'
    ];

    /**
     * Campos obrigatórios para edição
     */
    const EDITCAMPOS = [
        'idtipo_despesa',
        'idempresa',
        'nome'
    ];

    /**
     * Campos obrigatórios para exclusão
     */
    const DELETECAMPOS = [
        'idtipo_despesa',
        'idempresa'
    ];

    /**
     * GET - Buscar todos os tipos de despesa de uma empresa
     * @route GET /getTiposDespesa/{idempresa}
     */
    public function getTiposDespesa($args)
    {
        try {
            $idempresa = $args['idempresa'];
            
            $result = Tipo_despesa::getAllByEmpresa($idempresa);
            
            ctrl::response($result, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * GET - Buscar um tipo de despesa por ID
     * @route GET /getTipoDespesa/{idtipo_despesa}/{idempresa}
     */
    public function getTipoDespesa($args)
    {
        try {
            $idtipo_despesa = $args['idtipo_despesa'];
            $idempresa = $args['idempresa'];
            
            $result = Tipo_despesa::getById($idtipo_despesa, $idempresa);
            
            if (empty($result)) {
                ctrl::response('Tipo de despesa não encontrado', 404);
            }
            
            ctrl::response($result, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * POST - Criar novo tipo de despesa
     * @route POST /addTipoDespesa
     */
    public function addTipoDespesa()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::ADDCAMPOS);
            
            // Validar se já existe um tipo com o mesmo nome
            if (Tipo_despesa::existeNome($data['nome'], $data['idempresa'])) {
                throw new Exception('Já existe um tipo de despesa com este nome');
            }
            
            // Criar tipo de despesa
            $dados = [
                'idempresa' => $data['idempresa'],
                'nome' => trim($data['nome']),
                'ativo' => 1
            ];
            
            $id = Tipo_despesa::criar($dados);
            
            ctrl::response(['idtipo_despesa' => $id], 201);
            
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * PUT - Editar tipo de despesa
     * @route PUT /editTipoDespesa
     */
    public function editTipoDespesa()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::EDITCAMPOS);
            
            // Verificar se o tipo de despesa existe
            $existe = Tipo_despesa::getById($data['idtipo_despesa'], $data['idempresa']);
            if (empty($existe)) {
                throw new Exception('Tipo de despesa não encontrado');
            }
            
            // Validar se já existe outro tipo com o mesmo nome
            if (Tipo_despesa::existeNome($data['nome'], $data['idempresa'], $data['idtipo_despesa'])) {
                throw new Exception('Já existe um tipo de despesa com este nome');
            }
            
            // Atualizar tipo de despesa
            $dados = [
                'nome' => trim($data['nome'])
            ];
            
            Tipo_despesa::atualizar($data['idtipo_despesa'], $data['idempresa'], $dados);
            
            ctrl::response('Tipo de despesa atualizado com sucesso', 200);
            
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * DELETE - Deletar tipo de despesa (soft delete)
     * @route DELETE /deleteTipoDespesa
     */
    public function deleteTipoDespesa()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::DELETECAMPOS);
            
            // Verificar se o tipo de despesa existe
            $existe = Tipo_despesa::getById($data['idtipo_despesa'], $data['idempresa']);
            if (empty($existe)) {
                throw new Exception('Tipo de despesa não encontrado');
            }
            
            // Desativar tipo de despesa (soft delete)
            Tipo_despesa::desativar($data['idtipo_despesa'], $data['idempresa']);
            
            ctrl::response('Tipo de despesa deletado com sucesso', 200);
            
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
}
