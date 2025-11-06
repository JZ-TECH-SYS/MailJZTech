<?php
namespace src\handlers\NFEs\UtilsNF;

use src\models\Nota_fiscal_backup;
use Exception;

/**
 * XMLBackupHelper
 * ------------
 * Mantém o histórico de tentativas de envio da NF-e / NFC-e.
 * Cada reenvio grava uma linha em nota_fiscal_backup.
 */
class XMLBackupHelper
{
    /* ================================================================
       1. Número da próxima tentativa
       ================================================================ */
    public static function proxTentativa(int $idempresa,int $idpedidovenda): int
    {
        $last = Nota_fiscal_backup::select()
            ->where('idempresa', $idempresa)
            ->where('idpedidovenda', $idpedidovenda)
            ->count();

        return $last + 1;
    }

    /* ================================================================
       2. Registra backup antes do reenvio
       ================================================================ */
    public static function registrar(array $notaAnt): void
    {
        if (empty($notaAnt['idregistronota'])) {
            throw new Exception('Backup: idregistronota é obrigatório.');
        }

        Nota_fiscal_backup::insert([
            'idregistronota'      => $notaAnt['idregistronota'],
            'idempresa'           => $notaAnt['idempresa'],
            'idpedidovenda'       => $notaAnt['idpedidovenda'],
            'tentativa'           => self::proxTentativa($notaAnt['idempresa'],$notaAnt['idpedidovenda']),
            'idsituacao_anterior' => $notaAnt['idsituacaonotasefaz'] ?? null,
            'cstat_anterior'      => $notaAnt['cstat_sefaz']         ?? null,
            'msgsefaz_anterior'   => $notaAnt['msgsefaz']            ?? null,
            'numeronota_old'      => $notaAnt['numeronota']          ?? null,
            'serie_old'           => $notaAnt['serie']               ?? null,
            'chavesefaz_old'      => $notaAnt['chavesefaz']          ?? null,
            'protocolo_old'       => $notaAnt['protocolo_autorizacao'] ?? null,
            'xml_old'             => $notaAnt['xml']                 ?? null,
        ])->execute();
    }

    /* ================================================================
       3. Históricos já gravados (se precisar em telas ou logs)
       ================================================================ */
    public static function getHistorico(int $idregistronota): array
    {
        return Nota_fiscal_backup::select()
            ->where('idregistronota', $idregistronota)
            ->orderBy('tentativa', 'asc')
            ->get();
    }
}
