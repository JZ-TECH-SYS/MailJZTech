<?php

/**
 * Classe helper para gerenciar Menu no sistema
 * 
 * Esta classe fornece métodos para gerenciar menus de um sistema.
 * 
 * Autor: Joaosn
 * Data de Início: 23/05/2023
 */

namespace src\handlers;

use src\handlers\service\MyZap;
use src\models\Empresa;
use src\models\Pedido_venda;
use src\models\Nota_fiscal;
use src\handlers\Pessoa;
use src\Config;

class MsgMyzap
{
    public static function notifyNewOrder(int $idempresa, int $idpedidovenda): void
    {
        $pedidoArr = PedidoVenda::getPedidoVendas(null, $idempresa, $idpedidovenda, null);
        $pedido = $pedidoArr[0] ?? null;
        if (!$pedido) {
            return;
        }

        $pessoa = Pessoa::getPessoaById($idempresa,$pedido['idcliente']);
        if (!$pessoa) {
            return;
        }

        $msg = self::buildOrderMessage(Empresa::getEMP($idempresa), $pedido);
        if ($pessoa['celular']) {
            self::sendWhatsapp($idempresa, $pessoa['celular'], $msg);
        }

        if ($pedido['metodo_entrega'] == 3 && !empty($pedido['idmesa'])) {
            $msgAdd = self::buildLinkMessageAddItems(Empresa::getEMP($idempresa), $pedido);
            self::sendWhatsapp($idempresa, $pessoa['celular'], $msgAdd);
        }
    }

    public static function sendWhatsapp(int $idempresa, string $numero, string $mensagem)
    {
        $empresa = Empresa::getEMP($idempresa);
        if (empty($empresa['session_myzap']) || empty($empresa['key_myzap'])) {
            return;
        }

        $numero = preg_replace('/\D/', '', $numero);
        if (!str_starts_with($numero, '55')) {
            $numero = '55' . $numero;
        }

        return MyZap::sendText($empresa['session_myzap'], $empresa['key_myzap'], $numero, $mensagem);
    }

    public static function formatCurrencyBr(float $valor): string
    {
        return 'R$ ' . number_format($valor, 2, ',', '.');
    }

    /**
     * Gera token seguro para acesso à comanda
     * @param int $idpedidovenda ID do pedido
     * @param string $idmesa Mesa do pedido
     * @return string Token hash de 16 caracteres
     */
    public static function gerarTokenComanda(int $idpedidovenda, string $idmesa): string
    {
        $salt = 'CLICK_EXPRESS_COMANDA_2025';
        return substr(md5($idpedidovenda . $idmesa . $salt), 0, 16);
    }

    /**
     * Valida token de acesso à comanda
     * @param int $idpedidovenda ID do pedido
     * @param string $idmesa Mesa do pedido
     * @param string $token Token a ser validado
     * @return bool True se token é válido
     */
    public static function validarTokenComanda(int $idpedidovenda, string $idmesa, string $token): bool
    {
        $tokenEsperado = self::gerarTokenComanda($idpedidovenda, $idmesa);
        return $token === $tokenEsperado;
    }

    public static function notifyStatusChange(int $idempresa, int $idpedidovenda)
    {
        $pedidoArr = Pedido_venda::select()->where('idempresa',$idempresa)->where('idpedidovenda',$idpedidovenda)->one();
        if (!$pedidoArr) {
            return;
        }
        $pessoa = Pessoa::getPessoaById($idempresa,$pedidoArr['idcliente']);
        if (!$pessoa) {
            return;
        }
        $celular = $pessoa['celular'] ?? '';
        if (!$celular) {
            return;
        }

        $status = $pedidoArr['idsituacao_pedido_venda'];

        $msg = 'Pronto! A caminho ou disponível para retirada. 🚚✨';
        switch($pedidoArr['metodo_entrega']){
            case 1:
                $msg = 'Opa! Que Maravilha, seu pedido está saindo pra entrega! 🚚✨';
                break;
            case 2:
                $msg = 'Opa! Que Maravilha, seu pedido está pronto para retirada! 🏃‍♂️✨';
                break;
            case 4:
                $msg = 'Opa! Que Maravilha, seu pedido está pronto para consumo no local! 🪑✨';
                break;
        }

     
        $mensagens = [
            1 => '🎉 *Pedido recebido!* Estamos processando seu pedido com carinho. 🍔⌛',
            4 => '👩‍🍳 *Preparando seu pedido!* Nossa equipe está cuidando de tudo para você. 🔥',
            3 => $msg,
            2 => '😋 *Pedido concluído!* Aproveite sua refeição e obrigado por escolher a gente! 👏',
            5 => '❌ *Pedido cancelado.* Se precisar de ajuda, estamos à disposição para esclarecer qualquer dúvida.'
        ];
        
        $texto = $mensagens[$status] ?? null;
        if ($texto) {
            self::sendWhatsapp($idempresa, $celular, $texto);
        }
    }

    public static function buildOrderMessage(array $empresa, array $pedido): string
    {
        $obs = $pedido['obs'];
        if (is_string($obs)) {
            $obs = json_decode($obs, true);
        }

        $pessoa = Pessoa::getPessoaById($empresa['idempresa'],$pedido['idcliente']);

        $msg = '🏬 *' . ($empresa['nomefantasia'] ?? $empresa['nome'] ?? '') . "*\n";
        $msg .= 'Olá *' . ($pedido['nome'] ?? '') . "*, muito obrigado pela sua preferência! 😊\n\n";
        $msg .= "📋 *Seu Pedido:*\n";

        foreach ($pedido['itens'] as $item) {
            $msg .= "━━━━━━━━━━━━━━━━━━\n";
            $msg .= '🍴 *' . $item['nome'] . "* — " . ($item['quantidade'] ?? 0) . "x\n";
            $msg .= '💰 ' . self::formatCurrencyBr($item['preco']) . "\n";
            if (!empty($item['obs'])) {
                $msg .= '📝 Obs: ' . $item['obs'] . "\n";
            }
            if (!empty($item['acrescimos'])) {
                $msg .= "➕ *Acréscimos:*\n";
                foreach ($item['acrescimos'] as $add) {
                    $msg .= '• ' . $add['nome'] . ' — ' . self::formatCurrencyBr($add['preco']) . ' (' . $add['quantidade'] . "x)\n";
                }
            }
        }

        $taxa = $obs['taxa'] ?? 0;
        $msg .= "━━━━━━━━━━━━━━━━━━\n";
        $msg .= '🚚 *Taxa de entrega:* ' . self::formatCurrencyBr($taxa) . "\n";

        if (!empty($pedido['cupon']['valor_cupons'])) {
            $msg .= '🎉 *Desconto:* -' . self::formatCurrencyBr($pedido['cupon']['valor_cupons']) . "\n";
        }

        $msg .= '🧾 *Total:* ' . self::formatCurrencyBr($pedido['total_pedido']) . "\n";
        $msg .= "━━━━━━━━━━━━━━━━━━\n";

        if (!empty($obs['nome_pagamento'])) {
            $msg .= '💳 *Pagamento:* ' . $obs['nome_pagamento'] . "\n";
        }

        if (!empty($obs['troco'])) {
            $msg .= '💵 *Troco para:* ' . self::formatCurrencyBr($obs['troco']) . "\n";
        }

        $metodos = [1 => '🚚 Entrega', 2 => '📦 Retirada', 3 => '🪑 Mesa', 4 => '🪑 Local'];
        $metodo = $metodos[$obs['metodo_entrega'] ?? $pedido['metodo_entrega'] ?? 1] ?? '';
        $msg .= '🚚 *Entrega:* ' . $metodo . "\n";

        if (($obs['metodo_entrega'] ?? 0) == 1) {
            $msg .= "📍 *Endereço:*\n";
            $msg .= '🌆 ' . ($obs['nome_cidade'] ?? '') . "\n";
            $msg .= '🏘️ ' . ($obs['nome_bairro'] ?? '') . "\n";
            $msg .= '🏠 ' . ($obs['endereco'] ?? '') . ', ' . ($obs['numero'] ?? '') . "\n";
            $msg .= '📝 ' . ($obs['complemento'] ?? '—') . "\n";
        }

        $msg .= '🙋‍♂️ *Cliente:* ' . ($pedido['nome'] ?? '') . "\n";
        $msg .= '📞 *Telefone:* ' . ($obs['celular'] ?? $pedido['celular'] ?? $pessoa['celular'] ?? '') . "\n";

        // ✅ ADICIONAR LINK PARA PEDIR MAIS ITENS (apenas para pedidos de salão/local)
        if ($pedido['metodo_entrega'] == 3 && !empty($pedido['idmesa'])) {
            $msg .= "\n━━━━━━━━━━━━━━━━━━\n";
            $msg .= "🔗 *Quer adicionar algo?*\n";
            $msg .= "Clique aqui para adicionar itens à sua comanda:\n";
        }

        return $msg;
    }

    public static function buildLinkMessageAddItems(array $empresa, array $pedido): string
    {
        $token = self::gerarTokenComanda($pedido['idpedidovenda'], $pedido['idmesa']);
        $nomeEmpresa = $empresa['nome'] ?? '';
        $mesa = $pedido['idmesa'];

        $front = Config::FRONT_URL;
        $link = "{$front}pedido/{$nomeEmpresa}/salao/{$mesa}/{$token}";

        return $link;
    }

    public static function sendNfePdf(int $idempresa, int $idpedidovenda): void
    {
        $nf = Nota_fiscal::select()
            ->where('idempresa', $idempresa)
            ->where('idpedidovenda', $idpedidovenda)
            ->where('status_processamento', 4)
            ->one();
        $pv = PedidoVenda::getPedidoVendas(null, $idempresa, $idpedidovenda, null)[0];

        if (!$nf || empty($nf['xml'])) {
            throw new \Exception('Nota não encontrada ou sem XML salvo');
        }


        $pessoa = Pessoa::getPessoaById($idempresa, $pv['idcliente']);
        if (!$pessoa || empty($pessoa['celular'])) {
            throw new \Exception('Cliente sem celular');
        }

        $xml    = $nf['xml'];
        $modelo = (int) $nf['modelo'];

        if ($modelo === 65) {
            $danfe = new \NFePHP\DA\NFe\Danfce($xml, 'P', '80');
            $danfe->setFont('arial');
            $pdf = $danfe->render();
        } else {
            $danfe = new \NFePHP\DA\NFe\Danfe($xml);
            $pdf   = $danfe->render();
        }

        $base64 = 'data:application/pdf;base64,' . base64_encode($pdf);

        self::sendFile64($idempresa, $pessoa['celular'], $base64, 'nota-fiscal.pdf');
    }

    public static function sendOrder(int $idempresa, int $idpedidovenda, string $extra = ''): void
    {
        $pedidoArr = PedidoVenda::getPedidoVendas(null, $idempresa, $idpedidovenda, null);
        $pedido = $pedidoArr[0] ?? null;
        if (!$pedido) {
            throw new \Exception('Pedido não encontrado');
        }

        $pessoa = Pessoa::getPessoaById($idempresa, $pedido['idcliente']);
        if (!$pessoa || empty($pessoa['celular'])) {
            throw new \Exception('Cliente sem celular');
        }

        $msg = self::buildOrderMessage(Empresa::getEMP($idempresa), $pedido);
        if ($extra) {
            $msg .= "\n\n" . $extra;
        }

        self::sendWhatsapp($idempresa, $pessoa['celular'], $msg);

        if (($pedido['metodo_entrega'] == 3 || $pedido['metodo_entrega'] == 4) && !empty($pedido['idmesa'])) {
            $msgAdd = self::buildLinkMessageAddItems(Empresa::getEMP($idempresa), $pedido);
            self::sendWhatsapp($idempresa, $pessoa['celular'], $msgAdd);
        }
    }

    private static function sendFile64(int $idempresa, string $numero, string $base64, string $nome): void
    {
        $empresa = Empresa::getEMP($idempresa);
        if (empty($empresa['session_myzap']) || empty($empresa['key_myzap'])) {
            throw new \Exception('Empresa não configurada para MyZap');
        }

        $numero = preg_replace('/\D/', '', $numero);
        if (!str_starts_with($numero, '55')) {
            $numero = '55' . $numero;
        }

        MyZap::sendFile64($empresa['session_myzap'], $empresa['key_myzap'], $numero, $base64, $nome);
    }
}
