<?php

namespace src\handlers;

use src\models\Emails_logs;

/**
 * Handler para lógica de negócio de Logs
 * Gerencia consultas e operações relacionadas a logs de e-mails
 *
 * @author MailJZTech
 * @date 2025-01-09
 */
class Logs
{
    /**
     * Lista logs com filtros
     *
     * @param array $filtros Filtros opcionais
     * @return array Retorna os logs
     */
    public static function listar($filtros = [])
    {
        $query = Emails_logs::select();

        // Aplicar filtros
        if (!empty($filtros['idsistema'])) {
            $query->where('idsistema', $filtros['idsistema']);
        }

        if (!empty($filtros['idemail'])) {
            $query->where('idemail', $filtros['idemail']);
        }

        if (!empty($filtros['tipo_log'])) {
            $query->where('tipo_log', $filtros['tipo_log']);
        }

        if (!empty($filtros['data_inicio'])) {
            $query->where('data_log', '>=', $filtros['data_inicio']);
        }

        if (!empty($filtros['data_fim'])) {
            $query->where('data_log', '<=', $filtros['data_fim']);
        }

        $limite = $filtros['limite'] ?? 50;
        $offset = $filtros['offset'] ?? 0;

        return $query
            ->orderBy('data_log', 'DESC')
            ->limit($limite)
            ->offset($offset)
            ->get();
    }

    /**
     * Obtém um log específico
     *
     * @param int $idlog ID do log
     * @return array|false Retorna os dados do log
     */
    public static function obter($idlog)
    {
        return Emails_logs::select()
            ->where('idlog', $idlog)
            ->one();
    }

    /**
     * Obtém logs de um e-mail específico
     *
     * @param int $idemail ID do e-mail
     * @return array Retorna array com os logs
     */
    public static function obterPorEmail($idemail)
    {
        return Emails_logs::obterPorEmail($idemail);
    }

    /**
     * Obtém logs recentes
     *
     * @param int $limite Limite de registros
     * @return array Retorna array com os logs
     */
    public static function obterRecentes($limite = 100)
    {
        return Emails_logs::obterRecentes($limite);
    }

    /**
     * Obtém logs por tipo
     *
     * @param string $tipo_log Tipo de log
     * @param int $limite Limite de registros
     * @return array Retorna array com os logs
     */
    public static function obterPorTipo($tipo_log, $limite = 100)
    {
        return Emails_logs::obterPorTipo($tipo_log, $limite);
    }

    /**
     * Obtém logs por período
     *
     * @param int $idsistema ID do sistema
     * @param string $data_inicio Data inicial (Y-m-d)
     * @param string $data_fim Data final (Y-m-d)
     * @return array Retorna array com os logs
     */
    public static function obterPorPeriodo($idsistema, $data_inicio, $data_fim)
    {
        return Emails_logs::obterPorPeriodo($idsistema, $data_inicio, $data_fim);
    }

    /**
     * Limpa logs antigos
     *
     * @param int $dias Número de dias a manter
     * @return bool Retorna true se deletado com sucesso
     */
    public static function limparAntigos($dias = 90)
    {
        return Emails_logs::limparAntigos($dias);
    }

    /**
     * Conta total de logs com filtros
     *
     * @param array $filtros Filtros opcionais
     * @return int Retorna o total
     */
    public static function contar($filtros = [])
    {
        $query = Emails_logs::select();

        // Aplicar filtros
        if (!empty($filtros['idsistema'])) {
            $query->where('idsistema', $filtros['idsistema']);
        }

        if (!empty($filtros['idemail'])) {
            $query->where('idemail', $filtros['idemail']);
        }

        if (!empty($filtros['tipo_log'])) {
            $query->where('tipo_log', $filtros['tipo_log']);
        }

        return $query->count() ?? 0;
    }
}
