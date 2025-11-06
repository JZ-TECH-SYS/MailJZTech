<?php

namespace src\handlers\NFEs\UtilsNF;

use core\Database;
use DateTime;
use Exception;
use NFePHP\NFe\Make;
use src\models\Nota_fiscal;

class XMLHelper
{
    public static function getRegistroNota($idempresa, $idpedidovenda)
    {
        $nota = Nota_fiscal::select()->where('idempresa', $idempresa)->where('idpedidovenda', $idpedidovenda)->one();
        return $nota['idregistronota'];
    }

    public static function setErroNota($idempresa, $idregistronota, $mensagemErro, $pv = false)
    {
        $up = Nota_fiscal::update([
            'data_hora_processamento' => date('Y-m-d H:i:s'),
            'status_processamento' => 3,
            'msg_erro' => $mensagemErro,
            'msgsefaz' => $mensagemErro
        ])
            ->where('idempresa', $idempresa);
        if ($pv) {
            $up->where('idpedidovenda', $idregistronota);
        } else {
            $up->where('idregistronota', $idregistronota);
        }
        $up->execute();
    }

    public static function atualizarNota($dados, $idempresa, $idregistronota, $pv = false)
    {
        $up = Nota_fiscal::update($dados)->where('idempresa', $idempresa);
        if ($pv) {
            $up->where('idpedidovenda', $idregistronota);
        } else {
            $up->where('idregistronota', $idregistronota);
        }
        $up->execute();
    }


    public static function getNumeroNota($idempresa,$modelo)
    {
        $res  = Database::switchParams(['idempresa' => $idempresa,'modelo'=>$modelo], 'NFE/getNumeroNota', true, true);
        $res = $res['retorno'][0]['numero_sugerido'] ?? null;
        if (!$res) {
            throw new \Exception("Erro ao gerar o número da nota fiscal, entre em contato com o suporte.");
        }
        return $res;
    }
}
