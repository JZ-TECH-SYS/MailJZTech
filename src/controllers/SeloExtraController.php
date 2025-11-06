<?php

namespace src\controllers;

use \core\Controller as ctrl;
use \src\handlers\SeloExtra as SeloHandler;
use \src\models\Cupon;
use Exception;

class SeloExtraController extends ctrl
{
    const ADD_FIELDS = ['idcliente'];

    public function index()
    {
        $idempresa = ctrl::getEmpresa();
        $selos = SeloHandler::getSelos($idempresa);
        ctrl::response($selos, 200);
    }

    public function store()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::ADD_FIELDS);
            $data['idempresa'] = ctrl::getEmpresa();
            $selo = SeloHandler::addSelo($data);
            ctrl::response($selo, 201);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    public function update($args)
    {
        try {
            $data = ctrl::getBody();
            $data['idempresa'] = ctrl::getEmpresa();
            $data['idextra'] = $args['id'];
            $selo = SeloHandler::editSelo($data);
            ctrl::response($selo, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    public function destroy($args)
    {
        try {
            $data = [
                'idempresa' => ctrl::getEmpresa(),
                'idextra'   => $args['id']
            ];
            $resp = SeloHandler::deleteSelo($data);
            ctrl::response($resp, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    public function clientes()
    {
        $idempresa = ctrl::getEmpresa();
        $cupon = new Cupon();
        $dados = $cupon->listarSelosClientes($idempresa);
        ctrl::response($dados, 200);
    }
}
