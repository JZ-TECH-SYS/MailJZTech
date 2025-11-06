<?php

namespace src\handlers;

use src\models\Cupon as CuponModel;
use Exception;
use core\Database as db;

class Cupon
{
    const RETORNO = [
        'cupon.idcupon',
        'cupon.idempresa',
        'cupon.celular',
        'cupon.valor',
        'cupon.descricao',
        'cupon.data_criacao',
        'cupon.data_exclusao',
        'cupon.idusuario',
        'cupon.idcuponpedidos',
        'cupon.data_uso',
        'p.nome as usuario_nome'
    ];

    public static function getCuponByTelefone($numero,$idempresa){
        $cupom = CuponModel::getCuponsDetalheTelefone($numero,$idempresa);
        return $cupom;  
    }

    /**
     * Condição base para as consultas.
     */
    public static function condicao()
    {
        return CuponModel::select(self::RETORNO)
            ->leftJoin('users as p', 'p.iduser', '=', 'cupon.idusuario');
    }

    /**
     * Busca todos os cupons de uma empresa.
     *
     * @param int $idempresa O ID da empresa.
     * @return array Lista de cupons associados à empresa.
     */
    public static function getCupons($idempresa)
    {
        $sql = "
            SELECT c.*, p.nome as usuario_nome,
                   COUNT(cp.idpedidovenda) AS qtd_pedidos
            FROM cupon c
            LEFT JOIN users p ON p.iduser = c.idusuario
            LEFT JOIN cupon_pedidos cp ON cp.idcupon = c.idcupon AND cp.idempresa = c.idempresa
            WHERE c.idempresa = :idempresa
            GROUP BY c.idcupon
        ";
        $db = db::getInstance()->prepare($sql);
        $db->bindValue(':idempresa', $idempresa);
        $db->execute();
        return $db->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Busca um cupom específico de uma empresa.
     *
     * @param int $idempresa O ID da empresa.
     * @param int $idcupon O ID do cupom.
     * @return array|bool Dados do cupom encontrado ou false se não encontrado.
     */
    public static function getCuponById($idempresa, $idcupon)
    {
        $cupom = self::condicao()
            ->where('cupon.idempresa', $idempresa)
            ->where('cupon.idcupon', $idcupon)
            ->one();
        return $cupom;
    }

    /**
     * Adiciona um novo cupom.
     *
     * @param array $data Dados do cupom a ser adicionado.
     * @return array Dados do cupom recém-criado.
     */
    public static function addCupon($data)
    {
        try {
            db::getInstance()->beginTransaction();
            $dadosCupon = [
                'idempresa'     => $data['idempresa'],
                'descricao'     => $data['descricao'] ?? null,
                'celular'       => $data['celular'] ?? null,
                'valor'         => $data['valor'],
                'idusuario'     => $data['idusuario'],
                'data_criacao'  => date('Y-m-d H:i:s')
            ];
            $id = CuponModel::insert($dadosCupon)->execute();
            db::getInstance()->commit();
        } catch (Exception $e) {
            db::getInstance()->rollBack();
            throw new Exception('Erro ao cadastrar cupom. Contate o administrador do sistema!');
        }

        return self::getCuponById($data['idempresa'], $id);
    }

    /**
     * Edita um cupom existente.
     *
     * @param array $data Dados atualizados do cupom.
     * @return array Dados do cupom atualizado.
     */
    public static function editCupon($data)
    {
        $cupomExistente = CuponModel::select()
            ->where('idempresa', $data['idempresa'])
            ->where('idcupon', $data['idcupon'])
            ->one();

        if (empty($cupomExistente)) {
            throw new Exception('Cupom não encontrado');
        }

        $dadosCupon = [
            'celular'       => $data['celular'] ?? $cupomExistente['celular'],
            'descricao'     => $data['descricao'] ?? $cupomExistente['descricao'],
            'valor'         => $data['valor']   ?? $cupomExistente['valor'],
            'idusuario'     => $data['idusuario'] ?? $cupomExistente['idusuario'],
            'data_exclusao' => $data['data_exclusao'] ?? $cupomExistente['data_exclusao'],
            'data_uso'      => $data['data_uso'] ?? $cupomExistente['data_uso']
        ];

        try {
            db::getInstance()->beginTransaction();
            CuponModel::update($dadosCupon)
                ->where('idempresa', $data['idempresa'])
                ->where('idcupon', $data['idcupon'])
                ->execute();
            db::getInstance()->commit();
        } catch (Exception $e) {
            db::getInstance()->rollBack();
            throw new Exception('Erro ao atualizar cupom. Contate o administrador do sistema!');
        }

        return self::getCuponById($data['idempresa'], $data['idcupon']);
    }

    /**
     * Exclui um cupom.
     *
     * @param array $data Dados do cupom a ser excluído.
     * @return array Mensagem de sucesso.
     */
    public static function deleteCupon($data)
    {
        try {
            CuponModel::delete()
                ->where('idempresa', $data['idempresa'])
                ->where('idcupon', $data['idcupon'])
                ->execute();
        } catch (Exception $e) {
            throw new Exception('Erro ao excluir cupom. Contate o administrador do sistema!');
        }

        return ['message' => 'Cupom excluído com sucesso'];
    }

    public static function getClientesEleitos($idempresa,$quantidade_pedidos,$valor_minimo){
        $clientes = CuponModel::getClientesEleitos($idempresa,$quantidade_pedidos,$valor_minimo);
        return $clientes;
    }
}
