<?php

namespace src\handlers\NFEs;

use NFePHP\Common\Certificate;
use NFePHP\NFe\Tools;
use NFePHP\NFe\Common\Standardize;
use src\handlers\NFEs\UtilsNF\Config;
use Exception;
use src\handlers\NFEs\UtilsNF\XMLHelper;
use Throwable;

class CancelarNota
{
    public static function cancelar($nota,$idempresa)
    {
        try {
            // Obter a configuração da empresa
            $config = Config::getConfigJson($idempresa);
            $tools = new Tools($config['stringJson'], Certificate::readPfx($config['certificado'], $config['password']));

            $chave = $nota['chavesefaz']; // Chave da NFe
            $nProt = $nota['protocolo_autorizacao']; // Número do protocolo
            $justificativa = $nota['motivo_cancelamento'] ?? 'Cancelamento solicitado pelo emitente';

            // Enviar o evento de cancelamento para a Sefaz
            $response = $tools->sefazCancela($chave, $justificativa, $nProt);
            
            // Processar a resposta da Sefaz
            $standardize = new Standardize();
            $stdResponse = $standardize->toStd($response);

            if ($stdResponse->cStat != '135') {
                throw new Exception("Erro ao cancelar a nota: $stdResponse->xMotivo");
            }
          
            $protocoloCancelamento = $stdResponse->retEvento->infEvento->nProt;
            $xmlCancelamento = $tools->lastResponse;
            // Atualizar o status da nota no banco de dados (opcional)
            XMLHelper::atualizarNota([
                'idsituacaonotasefaz'  => 5,
                'data_cancelamento' => date('Y-m-d'),
                'protocolo_cancelamento' => $protocoloCancelamento,
                'xml_cancelamento' => $xmlCancelamento
            ], $nota['idempresa'], $nota['idregistronota']);
            return [
                'success' => true,
                'message' => 'Nota fiscal cancelada com sucesso.',
            ];
        } catch (Throwable $e) {
            // Retornar o erro detalhado
            XMLHelper::setErroNota($nota['idempresa'], $nota['idregistronota'], $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }
}
