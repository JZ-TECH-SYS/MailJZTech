<?php

/**
 * Model de Tipo de Despesa
 * @author: AI Assistant
 * @date: 2025-10-10
 */

namespace src\models;

use \core\Model;

class Tipo_despesa extends Model
{
    /**
     * Busca todos os tipos de despesa ativos de uma empresa
     * 
     * @param int $idempresa
     * @return array
     */
    public static function getAllByEmpresa($idempresa)
    {
        return self::select()
            ->where('idempresa', $idempresa)
            ->where('ativo', 1)
            ->orderBy('nome', 'ASC')
            ->get();
    }

    /**
     * Busca um tipo de despesa por ID
     * 
     * @param int $idtipo_despesa
     * @param int $idempresa
     * @return array|null
     */
    public static function getById($idtipo_despesa, $idempresa)
    {
        return self::select()
            ->where('idtipo_despesa', $idtipo_despesa)
            ->where('idempresa', $idempresa)
            ->one();
    }

    /**
     * Cria um novo tipo de despesa
     * 🔹 Gera automaticamente o próximo ID sequencial por empresa
     * 🔹 IDs começam em 100 (1-99 reservados para tipos padrão)
     * 
     * @param array $dados ['idempresa' => int, 'nome' => string]
     * @return int|false ID do tipo criado ou false em caso de erro
     */
    public static function criar($dados)
    {
        try {
            // Executar INSERT com geração automática de ID
            $params = [
                'idempresa' => $dados['idempresa'],
                'nome' => $dados['nome']
            ];
            
            $result = \core\Database::switchParams(
                $params,
                'tipo_despesa/criar_tipo_despesa',
                true,  // Executar
                true   // Logar
            );
            
            if ($result['error']) {
                error_log("Erro ao criar tipo_despesa: " . $result['error']);
                return false;
            }
            
            // Buscar o ID gerado
            $resultId = \core\Database::switchParams(
                $params,
                'tipo_despesa/get_ultimo_id',
                true,
                false  // Não precisa logar SELECT simples
            );
            
            if ($resultId['error'] || empty($resultId['retorno'])) {
                return false;
            }
            
            return (int)$resultId['retorno'][0]['idtipo_despesa'];
            
        } catch (\Exception $e) {
            error_log("Erro ao criar tipo_despesa: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Atualiza um tipo de despesa
     * 
     * @param int $idtipo_despesa
     * @param int $idempresa
     * @param array $dados
     * @return bool
     */
    public static function atualizar($idtipo_despesa, $idempresa, $dados)
    {
        return self::update($dados)
            ->where('idtipo_despesa', $idtipo_despesa)
            ->where('idempresa', $idempresa)
            ->execute();
    }

    /**
     * Desativa um tipo de despesa (soft delete)
     * 
     * @param int $idtipo_despesa
     * @param int $idempresa
     * @return bool
     */
    public static function desativar($idtipo_despesa, $idempresa)
    {
        return self::update(['ativo' => 0])
            ->where('idtipo_despesa', $idtipo_despesa)
            ->where('idempresa', $idempresa)
            ->execute();
    }

    /**
     * Verifica se existe um tipo de despesa com o mesmo nome na empresa
     * 
     * @param string $nome
     * @param int $idempresa
     * @param int|null $excluir_id Para excluir da busca (usado em edição)
     * @return bool
     */
    public static function existeNome($nome, $idempresa, $excluir_id = null)
    {
        $query = self::select(['idtipo_despesa'])
            ->where('nome', $nome)
            ->where('idempresa', $idempresa)
            ->where('ativo', 1);

        if ($excluir_id) {
            $query->where('idtipo_despesa', '!=', $excluir_id);
        }

        $result = $query->one();
        return !empty($result);
    }
}
