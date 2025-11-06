<?php

/**
 * Classe responsável por gerenciar pagamentos no sistema.
 * 
 * Autor: Joaosn
 * Data de Início: 23/05/2023
 */

namespace src\handlers;

use src\models\Pagamentos as PagamentoModel;
use src\models\Tipo_pagamento as MeiosPagamentosModel;
use \core\Controller as ctrl;
use Exception;

class Pagamentos
{
    /**
     * Retorna uma lista de pagamentos de um determinado pedido de venda.
     * 
     * @param int $idempresa ID da empresa do pagamento.
     * @param int $idpedidovenda ID do pedido de venda do pagamento.
     * @return array Uma lista de pagamentos correspondentes ao pedido de venda fornecido.
     */
    public static function getPagamentos($idempresa, $idpedidovenda)
    {
        $pagamentos = PagamentoModel::select()
            ->where('idempresa', $idempresa)
            ->where('idpedidovenda', $idpedidovenda)
            ->get();
        
        // **PROCESSAR ITENS PAGOS NA MESMA CONSULTA** (sem fazer consulta dupla!)
        $itensPagos = [];
        
        foreach ($pagamentos as $pagamento) {
            if (!empty($pagamento['itens_detalhes'])) {
                $detalhes = json_decode($pagamento['itens_detalhes'], true);
                if (is_array($detalhes)) {
                    foreach ($detalhes as $idItem => $quantidade) {
                        $itensPagos[$idItem] = ($itensPagos[$idItem] ?? 0) + $quantidade;
                    }
                }
            }
        }
        
        return [
            'pagamentos' => $pagamentos,
            'itens_pagos' => $itensPagos
        ];
    }

    /**
     * Retorna um pagamento específico com base em seu ID.
     * 
     * @param int $idempresa ID da empresa do pagamento.
     * @param int $idpedidovenda ID do pedido de venda do pagamento.
     * @param int $idpagamento ID do pagamento.
     * @return object|null Um objeto representando o pagamento ou nulo se o pagamento não for encontrado.
     */
    public static function getPagamentosById($idempresa, $idpedidovenda, $idpagamento)
    {
        $pagamentos = PagamentoModel::select()
            ->where('idempresa', $idempresa)
            ->where('idpedidovenda', $idpedidovenda)
            ->where('idpagamento', $idpagamento)
            ->one();
        return $pagamentos;
    }

    /**
     * Adiciona um novo pagamento ao sistema.
     * 
     * @param array $data Um array contendo os dados do pagamento a ser adicionado.
     * @return object|null Um objeto representando o novo pagamento adicionado ou nulo se houver um erro.
     */
    public static function addPagamento($data)
    {
        // Preparar dados para inserção
        $insertData = [
            'idpedidovenda'   => $data['idpedidovenda'],
            'idtipopagamento' => $data['idtipopagamento'],
            'idempresa'       => $data['idempresa'],
            'valor'           => $data['valor'],
            'cAut'            => $data['cAut'] ?? null,
        ];

        // Adicionar itens_detalhes se fornecido (JSON com quantidades por item)
        if (isset($data['itens_detalhes']) && !empty($data['itens_detalhes'])) {
            $insertData['itens_detalhes'] = json_encode($data['itens_detalhes']);
        }

        $id = PagamentoModel::insert($insertData)->execute();

        return self::getPagamentosById($data['idempresa'], $data['idpedidovenda'], $id);
    }

    /**
     * Edita um pagamento existente no sistema.
     * 
     * @param array $data Um array contendo os dados do pagamento a ser editado.
     * @return object|null Um objeto representando o pagamento editado ou nulo se houver um erro.
     */
    public static function editPagamento($data)
    {
        $isPagamento = self::validadePagamento($data);
        PagamentoModel::update([
            'valor'           => $data['valor']           ?? $isPagamento['valor'],
            'idtipopagamento' => $data['idtipopagamento'] ?? $isPagamento['idtipopagamento']
        ])
            ->where('idempresa', $data['idempresa'])
            ->where('idpagamento', $data['idpagamento'])
            ->where('idpedidovenda', $data['idpedidovenda'])
            ->execute();
        return self::getPagamentosById($data['idempresa'], $data['idpedidovenda'], $data['idpagamento']);
    }

    /**
     * Exclui um pagamento existente no sistema.
     * 
     * @param array $data Um array contendo os dados do pagamento a ser excluído.
     * @return array Um array com uma mensagem de sucesso ou uma mensagem de erro, se houver.
     */
    public static function deletePagamento($data)
    {
        self::validadePagamento($data);
        PagamentoModel::delete()
            ->where('idempresa', $data['idempresa'])
            ->where('idpagamento', $data['idpagamento'])
            ->where('idpedidovenda', $data['idpedidovenda'])
            ->execute();
        return ['message' => 'Pagamento excluído com sucesso'];
    }

    /**
     * Exclui TODOS os pagamentos de um pedido (RESTAURAR TUDO).
     * 
     * @param array $data Um array contendo idempresa e idpedidovenda.
     * @return array Um array com uma mensagem de sucesso.
     */
    public static function deleteAllPagamentos($data)
    {
        PagamentoModel::delete()
            ->where('idempresa', $data['idempresa'])
            ->where('idpedidovenda', $data['idpedidovenda'])
            ->execute();
        return ['message' => 'Todos os pagamentos foram removidos com sucesso'];
    }

    /**
     * Verifica a validade de um pagamento existente no sistema.
     * 
     * @param array $data Um array contendo os dados do pagamento a ser verificado.
     * @return object|null Um objeto representando o pagamento ou nulo se o pagamento não for encontrado.
     * @throws Exception Uma exceção é lançada se o pagamento não for encontrado.
     */
    private static function validadePagamento($data)
    {
        $isPagamento = self::getPagamentosById($data['idempresa'], $data['idpedidovenda'], $data['idpagamento']);
        if (empty($isPagamento)) {
            throw new Exception('Pagamento não encontrado');
        }
        return $isPagamento;
    }

    /**
     * Recupera os meios de pagamento ativos de uma empresa.
     *
     * Esta função estática busca os meios de pagamento ativos (status = 1)
     * associados a uma determinada empresa, utilizando o ID da empresa como
     * parâmetro. Os meios de pagamento inativos (status = 2) são ignorados.
     * O resultado é retornado como um array associativo com os registros dos meios de pagamento.
     *
     * @param int $idempresa O identificador da empresa para a qual os meios de pagamento ativos devem ser buscados.
     * @return array Um array associativo contendo os registros dos meios de pagamento ativos da empresa.
     */
    public static function getMeiosPagamentos($idempresa,$web = false)
    {
        if($web){
            $meiosPagamentos = MeiosPagamentosModel::select()
                ->where('idempresa', $idempresa)
                ->where('status', 1) // 1 = Ativo 2 = Inativo
                ->where('web', 1) // 1 = mostra 2 = nao mostra              
                ->get();
        }else{
            $meiosPagamentos = MeiosPagamentosModel::select()
                ->where('idempresa', $idempresa)
                ->where('status', 1) // 1 = Ativo 2 = Inativo
                ->get();
        }
        return $meiosPagamentos;
    }

    public static function updateCAut($data)
    {
        try 
        {
            PagamentoModel::update([
              'cAut'          => $data['cAut']
              ])
              ->where('idempresa', $data['idempresa'])
              ->where('idpagamento', $data['idpagamento'])
              ->where('idpedidovenda', $data['idpedidovenda'])
              ->execute();
        } catch (Exception $e) {
            throw new Exception('Erro ao atualizar cAut: ' . $e->getMessage());
        }
    }
}
