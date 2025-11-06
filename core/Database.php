<?php

namespace core;

use \src\Config;
use PDO;

class Database
{
    private static $_pdo;

    /**
     * Chama o banco cadastro em Config.php
     * se parametro $db for passado, e for uma numero idFilial, chama o banco da filial
     * @param string $db
     */
    public static function getInstance()
    {
        if (!isset(self::$_pdo)) {
            $port = defined('DB_PORT') && Config::DB_PORT ? ";port=" . Config::DB_PORT : "";
            $String_conect = Config::DB_DRIVER . ":dbname=" . Config::DB_DATABASE . ";host=" . Config::DB_HOST . $port;
            self::$_pdo = new \PDO($String_conect, Config::DB_USER, Config::DB_PASS);
            self::$_pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
            self::$_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // 🧩 Garante charset 4 bytes para emojis e caracteres especiais
            self::$_pdo->exec("SET NAMES utf8mb4");
        }

        return self::$_pdo;
    }

    /**
     * Retorna ou executa um SQL
     * se penultimo for false retorna SQL com parametros subtituidos
     * ultimo parametro ira logar SQL e retorno do mesmo nos LOGs caso seja true
     * sempre verificar tipos dos campos nos Parametros do SQL EX:int,string ...
     * retorno array ['retorno']
     * erro em ['error']
     * @param string  $banco
     * @param array   $params
     * @param string  $sqlnome
     * @param boolean $exec
     * @param boolean $log
     *
     * @return array ['retorno']
     */
    public static function switchParams($params, $sqlnome, $exec = false, $log = false)
    {
        $sql = file_get_contents('../SQL/' . $sqlnome . '.sql');
        $res = [
            'retorno' => false,
            'error' => false,
        ];
        $pdo = self::getInstance();
        try {
            if (!empty($params)) {
                foreach ($params as $nome => $valor) {
                    $rpl = str_replace('\"', "'", $valor);
                    $valor = is_string($valor) ? trim($rpl) : $valor;
                    $sql = preg_replace('/:' . (string)$nome . '\b/i', $valor, $sql);
                };
            }
            $sql = str_replace('idsql=', 'idsql=E', $sql);
            if ($exec) {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare($sql);
                $stmt->execute();
                $res['retorno'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $pdo->commit();
            } else {
                $res['retorno'] = $sql;
            }

            if ($log) {
                $logjv = [
                    'data' => date('Y-m-d H:i:s'),
                    'sql'  => $sql,
                    'params' => $params,
                    'res'  => $res['retorno']
                ];
                file_put_contents('../logs/exec' . date('Y-m-d') . '-sql.txt', print_r($logjv, true), FILE_APPEND);
            }
        } catch (\Exception $e) {
            $pdo->rollBack();
            $logjv = [
                'data' => date('Y-m-d H:i:s'),
                'msg'  => $e->getMessage(),
                //'trace' => $e->getTrace(),
                'sql' => $sql
            ];
            file_put_contents('../logs/error' . date('Y-m-d') . '-sql.txt', print_r($logjv, true), FILE_APPEND);
            $res['error'] = $e->getMessage();
        }

        unset($pdo);
        return $res;
    }

    private function __construct() {}
    private function __clone() {}
    public function __wakeup() {}
}
