<?php

namespace src\handlers\NFEs;

use NFePHP\NFe\Tools;
use Exception;
use NFePHP\Common\Certificate;
use src\handlers\NFEs\UtilsNF\Config;

class InutilizarNota
{
    /**
     * Inutilizar uma numeração de NF-e.
     * 
     * @param string $cnpjempresa CNPJ da empresa.
     * @param int $nSerie Série da numeração a ser inutilizada.
     * @param int $nIni Número inicial da numeração.
     * @param int $nFin Número final da numeração.
     * @param string $xJust Justificativa para a inutilização.
     * @param int|null $tpAmb Tipo de ambiente: 1 = Produção, 2 = Homologação.
     * @param int|null $ano Ano da numeração a ser inutilizada.
     * 
     * @return array Resposta da SEFAZ ou erro.
     */
    public static function inutilizar($cnpjempresa, $nSerie, $nIni, $nFin, $xJust, $tpAmb = 1, $ano = null)
    {
        try {
            // Configurações do certificado digital
            $configJson = Config::getConfigJson($cnpjempresa);
            $content = Config::getCertificado($cnpjempresa);
            $tools = new Tools($configJson['stringJson'], Certificate::readPfx($content, $configJson['password']));

            // Checa se o ano foi fornecido; caso contrário, define o ano atual
            $ano = $ano ?? date('y');

            // Executa a inutilização
            $response = $tools->sefazInutiliza($nSerie, $nIni, $nFin, $xJust, $tpAmb, $ano);

            // Verifica se a resposta é um XML válido antes de processá-la
            $xml = @simplexml_load_string($response);
            if (!$xml) {
                throw new Exception("Resposta da SEFAZ não é um XML válido: " . $response);
            }
            
            $responseArray = json_decode(json_encode($xml), true);

            // Retorna a resposta com status de sucesso
            return [
                'status' => 'success',
                'data' => $responseArray
            ];
        } catch (Exception $e) {
            // Log de erro detalhado
            return [
                'status' => 'error',
                'message' => 'Erro ao inutilizar a numeração: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ];
        }
    }
}
