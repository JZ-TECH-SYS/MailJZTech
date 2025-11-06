<?php

/**
 * Desc: Classe helper para gerenciar Pessoas no sistema
 * Autor: Joaosn
 * Data de Início: 23/05/2023
 * Data de Alteração: 08/06/2023 19:24
 */

namespace src\handlers;

use src\models\Pessoa as PessoaModel;
use Exception;
use src\models\Endereco as EnderecoModel;
use core\Database as db;
use Mpdf\Tag\Em;
use ClanCats\Hydrahon\Query\Sql\Func as H;

class Pessoa
{
    const RETORNO = [
        'pessoa.idcliente',
        'pessoa.idempresa',
        'pessoa.nome',
        'pessoa.cpf',
        'pessoa.celular',
        'e.idendereco',
        'e.endereco',
        'e.numero as numero_casa',
        'e.complemento',
        'c.id as idcidade',
        'c.nome as cidade',
        'es.id as idestado',
        'es.nome as estado',
        'es.uf'
    ];

    /**
     * @var \PDOStatement
     */
    public static function condicao ()
    {
        return PessoaModel::select(self::RETORNO)
        ->leftjoin('endereco as e', function ($join) {
            $join->on('e.idempresa', '=', 'pessoa.idempresa')
                ->on('e.idcliente', '=', 'pessoa.idcliente');
        })
        ->leftjoin('cidade as c','c.id','=','e.idcidade')
        ->leftjoin('estado as es','es.id','=','c.uf');
    }

    /**
     * Busca todas as pessoas associadas a uma empresa específica.
     *
     * @param int $idempresa O ID da empresa.
     * @return array Lista de pessoas associadas à empresa.
     */
    public static function getPessoas($idempresa)
    {
        $pessoa = self::condicao()
            ->where('pessoa.idempresa', $idempresa)
        ->execute();
        return $pessoa;
    }

    /**
     * Busca Pessoa por nome ou celualr
     */
    public static function getPessoaByNomeOrCelular($idempresa, $nome, $celular)
    {
        $pessoa = self::condicao()
                    ->where('pessoa.idempresa', $idempresa)
                    ->Where('pessoa.celular', $celular)
        ->one();
        return $pessoa;
    }

    /**
     * Busca uma pessoa específica associada a uma empresa usando o ID da pessoa.
     *
     * @param int $idempresa O ID da empresa.
     * @param int $idcliente O ID da pessoa (cliente).
     * @return array|bool Dados da pessoa encontrada ou false se não encontrada.
     */
    public static function getPessoaById($idempresa, $idcliente)
    {
        $pessoa = self::condicao()
                    ->where('pessoa.idempresa', $idempresa)
                    ->where('pessoa.idcliente', $idcliente)
        ->one();
        return $pessoa;
    }

    /**
     * Adiciona uma nova pessoa ao sistema e retorna a pessoa recém-criada.
     *
     * @param array $data Dados da pessoa a serem adicionados.
     * @return array Dados da pessoa recém-criada.
     */
    public static function addPessoa($data)
    {
        // Verifica se o CPF já está cadastrado para a empresa específica
        $isCpf = PessoaModel::isCpf($data);
        if (!empty($isCpf)) {
            throw new Exception('CPF/Celular já cadastrado Add');
        }

        try{
            db::getInstance()->beginTransaction();
            $dadosPessoa = [
                'idempresa'   => $data['idempresa'],
                'nome'        => $data['nome']        ?? null,
                'cpf'         => $data['cpf']         ?? null,
                'celular'     => $data['celular']     ?? null 
            ];
            $id =  PessoaModel::insert($dadosPessoa)->execute();
    
            $dadosEndereco = [
                'idempresa'   => $data['idempresa'],
                'idcliente'   => $id,
                'endereco'    => $data['endereco']    ?? null,
                'numero'      => $data['numero_casa'] ?? null,
                'complemento' => $data['complemento'] ?? null,
                'idcidade'    => $data['idcidade']    ?? null
            ];
            EnderecoModel::insert($dadosEndereco)->execute();
            db::getInstance()->commit();
        }catch(Exception $e){
            db::getInstance()->rollBack();
            throw new Exception('Erro ao cadastrar pessoa Contate o administrador do sistema!'. $e->getMessage());
        }
      
        // Retorna a pessoa recém-criada usando o ID da empresa e o ID da pessoa
        return self::getPessoaById($data['idempresa'], $id);
    }

    /**
     * Adiciona uma nova pessoa ao sistema e retorna a pessoa recém-criada.
     *
     * @param array $data Dados da pessoa a serem adicionados.
     * @return array Dados da pessoa recém-criada.
     */
    public static function addPessoaOnline($data)
    {
        try{
            $numero = (substr($data['celular'], 0, 2) == "55") ? substr($data['celular'], 2) : $data['celular'];
            $id = PessoaModel::select()->where('idempresa', $data['idempresa'])->where('celular', $numero)->one();
            if (empty($id)) {
                $dadosPessoa = [
                    'idempresa'   => $data['idempresa'],
                    'nome'        => $data['nome']        ?? null,
                    'cpf'         => $data['cpf']         ?? null,
                    'celular'     => $numero              ?? null 
                ];
                $id =  PessoaModel::insert($dadosPessoa)->execute();
            }

            $idcliente = $id['idcliente'] ?? $id;
            $endereco = EnderecoModel::select()->where('idempresa', $data['idempresa'])->where('idcliente', $idcliente)->one();
            if (empty($endereco)) {
                $dadosEndereco = [
                    'idempresa'   => $data['idempresa'],
                    'idcliente'   => $idcliente,
                    'endereco'    => $data['endereco']    ?? null,
                    'numero'      => $data['numero']      ?? null,
                    'complemento' => $data['complemento'] ?? null,
                    'idcidade'    => $data['idcidade']    ?? null,
                    'idbairro'    => $data['idbairro']    ?? null,
                ];
                EnderecoModel::insert($dadosEndereco)->execute();
            }
            
            return self::getPessoaById($data['idempresa'], $idcliente);
        }catch(Exception $e){
            throw new Exception('Erro ao cadastrar pessoa Contate o administrador do sistema!'. $e->getMessage());
        }   
        // Retorna a pessoa recém-criada usando o ID da empresa e o ID da pessoa
    }

    /**
     * Atualiza os detalhes de uma pessoa no sistema e retorna a pessoa atualizada.
     *
     * @param array $data Dados atualizados da pessoa.
     * @return array Dados da pessoa atualizada.
     */
    public static function editPessoa($data)
    {
        // Obtém a pessoa que será editada usando o ID da empresa e o ID da pessoa
        $pessoa   = PessoaModel::select()->where('idempresa', $data['idempresa'])->where('idcliente', $data['idcliente'])->one();
        $endereco = EnderecoModel::select()->where('idempresa', $data['idempresa'])->where('idcliente', $data['idcliente'])->one();
        if (empty($pessoa)) {
            throw new Exception('Pessoa não encontrada');
        }

        if (($pessoa['cpf'] != $data['cpf'])  || ($pessoa['celular'] != $data['celular'])) {
            // Verifica se o CPF já está cadastrado para a empresa específica
            $isCpf = PessoaModel::isCpf($data);
            if (!empty($isCpf)) {
                throw new Exception('CPF/Celular já cadastrado');
            }
        }

        // Atualiza os detalhes da pessoa no banco de dados usando os dados fornecidos
        // Se os dados não forem fornecidos, mantém os valores existentes
        $dadosPessoa = [
            'nome'         => $data['nome']        ?? $pessoa['nome'],
            'cpf'          => $data['cpf']         ?? $pessoa['cpf'],
            'celular'      => $data['celular']     ?? $pessoa['celular']
        ];

        $dadosEnderco = [
            'endereco'     => $data['endereco']    ?? $endereco['endereco'],
            'numero'       => $data['numero_casa'] ?? $endereco['numero'],
            'complemento'  => $data['complemento'] ?? $endereco['complemento'],
            'idcidade'     => $data['idcidade']    ?? $endereco['idcidade']
        ];

        try{
            db::getInstance()->beginTransaction();
            PessoaModel::update($dadosPessoa)
                ->where('idempresa', $data['idempresa'])
                ->where('idcliente', $data['idcliente'])
            ->execute();

            EnderecoModel::update($dadosEnderco)
                ->where('idempresa', $data['idempresa'])
                ->where('idcliente', $data['idcliente'])
            ->execute();    
            db::getInstance()->commit();
        }catch(Exception $e){
            db::getInstance()->rollBack();
            throw new Exception('Erro ao atualizar pessoa Contate o administrador do sistema!');
        }
    
        // Retorna a pessoa atualizada usando o ID da empresa e o ID da pessoa
        return self::getPessoaById($data['idempresa'], $data['idcliente']);
    }

    /**
     * Exclui uma pessoa do sistema e retorna uma mensagem de sucesso.
     *
     * @param array $data Dados da pessoa a serem excluídos (ID da empresa e ID da pessoa).
     * @return array Mensagem indicando que a pessoa foi excluída com sucesso.
     */
    public static function deletePessoa($data)
    {
        try{
            // Remove a pessoa do banco de dados usando o ID da empresa e o ID da pessoa
            PessoaModel::delete()
                ->where('idempresa', $data['idempresa'])
                ->where('idcliente', $data['idcliente'])
            ->execute();
    
            EnderecoModel::delete()
                ->where('idempresa', $data['idempresa'])
                ->where('idcliente', $data['idcliente'])
            ->execute();
        }catch(Exception $e){
            throw new Exception('Erro ao excluir pessoa Contate o administrador do sistema!');
        }
        // Retorna uma mensagem indicando que a pessoa foi excluída com sucesso
        return ['message' => 'Pessoa excluída com sucesso'];
    }

    public static function getInfosNFE($idcliente, $idempresa)
    {
        $pessoa = PessoaModel::getInfosNFE($idcliente, $idempresa);
        return $pessoa;
    }
}
