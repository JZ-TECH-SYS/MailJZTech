<?php

namespace src\handlers\NFEs;

use NFePHP\NFe\Tools;
use Exception;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Common\Standardize;
use NFePHP\NFe\Complements;
use src\handlers\NFEs\UtilsNF\Config;
use src\handlers\NFEs\UtilsNF\XMLHelper;

class ProcessarReciboNota
{
    public static function processarRecibo(array $nf): void
    {
        try {
            $configJson = Config::getConfigJson($nf['idempresa']);
            $certificado = Config::getCertificado($nf['idempresa']);

            $tools = new Tools($configJson['stringJson'], Certificate::readPfx($certificado, $configJson['password']));
            $resp = $tools->sefazConsultaRecibo($nf['recibo_sefaz']);

            $std = (new Standardize())->toStd($resp);

            // Se ainda está em processamento (105), não faz nada
            if ((int)$std->cStat === 105) {
                return;
            }

            // Se retorno for inválido ou sem protNFe, grava como erro
            if ((int)$std->cStat !== 104 || !isset($std->protNFe[0]->infProt)) {
                $mensagemErro = "[{$std->cStat}] {$std->xMotivo}";
                XMLHelper::setErroNota($nf['idempresa'], $nf['idpedidovenda'], $mensagemErro, true);
                return;
            }

            // Pega o protocolo de autorização
            $prot       = $std->protNFe[0]->infProt;
            $autorizada = (int)$prot->cStat === 100;

            $xmlAutorizado = null;
            if ($autorizada) {
                $xmlAutorizado = Complements::toAuthorize($nf['xml'], $resp);
            }

            XMLHelper::atualizarNota(array_filter([
                'idsituacaonotasefaz'   => $autorizada ? 2 : 3,
                'status_processamento'  => 4,
                'protocolo_autorizacao' => $prot->nProt,
                'data_hora_autorizacao' => date('Y-m-d H:i:s', strtotime($prot->dhRecbto)),
                'cstat_sefaz'           => $prot->cStat,
                'msgsefaz'              => $prot->xMotivo,
                'xml'                   => $xmlAutorizado
            ]), $nf['idempresa'], $nf['idpedidovenda'], true);

        } catch (Exception $e) {
            XMLHelper::setErroNota($nf['idempresa'], $nf['idpedidovenda'], $e->getMessage(), true);
            throw $e;
        }
    }
}
