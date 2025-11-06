<?php
/**
 * Controlador de Pedidos de Venda – integração IA
 * Autor: Joaosn
 * Última refatoração: 16/07/2025
 *
 * Todas as requisições:  POST /api/v1/pedido-venda-ia
 * Body esperado:         { "acao": "<nomeDaAcao>", "payload": { … } }
 * Resposta padronizada:  ctrl::response(['status'=>'ok','acao'=>...,'data'=>...])
 */

namespace src\controllers;

use \core\Controller as ctrl;
use Exception;
use PDOException;
use src\handlers\PedidoVenda;
use src\handlers\PedidoVendaItem;
use src\handlers\PedidoVendaItemAcrescimo;
use src\handlers\Printer;

class IAPedidoVendaController extends ctrl
{
    /* ──────────────────────────────────────────────────────────────────── */
    /** Ações públicas que a IA pode chamar */
    private const ALLOWED_ACTIONS = [
        // leituras
        'getPedidos','getPedidosOnline','getPedidosById',
        // pedido
        'addPedido','editPedido','deletePedido',
        // itens
        'addPedidoItem','editPedidoItem','deletePedidoItem',
        // acréscimos
        'addPedidoItemAcrescimo','editPedidoItemAcrescimo','deletePedidoItemAcrescimo',
        // util
        'alterarQuantidade'
    ];

    /* Campos obrigatórios */
    private const ADDCAMPOS              = ['idempresa','nome','celular'];
    private const EDITCAMPOS             = ['idempresa','idpedidovenda'];
    private const ADDITEMCAMPOS          = ['idempresa','idpedidovenda','idproduto','quantidade'];
    private const EDITITEMCAMPOS         = ['idempresa','idpedidovenda','idproduto','idpedido_item'];
    private const ADDITEMACRESCIMOCAMPOS = ['idempresa','idpedidovenda','idproduto','idpedido_item','quantidade'];
    private const EDITITEMACRESCIMOCAMPOS= ['idempresa','idpedidovenda','idproduto','idpedido_item','idpedido_acrescimo'];
    private const ALTERAQUANTIA          = ['idempresa','idpedidovenda','idproduto','tabela','acao','value'];

    /* Outras constantes */
    private const TABELASITEMS           = ['pedido_venda_item','pedido_venda_item_acrescimos'];
    private const ACOES                  = [1,2];

    /* ────────────────────────── Roteador único ────────────────────────── */
    public function acao()
    {
        try {
            $body = ctrl::getBody();
            $acao = $body['acao']    ?? null;
            $data = $body['payload'] ?? [];

            if (!$acao)                   throw new Exception('Ação não informada');
            if (!($data['idempresa']??0)) throw new Exception('ID da empresa não informado');
            if (!in_array($acao, self::ALLOWED_ACTIONS,true) || !method_exists($this,$acao)){
                throw new Exception('Ação inválida');
            }

            $result = $this->$acao($data);
            ctrl::response(['status'=>'ok','acao'=>$acao,'data'=>$result],200);

        } catch (Exception|PDOException $e) {
            ctrl::rejectResponse($e);
        }
    }

    /* ───────────────────────── Métodos de leitura ─────────────────────── */
    protected function getPedidosById(array $args)
    {
        try {
            $this->verificar($args,['idempresa','idpedidovenda']);
            $p = PedidoVenda::getPedidoVendas(null,$args['idempresa'],$args['idpedidovenda'],null);
            return Printer::vericarImpresao($p[0]);
        } catch (\Throwable $e) { throw $e; }
    }

    /* ───────────────────── Cabeçalho de pedido ─────────────────────────── */
    protected function addPedido(array $d)
    {
        try {
            $this->verificar($d,self::ADDCAMPOS);
            $id = PedidoVenda::addPedidoVenda($d,false,true);
            if (!$id) throw new Exception('Erro ao adicionar pedido');

            return [
                'idpedidovenda'=>$id,
                'idempresa'    =>$d['idempresa'],
                'nome'         =>$d['nome'],
                'celular'      =>$d['celular']
            ];
        } catch (\Throwable $e) { throw $e; }
    }

    protected function editPedido(array $d)
    {
        try {
            $this->verificar($d,self::EDITCAMPOS);
            if (!PedidoVenda::editPedidoVenda($d,false)) throw new Exception('Erro ao editar pedido');
            return $d;
        } catch (\Throwable $e) { throw $e; }
    }

    protected function deletePedido(array $d)
    {
        try {
            $this->verificar($d,self::EDITCAMPOS);
            PedidoVenda::deletePedidoVenda($d,false);
            return [
                'status'=>'ok',
                'message'=>'Pedido deletado com sucesso',
                'idpedidovenda'=>$d['idpedidovenda'],
                'idempresa'    =>$d['idempresa']
            ];
        } catch (\Throwable $e) { throw $e; }
    }

    /* ───────────────────────────── Itens ───────────────────────────────── */
    protected function addPedidoItem(array $d)
    {
        try {
            $this->verificar($d,self::ADDITEMCAMPOS);
            self::validaQuantidade($d['quantidade']);
            PedidoVendaItem::addPedidoVendItem($d,false);
            return $d;
        } catch (\Throwable $e) { throw $e; }
    }

    protected function editPedidoItem(array $d)
    {
        try {
            $this->verificar($d,self::EDITITEMCAMPOS);
            self::validaQuantidade($d['quantidade']);
            PedidoVendaItem::editPedidoVendaItem($d,false);
            return $d;
        } catch (\Throwable $e) { throw $e; }
    }

    protected function deletePedidoItem(array $d)
    {
        try {
            $this->verificar($d,self::EDITITEMCAMPOS);
            PedidoVendaItem::deletePedidoVendaItem($d,false);
            return $d;
        } catch (\Throwable $e) { throw $e; }
    }

    /* ───────────────────────── Acréscimos ─────────────────────────────── */
    protected function addPedidoItemAcrescimo(array $d)
    {
        try {
            $this->verificar($d,self::ADDITEMACRESCIMOCAMPOS);
            self::validaQuantidade($d['quantidade']);
            PedidoVendaItemAcrescimo::addPedidoVendItemAcrescimo($d,false);
            return $d;
        } catch (\Throwable $e) { throw $e; }
    }

    protected function editPedidoItemAcrescimo(array $d)
    {
        try {
            $this->verificar($d,self::EDITITEMACRESCIMOCAMPOS);
            self::validaQuantidade($d['quantidade']);
            PedidoVendaItemAcrescimo::editPedidoVendaItemAcrescimo($d,false);
            return $d;
        } catch (\Throwable $e) { throw $e; }
    }

    protected function deletePedidoItemAcrescimo(array $d)
    {
        try {
            $this->verificar($d,self::EDITITEMACRESCIMOCAMPOS);
            PedidoVendaItemAcrescimo::deletePedidoVendaItemAcrescimo($d,false);
            return $d;
        } catch (\Throwable $e) { throw $e; }
    }

    /* ─────────────────── Alterar quantidade (item/acréscimo) ───────────── */
    protected function alterarQuantidade(array $d)
    {
        try {
            $this->verificar($d,self::ALTERAQUANTIA);

            if (!in_array($d['acao'],self::ACOES,true))     throw new Exception('Ação inválida (1=soma,2=subtrai)');
            if (!in_array($d['tabela'],self::TABELASITEMS,true)) throw new Exception('Tabela inválida');

            $r = PedidoVenda::alteraQuantia($d);
            if (!$r) throw new Exception('Erro ao alterar quantidade');
            return $r;
        } catch (\Throwable $e) { throw $e; }
    }

    /* ───────────────────────────── Helpers ─────────────────────────────── */
    private function verificar(array $d,array $req): void
    {
        ctrl::verificarCamposVazios($d,$req);
    }

    private static function validaQuantidade($q): void
    {
        if ($q<0) throw new Exception('Quantidade deve ser maior que zero');
    }
}
