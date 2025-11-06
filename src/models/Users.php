<?php

namespace src\models;

use core\Database;
use \core\Model;
use PDO;

/**
 * Classe modelo para a tabela 'users' do banco de dados.
 *
 * @author Seu Nome
 * @date 30-03-2023
 */
class Users extends Model
{
    public $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Busca informações do usuário com base no token fornecido.
     *
     * @param string $token O token de autenticação do usuário
     * @return array Retorna um array associativo contendo as informações do usuário
     */
    public function getUserToken($token)
    {
        $sql = "
            select 
                 u.*
                ,e.tipo_estabelecimento
                ,e.nome    as nome_empresa
                ,e.cnpj    as cnpj_empresa
                ,ep.valor  as maximo_contas
                ,ep2.valor as controlar_estoque
                ,ep12.valor as usa_myzap
                ,ep13.valor as usa_nfe
            from users u
                left join empresa e on e.idempresa = u.idempresa
                left join empresa_parametro ep on ep.idempresa = e.idempresa and ep.idparametro = 1 /*maximo de user cadatrados*/
                left join empresa_parametro ep2 on ep2.idempresa = e.idempresa and ep2.idparametro = 2 /*controlar estoque*/
                LEFT JOIN empresa_parametro as ep12 ON ep12.idempresa = e.idempresa AND ep12.idparametro = 12 /*utiliza myzap boolean*/
                LEFT JOIN empresa_parametro as ep13 ON ep13.idempresa = e.idempresa AND ep13.idparametro = 13 /*usa nfe boolean*/
            where u.token = :token 
        ";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':token', $token);
        $sql->execute();
        $res = $sql->fetch(PDO::FETCH_ASSOC);
        $res['controlar_estoque'] = $res['controlar_estoque'] == 'true' ? true : false ;
        $res['usa_myzap'] = $res['usa_myzap'] == 'true' ? true : false ;
        $res['usa_nfe'] = $res['usa_nfe'] == 'true' ? true : false ;
        return $res;
    }

    /**
     * Busca informações do usuário com base no nome fornecido.
     *
     * @param string $nome O nome de usuário
     * @return array Retorna um array associativo contendo as informações do usuário
     */
    public function getUserName($nome)
    {
        $sql = "
            select 
                 u.*
                ,e.nome             as nome_empresa
                ,e.nomefantasia    as nome_fantasia_empresa
                ,e.cnpj    as cnpj_empresa
                ,ep.valor  as maximo_contas
                ,ep2.valor as controlar_estoque 
                ,e.tipo_estabelecimento
                ,ep12.valor as usa_myzap
                ,ep13.valor as usa_nfe
            from users u
                left join empresa e on e.idempresa = u.idempresa
                left join empresa_parametro ep on ep.idempresa = e.idempresa and ep.idparametro = 1 /*maximo de user cadatrados*/
                left join empresa_parametro ep2 on ep2.idempresa = e.idempresa and ep2.idparametro = 2 /*controlar estoque*/
                LEFT JOIN empresa_parametro as ep12 ON ep12.idempresa = e.idempresa AND ep12.idparametro = 12 /*utiliza myzap boolean*/
                LEFT JOIN empresa_parametro as ep13 ON ep13.idempresa = e.idempresa AND ep13.idparametro = 13 /*usa nfe boolean*/
            where u.nome = :nome 
        ";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':nome', $nome);
        $sql->execute();
        $res = $sql->fetch(PDO::FETCH_ASSOC);
        $res['controlar_estoque'] = $res['controlar_estoque'] == 'true' ? true : false ;
        $res['usa_myzap'] = $res['usa_myzap'] == 'true' ? true : false ;
        $res['usa_nfe'] = $res['usa_nfe'] == 'true' ? true : false ;
        return $res;
    }

    /**
     * Atualiza o token de autenticação para um usuário específico com base no nome fornecido.
     *
     * @param string $token O novo token de autenticação
     * @param string $nome O nome de usuário do usuário cujo token será atualizado
     */
    public function saveToken($token, $nome)
    {
        $sql = "UPDATE users SET token = :token WHERE nome = :nome";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':token', $token);
        $sql->bindValue(':nome', $nome);
        $sql->execute();
    }
}
