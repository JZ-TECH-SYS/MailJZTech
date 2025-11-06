<?php

namespace src\controllers;

use \core\Controller as ctrl;
use src\handlers\MesaReserva;

class MesaReservaController extends ctrl
{
    const ADDCAMPOS = ['idempresa', 'idmesa', 'expira_em'];
    const EDITCAMPOS = ['idreserva', 'idempresa'];

    /**
     * GET - Buscar todas as reservas
     * @route GET /getMesasReservas/{idempresa}/{status}
     */
    public function getMesasReservas($args)
    {
        try {
            $status = $args['status'] == '69' ? null : $args['status'];
            $reservas = MesaReserva::getReservas($args['idempresa'], $status);
            ctrl::response($reservas, 200);
        } catch (\Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * POST - Criar nova reserva
     * @route POST /addMesaReserva
     */
    public function addMesaReserva()
    {
        try {
            $data = ctrl::getBody();

            ctrl::verificarCamposVazios($data, self::ADDCAMPOS);

            $reserva = MesaReserva::criar($data);
            ctrl::response($reserva, 201);
        } catch (\Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * PUT - Editar reserva
     * @route PUT /editMesaReserva
     */
    public function editMesaReserva()
    {
        try {
            $data = ctrl::getBody();

            ctrl::verificarCamposVazios($data, self::EDITCAMPOS);

            $reserva = MesaReserva::editar($data);
            ctrl::response($reserva, 200);
        } catch (\Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * PUT - Cancelar reserva
     * @route PUT /cancelarMesaReserva/{idreserva}/{idempresa}
     */
    public function cancelarMesaReserva($args)
    {
        try {
            $reserva = MesaReserva::cancelar($args['idreserva'], $args['idempresa']);
            ctrl::response($reserva, 200);
        } catch (\Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * GET - Buscar reserva ativa por mesa (ROTA PÚBLICA)
     * @route GET /getReservaAtivaByMesa/{idempresa}/{idmesa}
     */
    public function getReservaAtivaByMesa($args)
    {
        try {
            $reserva = \src\models\Mesa_reserva::getReservaAtivaByMesa(
                $args['idempresa'],
                $args['idmesa']
            );

            ctrl::response($reserva, 200);
        } catch (\Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * DELETE - Deletar reserva
     * @route DELETE /deleteMesaReserva/{idreserva}/{idempresa}
     */
    public function deleteMesaReserva($args)
    {
        try {
            MesaReserva::deletar($args['idreserva'], $args['idempresa']);
            ctrl::response(['message' => 'Reserva excluída com sucesso'], 200);
        } catch (\Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
}
