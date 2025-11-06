<?php

/**
 * Classe responsável pelo controle da página inicial
 * Autor: Joaosn
 * Data de início: 23/05/2023
 */

namespace src\controllers;

use \core\Controller as ctrl;
use src\handlers\Relatorio;

class RelatorioController extends ctrl
{
    /**
     * Relatório de produtos com ranking por período
     * @param $args['idempresa'] - ID da empresa
     * @param $args['dataini'] - Data inicial (YYYY-MM-DD)
     * @param $args['datafim'] - Data final (YYYY-MM-DD)
     */
    public function getRelatorioProdutos($args)
    {
       $rel = Relatorio::getProdutosRank($args['idempresa'], $args['dataini'], $args['datafim']);
       if(!$rel){
           ctrl::response('Nenhum produto encontrado', 404);
       }
       ctrl::response($rel, 200);
    }

    /**
     * pegas todas venda feitas no dia
     */
    public function getVendas($args){
        $rel = Relatorio::getVendas($args['idempresa'], $args['dataini'], $args['datafim'], $args['idsituacao_pedido_venda']);
       if(!$rel){
           ctrl::response('Nenhuma venda encontrado no perido', 404);
       }
       ctrl::response($rel, 200);
    }

    /**
     * pegas todas venda feitas no dia
     */
    public function getVendasDiario($args){
        $rel = Relatorio::getVendasDiario($args['idempresa'], $args['dataini'], $args['datafim']);
       if(!$rel){
           ctrl::response('Nenhuma venda encontrado no perido', 404);
       }
       ctrl::response($rel, 200);
    }

    /**
     * pega todas infos do dashbord
     *
     */
    public function getDash($args){
       $rel = Relatorio::getDash($args['idempresa']);
       if(!$rel){
           ctrl::response('Nenhuma venda encontrado no perido', 404);
       }
       ctrl::response($rel, 200);
    }

    /**
     * Relatório comparativo entre pedidos e notas fiscais
     */
    public function relatorioPedidosNotas($args){
        $rel = Relatorio::relatorioPedidosNotas($args['idempresa'], $args['dataini'], $args['datafim']);
        if(!$rel){
            ctrl::response('Nenhum dado encontrado no período', 404);
        }
        ctrl::response($rel, 200);
    }
}
