<?php

/**
 * Classe CidadeCrontoller
 * Controlador de Cidade responsável por gerenciar operações relacionadas a Cidades.
 * 
 * @author João Silva
 * @since 23/05/2023
 */

namespace src\controllers;

use \core\Controller as ctrl;
use \src\handlers\Cidade as CidadeHelp;

class CidadeCrontoller extends ctrl
{
   
    /**
     * Retorna todos os cidades 
     * 
     * @param array $args Array contendo as cidades
     */
    public function getCidades($args)
    {
        $cidades = CidadeHelp::getCidades($args['filtro']);
        ctrl::response($cidades, 200);
    }

    /**
     * Retorna um cidade específico de uma empresa.
     * 
     * @param array $args Array contendo as cidade.
     */
    public function getCidadeById($args)
    {
        $cidades = CidadeHelp::getCidadesById($args['idcidade']);
        if (!$cidades) {
            ctrl::response('Cidade não encontrado', 404);
        }
        ctrl::response($cidades, 200);
    }

    /**
     * Retorna um cidade específico de uma empresa.
     * 
     * @param array $args Array contendo as cidade.
     */
    public function getCity($args){
        $cidades = CidadeHelp::getCity($args['idempresa']);
        if (!$cidades) {
            ctrl::response('Cidade não encontrado', 404);
        }
        ctrl::response($cidades, 200);
    }

}
