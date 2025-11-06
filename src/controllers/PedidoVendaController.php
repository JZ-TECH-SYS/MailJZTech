<?php

/**
 * Controlador de Pedidos de Venda
 * Autor: Joaosn
 * Data de início: 23/05/2023
 */

namespace src\controllers;

use \core\Controller as ctrl;
use Exception;
use PDO;
use PDOException;
use src\handlers\Help;
use \src\handlers\PedidoVenda;
use \src\handlers\PedidoVendaItem;
use \src\handlers\PedidoVendaItemAcrescimo;
use src\handlers\Printer;
use src\models\Cupon;
use src\models\Empresa_parametro;
use src\models\Mesa_salao;
use src\models\Pagamentos;
use src\models\Pessoa;
use src\models\Pedido_venda;

class PedidoVendaController extends ctrl
{
    /**
     * Campos obrigatórios para criar um novo pedido
     */
    const ADDCAMPOS = [
        'idempresa',
        'nome',
        'celular'
    ];

    /**
     * Campos obrigatórios para editar e excluir um pedido
     */
    const EDITCAMPOS = [
        'idempresa',
        'idpedidovenda'
    ];

    /**
     * Campos obrigatórios para adicionar um item ao pedido
     */
    const ADDITEMCAMPOS = [
        'idempresa',
        'idpedidovenda',
        'idproduto',
        'quantidade'
    ];

    /**
     * Campos obrigatórios para editar e excluir um item do pedido
     */
    const EDITITEMCAMPOS = [
        'idempresa',
        'idpedidovenda',
        'idproduto',
        'idpedido_item'
    ];

    /**
     * Campos obrigatórios para adicionar um acréscimo ao item do pedido
     */
    const ADDITEMACRESCIMOCAMPOS = [
        'idempresa',
        'idpedidovenda',
        'idproduto',
        'idpedido_item',
        'quantidade'
    ];

    /**
     * Campos obrigatórios para editar e excluir um acréscimo do item do pedido
     */
    const EDITITEMACRESCIMOCAMPOS = [
        'idempresa',
        'idpedidovenda',
        'idproduto',
        'idpedido_item',
        'idpedido_acrescimo'
    ];

    /**
     * Campos obrigatórios para adicionar ou remover quantidade de item ou acrescimo no pedido
     */
    const ALTERAQUANTIA = [
        'idempresa',
        'idpedidovenda',
        'idproduto',
        'tabela',
        'acao',
        'value'
    ];

    /**
     * Tabelas que podem ser alteradas valro em quantidade
     */
    const TABELASITEMS = [
        'pedido_venda_item',
        'pedido_venda_item_acrescimos'
    ];

    /**
     * Keys das tabelas que podem ser alteradas valro em quantidade
     */
    const TABELASITEMSKEYS = [
        'idpedido_item',
        'idpedido_acrescimo'
    ];

    /**
     * Ações que podem ser realizadas em uma tabela TABELASITEMS
     * 1 = somar, 2 = subtrair   
     */
    const ACOES = [
        1,
        2
    ];

    /**
     * campos obrigatorios para print
     */
    const GETPRINT = [
        'idempresa',
        'idpedidovenda'
    ];

    /**
     * campos obrigatorios para entrega
     */
    const ADDENTREGACAMPOS = [
        'metodo_entrega',
        'metodo_pagamento',
        'idcidade',
        'idbairro',
        'endereco'
    ];

    /**
     * campos obrigatorios para retirada
     */
    const ADDRETIRADACAMPOS = [
        'metodo_entrega',
        'celular',
        'nome'
    ];

    /**
     * campos obrigatorios para mesa no local
     */
    const ADDLOCALCAMPOS = [
        'metodo_entrega',
        'celular',
        'nome',
        'idmesa'
    ];

    /**
     * campos obrigatorios para mesa consumo no local
     */
    const ADDLOCALCONSUMO = [
        'metodo_entrega',
        'celular',
        'nome'
    ];

    /**
     * Busca todos os pedidos de uma empresa
     */
    public function getPedidos($args)
    {
        $pedido = PedidoVenda::getPedidoVendas($args['idsituacao'], $args['idempresa'], null, 1);
        ctrl::response($pedido, 200);
    }

    /**
     * Busca todos os pedidos de uma empresa
     */
    public function getPedidosOnline($args)
    {
        $pedido = PedidoVenda::getPedidoVendas($args['idsituacao'], $args['idempresa'], null, $args['origin']);
        ctrl::response($pedido, 200);
    }

    /**
     * Busca um pedido específico de uma empresa
     */
    public function getPedidosById($args)
    {
        $pedido = PedidoVenda::getPedidoVendas(null, $args['idempresa'], $args['idpedidovenda'], null);
        $pedido = Printer::vericarImpresao($pedido[0]);
        ctrl::response($pedido, 200);
    }

    /**
     * Retorna os últimos pedidos de um cliente pelo telefone
     */
    public function getLastOrdersPhone($args)
    {
        try {
            if (empty($args['celular']) || empty($args['idempresa'])) {
                throw new Exception('dados inválidos');
            }

            $cliente = Pessoa::select()
                ->where('idempresa', $args['idempresa'])
                ->where('celular', $args['celular'])
                ->one();

            if (!$cliente) {
                ctrl::response([], 200);
                return;
            }

            $pedidos = Pedido_venda::select([
                    'idempresa',
                    'idpedidovenda',
                    'origin',
                    'idsituacao_pedido_venda'
                ])
                ->where('idempresa', $args['idempresa'])
                ->where('idcliente', $cliente['idcliente'])
                ->where('origin', 2) // Origin 1 = Local
                ->orderBy('data_pedido', 'DESC')
                ->limit(5)
                ->get();
            $pedidosCompletos = [];
            foreach ($pedidos as $pedido) {
                $pv = PedidoVenda::getPedidoVendas($pedido['idsituacao_pedido_venda'], $pedido['idempresa'], $pedido['idpedidovenda'], $pedido['origin']);
                if (empty($pv) || !isset($pv[0])) {
                    continue;
                }
                $meiopagamento = Pagamentos::select('p.descricao')
                    ->join('tipo_pagamento as p', 'p.idtipopagamento', '=', 'pagamentos.idtipopagamento')
                    ->where('pagamentos.idempresa', $pedido['idempresa'])
                    ->where('pagamentos.idpedidovenda', $pedido['idpedidovenda'])
                    ->one();

                $pv[0]['meiopagamento'] = $meiopagamento ? $meiopagamento['descricao'] : 'Não informado';
                $pedidosCompletos[] = $pv[0];
            }    

            ctrl::response($pedidosCompletos, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Adiciona um novo pedido
     */
    public function addPedido()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::ADDCAMPOS);
            $pedido = PedidoVenda::addPedidoVenda($data);
            if (!$pedido) {
                throw new Exception('Erro ao adicionar pedido');
            }
            ctrl::response($pedido, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Edita um pedido existente
     */
    public function editPedido()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::EDITCAMPOS);
            $pedido = PedidoVenda::editPedidoVenda($data);
            if (!$pedido) {
                throw new Exception('Erro ao editar pedido');
            }
            ctrl::response($pedido[0], 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Exclui um pedido caso ainda não tenha sido pago
     */
    public function deletePedido()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::EDITCAMPOS);
            $pedido = PedidoVenda::deletePedidoVenda($data);
            if (!$pedido) {
                throw new Exception('Erro ao deletar pedido');
            }
            ctrl::response($pedido, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Adiciona um item ao pedido
     */
    public function addPedidoItem()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::ADDITEMCAMPOS);
            self::ValidaQuantidade($data['quantidade']);
            $pedido = PedidoVendaItem::addPedidoVendItem($data);
            if (!$pedido) {
                throw new Exception('Erro ao adicionar item ao pedido');
            }
            ctrl::response($pedido, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Edita um item do pedido
     */
    public function editPedidoItem()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::EDITITEMCAMPOS);
            self::ValidaQuantidade($data['quantidade']);
            $pedido = PedidoVendaItem::editPedidoVendaItem($data);
            if (!$pedido) {
                throw new Exception('Erro ao editar item do pedido');
            }
            ctrl::response($pedido, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Exclui um item do pedido
     */
    public function deletePedidoItem()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::EDITITEMCAMPOS);
            $pedido = PedidoVendaItem::deletePedidoVendaItem($data);
            if (!$pedido) {
                throw new Exception('Erro ao deletar item do pedido');
            }
            ctrl::response($pedido, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Adiciona um acréscimo ao item do pedido
     */
    public function addPedidoItemAcrescimo()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::ADDITEMACRESCIMOCAMPOS);
            self::ValidaQuantidade($data['quantidade']);
            $pedido = PedidoVendaItemAcrescimo::addPedidoVendItemAcrescimo($data);
            if (!$pedido) {
                throw new Exception('Erro ao adicionar acrescimo ao item do pedido');
            }
            ctrl::response($pedido, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        } catch (PDOException $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Edita um acréscimo do item do pedido
     */
    public function editPedidoItemAcrescimo()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::EDITITEMACRESCIMOCAMPOS);
            self::ValidaQuantidade($data['quantidade']);
            $pedido = PedidoVendaItemAcrescimo::editPedidoVendaItemAcrescimo($data);
            if (!$pedido) {
                throw new Exception('Erro ao editar acrescimo do item do pedido');
            }
            ctrl::response($pedido, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Exclui um acréscimo do item do pedido
     */
    public function deletePedidoItemAcrescimo()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::EDITITEMACRESCIMOCAMPOS);
            $pedido = PedidoVendaItemAcrescimo::deletePedidoVendaItemAcrescimo($data);
            if (!$pedido) {
                throw new Exception('Erro ao deletar acrescimo do item do pedido');
            }
            ctrl::response($pedido, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * adicona ou remove quantidade de item ou acrescimo no pedido de UM em UM
     */
    public function alterarQuantidade()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::ALTERAQUANTIA);
            if (!in_array($data['acao'], self::ACOES)) {
                throw new Exception('Ação inválida');
            }
            if (!in_array($data['tabela'], self::TABELASITEMS)) {
                throw new Exception('Tabela inválida');
            }

            $pedido = PedidoVenda::alteraQuantia($data);
            if (!$pedido) {
                throw new Exception('Erro ao alterar quantidade');
            }
            ctrl::response($pedido, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Valida a quantidade
     */
    private static function ValidaQuantidade($quantidade)
    {
        if ($quantidade < 0) {
            throw new Exception('Quantidade deve ser maior que zero');
        }
    }

    /**
     * Insere um pedido de venda recebendo JSON pedido completo
     */
    public function addPedidoCompleto()
    {
        try {
            $data = ctrl::getBody();
            if (!Help::estaAberto($data['idempresa'])) {
                Help::abreFechaSite('fechar', $data['idempresa']);
                throw new Exception('No momento não estamos atendendo, por favor tente mais tarde !'. date('d/m/Y H:i:s') );
            }
            ctrl::verificarCamposVazios($data, self::ADDCAMPOS);
            if (isset($data['idmesa']) && !empty($data['idmesa'])) {
                $emuso = PedidoVenda::validaMesa($data['idempresa'], $data['idmesa'], $data['nome']);
                if ($emuso) {
                    throw new Exception('A mesa selecionada já está em uso. Por favor, consulte o garçom. Se você já está nesta mesa, pode abrir o pedido apenas alterando o campo "nome completo".');
                }
            }
            if (!isset($data['itens']) || empty($data['itens'])) {
                throw new Exception('Pedido deve conter Itens do pedido');
            }
            foreach ($data['itens'] as &$item) {
                $item['idempresa'] = $data['idempresa'];
                $itensCampos = self::ADDITEMCAMPOS;
                unset($itensCampos[1]);
                ctrl::verificarCamposVazios($item, $itensCampos);
                self::validaQuantidade($item['quantidade']);
                PedidoVenda::validaEstoque($data['idempresa'], $item['idproduto'], $item['quantidade']);

                if (isset($item['acrescimos']) && !empty($item['acrescimos'])) {
                    foreach ($item['acrescimos'] as &$acrescimo) {
                        $acrescimo['idempresa'] = $data['idempresa'];
                        $acrescimoCampos = self::ADDITEMACRESCIMOCAMPOS;
                        unset($acrescimoCampos[1]);
                        unset($acrescimoCampos[3]);
                        ctrl::verificarCamposVazios($acrescimo, $acrescimoCampos);
                        self::validaQuantidade($acrescimo['quantidade']);
                        PedidoVenda::validaEstoque($data['idempresa'], $item['idproduto'], $item['quantidade']);
                    }
                }
            }

            switch ($data['metodo_entrega']) {
                case 1: //->1=entrega 
                    ctrl::verificarCamposVazios($data, self::ADDENTREGACAMPOS);
                    $data['idmesa'] = null;
                    $data['origin'] = 2;
                    break;
                case 2: //->2=retirada
                    ctrl::verificarCamposVazios($data, self::ADDRETIRADACAMPOS);
                    $data['idmesa'] = null;
                    $data['origin'] = 2;
                    break;
                case 3: //->3=mesa no local
                    $mesa = Mesa_salao::mesaExiste($data['idmesa'], $data['idempresa']);
                    if (!$mesa) {
                        throw new Exception('Mesa não encontrada !');
                    }
                    ctrl::verificarCamposVazios($data, self::ADDLOCALCAMPOS);
                    $data['origin'] = 1;
                    break;
                case 4: //->3=consumo no local
                    $data['idmesa'] = PedidoVenda::getNumeroMesaDisponivel($data['idempresa']);
                    ctrl::verificarCamposVazios($data, self::ADDLOCALCONSUMO);
                    $data['origin'] = 1;
                    break;
                default:
                    throw new Exception('Metodo de entrega inválido');
            }

            self::validarCuponsDesconto($data);
            $pedido = PedidoVenda::addPedidoVendaCompleto($data);

            if (!$pedido) {
                throw new Exception('Erro ao adicionar pedido');
            }

            ctrl::response($pedido, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    private static function validarCuponsDesconto($data)
    {
        if (isset($data['idscupon']) && !empty($data['idscupon'])) {
            $ids = explode(',', $data['idscupon']);
            $getCupons = Cupon::select()->where('idempresa', $data['idempresa'])->whereIn('idcupon', $ids)->execute();

            if (!$getCupons) {
                throw new Exception('Cupoms inválido');
            }

            $total = 0;
            foreach ($getCupons as $item) {
                $total += $item['valor'];
            }

            if ((float)$total != (float)$data['valor_cupons']) {
                throw new Exception('O valores dos Cupons não conferem!');
            }

            return true;
        }
    }
}
