<?php

/**
 * Gera ESC/POS RAW para Bematec MP-4200 TH 80 mm
 * (substitui o antigo MontarPDF).
 */


namespace src\handlers;

use Exception;
use src\handlers\PedidoVenda;
use src\models\Categoria;
use src\models\Empresa;

class MontarEscpos
{
    /* bytes de atalho */
    private const ESC = "\x1B";
    private const GS  = "\x1D";
    private const LF  = "\x0A";

    private static function init()
    {
        return self::ESC . '@';
    }
    private static function align($n)
    {
        return self::ESC . 'a' . chr($n);
    } // 0-L 1-C 2-R
    private static function bold($on)
    {
        return self::ESC . 'E' . ($on ? "\x01" : "\x00");
    }
    private static function cut()
    {
        return self::GS . 'V' . "\x41" . "\x03";
    }
    private static function sep()
    {
        return self::LF . str_repeat('-', 32) . self::LF;
    }

    /* ========= builders ========= */

    public static function item(int $idemp, int $idpv, int $iditem): string
    {
        $pedido = PedidoVenda::getPedidoVendas(null, $idemp, $idpv, null, null)[0];
        //print_r($pedido);die;
        $item   = array_values(array_filter(
            $pedido['itens'],
            fn($i) => $i['idpedido_item'] === $iditem
        ))[0] ?? null;
        if (!$item) throw new Exception('Item não encontrado');

        $raw  = self::init() . self::align(1) . self::bold(true)
            . "ITEM" . self::LF
            . self::bold(false) . self::align(0)
            . "{$item['quantidade']} x {$item['nome']}" . self::LF;

        if ($item['obs'])         $raw .= "Obs: {$item['obs']}" . self::LF;
        foreach ($item['acrescimos'] ?? [] as $a)
            $raw .= "  + {$a['quantidade']} {$a['nome']}" . self::LF;

        return $raw . self::sep() . self::cut();
    }

    public static function pedido(int $idemp, int $idpv, bool $pendentes = false): array
    {
        $pedido = PedidoVenda::getPedidoVendas(null, $idemp, $idpv, null, null)[0];
        $cliente = $pedido['nome'] ?? 'Cliente';
        $mesa = $pedido['idmesa'] ?? '-';
        $lista = [];

        /* agrupa itens por categoria */
        $porCat = [];
        foreach ($pedido['itens'] as $i) {
            if ($pendentes && $i['impresso']) continue;
            $porCat[$i['idcategoria']][] = $i;
        }

        foreach ($porCat as $idcat => $itens) {
            $cat = Categoria::select()
                ->where('idcategoria', $idcat)
                ->where('idempresa', $idemp)
                ->where('imprimir', 1)->one();
            if (!$cat) continue;

            $raw = self::init()
                . self::align(1) . self::bold(true)
                . "PEDIDO DE(A): " . strtoupper($cliente) . self::LF
                . "TIPO: " . strtoupper($cat['descricao']) . self::LF
                . self::bold(false) . self::align(0)
                . "MESA: {$mesa}" . self::LF
                . "PEDIDO Nº: {$idpv}" . self::sep();

            foreach ($itens as $item) {
                $raw .= "{$item['quantidade']} - {$item['nome']}" . self::LF;

                if (!empty($item['obs'])) {
                    $raw .= "Obs: {$item['obs']}" . self::LF;
                }

                if (!empty($item['acrescimos'])) {
                    $raw .= "Adicionais:" . self::LF;
                    foreach ($item['acrescimos'] as $a) {
                        $raw .= "-> {$a['quantidade']} - {$a['nome']}" . self::LF;
                    }
                }

                $raw .= self::sep();
            }

            $raw .= self::cut();
            $reps = max(1, $cat['quantidade_impressao']);
            while ($reps--) $lista[] = $raw;
        }

        return $lista;
    }


    public static function recibo(int $idemp, int $idpv): string
    {
        $ped = PedidoVenda::getPedidoVendas(null, $idemp, $idpv, null, null)[0];
        $emp = Empresa::select()->where('idempresa', $idemp)->one();
        $cliente = $ped['nome'] ?? 'Cliente';
        $mesa = $ped['idmesa'] ?? '-';
        $forma = $ped['meio_pagamento'] ?? '---';
        $total = number_format($ped['total_pedido'], 2, ',', '.');

        $cmdExpand = self::ESC . "!" . chr(0x20); // expandida
        $cmdNormal = self::ESC . "!" . chr(0x00); // padrão

        $raw = self::init()
            . self::align(1) . self::bold(true) . $cmdExpand
            . strtoupper($emp['nome']) . self::LF
            . "CNPJ: {$emp['cnpj']}" . self::LF
            . "MESA: {$mesa}   PEDIDO Nº: {$idpv}" . self::LF
            . self::sep();

        foreach ($ped['itens'] as $i) {
            $qtd = $i['quantidade'];
            $nome = strtoupper($i['nome']);
            $valor = number_format($qtd * $i['preco'], 2, ',', '.');

            $raw .= "{$qtd} x {$nome}" . self::align(2) . "R$ {$valor}" . self::LF . self::align(0);

            if (!empty($i['obs'])) {
                $raw .= "Obs: {$i['obs']}" . self::LF;
            }

            foreach ($i['acrescimos'] ?? [] as $a) {
                $raw .= "+ {$a['quantidade']} x {$a['nome']}" . self::LF;
            }

            $raw .= self::LF;
        }

        $raw .= self::sep()
            . "TOTAL: R$ {$total}" . self::LF
            . "Forma Pgto: {$forma}" . self::LF
            . "Cliente: {$cliente}" . self::LF
            . self::LF . "Obrigado e volte sempre!"
            . self::cut();

        return $raw;
    }
}
