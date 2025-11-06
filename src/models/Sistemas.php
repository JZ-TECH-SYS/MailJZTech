<?php

namespace src\models;

use core\Model;

/**
 * Classe modelo para a tabela 'sistemas' do banco de dados.
 * Representa os sistemas/clientes que utilizam a API de envio de e-mails.
 *
 * @author MailJZTech
 * @date 2025-01-01
 */
class Sistemas extends Model
{
    /**
     * Obtém todos os sistemas ativos
     *
     * @return array Retorna um array com todos os sistemas
     */
    public function getAll()
    {
        return self::select()
            ->where('status', 'ativo')
            ->orderBy('data_criacao', 'DESC')
            ->get();
    }

    /**
     * Obtém um sistema pelo ID
     *
     * @param int $idsistema ID do sistema
     * @return array|false Retorna os dados do sistema
     */
    public function getById($idsistema)
    {
        return self::select()
            ->where('idsistema', $idsistema)
            ->where('status', 'ativo')
            ->one();
    }

    /**
     * Obtém um sistema pela chave de API
     *
     * @param string $chaveApi Chave de API do sistema
     * @return array|false Retorna os dados do sistema
     */
    public static function getByApiKey($chaveApi)
    {
        return self::select()
            ->where('chave_api', $chaveApi)
            ->where('status', 'ativo')
            ->one();
    }

    /**
     * Obtém sistemas por usuário
     *
     * @param int $idusuario ID do usuário
     * @return array Retorna um array com os sistemas do usuário
     */
    public function getByUsuario($idusuario)
    {
        return self::select()
            ->where('idusuario', $idusuario)
            ->where('status', 'ativo')
            ->orderBy('data_criacao', 'DESC')
            ->get();
    }

    /**
     * Cria um novo sistema
     *
     * @param array $dados Dados do sistema
     * @return int|false Retorna o ID do sistema criado
     */
    public function criar($dados)
    {
        return self::insert([
            'idusuario' => $dados['idusuario'],
            'nome' => $dados['nome'],
            'descricao' => $dados['descricao'] ?? null,
            'nome_remetente' => $dados['nome_remetente'],
            'email_remetente' => $dados['email_remetente'] ?? 'contato@jztech.com.br',
            'chave_api' => $dados['chave_api'],
            'status' => 'ativo'
        ])->execute();
    }

    /**
     * Atualiza um sistema
     *
     * @param int $idsistema ID do sistema
     * @param array $dados Dados a atualizar
     * @return bool Retorna true se atualizado com sucesso
     */
    public function atualizar($idsistema, $dados)
    {
        $dados['data_atualizacao'] = date('Y-m-d H:i:s');
        
        return self::update($dados)
            ->where('idsistema', $idsistema)
            ->execute();
    }

    /**
     * Desativa um sistema (soft delete)
     *
     * @param int $idsistema ID do sistema
     * @return bool Retorna true se desativado com sucesso
     */
    public function desativar($idsistema)
    {
        return self::update([
            'status' => 'inativo',
            'data_atualizacao' => date('Y-m-d H:i:s')
        ])
            ->where('idsistema', $idsistema)
            ->execute();
    }

    /**
     * Gera uma nova chave de API
     *
     * @return string Retorna a chave de API gerada
     */
    public static function gerarChaveApi()
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Regenera a chave de API de um sistema
     *
     * @param int $idsistema ID do sistema
     * @return string|false Retorna a nova chave de API
     */
    public function regenerarChaveApi($idsistema)
    {
        $novaChave = self::gerarChaveApi();
        
        $resultado = self::update([
            'chave_api' => $novaChave,
            'data_atualizacao' => date('Y-m-d H:i:s')
        ])
            ->where('idsistema', $idsistema)
            ->execute();

        return $resultado ? $novaChave : false;
    }

    /**
     * Atualiza o último uso do sistema
     *
     * @param int $idsistema ID do sistema
     * @return bool Retorna true se atualizado com sucesso
     */
    public function atualizarUltimoUso($idsistema)
    {
        return self::update(['ultimo_uso' => date('Y-m-d H:i:s')])
            ->where('idsistema', $idsistema)
            ->execute();
    }
}
