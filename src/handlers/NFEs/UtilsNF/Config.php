<?php

namespace src\handlers\NFEs\UtilsNF;
use Exception;
class Config
{
    const CSTAT_REENVIAVEL = [
        /* Falha de comunicação – SEFAZ não recebeu */
          0,        // sem retorno / timeout interno
          108,      // Serviço paralisado momentaneamente
          109,      // Serviço paralisado sem previsão
          111,      // Consulta cadastro com uma ocorrência (⚠ depende do caso)
    
        /* Erros de formação/validação do XML */
          215, 216, 217, 218, 219,           // Conteúdo ou falta de tag obrigatória
          220, 221, 222, 223, 224, 225,      // Erro no schema XML
          226,                               // Código de segurança CSRT inválido
          227,                               // Divergência entre CFOP e natureza da operação
          228, 229,                          // CNPJ emitente ou destinatário inválido
          231, 232, 233,                     // IE destinatário inválida/irregular
          234, 235,                          // IE substituta inexistente
          236, 237,                          // DataEmissão fora do prazo ou futura
          238,                               // Prazo de cancelamento ultrapassado (reenvio de cancelamento)
          239, 240,                          // CNPJ emitente irregular
          242, 243,                          // XML malformado ou versão inexistente
          245, 246,                          // Erro na assinatura ou digest value
          252,                               // Certificado expirado / revogado
    
        /* Duplicidade que ainda pode ser contornada */
          204,                               // Duplicidade de NF-e (corrigir chave ou aguardar recibo)
          539,                               // Duplicidade com diferença de chave (aguardar ou gerar nova chave)
    
        /* Uso em contingência que falhou */
          580, 587, 590,                      // CSC / QR-Code inválido ou em branco (NFC-e)

        /** sem grupo de pis */
        /* Outros erros que podem ser corrigidos */
          301, 302, 303, 304, 305, 306,      // Erros de validação de campos específicos
          307, 308, 309, 310,                // Erros de validação de campos específicos
          311, 312,                           // Erros de validação de campos específicos
          313,                                // Erro de validação de campos específicos
          314,                                 // Erro de validação de campos específicos
          745,                                   // grupo de PIS inválido
          464,
          564
    ];
    

    private static function base()
    {
        return dirname(__DIR__);
    }

    public static function getConfigJson($cnpjconfig)
    {
        // Carregar o arquivo de configuração JSON
        $configJson = file_get_contents(self::base().'/Config/' . $cnpjconfig . '/config.json');
        if (empty($configJson)) {
            throw new Exception("Arquivo de configuração não encontrado.");
        }
        $decoded = json_decode($configJson, true);
        if (empty($decoded)) {
            throw new Exception("Erro ao decodificar o arquivo de configuração.");
        }

        return [
            'stringJson' => $configJson,
            'password' => $decoded['senha']
        ];
    }

    public static function getCertificado($cnpjconfig)
    {
        // Carregar o arquivo de certificado
        $certificado = file_get_contents(self::base() . '/Config/' . $cnpjconfig . '/certificado.pfx');
        if (empty($certificado)) {
            throw new Exception("Certificado não encontrado.");
        }
        return $certificado;
    }
}
