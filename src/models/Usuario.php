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
    public $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Busca informações do usuário com base no token fornecido.
     *
     * @param string $token O token de autenticação do usuário
     * @return array|false Retorna um array associativo contendo as informações do usuário
     */
    public function getUserToken($token)
    {
        $sql = "SELECT * FROM usuarios WHERE token = :token AND status = 'ativo'";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':token', $token);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Busca informações do usuário com base no nome fornecido.
     *
     * @param string $nome O nome de usuário
     * @return array|false Retorna um array associativo contendo as informações do usuário
     */
    public function getUserName($nome)
    {
        $sql = "SELECT * FROM usuarios WHERE nome = :nome AND status = 'ativo'";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':nome', $nome);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Busca informações do usuário com base no email fornecido.
     *
     * @param string $email O email do usuário
     * @return array|false Retorna um array associativo contendo as informações do usuário
     */
    public function getUserEmail($email)
    {
        $sql = "SELECT * FROM usuarios WHERE email = :email AND status = 'ativo'";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Atualiza o token de autenticação para um usuário específico.
     *
     * @param string $token O novo token de autenticação
     * @param string $nome O nome de usuário
     * @return bool Retorna true se a atualização foi bem-sucedida
     */
    public function saveToken($token, $nome)
    {
        $sql = "UPDATE usuarios SET token = :token, ultimo_acesso = NOW() WHERE nome = :nome";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':token', $token);
        $stmt->bindValue(':nome', $nome);
        return $stmt->execute();
    }

    /**
     * Atualiza o secret TOTP e marca 2FA como habilitado.
     *
     * @param int $idusuario ID do usuário
     * @param string $secret Secret TOTP
     * @param array $backup_codes Códigos de backup
     * @return bool Retorna true se a atualização foi bem-sucedida
     */
    public function saveTotpSecret($idusuario, $secret, $backup_codes = [])
    {
        $sql = "UPDATE usuarios SET totp_secret = :secret, totp_habilitado = TRUE, backup_codes = :backup_codes WHERE idusuario = :idusuario";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':idusuario', $idusuario);
        $stmt->bindValue(':secret', $secret);
        $stmt->bindValue(':backup_codes', json_encode($backup_codes));
        return $stmt->execute();
    }

    /**
     * Limpa o token de um usuário (logout).
     *
     * @param string $token O token a ser limpo
     * @return bool Retorna true se a limpeza foi bem-sucedida
     */
    public function clearToken($token)
    {
        $sql = "UPDATE usuarios SET token = NULL WHERE token = :token";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':token', $token);
        return $stmt->execute();
    }
}
