<?php

namespace src\handlers;

use Exception;
use src\models\Empresa;
use src\models\Empresa_parametro;
use src\handlers\PedidoVenda;
use src\models\Categoria;
use src\models\Bairros;
use src\models\Filaprint;
use src\models\Cupon;
use src\models\Pedido_venda;
use src\models\Pessoa;
use src\handlers\Help as help;
use src\handlers\Pagamentos as HandlersPagamentos;
use src\models\Pagamentos;
use src\models\Produto_menu;
use src\models\Tipo_pagamento;

class MontarHTML
{
    private static function wrap(string $txt, int $limite = 26): string
    {
        $txt   = trim($txt);
        $out   = '';
        $line  = '';

        foreach (preg_split('/\s+/', $txt) as $word) {
            if (mb_strlen($word) > $limite) {
                if ($line !== '') {
                    $out .= rtrim($line) . "\n";
                    $line = '';
                }
                $out .= implode("\n", str_split($word, $limite)) . "\n";
                continue;
            }

            if (mb_strlen($line . $word) + 1 > $limite) {
                $out .= rtrim($line) . "\n";
                $line = '';
            }
            $line .= $word . ' ';
        }

        if ($line !== '') {
            $out .= rtrim($line) . "\n";
        }
        return $out;
    }

    private static function moeda($valor): string
    {
        return number_format($valor, 2, ',', '.');
    }

    private static function getMedidas(int $idEmpresa): array
    {
        $medida = Empresa_parametro::select()
            ->where('idempresa', $idEmpresa)
            ->where('idparametro', 10)
            ->one();

        if (!$medida) throw new Exception('Configure largura/altura em empresa_parametro (idparametro 10)');

        [$largura, $altura] = array_map('trim', explode(',', $medida['valor']));
        $empresa = Empresa::select()->where('idempresa', $idEmpresa)->one();

        return ['empresa' => $empresa, 'largura' => $largura, 'altura' => $altura];
    }


    /**
     * na tela esse button RECIBO ok
     */
    public static function recibo(int $idEmpresa, int $idPedido): string
    {
        $dados   = self::getMedidas($idEmpresa);
        $css     = self::css($dados['largura']);
        $empresa = $dados['empresa'];
        $pedido  = PedidoVenda::getPedidoVendas(null, $idEmpresa, $idPedido, null, null)[0];
        $cliente = Pessoa::select()
            ->where('idcliente', $pedido['idcliente'])
            ->where('idempresa', $idEmpresa)
            ->where('nome', '<>', 'venda')
            ->one();

        $meiopagamento = Pagamentos::select('p.descricao')
            ->join('tipo_pagamento as p', 'p.idtipopagamento', '=', 'pagamentos.idtipopagamento')
            ->where('pagamentos.idempresa', $idEmpresa)
            ->where('pagamentos.idpedidovenda', $idPedido)
            ->one();


        $cupom   = Cupon::getCuponsPedido($idEmpresa, $idPedido);
        $qtdpedidos = 0;

        $cuponsDisponiveis = [];
        $qtdCupons = 0;
        $valorTotalCupons = 0;

        $obspdv = is_array($pedido['obs']) ? $pedido['obs'] : null;
        $camposAdicionais = $obspdv['campos_adicionais_checkout'] ?? [];

        $cliente = Pessoa::select()
            ->where('idcliente', $pedido['idcliente'])
            ->where('idempresa', $idEmpresa)
            ->one();
        if ($cliente) {
            $qtdpedidos = PedidoVenda::getQTPedidosClientes($cliente['celular'], $idEmpresa);
            $cuponsDisponiveis = Cupon::getCuponsDetalheTelefone($cliente['celular'], $idEmpresa);

            $qtdCupons = count($cuponsDisponiveis);
            $valorTotalCupons = array_sum(array_column($cuponsDisponiveis, 'valor_cupons'));
        }
        $total = (float) $pedido['total_pedido'];
        $html = $css . '<div class="wrap">';

        // Cabeçalho
        $html .= strtoupper(self::wrap($empresa['nomefantasia']));
        $html .= self::wrap($empresa['endereco'] . ", " . $empresa['numero']);
        $html .= "CNPJ: {$empresa['cnpj']}";
        $html .= "\n==============================\n";

        if (!empty($empresa['dilema'])) {
            $html .= strtoupper(self::wrap($empresa['dilema'])) . "\n";
        }

        $html .= "Nº Pedido: {$idPedido}\n";
        $html .= "Data: " . help::formatarData($pedido['data_pedido'], true) . "\n";
        if ($qtdpedidos > 0) {
            $html .= "QTD Pedidos: {$qtdpedidos}\n";
        }
        $html .= "Cliente: " . strtoupper(self::wrap($pedido['nome']));
        if (!empty($obspdv['celular'])) $html .= "Celular: {$obspdv['celular']}\n";
        if (!empty($pedido['idmesa'])) {
            $html .= "Mesa: {$pedido['idmesa']}\n";
        }

        if (!empty($cuponsDisponiveis)) {
            $html .= "\n=== CUPONS DISPONÍVEIS ===\n";
            $html .= "Disponíveis: {$qtdCupons}\n";
            $html .= "Total : R$ " . self::moeda($valorTotalCupons) . "\n";
        }
        if (is_array($pedido['obs'])) {
            $obsArray = $pedido['obs'];
            // Quando for realmente delivery (metodo_entrega = 1) mostramos bloco detalhado de entrega
            if ((string)$pedido['metodo_entrega'] === '1') {
                $bairro = Bairros::select()->where('idbairro', $obsArray['idbairro'] ?? null)->one();
                $html .= "\n======== ENTREGA =========\n"; // bloco de entrega somente quando metodo_entrega = 1
                if (!empty($obsArray['endereco'])) $html .= self::wrap("Endereço: {$obsArray['endereco']}");
                if (!empty($obsArray['numero']))   $html .= self::wrap("N*: {$obsArray['numero']}");
                if (!empty($obsArray['complemento'])) $html .= self::wrap("Compl: {$obsArray['complemento']}");
                if (!empty($bairro))                   $html .= self::wrap("Bairro: {$bairro['nome']}");
                if (!empty($obsArray['nome_cidade']))  $html .= self::wrap("Cidade: {$obsArray['nome_cidade']}");
                if (!empty($obsArray['obs']))          $html .= "Obs Entrega: " . self::wrap($obsArray['obs']);
            } else {
                // Para retirada / consumo local / salão apenas descrevemos o método e observações gerais
                $html .= "\n==============================\n";
                switch ($pedido['metodo_entrega']) {
                    case '2': // retirada
                        $html .= "RETIRADA NO BALCAO\n";
                        break;
                    case '3': // cliente salão
                        $html .= "CLIENTE SALÃO\n";
                        break;
                    case '4': // consumir local
                        $html .= "CONSUMIR NO LOCAL\n";
                        break;
                    case '1':
                        $html .= "ENTREGA AO CLIENTE\n"; // fallback (não deveria cair aqui)
                        break;
                    default:
                        // nenhum texto adicional
                        break;
                }
                if (!empty($obsArray['obs']))      $html .= "Obs: " . self::wrap($obsArray['obs']);
                if (!empty($obsArray['celular']))  $html .= "Celular: {$obsArray['celular']}\n";
            }
        } else {
            // Obs não é array (caso legado) mantemos comportamento anterior mínimo
            $html .= "\n==============================\n";
            switch ($pedido['metodo_entrega']) {
                case '1':
                    $html .= "ENTREGA AO CLIENTE\n";
                    break;
                case '2':
                    $html .= "RETIRADA NO BALCAO\n";
                    break;
                case '3':
                    $html .= "CLIENTE SALÃO\n";
                    break;
                case '4':
                    $html .= "CONSUMIR NO LOCAL\n";
                    break;
                default:
                    break;
            }
            if (!empty($cliente))       $html .= "Celular: {$cliente['celular']}\n";
            if (!empty($meiopagamento)) $html .= "Pagamento: {$meiopagamento['descricao']}\n";
        }

        if (!empty($camposAdicionais)) {
            $html .= "\n=== CAMPOS ADICIONAIS ===\n";
            foreach ($camposAdicionais as $campo) {
                $label = trim(str_replace('**', '', $campo['label'] ?? ''));
                if ($label === '') continue;
                // Imprime o label seguido de dois pontos
                $html .= self::wrap($label . ':');

                $valorLimpo = (string)($campo['value'] ?? '');
                $valorLimpo = explode('|', $valorLimpo); 

                print_r($valorLimpo);die;
                // Divide somente por pipe e remove entradas vazias
                

                if (empty($valorLimpo)) {
                    $html .= self::wrap('- (sem valor)');
                } else {
                    foreach ($valorLimpo as $parte) {
                        // Cada resposta em sua própria linha com marcador
                        $html .= self::wrap('- ' . $parte);
                    }
                }
                $html .= "\n"; // linha em branco entre campos diferentes
            }
        }

        // Itens
        $i = 1;
        $itotal = count($pedido['itens']);
        foreach ($pedido['itens'] as $it) {

            if ($i == 1) {
                $html .= "\n______________________________\n\n";
            }

            $html .= self::wrap("{$it['quantidade']} - {$it['nome']}");
            if (!empty($it['obs'])) {
                $html .= "Obs: " . self::wrap($it['obs']);
            }

            if (!empty($it['acrescimos'])) {
                $html .= "Adicionais:\n";
                foreach ($it['acrescimos'] as $add) {
                    $html .= self::wrap("  ↳ {$add['quantidade']} * {$add['nome']} ");
                }
            }


            if ($itotal >= 2 && $i != $itotal) $html .= "\n______________________________\n\n";
            $i++;
        }
        $html .= "\n";

        $html .= "\n======= PAGAMENTOS ========\n";
        if (!empty($obspdv['taxa'])) {
            $html .= "Taxa Entrega: R$ " . self::moeda($obspdv['taxa']) . "\n";
        }
        if (!empty($obspdv['troco'])) {
            $troco = floatval(str_replace(',', '.', str_replace('.', '', $obspdv['troco'])));
            $diferenca = ($troco > $total) ? $troco - $total : $total - $troco;
            $html .= "Troco: R$ " . self::moeda($diferenca) . "\n";
        }
        $html .= "\n";
        // Cupons e descontos
        if (!empty($cupom['idscupon'])) {
            $html .= "CUPONS: {$cupom['idscupon']}\n";
            $html .= "DESCONTO: - R$ " . self::moeda($cupom['valor_cupons']) . "\n";
        }

        if (!empty($obspdv['nome_pagamento'])) $html .= self::wrap("Pag.: {$obspdv['nome_pagamento']}");
        $html .= "<strong>TOTAL : R$ " . self::moeda($total) . "</strong>\n";
        $html .= "\n";
        // Rodapé
        $html .= self::wrap("Agradecemos pela sua preferência! Esperamos vê-lo(a) novamente em breve!") . "\n";
        $html .= "\n\n\n\n";
        $html .= '</div>';

        return $html;
    }



    /**
     * na tela esse button FILA PEDIDO ok
     */
    public static function pedido(int $idEmpresa, int $idPedido): array
    {
        $dados    = self::getMedidas($idEmpresa);
        $css      = self::css($dados['largura']);
        $pedido   = PedidoVenda::getPedidoVendas(null, $idEmpresa, $idPedido, null, null)[0];
        $tickets  = [];

        // agrupa itens por categoria
        $porCat = [];
        foreach ($pedido['itens'] as $it) {
            $porCat[$it['idcategoria']][] = $it;
        }

        foreach ($porCat as $idCat => $lista) {
            $cat = Categoria::select()->where('idcategoria', $idCat)->where('idempresa', $idEmpresa)->one();
            if (!$cat || $cat['imprimir'] != 1) continue;

            $html  = $css . '<div class="wrap">';
            $html .= self::wrap("Pedido de(a) {$pedido['nome']}");
            $html .= "Tipo: {$cat['descricao']}\n";

            if ($pedido['origin'] == 2) {
                switch ($pedido['metodo_entrega']) {
                    case '1':
                        $html .= "CLIENTE ENTREGA\n";
                        break;
                    case '2':
                        $html .= "CLIENTE RETIRA\n";
                        break;
                    case '3':
                        $html .= "CLIENTE SALÃO\n";
                        break;
                    case '4':
                        $html .= "CONSUMIR NO LOCAL\n";
                        break;
                    default:
                        $html .= "Mesa: {$pedido['idmesa']}\n";
                        break;
                }
            } elseif ($pedido['idmesa']) {
                $html .= "Mesa: {$pedido['idmesa']}\n";
            }

            $html .= "N Pedido: {$idPedido}\n";
            $html .= "Data: " . help::formatarData($pedido['data_pedido'], true) . "\n";
            $html .= "__________________________________________\n\n";

            foreach ($lista as $it) {
                $html .= self::wrap("{$it['quantidade']} - {$it['nome']}");
                if (!empty($it['obs'])) {
                    $html .= self::wrap("Obs: " . $it['obs']);
                }

                if (!empty($it['acrescimos'])) {
                    $html .= "Adicionais:\n";
                    foreach ($it['acrescimos'] as $add) {
                        $html .= self::wrap("↳ {$add['quantidade']} * {$add['nome']}");
                    }
                }
                $html .= "__________________________________________\n\n";
            }

            $html .= '</div>';

            $qtd = max(1, (int)$cat['quantidade_impressao']);
            for ($i = 0; $i < $qtd; $i++) {
                $tickets[] = $html;
            }
        }

        return $tickets;
    }


    /*
    * na tela esse button impressoes pendentes
    */
    public static function pendente(int $idEmpresa, int $idPedido): array
    {
        // busca o pedido e já sinaliza itens já impressos
        $pedidos = PedidoVenda::getPedidoVendas(null, $idEmpresa, $idPedido, null, null);
        if (empty($pedidos)) {
            return [];
        }
        $pedido = Printer::vericarImpresao($pedidos[0]);

        // pega medidas e CSS
        $dados = self::getMedidas($idEmpresa);
        $css   = self::css($dados['largura']);

        $cliente = $pedido['nome'];
        $mesa    = $pedido['idmesa'] ?? '';

        // agrupa apenas os itens que ainda não foram impressos
        $itensPorCategoria = [];
        foreach ($pedido['itens'] as $item) {
            if ($item['impresso']) {
                continue;
            }
            $itensPorCategoria[$item['idcategoria']][] = $item;
        }

        if (empty($itensPorCategoria)) {
            Filaprint::update([
                'status' => 2 // 2 = impresso
                ,
                'data_impresso' => date('Y-m-d H:i:s')
            ])
                ->where('idempresa', $idEmpresa)
                ->where('idpedidovenda', $idPedido)
                ->where('status', 1)
                ->execute();
            return []; // não há itens pendentes
        }

        $htmls = [];
        // para cada categoria, monta um HTML distinto
        foreach ($itensPorCategoria as $idCategoria => $itens) {
            $categoria = Categoria::select()
                ->where('idcategoria', $idCategoria)
                ->where('idempresa',  $idEmpresa)
                ->where('imprimir',   1)
                ->one();

            if (empty($categoria)) {
                continue;
            }

            $html  = $css . '<div class="wrap">';
            $html .= self::wrap("Pedido de(a) {$cliente}");
            $html .= self::wrap("N Pedido: {$idPedido}");
            $html .= self::wrap("Data: " . help::formatarData($pedido['data_pedido'], true));

            // tipo de entrega / mesa
            if ($pedido['origin'] == 2) {
                switch ($pedido['metodo_entrega']) {
                    case '1':
                        $html .= self::wrap("CLIENTE ENTREGA");
                        break;
                    case '2':
                        $html .= self::wrap("CLIENTE RETIRA");
                        break;
                    case '3':
                        $html .= self::wrap("CLIENTE SALÃO");
                        break;
                    case '4':
                        $html .= self::wrap("CONSUMIR NO LOCAL");
                        break;
                    default:
                        $html .= self::wrap("Mesa: {$mesa}");
                        break;
                }
            } else {
                $html .= self::wrap("Mesa: {$mesa}");
            }

            // descrição da categoria
            $html .= self::wrap("Tipo: {$categoria['descricao']}");

            // itens e adicionais
            foreach ($itens as $item) {
                $html .= "__________________________________________\n";
                $html .= self::wrap("{$item['quantidade']} - {$item['nome']}");

                if (!empty($item['obs'])) {
                    $html .= self::wrap("Obs: {$item['obs']}");
                }

                if (!empty($item['acrescimos'])) {
                    $html .= self::wrap("Adicionais:");
                    foreach ($item['acrescimos'] as $add) {
                        $html .= self::wrap("↳ {$add['quantidade']} * {$add['nome']}");
                    }
                }
                $html .= "\n";
            }

            $html .= '</div>';

            $quantidade_impressao = $categoria['quantidade_impressao'] ?? 1;
            for ($i = 0; $i < $quantidade_impressao; $i++) {
                $htmls[] = $html;
            }
        }

        Filaprint::update([
            'status' => 2 // 2 = impresso
            ,
            'data_impresso' => date('Y-m-d H:i:s')
        ])
            ->where('idempresa', $idEmpresa)
            ->where('idpedidovenda', $idPedido)
            ->where('status', 1)
            ->execute();

        return $htmls;
    }


    /**
     * na tela esse button FILA ITEM ok
     */
    public static function pedidoItem(int $idEmpresa, int $idPedido, int $idItem): string
    {
        $htmls = [];
        $dados  = self::getMedidas($idEmpresa);
        $css    = self::css($dados['largura']);
        $pedido = PedidoVenda::getPedidoVendas(null, $idEmpresa, $idPedido, null, null)[0];

        $item = array_values(array_filter(
            $pedido['itens'],
            fn($it) => $it['idpedido_item'] == $idItem
        ))[0] ?? null;

        if (!$item) throw new Exception('Item não encontrado');

        $cat = Categoria::select()
            ->where('idcategoria', $item['idcategoria'])
            ->where('idempresa', $idEmpresa)
            ->one();

        $html  = $css . '<div class="wrap">';
        $html .= "Pedido de(a)\n";
        $html .= self::wrap($pedido['nome']) . "\n\n";
        $html .= "Tipo: {$cat['descricao']}\n";
        $html .= "N* PV: {$idPedido}\n\n";
        $html .= "Data: " . help::formatarData($pedido['data_pedido'], true) . "\n";

        if ($pedido['origin'] == 2) {
            switch ($pedido['metodo_entrega']) {
                case '1':
                    $html .= "CLIENTE ENTREGA\n";
                    break;
                case '2':
                    $html .= "CLIENTE RETIRA\n";
                    break;
                case '3':
                    $html .= "CLIENTE SALÃO\n";
                    break;
                case '4':
                    $html .= "CONSUMIR NO LOCAL\n";
                    break;
                default:
                    $html .= "Mesa: {$pedido['idmesa']}\n";
                    break;
            }
        } elseif ($pedido['idmesa']) {
            $html .= "Mesa: {$pedido['idmesa']}\n";
        }


        $html .= "__________________________________________\n\n";

        $html .= self::wrap("{$item['quantidade']} - {$item['nome']}");

        if (!empty($item['obs'])) {
            $html .= self::wrap("Obs: " . $item['obs']);
        }

        if (!empty($item['acrescimos'])) {
            $html .= "Adicionais:\n";
            foreach ($item['acrescimos'] as $add) {
                $html .= self::wrap("↳ {$add['quantidade']} * {$add['nome']}");
            }
        }

        $html .= "__________________________________________\n";
        $html .= '</div>';

        $quantidade_impressao = $categoria['quantidade_impressao'] ?? 1;
        for ($i = 0; $i < $quantidade_impressao; $i++) {
            $htmls[] = $html;
        }
        return $html;
    }

    
    private static function css(string $larguraMm): string
    {
        return <<<CSS
            <style>
                @page {
                    size: {$larguraMm}mm auto;
                    margin: 0;
                    padding: 0;
                }
                body {
                    font-family: monospace;
                    font-size: 12pt;
                    margin: 0;
                    padding: 0;
                    white-space: pre-wrap;
                    line-height: 1.05;
                    font-weight: bold;
                }
                .titulo {
                    text-align: center;
                    font-weight: bold;
                    font-size: 12pt;
                    margin: 6px 0;
                }
                .total {
                    text-align: center;
                    font-weight: bold;
                    font-size: 13pt;
                    margin: 8px 0;
                }
                .linha {
                    border-bottom: 2px dashed #000;
                    margin: 0px 0;
                }
                .wrap {
                    width: 100%;
                }
            </style>
        CSS;
    }
}
