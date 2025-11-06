<?php

namespace src\handlers;

use core\Database;
use Exception;

class RelatorioVendaCusto
{
    public static function getRelatorioVendaCusto(int $idempresa, string $dataini, string $datafim): array
    {
        $vendasDiarias = self::getVendasDiariasPorPeriodo($idempresa, $dataini, $datafim);
        $custosDiarios = self::getCustosDiariosPorPeriodo($idempresa, $dataini, $datafim);
        $idsPedidosPorDia = self::getIdsPedidosPorDia($idempresa, $dataini, $datafim);

        $relatorioDiario = self::combinarVendasCustos(
            $vendasDiarias,
            $custosDiarios,
            $dataini,
            $datafim,
            $idsPedidosPorDia
        );

        $totalVendas = array_sum(array_map('floatval', array_column($vendasDiarias, 'total_vendas')));
        $totalCustos = array_sum(array_map('floatval', array_column($custosDiarios, 'total_custos')));
        $resultadoBruto = $totalVendas - $totalCustos;
        $quantidadeVendas = array_sum(array_map('intval', array_column($vendasDiarias, 'quantidade_vendas')));

        $ticketMedio = $quantidadeVendas > 0 ? $totalVendas / $quantidadeVendas : 0;
        $custoMedio = $quantidadeVendas > 0 ? $totalCustos / $quantidadeVendas : 0;
        $margemBruta = $totalVendas > 0 ? ($resultadoBruto / $totalVendas) * 100 : 0;

        return [
            'periodo' => [
                'data_inicio' => $dataini,
                'data_fim' => $datafim
            ],
            'consolidado' => [
                'total_vendas' => round($totalVendas, 2),
                'total_custos' => round($totalCustos, 2),
                'resultado_bruto' => round($resultadoBruto, 2),
                'margem_bruta' => round($margemBruta, 2),
                'quantidade_vendas' => $quantidadeVendas,
                'ticket_medio' => round($ticketMedio, 2),
                'custo_medio' => round($custoMedio, 2)
            ],
            'diario' => $relatorioDiario
        ];
    }

    public static function getDetalheDia(int $idempresa, string $dia): array
    {
        $db = Database::getInstance();

        $sqlPedidos = "
            SELECT 
                pv.idpedidovenda,
                COALESCE(pv.total_pedido, 0) AS total_pedido,
                COALESCE(pv.data_baixa, pv.data_pedido) AS data
            FROM pedido_venda pv
            WHERE pv.idempresa = :idempresa
              AND pv.idsituacao_pedido_venda = 2
              AND DATE(COALESCE(pv.data_baixa, pv.data_pedido)) = :dia
            ORDER BY COALESCE(pv.data_baixa, pv.data_pedido) ASC
        ";

        $stmtPedidos = $db->prepare($sqlPedidos);
        $stmtPedidos->bindValue(':idempresa', $idempresa, \PDO::PARAM_INT);
        $stmtPedidos->bindValue(':dia', $dia, \PDO::PARAM_STR);
        $stmtPedidos->execute();
        $pedidos = $stmtPedidos->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($pedidos)) {
            return [
                'dia' => $dia,
                'total_vendas' => 0,
                'total_custos' => 0,
                'resultado' => 0,
                'pedidos' => []
            ];
        }

        $sqlCustos = "
            SELECT 
                custos.idpedidovenda,
                SUM(custos.custo) AS total_custo
            FROM (
                SELECT 
                    pv.idpedidovenda,
                    SUM(pvi.quantidade * COALESCE(pvi.custo_unitario, prod.preco_custo, 0)) AS custo
                FROM pedido_venda pv
                INNER JOIN pedido_venda_item pvi
                    ON pvi.idempresa = pv.idempresa
                   AND pvi.idpedidovenda = pv.idpedidovenda
                LEFT JOIN produtos prod
                    ON prod.idempresa = pvi.idempresa
                   AND prod.idproduto = pvi.idproduto
                WHERE pv.idempresa = :idempresa
                  AND pv.idsituacao_pedido_venda = 2
                  AND DATE(COALESCE(pv.data_baixa, pv.data_pedido)) = :dia
                GROUP BY pv.idpedidovenda

                UNION ALL

                SELECT 
                    pv.idpedidovenda,
                    SUM(pvai.quantidade * COALESCE(pvai.custo_unitario, prod.preco_custo, 0)) AS custo
                FROM pedido_venda pv
                INNER JOIN pedido_venda_item_acrescimos pvai
                    ON pvai.idempresa = pv.idempresa
                   AND pvai.idpedidovenda = pv.idpedidovenda
                LEFT JOIN produtos prod
                    ON prod.idempresa = pvai.idempresa
                   AND prod.idproduto = pvai.idproduto
                WHERE pv.idempresa = :idempresa
                  AND pv.idsituacao_pedido_venda = 2
                  AND DATE(COALESCE(pv.data_baixa, pv.data_pedido)) = :dia
                GROUP BY pv.idpedidovenda
            ) custos
            GROUP BY custos.idpedidovenda
        ";

        $stmtCustos = $db->prepare($sqlCustos);
        $stmtCustos->bindValue(':idempresa', $idempresa, \PDO::PARAM_INT);
        $stmtCustos->bindValue(':dia', $dia, \PDO::PARAM_STR);
        $stmtCustos->execute();
        $custosPorPedido = $stmtCustos->fetchAll(\PDO::FETCH_KEY_PAIR);

        $pedidosDetalhados = array_map(function ($pedido) use ($custosPorPedido) {
            $custo = isset($custosPorPedido[$pedido['idpedidovenda']]) ? (float)$custosPorPedido[$pedido['idpedidovenda']] : 0.0;
            $totalPedido = (float)$pedido['total_pedido'];
            return [
                'idpedidovenda' => (int)$pedido['idpedidovenda'],
                'data' => $pedido['data'],
                'total_pedido' => round($totalPedido, 2),
                'custo_total' => round($custo, 2),
                'lucro' => round($totalPedido - $custo, 2)
            ];
        }, $pedidos);

        $totalPedidos = array_sum(array_map(fn ($pedido) => $pedido['total_pedido'], $pedidosDetalhados));
        $totalCustos = array_sum(array_map(fn ($pedido) => $pedido['custo_total'], $pedidosDetalhados));

        return [
            'dia' => $dia,
            'total_vendas' => round($totalPedidos, 2),
            'total_custos' => round($totalCustos, 2),
            'resultado' => round($totalPedidos - $totalCustos, 2),
            'pedidos' => $pedidosDetalhados
        ];
    }

    private static function getVendasDiariasPorPeriodo(int $idempresa, string $dataini, string $datafim): array
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

    private static function getCustosDiariosPorPeriodo(int $idempresa, string $dataini, string $datafim): array
    {
        $query = "
            SELECT 
                custos.dia,
                SUM(custos.total_custo) AS total_custos
            FROM (
                SELECT 
                    DATE(COALESCE(pv.data_baixa, pv.data_pedido)) AS dia,
                    SUM(pvi.quantidade * COALESCE(pvi.custo_unitario, prod.preco_custo, 0)) AS total_custo
                FROM pedido_venda pv
                INNER JOIN pedido_venda_item pvi
                    ON pvi.idempresa = pv.idempresa
                   AND pvi.idpedidovenda = pv.idpedidovenda
                LEFT JOIN produtos prod
                    ON prod.idempresa = pvi.idempresa
                   AND prod.idproduto = pvi.idproduto
                WHERE pv.idempresa = :idempresa
                  AND pv.idsituacao_pedido_venda = 2
                  AND DATE(COALESCE(pv.data_baixa, pv.data_pedido)) BETWEEN :dataini AND :datafim
                GROUP BY DATE(COALESCE(pv.data_baixa, pv.data_pedido))

                UNION ALL

                SELECT 
                    DATE(COALESCE(pv.data_baixa, pv.data_pedido)) AS dia,
                    SUM(pvai.quantidade * COALESCE(pvai.custo_unitario, prod.preco_custo, 0)) AS total_custo
                FROM pedido_venda pv
                INNER JOIN pedido_venda_item_acrescimos pvai
                    ON pvai.idempresa = pv.idempresa
                   AND pvai.idpedidovenda = pv.idpedidovenda
                LEFT JOIN produtos prod
                    ON prod.idempresa = pvai.idempresa
                   AND prod.idproduto = pvai.idproduto
                WHERE pv.idempresa = :idempresa
                  AND pv.idsituacao_pedido_venda = 2
                  AND DATE(COALESCE(pv.data_baixa, pv.data_pedido)) BETWEEN :dataini AND :datafim
                GROUP BY DATE(COALESCE(pv.data_baixa, pv.data_pedido))
            ) custos
            GROUP BY custos.dia
            ORDER BY custos.dia ASC
        ";

        $db = Database::getInstance();
        $stmt = $db->prepare($query);
        $stmt->bindParam(':idempresa', $idempresa, \PDO::PARAM_INT);
        $stmt->bindParam(':dataini', $dataini, \PDO::PARAM_STR);
        $stmt->bindParam(':datafim', $datafim, \PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private static function combinarVendasCustos(array $vendasDiarias, array $custosDiarios, string $dataini, string $datafim, array $idsPedidosPorDia = []): array
    {
        $vendasPorDia = [];
        foreach ($vendasDiarias as $venda) {
            $vendasPorDia[$venda['dia']] = $venda;
        }

        $custosPorDia = [];
        foreach ($custosDiarios as $custo) {
            $custosPorDia[$custo['dia']] = $custo;
        }

        $diario = [];
        $inicio = new \DateTime($dataini);
        $fim = new \DateTime($datafim);

        for ($data = clone $inicio; $data <= $fim; $data->modify('+1 day')) {
            $diaStr = $data->format('Y-m-d');
            $vendas = isset($vendasPorDia[$diaStr]) ? (float)$vendasPorDia[$diaStr]['total_vendas'] : 0.0;
            $custos = isset($custosPorDia[$diaStr]) ? (float)$custosPorDia[$diaStr]['total_custos'] : 0.0;
            $resultado = $vendas - $custos;
            $qtdVendas = isset($vendasPorDia[$diaStr]) ? (int)$vendasPorDia[$diaStr]['quantidade_vendas'] : 0;
            $ticketMedioDia = $qtdVendas > 0 ? $vendas / $qtdVendas : 0;
            $custoMedioDia = $qtdVendas > 0 ? $custos / $qtdVendas : 0;
            $margemDia = $vendas > 0 ? ($resultado / $vendas) * 100 : 0;

            $diario[] = [
                'dia' => $diaStr,
                'dia_semana' => (int)$data->format('N'),
                'nome_dia' => self::traduzirDiaSemana($data->format('l')),
                'vendas' => round($vendas, 2),
                'custos' => round($custos, 2),
                'resultado' => round($resultado, 2),
                'qtd_vendas' => $qtdVendas,
                'ticket_medio_dia' => round($ticketMedioDia, 2),
                'custo_medio_dia' => round($custoMedioDia, 2),
                'margem_dia' => round($margemDia, 2),
                'ids_pedidos' => isset($idsPedidosPorDia[$diaStr]) ? implode(',', $idsPedidosPorDia[$diaStr]) : ''
            ];
        }

        return $diario;
    }

    private static function getIdsPedidosPorDia(int $idempresa, string $dataini, string $datafim): array
    {
        $db = Database::getInstance();
        $sql = "
            SELECT DATE(COALESCE(pv.data_baixa, pv.data_pedido)) AS dia, pv.idpedidovenda
            FROM pedido_venda pv
            WHERE pv.idempresa = :idempresa
              AND pv.idsituacao_pedido_venda = 2
              AND DATE(COALESCE(pv.data_baixa, pv.data_pedido)) BETWEEN :dataini AND :datafim
            ORDER BY dia ASC
        ";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':idempresa', $idempresa, \PDO::PARAM_INT);
        $stmt->bindParam(':dataini', $dataini, \PDO::PARAM_STR);
        $stmt->bindParam(':datafim', $datafim, \PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $row) {
            $dia = $row['dia'];
            if (!isset($out[$dia])) {
                $out[$dia] = [];
            }
            $out[$dia][] = $row['idpedidovenda'];
        }
        return $out;
    }

    private static function traduzirDiaSemana(string $dayName): string
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

        return $traducoes[$dayName] ?? $dayName;
    }
}

