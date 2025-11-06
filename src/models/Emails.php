<?php

namespace src\models;

use core\Model;

/**
 * Classe modelo para a tabela 'emails_enviados' do banco de dados.
 * Representa o histórico de e-mails enviados através da API.
 */
class Emails extends Model
{
    /**
     * Obtém todos os e-mails de um sistema
     */
    public static function getBySystem($idsistema, $limite = 50, $offset = 0)
    {
        $sql = "SELECT * FROM emails_enviados 
                WHERE idsistema = ? 
                ORDER BY data_criacao DESC 
                LIMIT ? OFFSET ?";
        
        return self::query($sql, [$idsistema, $limite, $offset]);
    }

    /**
     * Obtém um e-mail específico
     */
    public static function getById($idemail)
    {
        $sql = "SELECT * FROM emails_enviados WHERE idemail = ?";
        $result = self::query($sql, [$idemail]);
        return !empty($result) ? $result[0] : null;
    }

    /**
     * Cria um novo registro de e-mail
     */
    public static function create($dados)
    {
        $sql = "INSERT INTO emails_enviados 
                (idsistema, destinatario, cc, bcc, assunto, corpo_html, corpo_texto, anexos, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        return self::query($sql, [
            $dados['idsistema'],
            $dados['destinatario'],
            $dados['cc'] ?? null,
            $dados['bcc'] ?? null,
            $dados['assunto'],
            $dados['corpo_html'],
            $dados['corpo_texto'] ?? null,
            $dados['anexos'] ?? null,
            $dados['status'] ?? 'pendente'
        ]);
    }

    /**
     * Atualiza o status de um e-mail
     */
    public static function updateStatus($idemail, $status, $mensagemErro = null)
    {
        $sql = "UPDATE emails_enviados 
                SET status = ?, mensagem_erro = ?, data_atualizacao = NOW()";
        
        $valores = [$status, $mensagemErro];

        if ($status === 'enviado') {
            $sql .= ", data_envio = NOW()";
        }

        $sql .= " WHERE idemail = ?";
        $valores[] = $idemail;

        return self::query($sql, $valores);
    }

    /**
     * Conta total de e-mails de um sistema
     */
    public static function countBySystem($idsistema)
    {
        $sql = "SELECT COUNT(*) as total FROM emails_enviados WHERE idsistema = ?";
        $result = self::query($sql, [$idsistema]);
        return !empty($result) ? $result[0]['total'] : 0;
    }

    /**
     * Obtém estatísticas de e-mails
     */
    public static function getStats($idsistema)
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'enviado' THEN 1 ELSE 0 END) as enviados,
                    SUM(CASE WHEN status = 'erro' THEN 1 ELSE 0 END) as erros,
                    SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) as pendentes
                FROM emails_enviados 
                WHERE idsistema = ?";
        
        $result = self::query($sql, [$idsistema]);
        return !empty($result) ? $result[0] : null;
    }
}
