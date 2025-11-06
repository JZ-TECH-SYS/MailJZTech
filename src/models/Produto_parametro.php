<?php

namespace src\models;

use core\Database;
use \core\Model;
use PDO;

/**
 * Classe modelo para a tabela 'produtos' do banco de dados.
 */
class Produto_parametro extends Model
{
    const descricaoParametro1 = 'definie se produto pode ter acrescimo';
    const descricaoAXParametro1 = 'parametro definie de produto pode ter acrescimo true sim false não';

    const descricaoParametro2 = 'definie se produto pode ter acrescimo gratis';
    const descricaoAXParametro2 = 'parametro definie de produto pode ter acrescimo gratis valor numerico';

    const descricaoParametro3 = 'definie se produto pode ter OBS no pedido';
    const descricaoAXParametro3 = 'parametro definie de produto pode ter OBS no pedido true sim false não';
}
