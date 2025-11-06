<?php

namespace src\models;

use core\Database;
use \core\Model;

/**
 * Classe modelo para a tabela 'Cupon' do banco de dados.
 */
class Cupon extends Model
{
    public static function getCuponsSumarizadoTelefone($numero, $idempresa)
    {
        $sql = "
            select
                GROUP_CONCAT(c.idcupon SEPARATOR',') as idscupon
                ,sum(c.valor)                as valor_cupons 
            from cupon c
            where c.celular = :numero
            and c.idempresa = :idempresa
            and c.data_uso is null
        ";

        $sql = Database::getInstance()->prepare($sql);
        $sql->bindValue(':numero', $numero);
        $sql->bindValue(':idempresa', $idempresa);
        $sql->execute();
        return $sql->fetch(\PDO::FETCH_ASSOC);
    }

    public static function getCuponsDetalheTelefone($numero, $idempresa)
    {
        $sql = "
            select
                 c.idcupon  as idcupon
                ,c.valor    as valor_cupons 
            from cupon c
            left join empresa_parametro ep on ep.idempresa = c.idempresa and ep.idparametro = 11 and ep.valor = 'true'
            where c.celular = :numero
            and c.idempresa = :idempresa
            and c.data_uso is null
        ";

        $sql = Database::getInstance()->prepare($sql);
        $sql->bindValue(':numero', $numero);
        $sql->bindValue(':idempresa', $idempresa);
        $sql->execute();
        return $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getCuponsPedido($idempresa, $idpedidovenda)
    {
        $sql = "
            select
                  GROUP_CONCAT(c.idcupon SEPARATOR',') as idscupon
                 ,count(c.idcupon)            as total_cupons
                 ,sum(c.valor)                as valor_cupons 
            from cupon c
            left join cupon_pedidos pv on pv.idcupon = c.idcupon and pv.idempresa = c.idempresa
            where pv.idpedidovenda = :idpedido
            and pv.idempresa = :idempresa
        ";

        $sql = Database::getInstance()->prepare($sql);
        $sql->bindValue(':idempresa', $idempresa);
        $sql->bindValue(':idpedido', $idpedidovenda);
        $sql->execute();
        return $sql->fetch(\PDO::FETCH_ASSOC) ?? [];
    }

    public static function getClientesEleitos(
        int   $idempresa,
        int   $quantidade_pedidos,
        float $valor_minimo
    ) {
        /*  ─────────────────────────  SQL  ───────────────────────── */
        /* versão sem comentários no meio do SQL */
        $sql = "
                SELECT
                    p.idcliente,
                    p.celular,
                    COALESCE(pe.pedidos_validos,0) + COALESCE(s.extras,0)                                     AS total_pedidos,
                    FLOOR( (COALESCE(pe.pedidos_validos,0) + COALESCE(s.extras,0)) / :quantidade_pedidos )    AS cupons_gerados,
                    pe.pedidos_ids,
                    s.extra_ids
                FROM pessoa p

                LEFT JOIN (
                    SELECT
                        idcliente,
                        COUNT(*)             AS extras,
                        GROUP_CONCAT(idextra) AS extra_ids
                    FROM cupon_extra
                    WHERE utilizado = 0
                    AND idempresa = :idempresa
                    GROUP BY idcliente
                ) s ON s.idcliente = p.idcliente

                LEFT JOIN (
                    SELECT
                        pv.idcliente,
                        COUNT(*)                       AS pedidos_validos,
                        GROUP_CONCAT(pv.idpedidovenda) AS pedidos_ids
                    FROM pedido_venda pv
                    WHERE pv.idempresa       = :idempresa
                    AND pv.total_pedido   >= :valor_minimo
                    AND pv.origin          = 2
                    AND NOT EXISTS (
                            SELECT 1
                            FROM cupon_pedidos cp
                            WHERE cp.idpedidovenda = pv.idpedidovenda
                            AND cp.idempresa     = pv.idempresa
                    )
                    GROUP BY pv.idcliente
                ) pe ON pe.idcliente = p.idcliente

                WHERE (COALESCE(pe.pedidos_validos,0) + COALESCE(s.extras,0)) >= :quantidade_pedidos
                HAVING cupons_gerados > 0
                ORDER BY total_pedidos DESC
        ";

        /*  ─────────────────────────  bind / exec  ───────────────────────── */
        $db = Database::getInstance()->prepare($sql);
        $db->bindValue(':idempresa',           $idempresa,         \PDO::PARAM_INT);
        $db->bindValue(':quantidade_pedidos',  $quantidade_pedidos, \PDO::PARAM_INT);
        $db->bindValue(':valor_minimo',        $valor_minimo);
        $db->execute();

        return $db->fetchAll(\PDO::FETCH_ASSOC);
    }



    public function listarSelosClientes($idempresa)
    {
        // 1. Busca a regra
        $regra = Cupon_regra::select()
            ->where('idempresa', $idempresa)
            ->where('status', 1)
            ->one();

        if (!$regra) {
            return [];
        }

        $qtd = (int) $regra['quantidade_pedidos'];
        $min = (float) $regra['valor_minimo'];

        // 2. Executa a SQL sem CTE
        $sql = "
            SELECT
                p.idcliente,
                p.nome,
                p.celular,
                COALESCE(pe.pedidos_validos, 0) + COALESCE(s.extras, 0) AS total_selos,
                FLOOR((COALESCE(pe.pedidos_validos, 0) + COALESCE(s.extras, 0)) / :qtd) AS cupons_prontos,
                :qtd - MOD((COALESCE(pe.pedidos_validos, 0) + COALESCE(s.extras, 0)), :qtd) AS faltam_para_proximo
            FROM pessoa p
            -- selos extras
            LEFT JOIN (
                SELECT idcliente, COUNT(*) AS extras
                FROM cupon_extra
                WHERE utilizado = 0
                AND idempresa = :idempresa
                GROUP BY idcliente
            ) s ON s.idcliente = p.idcliente

            -- pedidos válidos
            LEFT JOIN (
                SELECT pv.idcliente, COUNT(*) AS pedidos_validos
                FROM pedido_venda pv
                WHERE pv.idempresa = :idempresa
                AND pv.total_pedido >= :valor_minimo
                AND pv.origin = 2
                AND NOT EXISTS (
                    SELECT 1
                        FROM cupon_pedidos cp
                    WHERE cp.idpedidovenda = pv.idpedidovenda
                        AND cp.idempresa = pv.idempresa
                )
                GROUP BY pv.idcliente
            ) pe ON pe.idcliente = p.idcliente

            ORDER BY total_selos DESC
       ";

        $db = Database::getInstance()->prepare($sql);
        $db->bindValue(':idempresa',     $idempresa);
        $db->bindValue(':valor_minimo',  $min);
        $db->bindValue(':qtd',           $qtd);
        $db->execute();

        return $db->fetchAll(\PDO::FETCH_ASSOC);
    }
}
