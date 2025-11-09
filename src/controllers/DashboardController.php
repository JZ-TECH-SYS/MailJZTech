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
     * ✅ ARQUITETURA CORRETA: Controller → Handler → Model
     * ✅ Dashboard GERAL - mostra dados de TODOS os sistemas
     */
    public function obterEstatisticas()
    {
        try {
            // Filtro opcional por sistema via query string (?idsistema=123)
            $idsistemaQS = filter_input(INPUT_GET, 'idsistema', FILTER_VALIDATE_INT);
            $idsistema = $idsistemaQS ?: null; // null = geral (todos)
            
            // ✅ Controller → Handler → Model
            $dados = EmailsHandler::obterDadosDashboard($idsistema, 10);

            // Retorna os dados
            return self::response([
                'estatisticas' => $dados['estatisticas'],
                'ultimos_emails' => $dados['ultimos_emails']
            ], 200);
        } catch (\Exception $e) {
            self::log('Erro ao obter estatísticas do dashboard: ' . $e->getMessage());
            return self::rejectResponse($e);
        }
    }
}
