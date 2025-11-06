<?php

namespace src\handlers\NFEs\UtilsNF\Tags;

use src\models\Tipo_pagamento;

class XMLtagsExtras
{
    /**
     * Monta a tag <infNFe> com informações básicas da nota.
     *
     * @return \stdClass
     */
    public static function informacaoNFE(): \stdClass
    {
        $std = new \stdClass();
        $std->versao = '4.00';
        $std->Id = null;        // ID será gerado automaticamente ou na montagem da chave
        $std->pk_nItem = '';     // Não é obrigatório para nossa geração

        return $std;
    }


    /**
     * Gera tag <transp> - Modalidade do frete
     */
    public static function transp(array $pedido): \stdClass
    {
        $std = new \stdClass();
        $std->modFrete = 9; // 9 = Sem frete
        return $std;
    }

    /**
     * Gera tag <pag> - Pagamento
     */
    public static function pag(float $vTroco = 0.00): \stdClass
    {
        $std = new \stdClass();
        $std->vTroco = number_format($vTroco, 2, '.', '');
        return $std;
    }

    /**
     * Gera tag <detPag> - Detalhamento do pagamento
     */
    public static function detPag(array $pedido): \stdClass
    {
        $std = new \stdClass();
        $tPag = $pedido['pagamento']['pag'] ?? 99;
        $std->tPag = str_pad((string)$tPag, 2, '0', STR_PAD_LEFT);
        $std->vPag = number_format($pedido['total_pedido'] ?? 0, 2, '.', '');
        
        if (
                strpos($pedido['pagamento']['desc'],'Cartão de Crédito') !== false 
             || strpos($pedido['pagamento']['desc'],'Cartão de Débito') !== false
        ) {
          $std->tBand = '99';
          $std->cAut = $pedido['pagamento']['cAut'] ?? '';
          $std->tpIntegra = 2;
        }

        if ((int)$tPag === 99) {
            $std->xPag = $pedido['pagamento']['desc'] ?? 'Outros';
        }

        return $std;
    }


    /**
     * Gera tag <infAdic> - Informações adicionais
     */
    public static function infAdic(array $pedido): \stdClass
    {
        $std = new \stdClass();
        $std->infCpl = $pedido['obs'] ?? '';

        return $std;
    }

    /**
     * Gera tag <infRespTec> - Responsável técnico
     */
    public static function infRespTec(): \stdClass
    {
        $std = new \stdClass();
        $std->CNPJ = '37598817000113';
        $std->xContato = 'JZ TECH LTDA';
        $std->email = 'jz.tech.digital@gmail.com';
        $std->fone = '44997633866';
        $std->idCSRT = '1';
        $std->hashCSRT = '8D1NK1357Q165WBN51D0RZSM8UYSDK67OCBF';

        return $std;
    }
}
