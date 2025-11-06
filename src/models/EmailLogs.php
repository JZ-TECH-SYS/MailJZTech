<?php

namespace src\models;

use core\Model;

/**
 * Classe modelo para a tabela 'emails_logs' do banco de dados.
 * Registra logs detalhados de cada operação de envio de e-mail.
 */
class EmailLogs extends Model
{
    /**
     * Cria um novo log
     */
    public static function create($idemail, $acao, $detalhes = null)
    {
        $sql = "INSERT INTO emails_logs (idemail, acao, detalhes) VALUES (?, ?, ?)";
        return self::query($sql, [$idemail, $acao, $detalhes]);
    }

    /**
     * Obtém todos os logs de um e-mail
     */
    public static function getByEmail($idemail)
    {
        $sql = "SELECT * FROM emails_logs WHERE idemail = ? ORDER BY data_criacao ASC";
        return self::query($sql, [$idemail]);
    }

    /**
     * Obtém logs recentes
     */
    public static function getRecent($limite = 100)
    {
        $sql = "SELECT el.*, e.destinatario, e.assunto, s.nome as sistema 
                FROM emails_logs el
                JOIN emails_enviados e ON el.idemail = e.idemail
                JOIN sistemas s ON e.idsistema = s.idsistema
                ORDER BY el.data_criacao DESC
                LIMIT ?";
        
        return self::query($sql, [$limite]);
    }
}
