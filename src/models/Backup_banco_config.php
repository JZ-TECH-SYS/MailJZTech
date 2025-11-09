<?php

namespace src\models;

use core\Model;

/**
 * Model para gerenciar configurações de backup de bancos de dados.
 * 
 * Tabela: backup_banco_config
 * 
 * @property int $idbackup_banco_config
 * @property string $nome_banco
 * @property string $bucket_nome
 * @property string $pasta_base
 * @property int $retencao_dias
 * @property string $ultimo_backup_em
 * @property int $total_backups
 * @property bool $ativo
 * @property string $criado_em
 * @property string $atualizado_em
 */
class Backup_banco_config extends Model
{
    /**
     * Busca todas as configurações ativas ordenadas por nome.
     *
     * @return array
     */
    public static function obterAtivos(): array
    {
        return self::select()
            ->where('ativo', 1)
            ->orderBy('nome_banco', 'ASC')
            ->get();
    }

    /**
     * Busca configuração por nome do banco.
     *
     * @param string $nomeBanco
     * @return array|null
     */
    public static function obterPorNome(string $nomeBanco): ?array
    {
        $resultado = self::select()
            ->where('nome_banco', $nomeBanco)
            ->one();

        return $resultado ?: null;
    }

    /**
     * Atualiza as estatísticas de backup (último backup e total).
     *
     * @param int $id
     * @param string $dataBackup
     * @return void
     */
    public static function atualizarEstatisticas(int $id, string $dataBackup): void
    {
        $config = self::select(['total_backups'])
            ->where('idbackup_banco_config', $id)
            ->one();

        self::update([
            'ultimo_backup_em' => $dataBackup,
            'total_backups' => ($config['total_backups'] ?? 0) + 1
        ])
        ->where('idbackup_banco_config', $id)
        ->execute();
    }

    /**
     * Verifica se existe configuração com o nome informado (exceto o próprio ID).
     *
     * @param string $nomeBanco
     * @param int|null $excluirId
     * @return bool
     */
    public static function existeNome(string $nomeBanco, ?int $excluirId = null): bool
    {
        $query = self::select(['idbackup_banco_config'])
            ->where('nome_banco', $nomeBanco);

        if ($excluirId !== null) {
            $query->where('idbackup_banco_config', '!=', $excluirId);
        }

        $resultado = $query->one();
        return !empty($resultado);
    }
}
