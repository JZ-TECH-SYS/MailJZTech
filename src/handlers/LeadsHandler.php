<?php

/**
 * Desc: Handler para registro de acessos e geração de leads
 * Autor: Sistema ClickExpress
 * Data de Criação: 16/08/2025
 * Migrado de: src\middleware\LeadsMiddleware
 */

namespace src\handlers;

use src\models\Site_acesso_log as AcessoLogModel;
use Exception;

class LeadsHandler
{
    /**
     * Páginas que devem ser logadas
     */
    const PAGES_TO_LOG = [
        '/home',
        '/listagens',
        '/produto',
        '/checkout',
        '/cardapio',
        '/pedidos'
    ];

    /**
     * Registra acesso se a página está na lista de monitoramento
     */
    public static function logAccess($idempresa, $idPessoa = null)
    {
        try {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            $page = parse_url($requestUri, PHP_URL_PATH);
            
            // Verifica se deve logar esta página
            if (!self::shouldLogPage($page)) {
                return false;
            }

            $dadosLog = [
                'idempresa' => $idempresa,
                'id_pessoa' => $idPessoa,
                'ip' => self::getClientIp(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'device' => self::detectDevice(),
                'os' => self::detectOS(),
                'browser' => self::detectBrowser(),
                'referer' => $_SERVER['HTTP_REFERER'] ?? null,
                'utm_source' => $_GET['utm_source'] ?? null,
                'utm_medium' => $_GET['utm_medium'] ?? null,
                'utm_campaign' => $_GET['utm_campaign'] ?? null,
                'utm_term' => $_GET['utm_term'] ?? null,
                'page' => $page,
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
                'status_code' => http_response_code() ?: 200,
                'session_id' => session_id(),
                'fingerprint' => self::generateFingerprint()
            ];

            AcessoLogModel::insert($dadosLog)->execute();
            return true;
        } catch (Exception $e) {
            // Log do erro mas não interrompe o fluxo
            error_log("Erro ao registrar lead: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifica se deve logar a página
     */
    private static function shouldLogPage($page)
    {
        foreach (self::PAGES_TO_LOG as $pageToLog) {
            if (strpos($page, $pageToLog) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Obtém IP real do cliente
     */
    private static function getClientIp()
    {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                   'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Detecta dispositivo
     */
    private static function detectDevice()
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        if (preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|Windows Phone/i', $userAgent)) {
            if (preg_match('/iPad/i', $userAgent)) {
                return 'tablet';
            }
            return 'mobile';
        }
        
        return 'desktop';
    }

    /**
     * Detecta sistema operacional
     */
    private static function detectOS()
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $osArray = [
            'Windows' => 'Windows',
            'Mac OS X' => 'macOS',
            'Linux' => 'Linux',
            'Android' => 'Android',
            'iPhone OS|iOS' => 'iOS'
        ];
        
        foreach ($osArray as $regex => $os) {
            if (preg_match("/$regex/i", $userAgent)) {
                return $os;
            }
        }
        
        return 'unknown';
    }

    /**
     * Detecta navegador
     */
    private static function detectBrowser()
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $browsers = [
            'Chrome' => 'Chrome',
            'Firefox' => 'Firefox',
            'Safari' => 'Safari',
            'Edge' => 'Edge',
            'Opera' => 'Opera'
        ];
        
        foreach ($browsers as $regex => $browser) {
            if (preg_match("/$regex/i", $userAgent)) {
                return $browser;
            }
        }
        
        return 'unknown';
    }

    /**
     * Gera fingerprint básico do navegador
     */
    private static function generateFingerprint()
    {
        $data = [
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
            $_SERVER['HTTP_ACCEPT'] ?? ''
        ];
        
        return md5(implode('|', $data));
    }

    /**
     * Método para ser chamado no início das requisições relevantes
     */
    public static function handle($idempresa, $idPessoa = null)
    {
        // Só registra se não for bot
        if (self::isBot()) {
            return false;
        }

        return self::logAccess($idempresa, $idPessoa);
    }

    /**
     * Detecta se é um bot
     */
    private static function isBot()
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $bots = ['bot', 'crawl', 'spider', 'scraper', 'curl', 'wget'];
        
        foreach ($bots as $bot) {
            if (stripos($userAgent, $bot) !== false) {
                return true;
            }
        }
        
        return false;
    }
}
