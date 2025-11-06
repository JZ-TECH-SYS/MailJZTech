<?php

/**
 * Classe helper para gerenciar Menu no sistema
 * 
 * Esta classe fornece métodos para gerenciar menus de um sistema.
 * 
 * Autor: Joaosn
 * Data de Início: 23/05/2023
 */

namespace src\handlers;

use core\Database;
use src\models\Menu as MenuModel;
use src\models\Produto_menu as ProdutoMenuModel;
use core\Database as db;
use Exception;

class Menu
{
    /**
     * Obtém todos os menus de uma determinada empresa
     * 
     * @param int $idempresa O ID da empresa
     * @return array|null Um array com os menus da empresa ou null se não houver nenhum menu registrado
     */
    public static function getMenus($idempresa)
    {
        $menus = MenuModel::select()
            ->where('idempresa', $idempresa)
            ->orderBy('ordem', 'asc')
            ->orderBy('descricao', 'asc')
            ->execute();
        return $menus;
    }

    /**
     * Obtém um menu específico de uma empresa
     * 
     * @param int $idempresa O ID da empresa
     * @param int $idmenu O ID do menu
     * @return array|null Um array com o menu especificado ou null se o menu não existir
     */
    public static function getMenuById($idempresa, $idmenu)
    {
        $menu = MenuModel::select()->where('idempresa', $idempresa)->where('idmenu', $idmenu)->one();
        return $menu;
    }

    /**
     * Adiciona um novo menu para uma determinada empresa
     * 
     * @param array $data Um array contendo os dados do novo menu
     * @return array|null Um array com o novo menu adicionado ou null se não for possível adicionar o menu
     */
    public static function addMenu($data)
    {
        // Verifica se já existe um menu com a mesma descrição para a empresa
        $isdescricao = MenuModel::select()->where('idempresa', $data['idempresa'])->where('descricao', $data['descricao'])->one();
        if (!empty($isdescricao)) {
            throw new Exception('Descrição já cadastrada!');
        }

        // Gera ordem automaticamente se não foi informada
        $ordem = isset($data['ordem']) && $data['ordem'] > 0 ? (int)$data['ordem'] : null;
        if ($ordem === null) {
            $stmt = Database::getInstance()->prepare('SELECT COALESCE(MAX(ordem),0)+1 as prox FROM menu WHERE idempresa = :idempresa');
            $stmt->bindValue(':idempresa', $data['idempresa']);
            $stmt->execute();
            $ordem = (int)$stmt->fetchColumn();
        }

        // Verifica se a ordem já existe e reorganiza se necessário
        if ($ordem > 0) {
            $existingMenu = MenuModel::select()->where('idempresa', $data['idempresa'])->where('ordem', $ordem)->one();
            if (!empty($existingMenu)) {
                // Move todos os menus com ordem >= a nova ordem para frente
                $stmt = Database::getInstance()->prepare("UPDATE menu SET ordem = ordem + 1 WHERE idempresa = ? AND ordem >= ?");
                $stmt->execute([$data['idempresa'], $ordem]);
            }
        }

        // Insere o novo menu no banco de dados
        $id =  MenuModel::insert([
            'idempresa'            => $data['idempresa'],
            'descricao'            => $data['descricao'],
            'descricao_auxiliar'   => $data['descricao_auxiliar'],
            'status'               => $data['status'],
            'ordem'                => $ordem
        ])->execute();

        // Retorna o novo menu adicionado
        return self::getMenuById($data['idempresa'], $id);
    }

    /**
     * Edita um menu existente de uma determinada empresa
     * 
     * @param array $data Um array contendo os dados do menu a ser editado
     * @return array|null Um array com o menu editado ou null se não for possível editar o menu
     */
    public static function editMenu($data)
    {
        // Busca o menu atual
        $menu = MenuModel::select()
            ->where('idempresa', $data['idempresa'])
            ->where('idmenu', $data['idmenu'])
            ->one();

        if (empty($menu)) {
            throw new Exception('Menu não encontrado!');
        }

        $ordemAtual = (int)$menu['ordem'];
        $novaOrdem = isset($data['ordem']) ? (int)$data['ordem'] : $ordemAtual;

        try {
            Database::getInstance()->beginTransaction();

            $updateData = [
                'descricao'            => $data['descricao'],
                'descricao_auxiliar'   => $data['descricao_auxiliar'],
                'status'               => $data['status']
            ];

            // Se houve alteração de ordem e é válida, faz swap com o item que ocupa novaOrdem (se existir)
            if ($novaOrdem !== $ordemAtual && $novaOrdem > 0) {
                $other = MenuModel::select()
                    ->where('idempresa', $data['idempresa'])
                    ->where('ordem', $novaOrdem)
                    ->one();

                if (!empty($other)) {
                    // atribui a ordem atual ao outro item
                    MenuModel::update(['ordem' => $ordemAtual])
                        ->where('idempresa', $data['idempresa'])
                        ->where('idmenu', $other['idmenu'])
                        ->execute();
                }

                // atualiza o menu atual para a nova ordem (mesmo que não exista outro)
                $updateData['ordem'] = $novaOrdem;
            }

            MenuModel::update($updateData)
                ->where('idempresa', $data['idempresa'])
                ->where('idmenu', $data['idmenu'])
                ->execute();

            Database::getInstance()->commit();
            return self::getMenuById($data['idempresa'], $data['idmenu']);
        } catch (Exception $e) {
            Database::getInstance()->rollback();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Exclui um menu existente de uma determinada empresa
     * 
     * @param array $data Um array contendo os dados do menu a ser excluído
     * @return array Um array com uma mensagem informando que o menu foi excluído com sucesso
     */
    public static function deleteMenu($data)
    {
        // Exclui o menu do banco de dados
        MenuModel::delete()
            ->where('idempresa', $data['idempresa'])
            ->where('idmenu', $data['idmenu'])
            ->execute();

        // Retorna uma mensagem informando que o menu foi excluído com sucesso
        return ['message' => 'Menu excluído com sucesso'];
    }

    /**
     * Obtém todos os itens DOS menu
     * 
     * @param int $idempresa O ID da empresa
     * @return array|null Um array com os itens do menu ou null se não houver nenhum item registrado
     */
    public static function getProdutoMenu($idempresa)
    {
        $produtos = ProdutoMenuModel::getProdutosMenu($idempresa);
        return $produtos;
    }

    /**
     * ADD um novo item no menu
     */
    public static function vincularProdutoMenu($data)
    {
        try {
            db::getInstance()->beginTransaction();
            foreach ($data['produtos'] as $produto) {
                $idProduto = is_array($produto) ? $produto['idproduto'] : $produto;
                $ordem = is_array($produto) && isset($produto['ordem']) ? (int)$produto['ordem'] : null;
                $ordem = $ordem !== null && $ordem >= 1 ? $ordem : null;

                // Gera ordem automática se não foi informada (sequencial dentro do menu)
                if ($ordem === null) {
                    $stmt = Database::getInstance()->prepare('SELECT COALESCE(MAX(ordem),0)+1 as prox FROM produto_menu WHERE idempresa = :idempresa AND idmenu = :idmenu');
                    $stmt->bindValue(':idempresa', $data['idempresa']);
                    $stmt->bindValue(':idmenu', $data['idmenu']);
                    $stmt->execute();
                    $ordem = (int)$stmt->fetchColumn();
                }

                ProdutoMenuModel::insert([
                    'idempresa' => $data['idempresa'],
                    'idmenu'    => $data['idmenu'],
                    'idproduto' => $idProduto,
                    'ordem'     => $ordem
                ])->execute();
            }
            db::getInstance()->commit();
            return true;
        } catch (Exception $e) {
            db::getInstance()->rollback();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * DELETE um item do menu
     */
    public static function deleteProdutoMenu($data)
    {
        try {
            db::getInstance()->beginTransaction();
            ProdutoMenuModel::delete()->whereIn('idparmsmenu', $data['idparmsmenu'])->execute();
            db::getInstance()->commit();
            return true;
        } catch (Exception $e) {
            db::getInstance()->rollback();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Faz swap de ordem entre dois produtos de um menu
     */
    public static function swapProdutoMenuOrdem($idempresa, $idmenu, $idparmsmenu1, $novaOrdem)
    {
        try {
            db::getInstance()->beginTransaction();

            // Busca o produto atual
            $produto1 = ProdutoMenuModel::select()
                ->where('idempresa', $idempresa)
                ->where('idmenu', $idmenu)
                ->where('idparmsmenu', $idparmsmenu1)
                ->one();

            if (empty($produto1)) {
                throw new Exception('Produto não encontrado no menu!');
            }

            $ordemAtual = (int)$produto1['ordem'];
            
            // Se a nova ordem é igual a atual, não faz nada
            if ($ordemAtual === $novaOrdem) {
                db::getInstance()->commit();
                return true;
            }

            // Busca o produto que ocupa a nova ordem
            $produto2 = ProdutoMenuModel::select()
                ->where('idempresa', $idempresa)
                ->where('idmenu', $idmenu)
                ->where('ordem', $novaOrdem)
                ->one();

            if (!empty($produto2)) {
                // Faz swap: produto2 vai para a ordem atual do produto1
                ProdutoMenuModel::update(['ordem' => $ordemAtual])
                    ->where('idempresa', $idempresa)
                    ->where('idmenu', $idmenu)
                    ->where('idparmsmenu', $produto2['idparmsmenu'])
                    ->execute();
            }

            // Produto1 vai para a nova ordem
            ProdutoMenuModel::update(['ordem' => $novaOrdem])
                ->where('idempresa', $idempresa)
                ->where('idmenu', $idmenu)
                ->where('idparmsmenu', $idparmsmenu1)
                ->execute();

            db::getInstance()->commit();
            return true;
        } catch (Exception $e) {
            db::getInstance()->rollback();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Atualiza a ordenação dos menus
     */
    public static function ordenarMenus($idempresa, $menus)
    {
        try {
            db::getInstance()->beginTransaction();
            foreach ($menus as $menu) {
                $ordem = max(1, (int)$menu['ordem']);
                MenuModel::update(['ordem' => $ordem])
                    ->where('idempresa', $idempresa)
                    ->where('idmenu', $menu['idmenu'])
                    ->execute();
            }
            db::getInstance()->commit();
            return true;
        } catch (Exception $e) {
            db::getInstance()->rollback();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Atualiza a ordenação dos produtos dentro de um menu
     */
    public static function ordenarProdutosMenu($idempresa, $idmenu, $produtos)
    {
        try {
            db::getInstance()->beginTransaction();
            foreach ($produtos as $produto) {
                $ordem = max(1, (int)$produto['ordem']);
                ProdutoMenuModel::update(['ordem' => $ordem])
                    ->where('idparmsmenu', $produto['idparmsmenu'])
                    ->execute();
            }
            db::getInstance()->commit();
            return true;
        } catch (Exception $e) {
            db::getInstance()->rollback();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Retorna as ordens disponíveis para os menus de uma empresa
     * 
     * @param int $idempresa ID da empresa
     * @param int|null $excludeMenuId ID do menu a ser excluído da lista (para edição)
     * @return array Lista de ordens com descrição dos menus
     */
    public static function getMenuOrders($idempresa, $excludeMenuId = null)
    {
        $query = "SELECT ordem, descricao, idmenu FROM menu WHERE idempresa = ? AND status = 1";
        $params = [$idempresa];

        if ($excludeMenuId) {
            $query .= " AND idmenu != ?";
            $params[] = $excludeMenuId;
        }

        $query .= " ORDER BY ordem ASC";

        $stmt = Database::getInstance()->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
