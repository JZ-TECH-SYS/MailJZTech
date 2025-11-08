<?php

namespace src\handlers\service;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use src\Config;
use src\models\Emails;
use src\models\EmailLogs;

class EmailService
{
    /**
     * Envia um e-mail usando PHPMailer
     *
     * @param int         $idsistema    ID do sistema que está enviando
     * @param string      $destinatario E-mail de destino
     * @param string      $assunto      Assunto do e-mail
     * @param string      $htmlBody     Corpo em HTML
     * @param string|null $altBody      Corpo em texto puro (fallback)
     * @param string|null $cc           E-mail(s) em cópia (opcional)
     * @param string|null $bcc          E-mail(s) em cópia oculta (opcional)
     * @param array|null  $anexos       Array de anexos (opcional)
     * @param string      $nomeRemetente Nome do remetente
     *
     * @return array Resultado com sucesso/erro e mensagem
     */
    public static function sendEmail(
        $idsistema,
        $destinatario,
        $assunto,
        $htmlBody,
        $altBody = null,
        $cc = null,
        $bcc = null,
        $anexos = null,
        $nomeRemetente = 'MailJZTech'
    ): array {
        try {
            // Criar registro do e-mail no banco ANTES de enviar
            $emailData = [
                'idsistema' => $idsistema,
                'destinatario' => $destinatario,
                'cc' => $cc ? json_encode(is_array($cc) ? $cc : [$cc]) : null,
                'bcc' => $bcc ? json_encode(is_array($bcc) ? $bcc : [$bcc]) : null,
                'assunto' => $assunto,
                'corpo_html' => $htmlBody,
                'corpo_texto' => $altBody,
                'anexos' => $anexos ? json_encode($anexos) : null,
                'status' => 'pendente'
            ];

            $resultInsert = Emails::criar($emailData);
            $idemail = $resultInsert;

            $mail = new PHPMailer(true);

            // Configuração do servidor SMTP
            $mail->isSMTP();
            $mail->CharSet   = 'UTF-8';
            $mail->Host      = Config::SMTP_HOST;
            $mail->SMTPAuth  = true;
            $mail->Username  = Config::EMAIL_API;
            $mail->Password  = Config::SENHA_EMAIL_API;
            $mail->SMTPSecure= 'ssl';
            $mail->Port      = Config::SMTP_PORT;

            // Configuração do remetente - sempre contato@jztech.com.br
            $mail->setFrom(Config::EMAIL_API, $nomeRemetente);

            // Destinatário
            $mail->addAddress($destinatario);

            // Cópia (opcional)
            if ($cc) {
                $ccArray = is_array($cc) ? $cc : [$cc];
                foreach ($ccArray as $ccEmail) {
                    $mail->addCC($ccEmail);
                }
            }

            // Cópia oculta (opcional)
            if ($bcc) {
                $bccArray = is_array($bcc) ? $bcc : [$bcc];
                foreach ($bccArray as $bccEmail) {
                    $mail->addBCC($bccEmail);
                }
            }

            // Anexos (opcional)
            if ($anexos && is_array($anexos)) {
                foreach ($anexos as $anexo) {
                    if (isset($anexo['caminho']) && file_exists($anexo['caminho'])) {
                        $mail->addAttachment(
                            $anexo['caminho'],
                            $anexo['nome'] ?? basename($anexo['caminho'])
                        );
                    }
                }
            }

            // Conteúdo do e-mail
            $mail->isHTML(true);
            $mail->Subject = $assunto;
            $mail->Body    = $htmlBody;
            
            if ($altBody) {
                $mail->AltBody = $altBody;
            }

            // Envia o e-mail
            $mail->send();

            // Atualizar status para enviado
            Emails::atualizarStatus($idemail, 'enviado');

            // Registrar log
            EmailLogs::criar($idemail, $idsistema, 0, 'enviado', 'E-mail enviado com sucesso via SMTP');

            return [
                'success' => true,
                'message' => 'E-mail enviado com sucesso',
                'idemail' => $idemail,
                'status' => 'enviado',
                'service' => 'phpmailer'
            ];

        } catch (Exception $e) {
            // Se o e-mail foi criado, atualizar status para erro
            if (isset($idemail)) {
                $errorMsg = $e->getMessage();
                Emails::atualizarStatus($idemail, 'erro', $errorMsg);
                EmailLogs::criar($idemail, $idsistema, 0, 'erro', $errorMsg);
            }

            return [
                'success' => false,
                'error' => true,
                'message' => 'Erro ao enviar e-mail: ' . $e->getMessage(),
                'details' => $mail->ErrorInfo ?? '',
                'idemail' => $idemail ?? null,
                'service' => 'phpmailer'
            ];
        } catch (\Throwable $e) {
            // Se o e-mail foi criado, atualizar status para erro
            if (isset($idemail)) {
                $errorMsg = $e->getMessage();
                Emails::atualizarStatus($idemail, 'erro', $errorMsg);
                EmailLogs::criar($idemail, $idsistema, 0, 'erro', $errorMsg);
            }

            return [
                'success' => false,
                'error' => true,
                'message' => 'Erro inesperado ao enviar e-mail',
                'details' => $e->getMessage(),
                'idemail' => $idemail ?? null,
                'service' => 'phpmailer'
            ];
        }
    }



    /**
     * Testa a configuração de e-mail
     *
     * @param string $testEmail E-mail para enviar o teste
     * @return array Resultado do teste
     */
    public static function testEmailConfiguration($testEmail)
    {
        return self::sendEmail(
            0, // idsistema = 0 para testes
            $testEmail,
            'Teste de Configuração - MailJZTech',
            '<h1>Teste de E-mail</h1><p>Se você recebeu este e-mail, a configuração está funcionando corretamente!</p>',
            'Teste de E-mail - Se você recebeu este e-mail, a configuração está funcionando corretamente!',
            null,
            null,
            null,
            'MailJZTech Test'
        );
    }

    /**
     * Valida configurações de e-mail
     *
     * @return array Status das configurações
     */
    public static function validateEmailConfiguration()
    {
        $errors = [];

        if (empty(Config::SMTP_HOST)) {
            $errors[] = 'SMTP_HOST não configurado';
        }

        if (empty(Config::EMAIL_API)) {
            $errors[] = 'EMAIL_API não configurado';
        }

        if (empty(Config::SENHA_EMAIL_API)) {
            $errors[] = 'SENHA_EMAIL_API não configurado';
        }

        if (empty(Config::SMTP_PORT)) {
            $errors[] = 'SMTP_PORT não configurado';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'service' => 'phpmailer'
        ];
    }
}