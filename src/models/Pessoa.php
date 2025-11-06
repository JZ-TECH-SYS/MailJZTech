<?php

namespace src\models;

use core\Database;
use \core\Model;
use PDO;

/**
 * Classe modelo para a tabela 'Pessoa' do banco de dados.
 */
class Pessoa extends Model
{
    public static function isCpf($data){

        $query = "
            SELECT *
            FROM pessoa
            WHERE
                idempresa = ? AND
                (cpf = ? OR celular = ?)
            LIMIT 1
        ";

        $sql = Database::getInstance()->prepare($query);
        $sql->bindValue(1, $data['idempresa'] ?? null);
        $sql->bindValue(2, $data['cpf'] ?? null);
        $sql->bindValue(3, $data['celular'] ?? null);
        $sql->execute();
        $result = $sql->fetch(PDO::FETCH_ASSOC);
        return $result;
    }

    public static function getInfosNFE($idcliente, $idempresa)
    {
        $dados = Database::switchParams(['idpessoa' => $idcliente, 'idempresa' => $idempresa], 'NFE/getInfosNFEpessoa', true, false);
        return $dados['retorno'][0] ?? [];
    }
}
