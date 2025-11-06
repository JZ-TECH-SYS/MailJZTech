<?php

/**
 *Controlador de login de usuário.
 *@autor: joaosn
 *@date Inicio: 2023-05-23
 */

namespace src\controllers;

use \core\Controller as ctrl;
use Exception;
use \src\handlers\UserHandlers;
use \src\models\Users;


class LoginController extends ctrl
{

    public $helpUser;

    public function __construct()
    {
        $this->helpUser = new UserHandlers();
    }

    /**
     * Verifica e valida o login do usuário
     */
    public function verificarLogin()
    {
        try {
            $dados = ctrl::getBody();
            $nome = $dados['nome'];
            $senha = $dados['senha'];
            if (!empty($nome) && isset($senha)) {
                $infos = $this->helpUser->verifyLogin($nome, $senha);
                if (!empty($infos)) {
                    $_SESSION['empresa'] = $infos;
                    ctrl::response($infos, 200);
                } else {
                    throw new Exception('Nome e/ou senha não conferem');
                }
            } else {
                throw new Exception('Prencha dados corretamente!');
            }
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Realiza o logout do usuário, removendo a sessão e redirecionando para a tela de login
     */
    public function logout()
    {
        try {
            if (!isset($_SESSION['token']) && empty($_SESSION['token'])) {
                throw new Exception('Usuário não está logado');
            }
            Users::update(['token' => null])->where('token', $_SESSION['token'])->execute();
            unset($_SESSION['token']);
            ctrl::response('Logout realizado com sucesso!', 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Valida o token do usuário
     */
    public function validaToken()
    {
        try {
            $headers = getallheaders();
            $tk = isset($headers['Authorization']) ? $headers['Authorization'] : null;
            $tk2 = isset($_REQUEST['jwt']) ? 'Bearer ' . $_REQUEST['jwt'] : null;
            $token = (!empty($tk) && strlen($tk) > 8) ? $tk : $tk2;

            if (isset($_SESSION['token']) && !empty($_SESSION['token']) &&  $token == 'Bearer ' . $_SESSION['token']) {
                $user = new Users();
                $infos = $user->getUserToken($_SESSION['token']);
                if (!empty($infos)) {
                    ctrl::response($infos, 200);
                } else {
                    throw new Exception('Token inválido');
                }
            }
            throw new Exception('Token inválido');
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
}
