<?php

/**
 * Desc: Classe helper para gerenciar Categorias no sistema
 * Autor: ClickExpress
 * Data de Início: 28/10/2025
 */

namespace src\handlers;

use src\models\Categoria as CategoriaModel;
use Exception;

class Categoria
{
    const RETORNO = [
        'idcategoria',
        'descricao',
        'idempresa',
        'imprimir',
        'quantidade_impressao',
        'nome_impresora'
    ];

    /**
     * Busca todas as categorias associadas a uma empresa específica.
     *
     * @param int $idempresa O ID da empresa.
     * @return array Lista de categorias associadas à empresa.
     */
    public static function getCategorias($idempresa)
    {
        $categorias = CategoriaModel::select(self::RETORNO)
            ->where('idempresa', $idempresa)
            ->orderBy('descricao', 'ASC')
            ->execute();
        
        return $categorias;
    }

    /**
     * Busca uma categoria específica pelo ID.
     *
     * @param int $idempresa O ID da empresa.
     * @param int $idcategoria O ID da categoria.
     * @return array|null Dados da categoria ou null se não encontrado.
     */
    public static function getCategoriaById($idempresa, $idcategoria)
    {
        $categoria = CategoriaModel::select(self::RETORNO)
            ->where('idempresa', $idempresa)
            ->where('idcategoria', $idcategoria)
            ->one();

        return $categoria;
    }

    /**
     * Adiciona uma nova categoria.
     *
     * @param array $data Dados da categoria a ser adicionada.
     * @return array Dados da categoria criada.
     * @throws Exception Se houver erro ao criar a categoria.
     */
    public static function addCategoria($data)
    {
        // Validações
        if (empty(trim($data['descricao']))) {
            throw new Exception('A descrição da categoria é obrigatória.');
        }

        // Verifica se já existe uma categoria com essa descrição na empresa
        $categoriaExistente = CategoriaModel::select(['idcategoria'])
            ->where('idempresa', $data['idempresa'])
            ->where('descricao', trim($data['descricao']))
            ->one();

        if ($categoriaExistente) {
            throw new Exception('Já existe uma categoria com esta descrição.');
        }

        // Prepara dados para inserção
        $dadosCategoria = [
            'idempresa' => (int)$data['idempresa'],
            'descricao' => trim($data['descricao']),
            'imprimir' => isset($data['imprimir']) ? (int)$data['imprimir'] : 1,
            'quantidade_impressao' => isset($data['quantidade_impressao']) ? (int)$data['quantidade_impressao'] : 1,
            'nome_impresora' => isset($data['nome_impresora']) ? trim($data['nome_impresora']) : null
        ];

        // Insere a categoria
        $idcategoria = CategoriaModel::insert($dadosCategoria);

        if (!$idcategoria) {
            throw new Exception('Erro ao criar a categoria.');
        }

        // Retorna a categoria criada
        return self::getCategoriaById($data['idempresa'], $idcategoria);
    }

    /**
     * Edita uma categoria existente.
     *
     * @param array $data Dados da categoria a ser editada.
     * @return array Dados da categoria atualizada.
     * @throws Exception Se houver erro ao editar a categoria.
     */
    public static function editCategoria($data)
    {
        // Verifica se a categoria existe
        $categoriaExistente = self::getCategoriaById($data['idempresa'], $data['idcategoria']);
        
        if (!$categoriaExistente) {
            throw new Exception('Categoria não encontrada.');
        }

        // Prepara dados para atualização
        $dadosUpdate = [];

        if (isset($data['descricao'])) {
            $descricao = trim($data['descricao']);
            if (empty($descricao)) {
                throw new Exception('A descrição da categoria não pode estar vazia.');
            }

            // Verifica se já existe outra categoria com essa descrição
            $outraCategoria = CategoriaModel::select(['idcategoria'])
                ->where('idempresa', $data['idempresa'])
                ->where('descricao', $descricao)
                ->where('idcategoria', '!=', $data['idcategoria'])
                ->one();

            if ($outraCategoria) {
                throw new Exception('Já existe outra categoria com esta descrição.');
            }

            $dadosUpdate['descricao'] = $descricao;
        }

        if (isset($data['imprimir'])) {
            $dadosUpdate['imprimir'] = (int)$data['imprimir'];
        }

        if (isset($data['quantidade_impressao'])) {
            $dadosUpdate['quantidade_impressao'] = (int)$data['quantidade_impressao'];
        }

        if (isset($data['nome_impresora'])) {
            $dadosUpdate['nome_impresora'] = $data['nome_impresora'] ? trim($data['nome_impresora']) : null;
        }

        // Atualiza a categoria
        if (!empty($dadosUpdate)) {
            CategoriaModel::update($dadosUpdate)
                ->where('idcategoria', $data['idcategoria'])
                ->where('idempresa', $data['idempresa'])
                ->execute();
        }

        // Retorna a categoria atualizada
        return self::getCategoriaById($data['idempresa'], $data['idcategoria']);
    }

    /**
     * Deleta uma categoria.
     *
     * @param array $data Dados contendo ID da categoria e empresa.
     * @return bool True se deletado com sucesso.
     * @throws Exception Se houver erro ao deletar a categoria.
     */
    public static function deleteCategoria($data)
    {
        // Verifica se a categoria existe
        $categoria = self::getCategoriaById($data['idempresa'], $data['idcategoria']);
        
        if (!$categoria) {
            throw new Exception('Categoria não encontrada.');
        }

        // Verifica se existem produtos vinculados a esta categoria
        $produtosVinculados = \src\models\Produtos::select(['COUNT(*) as total'])
            ->where('idempresa', $data['idempresa'])
            ->where('idcategoria', $data['idcategoria'])
            ->one();

        if ($produtosVinculados && $produtosVinculados['total'] > 0) {
            throw new Exception('Não é possível excluir esta categoria pois existem produtos vinculados a ela.');
        }

        // Deleta a categoria
        $deletado = CategoriaModel::delete()
            ->where('idcategoria', $data['idcategoria'])
            ->where('idempresa', $data['idempresa'])
            ->execute();

        if (!$deletado) {
            throw new Exception('Erro ao deletar a categoria.');
        }

        return true;
    }
}
