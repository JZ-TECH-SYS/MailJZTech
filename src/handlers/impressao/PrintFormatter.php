<?php

namespace src\handlers\impressao;

/**
 * Service responsável por formatar os dados para impressão
 * Mantém toda a lógica de formatação do código original
 */
class PrintFormatter
{
    /**
     * Formata os dados do recibo mantendo a mesma lógica original
     */
    public static function formatReciboData(array $data): array
    {
        // Retorna os dados já organizados e prontos para usar no template
        // Mantém a mesma estrutura que o código original esperava
        
        return [
            'empresa' => $data['empresa'],
            'largura' => $data['largura'],
            'altura' => $data['altura'],
            'pedido' => $data['pedido'],
            'cliente' => $data['cliente'],
            'clienteCompleto' => $data['clienteCompleto'],
            'meiopagamento' => $data['meiopagamento'],
            'cupom' => $data['cupom'],
            'qtdpedidos' => $data['qtdpedidos'],
            'cuponsDisponiveis' => $data['cuponsDisponiveis'],
            'qtdCupons' => $data['qtdCupons'],
            'valorTotalCupons' => $data['valorTotalCupons'],
            'obspdv' => $data['obspdv'],
            'bairro' => $data['bairro'],
            'total' => $data['total']
        ];
    }

    /**
     * Formata dados para impressão de pedidos por categoria
     */
    public static function formatPedidoData(array $data): array
    {
        $pedido = $data['pedido'];
        $categorias = $data['categorias'];

        // Agrupa itens por categoria (mesma lógica original)
        $porCat = [];
        foreach ($pedido['itens'] as $it) {
            $porCat[$it['idcategoria']][] = $it;
        }

        return [
            'pedido' => $pedido,
            'categorias' => $categorias,
            'itensPorCategoria' => $porCat
        ];
    }

    /**
     * Formata dados para impressão pendente
     */
    public static function formatPendenteData(array $data): array
    {
        if (empty($data['pedidos'])) {
            return ['itensPorCategoria' => []];
        }

        $pedido = $data['pedido'];
        $categorias = $data['categorias'];

        // Agrupa apenas os itens que ainda não foram impressos (mesma lógica)
        $itensPorCategoria = [];
        foreach ($pedido['itens'] as $item) {
            if (isset($item['impresso']) && $item['impresso']) {
                continue;
            }
            $itensPorCategoria[$item['idcategoria']][] = $item;
        }

        return [
            'pedido' => $pedido,
            'categorias' => $categorias,
            'itensPorCategoria' => $itensPorCategoria,
            'cliente' => $pedido['nome'],
            'mesa' => $pedido['idmesa'] ?? ''
        ];
    }

    /**
     * Formata dados para impressão de item específico
     */
    public static function formatItemData(array $data): array
    {
        return [
            'pedido' => $data['pedido'],
            'item' => $data['item'],
            'categoria' => $data['categoria']
        ];
    }
}
