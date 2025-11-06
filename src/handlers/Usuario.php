<?php

namespace src\handlers;

use src\models\Users as UsersModel;
use Exception;
use core\Database as db;

class Usuario
{
    public static function getUsuarios($idempresa, $nome = null)
    {
        $query = UsersModel::select(['iduser','nome','idempresa','tipo']);
        $query->where('idempresa', $idempresa)->whereNotIn('nome', ['SISTEMA8','SISTEMA3']);
        if (!empty($nome)) {
            $query->where('nome', 'LIKE', '%'.$nome.'%');
        }
        return $query->execute();
    }

    public static function editUsuario($data)
    {
        $usuario = UsersModel::select()
            ->where('idempresa', $data['idempresa'])
            ->where('iduser', $data['iduser'])
            ->one();
        if (empty($usuario)) {
            throw new Exception('Usuário não encontrado');
        }

        $dados = [];
        if (isset($data['nome'])) {
            $dados['nome'] = $data['nome'];
        }
        if (!empty($data['senha'])) {
            $dados['senha'] = password_hash($data['senha'], PASSWORD_DEFAULT);
        }
        if (!empty($dados)) {
            db::getInstance()->beginTransaction();
            try {
                UsersModel::update($dados)
                    ->where('iduser', $data['iduser'])
                    ->execute();
                db::getInstance()->commit();
            } catch (Exception $e) {
                db::getInstance()->rollBack();
                throw new Exception('Erro ao atualizar usuário');
            }
        }
        return UsersModel::select(['iduser','nome','idempresa','tipo'])
            ->where('iduser', $data['iduser'])
            ->one();
    }
}
