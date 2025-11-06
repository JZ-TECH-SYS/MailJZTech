<?php

/**
 * desc: helper de manipulação de Usuarios 
 * @autor: joaosn
 * @date: 23/05/2020
 */

namespace src\handlers;

use src\models\Parametrizacao_empresa as ParmsEmp;
use \src\models\Users;

class UserHandlers
{
    public $user;

    /**
     * Construtor da classe UserHandlers.
     */
    public function __construct()
    {
        $this->user = new Users;
    }

    /**
     * Verifica se o usuário está logado com base no token de sessão.
     *
     * @return bool Retorna true se o usuário estiver logado, caso contrário, retorna false.
     */
    public function checkLogin()
    {
        if (!empty($_SESSION['token'])) {
            $token = $_SESSION['token'];
            $data = $this->user->getUserToken($token);
            if ($data && count($data) > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verifica se o nome de usuário e a senha fornecidos são válidos.
     *
     * @param string $nome O nome de usuário fornecido
     * @param string $senha A senha fornecida
     * @return array|false Retorna um array contendo informações do usuário, incluindo o token, se a autenticação for bem-sucedida; caso contrário, retorna false.
     */
    public function verifyLogin($nome, $senha)
    {
        $user = $this->user->getUserName($nome);
        if (!empty($user)) {
            if (password_verify($senha, $user['senha'])) {
                $token = md5(time() . rand(0, 9999) . time());
                $this->user->saveToken($token, $nome, $user['iduser']);
                $user['token'] = $token;
                $_SESSION['token'] = $token;
                
                return $user;
            }
            return false;
        }
    }

    /**
     * Obtém a quantidade de logins permitidos para uma empresa específica.
     *
     * @param int $idempresa O ID da empresa cuja quantidade de logins permitidos será buscada
     * @return int Retorna o valor da quantidade de logins permitidos para a empresa
     */
    public static function getQuantidadeLoginPermitida($idempresa)
    {
        $quantia_login_permitida = ParmsEmp::select(['valor'])->where('idempresa', $idempresa)->where('idparametro', 1)->one();
        return $quantia_login_permitida['valor'];
    }
}
