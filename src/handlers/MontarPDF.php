<?php


/**
 * Classe responsável por ter varias funções auxiliares
 *
 * @autor: joaosn
 * @dateInicio: 23/05/2023
 */

namespace src\handlers;

use Exception;
use src\handlers\PedidoVenda;
use src\handlers\Printer;
use src\models\Bairros;
use src\models\Categoria;
use src\models\Cupon;
use src\models\Cupon_pedidos;
use src\models\Empresa;
use src\models\Empresa_parametro;
use src\models\Filaprint;

const CSSpdf = '<style>
        html,
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            font-weight: bold;
        }
        h2 {
            color: navy;
            text-align: center;
        }

        .header-content,
        .body-content {
            margin: auto;
            width: 35%;
        }

        p {
            margin-top: 1%;
            margin-bottom: 1%;
        }

        .extras {
            padding-left: 13px;
        }

        .extras-content {
            padding-left: 20px;
        }
        </style>';

/**
 * Classe MontarPDF com html para montar o pdf
 * 
 * @package src\handlers
 */
class MontarPDF
{
    private static function numeroEUA($numero) {
        // Remove os separadores de milhar
        $numero = str_replace('.', '', $numero);
        // Substitui a vírgula do separador decimal pelo ponto
        $numero = str_replace(',', '.', $numero);
        
        // Formata o número para o padrão dos EUA com dois decimais
        $numeroFormatado = number_format($numero, 2, '.', ',');
        
        return $numeroFormatado;
    }

    private static function getMedidas($idempresa){
        try{
            $empresa = Empresa::select()->where('idempresa',$idempresa)->one();
            $medida  = Empresa_parametro::select()->where('idempresa',$idempresa)->where('idparametro',10)->one();
            if(!isset($medida['valor'])){
                throw new Exception('deve ser feito cadastro de medida para impressão no banco de dados');
            }
            $separa =  explode(',',$medida['valor']);
            $largura = $separa[0];
            $altura  = $separa[1]; 
        
            return ['largura' => $largura, 'altura' => $altura, 'empresa' => $empresa ];
        }catch(Exception){
            throw new Exception('ops.. alconteceu algo na fila de impressão Conate o suporte! erro ao pegar medidas');
        }
    }

    public static function PDFpedidoItem($idempresa,$idpedidovenda,$idpedidoitem){
        $pdfs = [];
        $dados   = self::getMedidas($idempresa);
        $pedido  = PedidoVenda::getPedidoVendas(null,$idempresa,$idpedidovenda,null,null);
        $pedidoItem = array_filter($pedido[0]['itens'], function($item) use ($idpedidoitem) {
            return $item['idpedido_item'] == $idpedidoitem;
        });
        $pedidoItem = reset($pedidoItem);
     
        $categoria = Categoria::select()->where('idcategoria',$pedidoItem['idcategoria'])->where('idempresa',$idempresa)->one();
    
        $cliente = $pedido[0]['nome'];
        $mesa    = $pedido[0]['idmesa'];
        
        $html = '';
        $html .= CSSpdf;
    
        $html .= '<div class="header-content">';
        $html .= "<h3>Pedido de(a) {$cliente}</h3>";
        $html .= "<h3>Tipo: {$categoria['descricao']}</h3>";

        if($pedido[0]['origin'] == 2){
            $html .= ($pedido[0]['origin'] == '2') ?"<p>CLIENTE RETIRA</p>":"<p>ENTREGA</p>";
        }else{
            $html .= "<p>N Mesa* {$mesa}</p>";
        }            

        $html .= "<p>N Pedido* {$idpedidovenda}</p>";
        $html .= '<p>__________________________<p>';
        $html .= '</div>'; // fechando div de header-content
        $html .= '<div class="body-content">';
        $html .= "<p>{$pedidoItem['quantidade']} - {$pedidoItem['nome']}</p>";
        if(!empty($pedidoItem['obs'])){
            $html .= '<p><strong>Obs:</strong> ' .$pedidoItem['obs']. '</p>';
        }
        if (!empty($pedidoItem['acrescimos'])) {
            $html .= '<div class="extras">Adicionais</div>';
            foreach ($pedidoItem['acrescimos'] as $acrescimo) {
                $html .= "<p class='extras-content'>-> {$acrescimo['quantidade']} - {$acrescimo['nome']}</p>";
            }
        }
        $html .= '</div>';

        $quantidade_impressao = $categoria['quantidade_impressao'] ?? 1;
        for($i=0;$i<$quantidade_impressao;$i++){
            $pdfs[] = self::pdf($html,$dados['largura'],$dados['altura']);
        }

        return $pdfs;
    }

    public static function PDFpedido($idempresa,$idpedidovenda){
        $pedido  = PedidoVenda::getPedidoVendas(null,$idempresa,$idpedidovenda,null,null);
        $dados   = self::getMedidas($idempresa);
   
        $cliente = $pedido[0]['nome'];
        $mesa    = $pedido[0]['idmesa'] ?? '';
        $total   = $pedido[0]['total_pedido'];
    
        // Primeira etapa: Agrupar itens por categoria
        $itensPorCategoria = [];
        foreach ($pedido[0]['itens'] as $item) {
            $idCategoria = $item['idcategoria'];
            if (!isset($itensPorCategoria[$idCategoria])) {
                $itensPorCategoria[$idCategoria] = [];
            }
            $itensPorCategoria[$idCategoria][] = $item;
        }
    
        // Segunda etapa: Para cada grupo de categoria, gerar o PDF
        $pdfs = [];
        foreach ($itensPorCategoria as $idCategoria => $itens) {
            $categoria = Categoria::select()->where('idcategoria',$idCategoria)->where('idempresa',$idempresa)->where('imprimir',1)->one();
            if(empty($categoria)){
                continue;    
            }
            $html = '';
            $html .= CSSpdf;
            $html .= '<div class="header-content">';
            $html .= "<h2>Pedido de(a) {$cliente}</h2>";
            $html .= "<h3>Tipo: {$categoria['descricao']}</h3>";

            if($pedido[0]['origin'] == 2){

                switch ($pedido[0]['metodo_entrega']) {
                    case '1':
                        $html .= "<p>CLIENTE ENTREGA</p>";
                        break;
                    case '2':
                        $html .= "<p>CLIENTE RETIRA</p>";
                        break;
                    case '3':
                        $html .= "<p>CLIENTE SALÃO</p>";
                        break;
                    default:
                        $html .= "<p>N Mesa* {$mesa}</p>";
                        break;
                }
            }else{
                $html .= "<p>N Mesa* {$mesa}</p>";
            } 

            $html .= "<p>N Pedido* {$idpedidovenda}</p>";
            $html .= '</div>'; // fechando div de header-content
            $html .= '<div class="body-content">';
            $index = 1;
            foreach ($itens as $item) {
                ($index == 1)?$html .= '<p>__________________________<p>':'';
                $html .= "<p>{$item['quantidade']} - {$item['nome']}</p>";
                if(!empty($item['obs'])){
                    $html .= '<p><strong>Obs:</strong> ' .$item['obs']. '</p>';
                }
                if (!empty($item['acrescimos'])) {
                    $html .= '<div class="extras">Adicionais</div>';
                    foreach ($item['acrescimos'] as $acrescimo) {
                        $html .= "<p class='extras-content'>-> {$acrescimo['quantidade']} - {$acrescimo['nome']}</p>";
                    }
                }
                $html .= '<p>__________________________<p>';
                $index++;
            }
            $html .= '</div>';

            $quantidade_impressao = $categoria['quantidade_impressao'] ?? 1;
            for($i=0;$i<$quantidade_impressao;$i++){
                $pdfs[] = self::pdf($html,$dados['largura'],$dados['altura']);
            }
        }
    
        
        return $pdfs;
    }

    public static function PDFpedente($idempresa,$idpedidovenda){

        $pedido  = PedidoVenda::getPedidoVendas(null,$idempresa,$idpedidovenda,null,null);
        $pedido  = Printer::vericarImpresao($pedido[0]);
        $dados   = self::getMedidas($idempresa);
   
        
        $cliente = $pedido['nome'];
        $mesa    = $pedido['idmesa'] ?? '';
        $total   = $pedido['total_pedido'];
    
        // Primeira etapa: Agrupar itens por categoria
        $itensPorCategoria = [];
        
        foreach ($pedido['itens'] as $item) {
            if($item['impresso']){
                continue;
            }
            $idCategoria = $item['idcategoria'];
            if (!isset($itensPorCategoria[$idCategoria])) {
                $itensPorCategoria[$idCategoria] = [];
            }
            $itensPorCategoria[$idCategoria][] = $item;
        }
    
        // Segunda etapa: Para cada grupo de categoria, gerar o PDF
        $pdfs = [];
        foreach ($itensPorCategoria as $idCategoria => $itens) {
            $categoria = Categoria::select()->where('idcategoria',$idCategoria)->where('idempresa',$idempresa)->where('imprimir',1)->one();
            if(empty($categoria)){
                continue;    
            }
            $html = '';
            $html .= CSSpdf;
            $html .= '<div class="header-content">';
            $html .= "<h2>Pedido de(a) {$cliente}</h2>";
            $html .= "<h3>Tipo: {$categoria['descricao']}</h3>";

            if($pedido['origin'] == 2){

                switch ($pedido[0]['metodo_entrega']) {
                    case '1':
                        $html .= "<p>CLIENTE ENTREGA</p>";
                        break;
                    case '2':
                        $html .= "<p>CLIENTE RETIRA</p>";
                        break;
                    case '3':
                        $html .= "<p>CLIENTE SALÃO</p>";
                        break;
                    default:
                        $html .= "<p>N Mesa* {$mesa}</p>";
                        break;
                }
            }else{
                $html .= "<p>N Mesa* {$mesa}</p>";
            } 

            $html .= "<p>N Pedido* {$idpedidovenda}</p>";
            $html .= '</div>'; // fechando div de header-content
            $html .= '<div class="body-content">';
            $index = 1;
            foreach ($itens as $item) {
                ($index == 1)?$html .= '<p>__________________________<p>':'';
                $html .= "<p>{$item['quantidade']} - {$item['nome']}</p>";
                if(!empty($item['obs'])){
                    $html .= '<p><strong>Obs:</strong> ' .$item['obs']. '</p>';
                }
                if (!empty($item['acrescimos'])) {
                    $html .= '<div class="extras">Adicionais</div>';
                    foreach ($item['acrescimos'] as $acrescimo) {
                        $html .= "<p class='extras-content'>-> {$acrescimo['quantidade']} - {$acrescimo['nome']}</p>";
                    }
                }
                $html .= '<p>__________________________<p>';
                $index++;
            }
            $html .= '</div>';

            $quantidade_impressao = $categoria['quantidade_impressao'] ?? 1;
            for($i=0;$i<$quantidade_impressao;$i++){
                $pdfs[] = self::pdf($html,$dados['largura'],$dados['altura']);
            }
        }

        Filaprint::update([
            'status' => 2 // 2 = impresso
           ,'data_impresso' => date('Y-m-d H:i:s')
        ])
        ->where('idempresa',$idempresa)
        ->where('idpedidovenda',$idpedidovenda)
        ->where('status',1)
        ->execute();

        return $pdfs;
    }

    public static function PDFrecibo($idempresa, $idpedidovenda){
        try{  
            $pedido  = PedidoVenda::getPedidoVendas(null,$idempresa,$idpedidovenda,null,null);
            $dados = self::getMedidas($idempresa);
            $getCupons = Cupon::getCuponsPedido($idempresa,$idpedidovenda);
            if(!isset($dados['empresa'])){
                throw new Exception('empresa não localizada!!');
            }
            $empresa = $dados['empresa'];
        
        
            $cliente = $pedido[0]['nome'];
            $mesa    = $pedido[0]['idmesa'];
            $total   = $pedido[0]['total_pedido'];
            $origin  = $pedido[0]['origin'];
        
            $html = '';
            $html .= CSSpdf;
            $html .= '<div class="header-content">';
            $html .= "<h2>Recibo de(a) {$cliente}</h2>";
            $html .= "<p>{$empresa['nome']}</p>";
            $html .= "<p>{$empresa['endereco']}, {$empresa['numero']}</p>";
            $html .= "<p>CNPJ: {$empresa['cnpj']}</p>";
            $html .= "<p>Descrição: {$empresa['dilema']}</p>";
            if(isset($getCupons['valor_cupons']) && !empty($getCupons['valor_cupons'])){
                $html .= "<p>N* Cupons: ".$getCupons['idscupon']."</p>";
                $html .= "<p>Deconto Cupons: ".number_format($getCupons['valor_cupons'], 2, ',', '.')."</p>";
                $total = $total - $getCupons['valor_cupons'];
            }
            $html .= "<p>Total: ".number_format($total, 2, ',', '.')."</p>";
            if($origin == 2 && !empty($pedido[0]['obs']) && isset($entrega['troco']) && !empty($entrega['troco'])){
                $entrega = $pedido[0]['obs'];
                $html .= "<p>Troco Para: {$entrega['troco']}</p>";
                $troco =  self::numeroEUA($entrega['troco']);
                $val = ($troco > $total) ? $troco - $total : $total - $troco;
                $html .= "<p>Troco: ".number_format($val, 2, ',', '.')."</p>";
            }
            $html .= '</div>'; // fechando div de header-content
            $html .= '<div class="body-content">';
            if(trim($mesa) != ''){
                $html .= "<p>N Mesa* {$mesa}</p>";
            }
            $html .= "<p>N Pedido* {$idpedidovenda}</p>";
            if($origin == 2 && !empty($pedido[0]['obs'])){
               
                $entrega = $pedido[0]['obs'];
                $bairro = Bairros::select()->where('idbairro',$entrega['idbairro'])->one();

                $html .= '<p>_____________Entrega_____________<p>';
                $html .= "<p>Endereço: {$entrega['endereco']}</p>";
                $html .= "<p>Numero: {$entrega['numero']}</p>";
                $html .= "<p>Complemento: {$entrega['complemento']}</p>";
                $html .= "<p>Bairro: {$bairro['nome']}</p>";
                $html .= "<p>Cidade: {$entrega['nome_cidade']}</p>";
                $html .= "<p>Celular: {$entrega['celular']}</p>";
                $html .= "<p>Forma Pagamento: {$entrega['nome_pagamento']}</p>";
                if(isset($entrega['troco']) && !empty($entrega['troco']) ){
                    $html .= "<p>Troco Para: {$entrega['troco']}</p>";
                    $troco =  self::numeroEUA($entrega['troco']);
                    $val = ($troco > $total) ? $troco - $total : $total - $troco;
                    $html .= "<p>Troco: ".number_format($val, 2, ',', '.')."</p>";
                }
                $html .= "<p>Taxa: ".number_format($entrega['taxa'], 2, ',', '.')."</p>";
            }
            if (!empty($pedido[0]['itens'])) {
                $index = 1;
                foreach ($pedido[0]['itens'] as $pedidoItem) {
                    ($index == 1)?$html .= '<p>__________________________<p>':'';
                    $html .= "<p>item {$index}</p>";
                    $html .= "<p>{$pedidoItem['quantidade']} x {$pedidoItem['nome']} - R$ " . number_format( $pedidoItem['quantidade']*$pedidoItem['preco'] , 2, ',', '.') . "</p>";
                    if(!empty($pedidoItem['obs'])){
                        $html .= '<p><strong>Obs:</strong> ' .$pedidoItem['obs']. '</p>';
                    }
                    if (!empty($pedidoItem['acrescimos'])) {
                        $html .= '<div class="extras">Adicionais</div>';
                        foreach ($pedidoItem['acrescimos'] as $acrescimo) {
                            $html .= "<p class='extras-content'>-> {$acrescimo['quantidade']} x {$acrescimo['nome']} -";
                            $html .= "R$ ".number_format($acrescimo['quantidade']*$acrescimo['preco'], 2, ',', '.');
                            $html .= ((int)$acrescimo['gratis'] > 0) ? ' (Gratis x'.$acrescimo['gratis'].')' : '';
                            $html .= "</p>";
                        }
                    }
                    $html .= '<p>__________________________<p>';
                    $index++;
                }
            }

            $html .= "<p>Total do Pedido: ".number_format($total, 2, ',', '.')."</p>";
            $html .= "<br>";
            $html .= "<p>Agradecemos pela sua preferência e esperamos vê-lo(a) novamente em breve!</p>";
            $html .= '</div>'; // fechando div de body-content
        
            return self::pdf($html,$dados['largura'],$dados['altura']);
        }catch(\Exception $e){
            throw new Exception('ops.. alconteceu algo na fila de impressão Conate o suporte!'.$e->getMessage());
        }
    }
    
    public static function pdf($html,$largura, $altura){
        $left = 0;
        $right = 0;
        if((int)$largura <= 58){
            $left = -45;
            $right = -45;
        }
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => [(int)$largura, (int)$altura],  // Largura de 80mm e altura de 297mm
            'margin_left' => $left,
            'margin_right' => $right,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'margin_header' => 0,
            'margin_footer' => 0
        ]);
        $mpdf->WriteHTML($html);
        $mpdf->SetFont('Arial');
        $pdfString = $mpdf->Output('', 'S');
        $base64Pdf = base64_encode($pdfString);
        return 'data:application/pdf;base64,'.$base64Pdf;
    }

}
