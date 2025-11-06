<?php

/**
 * Classe RelatorioFinanceiroController
 * Controlador de Relatórios Financeiros responsável por gerenciar operações relacionadas a relatórios de vendas x despesas.
 * 
 * @author AI Assistant
 * @since 01/10/2025
 */

namespace src\controllers;

use \core\Controller as ctrl;
use Exception;
use \src\handlers\RelatorioFinanceiro as RelatorioFinanceiroHelp;

class RelatorioFinanceiroController extends ctrl
{
    /**
     * Retorna relatório financeiro consolidado (vendas x despesas) por período
     * 
     * @param array $args Array contendo o ID da empresa, data inicial e final.
     */
    public function getRelatorioFinanceiro($args)
    {
        try {
            $relatorio = RelatorioFinanceiroHelp::getRelatorioFinanceiro(
                $args['idempresa'], 
                $args['dataini'], 
                $args['datafim']
            );
            
            if (!$relatorio) {
                ctrl::response('Nenhum dado encontrado no período', 404);
            }
            
            ctrl::response($relatorio, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Detalhe de um dia específico
     */
    public function getRelatorioFinanceiroDia($args)
    {
        try {
            $detalhe = RelatorioFinanceiroHelp::getDetalheDia(
                $args['idempresa'],
                $args['dia']
            );
            ctrl::response($detalhe, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
}
