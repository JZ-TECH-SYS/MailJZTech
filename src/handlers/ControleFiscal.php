<?php
namespace src\handlers;

use core\Database as db;
use Exception;
use src\models\Controle_fiscal                  as ControleFiscalModel;
use src\models\Controle_fiscal_detalhes_tributacao as DetTribModel;
use src\models\Produtos;

class ControleFiscal
{
    /*───────────────────────────── CONSULTAS ─────────────────────────────*/

    /** Lista todas as regras e seus detalhes */
    public static function getRegrasFiscais(int $idempresa): array
    {
        $regras = ControleFiscalModel::select()
            ->where('idempresa', $idempresa)
            ->get() ?: [];

        foreach ($regras as &$r) {
            $r['detalhes_tributacao'] = DetTribModel::select()
                ->where('idcontrole_fiscal', $r['idcontrole_fiscal'])
                ->where('idempresa', $idempresa)
                ->get();
        }
        return $regras;
    }

    /** Busca 1 regra por id */
    public static function getRegraFiscalById(int $idempresa, int $idcontrole): array
    {
        $regra = ControleFiscalModel::select()
            ->where('idempresa', $idempresa)
            ->where('idcontrole_fiscal', $idcontrole)
            ->one() ?: [];

        if ($regra) {
            $regra['detalhes_tributacao'] = DetTribModel::select()
                ->where('idcontrole_fiscal', $idcontrole)
                ->where('idempresa', $idempresa)
                ->get();
        }
        return $regra;
    }

    /*───────────────────────────── INSERT ─────────────────────────────*/

    public static function addRegraFiscal(array $data): array
    {
        try {
            db::getInstance()->beginTransaction();

            /* cabeçalho */
            $idcontrole = ControleFiscalModel::insert([
                'idempresa' => $data['idempresa'],
                'idusuario' => $data['idusuario'],
                'descricao' => $data['descricao'],
                'apartirde' => $data['apartirde'], // AAAAMM
                'padrao'    => $data['padrao'] ?? 0
            ])->execute();

            /* detalhes */
            self::persistDetalhes($idcontrole, $data);

            db::getInstance()->commit();
            return self::getRegraFiscalById($data['idempresa'], $idcontrole);
        } catch (Exception $e) {
            db::getInstance()->rollBack();
            throw new Exception('Erro ao adicionar regra fiscal: '.$e->getMessage());
        }
    }

    /*───────────────────────────── UPDATE ─────────────────────────────*/

    public static function editRegraFiscal(array $data): array
    {
        try {
            db::getInstance()->beginTransaction();

            /* update cabeçalho */
            ControleFiscalModel::update([
                    'descricao' => $data['descricao'],
                    'apartirde' => $data['apartirde'],
                    'padrao'    => $data['padrao'] ?? 0
                ])
                ->where('idcontrole_fiscal', $data['idcontrole_fiscal'])
                ->where('idempresa', $data['idempresa'])
                ->execute();

            /* apaga e recria detalhes */
            DetTribModel::delete()
                ->where('idcontrole_fiscal', $data['idcontrole_fiscal'])
                ->where('idempresa', $data['idempresa'])
                ->execute();

            self::persistDetalhes($data['idcontrole_fiscal'], $data);

            db::getInstance()->commit();
            return self::getRegraFiscalById($data['idempresa'], $data['idcontrole_fiscal']);
        } catch (Exception $e) {
            db::getInstance()->rollBack();
            throw new Exception('Erro ao editar regra fiscal: '.$e->getMessage());
        }
    }

    /*───────────────────────────── DELETE ─────────────────────────────*/

    public static function deleteRegraFiscal(array $data): array
    {
        try {
            db::getInstance()->beginTransaction();

            $produtosExistem = Produtos::select()
                ->where('idempresa', $data['idempresa'])
                ->where('idcontrole_fiscal', $data['idcontrole_fiscal'])
                ->count();
            
            if ($produtosExistem > 0) {
                throw new Exception('Não é possível excluir a regra fiscal, pois existem produtos vinculados a ela.');
            }

            DetTribModel::delete()
                ->where('idcontrole_fiscal', $data['idcontrole_fiscal'])
                ->where('idempresa', $data['idempresa'])
                ->execute();

            ControleFiscalModel::delete()
                ->where('idcontrole_fiscal', $data['idcontrole_fiscal'])
                ->where('idempresa', $data['idempresa'])
                ->execute();

            db::getInstance()->commit();
            return ['message'=>'Regra fiscal excluída com sucesso'];
        } catch (Exception $e) {
            db::getInstance()->rollBack();
            throw new Exception('Erro ao excluir regra fiscal: '.$e->getMessage());
        }
    }

    /*───────────────────────────── AUXILIAR ─────────────────────────────*/

    /** Insere o array de detalhes já no novo layout */
    private static function persistDetalhes(int $idcontrole, array $data): void
    {
        if (empty($data['detalhes_tributacao'])) return;

        foreach ($data['detalhes_tributacao'] as $d) {
            DetTribModel::insert([
                'idcontrole_fiscal' => $idcontrole,
                'idempresa'         => $data['idempresa'],
                'idusuario'         => $data['idusuario'],

                /* campos-chave da regra */
                'UF'           => $d['UF']           ?? null,
                'uf_origem'    => $d['uf_origem']    ?? null,
                'CFOP'         => $d['CFOP']         ?? null,
                'tipo_imposto' => $d['tipo_imposto'] ?? null,
                'CST'          => $d['CST']          ?? null,
                'CSOSN'        => $d['CSOSN']        ?? null,

                /* classificação mercadoria */
                'NCM'  => $d['NCM']  ?? null,
                'CEST' => $d['CEST'] ?? null,

                /* alíquotas */
                'base'         => $d['base']         ?? null,
                'aliquota'     => $d['aliquota']     ?? null,

                /* novos campos de IVA/FCP/ST */
                'tipo_iva'        => $d['tipo_iva']        ?? null,        // 'CBS','IBS','IS'
                'aliquota_cbs'    => $d['aliquota_cbs']    ?? null,
                'aliquota_ibs'    => $d['aliquota_ibs']    ?? null,
                'aliquota_is'     => $d['aliquota_is']     ?? null,
                'aliquota_fcp'    => $d['aliquota_fcp']    ?? null,
                'v_fcp'           => $d['v_fcp']           ?? null,
                'mod_bc_st'       => $d['mod_bc_st']       ?? null,
                'mva_st'          => $d['mva_st']          ?? null,

                /* seletivo / benefícios */
                'seletivo_cod_prod' => $d['seletivo_cod_prod'] ?? null,
                'cbenef'            => $d['cbenef']            ?? null,

                /* eventual CNPJ terceiro */
                'CNPJ' => $d['CNPJ'] ?? null
            ])->execute();
        }
    }
}
