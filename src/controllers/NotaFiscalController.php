<?php

/**
 * Classe responsável pela emissão de notas fiscais eletrônicas.
 * Autor: Joaosn
 * Data de início: 18/10/2024
 */

namespace src\controllers;
date_default_timezone_set('America/Sao_Paulo');

use \core\Controller as ctrl;
use \src\handlers\NotaFiscal as NFEHelp;
use NFePHP\DA\NFe\Danfce;
use NFePHP\DA\NFe\Danfe;
use src\handlers\Ultils\Help;
use src\models\Cfops;
use src\models\Estado;
use src\models\Ncm_cest;
use src\models\Nota_fiscal;
use src\models\Origem_mercadoria;
use src\models\Tipos_imposto;

class NotaFiscalController extends ctrl
{

    public function gerarNota($args)
    {
        try {
            $pedido = $args['idpedidovenda'];
            $empresa = $args['idempresa'];
            $tipoNota = $args['tiponota'] ?? 65; // nfe ou nfce

            if (empty($pedido) || empty($empresa)) {
                throw new \Exception("Parâmetros inválidos informe o id da empresa e o id do pedido de venda.");
            }

            $nota = NFEHelp::gerarNotaVenda($empresa, $pedido,$tipoNota);
            ctrl::response($nota, 200);
        } catch (\Throwable $th) {
            ctrl::rejectResponse($th);
        }
    }

    public function processarNotasPendente()
    {
        try {
            $notas = NFEHelp::processarNotas();
            ctrl::response($notas, 200);
        } catch (\Throwable $th) {
            ctrl::rejectResponse($th);
        }
    }

    public static function cancelarNota($args)
    {
        try {
            $idregistronota = $args['idregistronota'];
            $idempresa = $args['idempresa'];
            if (empty($idregistronota) || empty($idempresa)) {
                throw new \Exception("Informe o id da nota fiscal.");
            }

            $cancelamento = NFEHelp::cancelarNota($idempresa, $idregistronota);
            ctrl::response($cancelamento, 200);
        } catch (\Throwable $th) {
            ctrl::rejectResponse($th);
        }
    }

    public static function reenviarNota($args)
    {
        try {
            $idregistronota = $args['idregistronota'];
            $idempresa = $args['idempresa'];

            if (empty($idempresa) || empty($idregistronota)) {
                throw new \Exception("Informe o id da nota fiscal.");
            }

            $reenvio = NFEHelp::reenviarNota($idempresa, $idregistronota);
            ctrl::response($reenvio, 200);
        } catch (\Throwable $th) {
            ctrl::rejectResponse($th);
        }
    }


    public function inutilizarNotas($args)
    {
        try {
            $idempresa = $args['idempresa'];
            $inutilizacao = NFEHelp::inutilizarNotas($idempresa);
            ctrl::response($inutilizacao, 200);
        } catch (\Throwable $th) {
            ctrl::rejectResponse($th);
        }
    }

    public function listaNotas($args)
    {
        try {
            $idempresa = $args['idempresa'];
            $dataini = $args['dataini'] ?? date('Y-m-d', strtotime('-1 month'));
            $datafim = $args['datafim'] ?? date('Y-m-d');
            $pgtoStr = $_GET['pgto'] ?? '';
            $idsPagto = array_filter(array_map('intval', explode(',', $pgtoStr)));

            $notas = NFEHelp::listaNotas($idempresa, $dataini, $datafim, $idsPagto);
            ctrl::response($notas, 200);
        } catch (\Throwable $th) {
            ctrl::rejectResponse($th);
        }
    }

    public function imprimir(array $args): void
    {
        try {
            $empresa = (int) ($args['idempresa']     ?? 0);
            $pedido  = (int) ($args['idpedidovenda'] ?? 0);

            if (!$empresa || !$pedido) {
                throw new \Exception('Informe idempresa e idpedidovenda.');
            }

            /* busca nota concluída (status 4 = concluído) */
            $nf = Nota_fiscal::select()
                ->where('idempresa', $empresa)
                ->where('idpedidovenda', $pedido)
                ->where('status_processamento', 4)
                ->one();

            if (!$nf || empty($nf['xml'])) {
                throw new \Exception('Nota não encontrada ou sem XML salvo Veriqfique se nota foi autorizada!');
            }

            $modelo = (int) $nf['modelo'];   // 55 = NFe, 65 = NFCe
            $xml    = $nf['xml'];

            if ($modelo === 65) {
               
                // NFC-e como PDF de bobina 80
                $danfe = new Danfce($xml, 'P', '80');
                $danfe->setFont('arial');

                $pdf   = $danfe->render(); // binário do PDF
                $loadXML = new \SimpleXMLElement($xml);
                $json = json_decode(json_encode($loadXML), true);

                ctrl::response([
                    'type'    => 'pdf',
                    'content' => base64_encode($pdf)
                ], 200);
            } else {
                // NFe → PDF
                $danfe   = new Danfe($xml);
                $pdf     = $danfe->render();
                $dataUri = 'data:application/pdf;base64,' . base64_encode($pdf);
            }

            // Retorna um link pronto para abrir no navegador
            ctrl::response([
                'link' => $dataUri
            ], 200);
        } catch (\Throwable $e) {
            // converte Error em Exception p/ manter type-hint
            if (!$e instanceof \Exception) {
                $e = new \Exception($e->getMessage(), 0, $e);
            }
            ctrl::rejectResponse($e);
        }
    }

    public function getTipoImposto()
    {
        try {
            $tiposimposto = Tipos_imposto::select()->where('ativo', 1)->get();
            ctrl::response($tiposimposto, 200);
        } catch (\Throwable $th) {
            ctrl::rejectResponse($th);
        }
    }

    public function getNcmCest()
    {
        try {
            $ncm_cest = Ncm_cest::select()->where('ativo', 1)->get();
            ctrl::response($ncm_cest, 200);
        } catch (\Throwable $th) {
            ctrl::rejectResponse($th);
        }
    }

    public function getOrigemMercadoria()
    {
        try {
            $origem_mercadoria = Origem_mercadoria::select()->get();
            ctrl::response($origem_mercadoria, 200);
        } catch (\Throwable $th) {
            ctrl::rejectResponse($th);
        }
    }

    public function getCfops()
    {
        try {
            $cfops = Cfops::select()->where('ativo', 1)->get();
            ctrl::response($cfops, 200);
        } catch (\Throwable $th) {
            ctrl::rejectResponse($th);
        }
    }

    public function getUfs()
    {
        try {
            $ufs = Estado::select()->get();
            ctrl::response($ufs, 200);
        } catch (\Throwable $th) {
            ctrl::rejectResponse($th);
        }
    }
}
