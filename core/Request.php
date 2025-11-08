<?php
namespace core;

use src\Config;

class Request {

    public static function getUrl() {
        // Tenta rota amigável padrão (?request=...)
        $url = filter_input(INPUT_GET, 'request');
        Controller::log('[REQUEST] Entrada query ?request= ' . var_export($url, true));

        if (empty($url)) {
            // Fallback: usa REQUEST_URI para funcionar com PHP built-in/Apache sem rewrite
            $uri = $_SERVER['REQUEST_URI'] ?? '/';
            Controller::log('[REQUEST] REQUEST_URI bruto: ' . $uri);

            // Remove query string
            if (false !== ($qPos = strpos($uri, '?'))) {
                $uri = substr($uri, 0, $qPos);
                Controller::log('[REQUEST] URI sem query string: ' . $uri);
            }

            // Remove BASE_DIR do início, se houver
            $baseDir = rtrim(Config::BASE_DIR, '/');
            Controller::log('[REQUEST] BASE_DIR configurado: ' . $baseDir);
            if (!empty($baseDir) && $baseDir !== '/') {
                $uri = preg_replace('#^'.preg_quote($baseDir, '#').'#', '', $uri, 1);
                Controller::log('[REQUEST] URI após remoção BASE_DIR: ' . $uri);
            }

            $url = trim($uri, '/');
        } else {
            // Remoção segura do BASE_DIR apenas quando for prefixo (evita remover todas as '/')
            $baseDir = rtrim(Config::BASE_DIR, '/');
            if (!empty($baseDir) && $baseDir !== '/') {
                $url = preg_replace('#^'.preg_quote($baseDir, '#').'#', '', $url, 1);
            }
            Controller::log('[REQUEST] URL após remover BASE_DIR: ' . $url);
        }

        // Normaliza com leading slash
        $final = '/' . ltrim($url, '/');
        Controller::log('[REQUEST] URL final normalizada: ' . $final);
        return $final;
    }

    public static function getMethod() {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }

}