<?php
namespace src\handlers\NFEs\UtilsNF\Tags;

class XMLtagsProdutos
{
    /**
     * Constrói a tag <prod>
     *
     * @param array $item  Dados do item (deve conter 'preco', 'quantidade', 'idproduto', 'nome' e opcionalmente 'imposto')
     * @param int   $nItem Número do item na NFe
     * @return \stdClass
     */
    public static function prod(array $item, int $nItem): \stdClass
    {
        // Preço unitário e quantidade
        $preco = isset($item['preco']) ? (float)$item['preco'] : 0.0;
        $qtd   = isset($item['quantidade']) ? (float)$item['quantidade'] : 0.0;
        $vProd = $preco * $qtd;

        // Pega o primeiro detalhe de imposto, se existir
        $firstImp = $item['imposto'][0] ?? [];

        // Só atribui NCM/CFOP se vier no array de imposto
        $ncm  = $firstImp['NCM']  ?? null;
        $cfop = $firstImp['CFOP'] ?? null;

        $std = new \stdClass();
        $std->item     = $nItem;
        $std->cProd    = $item['idproduto'] ?? null;
        $std->cEAN     = $item['gtin'] ?? 'SEM GTIN';
        $std->xProd    = $item['nome'] ?? '';
        if ($ncm !== null) {
            $std->NCM = $ncm;
        }
        if ($cfop !== null) {
            $std->CFOP = $cfop;
        }
        $std->uCom     = 'UN';
        $std->qCom     = number_format($qtd,   4, '.', '');
        $std->vUnCom   = number_format($preco, 10, '.', '');
        $std->vProd    = number_format($vProd,  2, '.', '');
        $std->cEANTrib = 'SEM GTIN';
        $std->uTrib    = 'UN';
        $std->qTrib    = $std->qCom;
        $std->vUnTrib  = $std->vUnCom;
        $std->indTot   = '1'; // compõe totais no <ICMSTot>

        return $std;
    }
}
