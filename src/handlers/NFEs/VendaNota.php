<?php

namespace src\handlers\NFEs;

use Throwable;
use stdClass;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Tools;
use NFePHP\NFe\Make;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Complements;
use src\handlers\NFEs\UtilsNF\XMLHelper;
use src\handlers\NFEs\UtilsNF\Config;
use src\handlers\NFEs\UtilsNF\Validador;
use src\handlers\NFEs\UtilsNF\XMLtags;

class VendaNota
{
    /**
     * Emite NF-e / NFC-e (55/65) para VENDA.
     * $pedido['tiponota'] deve ser 55 (NF-e) ou 65 (NFC-e).
     * @param array $pedido Saída de PedidoVenda::getPedidoVendaNFE()
     * @return string Mensagem de status ou erro
     * @throws Throwable
     */
    public static function gerar(array $pedido): string
    {

        try {
            Validador::validarPedido(
                $pedido['idempresa'],
                $pedido['idpedidovenda'],
                $pedido
            );

            $tipoNota     = 65;
            $cnpjEmitente = $pedido['emitente']['cnpj'];
            $codigoCRT    = $pedido['emitente']['codtrib'];

            // Configuração para o Tools
            $conf        = Config::getConfigJson($cnpjEmitente);//teste remover depois
            $stringJson  = $conf['stringJson'] ?? '';
            $password    = $conf['password']   ?? '';

            // Monta o XML de dados
            $make = new Make();
            $totaisImposto = self::buildInfNFe($make, $pedido, $tipoNota, $codigoCRT);
            $valorTotal     = (float) ($pedido['total_pedido'] ?? 0);
            $valorImpostos  = array_sum($totaisImposto);

            // Assina (e, se for NFC-e, adiciona QR Code automaticamente)
            $tools = new Tools(
                $stringJson,
                Certificate::readPfx(
                    Config::getCertificado($cnpjEmitente),
                    $password
                )
            );
            if ($tipoNota === 65) {
                $tools->model(65);
            }

            $xmlAssinado = $tools->signNFe($make->montaNFe());

          
            $loteId  = str_pad(
                XMLHelper::getRegistroNota($pedido['idempresa'], $pedido['idpedidovenda']),
                15,
                '0',
                STR_PAD_LEFT
            );
            $xmlResp = $tools->sefazEnviaLote([$xmlAssinado], $loteId, 1);
            $stdResp = (new Standardize())->toStd($xmlResp);
            return self::tratarResposta(
                $stdResp,
                $make,
                $pedido,
                $xmlAssinado,
                $valorTotal,
                $valorImpostos,
                $xmlResp
            );
        } catch (Throwable $e) {
            self::handleError($pedido, $make ?? null, $e);
            throw $e;
        }
    }

    /** Monta todas as tags infNFe, ide, emit, itens, totais etc. */
    private static function buildInfNFe(Make $make, array $pedido, int $tipoNota, int $codigoCRT): array
    {
        // --- Cabeçalho sempre ---
        $make->taginfNFe(XMLtags::informacaoNFE());
        $make->tagide(XMLtags::ide($pedido,$pedido['idpedidovenda'],$pedido['idempresa'],$tipoNota));
        $make->tagemit(XMLtags::emit($pedido, $codigoCRT));
        $make->tagenderEmit(XMLtags::enderEmit($pedido));

        // --- Destinatário só para modelo 55 (ou operações > R$2k) ---
        if (
            $tipoNota === 55 ||
            ($tipoNota === 65 && mb_strtoupper(trim($pedido['destinatario']['nome'] ?? '')) !== 'CONSUMIDOR NÃO IDENTIFICADO')
        ) {
            $make->tagdest(XMLtags::dest($pedido));

            // Só adiciona endereço se realmente tiver dados
            $temEndereco = !empty($pedido['destinatario']['endereco']) || !empty($pedido['destinatario']['bairro']) || !empty($pedido['destinatario']['cidade']);
            if ($temEndereco) {
                $make->tagenderDest(XMLtags::enderDest($pedido));
            }
        }


        // Itens e impostos
        $totais = ['ICMS' => 0.0, 'PIS' => 0.0, 'COFINS' => 0.0];
        $nItem  = 1;
        foreach ($pedido['itens'] as $item) {
            $vProd = floatval($item['preco']) * floatval($item['quantidade']);
            $make->tagprod(XMLtags::prod($item, $nItem));
            $make->tagimposto(XMLtags::imposto($nItem));

            foreach ($item['imposto'] ?? [] as $imp) {
                $tipo = strtoupper($imp['tipo_imposto'] ?? '');
                switch ($tipo) {
                    case 'ICMS':
                        $icms = XMLtags::buildICMS($imp, $vProd, $nItem, $codigoCRT);
                        $make->{$icms['method']}($icms['data']);
                        $totais['ICMS'] += $icms['data']->vICMS ?? 0.0;
                        break;
                    case 'PIS':
                        $pis = XMLtags::buildPIS($imp, $vProd, $codigoCRT, $nItem);
                        $make->{$pis['method']}($pis['data']);
                        $totais['PIS'] += $pis['data']->vPIS ?? 0.0;
                        break;
                    case 'COFINS':
                        $cof = XMLtags::buildCOFINS($imp, $vProd, $codigoCRT, $nItem);
                        $make->{$cof['method']}($cof['data']);
                        $totais['COFINS'] += $cof['data']->vCOFINS ?? 0.0;
                        break;
                    case 'IPI':
                        $ipi = XMLtags::buildIPI($imp, $vProd, $nItem);
                        $make->{$ipi['method']}($ipi['data']);
                        break;
                    case 'ISSQN':
                        $iss = XMLtags::buildISSQN($imp, $vProd, $nItem);
                        $make->{$iss['method']}($iss['data']);
                        break;
                    default:
                        // Ignora impostos não mapeados
                }
            }
            $nItem++;
        }

        // Totais, transporte, pagamento e complementares
        $make->tagICMSTot(
            XMLtags::ICMSTot($pedido, $totais['ICMS'], $totais['PIS'], $totais['COFINS'])
        );

        $make->tagtransp(XMLtags::transp($pedido));
        $make->tagpag(XMLtags::pag());
        $make->tagdetPag(XMLtags::detPag($pedido));

        // --- Informações adicionais (infAdic só 55) ---
        if ($tipoNota === 55) {
            $make->taginfAdic(XMLtags::infAdic($pedido));
        }
        $make->taginfRespTec(XMLtags::infRespTec());

        return $totais;
    }

    /** Garante persistência e mensagem adequada para qualquer cStat */
    private static function tratarResposta(
        stdClass $resp,
        Make    $make,
        array   $pedido,
        string  $xmlAssinado,
        float   $valorTotal,
        float   $valorImpostos,
        ?string $xmlResp = null
    ): string {
        $cStat = (int)$resp->cStat;
        // Processando...
        if (in_array($cStat, [103, 105], true)) {
            return self::salvarNota([
                'idsituacaonotasefaz'     => 1,
                'status_processamento'    => 2,
                'xml'                     => $xmlAssinado,
                'chavesefaz'              => $make->getChave($xmlAssinado),
                'recibo_sefaz'            => $resp->infRec->nRec ?? null,
                'cstat_sefaz'             => $cStat,
                'msgsefaz'                => $resp->xMotivo,
                'data_hora_processamento' => date('Y-m-d H:i:s'),
                'dataemissao'             => date('Y-m-d'),
                'modelo'                  => $pedido['tipo_nota'] ?? 65,
            ], $pedido, $valorTotal, $valorImpostos) ?: 'Pedido aguardando processamento. Aguarde retorno da SEFAZ.';
        }

        // Autorizada/Rejeitada
        if ($cStat === 104) {
            $prot       = $resp->protNFe->infProt;
            $autorizada = ((int)$prot->cStat === 100);
            $xmlSalvo   = $xmlAssinado;
            if ($autorizada && $xmlResp) {
                $xmlSalvo = Complements::toAuthorize($xmlAssinado, $xmlResp);
            }
            return self::salvarNota([
                'idsituacaonotasefaz'   => $autorizada ? 2 : 3,
                'status_processamento'  => 4,
                'xml'                   => $xmlSalvo,
                'chavesefaz'            => $make->getChave($xmlAssinado),
                'numeronota'            => XMLtags::ide($pedido, $pedido['idempresa'], $pedido['idpedidovenda'], $pedido['tipo_nota'] ?? 65)->nNF,
                'serie'                 => XMLtags::ide($pedido, $pedido['idempresa'], $pedido['idpedidovenda'], $pedido['tipo_nota'] ?? 65)->serie,
                'modelo'                => XMLtags::ide($pedido, $pedido['idempresa'], $pedido['idpedidovenda'], $pedido['tipo_nota'] ?? 65)->mod,
                'protocolo_autorizacao' => $prot->nProt ?? 'SEM PROTOCOLO',
                'data_hora_autorizacao' => date('Y-m-d H:i:s', strtotime($prot->dhRecbto)),
                'cstat_sefaz'           => (int)$prot->cStat,
                'msgsefaz'              => $prot->xMotivo,
                'dataemissao'           => date('Y-m-d')
            ], $pedido, $valorTotal, $valorImpostos) ?: (
                $autorizada ? 'Autorizada' : 'Rejeitada pela SEFAZ: ' . $prot->xMotivo
            );
        }

        // Qualquer outro status (ex: 464, 539, etc.)
        return self::salvarNota([
            'idsituacaonotasefaz'     => 3,
            'status_processamento'    => 4,
            'xml'                     => $xmlAssinado,
            'chavesefaz'              => $make->getChave($xmlAssinado),
            'protocolo_autorizacao'   => null,
            'cstat_sefaz'             => $cStat,
            'msgsefaz'                => $resp->xMotivo,
            'data_hora_processamento' => date('Y-m-d H:i:s'),
            'dataemissao'             => date('Y-m-d'),
            'modelo'                  => $pedido['tipo_nota'] ?? 65,
        ], $pedido, $valorTotal, $valorImpostos) ?: sprintf('Erro SEFAZ [%d]: %s', $cStat, $resp->xMotivo);
    }

    /** Atualiza no banco e retorna mensagem customizada (ou null) */
    private static function salvarNota(array $data, array $pedido, float $valorTotal, float $valorImpostos): ?string
    {
        $data['valor_total']    = $valorTotal;
        $data['valor_impostos'] = $valorImpostos;
        XMLHelper::atualizarNota(
            $data,
            $pedido['idempresa'],
            $pedido['idpedidovenda'],
            true
        );
        return null;
    }

    /** Registra exceção no log de erros e mantém trace */
    private static function handleError(array $pedido, ?Make $make, Throwable $e): void
    {
        $msg   = $e->getMessage();
        $extra = '';
        if ($make) {
            $errs  = $make->getErrors();
            $extra = "\nErros internos: " . implode('; ', $errs);
        }
        XMLHelper::setErroNota(
            $pedido['idempresa'],
            $pedido['idpedidovenda'],
            $msg . $extra,
            true
        );
    }
}
