<?php

namespace src\handlers;

use src\models\Emails_enviados;
use src\models\Emails_logs;
use src\handlers\service\EmailService;
use src\handlers\Sistemas as SistemasHandler;

/**
 * Handler para lógica de negócio de Emails
 * Gerencia envio, histórico e operações relacionadas a e-mails
 *
 * @author MailJZTech
 * @date 2025-01-01
 */
class Emails
{
    /**
     * Envia um e-mail
     *
     * @param int $idsistema ID do sistema
     * @param int $idusuario ID do usuário
     * @param array $dados Dados do e-mail
     * @return array Retorna resultado da operação
     */
    public static function enviar($idsistema, $idusuario, $dados)
    {
        // Pressupõe validação anterior no Controller/core (mantendo simples aqui)

        // Garantir idusuario válido (FK): se vazio, usa dono do sistema
        $idusuarioLog = $idusuario;
        if (empty($idusuarioLog) || !is_numeric($idusuarioLog)) {
            $sistema = SistemasHandler::obterPorId($idsistema);
            if (!empty($sistema['idusuario'])) {
                $idusuarioLog = (int)$sistema['idusuario'];
            } else {
                return ['sucesso' => false, 'mensagem' => 'Usuário não identificado para este sistema'];
            }
        }

        // Log básico de tentativa
        Emails_logs::criar(null, $idsistema, $idusuarioLog, 'envio', 'Tentando enviar e-mail', [
            'destinatario' => $dados['destinatario'] ?? null,
            'assunto' => $dados['assunto'] ?? null
        ]);

        // Tenta enviar o e-mail
        try {
            $resultado = EmailService::sendEmail(
                $idsistema,
                $dados['destinatario'],
                $dados['assunto'],
                $dados['corpo_html'] ?? $dados['corpo_texto'],
                $dados['corpo_texto'] ?? null,
                $dados['cc'] ?? null,
                $dados['bcc'] ?? null,
                $dados['anexos'] ?? null,
                $dados['nome_remetente'] ?? $sistema['nome'] ?? 'MailJZTech',
                $idusuario  // ✅ Passar idusuario
            );

            if ($resultado['success']) {
                // Cria registro apenas no sucesso
                $idEmail = Emails_enviados::criar([
                    'idsistema' => $idsistema,
                    'idusuario' => $idusuarioLog,
                    'destinatario' => $dados['destinatario'],
                    'cc' => isset($dados['cc']) ? (is_array($dados['cc']) ? json_encode($dados['cc']) : $dados['cc']) : null,
                    'bcc' => isset($dados['bcc']) ? (is_array($dados['bcc']) ? json_encode($dados['bcc']) : $dados['bcc']) : null,
                    'assunto' => $dados['assunto'],
                    'corpo_html' => $dados['corpo_html'] ?? null,
                    'corpo_texto' => $dados['corpo_texto'] ?? null,
                    'anexos' => isset($dados['anexos']) ? json_encode($dados['anexos']) : null,
                    'status' => 'enviado',
                    'data_envio' => date('Y-m-d H:i:s')
                ]);

                Emails_logs::criar($idEmail, $idsistema, $idusuarioLog, 'envio', 'E-mail enviado com sucesso', [
                    'timestamp' => date('Y-m-d H:i:s')
                ]);

                return [
                    'sucesso' => true,
                    'mensagem' => 'E-mail enviado com sucesso',
                    'idemail' => $idEmail
                ];
            } else {
                // Falha: loga e retorna
                Emails_logs::criar(null, $idsistema, $idusuarioLog, 'erro', 'Falha no envio de e-mail', [
                    'erro' => $resultado['message'] ?? 'sem mensagem'
                ]);

                return [
                    'sucesso' => false,
                    'mensagem' => 'Erro ao enviar e-mail: ' . $resultado['message'],
                    'idemail' => null
                ];
            }
    } catch (\Exception $e) {
            Emails_logs::criar(null, $idsistema, $idusuarioLog, 'erro', 'Exceção durante envio de e-mail', [
                'excecao' => $e->getMessage(),
                'arquivo' => $e->getFile(),
                'linha' => $e->getLine()
            ]);

            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao enviar e-mail: ' . $e->getMessage(),
                'idemail' => null
            ];
        }
    }

    /**
     * Obtém um e-mail específico
     *
     * @param int $idemail ID do e-mail
     * @param int $idsistema ID do sistema (para validação)
     * @return array|false Retorna os dados do e-mail
     */
    public static function obter($idemail, $idsistema)
    {
        $email = Emails_enviados::getById($idemail);

        if (!$email) {
            return false;
        }

        // Verifica se o e-mail pertence ao sistema
        if ($email['idsistema'] != $idsistema) {
            return false;
        }

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
    public static function listar($idsistema, $limite = 50, $offset = 0)
    {
        return Emails_enviados::getBySystem($idsistema, $limite, $offset);
    }

    /**
     * Obtém estatísticas de e-mails de um sistema
     *
     * @param int $idsistema ID do sistema
     * @return array Retorna as estatísticas
     */
    public static function obterEstatisticas($idsistema)
    {
        return Emails_enviados::obterEstatisticas($idsistema);
    }

    /**
     * Testa a configuração de e-mail
     *
     * @param string $email E-mail de teste
     * @param int $idusuario ID do usuário (para logs)
     * @return array Retorna resultado do teste
     */
    public static function testar($email, $idusuario = 0)
    {
        try {
            $resultado = EmailService::sendEmail(
                0, // idsistema para teste (não logamos em tabela para evitar FK)
                $email,
                'Teste de Configuração - MailJZTech',
                '<h1>Teste de Configuração</h1><p>Se você recebeu este e-mail, a configuração está funcionando corretamente.</p>',
                'Se você recebeu este e-mail, a configuração está funcionando corretamente.',
                null,
                null,
                null,
                'MailJZTech Teste',
                $idusuario
            );

            return [
                'sucesso' => $resultado['success'],
                'mensagem' => $resultado['message']
            ];
        } catch (\Exception $e) {
            return [
                'sucesso' => false,
                'mensagem' => 'Erro ao testar: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Conta total de e-mails de um sistema
     *
     * @param int $idsistema ID do sistema
     * @return int Retorna o total
     */
    public static function contar($idsistema)
    {
        return Emails_enviados::countBySystem($idsistema);
    }

    /**
     * Valida configuração de e-mail
     *
     * @return array Retorna se a configuração é válida
     */
    public static function validarConfiguracao()
    {
        return EmailService::validateEmailConfiguration();
    }

    /**
     * Obtém dados do dashboard (estatísticas + últimos e-mails)
     *
     * @param int $idsistema ID do sistema
     * @param int $limite Limite de e-mails recentes
     * @return array Retorna dados completos do dashboard
     */
    public static function obterDadosDashboard($idsistema, $limite = 10)
    {
        // Obtém estatísticas usando SQL complexo
        $statsRaw = self::obterEstatisticas($idsistema);
        
        // Garante que todos os campos existam
        $stats = [
            'total' => (int)($statsRaw['total'] ?? 0),
            'enviados' => (int)($statsRaw['enviados'] ?? 0),
            'erros' => (int)($statsRaw['erros'] ?? 0),
            'pendentes' => (int)($statsRaw['pendentes'] ?? 0)
        ];
        
        // Obtém últimos e-mails usando Query Builder
        if (!empty($idsistema)) {
            $ultimosEmails = Emails_enviados::getBySystem($idsistema, $limite, 0);
        } else {
            $ultimosEmails = Emails_enviados::getRecentes($limite);
        }

        return [
            'estatisticas' => $stats,
            'ultimos_emails' => $ultimosEmails ?? []
        ];
    }
}
