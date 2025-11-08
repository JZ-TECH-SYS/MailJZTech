<?php

/**
 * Handler Usuario - Lógica de autenticação e gerenciamento de usuários
 * @author MailJZTech
 * @date 2025-01-01
 */

namespace src\handlers;

use src\models\Usuarios as UsuarioModel;
use Exception;

class Usuarios
{
    /**
     * Verifica se o usuário está logado com base no token de sessão.
     *
     * @return bool Retorna true se o usuário estiver logado, caso contrário, retorna false.
     */
    public static function checkLogin()
    {
        if (!empty($_SESSION['token'])) {
            $token = $_SESSION['token'];
            $data = UsuarioModel::getUserToken($token);
            if ($data && count($data) > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verifica se o email e a senha fornecidos são válidos.
     *
     * @param string $email O email fornecido
     * @param string $senha A senha fornecida
     * @return array|false Retorna um array contendo informações do usuário, incluindo o token, se a autenticação for bem-sucedida; caso contrário, retorna false.
     */
    public static function verifyLogin($email, $senha)
    {
        $usuario = UsuarioModel::getUserEmail($email);
        //echo password_hash('0137', PASSWORD_BCRYPT);die;
        if (!empty($usuario)) {
            if (password_verify($senha, $usuario['senha'])) {
                $token = md5(time() . rand(0, 9999) . time());
                UsuarioModel::saveToken($token, $email);
                $usuario['token'] = $token;
                
                return $usuario;
            }
            return false;
        }
    }

    /**
     * Realiza o logout do usuário.
     *
     * @param string $token O token do usuário
     * @return bool Retorna true se o logout foi bem-sucedido
     */
    public static function logout($token)
    {
        return UsuarioModel::clearToken($token);
    }

    /**
     * Gera um secret TOTP para 2FA.
     *
     * @return string Retorna o secret TOTP
     */
    public static function generateTotpSecret()
    {
        // Gera um secret aleatório de 32 caracteres
        $secret = '';
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        for ($i = 0; $i < 32; $i++) {
            $secret .= $chars[rand(0, strlen($chars) - 1)];
        }
        return $secret;
    }

    /**
     * Gera códigos de backup para 2FA.
     *
     * @param int $quantidade Quantidade de códigos a gerar (padrão 10)
     * @return array Retorna um array com os códigos de backup
     */
    public static function generateBackupCodes($quantidade = 10)
    {
        $codes = [];
        for ($i = 0; $i < $quantidade; $i++) {
            $code = '';
            for ($j = 0; $j < 8; $j++) {
                $code .= rand(0, 9);
            }
            $codes[] = $code;
        }
        return $codes;
    }

    /**
     * Verifica se um código TOTP é válido.
     *
     * @param string $secret O secret TOTP
     * @param string $code O código TOTP fornecido
     * @return bool Retorna true se o código for válido
     */
    public static function verifyTotp($secret, $code)
    {
        // Implementação simples de verificação TOTP
        // Você pode usar uma biblioteca como "spomky-labs/otphp" para uma implementação mais robusta
        
        // Por enquanto, apenas verifica se o código tem 6 dígitos
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        // Aqui você implementaria a lógica real de verificação TOTP
        // usando a biblioteca apropriada
        // Exemplo com spomky-labs/otphp:
        // $totp = TOTP::create($secret);
        // return $totp->verify($code);

        return true; // Placeholder
    }

    /**
     * Salva o secret TOTP para um usuário.
     *
     * @param int $idusuario ID do usuário
     * @param string $secret Secret TOTP
     * @return bool Retorna true se a operação foi bem-sucedida
     */
    public static function saveTotpSecret($idusuario, $secret)
    {
        $backup_codes = self::generateBackupCodes(10);
        return UsuarioModel::saveTotpSecret($idusuario, $secret, $backup_codes);
    }
}
