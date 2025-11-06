<?php

namespace src\handlers\NFEs\UtilsNF\Tags;

class XMLtagsImpostos
{
    /* ==================================================================
       1. Funções de Montagem dos Impostos
       ==================================================================*/

    public static function imposto(int $itemNumber): \stdClass
    {
        $std = new \stdClass();
        $std->item = $itemNumber;
        return $std;
    }

    public static function buildICMS($imp,  $vProd,  $itemNumber,  $crt)
    {

        $orig = '0';
        $aliq = (float)($imp['aliquota'] ?? 0);

        if (in_array($crt, [1, 2])) {
            // Simples Nacional
            $std = new \stdClass();
            $std->item = $itemNumber;
            $std->orig = $orig;
            $csosn = $imp['CSOSN'] ?? '102';


            switch ($csosn) {
                case '101':
                    $std->CSOSN = '101';
                    $std->pCredSN = $aliq;
                    $std->vCredICMSSN = round($vProd * $aliq / 100, 2);
                    break;

                case '201':
                case '202':
                case '500':
                    $std->CSOSN = $csosn;
                    self::fillST($std, $imp, $vProd);
                    break;

                default:
                    $std->CSOSN = '102';
            }
            return ['method' => 'tagICMSSN', 'data' => $std];
        }

        // Regime Normal
        $std = new \stdClass();
        $std->item = $itemNumber;
        $std->orig = $orig;
        $cst = $imp['CST'] ?? '00';

        switch ($cst) {
            case '00':
                $std->CST = '00';
                $std->modBC = '3';
                $std->vBC = $vProd;
                $std->pICMS = $aliq;
                $std->vICMS = round($vProd * $aliq / 100, 2);
                self::fillFCP($std, $imp, $vProd, false);
                break;

            case '20':
                $std->CST = '20';
                $std->modBC = '3';
                $red = (float)($imp['perc_reducao'] ?? 0);
                $std->pRedBC = $red;
                $std->vBC = round($vProd * (100 - $red) / 100, 2);
                $std->pICMS = $aliq;
                $std->vICMS = round($std->vBC * $aliq / 100, 2);
                self::fillFCP($std, $imp, $std->vBC, false);
                break;

            case '10':
            case '30':
                $std->CST = $cst;
                $std->modBC = '3';
                $std->vBC = $vProd;
                $std->pICMS = $aliq;
                $std->vICMS = round($vProd * $aliq / 100, 2);
                self::fillST($std, $imp, $vProd);
                self::fillFCP($std, $imp, $vProd, true);
                break;

            case '40':
            case '41':
            case '50':
                $std->CST = $cst;
                break;

            case '51':
                $std->CST = '51';
                $std->modBC = '3';
                $std->vBC = $vProd;
                $std->pICMS = $aliq;
                $std->vICMS = round($vProd * $aliq / 100, 2);
                $pDif = (float)($imp['perc_diferido'] ?? 100);
                $std->pDif = $pDif;
                $std->vICMSDif = round($std->vICMS * $pDif / 100, 2);
                $std->vICMSOp  = $std->vICMS - $std->vICMSDif;
        }

        return ['method' => 'tagICMS', 'data' => $std];
    }

    public static function buildPIS(array $imp, float $vProd, int $crt, int $itemNumber): array
    {
        $std = new \stdClass();
        $std->item = $itemNumber;
        $cst = $imp['CST'] ?? '01';

        if (in_array($crt, [1, 2])) {                     // Simples Nacional
            // CST que exige base/valor – gerará <PISOutr>
            if (in_array($cst, ['01', '02', '49'])) {
                $std->CST   = $cst;
                $std->vBC   = $vProd;                      // base = valor do item
                $std->pPIS  = $imp['aliquota'] ?? 0;       // % PIS
                $std->vPIS  = round($vProd * $std->pPIS / 100, 2);
            } else {                                      // 04‑09 (PISNT)
                $std->CST = $cst;
            }
            return ['method' => 'tagPIS', 'data' => $std]; // <<< usa tagPIS
        }

        if (in_array($cst, ['01', '02'])) {
            $std->CST = $cst;
            $std->vBC = $vProd;
            $std->pPIS = $imp['aliquota'] ?? 1.65;
            $std->vPIS = round($vProd * $std->pPIS / 100, 2);
            return ['method' => 'tagPISAliq', 'data' => $std];
        }

        $std->CST = $cst;
        return ['method' => 'tagPIS', 'data' => $std];
    }

    public static function buildCOFINS(array $imp, float $vProd, int $crt, int $itemNumber): array
    {
        $std = new \stdClass();
        $std->item = $itemNumber;
        $cst = $imp['CST'] ?? '01';

        if (in_array($crt, [1, 2])) {                     // Simples Nacional
            if (in_array($cst, ['01', '02', '49'])) {       // gera <COFINSOutr>
                $std->CST     = $cst;
                $std->vBC     = $vProd;
                $std->pCOFINS = $imp['aliquota'] ?? 0;     // % COFINS
                $std->vCOFINS = round($vProd * $std->pCOFINS / 100, 2);
            } else {                                      // 04‑09 (COFINSNT)
                $std->CST = $cst;
            }
            return ['method' => 'tagCOFINS', 'data' => $std]; // <<< usa tagCOFINS
        }


        if (in_array($cst, ['01', '02'])) {
            $std->CST = $cst;
            $std->vBC = $vProd;
            $std->pCOFINS = $imp['aliquota'] ?? 7.6;
            $std->vCOFINS = round($vProd * $std->pCOFINS / 100, 2);
            return ['method' => 'tagCOFINSAliq', 'data' => $std];
        }

        $std->CST = $cst;
        return ['method' => 'tagCOFINS', 'data' => $std];
    }

    public static function buildIPI(array $imp, float $vProd, int $itemNumber): array
    {
        $std = new \stdClass();
        $std->item = $itemNumber;
        $cst = $imp['CST'] ?? '99';

        if (in_array($cst, ['00', '49', '50', '99'])) {
            $std->CST = $cst;
            $std->vBC = $vProd;
            $std->pIPI = $imp['aliquota'] ?? 0;
            $std->vIPI = round($vProd * $std->pIPI / 100, 2);
            return ['method' => 'tagIPITrib', 'data' => $std];
        }

        $std->CST = $cst;
        return ['method' => 'tagIPINT', 'data' => $std];
    }

    public static function buildISSQN(array $imp, float $vServ, int $itemNumber): array
    {
        $std = new \stdClass();
        $std->item = $itemNumber;
        $std->vBC = $vServ;
        $std->vAliq = (float)($imp['aliquota'] ?? 2);
        $std->vISSQN = round($vServ * $std->vAliq / 100, 2);
        $std->cMunFG = $imp['cMunFG'] ?? '0000000';
        $std->cListServ = $imp['cListServ'] ?? '0000';
        return ['method' => 'tagISSQN', 'data' => $std];
    }

    /**
     * Monta a tag <ICMSTot> com os totais da nota.
     *
     * @param array $pedido Dados do pedido/venda.
     * @param float $totalICMS Valor total de ICMS.
     * @param float $totalPIS Valor total de PIS.
     * @param float $totalCOFINS Valor total de COFINS.
     * @return \stdClass
     */
    public static function ICMSTot(array $pedido, float $totalICMS, float $totalPIS, float $totalCOFINS): \stdClass
    {
        $std = new \stdClass();
        $std->vBC = '0.00'; // Base de cálculo ICMS (geralmente 0 para Simples Nacional)
        $std->vICMS = number_format($totalICMS, 2, '.', '');
        $std->vICMSDeson = '0.00';
        $std->vFCP = '0.00';
        $std->vBCST = '0.00';
        $std->vST = '0.00';
        $std->vFCPST = '0.00';
        $std->vFCPSTRet = '0.00';
        $std->vProd = number_format($pedido['total_pedido'] ?? 0, 2, '.', '');
        $std->vFrete = '0.00';
        $std->vSeg = '0.00';
        $std->vDesc = '0.00';
        $std->vII = '0.00';
        $std->vIPI = '0.00';
        $std->vIPIDevol = '0.00';
        $std->vPIS = number_format($totalPIS, 2, '.', '');
        $std->vCOFINS = number_format($totalCOFINS, 2, '.', '');
        $std->vOutro = '0.00';
        $std->vNF = number_format($pedido['total_pedido'] ?? 0, 2, '.', '');
        $std->vTotTrib = number_format($totalICMS + $totalPIS + $totalCOFINS, 2, '.', '');

        return $std;
    }

    /* ==================================================================
       2. Helpers para FCP e ST
       ==================================================================*/

    private static function fillST(\stdClass &$std, array $imp, float $vProd): void
    {

        if (!isset($imp['mod_bc_st'])) {
            return;
        }
        $std->modBCST = $imp['mod_bc_st'];
        $mva = (float)($imp['mva_st'] ?? 0);
        $std->pMVAST = $mva ?: null;
        $std->vBCST = $mva ? round($vProd * (1 + $mva / 100), 2) : $vProd;
        $std->pICMSST = (float)($imp['aliquota'] ?? 0);
        $std->vICMSST = round($std->vBCST * $std->pICMSST / 100, 2);
    }

    private static function fillFCP(\stdClass &$std, array $imp, float $base, bool $st): void
    {
        if (empty($imp['aliquota_fcp'])) {
            return;
        }
        $p = (float)$imp['aliquota_fcp'];
        $v = round($base * $p / 100, 2);
        if ($st) {
            $std->pFCPST = $p;
            $std->vFCPST = $v;
        } else {
            $std->pFCP = $p;
            $std->vFCP = $v;
        }
    }
}
