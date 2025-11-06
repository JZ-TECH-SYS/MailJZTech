<?php


/**
 * Classe responsável por ter varias funções auxiliares
 *
 * @autor: joaosn
 * @dateInicio: 23/05/2023
 */

namespace src\handlers;
error_reporting(E_ALL & ~E_DEPRECATED);

use Exception;
use src\handlers\MontarPDF;
use src\handlers\PedidoVenda as PV;
use src\models\Filaprint;
use core\Database as db;
use src\models\Categoria;
use src\models\Empresa;
use src\models\Pessoa;
use src\models\Endereco;
use src\models\Cidade;
use src\models\Empresa_parametro;
use src\models\Pedido_venda;


/**
 * Classe Help com funções auxiliares
 * 
 * @package src\handlers
 */
class Printer
{
  
   /**
    *  Geração de PDF para impressão
    *  @param array $dados
    */
   public static function getPrint(array $dados) {
      try {
         switch ($dados['tipo_impressao']) {
            case 'all':
               $pdf =  MontarPDF::PDFpedido($dados['idempresa'], $dados['idpedidovenda']);
               break;
            case 'recibo':
               $pdf =  MontarPDF::PDFrecibo($dados['idempresa'], $dados['idpedidovenda']);
               break;
            case 'pedente':
               $pdf =  MontarPDF::PDFpedente($dados['idempresa'], $dados['idpedidovenda']);
               break;
            default:
               $pdf = MontarPDF::PDFpedidoItem($dados['idempresa'], $dados['idpedidovenda'], $dados['idpedidovendaitem']);
               break;
         }
         return ['pdfs' => $pdf];
      }catch(\Exception $e){
         throw new Exception('Ouve Poblema Ao getPrint Montar PDF '.$e->getMessage(), 400);
      } 
   }

   /**
    *  adiciona um item ou pedido na fila de impressão
    */
   public static function sendPrint($dados){
      try {
         $idfila = [];
         if($dados['idpedidovendaitem'] == 'all' || $dados['idpedidovendaitem'] == 'pedente'){
            $getPedido = PV::getPedidoVendas(null,$dados['idempresa'],$dados['idpedidovenda'],null);
            $pedido    = Printer::vericarImpresao($getPedido[0]);

            if(isset($pedido['itens'])){
               switch ($dados['idpedidovendaitem']) {
                  case 'all':
                        $quantidade = count($pedido['itens']);
                        $contagem = 0;
                        foreach($pedido['itens'] as $item){
                              $isUltimo = ($contagem == $quantidade - 1) ? true : false;
                              $idfila[] = Filaprint::insert([
                                 'idempresa' => $dados['idempresa'],
                                 'idpedidovenda' => $dados['idpedidovenda'],
                                 'idpedidovendaitem' => $item['idpedido_item'],
                                 'tipo_impressao'    => $dados['idpedidovendaitem'],
                                 'status'  => ($isUltimo) ? 1 : 2 // 1 = aguardando impressão
                              ])->execute();   
                              $contagem++;
                        }
                     break;
                  default:
                        foreach($pedido['itens'] as $item){
                              if($item['impresso']){
                                 continue;
                              }
                              $idfila[] = Filaprint::insert([
                                 'idempresa' => $dados['idempresa'],
                                 'idpedidovenda' => $dados['idpedidovenda'],
                                 'idpedidovendaitem' => $item['idpedido_item'],
                                 'tipo_impressao'    => $dados['idpedidovendaitem'],
                                 'status'  => 1 
                              ])->execute();   
                        }
                     break;
               }
               
            }
         }else{
            $idfila[] = Filaprint::insert([
               'idempresa' => $dados['idempresa'],
               'idpedidovenda' => $dados['idpedidovenda'],
               'idpedidovendaitem' => is_numeric($dados['idpedidovendaitem']) ?  $dados['idpedidovendaitem'] : 'false',
               'tipo_impressao'    => is_numeric($dados['idpedidovendaitem']) ? 'item' : $dados['idpedidovendaitem'], // item ou recibo
               'status'  => 1 // 1 = aguardando impressão
            ])->execute();
         }
         return $idfila;
      }catch(\Exception $e){
         throw new Exception('Ouve Poblema Ao adicinar essa solicitação na Fila '.$e->getMessage(), 400);
      } 
      
   }

   /**
    * 
    */
   public static function cronImpressaoDireta($idempresa = null){
      $return = ['pdfs'=>false];
      $idempresa = $idempresa ?? ($_SESSION['empresa']['idempresa']??null);
      try {
         db::getInstance()->beginTransaction();
         $getONePrint = Filaprint::select()->where('status',1)->where('idempresa',$idempresa)->one();
         //print_r($getONePrint);die;
         if($getONePrint){
            $dados = [
               'idempresa' => $getONePrint['idempresa'],
               'idpedidovenda' => $getONePrint['idpedidovenda'],
               'idpedidovendaitem' => $getONePrint['idpedidovendaitem'],
               'tipo_impressao'    => $getONePrint['tipo_impressao']
            ];
            $pdfs = self::getPrint($dados);
            if(!$pdfs['pdfs']){
               throw new Exception('não foi posivel montar PDF contate Suporte Não gerou PDF!!');
            }
            Filaprint::update([
                'status' => 2 // 2 = impresso
               ,'data_impresso' => date('Y-m-d H:i:s')
            ])
               ->where('idprint',$getONePrint['idprint'])
               ->where('idempresa',$getONePrint['idempresa'])
               ->where('idpedidovenda',$getONePrint['idpedidovenda'])
               ->where('idpedidovendaitem',$getONePrint['idpedidovendaitem'])
               ->where('status',1)
            ->execute();
            db::getInstance()->commit();
            if(is_array($pdfs['pdfs'])){
               $return['pdfs'] = $pdfs['pdfs'];
            }else{
               $return['pdfs'][] = $pdfs['pdfs'];
            }
         }
      }catch(\Exception $e){
         db::getInstance()->rollback();
         print_r($e->getMessage());
         throw new Exception('ops.. alconteceu algo na fila de impressão Conate o suporte!'.$e->getMessage());
      }

      return $return;
   }

   public static function vericarImpresao($pedido){
      if(isset($pedido['itens']) && !empty($pedido['itens'])){
          foreach($pedido['itens'] as &$item){
              $item['impresso'] = false; 

              $prints = Filaprint::select()
                  ->where('idempresa', $item['idempresa'])
                  ->where('idpedidovenda', $item['idpedidovenda'])
                  ->where('idpedidovendaitem', $item['idpedido_item'])
                  ->where('status', 2) // 2 = impresso
                  ->one();

               if($prints){
                  $item['impresso'] = true; 
               }

               $imprime = Categoria::select()->where('idempresa', $item['idempresa'])->where('idcategoria', $item['idcategoria'])->one();
               $item['imprime'] = isset($imprime['imprimir']) && $imprime['imprimir']  == 1 ? true : false;
          }
      }
      return $pedido;
   }

   /**
    * lista os pedidos que estão na fila de impressão
    */
   public static function getFilaImpressao($idempresa){
      try {
         $lista = Filaprint::select()
            ->where('idempresa',$idempresa)
            ->where('status',1)
            ->orderBy('data_cadastro','DESC')
            ->limit(15)
            ->get();
         return $lista;
      } catch (\Exception $e) {
         throw new Exception('Ouve Poblema Ao getFilaImpressao '.$e->getMessage(), 400);
      }
   }

   public static function marcarImpresso($data){
      try {
         Filaprint::update([
            'status' => 2 // 2 = impresso
           ,'data_impresso' => date('Y-m-d H:i:s')
         ])
            ->where('idprint',$data['idprint'])
            ->where('idempresa',$data['idempresa'])
         ->execute();
         return true;
      } catch (\Exception $e) {
         throw new Exception('Ouve Poblema Ao marcarImpresso '.$e->getMessage(), 400);
      }
   }
  
}
