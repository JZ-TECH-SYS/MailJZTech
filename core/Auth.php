<?php

namespace core;

use \core\Controller as ctrl;
use \src\models\Usuarios as Users;
use \src\handlers\Usuarios as UserHandlers;

class Auth extends ctrl
{
    public  function validaToken($token, $args)
    {
        // Para rotas de VIEW (navegação normal), aceita apenas sessão válida
        // Para rotas de API, exige Bearer token
        
        // Se tem sessão válida e usuário está logado, permite acesso
        if (!empty($_SESSION['token']) && UserHandlers::checkLogin()) {
            return; // Autorizado
        }
        
        // Se não tem sessão, tenta validar por Bearer token (APIs)
        if ($token) {
            $authHeaderParts = explode(' ', $token);
            if (count($authHeaderParts) === 2) {
                $tokenValue = $authHeaderParts[1];
                $autorization = Users::select(['token', 'idempresa'])->where('token', $tokenValue)->one();
                
                if (!empty($autorization)) {
                    $idempresa = $this->verificarEmpresa($args);
                    if ($idempresa == null || $autorization['idempresa'] == $idempresa) {
                        return; // Autorizado
                    }
                }
            }
        }
        
        // Se chegou aqui, não autorizado
        self::VALIDATION();
    }

    public function verificarEmpresa($args)
    {
        $idempresa = null;
        if (isset($args['idempresa'])) {
            $idempresa = $args['idempresa'];
        }

        $payload = ctrl::getBody(false);
        if (isset($payload['idempresa'])) {
            $idempresa = $payload['idempresa'];
        }

        return $idempresa;
    }

    public static function VALIDATION()
    {
        ctrl::redirect('login');
    }
}
