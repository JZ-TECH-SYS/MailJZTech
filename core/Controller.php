<?php

namespace core;

use Exception;
use \src\Config;

class Controller
{

    public static function redirect($url)
    {
        header("Location: " . self::getBaseUrl() . $url);
        exit;
    }

    public static function getBaseUrl()
    {
        $base = (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') ? 'https://' : 'http://';
        $base .= $_SERVER['SERVER_NAME'];
        if ($_SERVER['SERVER_PORT'] != '80') {
            $base .= ':' . $_SERVER['SERVER_PORT'];
        }
        $base .= Config::BASE_DIR;

        return $base;
    }

    private function _render($folder, $viewName, $viewData = [])
    {
        // Suporta caminhos com subpastas: 'login/login', 'sistemas/criar_sistema', etc
        $filePath = '../src/views/' . $folder . '/' . $viewName . '.php';

        // Se o arquivo não existe, tenta verificar se $viewName é uma pasta com index.php
        if (!file_exists($filePath)) {
            $alternativePath = '../src/views/' . $folder . '/' . $viewName . '/index.php';
            if (file_exists($alternativePath)) {
                $filePath = $alternativePath;
            }
        }

        if (file_exists($filePath)) {
            extract($viewData);
            $render = fn($vN, $vD = []) => $this->renderPartial($vN, $vD);
            $base = $this->getBaseUrl();
            require $filePath;
        }
    }

    private function renderPartial($viewName, $viewData = [])
    {
        $this->_render('partials', $viewName, $viewData);
    }

    public function render($viewName, $viewData = [])
    {
        $this->_render('pages', $viewName, $viewData);
    }

    /**
     * recebe um array e verifica item vazios se tiver algum vazio retorna true
     * @param array $error
     */
    public function AllVazio($error)
    {
        foreach ($error as $it => $value) {
            if (empty($it) || is_null($it) || $it == '' || trim($it) == '') {
                return true;
            }
        }
        return false;
    }

    /**
     * Verifica se há campos vazios ou não presentes na lista de campos a serem validados.
     * @param array $campos  Array associativo com os campos e seus respectivos valores a serem verificados.
     * @param array $validar Lista de campos obrigatórios a serem verificados.
     * @return bool Retorna true se todos os campos obrigatórios estiverem preenchidos, caso contrário, rejeita a resposta.
     */
    public function verificarCamposVazios(array $campos, array $validar): bool
    {
        // Verifica se os campos obrigatórios estão presentes e preenchidos
        foreach ($validar as $key) {
            if (!array_key_exists($key, $campos)) {
                throw new Exception('Campo obrigatório não encontrado: ' . $key);
            }

            if (is_array($campos[$key])) {
                if (empty($campos[$key])) {
                    throw new Exception('Campo obrigatório vazio: ' . $key);
                }
            } else {
                if (empty(trim($campos[$key]))) {
                    throw new Exception('Campo obrigatório vazio: ' . $key);
                }
            }
        }

        return true;
    }



    /**
     * receber boy e retorna array
     * @param bool $valida_body
     */
    public function getBody($valida_body = true)
    {
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        if (empty($data) && $valida_body) {
            throw new Exception('Nenhum dado foi enviado');
        }
        return $data;
    }

    /**
     * define status e respota para usuario
     * @param array $item
     * @param int $status
     * ex: [
     *   result: [dados pro retorno]
     *   error: [msg de erro] || false
     * ]
     */
    public static function response($item, $status = 200)
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode([
            'result' => $item,
            'error' => ($status > 300) ? true : false
        ]);
        die;
    }

    /**
     * define status e respota para usuario
     * $msg
     */
    public static function rejectResponse($msg)
    {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode([
            'result' => '',
            'error'  => $msg->getMessage()
        ]);
        die;
    }

    /**
     * verifica parametro de saldo e retorna
     * @return bool
     */
    public static function validar_saldo()
    {
        return isset($_SESSION['empresa']) && $_SESSION['empresa']['controlar_estoque'] == 'true' ? true : false;
    }

    public static function getUsuario()
    {
        return $_SESSION['empresa']['iduser'];
    }

    public static function getEmpresa()
    {
        return $_SESSION['empresa']['idempresa'];
    }

    /**
     * Log de sistema profissional
     * @param string|array $log Conteúdo do log (string ou array para serialização)
     */
    public static function log($log)
    {
        // Converte array para JSON se necessário
        if (is_array($log)) {
            $log = json_encode($log, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        // Data no formato brasileiro (dd/mm/yyyy)
        $data = date('d/m/Y H:i:s');

        // Caminho do arquivo de log
        $logFile = '../logs/app.log';

        // Garante que o diretório existe
        $logDir = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // Escreve no arquivo com tratamento de erro
        $entry = "[$data] $log" . PHP_EOL;
        if (file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX) === false) {
            // Em caso de erro, poderia lançar uma exception ou logar em outro lugar
            error_log("Falha ao escrever no log: $entry");
        }
    }

    public static function head($bearer = false)
    {
        $headers = getallheaders();
        $authorization = null;
        $authorization = self::authorization($headers);

        if (!$authorization && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $authorization = self::authorization($headers);
        }

        if ($bearer) {
            if (strpos($authorization, 'Bearer ') === 0) {
                $authorization = str_replace('Bearer ', '', $authorization);
            } else {
                return null;
            }
        }

        return (!empty($authorization) && strlen($authorization) > 8) ? $authorization : null;
    }

    private static function authorization($headers)
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

    /**
     * Extrai o token do header Authorization com ou sem Bearer
     * @param bool $bearer Se true, remove o prefixo "Bearer " do token
     * @return string|null Retorna o token ou null se não encontrado
     */
    public static function getToken($bearer = true)
    {
        $token = self::head(false);

        if (!$token) {
            return null;
        }

        if ($bearer && strpos($token, 'Bearer ') === 0) {
            return str_replace('Bearer ', '', $token);
        }

        return $token;
    }
}
