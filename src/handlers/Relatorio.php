<?php

/**
 * desc: helper de manipulação de Relatorios 
 * @autor: joaosn
 * @date: 22/06/2020
 */

namespace src\handlers;

use \src\models\Produtos as ProdutosModel;
use \src\models\Pedido_venda as PedidoVendaModel;
use \core\Controller as ctrl;
use core\Database;
use Exception;


class Relatorio extends ctrl
{
    public static function getProdutosRank($idempresa, $dataini = null, $datafim = null)
    {
        $produtos = ProdutosModel::getProdutosRank($idempresa, $dataini, $datafim);
        return $produtos;
    }

    public static function getVendas($idempresa, $dataini, $datafim, $idsituacao_pedido_venda)
    {
        $vendasDetalhado = PedidoVendaModel::getVendasDetalhado($idempresa, $idsituacao_pedido_venda, $dataini, $datafim);
        $vendasDia = PedidoVendaModel::getVendasDia($dataini, $datafim, $idempresa, $idsituacao_pedido_venda);
        return ['detalhado' => $vendasDetalhado, 'dia' => $vendasDia];
    }

    /**
     * ✅✅ MÉTODO CORRIGIDO: getVendasDiario
     * 
     * Correção: Usa os métodos corrigidos e adiciona validação de consistência
     * 
     * ⚠️ IMPORTANTE: A diferença entre produtos e pagamentos pode incluir:
     * - Taxa de entrega (pedidos delivery)
     * - Descontos aplicados
     * - Gorjeta
     * - Taxas administrativas
     * 
     * Por isso, produtos calculados != pagamentos recebidos (esperado!)
     */
    public static function getVendasDiario($idempresa, $dataini, $datafim)
    {
        $vendasDetalhado = PedidoVendaModel::getVendasProdutos($idempresa, $dataini, $datafim);
        $valores         = PedidoVendaModel::getRecebidos($idempresa, $dataini, $datafim);
        $quantidade      = PedidoVendaModel::getQuantidade($idempresa, $dataini, $datafim);
        $analitico       = PedidoVendaModel::getRecebidosAnalitico($idempresa, $dataini, $datafim);
        
        // ✅ Validação de consistência (compara total_pedido vs pagamentos)
        $validacao       = PedidoVendaModel::validarConsistenciaVendas($idempresa, $dataini, $datafim);
        
        // ✅ Resumo de taxas e descontos (diferença entre calculado e total_pedido)
        $taxasDescontos  = PedidoVendaModel::getResumoTaxasDescontos($idempresa, $dataini, $datafim);

        $aggpordata = [];
        foreach ($valores as $value) {
            if (!isset($value['dia'])) continue;
            $aggpordata[$value['dia']][] = $value;
        }

        return [
            'produtos'  => $vendasDetalhado,
            'recebidos' => [
                'analitico' => $analitico,
                'detalhado' => $aggpordata
            ],
            'qtd'       => $quantidade,
            'validacao' => $validacao,  // Validação total_pedido vs pagamentos
            'taxas_descontos' => $taxasDescontos  // ✅ NOVO: Lista de pedidos com taxa/desconto
        ];
    }

    public static function getDash($idempresa)
    {
        $cards = PedidoVendaModel::getCardDash($idempresa);
        $dias  = PedidoVendaModel::getDiasDash($idempresa);
        $geral = PedidoVendaModel::getGeralDash($idempresa);
        return [
            'cards' => $cards,
            'dias' => $dias,
            'geral' => $geral
        ];
    }

    /**
     * MÉTODO CORRIGIDO: relatorioPedidosNotas
     * 
     * Correção: Implementa query local em vez de depender de API externa
     * para garantir consistência e transparência nos cálculos.
     */
    public static function relatorioPedidosNotas($idempresa, $datainicio, $datafim)
    {
        try {
            // Query local para buscar dados de pedidos e notas
            $sql = "
                SELECT 
                    pv.idpedidovenda,
                    pv.total_pedido,
                    pv.data_baixa,
                    nf.idregistronota,
                    nf.valor_total as valor_nota,
                    nf.data_emissao,
                    CASE 
                        WHEN nf.idregistronota IS NOT NULL THEN 1
                        ELSE 0
                    END as tem_nota
                FROM pedido_venda pv
                LEFT JOIN nota_fiscal nf ON nf.idpedidovenda = pv.idpedidovenda 
                    AND nf.idempresa = pv.idempresa
                    AND nf.situacao = 'AUTORIZADA'  -- Apenas notas autorizadas
                WHERE pv.idempresa = :idempresa
                    AND DATE(pv.data_baixa) BETWEEN :datainicio AND :datafim
                    AND pv.idsituacao_pedido_venda = 2
                ORDER BY pv.data_baixa DESC
            ";

            $stmt = Database::getInstance()->prepare($sql);
            $stmt->bindValue(':idempresa', $idempresa);
            $stmt->bindValue(':datainicio', $datainicio);
            $stmt->bindValue(':datafim', $datafim);
            $stmt->execute();
            
            $pedidos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Cálculos corrigidos
            $resumo = [
                'pedidos_com_nota'  => 0,
                'pedidos_sem_nota'  => 0,
                'total_notas'       => 0,
                'valor_pedidos'     => 0.0,
                'valor_notas'       => 0.0,
                'diferenca_valores' => 0.0,
                'detalhes'          => []
            ];

            $notasUnicas = [];

            foreach ($pedidos as $p) {
                $valorPedido = (float) ($p['total_pedido'] ?? 0);
                $resumo['valor_pedidos'] += $valorPedido;

                if (!empty($p['idregistronota'])) {
                    $resumo['pedidos_com_nota'] += 1;
                    
                    // Evita contar a mesma nota múltiplas vezes
                    if (!in_array($p['idregistronota'], $notasUnicas)) {
                        $notasUnicas[] = $p['idregistronota'];
                        $valorNota = (float) ($p['valor_nota'] ?? 0);
                        $resumo['valor_notas'] += $valorNota;
                        
                        // Adiciona detalhes para auditoria
                        $resumo['detalhes'][] = [
                            'pedido' => $p['idpedidovenda'],
                            'valor_pedido' => $valorPedido,
                            'nota' => $p['idregistronota'],
                            'valor_nota' => $valorNota,
                            'diferenca' => $valorPedido - $valorNota,
                            'data_pedido' => $p['data_baixa'],
                            'data_nota' => $p['data_emissao']
                        ];
                    }
                } else {
                    $resumo['pedidos_sem_nota'] += 1;
                    
                    // Adiciona pedidos sem nota para auditoria
                    $resumo['detalhes'][] = [
                        'pedido' => $p['idpedidovenda'],
                        'valor_pedido' => $valorPedido,
                        'nota' => null,
                        'valor_nota' => 0,
                        'diferenca' => $valorPedido,
                        'data_pedido' => $p['data_baixa'],
                        'data_nota' => null
                    ];
                }
            }

            $resumo['total_notas'] = count($notasUnicas);
            $resumo['diferenca_valores'] = round($resumo['valor_pedidos'] - $resumo['valor_notas'], 2);

            // Adiciona estatísticas adicionais
            $resumo['estatisticas'] = [
                'total_pedidos' => count($pedidos),
                'percentual_com_nota' => count($pedidos) > 0 ? 
                    round(($resumo['pedidos_com_nota'] / count($pedidos)) * 100, 2) : 0,
                'valor_medio_pedido' => count($pedidos) > 0 ? 
                    round($resumo['valor_pedidos'] / count($pedidos), 2) : 0,
                'valor_medio_nota' => $resumo['total_notas'] > 0 ? 
                    round($resumo['valor_notas'] / $resumo['total_notas'], 2) : 0
            ];

            return $resumo;

        } catch (Exception $e) {
            // Log do erro para debugging
            error_log("Erro no relatório pedidos x notas: " . $e->getMessage());
            
            // Fallback para o método original em caso de erro
            return self::relatorioPedidosNotasOriginal($idempresa, $datainicio, $datafim);
        }
    }

    /**
     * Método original mantido como fallback
     */
    private static function relatorioPedidosNotasOriginal($idempresa, $datainicio, $datafim)
    {
        $params = [
            'idempresa'  => $idempresa,
            'datainicio' => $datainicio,
            'datafim'    => $datafim,
            'meios'      => ''
        ];

        $response = Database::switchParams($params, 'NFE/getNotasREL', true);
        $pedidos = $response['retorno'] ?? [];

        $resumo = [
            'pedidos_com_nota'  => 0,
            'pedidos_sem_nota'  => 0,
            'total_notas'       => 0,
            'valor_pedidos'     => 0.0,
            'valor_notas'       => 0.0,
            'diferenca_valores' => 0.0,
            'metodo'            => 'api_externa'  // Indica que usou método original
        ];

        foreach ($pedidos as $p) {
            $resumo['valor_pedidos'] += (float) ($p['total_pedido'] ?? 0);
            if (!empty($p['idregistronota'])) {
                $resumo['pedidos_com_nota'] += 1;
                $resumo['valor_notas'] += (float) ($p['valor_total'] ?? 0);
            } else {
                $resumo['pedidos_sem_nota'] += 1;
            }
        }

        $resumo['total_notas'] = $resumo['pedidos_com_nota'];
        $resumo['diferenca_valores'] = $resumo['valor_pedidos'] - $resumo['valor_notas'];

        return $resumo;
    }

    /**
     * NOVO MÉTODO: Relatório de auditoria para identificar inconsistências
     */
    public static function relatorioAuditoria($idempresa, $datainicio, $datafim)
    {
        $sql = "
            SELECT 
                'pedidos_sem_pagamento' as tipo_inconsistencia,
                pv.idpedidovenda,
                pv.total_pedido,
                pv.data_baixa,
                'Pedido sem pagamento registrado' as descricao
            FROM pedido_venda pv
            LEFT JOIN pagamentos p ON p.idpedidovenda = pv.idpedidovenda 
                AND p.idempresa = pv.idempresa
            WHERE pv.idempresa = :idempresa
                AND DATE(pv.data_baixa) BETWEEN :datainicio AND :datafim
                AND pv.idsituacao_pedido_venda = 2
                AND p.idpagamento IS NULL
                
            UNION ALL
            
            SELECT 
                'divergencia_valor_pagamento' as tipo_inconsistencia,
                pv.idpedidovenda,
                pv.total_pedido,
                pv.data_baixa,
                CONCAT('Divergência: Pedido R$ ', pv.total_pedido, ' vs Pagamento R$ ', SUM(p.valor)) as descricao
            FROM pedido_venda pv
            INNER JOIN pagamentos p ON p.idpedidovenda = pv.idpedidovenda 
                AND p.idempresa = pv.idempresa
            WHERE pv.idempresa = :idempresa
                AND DATE(pv.data_baixa) BETWEEN :datainicio AND :datafim
                AND pv.idsituacao_pedido_venda = 2
            GROUP BY pv.idpedidovenda, pv.total_pedido, pv.data_baixa
            HAVING ABS(pv.total_pedido - SUM(p.valor)) > 0.01
            
            ORDER BY data_baixa DESC
        ";

        $stmt = Database::getInstance()->prepare($sql);
        $stmt->bindValue(':idempresa', $idempresa);
        $stmt->bindValue(':datainicio', $datainicio);
        $stmt->bindValue(':datafim', $datafim);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
