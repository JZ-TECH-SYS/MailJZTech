<?php

/**
 * Classe responsável por fazer a conexão com o banco de dados
 * e executar queries SQL utilizando a biblioteca Hydrahon
 *
 * @autor: joaosn
 * @dateInicio: 23/05/2023
 */

namespace src\handlers;

use src\models\Pedido_venda as PedidoVendaModel;
use src\models\Pedido_venda_item as PedidoVendaItemModel;
use src\models\Pedido_venda_item_acrescimos as PedidoVendaItemAcrescimosModel;
use src\models\Saldo_produto as SaldoProdutoModel;
use src\models\Produtos as ProdutosModel;
use src\models\Pagamentos as PagamentosModel;
use src\handlers\Pagamentos as PagamentosHandler;
use ClanCats\Hydrahon\Query\Sql\Func as F;

use src\handlers\Pessoa;
use src\handlers\Produtos as ProdutoHandler;
use core\Controller as ctrl;
use Exception;
use core\Database as db;
use PDOException;
use src\controllers\EmailController;
use src\handlers\service\EmailService;
use src\handlers\service\GoogleMapsService;
use src\models\Bairros;
use src\models\Cidade;
use src\models\Cupon;
use src\models\Cupon_pedidos;
use src\models\Pessoa as ModelsPessoa;
use src\models\Produto_parametro;
use src\models\Tipo_pagamento;
use src\models\Tipo_produto;
use src\models\Empresa;
use src\handlers\service\MyZap;
use src\models\Cliente_localizacao_cache;
use src\models\Estado;

class PedidoVenda
{
    /**
     * desc: retorna todos os pedidos de uma empresa ou apenas um pedido
     * responsável por montar o array de pedidos de venda com seus itens e acréscimos
     * @param int $idsituacao
     * @param int $idempresa
     * @param int|null $idpedidovenda
     * @return array
     */
    public static function getPedidoVendas($idsituacao, $idempresa, $idpedidovenda, $origin = 1)
    {
        $idpedidovenda = isset($idpedidovenda) && !empty($idpedidovenda) ? $idpedidovenda : null;
        $idsituacao = isset($idsituacao) && !empty($idsituacao) ? $idsituacao : null;

        $estruturaPedidos = [];
        $pedidoVenda = self::getPedidos($idempresa, $idsituacao, $idpedidovenda, $origin);

        $pedidoVendaItem  = self::getPedidoItem($idempresa, $idsituacao, $idpedidovenda);
        $pedidoVendaItemAcrescimos = self::getPedidoItemAcrescimos($idempresa, $idsituacao, $idpedidovenda);
        $estruturaPedidos = array_map(function ($pedido) use ($pedidoVendaItem, $pedidoVendaItemAcrescimos) {

            if (!empty($pedido['obs']) &&  json_decode($pedido['obs'], true) != null) {
                $pedido['obs'] = json_decode($pedido['obs'], true);
                $taxa        = Bairros::select()->where('idempresa', $pedido['idempresa'])->where('idbairro', $pedido['obs']['idbairro'] ?? 0)->one();
                $cidade      = Cidade::select()->where('id', $pedido['obs']['idcidade'] ?? 0)->one();
                $pagamentos  = null;
                if (isset($pedido['obs']['metodo_pagamento'])) {
                    $pagamentos = Tipo_pagamento::select()->where('idempresa', $pedido['idempresa'])->where('idtipopagamento', $pedido['obs']['metodo_pagamento'])->one();
                }

                $pedido['obs']['taxa']           = $taxa['taxa'] ?? 0;
                $pedido['obs']['nome_bairro']    = $taxa['nome'] ?? '';
                $pedido['obs']['nome_cidade']    = $cidade['nome'] ?? '';
                $pedido['obs']['nome_pagamento'] = $pagamentos['descricao'] ?? '';
            }

            $itensFiltrados = self::filtrarItensPedido($pedidoVendaItem, $pedido['idpedidovenda']);
            $cupon = Cupon::getCuponsPedido($pedido['idempresa'], $pedido['idpedidovenda']);
            $itens = array_map(function ($item) use ($pedidoVendaItemAcrescimos) {
                $itensGratis          =  Produto_parametro::select()->where('idproduto', $item['idproduto'])->where('idempresa', $item['idempresa'])->where('idparametro', 2)->one();
                $item['itens_gratis'] = (isset($itensGratis['valor'])) ? (int)$itensGratis['valor'] : 0;
                $acrescimos = self::filtrarAcrescimos($pedidoVendaItemAcrescimos, $item['idpedidovenda'], $item['idpedido_item']);
                $item['acrescimos'] = array_values($acrescimos);
                return $item;
            }, $itensFiltrados);

            $pedido['itens'] = array_values($itens);
            $pedido['cupon'] = $cupon;

            $pedido['total_pedido'] = self::calculaTotal($pedido['itens']);
            if (!empty($pedido['obs']['taxa'])) {
                $pedido['total_pedido'] += $pedido['obs']['taxa'];
            }
            if (isset($cupon['valor_cupons']) && $cupon['valor_cupons'] > 0) {
                $pedido['total_pedido'] -= $cupon['valor_cupons'];
                $pedido['valor_cupons_aplicados'] = $cupon['valor_cupons'];
            }

            $pedido['cell'] = Help::getCellPedido($pedido);
            return $pedido;
        }, $pedidoVenda);

        return $estruturaPedidos;
    }


    /**
     * Calcula o total do pedido somando itens e acréscimos, descontando os gratuitos.
     */
    public static function calculaTotal(array $itens): float
    {
        $totalPedido = 0.0;

        foreach ($itens as $item) {
            $totalItem = ($item['quantidade'] ?? 0) * ($item['preco'] ?? 0);

            if (!empty($item['acrescimos'])) {
                foreach ($item['acrescimos'] as $add) {
                    $qtdPaga = ($add['quantidade'] ?? 0) - ($add['gratis'] ?? 0);
                    $totalItem += $qtdPaga * ($add['preco'] ?? 0);
                }
            }

            $totalPedido += $totalItem;
        }

        return $totalPedido;
    }

    /**
     * Adiciona um novo pedido de venda.
     *
     * @param array $data Os dados do pedido a ser adicionado.
     * @param bool $retorno Indica se a função deve retornar os dados atualizados do pedido (true) ou apenas o ID do pedido (false).
     * @return array|int O array de pedidos de venda atualizado ou o ID do pedido adicionado.
     */
    public  static function addPedidoVenda($data, $retorno = true, $ia = false)
    {
        $existePessoa = Pessoa::getPessoaByNomeOrCelular($data['idempresa'], $data['nome'], $data['celular']);
        $existeMesa = self::validaMesa($data['idempresa'], $data['idmesa'],  $data['nome']);
        if (!empty($existeMesa)) {
            throw new Exception('Mesa já está em uso');
        }

        if (
            isset($data['nome'])
            && isset($data['celular'])
            && empty($existePessoa)
            && empty($data['idcliente'])
            && $data['nome'] != 'Venda' //cadro essa pessoa pra todas empresa
        ) {
            $cli = Pessoa::addPessoa($data);
            $data['idcliente'] = $cli['idcliente'];
        }

        $idpessoa = $existePessoa['idcliente'] ?? $data['idcliente'];
        $insert = [
            'nome'                    => $data['nome'],
            'idmesa'                  => $data['idmesa'],
            'idempresa'               => $data['idempresa'],
            'idcliente'               => !empty($idpessoa) ? $idpessoa : null,
            'obs'                     => $data['obs'] ?? $data['observacoes'] ?? '',
            'data_pedido'             => date('Y-m-d H:i:s'),
            'origin'                  => $data['origin'] ?? 1,
            'metodo_entrega'          => $data['metodo_entrega'] ?? 2
        ];

        $idpedido = PedidoVendaModel::insert($insert)->execute();

        $retorno = $retorno ? self::getPedidoVendas(1, $data['idempresa'], $idpedido) : $idpedido;
        return $retorno;
    }

    /**
     * desc: edita um pedido de venda
     * @param array $data
     * @return array
     */
    public static function editPedidoVenda($data, $retorno = true)
    {
        // Busca o pedido a ser editado
        $pedido = self::getPedidoVendas(null, $data['idempresa'], $data['idpedidovenda'], null);
        if (empty($pedido)) {
            throw new Exception('Pedido não encontrado');
        }

        $idpessoa = null;
        if ($pedido[0]['origin'] != 2 && $retorno) {
            $existeMesa = isset($data['idmesa']) ? self::validaMesa($data['idempresa'], $data['idmesa'], $data['nome']) : null;
            if (!empty($existeMesa) && $existeMesa['idpedidovenda'] != $data['idpedidovenda']) {
                throw new Exception('Mesa já está em uso');
            }

            if (isset($data['nome']) && isset($data['celular'])) {
                $existePessoa = Pessoa::getPessoaByNomeOrCelular($data['idempresa'], $data['nome'], $data['celular'] ?? 0);
                if (isset($data['nome']) && isset($data['celular']) && empty($existePessoa) && $existePessoa['idcliente'] != $pedido[0]['idcliente']) {
                    $cli = Pessoa::addPessoa($data);
                    $data['idcliente'] = $cli['idcliente'];
                }

                $idpessoa = $existePessoa['idcliente'] ?? $data['idcliente'] ?? $pedido[0]['idcliente'];
            } else {
                $idpessoa = $data['idcliente'] ?? $pedido[0]['idcliente'];
            }
        }

        // OBS pode vir como array/obj: serializa para JSON. Se não vier no payload, mantém o existente.
        $obs = array_key_exists('obs', $data) ? $data['obs'] : $pedido[0]['obs'];
        if (is_array($obs) || is_object($obs)) {
            $obs = json_encode($obs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (isset($data['idsituacao_pedido_venda']) && $data['idsituacao_pedido_venda'] == 2) {
            $data['data_baixa'] = date('Y-m-d H:i:s');
        }

        // Atualiza os dados do pedido
        // NOTA: total_pedido é calculado automaticamente pela trigger recalcular_total_ao_mudar_situacao
        PedidoVendaModel::update([
            'nome'                    => !empty($data['nome'])                    ? $data['nome']                    : $pedido[0]['nome'],
            'idsituacao_pedido_venda' => !empty($data['idsituacao_pedido_venda']) ? $data['idsituacao_pedido_venda'] : $pedido[0]['idsituacao_pedido_venda'],
            'idmesa'                  => !empty($data['idmesa'])                  ? $data['idmesa']                  : $pedido[0]['idmesa'],
            'idempresa'               => !empty($data['idempresa'])               ? $data['idempresa']               : $pedido[0]['idempresa'],
            'idcliente'               => !empty($idpessoa)                        ? $idpessoa                        : $pedido[0]['idcliente'],
            'data_pedido'             => !empty($data['data_pedido'])             ? $data['data_pedido']             : $pedido[0]['data_pedido'],
            'obs'                     => $obs,
            'data_baixa'              => !empty($data['data_baixa'])              ? $data['data_baixa']              : $pedido[0]['data_baixa']
        ])
            ->where('idempresa', $data['idempresa'])
            ->where('idpedidovenda', $data['idpedidovenda'])
            ->execute();

        // Retorna o pedido atualizado
        $result = $retorno ? self::getPedidoVendas(null, $data['idempresa'], $data['idpedidovenda'], null) : $data['idpedidovenda'];
        if (isset($data['notificar']) && $data['notificar'] == 1) {
            MsgMyzap::notifyStatusChange($data['idempresa'], $data['idpedidovenda'], (int)$data['idsituacao_pedido_venda']);
        }

        return $result;
    }



    /**
     * desc: deleta um pedido de venda 
     * só não vai deletar se o pedido já tiver sido pago 
     * ou tiver pagamentos pendentes ou já feitos
     * @param array $data - informações do pedido a ser deletado
     * @return array - estrutura de pedidos atualizada
     */
    public static function deletePedidoVenda($data)
    {
        $pedido = self::getPedidoVendas(null, $data['idempresa'], $data['idpedidovenda'], null);
        if (empty($pedido)) {
            throw new Exception('Pedido não encontrado');
        }

        $pagamentos = PagamentosModel::select()->where('idempresa', $data['idempresa'])->where('idpedidovenda', $data['idpedidovenda'])->one();
        if (!empty($pagamentos) && count($pagamentos) > 0) {
            throw new Exception('Pedido não pode ser deletado, pois já possui pagamento(s)');
        }

        PedidoVendaModel::delete()
            ->where('idempresa', $data['idempresa'])
            ->where('idpedidovenda', $data['idpedidovenda'])
            ->execute();

        return ['status' => 'ok', 'message' => 'Pedido deletado com sucesso ' . $data['idpedidovenda']];
    }

    /**
     * desc: busca todos os pedidos de venda de uma empresa ou apenas um pedido
     * responsável por montar o array de pedidos de venda com seus itens e acréscimos
     * @param int $idempresa - id da empresa
     * @param int $idsituacao - id da situação do pedido (opcional)
     * @param int $idpedidovenda - id do pedido (opcional)
     * @return array - array com a estrutura de pedidos de venda
     */
    public static function getPedidos($idempresa, $idsituacao, $idpedidovenda, $origin)
    {
        $pedidos = PedidoVendaModel::select()->where('idempresa', $idempresa);
        if ($origin) {
            $pedidos->where('origin', $origin);
        }
        if ($idsituacao) {
            $pedidos->where('idsituacao_pedido_venda', $idsituacao);
        }
        if ($idpedidovenda) {
            $pedidos->where('idpedidovenda', $idpedidovenda);
        }

        if ($idsituacao == 2) {
            $pedidos->where('data_pedido', '>=', date('Y-m-d H:i:s', strtotime('-2 day')));
            $pedidos->where('data_pedido', '<=', date('Y-m-d H:i:s', strtotime('+1 day')));
        }

        return $pedidos->orderBy('idmesa', 'asc')->orderBy('data_pedido', 'desc')->get();
    }


    /**
     * desc: busca todos os itens de um pedido de venda ou apenas um item
     * @param int $idempresa - id da empresa
     * @param int $idsituacao - id da situação do pedido (opcional)
     * @param int $idpedidovenda - id do pedido (opcional)
     * @param int $idpedido_item - id do item do pedido (opcional)
     * @return array - array com os itens do pedido de venda
     */
    public static function getPedidoItem($idempresa, $idsituacao, $idpedidovenda, $idpedido_item = null)
    {

        $pedidoItem = PedidoVendaItemModel::select([
            'pedido_venda_item.idpedido_item',
            'pedido_venda_item.idpedidovenda',
            'pedido_venda_item.idempresa',
            'pedido_venda_item.idproduto',
            'pedido_venda_item.quantidade',
            'pedido_venda_item.obs',
            'p.nome',
            'p.descricao',
            'p.cod_barras',
            'p.tipo_produto',
            'p.idcategoria',
            'p.foto',
        ])
            // coalesce(pedido_venda_item.preco_unitario, p.preco) as preco
            ->addField(new F('coalesce', 'pedido_venda_item.preco_unitario', 'p.preco'), 'preco')
            // idem com o mesmo alias "preco_unitario"
            ->addField(new F('coalesce', 'pedido_venda_item.preco_unitario', 'p.preco'), 'preco_unitario')
            // coalesce(pedido_venda_item.custo_unitario, p.preco_custo, 0) as custo_unitario
            ->addField(new F('coalesce', 'pedido_venda_item.custo_unitario', 'p.preco_custo', 'p.preco'), 'custo_unitario')

            ->innerJoin('produtos as p', function ($join) {
                $join->on('p.idproduto', '=', 'pedido_venda_item.idproduto')
                    ->on('p.idempresa', '=', 'pedido_venda_item.idempresa');
            })
            ->innerJoin('pedido_venda as pv', function ($join) {
                $join->on('pv.idpedidovenda', '=', 'pedido_venda_item.idpedidovenda')
                    ->on('pv.idempresa', '=', 'pedido_venda_item.idempresa');
            })
            ->where('pedido_venda_item.idempresa', $idempresa);

        if ($idsituacao) {
            $pedidoItem->where('pv.idsituacao_pedido_venda', $idsituacao);
        }
        if ($idpedidovenda) {
            $pedidoItem->where('pedido_venda_item.idpedidovenda', $idpedidovenda);
        }
        if ($idpedido_item) {
            $pedidoItem->where('pedido_venda_item.idpedido_item', $idpedido_item);
        }

        $result = $pedidoItem->get();

        return $result;
    }


    /**
     * desc: Retorna os acréscimos de um item de pedido de venda
     * @param int $idempresa - id da empresa
     * @param int $idsituacao - id da situação do pedido de venda
     * @param int $idpedidovenda - id do pedido de venda
     * @param int $idpedido_acrescimo - id do acréscimo do pedido de venda
     * @return array - lista de acréscimos do item de pedido de venda
     */
    public static function getPedidoItemAcrescimos($idempresa, $idsituacao, $idpedidovenda, $idpedido_acrescimo = null)
    {
        $pedidoVendaItemAcrescimo = PedidoVendaItemAcrescimosModel::select([
            'pedido_venda_item_acrescimos.idpedido_acrescimo',
            'pedido_venda_item_acrescimos.idpedido_item',
            'pedido_venda_item_acrescimos.idempresa',
            'pedido_venda_item_acrescimos.idpedidovenda',
            'pedido_venda_item_acrescimos.idproduto',
            'pedido_venda_item_acrescimos.quantidade',
            'p.nome',
            'p.descricao',
            'p.cod_barras',
            'p.tipo_produto',
            'p.idcategoria',
            'p.foto',
            'ig.quantidade as gratis'
        ])
            // coalesce(pedido_venda_item_acrescimos.preco_unitario, p.preco) as preco
            ->addField(new F('coalesce', 'pedido_venda_item_acrescimos.preco_unitario', 'p.preco'), 'preco')
            // idem com o mesmo alias "preco_unitario"
            ->addField(new F('coalesce', 'pedido_venda_item_acrescimos.preco_unitario', 'p.preco'), 'preco_unitario')
            // coalesce(pedido_venda_item_acrescimos.custo_unitario, p.preco_custo, p.preco) as custo_unitario
            ->addField(new F('coalesce', 'pedido_venda_item_acrescimos.custo_unitario', 'p.preco_custo', 'p.preco'), 'custo_unitario')
            ->innerJoin('produtos as p', function ($join) {
                $join->on('p.idproduto', '=', 'pedido_venda_item_acrescimos.idproduto')
                    ->on('p.idempresa', '=', 'pedido_venda_item_acrescimos.idempresa');
            })
            ->innerJoin('pedido_venda as pv', function ($join) {
                $join->on('pv.idpedidovenda', '=', 'pedido_venda_item_acrescimos.idpedidovenda')
                    ->on('pv.idempresa', '=', 'pedido_venda_item_acrescimos.idempresa');
            })
            ->leftJoin('pedido_venda_item_gratis as ig', function ($join) {
                $join->on('ig.idpedidovenda', '=', 'pedido_venda_item_acrescimos.idpedidovenda')
                    ->on('ig.idempresa',     '=', 'pedido_venda_item_acrescimos.idempresa')
                    ->on('ig.iditem',        '=', 'pedido_venda_item_acrescimos.idpedido_acrescimo')
                    ->where('ig.idtipo', '=', 2);
            })
            ->where('p.idempresa', $idempresa);


        if ($idsituacao) {
            $pedidoVendaItemAcrescimo->where('pv.idsituacao_pedido_venda', $idsituacao);
        }

        if ($idpedidovenda) {
            $pedidoVendaItemAcrescimo->where('pv.idpedidovenda', $idpedidovenda);
        }

        if ($idpedido_acrescimo) {
            $pedidoVendaItemAcrescimo->where('pedido_venda_item_acrescimos.idpedido_acrescimo', $idpedido_acrescimo);
        }

        return $pedidoVendaItemAcrescimo->get();
    }


    /**
     * Altera a quantidade de um item ou acréscimo em um pedido de venda, adicionando ou removendo a quantidade especificada.
     *
     * @param array $data Array associativo contendo as informações necessárias para a atualização da quantidade do item ou acréscimo.
     * @param string $data['tabela'] Define se a operação é para um 'pedido_venda_item' ou 'pedido_venda_item_acrescimos'.
     * @param int $data['idempresa'] Identificador da empresa relacionada ao pedido de venda.
     * @param int $data['idpedidovenda'] Identificador do pedido de venda.
     * @param int $data['key'] Identificador do item ou acréscimo a ser alterado.
     * @param string $data['acao'] Define se a ação é 'somar' para adicionar quantidade ou qualquer outro valor para subtrair quantidade.
     *
     * @return int Retorna o identificador do item ou acréscimo atualizado.
     */
    public static function alteraQuantia($data)
    {
        $tabela = ($data['tabela'] === 'pedido_venda_item') ? PedidoVendaItemModel::class : PedidoVendaItemAcrescimosModel::class;
        $key = ($data['tabela'] === 'pedido_venda_item') ? 'idpedido_item' : 'idpedido_acrescimo';
        function getitem($tabela, $key, $data)
        {
            return $tabela::select()
                ->where('idempresa', $data['idempresa'])
                ->where('idpedidovenda', $data['idpedidovenda'])
                ->where($key, $data['value'])
                ->one();
        }

        $item = getitem($tabela, $key, $data);
        ($data['acao'] == 1) ? $item['quantidade']++ : $item['quantidade']--;
        if ($data['acao'] == 1) {
            self::validaEstoque($data['idempresa'], $item['idproduto'], 1);
        }
        if ($item['quantidade'] < 1) {
            throw new Exception('Quantidade não pode ser menor que 1', 400);
        }

        $tabela::update()
            ->set('quantidade', $item['quantidade'])
            ->where('idempresa', $data['idempresa'])
            ->where('idpedidovenda', $data['idpedidovenda'])
            ->where($key, $data['value'])
            ->execute();

        return getitem($tabela, $key, $data);
    }

    /**
     *  desc: Validar se produto tem estoque para venda
     */
    public static function validaEstoque($idempresa, $idproduto, $quantidade)
    {
        //verifica se o controle de estoque esta ativo para a empresa se nao estiver retorna true
        if (!ctrl::validar_saldo()) {
            return true;
        }

        $produto = SaldoProdutoModel::select()->where('idempresa', $idempresa)->where('idproduto', $idproduto)->one();
        if ($produto['quantidade'] < $quantidade) {
            throw new Exception('Produto sem estoque suficiente para venda. ProdutoID:' . $idproduto . '  com Saldo de ' . $produto['quantidade'], 400);
        } else {
            return true;
        }
    }

    /**
     * valida se mesa ja esta em uso
     */
    public static function validaMesa($idempresa, $idmesa, $nome)
    {
        return PedidoVendaModel::select()
            ->where('idempresa', $idempresa)
            ->where('idmesa', $idmesa)
            ->where('nome', $nome)
            ->where('idsituacao_pedido_venda', 1)
            ->one();
    }

    /**
     * Salva ou atualiza cache de localização do cliente (geolocalização + endereço).
     * Prioriza latitude/longitude se fornecidos; caso contrário, usa apenas endereço.
     *
     * @param array $data Dados do pedido completo
     * @param int $idcliente ID do cliente
     * @param array|null $geo Bloco 'geo' do obs (opcional)
     */
    private static function salvarCacheLocalizacaoCliente(array $data, int $idcliente, ?array $geo)
    {
        try {
            $bairro = Bairros::select()->where('idempresa', $data['idempresa'])->where('idbairro', $data['idbairro'] ?? 0)->one();
            $cidade = Cidade::select()->where('id', $data['idcidade'] ?? 0)->one();
            $estado = Estado::select()->where('id', $cidade['uf'] ?? '')->one();
            // Monta endereço completo
            $enderecoCompleto = null;
            if (!empty($data['endereco'])) {
                $partes = [trim($data['endereco'])];
                if (!empty($data['numero']))   $partes[] = trim($data['numero']);
                if (!empty($bairro['nome']))   $partes[] = trim($bairro['nome']);
                if (!empty($cidade['nome']))   $partes[] = trim($cidade['nome']);
                if (!empty($estado['uf']))     $partes[] = trim($estado['uf']);
                if (!empty($data['cep']))      $partes[] = preg_replace('/\D/', '', trim($data['cep']));
                $enderecoCompleto = implode(', ', $partes);
            }

            // Extrai coordenadas: prioriza nível raiz, depois bloco 'geo'
            $latitude  = isset($data['latitude'])  ? (float)$data['latitude']  : (isset($geo['latitude'])  ? (float)$geo['latitude']  : null);
            $longitude = isset($data['longitude']) ? (float)$data['longitude'] : (isset($geo['longitude']) ? (float)$geo['longitude'] : null);

            // Precision source
            $validPrecisionSources = ['geolocation', 'geocode', 'cep', 'manual'];
            $precisionSource = $data['precision_source'] ?? ($geo['precision_source'] ?? 'manual');
            if (!in_array($precisionSource, $validPrecisionSources)) {
                $precisionSource = 'manual';
            }

            // Validated_at: usa do geo se existir, ou data atual se coordenadas presentes
            $validatedAt = null;
            if ($latitude && $longitude) {
                $validatedAt = $geo['validated_at'] ?? date('Y-m-d H:i:s');
            }

            if(empty($longitude) || empty($latitude)){
                //busca no google maps a localização pelo endereço
                //(ex: logradouro, numero, bairro, cidade, uf, cep)
                $geoData = GoogleMapsService::getCoordinatesByAddress($enderecoCompleto);
                if(!empty($geoData)){
                    $latitude = $geoData['latitude'];
                    $longitude = $geoData['longitude'];
                    $precisionSource = 'geocode';
                    $validatedAt = date('Y-m-d H:i:s');
                } 
            }

            $idcache = Cliente_localizacao_cache::saveOrUpdate([
                'endereco_completo' => $enderecoCompleto,
                'cep'               => $data['cep'] ?? null,
                'numero'            => $data['numero'] ?? '',
                'complemento'       => $data['complemento'] ?? null,
                'bairro'            => $bairro['nome'] ?? '',
                'cidade'            => $cidade['nome'] ?? '',
                'uf'                => $estado['uf'] ?? '',
                'latitude'          => $latitude,
                'longitude'         => $longitude,
                'place_id'          => null,
                'precision_source'  => $precisionSource,
                'validated_at'      => $validatedAt
            ]);

            return Cliente_localizacao_cache::select()->where('idcache', $idcache)->one();
        } catch (\Throwable $e) {
            ctrl::log('Erro ao salvar cache de localização do cliente: ' . $e->getMessage());
        }
    }

    /**
     * Insere um pedido de venda completo, incluindo itens e acréscimos.
     *
     * @param array $data Array associativo contendo os dados do pedido, itens e acréscimos.
     * @return array Retorna o pedido de venda completo, incluindo itens e acréscimos, após ser inserido.
     */
    public static function addPedidoVendaCompleto($data)
    {
        try {
            db::getInstance()->beginTransaction();
            // Insere o pedido de venda e obtém o ID do pedido inserido
            $pessoa = Pessoa::addPessoaOnline($data);

            $obsData = [
                'campos_adicionais_checkout' => $data['campos_adicionais_checkout'] ?? [],
                'obs'              => $data['observacoes']
            ];

            // Opcional: persistir geolocalização somente na finalização (se fornecida)
            $validPrecisionSources = ['geolocation', 'geocode', 'cep', 'manual'];
            $precisionSource = $data['precision_source'] ?? 'manual';
            if (!in_array($precisionSource, $validPrecisionSources)) {
                $precisionSource = 'manual';
            }

            $latitude = isset($data['latitude']) ? (float)$data['latitude'] : 0.0;
            $longitude = isset($data['longitude']) ? (float)$data['longitude'] : 0.0;
            $obsData['geo'] = [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'precision_source' => $precisionSource,
                'validated_at' => date('Y-m-d H:i:s')
            ];
            
            // Salva cache de localização do cliente (se entrega), agora com $obsData['geo'] já montado
            $location = null;
            if ((int)($data['metodo_entrega'] ?? 0) === 1 && !empty($pessoa['idcliente'])) {
                $location = self::salvarCacheLocalizacaoCliente($data, $pessoa['idcliente'], $obsData['geo']);
                // Se não havia coordenadas e o cache retornou, preenche de volta no obsData
                if ((empty($obsData['geo']['latitude']) || empty($obsData['geo']['longitude']))
                    && !empty($location['latitude']) && !empty($location['longitude'])) {
                    $obsData['geo']['latitude'] = (float)$location['latitude'];
                    $obsData['geo']['longitude'] = (float)$location['longitude'];
                    if (empty($obsData['geo']['precision_source']) || $obsData['geo']['precision_source'] === 'manual') {
                        $obsData['geo']['precision_source'] = 'geocode';
                    }
                    $obsData['geo']['validated_at'] = date('Y-m-d H:i:s');
                }
            }
            

            if ($data['metodo_entrega'] == 1) {
                $obsData = array_merge($obsData, [
                    'idempresa'        => $data['idempresa'],
                    'nome'             => $data['nome'],
                    'celular'          => $data['celular'],
                    'metodo_entrega'   => $data['metodo_entrega'],
                    'metodo_pagamento' => $data['metodo_pagamento'],
                    'troco'            => $data['troco'],
                    'idcidade'         => $data['idcidade'],
                    'idbairro'         => $data['idbairro'],
                    'endereco'         => $data['endereco'],
                    'numero'           => $data['numero'],
                    'cep'              => $data['cep'] ?? '',
                    'complemento'      => $data['complemento'],
                    'obs'              => $data['observacoes'],
                    'campos_adicionais_checkout' => $data['campos_adicionais_checkout'] ?? []
                ]);
            }

            $data['obs'] = json_encode($obsData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $data['idcliente'] = $pessoa['idcliente'];
            $idpedido = self::addPedidoVenda($data, false);

            // Insere os itens do pedido de venda

            foreach ($data['itens'] as $item) {
                $item['idpedidovenda'] = $idpedido;
                $idpedido_item = PedidoVendaItem::addPedidoVendItem($item, false);
                // Insere os acréscimos do item, se houver
                if (!empty($item['acrescimos'])) {
                    foreach ($item['acrescimos'] as $acrescimo) {
                        $acrescimo['idpedidovenda'] = $idpedido;
                        $acrescimo['idpedido_item'] = $idpedido_item;
                        PedidoVendaItemAcrescimo::addPedidoVendItemAcrescimo($acrescimo, false);
                    }
                }
            }

            Printer::sendPrint([
                'idempresa' => $data['idempresa'],
                'idpedidovenda' => $idpedido,
                'idpedidovendaitem' => (int)$data['idmesa'] > 0 ? 'pedente' : 'recibo',
            ]);

            $valorPedido = self::getPedidoVendas(null, $data['idempresa'], $idpedido, null);
            if (isset($data['metodo_pagamento']) && $data['metodo_pagamento'] != 0) {
                PagamentosHandler::addPagamento([
                    'idpedidovenda'   => $idpedido,
                    'idtipopagamento' => $data['metodo_pagamento'],
                    'idempresa'       => $data['idempresa'],
                    'valor'           => $valorPedido[0]['total_pedido']
                ]);
            }

            if (isset($data['idscupon']) && !empty($data['idscupon'])) {
                $ids = explode(',', $data['idscupon']);
                $getCupons = Cupon::select()->where('idempresa', $data['idempresa'])->whereIn('idcupon', $ids)->execute();

                foreach ($getCupons as $cupon) {
                    Cupon::update([
                        'data_uso' => date('Y-m-d H:i:s')
                    ])
                        ->where('idempresa', $data['idempresa'])
                        ->where('idcupon', $cupon['idcupon'])
                        ->execute();

                    Cupon_pedidos::insert([
                        'idcupon' => $cupon['idcupon'],
                        'idpedidovenda' => $idpedido,
                        'idempresa' => $data['idempresa']
                    ])->execute();
                }
            }

            try {
                MsgMyzap::notifyNewOrder($data['idempresa'], $idpedido);
            } catch (Exception $e) {
                EmailController::notificarMyzapForra($data['idempresa'], $data, $idpedido);
            }
            db::getInstance()->commit();
            return $valorPedido;
        } catch (PDOException $e) {
            db::getInstance()->rollBack();
            $data['erro'] = $e->getMessage();
            ctrl::log($data);
            throw new Exception($e->getMessage());
        } finally {
            Help::validarCuponPendentes();
        }
    }

    /**
     * Filtra os itens de um pedido de venda pelo ID do pedido.
     * 
     * @param array $itens Array com os itens do pedido.
     * @param int $idpedidovenda ID do pedido de venda a ser filtrado.
     * 
     * @return array Array com os itens filtrados.
     */
    private static function filtrarItensPedido($itens, $idpedidovenda)
    {
        return array_filter($itens, function ($item) use ($idpedidovenda) {
            return $item['idpedidovenda'] === $idpedidovenda;
        });
    }

    /**
     * Filtra os acréscimos relacionados a um item de um pedido de venda.
     * 
     * @param array $acrescimos Array com os acréscimos a serem filtrados.
     * @param int $idpedidovenda ID do pedido de venda relacionado aos acréscimos.
     * @param int $idpedido_item ID do item de pedido relacionado aos acréscimos.
     * 
     * @return array Array com os acréscimos filtrados.
     */
    private static function filtrarAcrescimos($acrescimos, $idpedidovenda, $idpedido_item)
    {
        return array_filter($acrescimos, function ($acrescimo) use ($idpedidovenda, $idpedido_item) {
            return $acrescimo['idpedidovenda'] === $idpedidovenda && $acrescimo['idpedido_item'] === $idpedido_item;
        });
    }


    /**
     * desc: Retorna o total de pedidos de venda por clientes quantidade
     */
    public static function getQTPedidosClientes($celular, $idEmpresa)
    {
        $pessoa = ModelsPessoa::select()
            ->where('idempresa', $idEmpresa)
            ->where('celular', $celular)
            ->one();

        if (empty($pessoa)) {
            return 0;
        }

        $pedidos = PedidoVendaModel::select()
            ->where('idempresa', $idEmpresa)
            ->where('idcliente', $pessoa['idcliente'])
            ->where('idsituacao_pedido_venda', 2)
            ->where('origin', 2)
            ->count();

        return $pedidos;
    }

    public static function logProcessamentoNFE($idempresa, $idpedidovenda, $mensagemErro)
    {
        PedidoVendaModel::update([
            'msg_processsamento_nota' => $mensagemErro
        ])
            ->where('idempresa', $idempresa)
            ->where('idpedidovenda', $idpedidovenda)
            ->execute();
    }

    /**
     * monta a estrutura de um pedido de venda para emissao de nota fiscal
     */
    public static function getPedidoVendaNFE($idempresa, $idpedidovenda)
    {
        $pedido = self::getPedidoVendas(null, $idempresa, $idpedidovenda, null)[0];
        if (empty($pedido)) {
            throw new Exception("Pedido de venda não encontrado.");
        }

        $pedido = self::normalizarPedido($pedido);
        if (empty($pedido)) {
            throw new Exception("Pedido de venda não encontrado.");
        }

        if (empty($pedido['itens'])) {
            throw new Exception("Itens do pedido de venda não encontrados.");
        }

        foreach ($pedido['itens'] as $key => $item) {
            $pedido['itens'][$key]['imposto'] = ProdutoHandler::getImpostoProduto($idempresa, $item['idproduto'], $idpedidovenda);
        }


        $pedido = $pedido;
        $pedido['itens'] = $pedido['itens'];
        $pedido['pagamento'] = PagamentosModel::getPagamentoNFE($idempresa, $idpedidovenda);
        $pedido['emitente'] = Empresa::getInfosNFE($idempresa);
        $pedido['destinatario'] = Pessoa::getInfosNFE($pedido['idcliente'], $idempresa);
        $pedido['frete']  =  $pedido['metodo_entrega'] == 1 ? 1 : 2;
        $pedido['operacao'] = 'VendaNota';

        return $pedido;
    }


    /**
     * Ajusta pedido para NFC-e
     * 1) Soma acréscimos ao preço unitário
     * 2) Rateia cupom de desconto proporcionalmente
     * 3) Rateia frete proporcionalmente
     * 4) Recalcula total_pedido baseado na soma exata dos itens
     *
     * Nenhum campo novo é criado; só altera:
     *   - $item['preco']
     *   - $pedido['total_pedido']
     *   - (zera $pedido['obs']['taxa'] para o gerador não mandar vFrete)
     */
    public static function normalizarPedido(array $pedido): array
    {
        $frete    = (float)($pedido['obs']['taxa'] ?? 0);      // será embutido
        $desconto = (float)($pedido['cupon']['valor_cupons'] ?? 0);

        /* ------------------------------------------------------------------
       1) Embute acréscimos no preço unitário
    ------------------------------------------------------------------*/
        $somaItens = 0.0;
        foreach ($pedido['itens'] as &$item) {
            $preco = (float)$item['preco'];

            foreach ($item['acrescimos'] ?? [] as $acc) {
                if (empty($acc['gratis'])) {
                    $qtdAcc = (float)($acc['quantidade'] ?? 1);
                    $preco += (float)$acc['preco'] * $qtdAcc;
                }
            }
            $item['preco'] = round($preco, 2);
            $somaItens    += $item['preco'] * (float)$item['quantidade'];
        }
        unset($item);

        /* ------------------------------------------------------------------
       2) Rateia DESCONTO (cupom) entre os itens
    ------------------------------------------------------------------*/
        if ($desconto > 0 && $somaItens > 0) {
            $restante = $desconto;
            foreach ($pedido['itens'] as $idx => &$item) {
                $totalItem = $item['preco'] * (float)$item['quantidade'];
                $quota     = round($desconto * ($totalItem / $somaItens), 2);
                if ($idx === array_key_last($pedido['itens'])) $quota = $restante;

                $item['preco'] = max(
                    0.0,
                    round($item['preco'] - $quota / $item['quantidade'], 2)
                );
                $restante -= $quota;
            }
            unset($item);
        }

        /* ------------------------------------------------------------------
       3) Rateia FRETE entre os itens
    ------------------------------------------------------------------*/
        if ($frete > 0) {
            // Recalcula soma após desconto
            $somaItensAposDesconto = 0.0;
            foreach ($pedido['itens'] as $item) {
                $somaItensAposDesconto += $item['preco'] * (float)$item['quantidade'];
            }

            if ($somaItensAposDesconto > 0) {
                $restoFrete = $frete;
                foreach ($pedido['itens'] as $idx => &$item) {
                    $totalItem = $item['preco'] * (float)$item['quantidade'];
                    $quota     = round($frete * ($totalItem / $somaItensAposDesconto), 2);
                    if ($idx === array_key_last($pedido['itens'])) $quota = $restoFrete;

                    $item['preco'] = round($item['preco'] + $quota / $item['quantidade'], 2);
                    $restoFrete   -= $quota;
                }
                unset($item);
            }
            $pedido['obs']['taxa'] = 0.0;   // garante que vFrete saia 0,00
        }

        /* ------------------------------------------------------------------
       4) Total final calculado pela soma exata dos itens finais
    ------------------------------------------------------------------*/
        $totalFinal = 0.0;
        foreach ($pedido['itens'] as $item) {
            $totalFinal += round($item['preco'] * (float)$item['quantidade'], 2);
        }

        $pedido['total_pedido'] = round($totalFinal, 2);

        return $pedido;
    }


    public static function getNumeroMesaDisponivel($idempresa)
    {
        $mesasOcupadas = PedidoVendaModel::select()
            ->where('idempresa', $idempresa)
            ->where('idsituacao_pedido_venda', 1)
            ->whereNotNull('idmesa')
            ->sum('idmesa');

        return (int)$mesasOcupadas + 1;
    }
}
