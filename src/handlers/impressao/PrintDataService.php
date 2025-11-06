<?php

namespace src\handlers\impressao;

use src\models\Empresa;
use src\models\Empresa_parametro;
use src\handlers\PedidoVenda;
use src\models\Categoria;
use src\models\Bairros;
use src\models\Cupon;
use src\models\Pessoa;
use src\models\Pagamentos;

/**
 * Service responsável por buscar todos os dados necessários para impressão
 * Separando a responsabilidade de consultas ao banco
 */
class PrintDataService
{
    /**
     * Busca todos os dados necessários para o recibo
     * Mantém a mesma lógica de consultas do código original
     */
    public static function getReciboData(int $idEmpresa, int $idPedido): array
    {
        // Busca dados da empresa e medidas (mesmo código original)
        $medida = Empresa_parametro::select()
            ->where('idempresa', $idEmpresa)
            ->where('idparametro', 10)
            ->one();

        if (!$medida) {
            throw new \Exception('Configure largura/altura em empresa_parametro (idparametro 10)');
        }

        [$largura, $altura] = array_map('trim', explode(',', $medida['valor']));
        $empresa = Empresa::select()->where('idempresa', $idEmpresa)->one();

        // Busca dados do pedido (mesmo código original)
        $pedido = PedidoVenda::getPedidoVendas(null, $idEmpresa, $idPedido, null, null)[0];

        // Busca cliente (mesmo código original)
        $cliente = Pessoa::select()
            ->where('idcliente', $pedido['idcliente'])
            ->where('idempresa', $idEmpresa)
            ->where('nome', '<>', 'venda')
            ->one();

        // Busca meio de pagamento (mesmo código original)
        $meiopagamento = Pagamentos::select('p.descricao')
            ->join('tipo_pagamento as p', 'p.idtipopagamento', '=', 'pagamentos.idtipopagamento')
            ->where('pagamentos.idempresa', $idEmpresa)
            ->where('pagamentos.idpedidovenda', $idPedido)
            ->one();

        // Busca cupons do pedido (mesmo código original)
        $cupom = Cupon::getCuponsPedido($idEmpresa, $idPedido);

        // Busca dados do cliente para cupons e quantidade de pedidos (mesmo código original)
        $qtdpedidos = 0;
        $cuponsDisponiveis = [];
        $qtdCupons = 0;
        $valorTotalCupons = 0;

        $clienteCompleto = Pessoa::select()
            ->where('idcliente', $pedido['idcliente'])
            ->where('idempresa', $idEmpresa)
            ->one();

        if ($clienteCompleto) {
            $qtdpedidos = PedidoVenda::getQTPedidosClientes($clienteCompleto['celular'], $idEmpresa);
            $cuponsDisponiveis = Cupon::getCuponsDetalheTelefone($clienteCompleto['celular'], $idEmpresa);

            $qtdCupons = count($cuponsDisponiveis);
            $valorTotalCupons = array_sum(array_column($cuponsDisponiveis, 'valor_cupons'));
        }

        // Busca bairro se for entrega (mesmo código original)
        $bairro = null;
        $obspdv = is_array($pedido['obs']) ? $pedido['obs'] : null;
        if (is_array($pedido['obs'])) {
            $entrega = $pedido['obs'];
            $bairro = Bairros::select()->where('idbairro', $entrega['idbairro'] ?? null)->one();
        }

        return [
            'empresa' => $empresa,
            'largura' => $largura,
            'altura' => $altura,
            'pedido' => $pedido,
            'cliente' => $cliente,
            'clienteCompleto' => $clienteCompleto,
            'meiopagamento' => $meiopagamento,
            'cupom' => $cupom,
            'qtdpedidos' => $qtdpedidos,
            'cuponsDisponiveis' => $cuponsDisponiveis,
            'qtdCupons' => $qtdCupons,
            'valorTotalCupons' => $valorTotalCupons,
            'obspdv' => $obspdv,
            'bairro' => $bairro,
            'total' => (float) $pedido['total_pedido']
        ];
    }

    /**
     * Busca dados para impressão de pedidos por categoria
     */
    public static function getPedidoData(int $idEmpresa, int $idPedido): array
    {
        $pedido = PedidoVenda::getPedidoVendas(null, $idEmpresa, $idPedido, null, null)[0];
        
        // Busca todas as categorias relacionadas aos itens
        $categorias = [];
        $categoriaIds = array_unique(array_column($pedido['itens'], 'idcategoria'));
        
        foreach ($categoriaIds as $idCat) {
            $cat = Categoria::select()
                ->where('idcategoria', $idCat)
                ->where('idempresa', $idEmpresa)
                ->one();
            if ($cat) {
                $categorias[$idCat] = $cat;
            }
        }

        return [
            'pedido' => $pedido,
            'categorias' => $categorias
        ];
    }

    /**
     * Busca dados para impressão pendente
     */
    public static function getPendenteData(int $idEmpresa, int $idPedido): array
    {
        $pedidos = PedidoVenda::getPedidoVendas(null, $idEmpresa, $idPedido, null, null);
        
        if (empty($pedidos)) {
            return ['pedidos' => []];
        }

        // Aqui precisa do Printer::vericarImpresao que ainda não existe
        // Por enquanto vou retornar o pedido sem verificação
        $pedido = $pedidos[0];

        // Busca categorias dos itens não impressos
        $categorias = [];
        foreach ($pedido['itens'] as $item) {
            if (!isset($item['impresso']) || !$item['impresso']) {
                if (!isset($categorias[$item['idcategoria']])) {
                    $cat = Categoria::select()
                        ->where('idcategoria', $item['idcategoria'])
                        ->where('idempresa', $idEmpresa)
                        ->where('imprimir', 1)
                        ->one();
                    if ($cat) {
                        $categorias[$item['idcategoria']] = $cat;
                    }
                }
            }
        }

        return [
            'pedido' => $pedido,
            'categorias' => $categorias
        ];
    }

    /**
     * Busca dados para impressão de item específico
     */
    public static function getItemData(int $idEmpresa, int $idPedido, int $idItem): array
    {
        $pedido = PedidoVenda::getPedidoVendas(null, $idEmpresa, $idPedido, null, null)[0];

        $item = array_values(array_filter(
            $pedido['itens'],
            fn($it) => $it['idpedido_item'] == $idItem
        ))[0] ?? null;

        if (!$item) {
            throw new \Exception('Item não encontrado');
        }

        $categoria = Categoria::select()
            ->where('idcategoria', $item['idcategoria'])
            ->where('idempresa', $idEmpresa)
            ->one();

        return [
            'pedido' => $pedido,
            'item' => $item,
            'categoria' => $categoria
        ];
    }
}
