<?php

namespace src\handlers;

use src\models\Cupon_regra as CuponRegraModel;
use Exception;
use core\Database as db;

class CuponRegra
{
    /**
     * Retorna todas as regras de cupons.
     *
     * @return array Lista de regras de cupons.
     */
    public static function getCuponRegras()
    {
        $regras = CuponRegraModel::select()->execute();
        return $regras;
    }

    /**
     * Retorna uma regra de cupom específica.
     *
     * @param int $idcuponregra O ID da regra de cupom.
     * @return array|bool Dados da regra de cupom ou false se não encontrada.
     */
    public static function getCuponRegraById($idcuponregra)
    {
        $regra = CuponRegraModel::select()
            ->where('idcuponregra', $idcuponregra)
            ->one();
        return $regra;
    }

    /**
     * Adiciona uma nova regra de cupom.
     *
     * @param array $data Dados da regra de cupom a ser adicionada.
     * @return array Dados da regra recém-criada.
     */
    public static function addCuponRegra($data)
    {
        try {
            db::getInstance()->beginTransaction();
            $dadosRegra = [
                'valor'                => $data['valor'],
                'descricao'            => $data['descricao'],
                'status'               => $data['status'] ?? 1,
                'quantidade_pedidos'   => $data['quantidade_pedidos'],
                'data_criacao'         => date('Y-m-d H:i:s')
            ];
            $id = CuponRegraModel::insert($dadosRegra)->execute();
            db::getInstance()->commit();
        } catch (Exception $e) {
            db::getInstance()->rollBack();
            throw new Exception('Erro ao cadastrar regra de cupom. Contate o administrador do sistema!');
        }

        return self::getCuponRegraById($id);
    }

    /**
     * Edita uma regra de cupom existente.
     *
     * @param array $data Dados atualizados da regra de cupom.
     * @return array Dados da regra de cupom atualizada.
     */
    public static function editCuponRegra($data)
    {
        $regraExistente = CuponRegraModel::select()
            ->where('idcuponregra', $data['idcuponregra'])
            ->one();

        if (empty($regraExistente)) {
            throw new Exception('Regra de cupom não encontrada');
        }

        $dadosRegra = [
            'valor'                => $data['valor']              ?? $regraExistente['valor'],
            'descricao'            => $data['descricao']          ?? $regraExistente['descricao'],
            'status'               => $data['status']             ?? $regraExistente['status'],
            'quantidade_pedidos'   => $data['quantidade_pedidos'] ?? $regraExistente['quantidade_pedidos']
        ];

        try {
            db::getInstance()->beginTransaction();
            CuponRegraModel::update($dadosRegra)
                ->where('idcuponregra', $data['idcuponregra'])
                ->execute();
            db::getInstance()->commit();
        } catch (Exception $e) {
            db::getInstance()->rollBack();
            throw new Exception('Erro ao atualizar regra de cupom. Contate o administrador do sistema!');
        }

        return self::getCuponRegraById($data['idcuponregra']);
    }

    /**
     * Exclui uma regra de cupom.
     *
     * @param array $data Dados da regra de cupom a ser excluída.
     * @return array Mensagem de sucesso.
     */
    public static function deleteCuponRegra($data)
    {
        try {
            CuponRegraModel::delete()
                ->where('idcuponregra', $data['idcuponregra'])
                ->execute();
        } catch (Exception $e) {
            throw new Exception('Erro ao excluir regra de cupom. Contate o administrador do sistema!');
        }

        return ['message' => 'Regra de cupom excluída com sucesso'];
    }
}
