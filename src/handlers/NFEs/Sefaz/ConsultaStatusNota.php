<?php

namespace src\handlers\NFEs;

use NFePHP\Common\Certificate;
use NFePHP\NFe\Tools;
use NFePHP\NFe\Common\Standardize;
use Exception;
use NFePHP\NFe\Complements;
use src\handlers\NFEs\UtilsNF\Config;
use src\handlers\NFEs\UtilsNF\XMLHelper;
use Throwable;

class ConsultaStatusNota
{
    public static function consultar($nota, $idempresa)
    {
        try {
            $config = Config::getConfigJson($idempresa);
            $tools = new Tools($config['stringJson'], Certificate::readPfx($config['certificado'], $config['password']));
            
            // Consulta o status do recibo na Sefaz
            $respostaConsulta = $tools->sefazConsultaRecibo($nota['recibo_sefaz']);
            $stdConsulta = (new Standardize())->toStd($respostaConsulta);
            
            self::processarRespostaLote($stdConsulta, $nota,$respostaConsulta);
            
            return [
                'success' => true,
                'message' => 'Nota fiscal consultada com sucesso.',
            ];
        } catch (Throwable $e) {
            XMLHelper::setErroNota($nota['idempresa'], $nota['idregistronota'], $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    private static function processarRespostaLote($stdConsulta,$nota,$respostaConsulta)
    {
        $statusLote = $stdConsulta->cStat;

        // Trata o status do lote
        if ($statusLote == '104') { // Lote processado
            self::processarStatusNota($nota,$stdConsulta,$respostaConsulta);
        } elseif ($statusLote == '105') { // Lote em processamento
            self::atualizarLoteEmProcessamento($nota);
        } else {
            // Lote com erro - registrar a mensagem de erro
            self::atualizarErroLote($nota, $stdConsulta->xMotivo);
        }
    }

    private static function processarStatusNota($nota, $stdConsulta,$respostaConsulta)
    {
        $statusNota = $stdConsulta->protNFe->infProt->cStat;
        $xMotivo = $stdConsulta->protNFe->infProt->xMotivo;
        $protocolo = $stdConsulta->protNFe->infProt->nProt ?? null;
        $xmlProtocolo = $stdConsulta->protNFe->infProt->any ?? null;
        $dataHora = $stdConsulta->protNFe->infProt->dhRecbto ?? null;

        $atualizacoes = [
            'msgsefaz' => $xMotivo,
            'protocolo_autorizacao' => $protocolo,
            'status_processamento' => 4, // Concluído
        ];

        if ($statusNota == '100') { // Nota autorizada
            $atualizacoes['idsituacaonotasefaz'] = 2; // Autorizada
            $atualizacoes['data_hora_autorizacao'] = $dataHora;
            $xmlAutorizado = Complements::toAuthorize($nota['xml'], $respostaConsulta);
            $atualizacoes['xml'] = $xmlAutorizado;
            
        } else { // Nota rejeitada ou outro status de erro
            $atualizacoes['idsituacaonotasefaz'] = 3; // Rejeitada
            $atualizacoes['msgsefaz'] = $xMotivo;
            $atualizacoes['status_processamento'] = 3; // Erro
        }

        XMLHelper::atualizarNota($atualizacoes, $nota['idempresa'], $nota['idregistronota']);
    }

    private static function atualizarLoteEmProcessamento($nota)
    {
        XMLHelper::atualizarNota([
            'status_processamento' => 2, // Em processamento
            'msgsefaz' => 'Lote em processamento pela Sefaz'
        ], $nota['idempresa'], $nota['idregistronota']);
    }

    private static function atualizarErroLote($nota, $mensagemErro)
    {
        XMLHelper::atualizarNota([
            'status_processamento' => 3, // Erro
            'msgsefaz' => $mensagemErro,
            'idsituacaonotasefaz' => 9 // Falha no Envio
        ], $nota['idempresa'], $nota['idregistronota']);
    }
}
