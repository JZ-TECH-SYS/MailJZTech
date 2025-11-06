<?php

/**
 * desc: helper de manipulação de produtos 
 * @autor: joaosn
 * @date: 23/05/2020
 */

namespace src\handlers;

use Com\Tecnick\Barcode\Type\Square\Aztec\Data;
use \src\models\Produtos as ProdutosModel;
use \src\models\Produto_parametro as ProdutoParmsModel;
use \src\handlers\Help;
use \core\Controller as ctrl;
use Exception;
use src\models\Pedido_estoque as PedidoEstoqueModel;
use src\models\Tipo_produto as TiposProdutosModel;
use src\models\Categoria as CategoriasModel;
use core\Database as db;
use core\Database;
use PDO;
use PDOException;
use src\models\Empresa;

class Produtos extends ctrl
{
    /**
     * Adiciona um novo produto
     *
     * @param $data array com as informações do produto:
     * - idempresa: o ID da empresa que está cadastrando o produto
     * - nome: o nome do produto
     * - descricao (opcional): a descrição do produto
     * - cod_barras (opcional): o código de barras do produto
     * - tipo_produto: o tipo do produto (por exemplo, acrescimo ou produto)
     * - preco: o preço do produto
     * - idcategoria: o ID da categoria do produto 
     * - foto (opcional): a URL da foto do produto
     * 
     * @return array com as informações do produto adicionado
     */
    public static function addProduto($data)
    {
        try {
            db::getInstance()->beginTransaction();
            $precoCusto = 0;
            if (array_key_exists('preco_custo', $data) && $data['preco_custo'] !== '' && $data['preco_custo'] !== null) {
                $precoCusto = $data['preco_custo'] == 0 ? 0 : Help::currencyBanco($data['preco_custo']);
            }

            $idproduto = ProdutosModel::insert([
                'idempresa' => $data['idempresa'],
                'nome' => $data['nome'],
                'descricao' => (!empty($data['descricao'])) ? $data['descricao'] : null,
                'cod_barras' => (!empty($data['cod_barras'])) ? $data['cod_barras'] : null,
                'tipo_produto' => $data['tipo_produto'],
                'preco' => Help::currencyBanco($data['preco']),
                'preco_custo' => $precoCusto,
                'idcategoria' => $data['idcategoria'],
                'foto' => (!empty($data['foto'])) ? $data['foto'] : null,
                'tipo_item_pizza' => $data['tipo_item_pizza'] ?? null,
                'ncm' => $data['ncm'] ?? null
            ])->execute();

            ProdutoParmsModel::insert([
                'idparametro'        => 1,
                'idempresa'          => $data['idempresa'],
                'idproduto'          => $idproduto,
                'descricao'          => ProdutoParmsModel::descricaoParametro1,
                'descricao_auxiliar' => ProdutoParmsModel::descricaoAXParametro1,
                'valor'              => $data['permite_acrescimo'] ?? 'false'
            ])->execute();

            ProdutoParmsModel::insert([
                'idparametro'        => 3,
                'idempresa'          => $data['idempresa'],
                'idproduto'          => $idproduto,
                'descricao'          => ProdutoParmsModel::descricaoParametro3,
                'descricao_auxiliar' => ProdutoParmsModel::descricaoAXParametro3,
                'valor'              => $data['permite_obs'] ?? 'false'
            ])->execute();


            ProdutoParmsModel::insert([
                'idparametro'        => 2,
                'idempresa'          => $data['idempresa'],
                'idproduto'          => $idproduto,
                'descricao'          => ProdutoParmsModel::descricaoParametro2,
                'descricao_auxiliar' => ProdutoParmsModel::descricaoAXParametro2,
                'valor'              => $data['itens_gratis'] ?? 'false'
            ])->execute();

            db::getInstance()->commit();
            return self::getProdutosById($data['idempresa'], $idproduto, $data['tipo_produto']);
        } catch (PDOException $e) {
            db::getInstance()->rollBack();
            throw new PDOException($e->getMessage());
        }
    }


    /**
     * Edita um produto existente
     * 
     * @param $data array com as informações do produto a ser editado, incluindo idproduto, idempresa, nome, descricao, cod_barras, tipo_produto, preco, idcategoria, foto
     * @return array com os dados do produto atualizados
     */
    public static function editProduto($data)
    {
        try {
            db::getInstance()->beginTransaction();
            // Busca o produto a ser editado
            $produto = ProdutosModel::select()->where('idproduto', $data['idproduto'])->where('idempresa', $data['idempresa'])->one();

            // Verifica se o produto existe
            if (empty($produto)) {
                throw new Exception('Produto não encontrado Editar');
            }

            // Deleta a imagem antiga, se houver
            if (!empty($produto['foto']) && $produto['foto'] != $data['foto'] && !empty($data['foto'])) {
                Resize::deleteImage($produto['foto']);
            }


            $preco = $data['preco'] == 0 ? 0 : Help::currencyBanco($data['preco']);
            $precoCusto = $produto['preco_custo'] ?? 0;
            if (array_key_exists('preco_custo', $data)) {
                $precoCusto = ($data['preco_custo'] == 0 || $data['preco_custo'] === '' || $data['preco_custo'] === null)
                    ? 0
                    : Help::currencyBanco($data['preco_custo']);
            }

            // Atualiza os dados do produto
            ProdutosModel::update([
                'nome'            => !empty($data['nome'])            ? $data['nome']                      : $produto['nome'],
                'descricao'       => !empty($data['descricao'])       ? $data['descricao']                 : $produto['descricao'],
                'cod_barras'      => !empty($data['cod_barras'])      ? $data['cod_barras']                : $produto['cod_barras'],
                'tipo_produto'    => !empty($data['tipo_produto'])    ? $data['tipo_produto']              : $produto['tipo_produto'],
                'preco'           => $preco,
                'preco_custo'     => $precoCusto,
                'idcategoria'     => !empty($data['idcategoria'])     ? $data['idcategoria']               : $produto['idcategoria'],
                'foto'            => (!empty($data['foto']))          ? $data['foto']                      : $produto['foto'],
                'tipo_item_pizza' => !empty($data['tipo_item_pizza']) ? $data['tipo_item_pizza']          : $produto['tipo_item_pizza'],
                'ncm'             => !empty($data['ncm']) ? $data['ncm'] : $produto['ncm']
            ])
                ->where('idproduto', $data['idproduto'])
                ->where('idempresa', $data['idempresa'])
                ->execute();

            $parmsAtual = ProdutoParmsModel::select()->where('idproduto', $data['idproduto'])->where('idempresa', $data['idempresa'])->where('idparametro', 1)->one();
            // Atualiza os parâmetros do produto
            ProdutoParmsModel::update([
                'valor' => $data['permite_acrescimo'] ?? $parmsAtual['valor']
            ])
                ->where('idproduto', $data['idproduto'])
                ->where('idempresa', $data['idempresa'])
                ->where('idparametro', 1)
                ->execute();

            $parmsAtualObs = ProdutoParmsModel::select()->where('idproduto', $data['idproduto'])->where('idempresa', $data['idempresa'])->where('idparametro', 3)->one();
            if (isset($parmsAtualObs['valor'])) {
                ProdutoParmsModel::update([
                    'valor' => $data['permite_obs'] ?? $parmsAtualObs['valor']
                ])
                    ->where('idproduto', $data['idproduto'])
                    ->where('idempresa', $data['idempresa'])
                    ->where('idparametro', 3)
                    ->execute();
            }

            if (!empty($data['itens_gratis']) && (int)$data['itens_gratis'] > 0) {
                $parmsGratis = ProdutoParmsModel::select()->where('idproduto', $data['idproduto'])->where('idempresa', $data['idempresa'])->where('idparametro', 2)->one();
                ProdutoParmsModel::update([
                    'valor' => $data['itens_gratis'] ?? $parmsGratis['valor']
                ])
                    ->where('idproduto', $data['idproduto'])
                    ->where('idempresa', $data['idempresa'])
                    ->where('idparametro', 2)
                    ->execute();
            }
            db::getInstance()->commit();
            return self::getProdutosById($data['idempresa'], $data['idproduto']);
        } catch (Exception $e) {
            db::getInstance()->rollBack();
            throw new Exception($e->getMessage());
        }
    }


    /**
     * Deleta um produto específico de uma empresa.
     *
     * Esta função verifica se o produto existe com base nos parâmetros fornecidos.
     * Se o produto for encontrado e tiver uma foto associada, a foto será excluída.
     * Em seguida, o produto será excluído do banco de dados.
     *
     * @param array $data Um array contendo informações do produto, incluindo 'idproduto' e 'idempresa'
     * @return array Retorna o produto deletado
     * @throws Exception Se o produto não for encontrado, uma exceção é lançada com a mensagem 'Produto não encontrado'
     */
    public static function deleteProduto($data)
    {
        // Busca o produto para verificar se existe
        $getProduto = ProdutosModel::select()->where('idproduto', $data['idproduto'])->where('idempresa', $data['idempresa'])->one();
        if (empty($getProduto)) {
            throw new Exception('Produto não encontrado para deletar');
        }
        // Se existir, verifica se há foto e exclui
        if (!empty($getProduto['foto'])) {
            Resize::deleteImage($getProduto['foto']);
        }

        // Executa a exclusão do produto
        ProdutosModel::delete()->where('idproduto', $data['idproduto'])->where('idempresa', $data['idempresa'])->execute();
        ProdutoParmsModel::delete()->where('idproduto', $data['idproduto'])->where('idempresa', $data['idempresa'])->execute();
        // Retorna o produto deletado
        return $getProduto;
    }



    /**
     * Adiciona o link da foto aos itens em um array de produtos.
     *
     * Esta função percorre o array de produtos e adiciona o link completo da foto
     * ao campo 'foto' de cada item, usando a URL base fornecida pela função ctrl::getBaseUrl().
     *
     * @param array $items Array de itens que contêm informações do produto
     * @return array Retorna o array de itens com o link da foto atualizado
     */
    public static function linkFoto(array $items)
    {
        if (!Help::is_multidimensional($items) && !empty($items['foto'])) {
            $items['foto'] = ctrl::getBaseUrl() . 'images/' . $items['foto'];
        } else if (Help::is_multidimensional($items)) {
            $items = array_map(function ($item) {
                if (!empty($item['foto'])) {
                    $item['foto'] = ctrl::getBaseUrl() . 'images/' . $item['foto'];
                }
                return $item;
            }, $items);
        }

        return $items;
    }


    public static function getProdutosBalance($idempresa)
    {
        return ProdutosModel::select()->where('idempresa', $idempresa)->get();
    }


    /**
     * Retorna a lista de produtos de uma empresa, incluindo o link da foto.
     *
     * Esta função consulta o banco de dados para obter todos os produtos de uma empresa específica,
     * juntamente com o saldo do produto. Apenas produtos com saldo positivo são incluídos.
     * Em seguida, ela adiciona o link completo da foto aos itens usando a função linkFoto().
     *
     * @param int $idempresa O ID da empresa cujos produtos serão buscados
     * @return array Retorna um array de produtos com o link da foto atualizado
     */
    public static function getProdutos($idempresa, $tipo = 1)
    {

        $items = ProdutosModel::select([
            'produtos.cod_barras',
            'produtos.idproduto',
            'produtos.idempresa',
            'produtos.nome',
            'produtos.descricao',
            'produtos.preco',
            'produtos.preco_custo',
            'produtos.foto',
            's.quantidade',
            'produtos.tipo_produto',
            'cs.idcategoria',
            'cs.descricao as categoria',
            'produtos.tipo_item_pizza',
            'produtos.ncm'
        ])
            ->leftJoin('saldo_produto as s', function ($join) {
                $join->on('s.idproduto', '=', 'produtos.idproduto');
                $join->on('s.idempresa', '=', 'produtos.idempresa');
            })
            ->leftJoin('categoria as cs', function ($join) {
                $join->on('cs.idcategoria', '=', 'produtos.idcategoria');
                $join->on('cs.idempresa', '=', 'produtos.idempresa');
            })
            ->where('produtos.idempresa', '=', $idempresa);
        // Tratamento limpo pro tipo
        if ($tipo == 3) {
            // apenas bordas
            $items->where('produtos.tipo_produto', '=', 3)
                ->where('produtos.tipo_item_pizza', '=', 3);
        } else {
            // produto normal (1) ou acréscimo (2)
            $items->where('produtos.tipo_produto', '=', $tipo);
        }

        if (ctrl::validar_saldo()) {
            $items->where('s.quantidade', '>', 0);
        }
        $result = $items->execute();
        $items = self::linkFoto($result);

        $result = array_map(function ($item) {
            $permiteAcrescimo = ProdutoParmsModel::select()->where('idproduto', $item['idproduto'])->where('idempresa', $item['idempresa'])->where('idparametro', 1)->one();
            $itensGratis =  ProdutoParmsModel::select()->where('idproduto', $item['idproduto'])->where('idempresa', $item['idempresa'])->where('idparametro', 2)->one();
            $permiteObs =  ProdutoParmsModel::select()->where('idproduto', $item['idproduto'])->where('idempresa', $item['idempresa'])->where('idparametro', 3)->one();

            $item['permite_acrescimo'] = (isset($permiteAcrescimo['valor']) && $permiteAcrescimo['valor'] == 'true') ? true : false;
            $item['itens_gratis'] = (!empty($itensGratis['valor'])  ? (int)$itensGratis['valor'] : false);
            $item['permite_obs'] = (isset($permiteObs['valor'])  && $permiteObs['valor'] == 'true') ? true : false;
            return $item;
        }, $items);

        return $result;
    }


    /**
     * Retorna um produto específico de uma empresa, incluindo o link da foto.
     *
     * Esta função consulta o banco de dados para obter um produto específico de uma empresa,
     * juntamente com o saldo do produto. Apenas produtos com saldo positivo são incluídos.
     * Em seguida, ela adiciona o link completo da foto ao item usando a função linkFoto().
     *
     * @param int $idempresa O ID da empresa cujo produto será buscado
     * @param int $idproduto O ID do produto a ser buscado
     * @return array Retorna um array contendo o produto com o link da foto atualizado
     */
    public static function getProdutosById($idempresa, $idproduto, $tipo = null)
    {
        $items = ProdutosModel::select([
            'produtos.cod_barras',
            'produtos.idproduto',
            'produtos.idempresa',
            'produtos.nome',
            'produtos.descricao',
            'produtos.preco',
            'produtos.preco_custo',
            'produtos.foto',
            's.quantidade',
            'produtos.tipo_produto',
            'produtos.idcategoria',
            'produtos.tipo_item_pizza',
            'produtos.ncm'
        ])
            ->leftJoin('saldo_produto as s', function ($join) {
                $join->on('s.idproduto', '=', 'produtos.idproduto');
                $join->on('s.idempresa', '=', 'produtos.idempresa');
            })
            ->where('produtos.idempresa', '=', $idempresa)
            ->where('produtos.idproduto', '=', $idproduto);
        if (isset($tipo) && !empty($tipo)) {
            $items->where('produtos.tipo_produto', '=', $tipo);
        }
        if (ctrl::validar_saldo()) {
            $items->where('s.quantidade', '>', 0);
        }
        $result = $items->one();
        if (empty($result)) {
            throw new Exception('Produto não encontrado para byID:' . $idproduto);
        }
        $permiteAcrescimo = ProdutoParmsModel::select()->where('idproduto', $result['idproduto'])->where('idempresa', $result['idempresa'])->where('idparametro', 1)->one();
        $itensGratis      =  ProdutoParmsModel::select()->where('idproduto', $result['idproduto'])->where('idempresa', $result['idempresa'])->where('idparametro', 2)->one();
        $permiteObs       =  ProdutoParmsModel::select()->where('idproduto', $result['idproduto'])->where('idempresa', $result['idempresa'])->where('idparametro', 3)->one();

        $result['permite_acrescimo'] = (isset($permiteAcrescimo['valor']) && $permiteAcrescimo['valor'] == 'true') ? true : false;
        $result['itens_gratis'] = (!empty($itensGratis['valor'])  ? (int)$itensGratis['valor'] : false);
        $result['permite_obs'] = (isset($permiteObs['valor']) && $permiteObs['valor'] == 'true') ? true : false;
        $items = self::linkFoto($result);
        return $items;
    }


    /**
     * Adiciona o saldo de um produto específico de uma empresa.
     *
     * Esta função insere um registro na tabela PedidoEstoqueModel, incluindo a quantidade
     * e o CNPJ do fornecedor. Se o CNPJ do fornecedor não for fornecido, um valor padrão
     * será usado.
     *
     * @param array $produto Um array contendo informações do produto, incluindo 'idempresa' e 'idproduto'
     * @param array $saldo Um array contendo informações do saldo, incluindo 'quantidade' e 'cnpj_fornecedor' (opcional)
     */
    public static function addProdutoSaldo($produto, $saldo)
    {
        PedidoEstoqueModel::insert([
            'idempresa'          => $produto['idempresa'],
            'idproduto'          => $produto['idproduto'],
            'quantidade'         => $saldo['quantidade'],
            'cnpj_fornecedor'    => $saldo['cnpj_fornecedor'] ?? 99999999999999,
            'valor_unitario'     => $saldo['valor_unitario'] ?? 0
        ])->execute();
    }


    /**
     * Retorna uma lista de categorias de produtos pertencentes a uma empresa específica.
     * @param int $idempresa O ID da empresa.
     * @return array Uma lista de objetos CategoriaModel correspondentes às categorias da empresa.
     */
    public static function getCategorias($idempresa)
    {
        // Seleciona todas as categorias de produtos no banco de dados que pertencem à empresa com o ID especificado.
        $items = CategoriasModel::select()->where('idempresa', $idempresa)->get();

        // Retorna a lista de objetos CategoriaModel correspondentes às categorias da empresa.
        return $items;
    }

    /**
     * Retorna uma lista de tipos de produtos pertencentes a uma empresa específica.
     * @param int $idempresa O ID da empresa.
     * @return array Uma lista de objetos TipoProdutoModel correspondentes aos tipos de produtos da empresa.
     */
    public static function getTiposProdutos($idempresa)
    {
        // Seleciona todos os tipos de produtos no banco de dados que pertencem à empresa com o ID especificado.
        $items = TiposProdutosModel::select()->where('idempresa', $idempresa)->get();

        // Retorna a lista de objetos TipoProdutoModel correspondentes aos tipos de produtos da empresa.
        return $items;
    }

    /**
     * valida se o produto pode ser impresso na comanda
     */
    public static function validarProdutoPrint($idempresa, $idproduto)
    {
        $produto = ProdutosModel::select()->where('idempresa', $idempresa)->where('idproduto', $idproduto)->one();
        if (empty($produto)) {
            throw new Exception('Produto não encontrado para validar impressão ID:' . $idproduto . ' Empresa:' . $idempresa);
        }
        $categoria = CategoriasModel::select()->where('idempresa', $idempresa)->where('idcategoria', $produto['idcategoria'])->one();
        if (empty($categoria)) {
            throw new Exception('Categoria não encontrada');
        }
        return $categoria['imprimir'] == '1' ? true : false;
    }


    public static function getImpostoProduto($idempresa, $idproduto, $idpedidovenda)
    {
        return ProdutosModel::getImpostoProduto($idempresa, $idproduto, $idpedidovenda);
    }
}
