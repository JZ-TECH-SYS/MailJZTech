<?php

namespace src\handlers;

use src\models\Emails as EmailsModel;
use src\models\EmailLogs as EmailLogsModel;
use src\handlers\service\EmailService;

/**
 * Handler para lógica de negócio de Emails
 * Gerencia envio, histórico e operações relacionadas a e-mails
 *
 * @author MailJZTech
 * @date 2025-01-01
 */
class Emails
{
    private $modelEmails;
    private $modelLogs;
    private $emailService;

    public function __construct()
    {
        $this->modelEmails = new EmailsModel();
        $this->modelLogs = new EmailLogsModel();
        $this->emailService = new EmailService();
    }

    /**
     * Envia um e-mail
     *
     * @param int $idsistema ID do sistema
     * @param int $idusuario ID do usuário
     * @param array $dados Dados do e-mail
     * @return array Retorna resultado da operação
     */
    public function enviar($idsistema, $idusuario, $dados)
    {
        // Validações
        if (empty($dados['destinatario'])) {
            return ['sucesso' => false, 'mensagem' => 'Destinatário é obrigatório'];
        }

        if (empty($dados['assunto'])) {
            return ['sucesso' => false, 'mensagem' => 'Assunto é obrigatório'];
        }

        if (empty($dados['corpo_html']) && empty($dados['corpo_texto'])) {
            return ['sucesso' => false, 'mensagem' => 'Corpo do e-mail é obrigatório'];
        }

        // Cria registro de e-mail
        $idEmail = $this->modelEmails->criar([
            'idsistema' => $idsistema,
            'idusuario' => $idusuario,
            'destinatario' => $dados['destinatario'],
            'cc' => $dados['cc'] ?? null,
            'bcc' => $dados['bcc'] ?? null,
            'assunto' => $dados['assunto'],
            'corpo_html' => $dados['corpo_html'] ?? null,
            'corpo_texto' => $dados['corpo_texto'] ?? null,
            'anexos' => isset($dados['anexos']) ? json_encode($dados['anexos']) : null,
            'status' => 'pendente'
        ]);

        if (!$idEmail) {
            $this->modelLogs->criar(null, $idsistema, $idusuario, 'erro', 'Falha ao criar registro de e-mail');
            return ['sucesso' => false, 'mensagem' => 'Erro ao criar registro de e-mail'];
        }

        // Tenta enviar o e-mail
        try {
            $resultado = $this->emailService->enviar(
                $dados['destinatario'],
                $dados['assunto'],
                $dados['corpo_html'] ?? $dados['corpo_texto'],
                $dados['nome_remetente'] ?? 'MailJZTech',
                $dados['cc'] ?? null,
                $dados['bcc'] ?? null,
                $dados['anexos'] ?? []
            );

            if ($resultado['sucesso']) {
                // Atualiza status para enviado
                $this->modelEmails->atualizarStatus($idEmail, 'enviado');
                $this->modelLogs->criar($idEmail, $idsistema, $idusuario, 'envio', 'E-mail enviado com sucesso');

                return [
                    'sucesso' => true,
                    'mensagem' => 'E-mail enviado com sucesso',
                    'idemail' => $idEmail
                ];
            } else {
                // Atualiza status para erro
                $this->modelEmails->atualizarStatus($idEmail, 'erro', $resultado['mensagem']);
                $this->modelLogs->criar($idEmail, $idsistema, $idusuario, 'erro', 'Falha ao enviar: ' . $resultado['mensagem']);

                return [
                    'sucesso' => false,
                    'mensagem' => 'Erro ao enviar e-mail: ' . $resultado['mensagem'],
                    'idemail' => $idEmail
                ];
            }
        } catch (\Exception $e) {
            $this->modelEmails->atualizarStatus($idEmail, 'erro', $e->getMessage());
            $this->modelLogs->criar($idEmail, $idsistema, $idusuario, 'erro', 'Exceção: ' . $e->getMessage());

            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao enviar e-mail: ' . $e->getMessage(),
                'idemail' => $idEmail
            ];
        }
    }

    /**
     * Obtém um e-mail específico
     *
     * @param int $idemail ID do e-mail
     * @param int $idusuario ID do usuário (para validação)
     * @return array|false Retorna os dados do e-mail
     */
    public function obter($idemail, $idusuario)
    {
        $email = $this->modelEmails->getById($idemail);

        if (!$email) {
            return false;
        }

        // Verifica se o e-mail pertence ao usuário (via sistema)
        // Aqui você pode adicionar validação se necessário

        return $email;
    }

    /**
     * Lista e-mails de um sistema
     *
     * @param int $idsistema ID do sistema
     * @param int $limite Limite de registros
     * @param int $offset Offset para paginação
     * @return array Retorna um array com os e-mails
     */
    public function listar($idsistema, $limite = 50, $offset = 0)
    {
        return $this->modelEmails->getBySystem($idsistema, $limite, $offset);
    }

    /**
     * Obtém estatísticas de e-mails de um sistema
     *
     * @param int $idsistema ID do sistema
     * @return array Retorna as estatísticas
     */
    public function obterEstatisticas($idsistema)
    {
        return $this->modelEmails->obterEstatisticas($idsistema);
    }

    /**
     * Testa a configuração de e-mail
     *
     * @param string $email E-mail de teste
     * @return array Retorna resultado do teste
     */
    public function testar($email)
    {
        try {
            $resultado = $this->emailService->enviar(
                $email,
                'Teste de Configuração - MailJZTech',
                '<h1>Teste de Configuração</h1><p>Se você recebeu este e-mail, a configuração está funcionando corretamente.</p>',
                'MailJZTech Teste'
            );

            return $resultado;
        } catch (\Exception $e) {
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao testar: ' . $e->getMessage()
            ];
        }
    }
}
