<?php

namespace src\handlers\service;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use src\Config;

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
        $nomeRemetente = 'MailJZTech',
        $idusuario = 0
    ): array {
        try {
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

            // Anexos (opcional) - Suporta caminho, base64 ou URL
            if ($anexos && is_array($anexos)) {
                foreach ($anexos as $anexo) {
                    // Anexo via CAMINHO de arquivo local
                    if (isset($anexo['caminho']) && !empty($anexo['caminho'])) {
                        if (file_exists($anexo['caminho'])) {
                            $mail->addAttachment(
                                $anexo['caminho'],
                                $anexo['nome'] ?? basename($anexo['caminho'])
                            );
                        } else {
                            throw new Exception("Arquivo não encontrado: {$anexo['caminho']}");
                        }
                    }
                    // Anexo via BASE64
                    elseif (isset($anexo['base64']) && !empty($anexo['base64']) && isset($anexo['nome'])) {
                        // Remove prefixo data:image/png;base64, se houver
                        $base64Data = $anexo['base64'];
                        if (strpos($base64Data, 'base64,') !== false) {
                            $base64Data = explode('base64,', $base64Data)[1];
                        }
                        
                        // Decodifica base64
                        $fileData = base64_decode($base64Data);
                        if ($fileData === false) {
                            throw new Exception("Base64 inválido para anexo: {$anexo['nome']}");
                        }
                        
                        // Adiciona anexo via string
                        $mail->addStringAttachment(
                            $fileData,
                            $anexo['nome'],
                            'base64',
                            $anexo['type'] ?? 'application/octet-stream'
                        );
                    }
                    // Anexo via URL/LINK (download automático)
                    elseif (isset($anexo['url']) && !empty($anexo['url'])) {
                        $url = $anexo['url'];
                        
                        // Valida se é URL válida
                        if (!filter_var($url, FILTER_VALIDATE_URL)) {
                            throw new Exception("URL inválida: {$url}");
                        }
                        
                        // Tenta fazer download do arquivo
                        $fileData = @file_get_contents($url);
                        
                        if ($fileData === false) {
                            throw new Exception("Não foi possível baixar o arquivo da URL: {$url}");
                        }
                        
                        // Nome do arquivo (usa o fornecido ou extrai da URL)
                        $fileName = $anexo['nome'] ?? basename(parse_url($url, PHP_URL_PATH));
                        if (empty($fileName) || $fileName === '/') {
                            $fileName = 'anexo_' . time() . '.pdf';
                        }
                        
                        // Adiciona anexo via string
                        $mail->addStringAttachment(
                            $fileData,
                            $fileName,
                            'base64',
                            $anexo['type'] ?? 'application/octet-stream'
                        );
                    }
                    // Se não tem caminho, base64 nem URL
                    else {
                        throw new Exception("Anexo inválido: deve conter 'caminho', 'base64' + 'nome', ou 'url'");
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

            return [
                'success' => true,
                'message' => 'E-mail enviado com sucesso',
                'service' => 'phpmailer'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => true,
                'message' => 'Erro ao enviar e-mail: ' . $e->getMessage(),
                'details' => isset($mail) ? ($mail->ErrorInfo ?? '') : '',
                'service' => 'phpmailer'
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => true,
                'message' => 'Erro inesperado ao enviar e-mail',
                'details' => $e->getMessage(),
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
            'MailJZTech Test',
            0 // idusuario = 0 para testes
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
