<?php

namespace core;

use \src\Config;
use \core\Controller as ctrl;

class RouterBase extends Auth
{
    public $token;

    public function __construct()
    {
        $headers = getallheaders();

        $authorization = null;

        $authorization = self::getHead($headers);

        if (!$authorization && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $authorization = self::getHead($headers);
        }

        $this->token = (!empty($authorization) && strlen($authorization) > 8) ? $authorization : null;
    }

    private static function getHead($headers)
    {
        $authorization = null;
        $headers = array_change_key_case($headers, CASE_LOWER);

        if (isset($headers['authorization'])) {
            $authorization = $headers['authorization'];
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authorization = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authorization = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['Authorization'])) {
            $authorization = $_SERVER['Authorization'];
        } elseif (isset($_REQUEST['jwt'])) {
            $authorization = 'Bearer ' . $_REQUEST['jwt'];
        }

        return $authorization;
    }



    public function run($routes)
    {

        $method = Request::getMethod();
        $url = Request::getUrl();

        ctrl::log('[ROUTER] Iniciando resolução de rota');
        ctrl::log('[ROUTER] Método: ' . $method . ' URL normalizada: ' . $url);

        // Define os itens padrão
        $controller = Config::ERROR_CONTROLLER;
        $action = Config::DEFAULT_ACTION;
        $privado = false;
        $args = [];

        if (isset($routes[$method])) {
            ctrl::log('[ROUTER] Total de rotas para método ' . $method . ': ' . count($routes[$method]));

            foreach ($routes[$method] as $route => $callback) {

                // Identifica os argumentos e substitui por regex
                $pattern = preg_replace('(\{[a-z0-9_]{1,}\})', '([a-z0-9-_]{1,})', $route);
                // Correção: padrão correto sem tabs acidentais
                $pattern = preg_replace('(\{[a-z0-9_]{1,}\})', '([a-z0-9-_]{1,})', $route);

                ctrl::log('[ROUTER] Testando rota declarada: ' . $route . ' -> padrão regex: ' . $pattern);
                // Faz o match da URL (sem repetição do padrão)
                if (preg_match('#^' . $pattern . '$#i', $url, $matches) === 1) {

                    // Remove o match completo, restam apenas os grupos capturados
                    array_shift($matches);

                    ctrl::log('[ROUTER] MATCH encontrado para rota: ' . $route . ' Grupos capturados: ' . json_encode($matches));

                    // Pega todos os argumentos para associar
                    $itens = array();
                    if (preg_match_all('(\{[a-z0-9_]{1,}\})', $route, $m)) {
                        $itens = preg_replace('(\{|\})', '', $m[0]);
                    }
                    // Faz a associação
                    $args = array();
                    foreach ($matches as $key => $match) {
                        $args[$itens[$key]] = $match;
                    }
                    ctrl::log('[ROUTER] Args resolvidos: ' . json_encode($args));

                    // Seta o controller/action
                    $callbackSplit = explode('@', $callback[0]);
                    $controller = $callbackSplit[0];
                    $privado =  $callback[1];
                    if (isset($callbackSplit[1])) {
                        $action = $callbackSplit[1];
                    }
                    ctrl::log('[ROUTER] Controller selecionado: ' . $controller . ' Action: ' . $action . ' Privado: ' . ($privado ? 'true' : 'false'));

                    break;
                }
            }
        }

        if ($privado) {
            $auth = new Auth();
            ctrl::log('[ROUTER] Rota privada: iniciando validação de token');
            $auth->validaToken($this->token, $args);
            ctrl::log('[ROUTER] Validação de token concluída');
        }
        $controller = "\src\controllers\\$controller";
        ctrl::log('[ROUTER] Instanciando controller: ' . $controller . ' e executando action: ' . $action);
        $definedController = new $controller();
        $definedController->$action($args);
        ctrl::log('[ROUTER] Execução concluída para ' . $url);
    }
}
