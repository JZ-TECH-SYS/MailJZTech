<?php

/**
 * Classe helper para gerenciar Relatórios Financeiros no sistema
 * 
 * Esta classe fornece métodos para gerar relatórios combinados de vendas e despesas.
 * 
 * Autor: AI Assistant
 * Data de Início: 01/10/2025
 */

namespace src\handlers;

use core\Database;
use src\handlers\Relatorio;
use src\handlers\Despesas;
use Exception;

class RelatorioFinanceiro
{
    /**
     * Obtém relatório financeiro consolidado (vendas x despesas) por período
     * 
     * @param int $idempresa O ID da empresa
     * @param string $dataini Data inicial (YYYY-MM-DD)
     * @param string $datafim Data final (YYYY-MM-DD)
     * @return array Um array com os dados consolidados do relatório financeiro
     */
    public static function getRelatorioFinanceiro($idempresa, $dataini, $datafim)
    {
        // Obter dados de vendas diárias
    $vendasDiarias = self::getVendasDiariasPorPeriodo($idempresa, $dataini, $datafim);
        
        // Obter dados de despesas diárias
    $despesasDiarias = Despesas::getDespesasDiarias($idempresa, $dataini, $datafim);

    // Mapear IDs brutos por dia para drill-down (pedidos / despesas)
    $idsPedidosPorDia = self::getIdsPedidosPorDia($idempresa, $dataini, $datafim);
    $idsDespesasPorDia = self::getIdsDespesasPorDia($idempresa, $dataini, $datafim);
        
        // Obter despesas por categoria
        $despesasPorCategoria = Despesas::getDespesasPorCategoria($idempresa, $dataini, $datafim);
        
        // Combinar dados dia a dia
    $relatorioComparado = self::combinarVendasDespesas($vendasDiarias, $despesasDiarias, $dataini, $datafim, $idsPedidosPorDia, $idsDespesasPorDia);
        
        // Calcular totais consolidados com conversão segura
        $totalVendas = array_sum(array_map('floatval', array_column($vendasDiarias, 'total_vendas')));
        $totalDespesas = array_sum(array_map('floatval', array_column($despesasDiarias, 'total_despesas')));
        $resultadoLiquido = $totalVendas - $totalDespesas;
        $qtdVendas = array_sum(array_map('intval', array_column($vendasDiarias, 'quantidade_vendas')));
        $qtdDespesas = array_sum(array_map('intval', array_column($despesasDiarias, 'quantidade_despesas')));
        $ticketMedio = $qtdVendas > 0 ? $totalVendas / $qtdVendas : 0;
        
        return [
            'periodo' => [
                'data_inicio' => $dataini,
                'data_fim' => $datafim
            ],
            'consolidado' => [
                'total_vendas' => round($totalVendas, 2),
                'total_despesas' => round($totalDespesas, 2),
                'resultado_liquido' => round($resultadoLiquido, 2),
                'margem_liquida' => $totalVendas > 0 ? round(($resultadoLiquido / $totalVendas) * 100, 2) : 0,
                'quantidade_vendas' => $qtdVendas,
                'quantidade_despesas' => $qtdDespesas,
                'ticket_medio' => round($ticketMedio, 2),
                'roi' => $totalDespesas > 0 ? round(($resultadoLiquido / $totalDespesas) * 100, 2) : 0
            ],
            'diario' => $relatorioComparado,
            'despesas_categoria' => $despesasPorCategoria
        ];
    }
    
    /**
     * Obtém vendas diárias simplificadas para o relatório financeiro
     * 
     * @param int $idempresa O ID da empresa
     * @param string $dataini Data inicial (YYYY-MM-DD)
     * @param string $datafim Data final (YYYY-MM-DD)
     * @return array Um array com as vendas agrupadas por dia
     */
    private static function getVendasDiariasPorPeriodo($idempresa, $dataini, $datafim)
    {
        $query = "
            SELECT 
                DATE(COALESCE(pv.data_baixa, pv.data_pedido)) as dia,
                SUM(COALESCE(pv.total_pedido, 0)) as total_vendas,
                COUNT(*) as quantidade_vendas,
                AVG(COALESCE(pv.total_pedido, 0)) as ticket_medio
            FROM pedido_venda pv
            WHERE pv.idempresa = :idempresa 
                AND DATE(COALESCE(pv.data_baixa, pv.data_pedido)) BETWEEN :dataini AND :datafim
                AND pv.idsituacao_pedido_venda = 2
                AND pv.total_pedido > 0
            GROUP BY DATE(COALESCE(pv.data_baixa, pv.data_pedido))
            ORDER BY dia ASC
        ";
        
        $db = Database::getInstance();
        $stmt = $db->prepare($query);
        $stmt->bindParam(':idempresa', $idempresa, \PDO::PARAM_INT);
        $stmt->bindParam(':dataini', $dataini, \PDO::PARAM_STR);
        $stmt->bindParam(':datafim', $datafim, \PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Combina dados de vendas e despesas por dia
     * 
     * @param array $vendasDiarias Array com vendas por dia
     * @param array $despesasDiarias Array com despesas por dia
     * @param string $dataini Data inicial
     * @param string $datafim Data final
     * @return array Array combinado com dados de cada dia
     */
    private static function combinarVendasDespesas($vendasDiarias, $despesasDiarias, $dataini, $datafim, $idsPedidosPorDia = [], $idsDespesasPorDia = [])
    {
        $resultado = [];
        
        // Converter arrays para associativos usando dia como chave
        $vendasPorDia = [];
        foreach ($vendasDiarias as $venda) {
            $vendasPorDia[$venda['dia']] = $venda;
        }
        
        $despesasPorDia = [];
        foreach ($despesasDiarias as $despesa) {
            $despesasPorDia[$despesa['dia']] = $despesa;
        }
        
        // Gerar todos os dias do período
        $dataAtual = new \DateTime($dataini);
        $dataFinal = new \DateTime($datafim);
        
        while ($dataAtual <= $dataFinal) {
            $diaStr = $dataAtual->format('Y-m-d');
            
            $vendas = isset($vendasPorDia[$diaStr]) ? floatval($vendasPorDia[$diaStr]['total_vendas']) : 0;
            $despesas = isset($despesasPorDia[$diaStr]) ? floatval($despesasPorDia[$diaStr]['total_despesas']) : 0;
            $resultado_dia = $vendas - $despesas;
            $qtdVendas = isset($vendasPorDia[$diaStr]) ? intval($vendasPorDia[$diaStr]['quantidade_vendas']) : 0;
            $qtdDespesas = isset($despesasPorDia[$diaStr]) ? intval($despesasPorDia[$diaStr]['quantidade_despesas']) : 0;
            $ticketMedioDia = $qtdVendas > 0 ? $vendas / $qtdVendas : 0;
            
            $idsPedidos = isset($idsPedidosPorDia[$diaStr]) ? $idsPedidosPorDia[$diaStr] : [];
            $idsDespesas = isset($idsDespesasPorDia[$diaStr]) ? $idsDespesasPorDia[$diaStr] : [];

            $resultado[] = [
                'dia' => $diaStr,
                'dia_semana' => $dataAtual->format('N'), // 1=segunda, 7=domingo
                'nome_dia' => self::traduzirDiaSemana($dataAtual->format('l')), // nome do dia em português
                'vendas' => round($vendas, 2),
                'despesas' => round($despesas, 2),
                'resultado' => round($resultado_dia, 2),
                'qtd_vendas' => $qtdVendas,
                'qtd_despesas' => $qtdDespesas,
                'ticket_medio_dia' => round($ticketMedioDia, 2),
                'margem_dia' => $vendas > 0 ? round(($resultado_dia / $vendas) * 100, 2) : 0,
                'ids_pedidos' => implode(',', $idsPedidos),
                'ids_despesas' => implode(',', $idsDespesas)
            ];
            
            $dataAtual->add(new \DateInterval('P1D'));
        }
        
        return $resultado;
    }

    /**
     * Retorna IDs de pedidos por dia para drill-down
     */
    private static function getIdsPedidosPorDia($idempresa, $dataini, $datafim)
    {
        $sql = "
            SELECT DATE(COALESCE(pv.data_baixa, pv.data_pedido)) AS dia, pv.idpedidovenda
            FROM pedido_venda pv
            WHERE pv.idempresa = :idempresa
              AND DATE(COALESCE(pv.data_baixa, pv.data_pedido)) BETWEEN :dataini AND :datafim
              AND pv.idsituacao_pedido_venda = 2
        ";
        $db = Database::getInstance();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':idempresa', $idempresa, \PDO::PARAM_INT);
        $stmt->bindParam(':dataini', $dataini, \PDO::PARAM_STR);
        $stmt->bindParam(':datafim', $datafim, \PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $out[$r['dia']][] = $r['idpedidovenda'];
        }
        return $out;
    }

    /**
     * Retorna IDs de despesas por dia para drill-down
     */
    private static function getIdsDespesasPorDia($idempresa, $dataini, $datafim)
    {
        $sql = "
            SELECT DATE(d.datahora) AS dia, d.iddespesas
            FROM despesas d
            WHERE d.idempresa = :idempresa
              AND DATE(d.datahora) BETWEEN :dataini AND :datafim
        ";
        $db = Database::getInstance();
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':idempresa', $idempresa, \PDO::PARAM_INT);
        $stmt->bindParam(':dataini', $dataini, \PDO::PARAM_STR);
        $stmt->bindParam(':datafim', $datafim, \PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $out = [];
        foreach ($rows as $r) {
            $out[$r['dia']][] = $r['iddespesas'];
        }
        return $out;
    }
    
    /**
     * Traduz nomes dos dias da semana do inglês para português
     * 
     * @param string $dayName Nome do dia em inglês
     * @return string Nome do dia em português
     */
    private static function traduzirDiaSemana($dayName)
    {
        $traducoes = [
            'Monday' => 'Segunda-feira',
            'Tuesday' => 'Terça-feira',
            'Wednesday' => 'Quarta-feira',
            'Thursday' => 'Quinta-feira',
            'Friday' => 'Sexta-feira',
            'Saturday' => 'Sábado',
            'Sunday' => 'Domingo'
        ];
        
        return isset($traducoes[$dayName]) ? $traducoes[$dayName] : $dayName;
    }
    
    /**
     * Obtém insights e análises do período
     * 
     * @param int $idempresa O ID da empresa
     * @param string $dataini Data inicial (YYYY-MM-DD)
     * @param string $datafim Data final (YYYY-MM-DD)
     * @return array Array com insights do período
     */
    public static function getInsightsFinanceiros($idempresa, $dataini, $datafim)
    {
        $relatorio = self::getRelatorioFinanceiro($idempresa, $dataini, $datafim);
        
        $insights = [];
        
        // Melhor dia de vendas
        $melhorDiaVendas = null;
        $maiorVenda = 0;
        foreach ($relatorio['diario'] as $dia) {
            if ($dia['vendas'] > $maiorVenda) {
                $maiorVenda = $dia['vendas'];
                $melhorDiaVendas = $dia;
            }
        }
        
        // Pior dia de resultado
        $piorDiaResultado = null;
        $menorResultado = PHP_FLOAT_MAX;
        foreach ($relatorio['diario'] as $dia) {
            if ($dia['resultado'] < $menorResultado) {
                $menorResultado = $dia['resultado'];
                $piorDiaResultado = $dia;
            }
        }
        
        // Categoria que mais gasta
        $categoriaMaxGasto = null;
        $maxGasto = 0;
        foreach ($relatorio['despesas_categoria'] as $categoria) {
            if ($categoria['total_valor'] > $maxGasto) {
                $maxGasto = $categoria['total_valor'];
                $categoriaMaxGasto = $categoria;
            }
        }
        
        return [
            'melhor_dia_vendas' => $melhorDiaVendas,
            'pior_dia_resultado' => $piorDiaResultado,
            'categoria_max_gasto' => $categoriaMaxGasto,
            'dias_lucrativos' => count(array_filter($relatorio['diario'], function($dia) { return $dia['resultado'] > 0; })),
            'dias_prejuizo' => count(array_filter($relatorio['diario'], function($dia) { return $dia['resultado'] < 0; })),
            'total_dias' => count($relatorio['diario'])
        ];
    }

    /**
     * Retorna detalhe completo de um dia: pedidos e despesas
     */
    public static function getDetalheDia($idempresa, $dia)
    {
        $db = Database::getInstance();

        // Pedidos do dia
        $sqlPedidos = "
            SELECT pv.idpedidovenda, COALESCE(pv.data_baixa, pv.data_pedido) as data, pv.total_pedido, '' as obs
            FROM pedido_venda pv
            WHERE pv.idempresa = :idempresa
              AND DATE(COALESCE(pv.data_baixa, pv.data_pedido)) = :dia
              AND pv.idsituacao_pedido_venda = 2
            ORDER BY COALESCE(pv.data_baixa, pv.data_pedido) ASC
        ";
        $stmt = $db->prepare($sqlPedidos);
        $stmt->bindParam(':idempresa', $idempresa, \PDO::PARAM_INT);
        $stmt->bindParam(':dia', $dia, \PDO::PARAM_STR);
        $stmt->execute();
        $pedidos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Despesas do dia
        $sqlDespesas = "
            SELECT d.iddespesas, d.datahora, d.valor, d.descricao, d.tipo_despesa
            FROM despesas d
            WHERE d.idempresa = :idempresa
              AND DATE(d.datahora) = :dia
            ORDER BY d.datahora ASC
        ";
        $stmt2 = $db->prepare($sqlDespesas);
        $stmt2->bindParam(':idempresa', $idempresa, \PDO::PARAM_INT);
        $stmt2->bindParam(':dia', $dia, \PDO::PARAM_STR);
        $stmt2->execute();
        $despesas = $stmt2->fetchAll(\PDO::FETCH_ASSOC);

        // Totais
        $totalPedidos = array_sum(array_map(fn($p) => (float)$p['total_pedido'], $pedidos));
        $totalDespesas = array_sum(array_map(fn($d) => (float)$d['valor'], $despesas));

        return [
            'dia' => $dia,
            'total_pedidos' => round($totalPedidos,2),
            'total_despesas' => round($totalDespesas,2),
            'resultado' => round($totalPedidos - $totalDespesas,2),
            'pedidos' => $pedidos,
            'despesas' => $despesas
        ];
    }
}
