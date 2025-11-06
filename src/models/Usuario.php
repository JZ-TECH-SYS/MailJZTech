<?php

namespace src\models;

use core\Database;
use \core\Model;
use PDO;

/**
 * Classe modelo para a tabela 'usuarios' do banco de dados.
 * Gerencia dados de autenticação e 2FA.
 *
 * @author MailJZTech
 * @date 2025-01-01
 */
class Usuario extends Model
{
    /**
     * Busca informações do usuário com base no token fornecido.
     *
     * @param string $token O token de autenticação do usuário
     * @return array|false Retorna um array associativo contendo as informações do usuário
     */
    public static function getUserToken($token)
    {
        return self::select()
            ->where('token', $token)
            ->where('status', 'ativo')
            ->one();
    }

    /**
     * Busca informações do usuário com base no nome fornecido.
     *
     * @param string $nome O nome de usuário
     * @return array|false Retorna um array associativo contendo as informações do usuário
     */
    public static function getUserName($nome)
    {
        return self::select()
            ->where('nome', $nome)
            ->where('status', 'ativo')
            ->one();
    }

    /**
     * Busca informações do usuário com base no email fornecido.
     *
     * @param string $email O email do usuário
     * @return array|false Retorna um array associativo contendo as informações do usuário
     */
    public static function getUserEmail($email)
    {
        return self::select()
            ->where('email', $email)
            ->where('status', 'ativo')
            ->one();
    }

    /**
     * Atualiza o token de autenticação para um usuário específico.
     *
     * @param string $token O novo token de autenticação
     * @param string $nome O nome de usuário
     * @return bool Retorna true se a atualização foi bem-sucedida
     */
    public static function saveToken($token, $nome)
    {
        return self::update(['token' => $token, 'ultimo_acesso' => date('Y-m-d H:i:s')])
            ->where('nome', $nome)
            ->execute();
    }

    /**
     * Atualiza o secret TOTP e marca 2FA como habilitado.
     *
     * @param int $idusuario ID do usuário
     * @param string $secret Secret TOTP
     * @param array $backup_codes Códigos de backup
     * @return bool Retorna true se a atualização foi bem-sucedida
     */
    public static function saveTotpSecret($idusuario, $secret, $backup_codes = [])
    {
        return self::update([
            'totp_secret' => $secret,
            'totp_habilitado' => true,
            'backup_codes' => json_encode($backup_codes)
        ])
            ->where('idusuario', $idusuario)
            ->execute();
    }

    /**
     * Limpa o token de um usuário (logout).
     *
     * @param string $token O token a ser limpo
     * @return bool Retorna true se a limpeza foi bem-sucedida
     */
    public static function clearToken($token)
    {
        return self::update(['token' => null])
            ->where('token', $token)
            ->execute();
    }

    /**
     * Cria um novo usuário.
     *
     * @param array $dados Dados do usuário (nome, email, senha, etc)
     * @return int|false Retorna o ID do usuário criado ou false
     */
    public static function criar($dados)
    {
        return self::insert($dados)->execute();
    }

    /**
     * Busca um usuário por ID.
     *
     * @param int $idusuario ID do usuário
     * @return array|false Retorna os dados do usuário
     */
    public static function getById($idusuario)
    {
        return self::select()
            ->where('idusuario', $idusuario)
            ->one();
    }

    /**
     * Lista todos os usuários ativos.
     *
     * @return array Retorna um array com todos os usuários
     */
    public static function listar()
    {
        return self::select()
            ->where('status', 'ativo')
            ->get();
    }

    /**
     * Atualiza dados de um usuário.
     *
     * @param int $idusuario ID do usuário
     * @param array $dados Dados a atualizar
     * @return bool Retorna true se atualizado com sucesso
     */
    public static function atualizar($idusuario, $dados)
    {
        return self::update($dados)
            ->where('idusuario', $idusuario)
            ->execute();
    }

    /**
     * Deleta um usuário (soft delete - marca como inativo).
     *
     * @param int $idusuario ID do usuário
     * @return bool Retorna true se deletado com sucesso
     */
    public static function deletar($idusuario)
    {
        return self::update(['status' => 'inativo'])
            ->where('idusuario', $idusuario)
            ->execute();
    }
}
