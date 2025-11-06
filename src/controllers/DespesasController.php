<?php

/**
 * Classe DespesasController
 * Controlador de Despesas responsável por gerenciar operações relacionadas a Despesas.
 * 
 * @author João Silva
 * @since 23/05/2023
 */

namespace src\controllers;

use \core\Controller as ctrl;
use Exception;
use \src\handlers\Despesas as DespesasHelp;
use \src\handlers\Produtos as ProdutosHelp;


class DespesasController extends ctrl
{
    /**
     * Campos obrigatórios para adicionar uma Despesas.
     */
    const ADDCAMPOS = [
        'idempresa','tipo_despesa','valor','descricao','datahora'
    ];

    /**
     * Campos obrigatórios para editar/excluir uma Despesas.
     */
    const EDITCAMPOS = [
        'iddespesas','idempresa','tipo_despesa','valor','descricao','datahora'
    ];

    const DELETE = [
        'iddespesas','idempresa'
    ];

    /**
     * Retorna todos os Despesas de uma empresa.
     * 
     * @param array $args Array contendo o ID da empresa.
     */
    public function getDespesas($args)
    {
        $pessoa = DespesasHelp::getDespesas($args['idempresa']);
        ctrl::response($pessoa, 200);
    }

    /**
     * Retorna um Despesas específico de uma empresa.
     * 
     * @param array $args Array contendo o ID da empresa e o ID do Pessoa.
     */
    public function getDespesasById($args)
    {
        $pessoa = DespesasHelp::getDespesasById($args['idempresa'], $args['iddespesa']);
        if (!$pessoa) {
            ctrl::response('Despesas não encontrado', 404);
        }
        ctrl::response($pessoa, 200);
    }

    /**
     * Adiciona um nova Despesas
     */
    public function addDespesa()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::ADDCAMPOS);
            $pessoa = DespesasHelp::addDespesas($data);
            ctrl::response($pessoa, 201);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Edita um Despesas existente.
     */
    public function editDespesa()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::EDITCAMPOS);
            $pessoa = DespesasHelp::editDespesas($data);
            ctrl::response($pessoa, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Deleta uma Despesas 
     */
    public function deleteDespesa()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::DELETE);
            $pessoa = DespesasHelp::deleteDespesas($data);
            ctrl::response($pessoa, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Retorna despesas de uma empresa por período
     * 
     * @param array $args Array contendo o ID da empresa, data inicial e final.
     */
    public function getDespesasPorPeriodo($args)
    {
        $despesas = DespesasHelp::getDespesasPorPeriodo($args['idempresa'], $args['dataini'], $args['datafim']);
        if (!$despesas) {
            ctrl::response('Nenhuma despesa encontrada no período', 404);
        }
        ctrl::response($despesas, 200);
    }

    /**
     * Retorna despesas agrupadas por categoria
     * 
     * @param array $args Array contendo o ID da empresa, data inicial e final.
     */
    public function getDespesasPorCategoria($args)
    {
        $despesas = DespesasHelp::getDespesasPorCategoria($args['idempresa'], $args['dataini'], $args['datafim']);
        if (!$despesas) {
            ctrl::response('Nenhuma despesa encontrada no período', 404);
        }
        ctrl::response($despesas, 200);
    }
}
