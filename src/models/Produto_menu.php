<?php

namespace src\models;

use  core\Database;
use  \core\Model;
use  \src\handlers\Produtos as ProdHelp;
use  \src\handlers\Help;
use PDO;

/**
 * Classe modelo para a tabela 'Menu' do banco de dados.
 */
class Produto_menu extends Model
{
    /** ➊ chaves que a IA realmente precisa */
    private const PROD_KEYS = [
        'idproduto',
        'nome',
        'descricao',
        'preco',
        'permite_acrescimo',
        'itens_gratis',
        'permite_obs'
    ];

    /** ➊ Campos que a IA precisa saber da empresa */
    private const EMPRESA_KEYS = [
        'idempresa',
        'nomefantasia',
        'telefone',
        'endereco',
        'numero',
        'bairro',
        'cidade',
        'tempo_espera',
        'horario_atendimento',
        'hora_abertura',
        'hora_fechamento',
        'meios_pagamentos'
    ];



    /**
     * Retorna array enxuto: menus → produtos (apenas campos úteis p/ IA)
     */
    public static function getProdutosMenuEmpresaIA(int $idempresa): array
    {
        $empressa = Help::getInfoPsedidoOn($idempresa);
        $infos = array_intersect_key($empressa, array_flip(self::EMPRESA_KEYS));

        $sql = '
            SELECT
                pm.idempresa,
                pm.idparmsmenu,
                pm.idmenu,
                pm.ordem,
                m.descricao,
                m.ordem AS ordem_menu,
                pm.idproduto,
                la.quantidade  AS limite_acrescimo,
                la2.quantidade AS limite_bordas
            FROM produto_menu pm
            JOIN menu      m  ON m.idmenu   = pm.idmenu    AND m.idempresa = pm.idempresa
            JOIN produtos  p  ON p.idproduto= pm.idproduto AND p.idempresa = pm.idempresa
            LEFT JOIN limitar_acrescimos la   ON la.idproduto = pm.idproduto AND la.idempresa = pm.idempresa AND la.tipo_produto = 1
            LEFT JOIN limitar_acrescimos la2  ON la2.idproduto= pm.idproduto AND la2.idempresa= pm.idempresa AND la2.tipo_produto = 2
            WHERE pm.idempresa = :idempresa
            ORDER BY m.ordem ASC, pm.ordem ASC, p.nome ASC
        ';
        $sth = Database::getInstance()->prepare($sql);
        $sth->bindValue(':idempresa', $idempresa, PDO::PARAM_INT);
        $sth->execute();
        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

        $menus = [];

        foreach ($rows as $row) {
            // ➋ Produto completo vindo do handler
            $produtoFull = ProdHelp::getProdutosById($row['idempresa'], $row['idproduto']);

            // ➌ Mantém apenas chaves relevantes
            $produto     = array_intersect_key($produtoFull, array_flip(self::PROD_KEYS));

            // extras úteis p/ IA (limites + trava)
            $produto['limite_acrescimo'] = $row['limite_acrescimo'];
            $produto['limite_bordas']    = $row['limite_bordas'];
            $produto['trava_acrescimo']  = Produto_acrescimos::getByIdTrava($row['idempresa'], $row['idproduto']);
            $produto['ordem']            = $row['ordem'];

            // ➍ Agrupa por menu
            $idMenu = $row['idmenu'];
            if (!isset($menus[$idMenu])) {
                $menus[$idMenu] = [
                    'idmenu'    => $idMenu,
                    'descricao' => $row['descricao'],
                    'ordem'     => $row['ordem_menu'],
                    'produtos'  => []
                ];
            }
            $menus[$idMenu]['produtos'][] = $produto;
        }


        // ➎ Adiciona infos da empresa no topo
        return [
            'empresa' => $infos,
            'cardapio'   => array_values($menus)
        ];
    }


    public static function getProdutosMenu($idempresa)
    {
        $sql = '
            SELECT
                pm.idempresa,
                pm.idparmsmenu,
                pm.idmenu,
                pm.ordem,
                m.descricao,
                m.ordem AS ordem_menu,
                pm.idproduto,
                la.quantidade AS limite_acrescimo,
                la2.quantidade AS limite_bordas
            FROM produto_menu pm
            INNER JOIN menu m ON m.idmenu = pm.idmenu AND m.idempresa = pm.idempresa
            INNER JOIN produtos p  ON p.idproduto = pm.idproduto AND p.idempresa = pm.idempresa
            LEFT JOIN limitar_acrescimos la  ON la.idproduto = pm.idproduto AND la.idempresa = pm.idempresa AND la.tipo_produto = 1
            LEFT JOIN limitar_acrescimos la2 ON la2.idproduto = pm.idproduto AND la2.idempresa = pm.idempresa AND la2.tipo_produto = 2
            WHERE pm.idempresa = :idempresa
            ORDER BY m.ordem ASC, pm.ordem ASC, p.nome ASC
        ';
        $sql = Database::getInstance()->prepare($sql);
        $sql->bindValue(':idempresa', $idempresa);
        $sql->execute();
        $result = $sql->fetchAll(PDO::FETCH_ASSOC);

        $menus = []; // nova array

        if (!empty($result)) {
            foreach ($result as $value) {
                $produto = ProdHelp::getProdutosById($value['idempresa'], $value['idproduto']);
                $trava_acrescimo = Produto_acrescimos::getByIdTrava($value['idempresa'], $value['idproduto']);
                $produto['idparmsmenu'] = $value['idparmsmenu']; // adiciona o idparmsmenu ao produto
                $produto['limite_acrescimo'] = $value['limite_acrescimo'];
                $produto['limite_bordas'] = $value['limite_bordas'];
                $produto['trava_acrescimo'] = $trava_acrescimo;
                $produto['ordem'] = $value['ordem'];
                //$produto['imgbase64'] = Help::getImgBase64($produto['foto']);

                if (isset($menus[$value['idmenu']])) {
                    // se o menu já existe, adiciona o produto à lista de produtos
                    $menus[$value['idmenu']]['produtos'][] = $produto;
                } else {
                    // se o menu não existe, cria uma nova entrada na array
                    $menus[$value['idmenu']] = [
                        'idempresa' => $value['idempresa'],
                        'idmenu' => $value['idmenu'],
                        'descricao' => $value['descricao'],
                        'ordem' => $value['ordem_menu'],
                        'produtos' => [$produto], // inicia a lista de produtos com o produto atual
                    ];
                }
            }
        }

        return  array_values($menus);
    }
}
