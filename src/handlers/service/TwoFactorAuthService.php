<?php

namespace src\handlers\service;

use Exception;

/**
 * Serviço de Autenticação de Dois Fatores (2FA) com TOTP
 * Compatível com Google Authenticator e Microsoft Authenticator
 */
class TwoFactorAuthService
{
    private const TOTP_WINDOW = 1; // Janela de tolerância de 1 período (30 segundos)
    private const TOTP_TIME_STEP = 30; // Período de 30 segundos
    private const TOTP_DIGITS = 6; // 6 dígitos
    private const BACKUP_CODES_COUNT = 10; // 10 códigos de backup
    private const BACKUP_CODE_LENGTH = 8; // 8 caracteres por código

    /**
     * Gerar um novo secret TOTP
     * @return string Secret em base32
     */
    public static function generateSecret(): string
    {
        $randomBytes = random_bytes(32);
        return self::base32Encode($randomBytes);
    }

    /**
     * Gerar QR Code para TOTP
     * @param string $email Email do usuário
     * @param string $secret Secret TOTP
     * @param string $issuer Nome da aplicação (ex: MailJZTech)
     * @return string URL do QR Code
     */
    public static function generateQRCode(string $email, string $secret, string $issuer = 'MailJZTech'): string
    {
        $label = urlencode("{$issuer} ({$email})");
        $params = [
            'secret' => $secret,
            'issuer' => urlencode($issuer),
            'algorithm' => 'SHA1',
            'digits' => self::TOTP_DIGITS,
            'period' => self::TOTP_TIME_STEP
        ];

        $otpauthUrl = "otpauth://totp/{$label}?" . http_build_query($params);

        // Usar Google Charts API para gerar QR Code
        return "https://chart.googleapis.com/chart?chs=300x300&chld=M|0&cht=qr&chl=" . urlencode($otpauthUrl);
    }

    /**
     * Verificar código TOTP
     * @param string $secret Secret TOTP
     * @param string $code Código de 6 dígitos
     * @return bool Verdadeiro se o código é válido
     */
    public static function verifyCode(string $secret, string $code): bool
    {
        // Remover espaços
        $code = str_replace(' ', '', $code);

        // Validar formato
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $decodedSecret = self::base32Decode($secret);
        if ($decodedSecret === false) {
            return false;
        }

        $currentTime = floor(time() / self::TOTP_TIME_STEP);

        // Verificar dentro da janela de tolerância
        for ($i = -self::TOTP_WINDOW; $i <= self::TOTP_WINDOW; $i++) {
            $timeCounter = $currentTime + $i;
            $hmac = hash_hmac('sha1', pack('N*', 0, $timeCounter), $decodedSecret, true);
            $offset = ord($hmac[19]) & 0x0F;
            $totp = (((ord($hmac[$offset]) & 0x7F) << 24) |
                     ((ord($hmac[$offset + 1]) & 0xFF) << 16) |
                     ((ord($hmac[$offset + 2]) & 0xFF) << 8) |
                     (ord($hmac[$offset + 3]) & 0xFF)) % pow(10, self::TOTP_DIGITS);

            if (intval($totp) === intval($code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gerar códigos de backup
     * @return array Array com códigos de backup
     */
    public static function generateBackupCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < self::BACKUP_CODES_COUNT; $i++) {
            $code = strtoupper(bin2hex(random_bytes(self::BACKUP_CODE_LENGTH / 2)));
            $codes[] = substr($code, 0, self::BACKUP_CODE_LENGTH);
        }
        return $codes;
    }

    /**
     * Verificar e usar código de backup
     * @param array $backupCodes Array de códigos de backup
     * @param string $code Código a verificar
     * @return array Array com códigos atualizados (código usado removido)
     */
    public static function verifyAndUseBackupCode(array $backupCodes, string $code): array
    {
        $code = strtoupper(str_replace(' ', '', $code));

        $key = array_search($code, $backupCodes);
        if ($key !== false) {
            unset($backupCodes[$key]);
            return array_values($backupCodes); // Re-indexar array
        }

        return $backupCodes; // Retornar original se não encontrado
    }

    /**
     * Codificar em base32
     * @param string $data Dados a codificar
     * @return string Dados codificados em base32
     */
    private static function base32Encode(string $data): string
    {
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output = '';
        $v = 0;
        $vbits = 0;

        for ($i = 0; $i < strlen($data); $i++) {
            $v = ($v << 8) | ord($data[$i]);
            $vbits += 8;

            while ($vbits >= 5) {
                $vbits -= 5;
                $output .= $base32chars[($v >> $vbits) & 31];
            }
        }

        if ($vbits > 0) {
            $output .= $base32chars[($v << (5 - $vbits)) & 31];
        }

        // Adicionar padding
        while (strlen($output) % 8 !== 0) {
            $output .= '=';
        }

        return $output;
    }

    /**
     * Decodificar de base32
     * @param string $data Dados codificados em base32
     * @return string|false Dados decodificados ou false se inválido
     */
    private static function base32Decode(string $data): string|false
    {
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $data = strtoupper($data);
        $output = '';
        $v = 0;
        $vbits = 0;

        for ($i = 0; $i < strlen($data); $i++) {
            $c = $data[$i];

            if ($c === '=') {
                break;
            }

            $digit = strpos($base32chars, $c);
            if ($digit === false) {
                return false;
            }

            $v = ($v << 5) | $digit;
            $vbits += 5;

            if ($vbits >= 8) {
                $vbits -= 8;
                $output .= chr(($v >> $vbits) & 255);
            }
        }

        return $output;
    }

    /**
     * Formatar código de backup para exibição
     * @param string $code Código de backup
     * @return string Código formatado (ex: XXXX-XXXX)
     */
    public static function formatBackupCode(string $code): string
    {
        return substr($code, 0, 4) . '-' . substr($code, 4);
    }

    /**
     * Formatar secret TOTP para exibição
     * @param string $secret Secret TOTP
     * @return string Secret formatado em grupos de 4 caracteres
     */
    public static function formatSecret(string $secret): string
    {
        $secret = str_replace('=', '', $secret);
        return implode(' ', str_split($secret, 4));
    }
}
