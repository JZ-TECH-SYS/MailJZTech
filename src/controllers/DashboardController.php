<?php

namespace src\controllers;

use \core\Controller as ctrl;
use src\handlers\Emails as EmailsHandler;
use src\models\EmailLogs;

class DashboardController extends ctrl
{
    /**
     * Renderiza o dashboard
     * GET /dashboard (privado = true)
     */
    public function index()
    {
        $this->render('dashboard');
    }

    /**
     * Obtém estatísticas do dashboard (API)
     * GET /api/dashboard/stats
     */
    public function obterEstatisticas()
    {
        try {
            // Obtém o ID do sistema da query string
            $idsistema = $_GET['idsistema'] ?? null;

            if (!$idsistema) {
                return $this->response(['mensagem' => 'ID do sistema é obrigatório'], 400);
            }

            $emailsHandler = new EmailsHandler();
            $emailLogs = new EmailLogs();

            // Obtém estatísticas de e-mails
            $stats = $emailsHandler->obterEstatisticas($idsistema);

            // Obtém logs recentes
            $logsRecentes = $emailLogs->obterRecentes(10);

            // Retorna os dados
            return $this->response([
                'sucesso' => true,
                'estatisticas' => $stats,
                'logs_recentes' => $logsRecentes
            ]);
        } catch (\Exception $e) {
            return $this->response([
                'sucesso' => false,
                'mensagem' => 'Erro ao obter estatísticas: ' . $e->getMessage()
            ], 500);
        }
    }
}
