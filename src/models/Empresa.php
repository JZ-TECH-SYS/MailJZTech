<?php

namespace src\models;

use core\Database;
use \core\Model;
use  \src\handlers\Produtos as ProdHelp;
use PDO;


/**
 * Classe modelo para a tabela 'Empresa' do banco de dados.
 */
class Empresa extends Model
{
    public static function getInfosPedidoOn($nome_empresa){
        $sql = "
            SELECT 
                e.idempresa,
                e.nome,
                e.nomefantasia,
                e.cnpj,
                e.endereco,
                e.numero,
                e.dilema,
                e.chavepix,
                e.cell,
                e.tipo_estabelecimento,
                (
                    SELECT 
                        CONCAT('[', GROUP_CONCAT(json_object('idcidade', c.id, 'nome', c.nome)), ']') AS cidades
                    FROM cidade c 
                    WHERE FIND_IN_SET(c.id, e.idcidade) > 0
                ) as cidades,
                (
                    SELECT 
                        CONCAT('[', GROUP_CONCAT(json_object('idtipopagamento', t.idtipopagamento, 'descricao', t.descricao)), ']') AS meios_pagamentos
                    FROM tipo_pagamento t
                    WHERE t.status = 1
                      and t.web = 1
                      and t.idempresa = e.idempresa
                ) as meios_pagamentos,
                ep3.valor as horario_atendimento,
                ep4.valor as esta_aberta,
                ep5.valor as tempo_espera,
                ep6.valor as logo,
                ep7.valor as banner_fundo,
                ep11.valor as ativar_cupon,
                ep14.valor as campos_adicionais_checkout,
                ep15.valor as usar_maps_auto_cep,
                ep17.valor as usar_geolocalizacao
            FROM
                empresa e
            LEFT JOIN
                empresa_parametro as ep3 ON ep3.idempresa = e.idempresa AND ep3.idparametro = 3
            LEFT JOIN
                empresa_parametro as ep4 ON ep4.idempresa = e.idempresa AND ep4.idparametro = 4
            LEFT JOIN
                empresa_parametro as ep5 ON ep5.idempresa = e.idempresa AND ep5.idparametro = 5
            LEFT JOIN
                empresa_parametro as ep6 ON ep6.idempresa = e.idempresa AND ep6.idparametro = 6
            LEFT JOIN
                empresa_parametro as ep7 ON ep7.idempresa = e.idempresa AND ep7.idparametro = 7
            LEFT JOIN
                empresa_parametro as ep11 ON ep11.idempresa = e.idempresa AND ep11.idparametro = 11
            LEFT JOIN
                empresa_parametro as ep14 ON ep14.idempresa = e.idempresa AND ep14.idparametro = 14
            LEFT JOIN
                empresa_parametro as ep15 ON ep15.idempresa = e.idempresa AND ep15.idparametro = 15
            LEFT JOIN
                empresa_parametro as ep17 ON ep17.idempresa = e.idempresa AND ep17.idparametro = 17

            WHERE
                e.nome = :nome_empresa or e.idempresa = :nome_empresa;
        ";
        $sql = Database::getInstance()->prepare($sql);
        $sql->bindValue(':nome_empresa', $nome_empresa);
        $sql->execute();
        $result = $sql->fetch(PDO::FETCH_ASSOC);
        $result['cidades'] = (isset($result['cidades'])) ? json_decode($result['cidades']) : [];
        $result['meios_pagamentos'] = (isset($result['meios_pagamentos'])) ? json_decode($result['meios_pagamentos']) : [];
        $result['campos_adicionais_checkout'] = isset($result['campos_adicionais_checkout']) && $result['campos_adicionais_checkout'] !== ''
            ? json_decode($result['campos_adicionais_checkout'], true)
            : [];
        if($result['ativar_cupon'] == 'true'){
            $cupons = self::regraCuponDesconto($result['idempresa']);
            $result['regracupons'] = $cupons ? $cupons : [];
        } else {
            $result['regracupons'] = [];
        }

        $result['usar_maps_auto_cep'] = isset($result['usar_maps_auto_cep']) && !empty($result['usar_maps_auto_cep']) ? 1 : 0;
    $result['usar_geolocalizacao'] = isset($result['usar_geolocalizacao']) && !empty($result['usar_geolocalizacao']) ? 1 : 0;
        return $result;
    }

    public static function regraCuponDesconto($idempresa){
        $sql = "
            select 
                 idcuponregra
                ,idempresa
                ,valor as valor_cupon
                ,descricao as descricao_cupon
                ,data_criacao 
                ,status
                ,quantidade_pedidos as quantidade_cupon
                ,valor_minimo as valor_minimo_cupon
            from cupon_regra r
            where r.idempresa = :idempresa
        ";
        $sql = Database::getInstance()->prepare($sql);
        $sql->bindValue(':idempresa', $idempresa);
        $sql->execute();
        $result = $sql->fetch(PDO::FETCH_ASSOC);
        return $result ? $result : [];
    }

    public static function getEMP($idempresa){
      return self::select()->where('idempresa', $idempresa)->one();
    }

    public static function getInfosNFE($idempresa){
        $data = Database::switchParams(['idempresa' => $idempresa],'NFE/getInfosNFEempresa', true, true);
        return $data['retorno'][0] ?? [];
    }
}
