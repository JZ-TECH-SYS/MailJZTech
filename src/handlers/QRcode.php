<?php


/**
 * Classe responsável por ter varias funções auxiliares
 *
 * @autor: joaosn
 * @dateInicio: 23/05/2023
 */

namespace src\handlers;

use Endroid\QrCode\QrCode as QRcodeGet;
use Endroid\QrCode\Writer\PngWriter;
use core\Controller as ctrl;
use src\Config;
use src\models\Empresa;
use src\handlers\PedidoVenda;

/**
 * Classe QRcode com funções auxiliares
 * 
 * @package src\handlers
 */
class QRcode
{
  
   /**
    *  Geração de QR para impressão
    *  @param array $dados
    */
   public static function getQR($idempresa,$idmesa = null,$nome = null) {
      // Criar QR Code
        $empresa = Empresa::select()->where('idempresa', $idempresa)->one();
        if($idmesa && is_numeric($idmesa)){
            $mesauso = PedidoVenda::validaMesa($idempresa,$idmesa, $nome);
            if($mesauso){
               throw new \Exception("Mesa em já em uso!");
            }
        }

        $empresa = Empresa::select()->where('idempresa', $idempresa)->one();
        $url  =  ( isset($idmesa) && !empty($idmesa) ) ? 'pedido/'.$empresa['nome'].'/salao/'.$idmesa : 'pedido/'.$empresa['nome'].'/salao' ;
       
        if($idmesa == 'GERAL'){
            $url = 'pedido/'.$empresa['nome'];
        }
       
        $link = Config::FRONT_URL . $url;
        $qrCode = QRcodeGet::create($link)
        ->setSize(300) // tamanho em pixels
        ->setMargin(10); // margem em torno do QR Code

        // Escrever o QR Code em uma imagem PNG
        $writer = new PngWriter();
        $tempImagePath = './../public/tmp/qrcode_' . uniqid() . '.png';
        $writer->write($qrCode)->saveToFile($tempImagePath);

        // Ler a imagem e converter para base64
        $type = pathinfo($tempImagePath, PATHINFO_EXTENSION);
        $data = file_get_contents($tempImagePath);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
        // Remover a imagem temporária
        unlink($tempImagePath);

        return [ 
            'url'       => $link,
            'empresa'   => $empresa['nome'],
            'idempresa' => $idempresa,
            'tipo'      => $idmesa ? 'Mesa' : 'Salão',
            'qrcode'    => $base64 
        ];
   }

    /**
     * Gera um QR Code (Data URI) a partir de um texto/URL arbitrário.
     * Retorna string vazia em caso de falha, para não quebrar fluxos de impressão.
     */
    public static function dataUriFromText(string $text, int $size = 200, int $margin = 10): string
    {
        try {
            if (trim($text) === '') return '';

            $qrCode = QRcodeGet::create($text)
                ->setSize($size)
                ->setMargin($margin);

            $writer = new PngWriter();

            // Compatível com endroid/qr-code v4: escreve para arquivo temporário como no getQR
            $tempImagePath = './../public/tmp/qrcode_' . uniqid() . '.png';
            $writer->write($qrCode)->saveToFile($tempImagePath);

            $type = pathinfo($tempImagePath, PATHINFO_EXTENSION) ?: 'png';
            $data = @file_get_contents($tempImagePath);
            $base64 = $data ? ('data:image/' . $type . ';base64,' . base64_encode($data)) : '';
            if (file_exists($tempImagePath)) {
                @unlink($tempImagePath);
            }

            return $base64;
        } catch (\Throwable $e) {
            return '';
        }
    }
 
}
