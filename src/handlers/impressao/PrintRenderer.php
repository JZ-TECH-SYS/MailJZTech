<?php

namespace src\handlers\impressao;

use Exception;
use src\models\Empresa;
use src\models\Empresa_parametro;
use src\handlers\PedidoVenda;
use src\models\Categoria;
use src\models\Bairros;
use src\models\Filaprint;
use src\models\Cupon;
use src\models\Pessoa;
use src\handlers\Help as help;
use src\models\Pagamentos;
use src\handlers\Printer;
use src\handlers\impressao\TemplateEngine;
use src\handlers\QRcode as QRHelper;

class PrintRenderer
{
    const LARGURA_PADRAO = 29;

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
        if (is_string($valor)) {
            $valor = floatval(str_replace(',', '.', $valor));
        }
        $resultado = number_format($valor, 2, ',', '.');
        return trim(preg_replace('/[^\d,.]/', '', $resultado));
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

    public static function recibo(int $idEmpresa, int $idPedido): array
    {
        $dados   = self::getMedidas($idEmpresa);
        $css     = self::css($dados['largura'], $idEmpresa);
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


        // Normaliza obs: pode vir array, string JSON ou vazio
        $obspdv = self::parseObs($pedido['obs'] ?? null);

        // GEO
        [$lat, $lng, $mapsUrl] = self::extractGeo($obspdv, $idEmpresa);
        $geoQrDataUri = !empty($mapsUrl) ? QRHelper::dataUriFromText($mapsUrl) : '';

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

        $templateData = [
            'css' => $css,
            'empresa_nome' => strtoupper(self::wrap($empresa['nomefantasia'])),
            'empresa_endereco' => self::wrap(($empresa['endereco'] ?? '') . (isset($empresa['numero']) ? ", " . $empresa['numero'] : '')),
            'empresa_cnpj' => $empresa['cnpj'] ?? '',
            'empresa_dilema' => !empty($empresa['dilema']) ? strtoupper(self::wrap($empresa['dilema'])) . "\n" : '',
            'numero_pedido' => $idPedido,
            'data_pedido' => help::formatarData($pedido['data_pedido'], true),
            'qtd_pedidos' => $qtdpedidos > 0 ? "QTD Pedidos: {$qtdpedidos}" : '',
            'cliente_nome' => $pedido['nome'] ?? 'VENDA',
            'celular' => !empty($obspdv['celular']) ? "Celular: {$obspdv['celular']}" : '',
            'mesa' => !empty($pedido['idmesa']) ? "Mesa: {$pedido['idmesa']}\n" : '',
            'cupons_disponiveis' => self::formatCuponsDisponiveis($cuponsDisponiveis, $qtdCupons, $valorTotalCupons),
            'secao_entrega' => self::formatSecaoEntrega($pedido, $cliente, $meiopagamento),
            'campos_adionais' => self::formatCamposAdionais($obspdv['campos_adicionais_checkout'] ?? []),
            'itens' => self::formatItens($pedido['itens']),
            'taxa' => !empty($obspdv['taxa']) ? self::formatPagamentoLinha("Taxa Entrega", self::moeda($obspdv['taxa'])) : '',
            'troco' => self::formatTrocoAlinhado($obspdv, $total),
            'cupons_desconto' => self::formatCuponsDescontoAlinhado($cupom),
            'pagamento' => !empty($obspdv['nome_pagamento']) ? self::formatLinhaAlinhada("Pagamento", $obspdv['nome_pagamento']) : '',
            'total' => self::formatTotalAlinhado(self::moeda($total)),
            'geo_qr' => $geoQrDataUri,
            'geo_coords' => (!empty($lat) && !empty($lng)) ? ("Lat: " . $lat . " | Lng: " . $lng) : ''
        ];

        $html = TemplateEngine::render('recibo', $templateData);
        return [[
            'texto' => $html,
            'impressora' => null
        ]];
    }

    /**
     * Normaliza o campo obs que pode vir como array, string JSON ou vazio
     */
    private static function parseObs($obs): array
    {
        if (empty($obs)) return [];
        if (is_array($obs)) return $obs;
        if (is_string($obs)) {
            $trim = trim($obs);
            if ($trim !== '' && ($trim[0] === '{' || $trim[0] === '[')) {
                $decoded = json_decode($trim, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return $decoded;
                }
            }
        }
        return [];
    }

    private static function extractGeo(?array $obs, int $idEmpresa): array
    {

        $isHabilitaGeo = Empresa_parametro::select()
            ->where('idempresa', $idEmpresa)
            ->where('idparametro', 17)
            ->one();

        $isHabilitaGeo = help::toBool($isHabilitaGeo['valor'] ?? false);
        if (empty($obs) || !is_array($obs) || !$isHabilitaGeo) {
            return [null, null, null];
        }

        // Nível raiz: aceita lat/lng ou latitude/longitude
        $lat = $obs['lat'] ?? $obs['latitude'] ?? null;
        $lng = $obs['lng'] ?? $obs['longitude'] ?? null;
        $mapsUrl = $obs['mapsUrl'] ?? $obs['maps_url'] ?? null;

        // Bloco 'geo'
        if ((!$lat || !$lng) && isset($obs['geo']) && is_array($obs['geo'])) {
            $g = $obs['geo'];
            $lat = $lat ?: ($g['lat'] ?? $g['latitude'] ?? null);
            $lng = $lng ?: ($g['lng'] ?? $g['longitude'] ?? null);
            $mapsUrl = $mapsUrl ?: ($g['mapsUrl'] ?? $g['maps_url'] ?? null);
        }
        // Bloco 'geolocation'
        if ((!$lat || !$lng) && isset($obs['geolocation']) && is_array($obs['geolocation'])) {
            $g = $obs['geolocation'];
            $lat = $lat ?: ($g['lat'] ?? $g['latitude'] ?? null);
            $lng = $lng ?: ($g['lng'] ?? $g['longitude'] ?? null);
            $mapsUrl = $mapsUrl ?: ($g['mapsUrl'] ?? $g['maps_url'] ?? null);
        }

        // Se não veio URL, monta com base nas coordenadas
        if (!$mapsUrl && $lat && $lng) {
            $mapsUrl = 'https://www.google.com/maps?q=' . $lat . ',' . $lng;
        }

        return [$lat, $lng, $mapsUrl];
    }

    private static function formatCamposAdionais($campos_adicionais_checkout): string
    {
        if (empty($campos_adicionais_checkout) || !is_array($campos_adicionais_checkout)) {
            return '';
        }
        $html = '';
        foreach ($campos_adicionais_checkout as $campo) {
            if (!isset($campo['label'])) continue;
            $label = trim(str_replace('**', '', (string)$campo['label']));
            if ($label === '') continue;

            $valorOriginal = isset($campo['value']) ? (string)$campo['value'] : '';
            $valorLimpo    = str_replace('**', '', $valorOriginal);
            $partes = array_filter(array_map('trim', explode('|', $valorLimpo)), fn($v) => $v !== '');

            $html .= self::wrap($label . ':');
            if (empty($partes)) {
                $html .= self::wrap('- (sem valor)');
            } else {
                foreach ($partes as $parte) {
                    $html .= self::wrap(' ' . $parte);
                }
            }
            $html .= "\n";
        }
        return $html;
    }

    private static function formatCuponsDisponiveis($cuponsDisponiveis, $qtdCupons, $valorTotalCupons): string
    {
        if (empty($cuponsDisponiveis)) {
            return '';
        }
        return "=== CUPONS DISPONÍVEIS ===\n" .
            "Disponíveis: {$qtdCupons}\n" .
            "Total : R$ " . self::moeda($valorTotalCupons) . "\n";
    }

    private static function formatSecaoEntrega($pedido, $cliente, $meiopagamento): string
    {
        $obs = self::parseObs($pedido['obs'] ?? null);
        $metodo = (int)($pedido['metodo_entrega'] ?? ($obs['metodo_entrega'] ?? 0));

        // Detecta se existem dados de entrega mesmo quando não for metodo 1
        $hasEntregaDados = false;
        foreach (['endereco', 'numero', 'complemento', 'cep', 'idbairro', 'nome_bairro', 'nome_cidade'] as $k) {
            if (!empty($obs[$k])) {
                $hasEntregaDados = true;
                break;
            }
        }

        $html = "\n==============================\n";

        // Quando houver dados de entrega em obs, sempre exibe o bloco de entrega
        if ($metodo === 1 || $hasEntregaDados) {
            $html .= "======== DADOS DE ENTREGA =========\n";
            if (!empty($obs['cep']))         $html .= self::wrap("CEP: {$obs['cep']}");
            if (!empty($obs['endereco']))    $html .= self::wrap("Endereço: {$obs['endereco']}");
            if (!empty($obs['numero']))      $html .= self::wrap("N* : {$obs['numero']}");
            if (!empty($obs['complemento'])) $html .= self::wrap("Compl: {$obs['complemento']}");

            if (!empty($obs['idbairro'])) {
                $bairro = Bairros::select()->where('idbairro', $obs['idbairro'])->one();
                if (!empty($bairro)) $html .= self::wrap("Bairro: {$bairro['nome']}");
            } elseif (!empty($obs['nome_bairro'])) {
                $html .= self::wrap("Bairro: {$obs['nome_bairro']}");
            }
            if (!empty($obs['nome_cidade'])) $html .= self::wrap("Cidade: {$obs['nome_cidade']}");
            if (!empty($obs['obs']))         $html .= "Obs Entrega: " . self::wrap($obs['obs']);
        }

        // Exibe o rótulo do método (balcão/salão/local) quando aplicável
        if ($metodo > 0) {
            switch ($metodo) {
                case 1:
                    $html .= "ENTREGA AO CLIENTE\n";
                    break;
                case 2:
                    $html .= "RETIRADA NO BALCAO\n";
                    break;
                case 3:
                    $html .= "CLIENTE SALÃO\n";
                    break;
                case 4:
                    $html .= "CONSUMIR NO LOCAL\n";
                    break;
                default:
                    break;
            }
        }

        if (!empty($cliente))       $html .= "Celular: {$cliente['celular']}\n";
        if (!empty($meiopagamento)) $html .= "Pagamento: {$meiopagamento['descricao']}\n";
        return $html;
    }

    private static function formatItens($itens): string
    {
        $html = '';
        $i = 1;
        $itotal = count($itens);
        foreach ($itens as $it) {
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
        return $html;
    }

    private static function formatPagamentoLinha(string $descricao, string $valor): string
    {
        $larguraTotal = self::LARGURA_PADRAO;
        $separador = ": ";
        $valorFormatado = "R$ " . $valor;
        $espacoDescricao = $larguraTotal - strlen($valorFormatado);
        $textoEsquerda = $descricao . $separador;
        if (strlen($textoEsquerda) > $espacoDescricao - 1) {
            $textoEsquerda = substr($textoEsquerda, 0, $espacoDescricao - 4) . "...";
        }
        $espacosNecessarios = $larguraTotal - strlen($textoEsquerda) - strlen($valorFormatado);
        $espacos = str_repeat(' ', max(1, $espacosNecessarios));
        return $textoEsquerda . $espacos . $valorFormatado;
    }

    private static function formatTrocoAlinhado($obspdv, $total): string
    {
        if (empty($obspdv['troco'])) {
            return '';
        }
        $troco = floatval(str_replace(',', '.', str_replace('.', '', $obspdv['troco'])));
        $diferenca = ($troco > $total) ? $troco - $total : $total - $troco;
        $html = '';
        $html .= self::formatPagamentoLinha("Troco", self::moeda((float)$diferenca)) . "\n";
        $html .= self::formatPagamentoLinha("A Receber", self::moeda((float)$troco));
        return $html;
    }

    private static function formatCuponsDescontoAlinhado($cupom): string
    {
        if (empty($cupom['idscupon'])) {
            return '';
        }
        return self::formatLinhaAlinhada("DESCONTO", "- R$ " . self::moeda($cupom['valor_cupons']));
    }

    private static function formatLinhaAlinhada(string $descricao, string $valor): string
    {
        $larguraTotal = self::LARGURA_PADRAO;
        $separador = ": ";
        $espacoDescricao = $larguraTotal - strlen($valor);
        $textoEsquerda = $descricao . $separador;
        if (strlen($textoEsquerda) > $espacoDescricao - 1) {
            $textoEsquerda = substr($textoEsquerda, 0, $espacoDescricao - 4) . "...";
        }
        $espacosNecessarios = $larguraTotal - strlen($textoEsquerda) - strlen($valor);
        $espacos = str_repeat(' ', max(1, $espacosNecessarios));
        return $textoEsquerda . $espacos . $valor;
    }

    private static function formatTotalAlinhado(string $valor): string
    {
        $larguraTotal = self::LARGURA_PADRAO;
        $textoEsquerda = "TOTAL :";
        $valorFormatado = "R$ " . $valor;
        $espacosNecessarios = $larguraTotal - strlen($textoEsquerda) - strlen($valorFormatado);
        $espacos = str_repeat(' ', max(1, $espacosNecessarios));
        return $textoEsquerda . $espacos . $valorFormatado;
    }

    public static function pedido(int $idEmpresa, int $idPedido): array
    {
        $dados = self::getMedidas($idEmpresa);
        $css = self::css($dados['largura'], $idEmpresa);
        $pedido = PedidoVenda::getPedidoVendas(null, $idEmpresa, $idPedido, null, null)[0];
        // Normaliza obs para uso no ticket de produção
        $obsTicket = self::parseObs($pedido['obs'] ?? null);
        $tickets = [];
        $porCat = [];
        foreach ($pedido['itens'] as $it) {
            $porCat[$it['idcategoria']][] = $it;
        }
        foreach ($porCat as $idCat => $lista) {
            $cat = Categoria::select()->where('idcategoria', $idCat)->where('idempresa', $idEmpresa)->one();
            if (!$cat || $cat['imprimir'] != 1) continue;
            $templateData = [
                'css' => $css,
                'cliente_nome' => self::wrap("Pedido de(a) {$pedido['nome']}"),
                'categoria_descricao' => $cat['descricao'],
                'metodo_entrega' => self::formatMetodoEntregaPedido($pedido),
                'numero_pedido' => $idPedido,
                'data_pedido' => help::formatarData($pedido['data_pedido'], true),
                'itens' => self::formatItensPedido($lista)
            ];
            $html = TemplateEngine::render('pedido', $templateData);
            $nome_impressora = $cat['nome_impresora'] ?? null;
            $qtd = max(1, (int)$cat['quantidade_impressao']);
            for ($i = 0; $i < $qtd; $i++) {
                $tickets[] = [
                    'texto' => $html,
                    'impressora' => $nome_impressora
                ];
            }
        }
        return $tickets;
    }

    private static function formatMetodoEntregaPedido($pedido): string
    {
        if (($pedido['origin'] ?? null) == 2) {
            switch ($pedido['metodo_entrega'] ?? null) {
                case '1':
                    return "CLIENTE ENTREGA\n";
                case '2':
                    return "CLIENTE RETIRA\n";
                case '3':
                    return "CLIENTE SALÃO\n";
                case '4':
                    return "CONSUMIR NO LOCAL\n";
                default:
                    return "Mesa: {$pedido['idmesa']}\n";
            }
        } elseif (!empty($pedido['idmesa'])) {
            return "Mesa: {$pedido['idmesa']}\n";
        }
        return '';
    }

    private static function formatItensPedido($lista): string
    {
        $html = '';
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
        return $html;
    }

    public static function pendente(int $idEmpresa, int $idPedido): array
    {
        $pedidos = PedidoVenda::getPedidoVendas(null, $idEmpresa, $idPedido, null, null);
        if (empty($pedidos)) {
            return [];
        }
        $pedido = Printer::vericarImpresao($pedidos[0]);
        $dados = self::getMedidas($idEmpresa);
        $css = self::css($dados['largura'], $idEmpresa);
        $cliente = $pedido['nome'];
        $mesa = $pedido['idmesa'] ?? '';
        $itensPorCategoria = [];
        foreach ($pedido['itens'] as $item) {
            if ($item['impresso']) {
                continue;
            }
            $itensPorCategoria[$item['idcategoria']][] = $item;
        }
        if (empty($itensPorCategoria)) {
            Filaprint::update([
                'status' => 2,
                'data_impresso' => date('Y-m-d H:i:s')
            ])
                ->where('idempresa', $idEmpresa)
                ->where('idpedidovenda', $idPedido)
                ->where('status', 1)
                ->execute();
            return [];
        }
        $htmls = [];
        foreach ($itensPorCategoria as $idCategoria => $itens) {
            $categoria = Categoria::select()
                ->where('idcategoria', $idCategoria)
                ->where('idempresa', $idEmpresa)
                ->where('imprimir', 1)
                ->one();
            if (empty($categoria)) {
                continue;
            }
            $templateData = [
                'css' => $css,
                'cliente_nome' => self::wrap("Pedido de(a) {$cliente}"),
                'numero_pedido' => self::wrap("N Pedido: {$idPedido}"),
                'data_pedido' => self::wrap("Data: " . help::formatarData($pedido['data_pedido'], true)),
                'metodo_entrega' => self::formatMetodoEntregaPendente($pedido, $mesa),
                'categoria_descricao' => self::wrap("Tipo: {$categoria['descricao']}"),
                'itens' => self::formatItensPendente($itens)
            ];
            $html = TemplateEngine::render('pendente', $templateData);
            $quantidade_impressao = $categoria['quantidade_impressao'] ?? 1;
            $nome_impressora = $categoria['nome_impresora'] ?? null;
            for ($i = 0; $i < $quantidade_impressao; $i++) {
                $htmls[] = [
                    'texto' => $html,
                    'impressora' => $nome_impressora
                ];
            }
        }
        Filaprint::update([
            'status' => 2,
            'data_impresso' => date('Y-m-d H:i:s')
        ])
            ->where('idempresa', $idEmpresa)
            ->where('idpedidovenda', $idPedido)
            ->where('status', 1)
            ->execute();
        return $htmls;
    }

    private static function formatMetodoEntregaPendente($pedido, $mesa): string
    {
        if (($pedido['origin'] ?? null) == 2) {
            switch ($pedido['metodo_entrega'] ?? null) {
                case '1':
                    return self::wrap("CLIENTE ENTREGA");
                case '2':
                    return self::wrap("CLIENTE RETIRA");
                case '3':
                    return self::wrap("CLIENTE SALÃO");
                case '4':
                    return self::wrap("CONSUMIR NO LOCAL");
                default:
                    return self::wrap("Mesa: {$mesa}");
            }
        } else {
            return self::wrap("Mesa: {$mesa}");
        }
    }

    private static function formatItensPendente($itens): string
    {
        $html = '';
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
        return $html;
    }

    public static function pedidoItem(int $idEmpresa, int $idPedido, int $idItem): array
    {
        $dados = self::getMedidas($idEmpresa);
        $css = self::css($dados['largura'], $idEmpresa);
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
        $templateData = [
            'css' => $css,
            'cliente_nome' => self::wrap($pedido['nome']),
            'categoria_descricao' => $cat['descricao'],
            'numero_pedido' => $idPedido,
            'data_pedido' => help::formatarData($pedido['data_pedido'], true),
            'metodo_entrega' => self::formatMetodoEntregaItem($pedido),
            'item_detalhes' => self::formatItemDetalhes($item)
        ];
        $html = TemplateEngine::render('item', $templateData);
        $nome_impressora = $cat['nome_impresora'] ?? null;
        $quantidade_impressao = $cat['quantidade_impressao'] ?? 1;
        $tickets = [];
        for ($i = 0; $i < $quantidade_impressao; $i++) {
            $tickets[] = [
                'texto' => $html,
                'impressora' => $nome_impressora
            ];
        }
        return $tickets;
    }

    private static function formatMetodoEntregaItem($pedido): string
    {
        if (($pedido['origin'] ?? null) == 2) {
            switch ($pedido['metodo_entrega'] ?? null) {
                case '1':
                    return "CLIENTE ENTREGA\n";
                case '2':
                    return "CLIENTE RETIRA\n";
                case '3':
                    return "CLIENTE SALÃO\n";
                case '4':
                    return "CONSUMIR NO LOCAL\n";
                default:
                    return "Mesa: {$pedido['idmesa']}\n";
            }
        } elseif (!empty($pedido['idmesa'])) {
            return "Mesa: {$pedido['idmesa']}\n";
        }
        return '';
    }

    private static function formatItemDetalhes($item): string
    {
        $html = self::wrap("{$item['quantidade']} - {$item['nome']}");
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
        return $html;
    }

    private static function css(string $larguraMm, int $idEmpresa): string
    {
        $margon = 0;
        if ($idEmpresa == 14) {
            $margon = 20;
        }
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
                        margin: 0 0 0 {$margon};
                        padding: 0;
                        white-space: pre;
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
