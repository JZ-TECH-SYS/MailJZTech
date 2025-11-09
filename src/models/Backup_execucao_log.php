<?php

namespace src\models;

use core\Model;

/**
 * Model para gerenciar logs de execução de backups.
 * 
 * Tabela: backup_execucao_log
 * 
 * @property int $idbackup_execucao_log
 * @property int $idbackup_banco_config
 * @property string $iniciado_em
 * @property string $finalizado_em
 * @property string $status
 * @property string $mensagem_erro
 * @property string $arquivo_local
 * @property string $gcs_objeto
 * @property int $tamanho_bytes
 * @property string $checksum_sha256
 * @property string $criado_em
 */
class Backup_execucao_log extends Model
{
    /**
     * Busca logs de uma configuração específica (limitado).
     *
     * @param int $idConfig
     * @param int $limite
     * @return array
     */
    public static function obterPorConfig(int $idConfig, int $limite = 50): array
    {
        return self::select()
            ->where('idbackup_banco_config', $idConfig)
            ->orderBy('iniciado_em', 'DESC')
            ->limit($limite)
            ->get();
    }

    /**
     * Busca logs com status de erro.
     *
     * @param int|null $idConfig
     * @param int $limite
     * @return array
     */
    public static function obterErros(?int $idConfig = null, int $limite = 20): array
    {
        $query = self::select()
            ->where('status', 'error');

        if ($idConfig !== null) {
            $query->where('idbackup_banco_config', $idConfig);
        }

        return $query->orderBy('iniciado_em', 'DESC')
            ->limit($limite)
            ->get();
    }

    /**
     * Busca o último backup bem-sucedido de uma configuração.
     *
     * @param int $idConfig
     * @return array|null
     */
    public static function obterUltimoSucesso(int $idConfig): ?array
    {
        $resultado = self::select()
            ->where('idbackup_banco_config', $idConfig)
            ->where('status', 'success')
            ->orderBy('iniciado_em', 'DESC')
            ->limit(1)
            ->one();

        return $resultado ?: null;
    }

    /**
     * Atualiza o status e dados de finalização de um log.
     *
     * @param int $idLog
     * @param string $status
     * @param array $dados (finalizado_em, mensagem_erro, gcs_objeto, tamanho_bytes, checksum_sha256)
     * @return void
     */
    public static function finalizarLog(int $idLog, string $status, array $dados = []): void
    {
        $update = [
            'status' => $status,
            'finalizado_em' => $dados['finalizado_em'] ?? date('Y-m-d H:i:s')
        ];

        if (isset($dados['mensagem_erro'])) {
            $update['mensagem_erro'] = $dados['mensagem_erro'];
        }
        if (isset($dados['gcs_objeto'])) {
            $update['gcs_objeto'] = $dados['gcs_objeto'];
        }
        if (isset($dados['tamanho_bytes'])) {
            $update['tamanho_bytes'] = $dados['tamanho_bytes'];
        }
        if (isset($dados['checksum_sha256'])) {
            $update['checksum_sha256'] = $dados['checksum_sha256'];
        }

        self::update($update)
            ->where('idbackup_execucao_log', $idLog)
            ->execute();
    }

    /**
     * Remove logs antigos baseado em data de retenção.
     *
     * @param int $idConfig
     * @param int $diasRetencao
     * @return int Quantidade de logs removidos
     */
    public static function limparAntigos(int $idConfig, int $diasRetencao): int
    {
        $dataLimite = date('Y-m-d H:i:s', strtotime("-{$diasRetencao} days"));

        // Busca os logs a serem removidos para retornar a contagem
        $logsAntigos = self::select(['idbackup_execucao_log'])
            ->where('idbackup_banco_config', $idConfig)
            ->where('iniciado_em', '<', $dataLimite)
            ->get();

        if (!empty($logsAntigos)) {
            self::delete()
                ->where('idbackup_banco_config', $idConfig)
                ->where('iniciado_em', '<', $dataLimite)
                ->execute();
        }

        return count($logsAntigos);
    }
}
