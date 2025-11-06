<?php

/**
 * Classe helper para gerenciar NotaFiscal no sistema
 * 
 * Esta classe fornece métodos para gerenciar NotaFiscal de um sistema.
 * 
 * Autor: Joaosn
 * Data de Início: 20/10/2024
 */

namespace src\handlers;

use core\Database;
use src\models\Nota_fiscal;
use src\handlers\PedidoVenda;
use src\handlers\NFEs\EmitirNota;
use src\handlers\NFEs\CancelarNota;
use src\handlers\NFEs\ReEnviarNota;
use Exception;
use src\handlers\NFEs\ConsultaStatusNota;
use src\handlers\NFEs\InutilizarNota;
use src\handlers\NFEs\Gerenciador;
use src\handlers\NFEs\ProcessarReciboNota;
use src\handlers\NFEs\UtilsNF\Config;
use src\handlers\NFEs\UtilsNF\OperacaoNFe;

class NotaFiscal
{
    public static function listaNotas($idempresa, $datainicio, $datafim, $meios = [])
    {
        $params = [
            'idempresa'  => $idempresa,
            'datainicio' => $datainicio,
            'datafim'    => $datafim,
            'meios'      => implode(',', $meios)
        ];

        $getNnotas = Database::switchParams($params, 'NFE/getNotas', true);
        $valores   = Database::switchParams($params, 'NFE/getvaloresNFE', true);
        $valores = $valores['retorno'] ?? [];
        $notas   = $getNnotas['retorno'] ?? [];

        return [
            'notas' => $notas,
            'valores' => $valores
        ];
    }

    public static function gerarNotaVenda($idempresa, $idpedido,$tipoNota)
    {
        self::validarNotaInsereRegistroNota($idempresa, $idpedido);
        $pedido = PedidoVenda::getPedidoVendaNFE($idempresa, $idpedido);

        $doc = preg_replace('/\D/', '', $pedido['destinatario']['cpf'] ?? $pedido['destinatario']['cnpj'] ?? '');
        if ($tipoNota == 55 && strlen($doc) <= 11) {
            throw new Exception('Notas modelo 55 não podem ser emitidas para pessoa física');
        }


        $pedido['tipo_nota'] = $tipoNota; // Define o tipo de nota (NFE ou NFCe)

        //print_r($pedido);die;
        $res = Gerenciador::operacao($pedido);
        return $res;
    }
    

    public static function processarNotas()
    {
        $notasProcessadas = [];
        $notasConsultadas = [];
        $pendentes = Nota_fiscal::select()
            ->where('status_processamento', 1)
            ->whereNull('chavesefaz')
            ->whereNull('recibo_sefaz')
            ->get();


        foreach ($pendentes as $nf) {
            $notasProcessadas[] = EmitirNota::gerarNota($nf['idempresa'], $nf['idpedidovenda']);
        }

        // 2) notas aguardando processamento SEFAZ
        $aguardando = Nota_fiscal::select()
            ->where('status_processamento', 2)
            ->whereNotNull('recibo_sefaz')
            ->get();

        foreach ($aguardando as $nf) {
            $notasConsultadas[] = ProcessarReciboNota::processarRecibo($nf);
        }

        return [
            'notasProcessadas' => $notasProcessadas,
            'notasConsultadas' => $notasConsultadas
        ];
    }

    private static function validarNotaInsereRegistroNota($idempresa, $idpedido)
    {
        $existenota = Nota_fiscal::select()
            ->where('idpedidovenda', $idpedido)
            ->where('idempresa', $idempresa)
            ->one();

        if ($existenota) {
            return $existenota;
        }
        
        $getPedidoNFE = PedidoVenda::getPedidoVendaNFE($idempresa, $idpedido);
        if (!$getPedidoNFE) {
            throw new Exception('Pedido não encontrado');
        }
        
        try {
            Nota_fiscal::insert([
                'idpedidovenda' => $idpedido,
                'idempresa' => $idempresa,
                'status_processamento' => 1,
                'idusuario' => $_SESSION['empresa']['idusuario'] ?? 0,
                'cpf_cnpj_destinatario' => $getPedidoNFE['destinatario']['cpf'] ?? '99999999999'
            ])->execute();
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }

    public static function cancelarNota($idempresa, $idregistronota)
    {
        $nota = Nota_fiscal::select()
            ->where('idempresa', $idempresa)
            ->where('idregistronota', $idregistronota)
            ->one();

        if (!$nota) {
            throw new Exception('Nota fiscal não encontrada!');
        }

       
        $dataAutorizacao = date('Y-m-d H:i:s', strtotime($nota['data_hora_autorizacao']));
        if ($dataAutorizacao < date('Y-m-d H:i:s', strtotime('-24 hours'))) {
            throw new Exception('Nota fiscal não pode ser cancelada, prazo de 24 horas expirado!');
        }


        if ($nota['idsituacaonotasefaz'] != 2) {
            throw new Exception('Olhando para o status da nota fiscal, não é possível cancelar a nota!');
        }

        $pedido = PedidoVenda::getPedidoVendaNFE($idempresa, $nota['idpedidovenda']);
        $pedido['tiponota'] = $nota['modelo']; // Define o tipo de nota (NFE ou NFCe)
        $pedido['operacao'] = OperacaoNFe::CANCELAMENTO;
        $pedido['nf'] = $nota;
        $res = Gerenciador::operacao($pedido);
        return $res;
    }

    public static function reEnviarNota($idempresa, $idregistronota)
    {

        $nota = Nota_fiscal::select()
            ->where('idempresa', $idempresa)
            ->where('idregistronota', $idregistronota)
            ->one();
        
        if (!in_array((int)$nota['cstat_sefaz'], Config::CSTAT_REENVIAVEL, true)) {
            throw new Exception('Nota fiscal não pode ser reenviada, status atual: ' . $nota['cstat_sefaz']);
        }

        if (!$nota) {
            throw new Exception('Nota fiscal não encontrada!');
        }

        // if (!in_array($nota['idsituacaonotasefaz'], [2, 4, 5, 6, 10])) {
        //     throw new Exception('Olhando para o status da nota fiscal, não é possível reenviar a nota! Status atual: ' . $nota['idsituacaonotasefaz']);
        // }

        $pedido = PedidoVenda::getPedidoVendaNFE($idempresa, $nota['idpedidovenda']);
        $pedido['tiponota'] = $nota['modelo']; // Define o tipo de nota (NFE ou NFCe)
        $pedido['operacao'] = OperacaoNFe::REENVIO;
        $pedido['nf'] = $nota;
        $res = Gerenciador::operacao($pedido);
        return $res;
    }

    public static function consultarNota($idempresa, $idregistronota)
    {
        $nota = Nota_fiscal::select()
            ->where('idempresa', $idempresa)
            ->where('idregistronota', $idregistronota)
            ->one();

        if (!$nota) {
            throw new Exception('Nota fiscal não encontrada!');
        }

        if ($nota['status_processamento'] == 4 || $nota['idsituacaonotasefaz'] == 2) {
            throw new Exception('Nota fiscal Ja foi Autorizada !');
        }

        return ConsultaStatusNota::consultar($nota, $idempresa);
    }

    public static function inutilizarNotas($idempresa)
    {
        $notas = Database::switchParams(['idempresa' => $idempresa], 'NFE/getNotasInutilizadas', true);
        $response = [];
        foreach ($notas as $nota) {
            $response[] = InutilizarNota::inutilizar($nota['cnpjempresa'], $nota['nSerie'], $nota['nIni'], $nota['nFin'], $nota['xJust'], $nota['tpAmb'], $nota['ano']);
        }

        return $response;
    }
}
