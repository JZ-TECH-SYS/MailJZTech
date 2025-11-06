<?php

namespace src\models;

use core\Model;
use core\Database;
use PDO;

/**
 * Classe modelo para a tabela 'emails_logs' do banco de dados.
 * Registra logs detalhados de cada operação de envio de e-mail.
 *
 * @author MailJZTech
 * @date 2025-01-01
 */
class EmailLogs extends Model
{
    /**
     * Cria um novo log
     *
     * @param int|null $idemail ID do e-mail
     * @param int $idsistema ID do sistema
     * @param int $idusuario ID do usuário
     * @param string $tipo_log Tipo de log
     * @param string $mensagem Mensagem do log
     * @param array|null $dados_adicionais Dados adicionais em JSON
     * @return int|false Retorna o ID do log criado
     */
    public function criar($idemail, $idsistema, $idusuario, $tipo_log, $mensagem, $dados_adicionais = null)
    {
        return self::insert([
            'idemail' => $idemail,
            'idsistema' => $idsistema,
            'idusuario' => $idusuario,
            'tipo_log' => $tipo_log,
            'mensagem' => $mensagem,
            'dados_adicionais' => $dados_adicionais ? json_encode($dados_adicionais) : null,
            'ip_origem' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ])->execute();
    }

    /**
     * Obtém todos os logs de um e-mail
     *
     * @param int $idemail ID do e-mail
     * @return array Retorna um array com os logs
     */
    public function obterPorEmail($idemail)
    {
        return self::select(['*'])
            ->where('idemail', $idemail)
            ->orderBy('data_log', 'ASC')
            ->get();
    }

    /**
     * Obtém todos os logs de um sistema
     *
     * @param int $idsistema ID do sistema
     * @param int $limite Limite de registros
     * @return array Retorna um array com os logs
     */
    public function obterPorSistema($idsistema, $limite = 100)
    {
        return self::select(['*'])
            ->where('idsistema', $idsistema)
            ->orderBy('data_log', 'DESC')
            ->limit($limite)
            ->get();
    }

    /**
     * Obtém logs recentes com informações de e-mail e sistema
     * Usa SQL puro por ser uma query com joins
     *
     * @param int $limite Limite de registros
     * @return array Retorna um array com os logs recentes
     */
    public function obterRecentes($limite = 100)
    {
        $db = Database::getInstance();
        $sql = "SELECT 
                    el.*,
                    e.destinatario,
                    e.assunto,
                    s.nome as sistema
                FROM emails_logs el
                LEFT JOIN emails_enviados e ON el.idemail = e.idemail
                LEFT JOIN sistemas s ON el.idsistema = s.idsistema
                ORDER BY el.data_log DESC
                LIMIT :limite";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtém logs por tipo
     *
     * @param string $tipo_log Tipo de log
     * @param int $limite Limite de registros
     * @return array Retorna um array com os logs
     */
    public function obterPorTipo($tipo_log, $limite = 100)
    {
        return self::select(['*'])
            ->where('tipo_log', $tipo_log)
            ->orderBy('data_log', 'DESC')
            ->limit($limite)
            ->get();
    }

    /**
     * Obtém logs por período
     *
     * @param int $idsistema ID do sistema
     * @param string $data_inicio Data inicial (Y-m-d)
     * @param string $data_fim Data final (Y-m-d)
     * @return array Retorna um array com os logs
     */
    public function obterPorPeriodo($idsistema, $data_inicio, $data_fim)
    {
        $db = Database::getInstance();
        $sql = "SELECT * FROM emails_logs 
                WHERE idsistema = :idsistema 
                AND DATE(data_log) BETWEEN :data_inicio AND :data_fim
                ORDER BY data_log DESC";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':idsistema', $idsistema, PDO::PARAM_INT);
        $stmt->bindValue(':data_inicio', $data_inicio);
        $stmt->bindValue(':data_fim', $data_fim);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Limpa logs antigos (mais de X dias)
     *
     * @param int $dias Número de dias a manter
     * @return bool Retorna true se deletado com sucesso
     */
    public function limparAntigos($dias = 90)
    {
        $db = Database::getInstance();
        $sql = "DELETE FROM emails_logs WHERE data_log < DATE_SUB(NOW(), INTERVAL :dias DAY)";
        
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':dias', $dias, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
}
