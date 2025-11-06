<?php

/**
 * LoginController - Controlador de autenticação
 * Responsável por renderizar views de login e processar autenticação
 * 
 * @author MailJZTech
 * @date 2025-01-01
 */

namespace src\controllers;

use \core\Controller as ctrl;
use \src\handlers\Usuario as UsuarioHandler;
use Exception;

class LoginController extends ctrl
{
    private $usuarioHandler;

    public function __construct()
    {
        $this->usuarioHandler = new UsuarioHandler();
    }

    /**
     * Renderiza a página de login
     * GET /
     */
    public function index()
    {
        // Se já está logado, redireciona para dashboard
        if ($this->usuarioHandler->checkLogin()) {
            $this->redirect('/dashboard');
        }
        
        $this->render('login');
    }

    /**
     * Processa o login do usuário
     * POST /login
     */
    public function verificarLogin()
    {
        try {
            $dados = ctrl::getBody();
            $nome = $dados['nome'] ?? null;
            $senha = $dados['senha'] ?? null;

            if (empty($nome) || empty($senha)) {
                throw new Exception('Usuário e senha são obrigatórios');
            }

            $usuario = $this->usuarioHandler->verifyLogin($nome, $senha);
            
            if (!$usuario) {
                throw new Exception('Usuário e/ou senha não conferem');
            }

            // Se 2FA está habilitado, retorna que precisa verificar 2FA
            if ($usuario['totp_habilitado']) {
                ctrl::response([
                    'success' => true,
                    'requer_2fa' => true,
                    'idusuario' => $usuario['idusuario'],
                    'mensagem' => '2FA obrigatório'
                ], 200);
            } else {
                // Se não tem 2FA, precisa configurar
                ctrl::response([
                    'success' => true,
                    'requer_2fa' => false,
                    'configurar_2fa' => true,
                    'idusuario' => $usuario['idusuario'],
                    'token' => $usuario['token'],
                    'mensagem' => 'Configure 2FA para continuar'
                ], 200);
            }
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Renderiza a página de configuração de 2FA
     * GET /configurar-2fa (privado = true)
     */
    public function paginaConfigurar2FA()
    {
        $this->render('configurar_2fa');
    }

    /**
     * Processa a confirmação de 2FA
     * POST /confirmar-2fa (privado = true)
     */
    public function confirmarDoisFatores()
    {
        try {
            $dados = ctrl::getBody();
            $codigo = $dados['codigo'] ?? null;
            $secret = $dados['secret'] ?? null;
            $idusuario = $dados['idusuario'] ?? null;

            if (empty($codigo) || empty($secret)) {
                throw new Exception('Código e secret são obrigatórios');
            }

            // Verifica o código TOTP
            if (!$this->usuarioHandler->verifyTotp($secret, $codigo)) {
                throw new Exception('Código TOTP inválido');
            }

            // Salva o secret TOTP para o usuário
            $this->usuarioHandler->saveTotpSecret($idusuario, $secret);

            ctrl::response([
                'success' => true,
                'mensagem' => '2FA configurado com sucesso'
            ], 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Renderiza a página de verificação de 2FA
     * GET /verificar-2fa
     */
    public function paginaVerificar2FA()
    {
        $this->render('verificar_2fa');
    }

    /**
     * Realiza o logout do usuário
     * GET /sair (privado = true)
     */
    public function logout()
    {
        try {
            if (empty($_SESSION['token'])) {
                throw new Exception('Usuário não está logado');
            }

            $token = $_SESSION['token'];
            $this->usuarioHandler->logout($token);
            
            unset($_SESSION['token']);
            session_destroy();

            ctrl::response('Logout realizado com sucesso', 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Valida o token do usuário
     * GET /validaToken
     */
    public function validaToken()
    {
        try {
            $headers = getallheaders();
            $tk = isset($headers['Authorization']) ? $headers['Authorization'] : null;
            $tk2 = isset($_REQUEST['jwt']) ? 'Bearer ' . $_REQUEST['jwt'] : null;
            $token = (!empty($tk) && strlen($tk) > 8) ? $tk : $tk2;

            if (isset($_SESSION['token']) && !empty($_SESSION['token']) && $token == 'Bearer ' . $_SESSION['token']) {
                $usuario = new \src\models\Usuario();
                $infos = $usuario->getUserToken($_SESSION['token']);
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
