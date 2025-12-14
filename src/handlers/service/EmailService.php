<?php

namespace src\handlers\service;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use src\Config;
use core\Controller as ctrl;

/**
 * Service para envio de e-mails com validação robusta
 * 
 * Status de envio:
 * - pendente: aguardando processamento
 * - processando: em processo de envio  
 * - aceito: aceito pelo servidor SMTP (código 250)
 * - enviado: confirmação de envio bem-sucedido
 * - rejeitado: rejeitado pelo servidor SMTP
 * - bounce: retornou (e-mail não entregue)
 * - falha: erro durante o processo de envio
 * 
 * @author MailJZTech
 * @date 2025-12-14
 */
class EmailService
{
    // Limites de tamanho
    const MAX_HTML_SIZE = 10485760;  // 10MB
    const MAX_SUBJECT_LENGTH = 998;   // RFC 2822
    const MAX_TOTAL_SIZE = 26214400;  // 25MB (limite comum SMTP)
    
    // Códigos SMTP de sucesso
    const SMTP_SUCCESS_CODES = [250, 251, 252];
    
    // Códigos SMTP de erro temporário (retry)
    const SMTP_TEMP_ERROR_CODES = [421, 450, 451, 452];
    
    // Códigos SMTP de erro permanente
    const SMTP_PERM_ERROR_CODES = [550, 551, 552, 553, 554];

    /**
     * Envia um e-mail usando PHPMailer com validação robusta
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
     * @param int         $idusuario    ID do usuário
     *
     * @return array Resultado detalhado com status, códigos SMTP e mensagens
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
        $startTime = microtime(true);
        $debugLog = [];
        
        try {
            // ========================================
            // 1. VALIDAÇÕES PRÉ-ENVIO
            // ========================================
            
            // Validar e-mail do destinatário
            $validacaoEmail = self::validateEmail($destinatario);
            if (!$validacaoEmail['valid']) {
                return self::buildErrorResponse(
                    'rejeitado',
                    'E-mail do destinatário inválido: ' . $validacaoEmail['error'],
                    '550',
                    $debugLog
                );
            }
            
            // Validar HTML
            $validacaoHtml = self::validateHtml($htmlBody);
            if (!$validacaoHtml['valid']) {
                return self::buildErrorResponse(
                    'falha',
                    'HTML inválido: ' . $validacaoHtml['error'],
                    null,
                    $debugLog,
                    $validacaoHtml
                );
            }
            $debugLog[] = "HTML validado: {$validacaoHtml['size_bytes']} bytes";
            
            // Validar tamanho do assunto
            if (strlen($assunto) > self::MAX_SUBJECT_LENGTH) {
                return self::buildErrorResponse(
                    'falha',
                    'Assunto excede o limite de ' . self::MAX_SUBJECT_LENGTH . ' caracteres',
                    null,
                    $debugLog
                );
            }
            
            // Sanitizar HTML para melhor compatibilidade
            $htmlBody = self::sanitizeHtml($htmlBody);
            
            // ========================================
            // 2. CONFIGURAÇÃO DO PHPMAILER
            // ========================================
            
            $mail = new PHPMailer(true);
            
            // Habilitar debug para capturar respostas SMTP
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;
            $smtpLog = '';
            $mail->Debugoutput = function($str, $level) use (&$smtpLog) {
                $smtpLog .= $str . "\n";
            };

            // Configuração do servidor SMTP
            $mail->isSMTP();
            $mail->CharSet   = 'UTF-8';
            $mail->Encoding  = 'base64'; // Melhor para HTML complexo
            $mail->Host      = Config::SMTP_HOST;
            $mail->SMTPAuth  = true;
            $mail->Username  = Config::EMAIL_API;
            $mail->Password  = Config::SENHA_EMAIL_API;
            $mail->SMTPSecure= PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port      = Config::SMTP_PORT;
            $mail->Timeout   = 30; // Timeout de conexão
            $mail->SMTPKeepAlive = false;

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
            
            // Gerar altBody automaticamente se não fornecido
            if ($altBody) {
                $mail->AltBody = $altBody;
            } else {
                $mail->AltBody = self::htmlToPlainText($htmlBody);
            }
            
            // Calcular tamanho total aproximado
            $totalSize = strlen($htmlBody) + strlen($mail->AltBody ?? '');
            if ($anexos) {
                foreach ($anexos as $anexo) {
                    if (isset($anexo['base64'])) {
                        $totalSize += strlen($anexo['base64']) * 0.75; // Base64 é ~33% maior
                    }
                }
            }
            
            if ($totalSize > self::MAX_TOTAL_SIZE) {
                return self::buildErrorResponse(
                    'falha',
                    'Tamanho total do e-mail excede o limite de 25MB',
                    '552',
                    $debugLog,
                    ['size' => $totalSize, 'limit' => self::MAX_TOTAL_SIZE]
                );
            }
            
            $debugLog[] = "Tamanho estimado: " . number_format($totalSize) . " bytes";

            // ========================================
            // 3. ENVIO COM CAPTURA DE RESPOSTA SMTP
            // ========================================
            
            $sendResult = $mail->send();
            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);
            
            // Extrair código SMTP da resposta
            $smtpCode = self::extractSmtpCode($smtpLog);
            $debugLog[] = "Código SMTP: {$smtpCode}";
            $debugLog[] = "Tempo de envio: {$duration}ms";
            
            // Verificar se foi realmente aceito
            if ($sendResult && in_array((int)$smtpCode, self::SMTP_SUCCESS_CODES)) {
                return [
                    'success' => true,
                    'status' => 'aceito', // Aceito pelo servidor SMTP
                    'message' => 'E-mail aceito pelo servidor SMTP',
                    'smtp_code' => $smtpCode,
                    'smtp_response' => self::extractLastSmtpResponse($smtpLog),
                    'size_bytes' => (int)$totalSize,
                    'duration_ms' => $duration,
                    'service' => 'phpmailer',
                    'debug_log' => $debugLog
                ];
            }
            
            // Enviou mas código não é de sucesso claro
            if ($sendResult) {
                return [
                    'success' => true,
                    'status' => 'enviado', // Marcamos como enviado, mas sem confirmação forte
                    'message' => 'E-mail enviado (aguardando confirmação)',
                    'smtp_code' => $smtpCode,
                    'smtp_response' => self::extractLastSmtpResponse($smtpLog),
                    'size_bytes' => (int)$totalSize,
                    'duration_ms' => $duration,
                    'service' => 'phpmailer',
                    'debug_log' => $debugLog,
                    'warning' => 'Código SMTP não confirmado como sucesso'
                ];
            }
            
            // Falha no envio
            return self::buildErrorResponse(
                self::getStatusFromSmtpCode($smtpCode),
                'Falha no envio: ' . $mail->ErrorInfo,
                $smtpCode,
                $debugLog,
                ['smtp_log' => $smtpLog]
            );

        } catch (Exception $e) {
            $smtpCode = self::extractSmtpCode($smtpLog ?? '');
            return self::buildErrorResponse(
                self::getStatusFromSmtpCode($smtpCode),
                'Erro PHPMailer: ' . $e->getMessage(),
                $smtpCode,
                $debugLog ?? [],
                ['exception' => $e->getMessage(), 'error_info' => isset($mail) ? ($mail->ErrorInfo ?? '') : '']
            );
        } catch (\Throwable $e) {
            return self::buildErrorResponse(
                'falha',
                'Erro inesperado: ' . $e->getMessage(),
                null,
                $debugLog ?? [],
                ['exception' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]
            );
        }
    }
    
    /**
     * Valida um endereço de e-mail
     */
    public static function validateEmail(string $email): array
    {
        if (empty($email)) {
            return ['valid' => false, 'error' => 'E-mail vazio'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'error' => 'Formato de e-mail inválido'];
        }
        
        // Verificar domínio
        $domain = substr(strrchr($email, '@'), 1);
        if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
            return ['valid' => false, 'error' => "Domínio '{$domain}' não possui registros MX válidos"];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Valida o HTML do e-mail
     */
    public static function validateHtml(string $html): array
    {
        $result = [
            'valid' => true,
            'warnings' => [],
            'size_bytes' => strlen($html)
        ];
        
        // Verificar tamanho
        if (strlen($html) > self::MAX_HTML_SIZE) {
            return [
                'valid' => false,
                'error' => 'HTML excede o limite de ' . (self::MAX_HTML_SIZE / 1024 / 1024) . 'MB',
                'size_bytes' => strlen($html)
            ];
        }
        
        // Verificar se está vazio
        if (empty(trim(strip_tags($html)))) {
            $result['warnings'][] = 'HTML contém apenas tags sem texto visível';
        }
        
        // Verificar tags não fechadas (básico)
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $loadResult = @$doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $errors = libxml_get_errors();
        libxml_clear_errors();
        
        if (!empty($errors)) {
            $criticalErrors = array_filter($errors, fn($e) => $e->level >= LIBXML_ERR_ERROR);
            if (!empty($criticalErrors)) {
                $result['warnings'][] = 'HTML contém erros de sintaxe que podem causar problemas de renderização';
            }
        }
        
        // Verificar scripts (não permitidos na maioria dos clientes de e-mail)
        if (preg_match('/<script\b/i', $html)) {
            $result['warnings'][] = 'HTML contém tags <script> que serão ignoradas pela maioria dos clientes de e-mail';
        }
        
        // Verificar estilos externos (podem não carregar)
        if (preg_match('/<link[^>]+stylesheet/i', $html)) {
            $result['warnings'][] = 'HTML contém CSS externo que pode não carregar em alguns clientes';
        }
        
        return $result;
    }
    
    /**
     * Sanitiza o HTML para melhor compatibilidade com clientes de e-mail
     */
    public static function sanitizeHtml(string $html): string
    {
        // Remover scripts
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html);
        
        // Remover event handlers inline
        $html = preg_replace('/\s+on\w+\s*=\s*["\'][^"\']*["\']/i', '', $html);
        
        // Garantir encoding UTF-8
        if (stripos($html, 'charset') === false && stripos($html, '<head') !== false) {
            $html = preg_replace('/<head([^>]*)>/i', '<head$1><meta charset="UTF-8">', $html);
        }
        
        return $html;
    }
    
    /**
     * Converte HTML para texto puro (fallback)
     */
    public static function htmlToPlainText(string $html): string
    {
        // Substituir tags de bloco por quebras de linha
        $text = preg_replace('/<(br|p|div|h[1-6]|li|tr)[^>]*>/i', "\n", $html);
        
        // Remover todas as tags
        $text = strip_tags($text);
        
        // Decodificar entidades HTML
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        
        // Normalizar espaços
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n\s+/', "\n", $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        
        return trim($text);
    }
    
    /**
     * Extrai o código SMTP da resposta
     */
    private static function extractSmtpCode(string $smtpLog): ?string
    {
        // Procurar pelo último código de resposta (ex: 250, 550, etc)
        if (preg_match_all('/(?:^|\n)\s*(\d{3})\s/m', $smtpLog, $matches)) {
            return end($matches[1]);
        }
        
        // Tentar extrair de mensagens de erro
        if (preg_match('/SMTP\s+(?:code|error)[:\s]+(\d{3})/i', $smtpLog, $match)) {
            return $match[1];
        }
        
        return null;
    }
    
    /**
     * Extrai a última resposta SMTP
     */
    private static function extractLastSmtpResponse(string $smtpLog): ?string
    {
        $lines = explode("\n", trim($smtpLog));
        $serverLines = array_filter($lines, fn($line) => preg_match('/^SERVER\s*->\s*CLIENT:/i', $line) || preg_match('/^\d{3}\s/', $line));
        
        if (!empty($serverLines)) {
            return trim(end($serverLines));
        }
        
        return null;
    }
    
    /**
     * Determina o status baseado no código SMTP
     */
    private static function getStatusFromSmtpCode(?string $code): string
    {
        if ($code === null) {
            return 'falha';
        }
        
        $codeInt = (int)$code;
        
        if (in_array($codeInt, self::SMTP_SUCCESS_CODES)) {
            return 'aceito';
        }
        
        if (in_array($codeInt, self::SMTP_TEMP_ERROR_CODES)) {
            return 'falha'; // Pode ser retentado
        }
        
        if (in_array($codeInt, self::SMTP_PERM_ERROR_CODES)) {
            return 'rejeitado';
        }
        
        // Código 4xx = erro temporário
        if ($codeInt >= 400 && $codeInt < 500) {
            return 'falha';
        }
        
        // Código 5xx = erro permanente
        if ($codeInt >= 500 && $codeInt < 600) {
            return 'rejeitado';
        }
        
        return 'falha';
    }
    
    /**
     * Constrói resposta de erro padronizada
     */
    private static function buildErrorResponse(
        string $status,
        string $message,
        ?string $smtpCode = null,
        array $debugLog = [],
        array $extra = []
    ): array {
        return array_merge([
            'success' => false,
            'status' => $status,
            'message' => $message,
            'smtp_code' => $smtpCode,
            'service' => 'phpmailer',
            'debug_log' => $debugLog
        ], $extra);
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
