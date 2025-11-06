<?php

/**
 * Classe responsável pelo controle de BKPController
 * Autor: Joaosn
 * Data de início: 23/05/2023
 */

namespace src\controllers;

use \core\Controller as Cltr;
use \core\Database as DB;
use \src\Config as CF;
use \src\controllers\EmailController;

class BKPController extends Cltr
{
    public function gerar($args)
    {
        if (!isset($args['token']) || $args['token'] !== '9b9a-4b9a4b9a4b9aCC5547645643') {
            Cltr::response(['msg' => 'Token inválido'], 401);
        }

        $enviar = (isset($args['enviar']) && $args['enviar'] == '1' ? true : false);
        // Configurações do banco de dados
        $dbHost = CF::DB_HOST;
        $dbUsername = CF::DB_USER;
        $dbPassword = CF::DB_PASS;
        $dbName = CF::DB_DATABASE;

        // Nome do arquivo de backup
        $backupFileName = 'backup_' . date('Y-m-d_H-i-s') . '.sql';

        // Comando para gerar o backup
        $backupFilePath = './../SQL/' . $backupFileName;
        $command = "mysqldump --triggers --routines --all-databases --host={$dbHost} --user={$dbUsername} --password='{$dbPassword}' > {$backupFilePath}";

        // Executa o comando para gerar o backup
        exec($command, $output, $result);

        if ($result === 0) {
            // O backup foi gerado com sucesso
            $message = 'Backup gerado com sucesso. ';
            if($enviar){
                $backupFileSize = filesize($backupFilePath);
                $emailSizeLimit = 26214400; // 25MB
                $email = new EmailController();
                if ($backupFileSize < $emailSizeLimit) {
                    // Envia o backup por e-mail se for menor que 25 MB
                    $email->sendEmail(
                        'jv.zyzz.legado@gmail.com',
                        'Backup gerado com sucesso',
                        $message . 'Arquivo anexado ao e-mail.',
                        'Zehenrique0822@gmail.com',
                        $backupFilePath
                    );
                } else {
                    // Notifica que o backup está disponível no servidor
                    $email->sendEmail(
                        'jv.zyzz.legado@gmail.com',
                        'Backup gerado com sucesso',
                        $message . 'Arquivo disponível no servidor. Tamanho excede o limite para envio por e-mail.',
                        'Zehenrique0822@gmail.com'
                    );
                }
            }
            Cltr::response(['msg' => $message], 200);
        } 

        if ($result !== 0) {
            // Ocorreu um erro ao gerar o backup
            $errorOutput = implode("\n", $output);
            $message = 'Erro ao gerar o backup: ' . $errorOutput;
                $email = new EmailController();
                $email->sendEmail(
                     'jv.zyzz.legado@gmail.com'
                    , 'Backup NÃO gerado!'
                    , $message
                    , null
                    , null
                );
            Cltr::response(['msg' => $message], 500);
        }


    }
}
