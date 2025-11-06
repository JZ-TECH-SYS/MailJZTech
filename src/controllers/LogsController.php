<?php

namespace src\controllers;

use \core\Controller as ctrl;
use src\models\EmailLogs;

class LogsController extends ctrl
{
    /**
     * Renderiza a página de logs
     * GET /logs (privado = true)
     */
    public function index()
    {
        $this->render('logs');
    }

    /**
     * Lista logs recentes (API)
     */
    public function listarLogs()
    {
        try {
            $idsistema = $_GET['idsistema'] ?? null;
            $limite = $_GET['limite'] ?? 100;

            if (!$idsistema) {
                return ctrl::response(['mensagem' => 'ID do sistema é obrigatório'], 400);
            }

            $emailLogs = new EmailLogs();
            $logs = $emailLogs->obterPorSistema($idsistema, $limite);

            return ctrl::response([
                'sucesso' => true,
                'logs' => $logs,
                'total' => count($logs)
            ], 200);
        } catch (\Exception $e) {
            return ctrl::response([
                'sucesso' => false,
                'mensagem' => 'Erro ao listar logs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Filtra logs por período (API)
     */
    public function filtrarLogs()
    {
        try {
            $idsistema = $_GET['idsistema'] ?? null;
            $data_inicio = $_GET['data_inicio'] ?? null;
            $data_fim = $_GET['data_fim'] ?? null;
            $tipo_log = $_GET['tipo_log'] ?? null;

            if (!$idsistema) {
                return $this->response(['mensagem' => 'ID do sistema é obrigatório'], 400);
            }

            $emailLogs = new EmailLogs();

            if ($data_inicio && $data_fim) {
                $logs = $emailLogs->obterPorPeriodo($idsistema, $data_inicio, $data_fim);
            } elseif ($tipo_log) {
                $logs = $emailLogs->obterPorTipo($tipo_log, 100);
            } else {
                $logs = $emailLogs->obterRecentes(100);
            }

            return ctrl::response([
                'sucesso' => true,
                'logs' => $logs,
                'total' => count($logs)
            ]);
        } catch (\Exception $e) {
            return ctrl::response([
                'sucesso' => false,
                'mensagem' => 'Erro ao filtrar logs: ' . $e->getMessage()
            ], 500);
        }
    }
}
