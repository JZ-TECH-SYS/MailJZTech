<?php

namespace src\controllers;

use \core\Controller as ctrl;
use Exception;
use src\handlers\Usuario as UsuarioHelp;

class UsuariosController extends ctrl
{
    const EDITCAMPOS = ['idempresa', 'iduser'];

    public function getUsuarios($args)
    {
        $nome = $args['nome'] ?? null;
        $usuarios = UsuarioHelp::getUsuarios($args['idempresa'], $nome);
        ctrl::response($usuarios, 200);
    }

    public function editUsuario()
    {
        try {
            $data = ctrl::getBody();
            ctrl::verificarCamposVazios($data, self::EDITCAMPOS);
            $usuario = UsuarioHelp::editUsuario($data);
            ctrl::response($usuario, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
}
