<?php

/**
 * Classe responsável por ter varias funções auxiliares
 *
 * @autor: joaosn
 * @dateInicio: 23/05/2023
 */

namespace src\handlers;

use Exception;
use src\handlers\PedidoVenda as PV;
use src\models\Filaprint;
use core\Database as db;
use src\handlers\MontarHTML as Ticket;
use src\handlers\impressao\PrintRenderer;

/**
 * Classe Help com funções auxiliares
 * 
 * @package src\handlers
 */
class Printer2
{

   /**
    * Gera ticket(s) conforme tipo – devolve ARRAY de objetos com texto e impressora.
    */
   public static function getPrint(array $d): array
   {
      $tickets = [];
      switch ($d['tipo_impressao']) {
         case 'all':
            $tickets = PrintRenderer::pedido($d['idempresa'], $d['idpedidovenda']);
            break;
         case 'recibo':
            $tickets = PrintRenderer::recibo($d['idempresa'], $d['idpedidovenda']);
            break;
         case 'pedente':
            $tickets = PrintRenderer::pendente($d['idempresa'], $d['idpedidovenda']);
            break;
         default:
            $tickets = PrintRenderer::pedidoItem(
               $d['idempresa'],
               $d['idpedidovenda'],
               $d['idpedidovendaitem']
            );
      }
      return ['tickets' => $tickets];
   }


   /**
    *  adiciona um item ou pedido na fila de impressão
    */
   public static function sendPrint($dados)
   {
      try {
         $idfila = [];
         if ($dados['idpedidovendaitem'] == 'all' || $dados['idpedidovendaitem'] == 'pedente') {
            $getPedido = PV::getPedidoVendas(null, $dados['idempresa'], $dados['idpedidovenda'], null);
            $pedido    = Printer::vericarImpresao($getPedido[0]);

            if (isset($pedido['itens'])) {
               switch ($dados['idpedidovendaitem']) {
                  case 'all':
                     $quantidade = count($pedido['itens']);
                     $contagem = 0;
                     foreach ($pedido['itens'] as $item) {
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
                     foreach ($pedido['itens'] as $item) {
                        if ($item['impresso']) {
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
         } else {
            $idfila[] = Filaprint::insert([
               'idempresa' => $dados['idempresa'],
               'idpedidovenda' => $dados['idpedidovenda'],
               'idpedidovendaitem' => is_numeric($dados['idpedidovendaitem']) ?  $dados['idpedidovendaitem'] : 'false',
               'tipo_impressao'    => is_numeric($dados['idpedidovendaitem']) ? 'item' : $dados['idpedidovendaitem'], // item ou recibo
               'status'  => 1 // 1 = aguardando impressão
            ])->execute();
         }
         return $idfila;
      } catch (\Exception $e) {
         throw new Exception('Ouve Poblema Ao adicinar essa solicitação na Fila ' . $e->getMessage(), 400);
      }
   }

   /**
    * Busca e processa próxima impressão da fila
    * 
    * @param int|null $idempresa ID da empresa
    * @param bool $comImpressora Se true, retorna array de objetos {texto, impressora}. Se false (padrão), retorna apenas strings HTML (retrocompatibilidade)
    * @return array
    */
   public static function cronImpressaoDireta($idempresa = null, $comImpressora = false)
   {
      $return = ['texto' => false];
      $idempresa = $idempresa ?? ($_SESSION['empresa']['idempresa'] ?? null);
      try {
         db::getInstance()->beginTransaction();
         $getONePrint = Filaprint::select()->where('status', 1)->where('idempresa', $idempresa)->one();

         if ($getONePrint) {
            $dados = [
               'idempresa' => $getONePrint['idempresa'],
               'idpedidovenda' => $getONePrint['idpedidovenda'],
               'idpedidovendaitem' => $getONePrint['idpedidovendaitem'],
               'tipo_impressao'    => $getONePrint['tipo_impressao']
            ];
            $textos = self::getPrint($dados);
            if (!$textos['tickets']) {
               throw new Exception('não foi posivel montar HTML contate Suporte Não gerou HTML!!');
            }
            Filaprint::update([
               'status' => 2,
               'data_impresso' => date('Y-m-d H:i:s')
            ])
               ->where('idprint', $getONePrint['idprint'])
               ->where('idempresa', $getONePrint['idempresa'])
               ->where('idpedidovenda', $getONePrint['idpedidovenda'])
               ->where('idpedidovendaitem', $getONePrint['idpedidovendaitem'])
               ->where('status', 1)
               ->execute();
            db::getInstance()->commit();
            
            // ✅ RETROCOMPATIBILIDADE: Por padrão retorna apenas strings HTML
            if ($comImpressora) {
               // Novo formato: retorna objetos { texto, impressora }
               $return['texto'] = $textos['tickets'];
            } else {
               // Formato antigo: retorna apenas strings HTML
               $return['texto'] = [];
               foreach ($textos['tickets'] as $ticket) {
                  if (is_array($ticket) && isset($ticket['texto'])) {
                     $return['texto'][] = $ticket['texto'];
                  } else {
                     $return['texto'][] = $ticket;
                  }
               }
            }
         }
      } catch (\Exception $e) {
         db::getInstance()->rollback();
         print_r($e->getMessage());
         throw new Exception('ops.. alconteceu algo na fila de impressão Conate o suporte!' . $e->getMessage());
      }

      return $return;
   }
}