<?php

namespace src\handlers\NFEs\UtilsNF\Tags;

use src\Config;
use core\Controller as ctrl;
use src\handlers\NFEs\UtilsNF\XMLHelper;

class XMLtagsIde
{
    /**
     * Gera a tag <ide> - Identificação da NF-e
     */
    public static function ide(array $pedido, int $idpedidovenda, int $idempresa, int $tipoNota = 65): \stdClass
    {
        $std = new \stdClass();
        $emitente = $pedido['emitente'] ?? [];

        $std->cUF = $emitente['coduf'] ?? 41;
        $std->cNF = str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        $std->natOp = 'VENDA';
        $std->mod = $tipoNota; // 55 = NF-e / 65 = NFC-e
        $std->serie = 1;
        $std->nNF = XMLHelper::getNumeroNota(ctrl::getEmpresa(),$tipoNota);
        $std->dhEmi = date('c');
        $std->tpNF = 1; // 1-Saída
        $std->idDest = 1; // 1-Operação interna
        $std->cMunFG = $emitente['codmunicipio'] ?? '4111555';
        $std->tpImp = ($std->mod == 65) ? 4 : 1;
        $std->tpEmis = 1; // 1-Normal
        $std->tpAmb = Config::SEFAZ;  // 2-Homologação
        $std->finNFe = 1; // 1-Normal
        $std->indFinal = 1; // 1-Consumidor Final
        $std->indPres = 1; // 1-Operação presencial
        $std->procEmi = 0;
        $std->verProc = 1; // versão do seu processo emissor (pode ser fixo)

        return $std;
    }
}
