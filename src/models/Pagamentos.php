<?php

namespace src\models;

use \core\Model;

/**
 * Classe modelo para a tabela 'pagamentos' do banco de dados.
 */
class Pagamentos extends Model
{
    public static function getPagamentoNFE($idempresa, $idpedidovenda)
    {
        $pagamentoTop = self::select()
            ->where('idempresa', $idempresa)
            ->where('idpedidovenda',  $idpedidovenda)
            ->orderBy('valor', 'desc')
            ->one();

        if (!$pagamentoTop) {
            return ['pag' => 99, 'desc' => 'Outros']; // sem pagamento
        }

        $tipo = Tipo_pagamento::select()
            ->where('idempresa', $idempresa)
            ->where('idtipopagamento', $pagamentoTop['idtipopagamento'])
            ->one();

        $d = isset($tipo['descricao']) ? mb_strtolower(trim($tipo['descricao'])) : '';

        // default
        $tPag = 99;
        $tDesc = 'Outros';

        switch (true) {
            case $d === 'dinheiro':
                $tPag = 1;
                $tDesc = 'Dinheiro';
                break;

            case $d === 'cheque' || str_contains($d, 'cheque'):
                $tPag = 2;
                $tDesc = 'Cheque';
                break;

            case str_contains($d, 'crédito') || str_contains($d, 'credito'):
                $tPag = 3;
                $tDesc = 'Cartão de Crédito';
                break;

            case str_contains($d, 'débito') || str_contains($d, 'debito'):
                $tPag = 4;
                $tDesc = 'Cartão de Débito';
                break;

            case str_contains($d, 'pix'):
                // se um dia detectar txid/e2eid, troque para 17 – PIX Dinâmico
                $tPag = 17;
                $tDesc = 'Pagamento Instantâneo (PIX) - Estático';
                break;

            case str_contains($d, 'cooper') && str_contains($d, 'card'):
            case str_contains($d, 'credi') || str_contains($d, 'private'):
                $tPag = 5;
                $tDesc = 'Cartão da Loja (Private Label), Crediário Digital, Outros Crediários';
                break;

            case str_contains($d, 'ticket') || str_contains($d, 'aliment'):
                $tPag = 10;
                $tDesc = 'Vale Alimentação';
                break;

            case str_contains($d, 'refei'):
                $tPag = 11;
                $tDesc = 'Vale Refeição';
                break;

            case str_contains($d, 'presente'):
                $tPag = 12;
                $tDesc = 'Vale Presente';
                break;

            case str_contains($d, 'combust'):
                $tPag = 13;
                $tDesc = 'Vale Combustível';
                break;

            case str_contains($d, 'duplicata'):
                $tPag = 14;
                $tDesc = 'Duplicata Mercantil';
                break;

            case str_contains($d, 'boleto'):
                $tPag = 15;
                $tDesc = 'Boleto Bancário';
                break;

            case str_contains($d, 'depósito') || str_contains($d, 'deposito') || str_contains($d, 'transfer'):
                $tPag = 16;
                $tDesc = 'Depósito Bancário';
                break;

            case str_contains($d, 'carteira') || str_contains($d, 'wallet'):
                $tPag = 18;
                $tDesc = 'Transferência bancária, Carteira Digital';
                break;

            case str_contains($d, 'crédito em loja') || str_contains($d, 'credito em loja') || str_contains($d, 'vale/troca') || str_contains($d, 'troca'):
                $tPag = 21;
                $tDesc = 'Crédito em Loja';
                break;

            case str_contains($d, 'pagamento eletr') || str_contains($d, 'eletrôn') || str_contains($d, 'eletron'):
                $tPag = 22;
                $tDesc = 'Pagamento Eletrônico – Meio não informado';
                break;
        }

        return [
            'pag'  => $tPag,   // use str_pad no momento de montar o XML
            'desc' => $tDesc,  // só vai no XML quando tPag = 99
            'idtipopagamento' => $pagamentoTop['idtipopagamento'] ?? null,
            'cAut' => $pagamentoTop['cAut'] ?? null // código de autorização da operadora, se houver
        ];
    }
}
