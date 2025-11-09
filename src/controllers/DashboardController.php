<?php

namespace src\controllers;

use \core\Controller as ctrl;
use src\handlers\Emails as EmailsHandler;
use src\handlers\Logs as LogsHandler;

/**
 * DashboardController - Responsável por exibir estatísticas
 * ✅ ARQUITETURA CORRETA: Controller → Handler → Model
 */
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
                return self::response(['mensagem' => 'ID do sistema é obrigatório'], 400);
            }

            // Obtém estatísticas de e-mails
            $stats = EmailsHandler::obterEstatisticas($idsistema);

            // Obtém logs recentes
            // ✅ Controller → Handler
            $logsRecentes = LogsHandler::obterRecentes(10);

            // Retorna os dados
            return self::response([
                'sucesso' => true,
                'estatisticas' => $stats,
                'logs_recentes' => $logsRecentes
            ], 200);
        } catch (\Exception $e) {
            return self::response([
                'sucesso' => false,
                'mensagem' => 'Erro ao obter estatísticas: ' . $e->getMessage()
            ], 500);
        }
    }
}
