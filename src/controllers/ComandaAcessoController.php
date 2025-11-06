<?php
namespace src\controllers;

use \core\Controller as ctrl;
use src\handlers\PedidoVenda;
use src\handlers\PedidoVendaItem;
use src\handlers\PedidoVendaItemAcrescimo;
use src\handlers\MsgMyzap;
use src\handlers\Pessoa;
use src\models\Pedido_venda;

class ComandaAcessoController extends ctrl
{
    /**
     * GET - Validar acesso à comanda via token
     * @route GET /validarAcessoComanda/{idempresa}/{mesa}/{token}
     * 
     * Valida se o token é válido para a comanda aberta na mesa
     * Retorna dados da comanda se válido
     */
    public function validarAcesso($args)
    {
        try {
            $idempresa = $args['idempresa'];
            $mesa = $args['mesa'];
            $token = $args['token'];

            // 1. Buscar comanda aberta na mesa
            $comanda = Pedido_venda::select()
                ->where('idempresa', $idempresa)
                ->where('idmesa', $mesa)
                ->where('idsituacao_pedido_venda', 1)
                ->one();

            if (!$comanda) {
                ctrl::response([
                    'error' => 'Nenhuma comanda aberta nesta mesa',
                    'tipo' => 'sem_comanda'
                ], 404);
            }

            // 2. Buscar dados do cliente
            $pessoa = Pessoa::getPessoaById($idempresa, $comanda['idcliente']);
            if (!$pessoa) {
                ctrl::response([
                    'error' => 'Cliente não encontrado'
                ], 404);
            }


            
            // 3. Validar token
            $tokenValido = MsgMyzap::validarTokenComanda(
                $comanda['idpedidovenda'],
                $mesa,
                $token
            );

            if (!$tokenValido) {
                ctrl::response([
                    'error' => 'Acesso não autorizado. Token inválido.',
                    'tipo' => 'token_invalido'
                ], 403);
            }

            // 4. Buscar comanda completa com itens
            $comandaCompleta = PedidoVenda::getPedidoVendas(
                null,
                $idempresa,
                $comanda['idpedidovenda'],
                null
            );

            if (empty($comandaCompleta)) {
                ctrl::response([
                    'error' => 'Erro ao carregar comanda'
                ], 500);
            }

            // 5. Retornar dados da comanda
            ctrl::response([
                'idpedidovenda' => $comanda['idpedidovenda'],
                'idmesa' => $comanda['idmesa'],
                'nome' => $comanda['nome'],
                'total_pedido' => $comandaCompleta[0]['total_pedido'],
                'itens' => $comandaCompleta[0]['itens'],
                'token' => $token, // ✅ INCLUIR TOKEN NA RESPOSTA
                'data_pedido' => $comanda['data_pedido']
            ], 200);

        } catch (\Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * POST - Adicionar items à comanda existente
     * @route POST /addItemComanda
     * 
     * Adiciona novos items à comanda validando token
     * Notifica via WhatsApp
     */
    public function addItemComanda($args)
    {
        try {
            $body = ctrl::getBody();

            // 1. Validar campos obrigatórios principais
            $required = ['idempresa', 'idpedidovenda', 'idmesa', 'token', 'itens'];
            foreach ($required as $field) {
                if (!isset($body[$field])) {
                    ctrl::response([
                        'error' => "Campo obrigatório ausente: {$field}"
                    ], 400);
                }
            }

            // 2. Validar que itens é um array
            if (!is_array($body['itens']) || empty($body['itens'])) {
                ctrl::response([
                    'error' => "itens deve ser um array não vazio"
                ], 400);
            }

            // 3. Buscar comanda
            $comanda = Pedido_venda::select()
                ->where('idempresa', $body['idempresa'])
                ->where('idpedidovenda', $body['idpedidovenda'])
                ->where('idsituacao_pedido_venda', 1)
                ->one();

            if (!$comanda) {
                ctrl::response([
                    'error' => 'Comanda não encontrada ou já foi fechada'
                ], 404);
            }

            // 4. Buscar cliente e validar token
            $pessoa = Pessoa::getPessoaById($body['idempresa'], $comanda['idcliente']);
            if (!$pessoa) {
                ctrl::response([
                    'error' => 'Cliente não encontrado'
                ], 404);
            }

            $tokenValido = MsgMyzap::validarTokenComanda(
                $body['idpedidovenda'],
                $comanda['idmesa'],
                $body['token']
            );

            if (!$tokenValido) {
                ctrl::response([
                    'error' => 'Token inválido'
                ], 403);
            }

            // 5. Adicionar cada item à comanda
            $itemsAdicionados = [];
            foreach ($body['itens'] as $item) {
                // Validar campos do item
                if (!isset($item['idproduto']) || !isset($item['quantidade'])) {
                    ctrl::response([
                        'error' => 'Cada item deve ter idproduto e quantidade'
                    ], 400);
                }

                $itemData = [
                    'idempresa' => $body['idempresa'],
                    'idpedidovenda' => $body['idpedidovenda'],
                    'idproduto' => $item['idproduto'],
                    'quantidade' => $item['quantidade'],
                    'preco' => $item['preco'] ?? 0,
                    'obs' => $item['obs'] ?? ''
                ];

                $idpedido_item = PedidoVendaItem::addPedidoVendItem($itemData, false);
                $itemsAdicionados[] = $idpedido_item;

                // 6. Adicionar acréscimos se houver
                if (!empty($item['acrescimos'])) {
                    foreach ($item['acrescimos'] as $acrescimo) {
                        $acrescimo['idempresa'] = $body['idempresa'];
                        $acrescimo['idpedidovenda'] = $body['idpedidovenda'];
                        $acrescimo['idpedido_item'] = $idpedido_item;
                        PedidoVendaItemAcrescimo::addPedidoVendItemAcrescimo($acrescimo, false);
                    }
                }
            }

            // 7. Enviar para fila de impressão (só pendentes)
            \src\handlers\Printer::sendPrint([
                'idempresa' => $body['idempresa'],
                'idpedidovenda' => $body['idpedidovenda'],
                'idpedidovendaitem' => 'pedente'
            ]);

            // 8. Notificar via WhatsApp
            try {
                $mensagem = "🔔 *Novo(s) item(ns) adicionado(s) à Mesa {$comanda['idmesa']}!*\n";
                foreach ($body['itens'] as $item) {
                    $produto = \src\models\Produtos::select()
                        ->where('idempresa', $body['idempresa'])
                        ->where('idproduto', $item['idproduto'])
                        ->one();
                    
                    $mensagem .= "\n🍴 {$item['quantidade']}x {$produto['nome']}";
                }

                MsgMyzap::sendWhatsapp($body['idempresa'], $pessoa['celular'], $mensagem);
            } catch (\Exception $e) {
                // Ignora erro de WhatsApp
            }

            // 9. Retornar comanda atualizada
            $comandaAtualizada = PedidoVenda::getPedidoVendas(
                null,
                $body['idempresa'],
                $body['idpedidovenda'],
                null
            );

            ctrl::response([
                'error' => false,
                'result' => $comandaAtualizada[0] ?? []
            ], 200);

        } catch (\Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
}
