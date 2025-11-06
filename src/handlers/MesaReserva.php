<?php
namespace src\handlers;

use src\models\Mesa_reserva;
use src\models\Mesa_salao;
use src\models\Pessoa;
use Exception;

class MesaReserva
{
    /**
     * Criar nova reserva
     */
    public static function criar($dados)
    {
        // Validar campos obrigatórios
        if (empty($dados['idempresa'])) {
            throw new Exception('ID da empresa é obrigatório');
        }

        if (empty($dados['idmesa'])) {
            throw new Exception('Mesa é obrigatória');
        }

        if (empty($dados['expira_em'])) {
            throw new Exception('Data/Hora de expiração é obrigatória');
        }

        // Verificar se mesa existe e está ativa
        $mesa = Mesa_salao::getMesaById($dados['idmesa'], $dados['idempresa']);
        if (!$mesa) {
            throw new Exception('Mesa não encontrada');
        }

        if ($mesa['ativo'] != 1) {
            throw new Exception('Mesa está inativa');
        }

        // Verificar se mesa já tem reserva ativa
        if (Mesa_reserva::mesaTemReservaAtiva($dados['idmesa'], $dados['idempresa'])) {
            throw new Exception('Mesa já possui reserva ativa');
        }

        // Validar: OU cliente OU identificador deve ser preenchido
        if (empty($dados['idcliente']) && empty($dados['identificador'])) {
            throw new Exception('Informe um cliente ou um identificador para a reserva');
        }

        // Se tiver cliente, validar se existe
        if (!empty($dados['idcliente'])) {
            $cliente = Pessoa::select()
                ->where('idcliente', $dados['idcliente'])
                ->where('idempresa', $dados['idempresa'])
                ->one();

            if (!$cliente) {
                throw new Exception('Cliente não encontrado');
            }
        }

        // Preparar dados
        $dadosReserva = [
            'idempresa' => $dados['idempresa'],
            'idmesa' => $dados['idmesa'],
            'idcliente' => $dados['idcliente'] ?? null,
            'identificador' => $dados['identificador'] ?? null,
            'status' => 1, // ativo
            'expira_em' => $dados['expira_em'] ?? null,
            'criado_por' => $dados['criado_por'] ?? null
        ];

        $idreserva = Mesa_reserva::criarReserva($dadosReserva);

        if (!$idreserva) {
            throw new Exception('Erro ao criar reserva');
        }

        return Mesa_reserva::getReservaById($idreserva, $dados['idempresa']);
    }

    /**
     * Editar reserva
     */
    public static function editar($dados)
    {
        // Validar campos obrigatórios
        if (empty($dados['idreserva'])) {
            throw new Exception('ID da reserva é obrigatório');
        }

        if (empty($dados['idempresa'])) {
            throw new Exception('ID da empresa é obrigatório');
        }

        // Verificar se reserva existe
        $reserva = Mesa_reserva::getReservaById($dados['idreserva'], $dados['idempresa']);
        if (!$reserva) {
            throw new Exception('Reserva não encontrada');
        }

        // Se alterou a mesa, validar
        if (isset($dados['idmesa']) && $dados['idmesa'] != $reserva['idmesa']) {
            $mesa = Mesa_salao::getMesaById($dados['idmesa'], $dados['idempresa']);
            if (!$mesa) {
                throw new Exception('Mesa não encontrada');
            }

            if ($mesa['ativo'] != 1) {
                throw new Exception('Mesa está inativa');
            }

            // Verificar se nova mesa já tem reserva ativa
            if (Mesa_reserva::mesaTemReservaAtiva($dados['idmesa'], $dados['idempresa'], $dados['idreserva'])) {
                throw new Exception('Mesa já possui reserva ativa');
            }
        }

        // Se alterou o cliente, validar
        if (isset($dados['idcliente']) && !empty($dados['idcliente'])) {
            $cliente = Pessoa::select()
                ->where('idcliente', $dados['idcliente'])
                ->where('idempresa', $dados['idempresa'])
                ->one();

            if (!$cliente) {
                throw new Exception('Cliente não encontrado');
            }
        }

        // Se alterou cliente ou identificador, validar que ao menos um é fornecido
        $temCliente = isset($dados['idcliente']) ? !empty($dados['idcliente']) : !empty($reserva['idcliente']);
        $temIdentificador = isset($dados['identificador']) ? !empty($dados['identificador']) : !empty($reserva['identificador']);
        
        if (!$temCliente && !$temIdentificador) {
            throw new Exception('Informe um cliente ou um identificador para a reserva');
        }

        // Preparar dados
        $dadosUpdate = [];
        if (isset($dados['idmesa'])) {
            $dadosUpdate['idmesa'] = $dados['idmesa'];
        }
        if (isset($dados['idcliente'])) {
            $dadosUpdate['idcliente'] = $dados['idcliente'];
        }
        if (isset($dados['identificador'])) {
            $dadosUpdate['identificador'] = $dados['identificador'];
        }
        if (isset($dados['status'])) {
            $dadosUpdate['status'] = $dados['status'];
        }
        if (isset($dados['expira_em'])) {
            $dadosUpdate['expira_em'] = $dados['expira_em'];
        }

        if (empty($dadosUpdate)) {
            throw new Exception('Nenhum campo para atualizar');
        }

        Mesa_reserva::editarReserva($dados['idreserva'], $dadosUpdate);

        return Mesa_reserva::getReservaById($dados['idreserva'], $dados['idempresa']);
    }

    /**
     * Deletar reserva
     */
    public static function deletar($idreserva, $idempresa)
    {
        // Verificar se reserva existe
        $reserva = Mesa_reserva::getReservaById($idreserva, $idempresa);
        if (!$reserva) {
            throw new Exception('Reserva não encontrada');
        }

        Mesa_reserva::deletarReserva($idreserva);

        return true;
    }

    /**
     * Cancelar reserva
     */
    public static function cancelar($idreserva, $idempresa)
    {
        // Verificar se reserva existe
        $reserva = Mesa_reserva::getReservaById($idreserva, $idempresa);
        if (!$reserva) {
            throw new Exception('Reserva não encontrada');
        }

        if ($reserva['status'] == 3) {
            throw new Exception('Reserva já está cancelada');
        }

        Mesa_reserva::cancelarReserva($idreserva);

        return Mesa_reserva::getReservaById($idreserva, $idempresa);
    }

    /**
     * Buscar todas as reservas
     */
    public static function getReservas($idempresa, $status = null)
    {
        return Mesa_reserva::getReservas($idempresa, $status);
    }
}
