<?php

namespace src\models;

use core\Model;
use core\Database;

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
        return self::select()
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
        return self::select()
            ->where('idsistema', $idsistema)
            ->orderBy('data_log', 'DESC')
            ->limit($limite)
            ->get();
    }

    /**
     * Obtém logs recentes com informações de e-mail e sistema
     * Usa SQL puro via switchParams() por ter JOINs
     *
     * @param int $limite Limite de registros
     * @return array Retorna um array com os logs recentes
     */
    public function obterRecentes($limite = 100)
    {
        $params = [
            'limite' => $limite
        ];
        
        $resultado = Database::switchParams($params, 'logs_obter_recentes', true);
        
        return $resultado['retorno'] ?? [];
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
        return self::select()
            ->where('tipo_log', $tipo_log)
            ->orderBy('data_log', 'DESC')
            ->limit($limite)
            ->get();
    }

    /**
     * Obtém logs por período
     * Usa SQL puro via switchParams() por ter filtro de data complexo
     *
     * @param int $idsistema ID do sistema
     * @param string $data_inicio Data inicial (Y-m-d)
     * @param string $data_fim Data final (Y-m-d)
     * @return array Retorna um array com os logs
     */
    public function obterPorPeriodo($idsistema, $data_inicio, $data_fim)
    {
        $params = [
            'idsistema' => $idsistema,
            'data_inicio' => "'" . $data_inicio . "'",
            'data_fim' => "'" . $data_fim . "'"
        ];
        
        $resultado = Database::switchParams($params, 'logs_obter_por_periodo', true);
        
        return $resultado['retorno'] ?? [];
    }

    /**
     * Limpa logs antigos (mais de X dias)
     * Usa SQL puro via switchParams()
     *
     * @param int $dias Número de dias a manter
     * @return bool Retorna true se deletado com sucesso
     */
    public function limparAntigos($dias = 90)
    {
        $params = [
            'dias' => $dias
        ];
        
        $resultado = Database::switchParams($params, 'logs_limpar_antigos', true);
        
        return !empty($resultado['retorno']);
    }
}
