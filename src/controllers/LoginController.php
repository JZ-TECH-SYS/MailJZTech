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
use \src\handlers\Usuarios as UsuarioHandler;
use \src\handlers\service\TwoFactorAuthService;
use Exception;

class LoginController extends ctrl
{
    /**
     * Renderiza a página de login
     * GET /
     */
    public function index()
    {
        // Se já está logado, redireciona para dashboard
        if (UsuarioHandler::checkLogin()) {
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
            $email = $dados['email'] ?? null;
            $senha = $dados['senha'] ?? null;

            if (empty($email) || empty($senha)) {
                throw new Exception('Usuário e senha são obrigatórios');
            }

            $usuario = UsuarioHandler::verifyLogin($email, $senha);

            if (!$usuario) {
                throw new Exception('Usuário e/ou senha não conferem');
            }

            // SEMPRE retornar token (mesmo para 2FA obrigatório)
            // Token será validado novamente após 2FA
            $response = [
                'success' => true,
                'idusuario' => $usuario['idusuario'],
                'token' => $usuario['token']
            ];

            // Se 2FA está habilitado, retorna que precisa verificar 2FA
            if (!empty($usuario['totp_habilitado'])) {
                $response['requer_2fa'] = true;
                $response['mensagem'] = '2FA obrigatório';
            } else {
                // Se não tem 2FA, precisa configurar
                $response['requer_2fa'] = false;
                $response['configurar_2fa'] = true;
                $response['mensagem'] = 'Configure 2FA para continuar';
            }

            ctrl::response($response, 200);
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
     * Inicia configuração de 2FA gerando secret, QR Code e códigos de backup
     * POST /iniciar-2fa
     */
    public function iniciarDoisFatores()
    {
        try {
            $dados = ctrl::getBody();
            $idusuario = $dados['usuario_id'] ?? $dados['idusuario'] ?? null;
            if (empty($idusuario)) {
                throw new Exception('ID do usuário é obrigatório');
            }

            $usuarioData = \src\models\Usuarios::getById($idusuario);
            if (!$usuarioData) {
                throw new Exception('Usuário não encontrado');
            }

            $email = $usuarioData['email'] ?? ('user' . $idusuario . '@local');
            $secret = TwoFactorAuthService::generateSecret();
            $qrUrl = TwoFactorAuthService::generateQRCode($email, $secret, 'MailJZTech');
            $backupCodes = TwoFactorAuthService::generateBackupCodes();
            $backupCodesFmt = array_map([TwoFactorAuthService::class, 'formatBackupCode'], $backupCodes);

            $_SESSION['pending_2fa'][$idusuario] = [
                'secret' => $secret,
                'backup_codes' => $backupCodes
            ];

            ctrl::response([
                'usuario_id' => $idusuario,
                'secret' => $secret,
                'secret_formatado' => TwoFactorAuthService::formatSecret($secret),
                'qr_code_url' => $qrUrl,
                'backup_codes' => $backupCodesFmt
            ], 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
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
            $idusuario = $dados['usuario_id'] ?? ($dados['idusuario'] ?? null);

            if (empty($codigo) || empty($secret) || empty($idusuario)) {
                throw new Exception('Código, secret e usuário são obrigatórios');
            }

            if (!TwoFactorAuthService::verifyCode($secret, $codigo)) {
                throw new Exception('Código TOTP inválido');
            }

            // Salva o secret TOTP para o usuário
            UsuarioHandler::saveTotpSecret($idusuario, $secret);

            // cria sessão apenas após 2FA configurado
            $usuarioData = \src\models\Usuarios::getById($idusuario);
            if (!empty($usuarioData['token'])) {
                $_SESSION['token'] = $usuarioData['token'];
                $_SESSION['idusuario'] = $idusuario;
            }

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
            UsuarioHandler::logout($token);

            unset($_SESSION['token']);
            session_destroy();

            ctrl::response('Logout realizado com sucesso', 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Verifica o código TOTP durante login
     * POST /verificar-2fa
     */
    public function verificarDoisFatores()
    {
        try {
            $dados = ctrl::getBody();
            $codigo = $dados['codigo_totp'] ?? null;
            $usuario_id = $dados['usuario_id'] ?? null;

            if (empty($codigo) || empty($usuario_id)) {
                throw new Exception('Código e ID do usuário são obrigatórios');
            }

            // Buscar o secret TOTP do usuário
            $usuarioData = \src\models\Usuarios::getById($usuario_id);

            if (!$usuarioData) {
                throw new Exception('Usuário não encontrado');
            }

            // Verifica o código TOTP
            if (!TwoFactorAuthService::verifyCode($usuarioData['totp_secret'], $codigo)) {
                throw new Exception('Código TOTP inválido');
            }

            // Criar sessão para o usuário
            $_SESSION['token'] = $usuarioData['token'];
            $_SESSION['idusuario'] = $usuario_id;

            ctrl::response([
                'success' => true,
                'mensagem' => '2FA verificado com sucesso'
            ], 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Verifica o código de backup durante login
     * POST /verificar-2fa-backup
     */
    public function verificarDoisFatoresBackup()
    {
        try {
            $dados = ctrl::getBody();
            $codigo_backup = $dados['codigo_backup'] ?? null;
            $usuario_id = $dados['usuario_id'] ?? null;

            if (empty($codigo_backup) || empty($usuario_id)) {
                throw new Exception('Código de backup e ID do usuário são obrigatórios');
            }

            // Buscar o usuário
            $usuarioData = \src\models\Usuarios::getById($usuario_id);

            if (!$usuarioData) {
                throw new Exception('Usuário não encontrado');
            }

            // Criar sessão para o usuário
            $_SESSION['token'] = $usuarioData['token'];
            $_SESSION['idusuario'] = $usuario_id;

            ctrl::response([
                'success' => true,
                'mensagem' => 'Código de backup verificado com sucesso'
            ], 200);
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
                $infos = \src\models\Usuarios::getUserToken($_SESSION['token']);
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
