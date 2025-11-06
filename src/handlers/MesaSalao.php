<?php
namespace src\handlers;

use src\models\Mesa_salao;
use Exception;

class MesaSalao
{
    /**
     * Criar nova mesa
     */
    public static function criar($dados)
    {
            
        // Validar campos obrigatórios
        if (empty($dados['idempresa'])) {
            throw new Exception('ID da empresa é obrigatório');
        }

        if (empty($dados['numero_mesa'])) {
            throw new Exception('Número da mesa é obrigatório');
        }

        // Validar se número da mesa já existe
        if (Mesa_salao::mesaExiste($dados['numero_mesa'], $dados['idempresa'])) {
            throw new Exception('Já existe uma mesa com esse número');
        }

        // Preparar dados
        $dadosMesa = [
            'idempresa' => $dados['idempresa'],
            'numero_mesa' => $dados['numero_mesa'],
            'apelido' => $dados['apelido'] ?? null,
            'ativo' => $dados['ativo'] ?? 1
        ];

        $idmesa = Mesa_salao::criarMesa($dadosMesa);

        if (!$idmesa) {
            throw new Exception('Erro ao criar mesa');
        }

        return Mesa_salao::getMesaById($idmesa, $dados['idempresa']);
    }

    /**
     * Editar mesa
     */
    public static function editar($dados)
    {
        // Validar campos obrigatórios
        if (empty($dados['idmesa'])) {
            throw new Exception('ID da mesa é obrigatório');
        }

        if (empty($dados['idempresa'])) {
            throw new Exception('ID da empresa é obrigatório');
        }

        // Verificar se mesa existe
        $mesa = Mesa_salao::getMesaById($dados['idmesa'], $dados['idempresa']);
        if (!$mesa) {
            throw new Exception('Mesa não encontrada');
        }

        // Validar se número da mesa já existe (exceto a própria)
        if (isset($dados['numero_mesa']) && Mesa_salao::mesaExiste($dados['numero_mesa'], $dados['idempresa'], $dados['idmesa'])) {
            throw new Exception('Já existe uma mesa com esse número');
        }

        // Preparar dados
        $dadosUpdate = [];
        if (isset($dados['numero_mesa'])) {
            $dadosUpdate['numero_mesa'] = $dados['numero_mesa'];
        }
        if (isset($dados['apelido'])) {
            $dadosUpdate['apelido'] = $dados['apelido'];
        }
        if (isset($dados['ativo'])) {
            $dadosUpdate['ativo'] = $dados['ativo'];
        }

        if (empty($dadosUpdate)) {
            throw new Exception('Nenhum campo para atualizar');
        }

        Mesa_salao::editarMesa($dados['idmesa'], $dadosUpdate);

        return Mesa_salao::getMesaById($dados['idmesa'], $dados['idempresa']);
    }

    /**
     * Deletar mesa
     */
    public static function deletar($idmesa, $idempresa)
    {
        // Verificar se mesa existe
        $mesa = Mesa_salao::getMesaById($idmesa, $idempresa);
        if (!$mesa) {
            throw new Exception('Mesa não encontrada');
        }

        // Verificar se mesa tem reservas ativas
        $temReserva = \src\models\Mesa_reserva::mesaTemReservaAtiva($idmesa, $idempresa);
        if ($temReserva) {
            throw new Exception('Não é possível excluir mesa com reserva ativa');
        }

        Mesa_salao::deletarMesa($idmesa);

        return true;
    }

    /**
     * Buscar todas as mesas
     */
    public static function getMesas($idempresa)
    {
        return Mesa_salao::getMesas($idempresa);
    }

    /**
     * Buscar mesas ativas (para autocomplete)
     */
    public static function getMesasAtivas($idempresa)
    {
        return Mesa_salao::getMesasAtivas($idempresa);
    }
}
