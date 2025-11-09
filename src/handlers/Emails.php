<?php

namespace src\handlers;

use src\models\Emails_enviados;
use src\models\Emails_logs;
use src\handlers\service\EmailService;
use src\handlers\Sistemas as SistemasHandler;
use core\Controller as ctrl;

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
     * Envia relatório de backups via cron
     * @param array $dados Dados do relatório
     */
    public static function enviarRelatorioBackupsCron($dados)
    {
        // Formatações e preparação de dados (pt-BR)
        $ano = date('Y');

        $timestamp = null;
        if (!empty($dados['data_execucao'])) {
            $timestamp = is_numeric($dados['data_execucao'])
                ? (int)$dados['data_execucao']
                : strtotime($dados['data_execucao']);
        }
        $dataExecucaoBr = $timestamp ? date('d/m/Y H:i:s', $timestamp) : date('d/m/Y H:i:s');

        $total   = (int)($dados['total'] ?? 0);
        $sucesso = (int)($dados['sucesso'] ?? 0);
        $erros   = (int)($dados['erros'] ?? max(0, $total - $sucesso));

        $taxaSucesso = $total > 0 ? round(($sucesso / $total) * 100) : 0;
        $taxaErro    = 100 - $taxaSucesso;

        $totalFmt   = number_format($total, 0, ',', '.');
        $sucessoFmt = number_format($sucesso, 0, ',', '.');
        $errosFmt   = number_format($erros, 0, ',', '.');

        $mensagemEsc = htmlspecialchars((string)($dados['mensagem'] ?? 'Sem mensagem informada.'), ENT_QUOTES, 'UTF-8');

        $html = <<<HTML
        <html>
            <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
            <style>
                /* Estilos básicos (compatíveis com a maioria dos clientes de e-mail) */
                body { margin:0; padding:0; background:#f3f4f6; font-family: Arial, Helvetica, sans-serif; color:#111827; }
                .container { max-width: 640px; margin: 0 auto; background:#ffffff; }
                .header {
                background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
                color:#ffffff; text-align:center; padding:28px 20px;
                }
                .header h1 { margin:0; font-size:22px; line-height:1.3; }
                .header p { margin:6px 0 0 0; font-size:13px; opacity:.95; }

                .content { padding:24px 22px; }
                .title { margin:0 0 12px 0; font-size:18px; color:#111827; }
                .message {
                background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px;
                padding:14px 16px; font-size:14px; color:#374151;
                }

                .stats-wrap { margin:18px 0; }
                .stats-table { width:100%; border-collapse: collapse; }
                .stats-table td {
                width:33.33%; padding:14px; text-align:center; vertical-align:middle;
                border:1px solid #f3f4f6; background:#fafafa;
                }
                .stats-title { display:block; font-size:12px; color:#6b7280; text-transform:uppercase; letter-spacing:.5px; }
                .stats-value { display:block; margin-top:6px; font-size:24px; font-weight:bold; color:#111827; }
                .ok { border-left:4px solid #10b981; }
                .err { border-left:4px solid #ef4444; }
                .tot { border-left:4px solid #4f46e5; }

                .bar-wrap { margin:18px 0 4px 0; background:#eef2ff; border-radius:8px; overflow:hidden; height:12px; }
                .bar-ok { height:12px; background:#10b981; width: {$taxaSucesso}%; display:inline-block; }
                .bar-err { height:12px; background:#ef4444; width: {$taxaErro}%; display:inline-block; }

                .bar-legend { font-size:12px; color:#6b7280; display:flex; justify-content:space-between; }
                .badge { display:inline-block; padding:2px 8px; font-size:12px; border-radius:999px; color:#fff; }
                .badge-ok { background:#10b981; }
                .badge-err { background:#ef4444; }

                .footer {
                background:#f9fafb; border-top:1px solid #e5e7eb;
                text-align:center; padding:16px; font-size:12px; color:#6b7280;
                }
                @media (max-width: 480px) {
                .stats-table td { display:block; width:100%; }
                }
            </style>
            </head>
            <body>
            <div class="container" role="article" aria-roledescription="email">
                <div class="header">
                <h1>📋 Relatório de Backups</h1>
                <p>Executado em {$dataExecucaoBr}</p>
                </div>

                <div class="content">
                <h2 class="title">Resumo</h2>
                <div class="message">{$mensagemEsc}</div>

                <div class="stats-wrap">
                    <table class="stats-table" role="presentation">
                    <tr>
                        <td class="tot">
                        <span class="stats-title">Total</span>
                        <span class="stats-value">{$totalFmt}</span>
                        </td>
                        <td class="ok">
                        <span class="stats-title">Sucesso</span>
                        <span class="stats-value">{$sucessoFmt}</span>
                        </td>
                        <td class="err">
                        <span class="stats-title">Erros</span>
                        <span class="stats-value">{$errosFmt}</span>
                        </td>
                    </tr>
                    </table>

                    <div class="bar-wrap" aria-label="Taxa de sucesso vs erros">
                    <span class="bar-ok"></span><span class="bar-err"></span>
                    </div>
                    <div class="bar-legend">
                    <span><span class="badge badge-ok">{$taxaSucesso}% Sucesso</span></span>
                    <span><span class="badge badge-err">{$taxaErro}% Erros</span></span>
                    </div>
                </div>
                </div>

                <div class="footer">
                MailJZTech © {$ano} • Relatório automático
                </div>
            </div>
            </body>
        </html>
        HTML;

        try {
            EmailService::sendEmail(
                0, // idsistema 0 para cron
                'jv.zyzz.legado@gmail.com', // Destinatário fixo para relatórios
                'Relatório de Backups - ' . date('Y-m-d H:i:s'),
                $html,
                null,
                'Zehenrique0822@gmail.com',
            );
        } catch (\Exception $e) {
            ctrl::log("Erro ao enviar relatório de backups via cron: " . $e->getMessage());
            return;
        }
    }


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
