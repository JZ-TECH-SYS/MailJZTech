<?php

namespace src\models;

use core\Database;
use \core\Model;

/**
 * Classe modelo para a tabela 'nota_fiscal' do banco de dados.
 */
class Nota_fiscal extends Model
{
    public static function getNotasPendentesEnvioContabilidade($idempres)
    {
        $data = Database::switchParams(['idempresa' => $idempres], 'NFE/getNotasPendentesEnvioContabilidade', true,true);
        $result = $data['retorno'] ?? [];
        if (empty($result)) return [];

        return $result;
    }
}
