<?php
namespace src\models;

use \core\Model;
use core\Database;

class Mesa_reserva extends Model
{
    /**
     * Buscar todas as reservas de uma empresa
     */
    public static function getReservas($idempresa, $status = null)
    {
        $params = [
            'idempresa' => $idempresa,
            'status' => $status ?? 69  // 69 = listar todas
        ];

        // Query complexa com JOINs - usa switchParams
        $result = Database::switchParams(
            $params,
            'mesa/getReservas',
            true,
            true
        );

        return $result['retorno'];
    }

    /**
     * Buscar reserva por ID
     */
    public static function getReservaById($idreserva, $idempresa)
    {
        $params = [
            'idreserva' => $idreserva,
            'idempresa' => $idempresa
        ];

        $result = Database::switchParams(
            $params,
            'mesa/getReservaById',
            true,
            true
        );

        return $result['retorno'][0] ?? null;
    }

    /**
     * Verificar se mesa já tem reserva ativa
     */
    public static function mesaTemReservaAtiva($idmesa, $idempresa, $idreserva = null)
    {
        $query = self::select()
            ->where('idmesa', $idmesa)
            ->where('idempresa', $idempresa)
            ->where('status', 1); // status ativo

        if ($idreserva) {
            $query->where('idreserva', '!=', $idreserva);
        }

        return $query->one();
    }

    /**
     * Buscar reserva ativa por mesa (para uso público)
     */
    public static function getReservaAtivaByMesa($idempresa, $numero_mesa)
    {
        $params = [
            'idempresa' => $idempresa,
            'numero_mesa' => $numero_mesa
        ];

        $result = Database::switchParams(
            $params,
            'mesa/getReservaAtivaByMesa',
            true,
            true
        );

        return $result['retorno'][0] ?? null;
    }

    /**
     * Criar nova reserva
     */
    public static function criarReserva($dados)
    {
        return self::insert($dados)->execute();
    }

    /**
     * Editar reserva
     */
    public static function editarReserva($idreserva, $dados)
    {
        return self::update($dados)
            ->where('idreserva', $idreserva)
            ->execute();
    }

    /**
     * Deletar reserva
     */
    public static function deletarReserva($idreserva)
    {
        return self::update(['status' => 3])
            ->where('idreserva', $idreserva)
            ->execute();
    }

    /**
     * Cancelar reserva (atualiza status)
     */
    public static function cancelarReserva($idreserva)
    {
        return self::update(['status' => 3]) // 3 = cancelado
            ->where('idreserva', $idreserva)
            ->execute();
    }
}
