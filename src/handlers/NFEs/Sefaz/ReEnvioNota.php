<?php

namespace src\handlers\NFEs;

use NFePHP\Common\Certificate;
use NFePHP\NFe\Tools;
use Exception;
use Throwable;
use src\handlers\NFEs\UtilsNF\Config;
use src\handlers\NFEs\UtilsNF\XMLHelper;

class ReEnviarNota
{
    public static function reEnvio($nota, $idempresa)
    {
        try {
            $config = Config::getConfigJson($idempresa);
            $tools = new Tools($config['stringJson'], Certificate::readPfx($config['certificado'], $config['password']));

            // Enviar o XML para a Sefaz
            $response = $tools->sefazEnviaLote($nota['xml']);
            $stdResponse = json_decode($response);

            if ($stdResponse->cStat != '103') {
                throw new Exception("Erro no reenvio da nota: {$stdResponse->xMotivo}");
            }

            // Atualizar o status da nota para "Enviada" e armazenar o número do recibo
            $recibo = $stdResponse->infRec->nRec;
            XMLHelper::atualizarNota([
                'idsituacaonotasefaz' => 1, 
                'status_processamento' => 2,
                'recibo_sefaz' => $recibo
            ], $nota['idempresa'], $nota['idregistronota']);
           
            return [
                'success' => true,
                'message' => 'Nota fiscal reenviada com sucesso.',
                'recibo' => $recibo,
            ];

        } catch (Throwable $e) {
            // Retornar o erro detalhado
            XMLHelper::setErroNota($nota['idempresa'], $nota['idregistronota'], $e->getMessage());
            throw new Exception("Erro no reenvio da nota: {$e->getMessage()}");
        }
    }
}
