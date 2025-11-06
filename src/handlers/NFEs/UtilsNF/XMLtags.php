<?php

/**
 * XMLtags - Controlador principal de geração de tags da NF-e (4.00)
 * ----------------------------------------------------------------
 * Este arquivo centraliza a chamada dos módulos de tags
 * (Ide, Emitente, Destinatário, Produtos, Impostos, Transporte, Pagamento, Extras).
 *
 * Funções:
 *  - Orquestrar chamadas para os arquivos XMLtagsXxx.php
 *  - Manter retrocompatibilidade para EmitirNota.php
 *
 * Organização:
 *  - Pasta /Tags contem os arquivos específicos por grupo de tag.
 *
 */

namespace src\handlers\NFEs\UtilsNF;

use src\handlers\NFEs\UtilsNF\Tags\XMLtagsIde;
use src\handlers\NFEs\UtilsNF\Tags\XMLtagsEmit;
use src\handlers\NFEs\UtilsNF\Tags\XMLtagsDest;
use src\handlers\NFEs\UtilsNF\Tags\XMLtagsImpostos;
use src\handlers\NFEs\UtilsNF\Tags\XMLtagsExtras;
use src\handlers\NFEs\UtilsNF\Tags\XMLtagsProdutos;

class XMLtags
{
    /* ================================================================
       0. Cabeçalho da NF-e (infNFe)
       ================================================================ */
    public static function informacaoNFE()
    {
        return XMLtagsExtras::informacaoNFE();
    }

    /* ================================================================
       1. Identificação da NF-e (ide)
       ================================================================ */
    public static function ide(array $pedido, int $idpedidovenda, int $idempresa, int $tipoNota = 65)
    {
        return XMLtagsIde::ide($pedido, $idpedidovenda, $idempresa, $tipoNota);
    }

    /* ================================================================
       2. Emitente (emit)
       ================================================================ */
    public static function emit(array $pedido, int $codigoRegimeTributario)
    {
        return XMLtagsEmit::emit($pedido, $codigoRegimeTributario);
    }

    public static function enderEmit(array $pedido)
    {
        return XMLtagsEmit::enderEmit($pedido);
    }

    /* ================================================================
       3. Destinatário (dest)
       ================================================================ */
    public static function dest(array $pedido)
    {
        return XMLtagsDest::dest($pedido);
    }

    public static function enderDest(array $pedido)
    {
        return XMLtagsDest::enderDest($pedido);
    }

    /* ================================================================
       4. Produtos (prod + imposto)
       ================================================================ */
    public static function prod(array $item, int $itemNumber)
    {
        return XMLtagsProdutos::prod($item, $itemNumber);
    }

    public static function imposto(int $itemNumber)
    {
        return XMLtagsImpostos::imposto($itemNumber);
    }

    /* ================================================================
       5. Impostos (ICMS, PIS, COFINS, IPI, ISSQN)
       ================================================================ */
    public static function buildICMS(array $imp, float $vProd, int $itemNumber, int $crt)
    {
        return XMLtagsImpostos::buildICMS($imp, $vProd, $itemNumber, $crt);
    }

    public static function buildPIS(array $imp, float $vProd, int $crt, int $itemNumber)
    {
        return XMLtagsImpostos::buildPIS($imp, $vProd, $crt, $itemNumber);
    }

    public static function buildCOFINS(array $imp, float $vProd, int $crt, int $itemNumber)
    {
        return XMLtagsImpostos::buildCOFINS($imp, $vProd, $crt, $itemNumber);
    }

    public static function buildIPI(array $imp, float $vProd, int $itemNumber)
    {
        return XMLtagsImpostos::buildIPI($imp, $vProd, $itemNumber);
    }

    public static function buildISSQN(array $imp, float $vServ, int $itemNumber)
    {
        return XMLtagsImpostos::buildISSQN($imp, $vServ, $itemNumber);
    }

    /* ================================================================
       6. Transporte (transp)
       ================================================================ */
    public static function transp(array $pedido)
    {
        return XMLtagsExtras::transp($pedido);
    }

    /* ================================================================
       7. Pagamento (pag + detPag)
       ================================================================ */
    public static function pag(string $vTroco = '0.00')
    {
        return XMLtagsExtras::pag($vTroco);
    }

    public static function detPag(array $pedido)
    {
        return XMLtagsExtras::detPag($pedido);
    }

    /* ================================================================
       8. Totais (ICMSTot)
       ================================================================ */
    public static function ICMSTot(array $pedido, float $totalICMS, float $totalPIS, float $totalCOFINS)
    {
        return XMLtagsImpostos::ICMSTot($pedido, $totalICMS, $totalPIS, $totalCOFINS);
    }

    /* ================================================================
       9. Extras
       ================================================================ */
    public static function infAdic(array $pedido)
    {
        return XMLtagsExtras::infAdic($pedido);
    }

    public static function infRespTec()
    {
        return XMLtagsExtras::infRespTec();
    }
}
