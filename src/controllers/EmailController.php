<?php

namespace src\controllers;

use core\Controller as ctrl;
use Exception;
use src\handlers\service\EmailService;
use src\models\Sistemas;
use src\models\Emails;

/**
 * EmailController - Responsável por gerenciar requisições de envio de e-mails
 */
class EmailController extends ctrl
{
    /**
     * Envia um e-mail através da API
     * 
     * Requisição POST /sendEmail
     * Headers: Authorization: Bearer {chave_api}
     * 
     * Body JSON:
     * {
     *   "destinatario": "usuario@example.com",
     *   "assunto": "Assunto do e-mail",
     *   "corpo_html": "<h1>Olá</h1>",
     *   "corpo_texto": "Olá (opcional)",
     *   "cc": ["cc@example.com"] ou "cc@example.com",
     *   "bcc": ["bcc@example.com"] ou "bcc@example.com",
     *   "anexos": [
     *     {
     *       "nome": "documento.pdf",
     *       "caminho": "/path/to/file.pdf"
     *     }
     *   ]
     * }
     */
    public function sendEmail()
    {
        try {
            // Validar autenticação
            $sistema = $this->validarApiKey();
            if (!$sistema) {
                throw new Exception('Chave de API inválida ou não fornecida');
            }

            // Obter dados da requisição
            $dados = ctrl::getBody();

            // Validar campos obrigatórios
            ctrl::verificarCamposVazios($dados, ['destinatario', 'assunto', 'corpo_html']);

            // Enviar e-mail
            $resultado = EmailService::sendEmail(
                $sistema['idsistema'],
                $dados['destinatario'],
                $dados['assunto'],
                $dados['corpo_html'],
                $dados['corpo_texto'] ?? null,
                $dados['cc'] ?? null,
                $dados['bcc'] ?? null,
                $dados['anexos'] ?? null,
                $sistema['nome_remetente']
            );

            if ($resultado['success']) {
                ctrl::response([
                    'mensagem' => $resultado['message'],
                    'idemail' => $resultado['idemail'],
                    'status' => $resultado['status']
                ], 200);
            } else {
                ctrl::response([
                    'mensagem' => $resultado['message'],
                    'idemail' => $resultado['idemail'] ?? null,
                    'erro' => $resultado['error']
                ], 400);
            }

        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Lista e-mails enviados por um sistema
     * 
     * GET /listarEmails?limite=50&pagina=1
     */
    public function listarEmails()
    {
        try {
            // Validar autenticação
            $sistema = $this->validarApiKey();
            if (!$sistema) {
                throw new Exception('Chave de API inválida ou não fornecida');
            }

            // Parâmetros de paginação
            $limite = $_GET['limite'] ?? 50;
            $pagina = $_GET['pagina'] ?? 1;
            $offset = ($pagina - 1) * $limite;

            // Obter e-mails
            $emails = Emails::getBySystem($sistema['idsistema'], $limite, $offset);
            $total = Emails::countBySystem($sistema['idsistema']);

            ctrl::response([
                'emails' => $emails,
                'total' => $total,
                'pagina' => $pagina,
                'limite' => $limite,
                'paginas_totais' => ceil($total / $limite)
            ], 200);

        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Obtém detalhes de um e-mail específico
     * 
     * GET /detalheEmail?idemail={id}
     */
    public function detalheEmail()
    {
        try {
            // Validar autenticação
            $sistema = $this->validarApiKey();
            if (!$sistema) {
                throw new Exception('Chave de API inválida ou não fornecida');
            }

            // Obter ID do e-mail da URL
            $idemail = $_GET['idemail'] ?? null;
            if (!$idemail) {
                throw new Exception('ID do e-mail não fornecido');
            }

            // Obter e-mail
            $email = Emails::getById($idemail);
            if (!$email) {
                throw new Exception('E-mail não encontrado');
            }

            // Verificar se o e-mail pertence ao sistema
            if ($email['idsistema'] != $sistema['idsistema']) {
                throw new Exception('Você não tem permissão para acessar este e-mail');
            }

            ctrl::response($email, 200);

        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Obtém estatísticas de e-mails de um sistema
     * 
     * GET /statsEmails
     */
    public function statsEmails()
    {
        try {
            // Validar autenticação
            $sistema = $this->validarApiKey();
            if (!$sistema) {
                throw new Exception('Chave de API inválida ou não fornecida');
            }

            // Obter estatísticas
            $stats = Emails::obterEstatisticas($sistema['idsistema']);

            ctrl::response($stats, 200);

        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Testa a configuração de e-mail
     * 
     * POST /testarEmail
     * Body: { "email_teste": "seu@email.com" }
     */
    public function testarEmail()
    {
        try {
            $dados = ctrl::getBody();
            ctrl::verificarCamposVazios($dados, ['email_teste']);

            $resultado = EmailService::testEmailConfiguration($dados['email_teste']);

            if ($resultado['success']) {
                ctrl::response([
                    'mensagem' => 'E-mail de teste enviado com sucesso',
                    'status' => 'enviado'
                ], 200);
            } else {
                ctrl::response([
                    'mensagem' => $resultado['message'],
                    'erro' => $resultado['error']
                ], 400);
            }

        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Valida a configuração de e-mail
     * 
     * GET /validarConfigEmail
     */
    public function validarConfigEmail()
    {
        try {
            $resultado = EmailService::validateEmailConfiguration();

            if ($resultado['valid']) {
                ctrl::response([
                    'mensagem' => 'Configuração de e-mail válida',
                    'status' => 'ok'
                ], 200);
            } else {
                ctrl::response([
                    'mensagem' => 'Configuração de e-mail inválida',
                    'erros' => $resultado['errors']
                ], 400);
            }

        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Renderiza a página de histórico de e-mails
     * GET /emails
     */
    public function index()
    {
        $this->render('emails');
    }

    /**
     * Valida a chave de API e retorna o sistema
     */
    private function validarApiKey()
    {
        // Obter header de autorização
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? null;

        if (!$authHeader) {
            return null;
        }

        // Extrair chave de API do header "Bearer {chave}"
        if (strpos($authHeader, 'Bearer ') === 0) {
            $chaveApi = substr($authHeader, 7);
            $sistema = Sistemas::getByApiKey($chaveApi);
            return $sistema;
        }

        return null;
    }
}
