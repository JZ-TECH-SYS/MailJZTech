<?php

/**
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
            $this->redirect('dashboard');
        }

        $this->render('login');
    }

    /**
     * Processa o login do usuário - ROTA UNIFICADA
     * POST /login
     * 
     * Cenários suportados:
     * 1. Login inicial (email + senha)
     * 2. Login com 2FA (email + senha + codigo_2fa)
     * 3. Iniciar config 2FA (email + senha + acao: "iniciar_2fa")
     * 4. Confirmar config 2FA (email + senha + acao: "confirmar_2fa" + secret + codigo)
     * 5. Código backup (email + senha + codigo_backup)
     */
    public function verificarLogin()
    {
        try {
            $dados = ctrl::getBody();
            $email = $dados['email'] ?? null;
            $senha = $dados['senha'] ?? null;
            $acao = $dados['acao'] ?? null;

            // Validação básica
            if (empty($email) || empty($senha)) {
                throw new Exception('Usuário e senha são obrigatórios');
            }

            // SEMPRE valida credenciais primeiro
            $usuario = UsuarioHandler::verifyLogin($email, $senha);
            if (!$usuario) {
                throw new Exception('Usuário e/ou senha não conferem');
            }

            // Detecta cenário baseado no payload
            
            // CENÁRIO 1: Iniciar configuração 2FA (primeira vez)
            if ($acao === 'iniciar_2fa') {
                ctrl::response($this->iniciarDoisFatores($usuario), 200);
                return;
            }

            // CENÁRIO 2: Confirmar configuração 2FA
            if ($acao === 'confirmar_2fa') {
                $secret = $dados['secret'] ?? null;
                $codigo = $dados['codigo'] ?? null;
                ctrl::response($this->confirmarDoisFatores($usuario, $secret, $codigo), 200);
                return;
            }

            // CENÁRIO 3: Verificar código 2FA durante login
            if (!empty($dados['codigo_2fa'])) {
                ctrl::response($this->verificarCodigoTotp($usuario, $dados['codigo_2fa']), 200);
                return;
            }

            // CENÁRIO 4: Verificar código de backup
            if (!empty($dados['codigo_backup'])) {
                ctrl::response($this->verificarCodigoBackup($usuario, $dados['codigo_backup']), 200);
                return;
            }

            // CENÁRIO 5: Login inicial (apenas email + senha)
            // Verifica se usuário tem 2FA habilitado
            $usuarioTem2FA = $this->usuarioTemDoisFatoresAtivo($usuario);
            $response = [
                'success' => true,
                'idusuario' => $usuario['idusuario'],
                'token' => $usuario['token'],
                'totp_configurado' => $usuarioTem2FA
            ];

            if ($usuarioTem2FA) {
                // Tem 2FA: pedir código
                $response['requer_2fa'] = true;
                $response['configurar_2fa'] = false;
                $response['mensagem'] = 'Insira o código do autenticador';
            } else {
                // Não tem 2FA: precisa configurar
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
     * [INTERNO] Inicia configuração de 2FA gerando secret, QR Code e códigos de backup
     * @param array $usuario Dados do usuário
     * @return array Dados para configuração (QR, secret, backup codes)
     */
    private function iniciarDoisFatores($usuario)
    {
        $idusuario = $usuario['idusuario'];
        $email = $usuario['email'] ?? ('user' . $idusuario . '@local');

        if ($this->usuarioTemDoisFatoresAtivo($usuario)) {
            throw new Exception('2FA já configurado para este usuário');
        }
        
        $secret = TwoFactorAuthService::generateSecret();
        $qrUrl = TwoFactorAuthService::generateQRCode($email, $secret, 'MailJZTech');
        $backupCodes = TwoFactorAuthService::generateBackupCodes();
        $backupCodesFmt = array_map([TwoFactorAuthService::class, 'formatBackupCode'], $backupCodes);

        $_SESSION['pending_2fa'][$idusuario] = [
            'secret' => $secret,
            'backup_codes' => $backupCodes
        ];

        return [
            'success' => true,
            'usuario_id' => $idusuario,
            'secret' => $secret,
            'secret_formatado' => TwoFactorAuthService::formatSecret($secret),
            'qr_code_url' => $qrUrl,
            'backup_codes' => $backupCodesFmt
        ];
    }

    /**
     * [INTERNO] Processa a confirmação de 2FA
     * @param array $usuario Dados do usuário
     * @param string $secret Secret TOTP
     * @param string $codigo Código de 6 dígitos
     * @return array Resposta de sucesso
     */
    private function confirmarDoisFatores($usuario, $secret, $codigo)
    {
        $idusuario = $usuario['idusuario'];

        if (empty($codigo) || empty($secret)) {
            throw new Exception('Código e secret são obrigatórios');
        }

        if (!TwoFactorAuthService::verifyCode($secret, $codigo)) {
            throw new Exception('Código TOTP inválido');
        }

        // Salva o secret TOTP para o usuário
        UsuarioHandler::saveTotpSecret($idusuario, $secret);

        // Cria sessão após 2FA configurado
        $_SESSION['token'] = $usuario['token'];
        $_SESSION['idusuario'] = $idusuario;

        return [
            'success' => true,
            'mensagem' => '2FA configurado com sucesso'
        ];
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

            ctrl::redirect('/login');
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * [INTERNO] Verifica o código TOTP durante login
     * @param array $usuario Dados do usuário
     * @param string $codigo Código de 6 dígitos
     * @return array Resposta de sucesso
     */
    private function verificarCodigoTotp($usuario, $codigo)
    {
        if (empty($codigo)) {
            throw new Exception('Código é obrigatório');
        }

        if (empty($usuario['totp_secret'])) {
            throw new Exception('Usuário não possui 2FA configurado');
        }

        // Verifica o código TOTP
        if (!TwoFactorAuthService::verifyCode($usuario['totp_secret'], $codigo)) {
            throw new Exception('Código TOTP inválido');
        }

        // Criar sessão para o usuário
        $_SESSION['token'] = $usuario['token'];
        $_SESSION['idusuario'] = $usuario['idusuario'];

        return [
            'success' => true,
            'mensagem' => '2FA verificado com sucesso'
        ];
    }

    /**
     * [INTERNO] Verifica o código de backup durante login
     * @param array $usuario Dados do usuário
     * @param string $codigo_backup Código de backup
     * @return array Resposta de sucesso
     */
    private function verificarCodigoBackup($usuario, $codigo_backup)
    {
        if (empty($codigo_backup)) {
            throw new Exception('Código de backup é obrigatório');
        }

        // TODO: Implementar validação real de código de backup
        // Por enquanto, aceita qualquer código (placeholder)

        // Criar sessão para o usuário
        $_SESSION['token'] = $usuario['token'];
        $_SESSION['idusuario'] = $usuario['idusuario'];

        return [
            'success' => true,
            'mensagem' => 'Código de backup verificado com sucesso'
        ];
    }

    /**
     * Normaliza o estado do 2FA garantindo que só consideramos habilitado quando há secret persistido
     */
private function usuarioTemDoisFatoresAtivo(array $usuario): bool
    {
        $possuiSecret = !empty($usuario['totp_secret']);
        $flagBruto = $usuario['totp_habilitado'] ?? false;

        $flagNormalizado = filter_var($flagBruto, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($flagNormalizado === null) {
            if (is_numeric($flagBruto)) {
                $flagNormalizado = intval($flagBruto) === 1;
            } elseif (is_string($flagBruto)) {
                $valor = strtolower(trim($flagBruto));
                $flagNormalizado = in_array($valor, ['true', '1', 'on', 'yes', 'sim'], true);
            } else {
                $flagNormalizado = (bool)$flagBruto;
            }
        }

        return $possuiSecret && $flagNormalizado;
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
                $infos = UsuarioHandler::checkLogin() ? ['token' => $_SESSION['token']] : null;
                if (!empty($infos)) {
                    ctrl::response($infos, 200);
                    return;
                }
            }
            throw new Exception('Token inválido');
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
}
