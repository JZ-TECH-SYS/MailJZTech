<?php

/**
 * Classe BairrosController
 * Controlador de Bairros responsável por gerenciar operações relacionadas a Bairros.
 * 
 * @author João Silva
 * @since 07/07/2023
 */

namespace src\controllers;

use \core\Controller as ctrl;
use \src\handlers\Bairro as BairroHelp;
use Exception;

class BairrosController extends ctrl
{
    /**
     * Campos obrigatórios para adicionar uma Pessoa.
     */
    const ADDCAMPOS = [
        'idempresa', 'nome','status','idcidade'
    ];

    /**
     * Campos obrigatórios para editar/excluir uma Pessoa.
     */
    const EDITCAMPOS = [
        'idempresa', 'idbairro'
    ];


    /**
     * Retorna todos os pessoa de uma empresa.
     * 
     * @param array $args Array contendo o ID da empresa.
     */
    public function getBairros($args)
    {
        // Parâmetros opcionais para Auto-CEP via query string
        $idcidade = $_GET['idcidade'] ?? null;
        $cep = $_GET['cep'] ?? null;
        
        $pessoa = BairroHelp::getBairros(
            $args['idempresa'], 
            $idcidade ? (int)$idcidade : null,
            $cep
        );
        
        ctrl::response($pessoa, 200);
    }

    /**
     * Retorna um Pessoa específico de uma empresa.
     * 
     * @param array $args Array contendo o ID da empresa e o ID do Pessoa.
     */
    public function getBairroById($args)
    {
        $pessoa = BairroHelp::getBairroById($args['idempresa'], $args['idbairro']);
        if (!$pessoa) {
            ctrl::response('Pessoa não encontrado', 404);
        }
        ctrl::response($pessoa, 200);
    }

    /**
     * Adiciona um nova Pessoa
     */
    public function addBairro()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::ADDCAMPOS);
            $pessoa = BairroHelp::addBairro($data);
            ctrl::response($pessoa, 201);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Edita um Pessoa existente.
     */
    public function editBairro()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::EDITCAMPOS);
            $pessoa = BairroHelp::editBairro($data);
            ctrl::response($pessoa, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Deleta uma Pessoa 
     */
    public function deleteBairro()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::EDITCAMPOS);
            $pessoa = BairroHelp::deleteBairro($data);
            ctrl::response($pessoa, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
}
