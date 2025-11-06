<?php

/**
 * Classe helper para gerenciar Cidades
 * 
 * Esta classe fornece métodos para gerenciar Cidades de um sistema.
 * 
 * Autor: Joaosn
 * Data de Início: 23/05/2023
 */

namespace src\handlers;

use src\models\Cidade as CidadeModel;
use src\models\Empresa;

class Cidade
{
    const RETORNO = [
         'cidade.id   as idcidade'
        ,'cidade.nome as nomecidade'
        ,'e.id        as idestado'
        ,'e.nome      as nomeestado'
        ,'e.uf        as uf'
        ,'e.ddd       as ddddoestado'
    ];
    /**
     * Obtém todos os cidades 
     * 
     * @return array|null Um array com os cidades do brasil veio 
     */
    public static function getCidades($filtro)
    {
        $cidade = CidadeModel::select(self::RETORNO)
            ->leftjoin('estado as e', 'e.id', '=', 'cidade.uf'); 
            if(is_string($filtro) && !is_numeric($filtro) && $filtro != 'todos'){
                $cidade->where('cidade.nome', 'like', '%'.$filtro.'%');
            }
            if(is_numeric($filtro)){
                $cidade->where('cidade.id', $filtro);
            }
        return $cidade->get();; 
    }

    /**
     * Obtém um menu específico de cidade empresa
     * 
     * @param int $idcidade O ID da idcidade
     * @return array|null Um array com o cidade especificado ou null se não existir
     */
    public static function getCidadesById($idcidade)
    {
        return CidadeModel::select(self::RETORNO)
            ->leftjoin('estado as e', 'e.id', '=', 'cidade.uf')
            ->where('cidade.id', $idcidade)
        ->one();

    }

    public static function getCity($idempresa) {
        $empresa = Empresa::select()->where('idempresa', $idempresa)->one();
        $idsCity = explode(',',$empresa['idcidade']);
        $cidade = CidadeModel::select(self::RETORNO)
            ->leftjoin('estado as e', 'e.id', '=', 'cidade.uf')
            ->whereIn('cidade.id', $idsCity )
        ->get();
        return $cidade;
    }

}
