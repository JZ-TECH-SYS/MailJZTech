<?php

namespace src\handlers\NFEs;

use DateInterval;
use Throwable;
use Exception;
use DateTime;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Tools;
use NFePHP\NFe\Common\Standardize;
use src\handlers\NFEs\UtilsNF\XMLHelper;
use src\handlers\NFEs\UtilsNF\Config;

class CancelarNota
{
    /**
     * Cancela uma NF-e/NFC-e usando diretamente o payload de $pedido,
     * que já contém todas as informações de NF em ['nf'].
     *
     * @param array $pedido Payload de venda, contendo:
     *                      - ['emitente']['cnpj']
     *                      - ['nf'] (array com dados da nota)
     *                      - opcional: ['motivo_cancelamento'], ['data_hora_cancelamento']
     * @return array|string Retorna array com dados do cancelamento ou string de erro.
     * @throws Exception
     */
    public static function gerar(array $pedido)
    {
        // 1) Extrai idempresa
        $idEmpresa = (int) ($pedido['idempresa'] ?? 0);
        if (!$idEmpresa) {
            throw new Exception('Campo idempresa ausente no payload.');
        }

        // 2) Verifica se ['nf'] existe e contém dados da nota
        if (empty($pedido['nf']) || !is_array($pedido['nf'])) {
            throw new Exception('Payload não contém seção [\'nf\'] com dados da nota.');
        }
        $nf = $pedido['nf'];

        // 3) Monta array $nota a partir de $pedido['nf']
        $nota = [
            'idregistronota'           => $nf['idregistronota'] ?? null,
            'data_hora_autorizacao'    => $nf['data_hora_autorizacao'] ?? null,
            'idsituacaonotasefaz'      => $nf['idsituacaonotasefaz'] ?? null,
            'modelo'                   => $nf['modelo'] ?? null,
            'chavesefaz'               => $nf['chavesefaz'] ?? '',
            'protocolo_autorizacao'    => $nf['protocolo_autorizacao'] ?? '',
            // CNPJ do emitente vem em $pedido['emitente']['cnpj']
            'cnpj_emitente'            => $pedido['emitente']['cnpj'] ?? '',
            'motivo_cancelamento'      => $pedido['motivo_cancelamento'] ?? 'Cancelamento via sistema',
        ];

        $dataAut = $nota['data_hora_autorizacao'];
        $dtAut = new DateTime($dataAut);
        $agora = new DateTime('now');

        // 4) Pré-condições de cancelamento
        // Se for NFC-e (modelo 65): prazo máximo 30 minutos
        if ((int)$nota['modelo'] === 65) {
            $limite = (clone $dtAut)->add(new DateInterval('PT30M'));
            if ($agora > $limite) {
                throw new Exception('Prazo de 30 minutos para cancelamento da NFC-e expirado.');
            }
        }
        // Se for NF-e (modelo 55): prazo máximo 24 horas
        elseif ((int)$nota['modelo'] === 55) {
            $limite = (clone $dtAut)->add(new DateInterval('P1D'));
            if ($agora > $limite) {
                throw new Exception('Prazo de 24 horas para cancelamento da NF-e expirado.');
            }
        } else {
            throw new Exception('Modelo de nota inválido para cancelamento.');
        }


        if ((int) $nota['idsituacaonotasefaz'] !== 2) {
            throw new Exception('Somente notas autorizadas podem ser canceladas (status SEFAZ).');
        }

        // 5) Carrega configuração e certificado usando CNPJ do emitente
        $cnpjEmitente = trim($nota['cnpj_emitente']);
        if (empty($cnpjEmitente)) {
            throw new Exception('CNPJ do emitente ausente no payload.');
        }
        $conf       = Config::getConfigJson($cnpjEmitente);
        $stringJson = $conf['stringJson'] ?? '';
        $password   = $conf['password']   ?? '';
        $certPath   = Config::getCertificado($cnpjEmitente);

        try {
            // 6) Instancia o Tools do NFePHP
            $tools = new Tools(
                $stringJson,
                Certificate::readPfx($certPath, $password)
            );
            $tools->model((int) $nota['modelo']); // 55 ou 65

            // 7) Prepara parâmetros para sefazCancela
            $chave = $nota['chavesefaz'];
            if (empty($chave) || strlen($chave) !== 44) {
                throw new Exception('Chave de acesso inválida para cancelamento.');
            }

            $just = $nota['motivo_cancelamento'] ?? 'Cancelamento via sistema, desistência do cliente';
            $nProt = $nota['protocolo_autorizacao'];
            if (empty($nProt)) {
                throw new Exception('Protocolo de autorização não encontrado para cancelamento.');
            }

            // $dhEvento: data/hora que será enviada; usa o que o usuário passar em $pedido['data_hora_cancelamento'], ou agora()
            $dhEvento = new DateTime('now');


            // 8) Gera um ID de lote único (até 15 dígitos):
            //    14 dígitos de date('YmdHis') + 1 dígito randômico = 15
            $idLote = date('YmdHis') . rand(0, 9);

            // 9) Chama sefazCancela (que internamente já dispara sefazEvento)
            //    assinatura do método, conforme doc oficial do PHPNFe v5.1:
            //    sefazCancela(string $chave, string $xJust, string $nProt,
            //                 ?\DateTimeInterface $dhEvento = null, ?string $lote = null): string
            $respXml = $tools->sefazCancela(
                $chave,
                $just,
                $nProt,
                $dhEvento,
                $idLote
            );

            // 10) Padroniza resposta para obter retEnvEvento
            $stdCanc = (new Standardize())->toStd($respXml);

            // 11) Trata resposta e atualiza banco
            return self::tratarResposta($stdCanc, $nota, $idEmpresa);
        } catch (Throwable $e) {
            XMLHelper::setErroNota(
                $idEmpresa,
                $nota['idregistronota'],
                'Erro Cancelamento: ' . $e->getMessage()
            );
            throw $e;
        }
    }

    /**
     * Trata a resposta de cancelamento e atualiza o banco (Nota_fiscal)
     *
     * @param object $stdCanc Objeto retornado por Standardize()
     * @param array  $nota    Dados internos de nota
     * @param int    $idempresa
     * @return array|string
     * @throws Exception
     */
    private static function tratarResposta($stdCanc, array $nota, int $idempresa)
    {
        $retEnv = $stdCanc->retEvento ?? null;
        if (!$retEnv) {
            throw new Exception('Resposta de cancelamento inválida da SEFAZ.');
        }

        // Pega infEvento mesmo que seja array ou objeto
        $objEvento = is_array($retEnv)
            ? ($retEnv[0]->infEvento ?? null)
            : ($retEnv->infEvento ?? null);

        if (!$objEvento) {
            throw new Exception('Não foi possível encontrar infEvento na resposta.');
        }

        $cStat = intval($objEvento->cStat ?? 0);
        $xMot  = (string) ($objEvento->xMotivo ?? '');
        $nProt = (string) ($objEvento->nProt ?? '');
        $dhEvt = (string) ($objEvento->dhRegEvento ?? $objEvento->dhEvento ?? '');

        // Se cancelamento homologado ou já registrado → trata como sucesso
        if (in_array($cStat, [101, 135, 151], true)) {
            // Atualiza nota como cancelada
            XMLHelper::atualizarNota(
                [
                    'idsituacaonotasefaz'     => 5, // cancelada
                    'protocolo_cancelamento'  => $nProt,
                    'msgsefaz'                => $xMot,
                    'data_cancelamento' => date('Y-m-d H:i:s'),
                ],
                $idempresa,
                $nota['idregistronota'],
                false
            );

            return [
                'sucesso'                 => true,
                'cStat'                   => $cStat,
                'xMotivo'                 => $xMot,
                'nProt'                   => $nProt,
                'data_hora_cancelamento' => $dhEvt,
            ];
        }

        // Qualquer outro status → erro
        XMLHelper::setErroNota(
            $idempresa,
            $nota['idregistronota'],
            "Rejeitado pela SEFAZ: ({$cStat}) {$xMot}"
        );
        return sprintf('Falha no cancelamento (%d): %s', $cStat, $xMot);
    }
}
