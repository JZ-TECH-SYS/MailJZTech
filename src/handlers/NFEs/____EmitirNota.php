<?php

namespace src\handlers\NFEs;

use Exception;
use Throwable;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Tools;
use NFePHP\NFe\Make;
use NFePHP\NFe\Common\Standardize;
use src\handlers\NFEs\UtilsNF\XMLHelper;
use src\handlers\NFEs\UtilsNF\Config;
use src\handlers\NFEs\UtilsNF\Validador;
use src\handlers\NFEs\UtilsNF\XMLtags;
use src\handlers\NFEs\UtilsNF\XMLcStatResponse;
use src\handlers\PedidoVenda;

class EmitirNota
{
    public static function gerarNota($idempresa, $idpedidovenda)
    {
        try {
            // Carrega pedido
            $pedido = PedidoVenda::getPedidoVendaNFE($idempresa, $idpedidovenda);

            //print_r($pedido); die;
            $tipoNota = $pedido['tipo_nota'] ?? 65; // 55 - NF-e, 65 - NFC-e

            Validador::validarPedido($idempresa, $idpedidovenda, $pedido);
            $codigoCRT = $pedido['emitente']['codtrib'];

            // Certificado
            $configJson = Config::getConfigJson($pedido['emitente']['cnpj']);
            $certificado = Config::getCertificado($pedido['emitente']['cnpj']);

            // Inicia NFe
            $nfe = new Make();
            $nfe->taginfNFe(XMLtags::informacaoNFE());
            $nfe->tagide(XMLtags::ide($pedido, $idpedidovenda, $idempresa, $tipoNota));
            $nfe->tagemit(XMLtags::emit($pedido, $codigoCRT));
            $nfe->tagenderEmit(XMLtags::enderEmit($pedido));
            $nfe->tagdest(XMLtags::dest($pedido));
            $nfe->tagenderDest(XMLtags::enderDest($pedido));

            $totalICMS = 0.00;
            $totalPIS = 0.00;
            $totalCOFINS = 0.00;
            $itemNumber = 1;

            // Produtos
            foreach ($pedido['itens'] as $item) {
                $item['imposto'] = $item['imposto'] ?? [];
                $preco = (float)$item['preco'];
                $qtd = (float)$item['quantidade'];
                $vProd = $preco * $qtd;

                $nfe->tagprod(XMLtags::prod($item, $itemNumber));
                $nfe->tagimposto(XMLtags::imposto($itemNumber));

                if (!empty($item['imposto'])) {
                    foreach ($item['imposto'] as $imp) {
                        if (empty($imp['tipo_imposto'])) continue;

                        switch (strtoupper($imp['tipo_imposto'])) {
                            case 'ICMS':
                                $icms = XMLtags::buildICMS($imp, $vProd, $itemNumber, $codigoCRT);
                                $nfe->{$icms['method']}($icms['data']);
                                if (isset($icms['data']->vICMS)) $totalICMS += (float)$icms[0]->vICMS;
                                break;

                            case 'PIS':
                                $pis = XMLtags::buildPIS($imp, $vProd, $codigoCRT, $itemNumber);
                                $nfe->{$pis['method']}($pis['data']);
                                if (isset($pis['data']->vPIS)) $totalPIS += (float)$pis['data']->vPIS;
                                break;

                            case 'COFINS':
                                $cofins = XMLtags::buildCOFINS($imp, $vProd, $codigoCRT, $itemNumber);
                                $nfe->{$cofins['method']}($cofins['data']);
                                if (isset($cofins['data']->vCOFINS)) $totalCOFINS += (float)$cofins['data']->vCOFINS;
                                break;

                            case 'IPI':
                                $ipi = XMLtags::buildIPI($imp, $vProd, $itemNumber);
                                $nfe->{$ipi['method']}($ipi['data']);
                                break;

                            case 'ISSQN':
                                $issqn = XMLtags::buildISSQN($imp, $vProd, $itemNumber);
                                $nfe->{$issqn['method']}($issqn['data']);
                                break;

                            default:
                                // Imposto ainda não tratado
                                break;
                        }
                   
                    }
                }

                $itemNumber++;
            }

           

            // Totais
            $nfe->tagICMSTot(XMLtags::ICMSTot($pedido, $totalICMS, $totalPIS, $totalCOFINS));
            $nfe->tagtransp(XMLtags::transp($pedido));
            $nfe->tagpag(XMLtags::pag());
            $nfe->tagdetPag(XMLtags::detPag($pedido));
            $nfe->taginfAdic(XMLtags::infAdic($pedido));
            $nfe->taginfRespTec(XMLtags::infRespTec());

            // Monta XML
            $xml = $nfe->montaNFe();


            $tools = new Tools($configJson['stringJson'], Certificate::readPfx($certificado, $configJson['password']));
            if ($tipoNota == 65) {
                $tools->model(65);
            }
            $xmlAssinado = $tools->signNFe($xml);

            // Envia para Sefaz
            $idLote = str_pad(XMLHelper::getRegistroNota($idempresa, $idpedidovenda), 15, '0', STR_PAD_LEFT);
            $resp = (new Standardize())->toStd(
                $tools->sefazEnviaLote([$xmlAssinado], $idLote, 1)
            );

            if (!isset($resp->cStat)) {
                throw new Exception('Resposta da Sefaz malformada ou vazia.');
            }

            switch ((int)$resp->cStat) {
                case 103: case 105:
                    $recibo = $resp->infRec->nRec ?? null;
                    XMLHelper::atualizarNota([
                        'idsituacaonotasefaz' => 1,
                        'status_processamento' => 2,
                        'xml' => $xmlAssinado,
                        'chavesefaz' => $nfe->getChave($xmlAssinado),
                        'recibo_sefaz' => $recibo,
                        'cstat_sefaz' => $resp->cStat,
                        'msgsefaz' => $resp->xMotivo,
                        'data_hora_processamento' => date('Y-m-d H:i:s')
                    ], $idempresa, $idpedidovenda, true);
                    return $recibo;

                case 104:
                    $prot = $resp->protNFe->infProt;
                    $autorizada = $prot->cStat == 100;
                    XMLHelper::atualizarNota([
                        'idsituacaonotasefaz' => $autorizada ? 2 : 3,
                        'status_processamento' => 4,
                        'xml' => $xmlAssinado,
                        'chavesefaz' => $nfe->getChave($xmlAssinado),
                        'numeronota' => XMLtags::ide($pedido, $idpedidovenda, $idempresa, $tipoNota)->nNF,
                        'serie' => XMLtags::ide($pedido, $idpedidovenda, $idempresa, $tipoNota)->serie,
                        'modelo' => XMLtags::ide($pedido, $idpedidovenda, $idempresa, $tipoNota)->mod,
                        'protocolo_autorizacao' => $prot->nProt,
                        'data_hora_autorizacao' => date('Y-m-d H:i:s', strtotime($prot->dhRecbto)),
                        'cstat_sefaz' => $prot->cStat,
                        'msgsefaz' => $prot->xMotivo
                    ], $idempresa, $idpedidovenda, true);
                    return 'Autorizada';
            }

        } catch (Throwable $e) {
            if (isset($nfe)) {
                $errors = $nfe->getErrors();
                $erroStr = implode("\n", array_map(fn($er) => "Erro: " . $er, $errors));
                XMLHelper::setErroNota($idempresa, $idpedidovenda, $e->getMessage() . $erroStr, true);
            } else {
                XMLHelper::setErroNota($idempresa, $idpedidovenda, $e->getMessage(), true);
            }
            throw new Exception($e->getMessage());
        }
    }
}
