<?php

namespace src\handlers;

use src\models\Cupon_extra as SeloModel;
use src\models\Pessoa;
use Exception;
use core\Database as db;

class SeloExtra
{
    /**
     * Lista selos extras de uma empresa com dados do cliente.
     */
    public static function getSelos($idempresa)
    {
        $sql = "SELECT s.*, p.nome, p.celular
                FROM cupon_extra s
                LEFT JOIN pessoa p ON p.idcliente = s.idcliente
                WHERE s.idempresa = :idempresa
                  AND s.utilizado = 0";
        $stmt = db::getInstance()->prepare($sql);
        $stmt->bindValue(':idempresa', $idempresa);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getSeloById($idempresa, $idextra)
    {
        $sql = "SELECT s.*, p.nome, p.celular
                FROM cupon_extra s
                LEFT JOIN pessoa p ON p.idcliente = s.idcliente
                WHERE s.idempresa = :idempresa AND s.idextra = :idextra";
        $stmt = db::getInstance()->prepare($sql);
        $stmt->bindValue(':idempresa', $idempresa);
        $stmt->bindValue(':idextra', $idextra);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public static function addSelo($data)
    {
        try {
            db::getInstance()->beginTransaction();
            $id = SeloModel::insert([
                'idempresa'    => $data['idempresa'],
                'idcliente'    => $data['idcliente']
            ])->execute();
            db::getInstance()->commit();
        } catch (Exception $e) {
            db::getInstance()->rollBack();
            throw new Exception('Erro ao cadastrar selo extra.');
        }

        return self::getSeloById($data['idempresa'], $id);
    }

    public static function editSelo($data)
    {
        $existente = SeloModel::select()
            ->where('idempresa', $data['idempresa'])
            ->where('idextra', $data['idextra'])
            ->one();
        if (!$existente) {
            throw new Exception('Selo não encontrado');
        }

        SeloModel::update([
            'descricao'    => $data['descricao'] ?? $existente['descricao'],
            'utilizado'    => $data['utilizado'] ?? $existente['utilizado']
        ])
            ->where('idempresa', $data['idempresa'])
            ->where('idextra', $data['idextra'])
            ->execute();

        return self::getSeloById($data['idempresa'], $data['idextra']);
    }

    public static function deleteSelo($data)
    {
        SeloModel::delete()
            ->where('idempresa', $data['idempresa'])
            ->where('idextra', $data['idextra'])
            ->execute();

        return ['message' => 'Selo excluído'];
    }
}
