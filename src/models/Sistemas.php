<?php

namespace src\models;

use core\Model;

/**
 * Classe modelo para a tabela 'sistemas' do banco de dados.
 * Representa os sistemas/clientes que utilizam a API de envio de e-mails.
 */
class Sistemas extends Model
{
    /**
     * Obtém todos os sistemas
     */
    public static function getAll()
    {
        $sql = "SELECT * FROM sistemas WHERE ativo = 1 ORDER BY data_criacao DESC";
        return self::query($sql);
    }

    /**
     * Obtém um sistema pelo ID
     */
    public static function getById($idsistema)
    {
        $sql = "SELECT * FROM sistemas WHERE idsistema = ? AND ativo = 1";
        return self::query($sql, [$idsistema]);
    }

    /**
     * Obtém um sistema pela chave de API
     */
    public static function getByApiKey($chaveApi)
    {
        $sql = "SELECT * FROM sistemas WHERE chave_api = ? AND ativo = 1";
        $result = self::query($sql, [$chaveApi]);
        return !empty($result) ? $result[0] : null;
    }

    /**
     * Cria um novo sistema
     */
    public static function create($dados)
    {
        $sql = "INSERT INTO sistemas (nome, descricao, nome_remetente, email_remetente, chave_api, ativo) 
                VALUES (?, ?, ?, ?, ?, 1)";
        
        return self::query($sql, [
            $dados['nome'],
            $dados['descricao'] ?? null,
            $dados['nome_remetente'],
            $dados['email_remetente'] ?? 'contato@gztech.com.br',
            $dados['chave_api']
        ]);
    }

    /**
     * Atualiza um sistema
     */
    public static function update($idsistema, $dados)
    {
        $campos = [];
        $valores = [];

        if (isset($dados['nome'])) {
            $campos[] = "nome = ?";
            $valores[] = $dados['nome'];
        }

        if (isset($dados['descricao'])) {
            $campos[] = "descricao = ?";
            $valores[] = $dados['descricao'];
        }

        if (isset($dados['nome_remetente'])) {
            $campos[] = "nome_remetente = ?";
            $valores[] = $dados['nome_remetente'];
        }

        if (isset($dados['ativo'])) {
            $campos[] = "ativo = ?";
            $valores[] = $dados['ativo'] ? 1 : 0;
        }

        $campos[] = "data_atualizacao = NOW()";
        $valores[] = $idsistema;

        $sql = "UPDATE sistemas SET " . implode(", ", $campos) . " WHERE idsistema = ?";
        
        return self::query($sql, $valores);
    }

    /**
     * Desativa um sistema (soft delete)
     */
    public static function deactivate($idsistema)
    {
        $sql = "UPDATE sistemas SET ativo = 0, data_atualizacao = NOW() WHERE idsistema = ?";
        return self::query($sql, [$idsistema]);
    }

    /**
     * Gera uma nova chave de API
     */
    public static function generateApiKey()
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Atualiza a chave de API de um sistema
     */
    public static function updateApiKey($idsistema)
    {
        $novaChave = self::generateApiKey();
        $sql = "UPDATE sistemas SET chave_api = ?, data_atualizacao = NOW() WHERE idsistema = ?";
        self::query($sql, [$novaChave, $idsistema]);
        return $novaChave;
    }
}
