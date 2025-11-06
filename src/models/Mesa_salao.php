<?php
namespace src\models;

use \core\Model;

class Mesa_salao extends Model
{
    /**
     * Buscar todas as mesas do salão de uma empresa
     */
    public static function getMesas($idempresa)
    {
        return self::select()
            ->where('idempresa', $idempresa)
            ->orderBy('numero_mesa', 'ASC')
            ->get();
    }

    /**
     * Buscar mesa por ID
     */
    public static function getMesaById($idmesa, $idempresa)
    {
        return self::select()
            ->where('idmesa', $idmesa)
            ->where('idempresa', $idempresa)
            ->one();
    }

    /**
     * Verificar se já existe mesa com esse número
     */
    public static function mesaExiste($numero_mesa, $idempresa, $idmesa = null)
    {
        $query = self::select()
            ->where('numero_mesa', $numero_mesa)
            ->where('idempresa', $idempresa);

        if ($idmesa) {
            $query->where('idmesa', '!=', $idmesa);
        }

        return $query->one();
    }

    /**
     * Criar nova mesa
     */
    public static function criarMesa($dados)
    {
        return self::insert($dados)->execute();
    }

    /**
     * Editar mesa
     */
    public static function editarMesa($idmesa, $dados)
    {
        return self::update($dados)
            ->where('idmesa', $idmesa)
            ->execute();
    }

    /**
     * Deletar mesa (apenas se não tiver reservas ativas)
     */
    public static function deletarMesa($idmesa)
    {
        return self::update(['ativo' => 0])
            ->where('idmesa', $idmesa)
            ->execute();
    }

    /**
     * Buscar mesas ativas (para autocomplete)
     */
    public static function getMesasAtivas($idempresa)
    {
        return self::select()
            ->where('idempresa', $idempresa)
            ->where('ativo', 1)
            ->orderBy('numero_mesa', 'ASC')
            ->get();
    }
}
