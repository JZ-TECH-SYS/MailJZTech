<?php

namespace src\models;

use core\Database;
use \core\Model;
use PDO;

/**
 * Classe modelo para a tabela 'pedido_venda' do banco de dados.
 */
class Pedido_venda extends Model
{
    public static function getVendasDetalhado($idempresa, $idsituacao_pedido_venda, $dataini, $datafim)
    {
        $sql = "
            select
                pv.idpedidovenda,
                pv.idempresa,
                pv.idmesa,
                pv.data_baixa,
                pv.total_pedido,
                pv.origin,
                pv.nome,
                count(pvi.idpedido_item) as quantidade_itens,
                count(pvia.idpedido_acrescimo) as quantidade_acres
            from pedido_venda pv
            left join pessoa p on p.idcliente = pv.idcliente and p.idempresa = pv.idempresa
            left join pedido_venda_item pvi on pvi.idempresa = pv.idempresa and pvi.idpedidovenda = pv.idpedidovenda
            left join pedido_venda_item_acrescimos pvia on pvia.idempresa = pvi.idempresa and pvia.idpedidovenda = pvi.idpedidovenda and pvia.idpedido_item = pvi.idpedido_item
            where pv.idempresa = :idempresa
                and (pv.idsituacao_pedido_venda = :idsituacao_pedido_venda or :idsituacao_pedido_venda = 69)
                and date(pv.data_baixa) between :dataini and :datafim
            group by 1, 2, 3, 4, 5, 6, 7
        ";

        $sql = Database::getInstance()->prepare($sql);
        $sql->bindValue(':idempresa', $idempresa);
        $sql->bindValue(':idsituacao_pedido_venda', $idsituacao_pedido_venda);
        $sql->bindValue(':dataini', addslashes($dataini));
        $sql->bindValue(':datafim', addslashes($datafim));
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public static function getVendasDia($dataini, $datafim, $idempresa, $idsituacao_pedido_venda)
    {
        $sql = "
            select
                DATE(pv.data_baixa) as data_baixa
               ,sum(pv.total_pedido) as total_venda_dia
            from pedido_venda pv
            where pv.idempresa = :idempresa
              and (pv.idsituacao_pedido_venda = :idsituacao_pedido_venda or :idsituacao_pedido_venda = 69)
              and DATE(pv.data_baixa)  between :dataini and :datafim
            group by DATE(pv.data_baixa)
        ";

        $sql = Database::getInstance()->prepare($sql);
        $sql->bindValue(':idempresa', $idempresa);
        $sql->bindValue(':idsituacao_pedido_venda', $idsituacao_pedido_venda);
        $sql->bindValue(':dataini', addslashes($dataini));
        $sql->bindValue(':datafim', addslashes($datafim));
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public static function getCardDash($idempresa)
    {
        $sql = "
        SELECT 
            'vendas' AS incon, 
            'R$ Hoje' AS title, 
            COALESCE(SUM(p.total_pedido), 0) AS total 
        FROM pedido_venda p 
        WHERE cast(p.data_baixa as date) = CURRENT_DATE 
          AND p.idempresa = :idempresa 
          AND p.idsituacao_pedido_venda = 2

        UNION 

        SELECT 
            'vendasSum' AS incon, 
            'R$ Semana' AS title, 
            COALESCE(SUM(pv.total_pedido), 0) AS total 
        FROM pedido_venda pv 
        WHERE WEEK(pv.data_baixa) = WEEK(CURRENT_DATE) AND YEAR(pv.data_baixa) = YEAR(CURRENT_DATE) 
          AND pv.idempresa = :idempresa 
          AND pv.idsituacao_pedido_venda = 2

        UNION 

        SELECT 
            'vendasSum' AS incon, 
            'R$ Mes   ' AS title, 
            COALESCE(SUM(pv.total_pedido), 0) AS total 
        FROM pedido_venda pv 
        WHERE MONTH(pv.data_baixa) = MONTH(CURRENT_DATE) AND YEAR(pv.data_baixa) = YEAR(CURRENT_DATE) 
        AND pv.idempresa = :idempresa 
        AND pv.idsituacao_pedido_venda = 2

        UNION 

        SELECT 
            'vendas' AS incon, 
            'R$ Total' AS title, 
            COALESCE(SUM(pv.total_pedido), 0) AS total 
        FROM pedido_venda pv 
        WHERE pv.idempresa = :idempresa 
        AND pv.idsituacao_pedido_venda = 2

        UNION 

        SELECT 
            'despesasSum' AS incon, 
            'R$ D/Mes' AS title, 
            COALESCE(SUM(d.valor), 0) AS total 
        FROM despesas d 
        WHERE MONTH(d.datahora) = MONTH(CURRENT_DATE) AND YEAR(d.datahora) = YEAR(CURRENT_DATE) 
        AND d.idempresa = :idempresa

        UNION

        SELECT 
            'despesas' AS incon, 
            'R$ D/Total' AS title, 
            COALESCE(SUM(d.valor), 0) AS total 
        FROM despesas d 
        WHERE d.idempresa = :idempresa

        UNION

        SELECT 
            'pedidos' AS incon, 
            'Pedidos Hoje' AS title, 
            COUNT(pd.idpedidovenda) AS total 
        FROM pedido_venda pd 
        WHERE cast(pd.data_baixa as date) = CURRENT_DATE 
        AND pd.idempresa = :idempresa

        UNION 

        SELECT 
            'pedidosSum' AS incon, 
            'Pedidos Semana' AS title, 
            COUNT(pdd.idpedidovenda) AS total 
        FROM pedido_venda pdd 
        WHERE WEEK(pdd.data_baixa) = WEEK(CURRENT_DATE) AND YEAR(pdd.data_baixa) = YEAR(CURRENT_DATE) 
        AND pdd.idempresa = :idempresa

        UNION 

        SELECT 
            'clientes' AS incon, 
            'Total Clientes' AS title, 
            COUNT(*) AS total 
        FROM pessoa ps 
        WHERE ps.idempresa = :idempresa

        UNION 

        SELECT 
            'produtos' AS incon, 
            'Total Produtos' AS title, 
            COUNT(*) AS total 
        FROM produtos pr 
        WHERE pr.tipo_produto = 1 
        AND pr.idempresa = :idempresa

        UNION 

        SELECT 
            'produtos' AS incon, 
            'Total Acréscimos' AS title, 
            COUNT(*) AS total 
        FROM produtos pa 
        WHERE pa.tipo_produto = 2 
        AND pa.idempresa = :idempresa

        UNION 

        SELECT 
            'city' AS incon, 
            'Total Bairros' AS title, 
            COUNT(*) AS total 
        FROM bairros c
        WHERE c.idempresa = :idempresa;
        ";
        $sql = Database::getInstance()->prepare($sql);
        $sql->bindValue(':idempresa', $idempresa);
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public static function getDiasDash($idempresa)
    {
        $sql = "
            select
                case
                    when dayofweek(pv.data_baixa) = 1 then 'Domingo'
                    when dayofweek(pv.data_baixa) = 2 then 'Segunda'
                    when dayofweek(pv.data_baixa) = 3 then 'Terça'
                    when dayofweek(pv.data_baixa) = 4 then 'Quarta'
                    when dayofweek(pv.data_baixa) = 5 then 'Quinta'
                    when dayofweek(pv.data_baixa) = 6 then 'Sexta'
                    when dayofweek(pv.data_baixa) = 7 then 'Sábado'
                end as diasemana,
                coalesce(sum(pv.total_pedido), 0) as total
            from pedido_venda pv
            where week(pv.data_baixa) = week(current_date)
                and year(pv.data_baixa) = year(current_date)
                and pv.idempresa = :idempresa
                and pv.idsituacao_pedido_venda = 2
            group by diasemana;
        ";
        $sql = Database::getInstance()->prepare($sql);
        $sql->bindValue(':idempresa', $idempresa);
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    public static function getGeralDash($idempresa)
    {
        $sql = "
        SELECT 
            'Vendas Online' AS descricao, 
            COALESCE(SUM(p.total_pedido), 0) AS total 
        FROM pedido_venda p 
        WHERE p.idempresa = :idempresa 
        AND p.idsituacao_pedido_venda = 2
        AND p.origin = 2
        
        UNION 
        
        SELECT 
            'Vendas Comanda' AS descricao, 
            COALESCE(SUM(p.total_pedido), 0) AS total 
        FROM pedido_venda p 
        WHERE p.idempresa = :idempresa 
        AND p.idsituacao_pedido_venda = 2
        AND p.origin = 1
        
        UNION 
        
        SELECT 
            'Total Produtos' AS descricao, 
            COUNT(*) AS total 
        FROM produtos pr 
        WHERE pr.tipo_produto = 1 
        AND pr.idempresa = :idempresa
        
        UNION 
        
        SELECT 
            'Total Acréscimos' AS descricao, 
            COUNT(*) AS total 
        FROM produtos pa 
        WHERE pa.tipo_produto = 2 
        AND pa.idempresa = :idempresa
        
        UNION 
        
        SELECT 
            'Total Clientes' AS descricao, 
            COUNT(*) AS total 
        FROM pessoa ps 
        WHERE ps.idempresa = :idempresa
        
        UNION 
        
        SELECT 
            'Total Bairros' AS descricao, 
            COUNT(*) AS total 
        FROM bairros c
        where c.idempresa = :idempresa;    
        ";
        $sql = Database::getInstance()->prepare($sql);
        $sql->bindValue(':idempresa', $idempresa);
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * MÉTODO CORRIGIDO: getVendasProdutos
     * 
     * Correção: Usa o valor proporcional do pedido em vez do preço atual do produto
     * para garantir que o total dos produtos bata com o total dos pagamentos.
     */
    /**
     * ✅✅✅ MÉTODO CORRIGIDO: getVendasProdutos
     * 
     * ⚠️ IMPORTANTE: Toda a lógica de cálculo está aqui no backend!
     * 
     * Lógica:
     * 1. USA preco_unitario do pedido_venda_item se existir (NOT NULL)
     * 2. SENÃO, busca o preço cadastrado do produto
     * 3. Calcula subtotal = (preco_unitario OU preco_produto) * quantidade
     * 4. Soma acréscimos vinculados ao item (pré-agregados em subquery)
     * 5. Total = subtotal + acréscimos
     * 
     * ✅ MySQL 5.7 Compatible: Usa derived table para pré-agregar acréscimos
     * ✅ Evita duplicação de valores (problema do LEFT JOIN direto)
     * 
     * O frontend SÓ LISTA os dados, sem fazer contas!
     * Assim evitamos divergências por acréscimos.
     */
    public static function getVendasProdutos($idempresa, $dataini, $datafim)
    {
        $sql = "
            SELECT
                p.idproduto,
                p.nome as descricao,
                -- ✅ Preço unitário médio: usa do item se existir, senão busca do produto
                ROUND(
                    SUM(COALESCE(pvi.preco_unitario, p.preco) * pvi.quantidade) / SUM(pvi.quantidade), 
                    2
                ) as valor,
                SUM(pvi.quantidade) as quantidade,
                -- ✅ Total = (preço * quantidade) + acréscimos pré-agregados
                ROUND(
                    SUM(COALESCE(pvi.preco_unitario, p.preco) * pvi.quantidade) + 
                    COALESCE(SUM(acrescimos.total_acrescimos), 0),
                    2
                ) as total
            FROM pedido_venda pv
                INNER JOIN pedido_venda_item pvi 
                    ON pvi.idempresa = pv.idempresa 
                    AND pvi.idpedidovenda = pv.idpedidovenda
                INNER JOIN produtos p 
                    ON p.idempresa = pvi.idempresa 
                    AND p.idproduto = pvi.idproduto
                -- ✅ LEFT JOIN com subquery que pré-agrega acréscimos por item
                -- Assim cada item aparece apenas 1 vez (evita duplicação)
                LEFT JOIN (
                    SELECT 
                        pvia.idempresa,
                        pvia.idpedidovenda,
                        pvia.idpedido_item,
                        SUM(COALESCE(pvia.preco_unitario, p_acr.preco) * pvia.quantidade) as total_acrescimos
                    FROM pedido_venda_item_acrescimos pvia
                    INNER JOIN produtos p_acr 
                        ON p_acr.idempresa = pvia.idempresa
                        AND p_acr.idproduto = pvia.idproduto
                    GROUP BY pvia.idempresa, pvia.idpedidovenda, pvia.idpedido_item
                ) as acrescimos
                    ON acrescimos.idempresa = pvi.idempresa
                    AND acrescimos.idpedidovenda = pvi.idpedidovenda
                    AND acrescimos.idpedido_item = pvi.idpedido_item
            WHERE pv.idempresa = :idempresa
                AND DATE(pv.data_baixa) BETWEEN :dataini AND :datafim
                AND pv.idsituacao_pedido_venda = 2
                AND p.tipo_produto = 1  -- Apenas produtos principais, não acréscimos
            GROUP BY p.idproduto, p.nome
            ORDER BY total DESC
        ";

        $sql = Database::getInstance()->prepare($sql);
        $sql->bindValue(':idempresa', $idempresa);
        $sql->bindValue(':dataini', $dataini);
        $sql->bindValue(':datafim', $datafim);
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * MÉTODO CORRIGIDO: getRecebidos
     * 
     * Correção: Adiciona validação para garantir que apenas pagamentos
     * de pedidos válidos sejam considerados.
     */
    public static function getRecebidos($idempresa, $dataini, $datafim)
    {
        $sql = "
            SELECT
                tp.descricao as tipo_pagamento,
                DATE(p.data_pagamento) as dia,
                ROUND(SUM(p.valor), 2) as valor
            FROM pedido_venda pv
                INNER JOIN pagamentos p ON (p.idempresa = pv.idempresa 
                    AND p.idpedidovenda = pv.idpedidovenda)
                INNER JOIN tipo_pagamento tp ON (tp.idempresa = p.idempresa 
                    AND tp.idtipopagamento = p.idtipopagamento)
            WHERE pv.idempresa = :idempresa
                AND DATE(pv.data_baixa) BETWEEN :dataini AND :datafim
                AND pv.idsituacao_pedido_venda = 2
                AND p.valor > 0  -- Apenas pagamentos com valor positivo
            GROUP BY tp.descricao, DATE(p.data_pagamento)
            ORDER BY dia, tp.descricao
        ";

        $sql = Database::getInstance()->prepare($sql);
        $sql->bindValue(':idempresa', $idempresa);
        $sql->bindValue(':dataini', addslashes($dataini));
        $sql->bindValue(':datafim', addslashes($datafim));
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        return $result ?? [];
    }

    /**
     * MÉTODO CORRIGIDO: getRecebidosAnalitico
     * 
     * Correção: Adiciona validação e melhora a precisão dos cálculos.
     */
    public static function getRecebidosAnalitico($idempresa, $dataini, $datafim)
    {
        $sql = "
            SELECT
                tp.descricao as tipo_pagamento,
                ROUND(SUM(p.valor), 2) as valor,
                COUNT(p.idpagamento) as quantidade_transacoes
            FROM pedido_venda pv
                INNER JOIN pagamentos p ON (p.idempresa = pv.idempresa 
                    AND p.idpedidovenda = pv.idpedidovenda)
                INNER JOIN tipo_pagamento tp ON (tp.idempresa = p.idempresa 
                    AND tp.idtipopagamento = p.idtipopagamento)
            WHERE pv.idempresa = :idempresa
                AND DATE(pv.data_baixa) BETWEEN :dataini AND :datafim
                AND pv.idsituacao_pedido_venda = 2
                AND p.valor > 0  -- Apenas pagamentos com valor positivo
                AND tp.descricao IS NOT NULL
            GROUP BY tp.descricao
            ORDER BY valor DESC
        ";

        $sql = Database::getInstance()->prepare($sql);
        $sql->bindValue(':idempresa', $idempresa);
        $sql->bindValue(':dataini', addslashes($dataini));
        $sql->bindValue(':datafim', addslashes($datafim));
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * MÉTODO CORRIGIDO: getQuantidade
     * 
     * Correção: Melhora a precisão dos cálculos e adiciona validações.
     */
    public static function getQuantidade($idempresa, $dataini, $datafim)
    {
        $sql = "
            SELECT
                'nº de entregas' as descricao,
                COUNT(DISTINCT pv.idpedidovenda) as quantidade
            FROM pedido_venda pv
            WHERE pv.idempresa = :idempresa
                AND DATE(pv.data_baixa) BETWEEN :dataini AND :datafim
                AND pv.idsituacao_pedido_venda = 2
                AND pv.origin = 2
                AND pv.metodo_entrega = 1
            
            UNION ALL
            
            SELECT
                'nº de retiradas' as descricao,
                COUNT(DISTINCT pv.idpedidovenda) as quantidade
            FROM pedido_venda pv
            WHERE pv.idempresa = :idempresa
                AND DATE(pv.data_baixa) BETWEEN :dataini AND :datafim
                AND pv.idsituacao_pedido_venda = 2
                AND pv.origin = 2
                AND pv.metodo_entrega = 2
            
            UNION ALL
            
            SELECT
                'nº de produtos online' as descricao,
                SUM(pvi.quantidade) as quantidade
            FROM pedido_venda pv
                INNER JOIN pedido_venda_item pvi ON (pvi.idempresa = pv.idempresa 
                    AND pvi.idpedidovenda = pv.idpedidovenda)
                INNER JOIN produtos p ON (p.idempresa = pvi.idempresa 
                    AND p.idproduto = pvi.idproduto)
            WHERE pv.idempresa = :idempresa
                AND DATE(pv.data_baixa) BETWEEN :dataini AND :datafim
                AND pv.idsituacao_pedido_venda = 2
                AND pv.origin = 2
                AND pv.metodo_entrega = 1
                AND p.tipo_produto = 1  -- Apenas produtos, não acréscimos
            
            UNION ALL
            
            SELECT
                'nº de produtos salão' as descricao,
                SUM(pvi.quantidade) as quantidade
            FROM pedido_venda pv
                INNER JOIN pedido_venda_item pvi ON (pvi.idempresa = pv.idempresa 
                    AND pvi.idpedidovenda = pv.idpedidovenda)
                INNER JOIN produtos p ON (p.idempresa = pvi.idempresa 
                    AND p.idproduto = pvi.idproduto)
            WHERE pv.idempresa = :idempresa
                AND DATE(pv.data_baixa) BETWEEN :dataini AND :datafim
                AND pv.idsituacao_pedido_venda = 2
                AND pv.origin = 1
                AND p.tipo_produto = 1  -- Apenas produtos, não acréscimos
            
            UNION ALL
            
            SELECT
                'nº de produtos geral' as descricao,
                SUM(pvi.quantidade) as quantidade
            FROM pedido_venda pv
                INNER JOIN pedido_venda_item pvi ON (pvi.idempresa = pv.idempresa 
                    AND pvi.idpedidovenda = pv.idpedidovenda)
                INNER JOIN produtos p ON (p.idempresa = pvi.idempresa 
                    AND p.idproduto = pvi.idproduto)
            WHERE pv.idempresa = :idempresa
                AND DATE(pv.data_baixa) BETWEEN :dataini AND :datafim
                AND pv.idsituacao_pedido_venda = 2
                AND p.tipo_produto = 1  -- Apenas produtos, não acréscimos
        ";

        $sql = Database::getInstance()->prepare($sql);
        $sql->bindValue(':idempresa', $idempresa);
        $sql->bindValue(':dataini', addslashes($dataini));
        $sql->bindValue(':datafim', addslashes($datafim));
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * ✅✅✅ MÉTODO CORRETO: Validação REAL produtos + acréscimos vs pagamentos
     * 
     * Usa a MESMA lógica de getVendasProdutos() para calcular total real
     * Compara: (produtos + acréscimos) vs pagamentos
     * 
     * ⚠️ IMPORTANTE: Não usa pv.total_pedido (pode estar desatualizado)
     */
    public static function validarConsistenciaVendas($idempresa, $dataini, $datafim)
    {
        // Query 1: Soma REAL (produtos + acréscimos) - MESMA lógica de getVendasProdutos()
        $sqlProdutos = "
            SELECT 
                ROUND(
                    SUM(
                        COALESCE(produtos.total_produtos, 0) + 
                        COALESCE(acrescimos.total_acrescimos, 0)
                    ), 
                    2
                ) as total_produtos,
                COUNT(DISTINCT pv.idpedidovenda) as quantidade_pedidos
            FROM pedido_venda pv
                LEFT JOIN (
                    SELECT 
                        pvi.idempresa,
                        pvi.idpedidovenda,
                        ROUND(SUM(
                            pvi.quantidade * COALESCE(pvi.preco_unitario, p.preco)
                        ), 2) as total_produtos
                    FROM pedido_venda_item pvi
                        LEFT JOIN produtos p ON (p.idproduto = pvi.idproduto 
                            AND p.idempresa = pvi.idempresa)
                    GROUP BY pvi.idempresa, pvi.idpedidovenda
                ) as produtos ON (produtos.idempresa = pv.idempresa 
                    AND produtos.idpedidovenda = pv.idpedidovenda)
                LEFT JOIN (
                    SELECT 
                        pvia.idempresa,
                        pvia.idpedidovenda,
                        ROUND(SUM(
                            pvia.quantidade * COALESCE(pvia.preco_unitario, p_acr.preco)
                        ), 2) as total_acrescimos
                    FROM pedido_venda_item_acrescimos pvia
                        INNER JOIN produtos p_acr ON (p_acr.idproduto = pvia.idproduto 
                            AND p_acr.idempresa = pvia.idempresa)
                    GROUP BY pvia.idempresa, pvia.idpedidovenda
                ) as acrescimos ON (acrescimos.idempresa = pv.idempresa 
                    AND acrescimos.idpedidovenda = pv.idpedidovenda)
            WHERE pv.idempresa = :idempresa
                AND DATE(pv.data_baixa) BETWEEN :dataini AND :datafim
                AND pv.idsituacao_pedido_venda = 2
        ";
        
        $stmt1 = Database::getInstance()->prepare($sqlProdutos);
        $stmt1->bindValue(':idempresa', $idempresa);
        $stmt1->bindValue(':dataini', addslashes($dataini));
        $stmt1->bindValue(':datafim', addslashes($datafim));
        $stmt1->execute();
        $resultProdutos = $stmt1->fetch(PDO::FETCH_ASSOC);
        
        // Query 2: Soma pagamentos
        $sqlPagamentos = "
            SELECT 
                ROUND(SUM(p.valor), 2) as total_pagamentos
            FROM pedido_venda pv
                INNER JOIN pagamentos p ON (p.idempresa = pv.idempresa 
                    AND p.idpedidovenda = pv.idpedidovenda)
            WHERE pv.idempresa = :idempresa
                AND DATE(pv.data_baixa) BETWEEN :dataini AND :datafim
                AND pv.idsituacao_pedido_venda = 2
                AND p.valor > 0
        ";
        
        $stmt2 = Database::getInstance()->prepare($sqlPagamentos);
        $stmt2->bindValue(':idempresa', $idempresa);
        $stmt2->bindValue(':dataini', addslashes($dataini));
        $stmt2->bindValue(':datafim', addslashes($datafim));
        $stmt2->execute();
        $resultPagamentos = $stmt2->fetch(PDO::FETCH_ASSOC);
        
        // Combina resultados
        $totalProdutos = $resultProdutos['total_produtos'] ?? 0;
        $totalPagamentos = $resultPagamentos['total_pagamentos'] ?? 0;
        
        return [
            'tipo' => 'validacao',
            'total_pedidos' => $totalProdutos,  // ✅ Total REAL (produtos + acréscimos)
            'total_pagamentos' => $totalPagamentos,
            'diferenca' => round($totalProdutos - $totalPagamentos, 2),
            'quantidade_pedidos' => $resultProdutos['quantidade_pedidos'] ?? 0
        ];
    }

    /**
     * ✅✅ NOVO MÉTODO: Obter resumo de taxas e descontos
     * 
     * Identifica a diferença entre o total calculado (produtos + acréscimos)
     * e o total_pedido (que inclui taxas de entrega, descontos aplicados, etc)
     */
    public static function getResumoTaxasDescontos($idempresa, $dataini, $datafim)
    {
        $sql = "
            SELECT
                pv.idpedidovenda,
                pv.nome,
                pv.total_pedido,
                pv.origin,
                pv.metodo_entrega,
                -- Calcula total de produtos + acréscimos
                ROUND(
                    COALESCE(produtos.total_produtos, 0) + 
                    COALESCE(acrescimos.total_acrescimos, 0),
                    2
                ) as total_calculado,
                -- Diferença = total_pedido - (produtos + acréscimos)
                ROUND(
                    pv.total_pedido - (
                        COALESCE(produtos.total_produtos, 0) + 
                        COALESCE(acrescimos.total_acrescimos, 0)
                    ),
                    2
                ) as diferenca
            FROM pedido_venda pv
                -- Soma produtos
                LEFT JOIN (
                    SELECT 
                        pvi.idempresa,
                        pvi.idpedidovenda,
                        SUM(COALESCE(pvi.preco_unitario, p.preco) * pvi.quantidade) as total_produtos
                    FROM pedido_venda_item pvi
                    INNER JOIN produtos p ON p.idempresa = pvi.idempresa 
                        AND p.idproduto = pvi.idproduto
                    GROUP BY pvi.idempresa, pvi.idpedidovenda
                ) as produtos 
                    ON produtos.idempresa = pv.idempresa
                    AND produtos.idpedidovenda = pv.idpedidovenda
                -- Soma acréscimos
                LEFT JOIN (
                    SELECT 
                        pvia.idempresa,
                        pvia.idpedidovenda,
                        SUM(COALESCE(pvia.preco_unitario, p_acr.preco) * pvia.quantidade) as total_acrescimos
                    FROM pedido_venda_item_acrescimos pvia
                    INNER JOIN produtos p_acr 
                        ON p_acr.idempresa = pvia.idempresa
                        AND p_acr.idproduto = pvia.idproduto
                    GROUP BY pvia.idempresa, pvia.idpedidovenda
                ) as acrescimos
                    ON acrescimos.idempresa = pv.idempresa
                    AND acrescimos.idpedidovenda = pv.idpedidovenda
            WHERE pv.idempresa = :idempresa
                AND DATE(pv.data_baixa) BETWEEN :dataini AND :datafim
                AND pv.idsituacao_pedido_venda = 2
                -- ✅ Mostra apenas pedidos com diferença (taxa/desconto)
                HAVING diferenca != 0
            ORDER BY ABS(diferenca) DESC
        ";

        $sql = Database::getInstance()->prepare($sql);
        $sql->bindValue(':idempresa', $idempresa);
        $sql->bindValue(':dataini', addslashes($dataini));
        $sql->bindValue(':datafim', addslashes($datafim));
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    }

    /**
     * Buscar o próximo número de mesa disponível
     * Retorna: MAX(idmesa) dos pedidos abertos + 1
     * 
     * @param int $idempresa
     * @return int Próximo número de mesa
     */
    public static function getProximaMesa($idempresa)
    {
        $params = ['idempresa' => $idempresa];

        // Query complexa com agregação - usa switchParams
        $result = Database::switchParams(
            $params,
            'mesa/getProximaMesa',
            true,
            true
        );

        $data = $result['retorno'];
        return intval($data[0]['proxima_mesa'] ?? 1);
    }
}