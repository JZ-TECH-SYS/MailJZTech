<?php

/**
 * Classe helper para gerenciar Despesas no sistema
 * 
 * Esta classe fornece métodos para gerenciar Despesas de um sistema.
 * 
 * Autor: Joaosn
 * Data de Início: 23/05/2023
 */

namespace src\handlers;

use core\Database;
use src\models\Despesas as DespesasModel;
use src\models\Produto_Despesas as ProdutoDespesasModel;
use core\Database as db;
use Exception;

class Despesas
{
    /**
     * Obtém todos os Despesas de uma determinada empresa
     * 
     * @param int $idempresa O ID da empresa
     * @return array|null Um array com os Despesas da empresa ou null se não houver nenhum Despesas registrado
     */
    public static function getDespesas($idempresa)
    {
        $Despesas = DespesasModel::select()->where('idempresa', $idempresa)->execute();
        return $Despesas;
    }

    /**
     * Obtém despesas de uma empresa por período
     * 
     * @param int $idempresa O ID da empresa
     * @param string $dataini Data inicial (YYYY-MM-DD)
     * @param string $datafim Data final (YYYY-MM-DD)
     * @return array Um array com as despesas do período
     */
    public static function getDespesasPorPeriodo($idempresa, $dataini, $datafim)
    {
        $query = "
            SELECT 
                d.iddespesas,
                d.idempresa,
                d.numeronota,
                d.chavesefaz,
                d.documento,
                d.datahora,
                d.tipo_despesa,
                d.valor,
                d.descricao,
                DATE(d.datahora) as dia
            FROM despesas d
            WHERE d.idempresa = :idempresa 
                AND DATE(d.datahora) BETWEEN :dataini AND :datafim
            ORDER BY d.datahora ASC
        ";
        
        $db = Database::getInstance();
        $stmt = $db->prepare($query);
        $stmt->bindParam(':idempresa', $idempresa, \PDO::PARAM_INT);
        $stmt->bindParam(':dataini', $dataini, \PDO::PARAM_STR);
        $stmt->bindParam(':datafim', $datafim, \PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtém despesas agrupadas por categoria e período para relatório financeiro
     * 
     * @param int $idempresa O ID da empresa
     * @param string $dataini Data inicial (YYYY-MM-DD)
     * @param string $datafim Data final (YYYY-MM-DD)
     * @return array Um array com as despesas agrupadas por categoria
     */
    public static function getDespesasPorCategoria($idempresa, $dataini, $datafim)
    {
        $query = "
            SELECT 
                d.tipo_despesa,
                SUM(d.valor) as total_valor,
                COUNT(*) as quantidade
            FROM despesas d
            WHERE d.idempresa = :idempresa 
                AND DATE(d.datahora) BETWEEN :dataini AND :datafim
                AND d.valor IS NOT NULL
            GROUP BY d.tipo_despesa
            ORDER BY total_valor DESC
        ";
        
        $db = Database::getInstance();
        $stmt = $db->prepare($query);
        $stmt->bindParam(':idempresa', $idempresa, \PDO::PARAM_INT);
        $stmt->bindParam(':dataini', $dataini, \PDO::PARAM_STR);
        $stmt->bindParam(':datafim', $datafim, \PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtém despesas diárias agrupadas por dia para relatório financeiro
     * 
     * @param int $idempresa O ID da empresa
     * @param string $dataini Data inicial (YYYY-MM-DD)
     * @param string $datafim Data final (YYYY-MM-DD)
     * @return array Um array com as despesas agrupadas por dia
     */
    public static function getDespesasDiarias($idempresa, $dataini, $datafim)
    {
        $query = "
            SELECT 
                DATE(d.datahora) as dia,
                SUM(d.valor) as total_despesas,
                COUNT(*) as quantidade_despesas
            FROM despesas d
            WHERE d.idempresa = :idempresa 
                AND DATE(d.datahora) BETWEEN :dataini AND :datafim
                AND d.valor IS NOT NULL
            GROUP BY DATE(d.datahora)
            ORDER BY dia ASC
        ";
        
        $db = Database::getInstance();
        $stmt = $db->prepare($query);
        $stmt->bindParam(':idempresa', $idempresa, \PDO::PARAM_INT);
        $stmt->bindParam(':dataini', $dataini, \PDO::PARAM_STR);
        $stmt->bindParam(':datafim', $datafim, \PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtém um Despesas específico de uma empresa
     * 
     * @param int $idempresa O ID da empresa
     * @param int $iddespesas O ID do Despesas
     * @return array|null Um array com o Despesas especificado ou null se o Despesas não existir
     */
    public static function getDespesasById($idempresa, $iddespesas)
    {
        $Despesas = DespesasModel::select()->where('idempresa', $idempresa)->where('iddespesas', $iddespesas)->one();
        return $Despesas;
    }

    /**
     * Adiciona um novo Despesas para uma determinada empresa
     * 
     * @param array $data Um array contendo os dados do novo Despesas
     * @return array|null Um array com o novo Despesas adicionado ou null se não for possível adicionar o Despesas
     */
    public static function addDespesas($data)
    {
        // Verifica se já existe um Despesas
        $isdescricao = DespesasModel::select()->where('idempresa', $data['idempresa'])->where('chavesefaz', $data['chavesefaz'])->one();
        if (!empty($isdescricao)) {
            throw new Exception('chavesefaz já cadastrada!');
        }

        // Insere o novo Despesas no banco de dados
        $id =  DespesasModel::insert([
            'idempresa'   =>  $data['idempresa'] 
           ,'numeronota'  =>  $data['numeronota'] ?? null
           ,'chavesefaz'  =>  $data['chavesefaz'] ?? null
           ,'documento'   =>  $data['documento']  ?? null
           ,'datahora'    =>  $data['datahora']   ?? null
           ,'tipo_despesa'=>  $data['tipo_despesa'] ?? null
           ,'valor'       =>  $data['valor']  ?? null
           ,'descricao'   =>  $data['descricao']  ?? null
           
        ])->execute();

        // Retorna o novo Despesas adicionado
        return self::getDespesasById($data['idempresa'], $id);
    }

    /**
     * Edita um Despesas existente de uma determinada empresa
     * 
     * @param array $data Um array contendo os dados do Despesas a ser editado
     * @return array|null Um array com o Despesas editado ou null se não for possível editar o Despesas
     */
    public static function editDespesas($data)
    {
        // Verifica se já existe um Despesas com a mesma descrição para a empresa (caso a descrição tenha sido alterada)
        $Despesas = DespesasModel::select()->where('idempresa', $data['idempresa'])->where('iddespesas', $data['iddespesas'])->one();
        if(!isset($Despesas) || empty($Despesas)){
            throw new Exception('Despesas não encontrado');
        }
      
        // Atualiza o Despesas no banco de dados
        DespesasModel::update([
             'numeronota'  =>  $data['numeronota']   ?? $Despesas['numeronota']
            ,'chavesefaz'  =>  $data['chavesefaz']   ?? $Despesas['chavesefaz']
            ,'documento'   =>  $data['documento']    ?? $Despesas['documento']
            ,'datahora'    =>  $data['datahora']     ?? $Despesas['datahora']
            ,'tipo_despesa'=>  $data['tipo_despesa'] ?? $Despesas['tipo_despesa']
            ,'valor'       =>  $data['valor']        ?? $Despesas['valor']
            ,'descricao'   =>  $data['descricao']    ?? $Despesas['descricao']
        ])
        ->where('idempresa', $data['idempresa'])
        ->where('iddespesas', $data['iddespesas'])
        ->execute();

        // Retorna o Despesas editado
        return self::getDespesasById($data['idempresa'], $data['iddespesas']);
    }

    /**
     * Exclui um Despesas existente de uma determinada empresa
     * 
     * @param array $data Um array contendo os dados do Despesas a ser excluído
     * @return array Um array com uma mensagem informando que o Despesas foi excluído com sucesso
     */
    public static function deleteDespesas($data)
    {
        // Exclui o Despesas do banco de dados
        DespesasModel::delete()
            ->where('idempresa', $data['idempresa'])
            ->where('iddespesas', $data['iddespesas'])
            ->execute();

        // Retorna uma mensagem informando que o Despesas foi excluído com sucesso
        return ['message' => 'Despesas excluído com sucesso'];
    }
}
