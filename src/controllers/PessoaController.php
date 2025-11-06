<?php

/**
 * Classe ProdutosController
 * Controlador de pessoa responsável por gerenciar operações relacionadas a pessoa.
 * 
 * @author João Silva
 * @since 23/05/2023
 */

namespace src\controllers;

use \core\Controller as ctrl;
use \src\handlers\Pessoa as PessoaHelp;
use Exception;

class PessoaController extends ctrl
{
    /**
     * Campos obrigatórios para adicionar uma Pessoa.
     */
    const ADDCAMPOS = [
        'idempresa', 'nome'
    ];

    /**
     * Campos obrigatórios para editar/excluir uma Pessoa.
     */
    const EDITCAMPOS = [
        'idempresa', 'idcliente'
    ];


    /**
     * Retorna todos os pessoa de uma empresa.
     * 
     * @param array $args Array contendo o ID da empresa.
     */
    public function getPessoas($args)
    {
        $pessoa = PessoaHelp::getPessoas($args['idempresa']);
        ctrl::response($pessoa, 200);
    }

    /**
     * Retorna um Pessoa específico de uma empresa.
     * 
     * @param array $args Array contendo o ID da empresa e o ID do Pessoa.
     */
    public function getPessoaById($args)
    {
        $pessoa = PessoaHelp::getPessoaById($args['idempresa'], $args['idpessoa']);
        if (!$pessoa) {
            ctrl::response('Pessoa não encontrado', 404);
        }
        ctrl::response($pessoa, 200);
    }

    /**
     * Adiciona um nova Pessoa
     */
    public function addPessoa()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::ADDCAMPOS);
            $pessoa = PessoaHelp::addPessoa($data);
            ctrl::response($pessoa, 201);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Edita um Pessoa existente.
     */
    public function editPessoa()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::EDITCAMPOS);
            $pessoa = PessoaHelp::editPessoa($data);
            ctrl::response($pessoa, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Deleta uma Pessoa 
     */
    public function deletePessoa()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::EDITCAMPOS);
            $pessoa = PessoaHelp::deletePessoa($data);
            ctrl::response($pessoa, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
}
