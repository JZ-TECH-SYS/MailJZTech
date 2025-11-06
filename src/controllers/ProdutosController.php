<?php

/**
 * Classe ProdutosController
 * Controlador de produtos responsável por gerenciar operações relacionadas a produtos.
 * 
 * @author João Silva
 * @since 23/05/2023
 */

namespace src\controllers;

use \core\Controller as ctrl;
use \src\handlers\Produtos as ProdHelp;
use \src\handlers\Resize;
use Exception;
use src\models\Produtos;

class ProdutosController extends ctrl
{
    /**
     * Campos obrigatórios para adicionar um produto.
     */
    const ADDCAMPOS = [
        'idempresa', 'nome', 'tipo_produto', 'idcategoria','cod_barras'
    ];

    /**
     * Campos obrigatórios para editar um produto.
     */
    const EDITCAMPOS = [
        'idempresa', 'idproduto'
    ];

    /**
     * Campos obrigatórios para deletar um produto.
     */
    const DELETECAMPOS = [
        'idempresa', 'idproduto'
    ];

    static $linkPhoto;

    /**
     * Retorna todos os produtos de uma empresa.
     * 
     * @param array $args Array contendo o ID da empresa.
     */
    public function getProdutos($args)
    {
        $tipo = $args['tipo'] ?? 1;
        $produtos = ProdHelp::getProdutos($args['idempresa'], $tipo);
        ctrl::response($produtos, 200);
    }

    public function getProdutosBalance($args)
    {
        $produtos = ProdHelp::getProdutosBalance($args['idempresa']);
        ctrl::response($produtos, 200);
    }

    /**
     * Retorna um produto específico de uma empresa.
     * 
     * @param array $args Array contendo o ID da empresa e o ID do produto.
     */
    public function getProdutosById($args)
    {
        $tipo = $args['tipo'] ?? null;
        $produtos = ProdHelp::getProdutosById($args['idempresa'], $args['idproduto'], $tipo);
        if (!$produtos) {
            ctrl::response('Produto não encontrado para ID:'.$args['idproduto'], 404);
        }
        ctrl::response($produtos, 200);
    }

    /**
     * Adiciona um novo produto.
     */
    public function addProduto()
    {
  
        try {
            self::$linkPhoto = '';
            $data = $_POST;
            ctrl::verificarCamposVazios($data, self::ADDCAMPOS);

            $codBarras = Produtos::select()->where('cod_barras', $data['cod_barras'])->where('idempresa', $data['idempresa'])->one();
            if ($codBarras) {
                throw new Exception('Código de barras já cadastrado!');
            }

            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $linkPhoto = Resize::saveUploadedImage($_FILES['foto']);
                $data['foto'] = $linkPhoto;
            }

            $produtos = ProdHelp::addProduto($data);
            if (self::$linkPhoto != '') {
                $produtos['foto'] = $this->getBaseUrl() . $linkPhoto;
            }
            if (isset($data['saldo']) && isset($data['saldo']['quantidade']) && $data['saldo']['quantidade'] > 0) {
                ProdHelp::addProdutoSaldo($produtos, $data['saldo']);
                $produtos = ProdHelp::getProdutosById($data['idempresa'], $produtos['idproduto']);
            }
            ctrl::response($produtos, 201);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Edita um produto existente.
     */
    public function editProduto()
    {
        try {
            self::$linkPhoto = '';
            $data = $_POST;
            ctrl::verificarCamposVazios($data, self::EDITCAMPOS);

            // Busca o produto atual para verificação
            $itemm = Produtos::select()->where('idproduto', $data['idproduto'])->where('idempresa', $data['idempresa'])->one();
            if ($itemm['cod_barras'] != $data['cod_barras']) {
                $codBarras = Produtos::select()->where('cod_barras', $data['cod_barras'])->where('idempresa', $data['idempresa'])->one();
                if ($codBarras) {
                    throw new Exception('Código de barras já cadastrado!');
                }
            }

            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $linkPhoto = Resize::saveUploadedImage($_FILES['foto']);
                $data['foto'] = $linkPhoto;
            }else{
                $data['foto'] = null;
            }

            $produtos = ProdHelp::editProduto($data);
            if (self::$linkPhoto != '') {
                $produtos['foto'] = $this->getBaseUrl() . $linkPhoto;
            }
            ctrl::response($produtos, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Deleta um produto e sua foto.
     */
    public function deleteProduto()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::DELETECAMPOS);
            $produto = ProdHelp::deleteProduto($data);
            $produto['msg'] = 'Produto e foto excluídos!';
            ctrl::response($produto, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Retorna tipos de produtos.
     */
    public function getTiposProdutos($args)
    {
        $tipos = ProdHelp::getTiposProdutos($args['idempresa']);
        ctrl::response($tipos, 200);
    }

    /**
     * Retorna categorias de produtos.
     */
    public function getCategorias($args)
    {
        $tipos = ProdHelp::getCategorias($args['idempresa']);
        ctrl::response($tipos, 200);
    }
}
