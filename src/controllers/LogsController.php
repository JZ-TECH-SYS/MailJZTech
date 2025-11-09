<?php

namespace src\controllers;

use core\Controller as ctrl;
use src\handlers\Logs as LogsHandler;

/**
 * Controller para gerenciar logs de e-mail
 * Segue arquitetura: Controller → Handler → Model
 *
 * @author MailJZTech
 * @date 2025-01-09
 */
class LogsController extends ctrl
{

    public function index()
    {
        $this->render('log');
    }

    /**
     * Lista logs de e-mail com filtros
     * GET /api/logs/listar?idemail=1&tipo_log=envio&limite=50&offset=0
     *
     * @return void
     */
    public function listar()
    {
        try {
            // Obter parâmetros da query string
            $filtros = [
                'idemail' => $_GET['idemail'] ?? null,
                'idsistema' => $_GET['idsistema'] ?? null,
                'idusuario' => $_GET['idusuario'] ?? null,
                'tipo_log' => $_GET['tipo_log'] ?? null,
                'data_inicio' => $_GET['data_inicio'] ?? null,
                'data_fim' => $_GET['data_fim'] ?? null,
            ];

            $limite = (int)($_GET['limite'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);

            // Chamar handler (Controller → Handler)
            $logs = LogsHandler::listar($filtros, $limite, $offset);
            $total = LogsHandler::contar($filtros);

            // Retornar resultado
            ctrl::response([
                'logs' => $logs,
                'total' => $total,
                'limite' => $limite,
                'offset' => $offset
            ], 200);

        } catch (\Exception $e) {
            ctrl::log("Erro em listar logs: " . $e->getMessage());
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Obtém detalhes de um log específico
     * GET /api/logs/obter?idlog=123
     *
     * @return void
     */
    public function obter()
    {
        try {
            // Obter ID do log
            $idlog = $_GET['idlog'] ?? null;

            if (!$idlog) {
                throw new \Exception('idlog é obrigatório');
            }

            // Chamar handler (Controller → Handler)
            $log = LogsHandler::obter($idlog);

            if (!$log) {
                ctrl::response(['mensagem' => 'Log não encontrado'], 404);
                return;
            }

            // Retornar resultado
            ctrl::response($log, 200);

        } catch (\Exception $e) {
            ctrl::log("Erro em obter log: " . $e->getMessage());
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Obtém logs de um e-mail específico
     * GET /api/logs/por-email?idemail=123
     *
     * @return void
     */
    public function porEmail()
    {
        try {
            // Obter ID do e-mail
            $idemail = $_GET['idemail'] ?? null;

            if (!$idemail) {
                throw new \Exception('idemail é obrigatório');
            }

            // Chamar handler (Controller → Handler)
            $logs = LogsHandler::obterPorEmail($idemail);

            // Retornar resultado
            ctrl::response(['logs' => $logs, 'total' => count($logs)], 200);

        } catch (\Exception $e) {
            ctrl::log("Erro em porEmail: " . $e->getMessage());
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Obtém logs recentes
     * GET /api/logs/recentes?limite=10
     *
     * @return void
     */
    public function recentes()
    {
        try {
            // Obter limite
            $limite = (int)($_GET['limite'] ?? 10);

            // Chamar handler (Controller → Handler)
            $logs = LogsHandler::obterRecentes($limite);

            // Retornar resultado
            ctrl::response(['logs' => $logs, 'total' => count($logs)], 200);

        } catch (\Exception $e) {
            ctrl::log("Erro em recentes: " . $e->getMessage());
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Obtém logs por tipo
     * GET /api/logs/por-tipo?tipo_log=erro
     *
     * @return void
     */
    public function porTipo()
    {
        try {
            // Obter tipo
            $tipoLog = $_GET['tipo_log'] ?? null;

            if (!$tipoLog) {
                throw new \Exception('tipo_log é obrigatório');
            }

            $limite = (int)($_GET['limite'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);

            // Chamar handler (Controller → Handler)
            $logs = LogsHandler::obterPorTipo($tipoLog, $limite, $offset);
            $total = LogsHandler::contar(['tipo_log' => $tipoLog]);

            // Retornar resultado
            ctrl::response([
                'logs' => $logs,
                'total' => $total,
                'tipo_log' => $tipoLog
            ], 200);

        } catch (\Exception $e) {
            ctrl::log("Erro em porTipo: " . $e->getMessage());
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Obtém logs por período
     * GET /api/logs/por-periodo?data_inicio=2025-01-01&data_fim=2025-01-31
     *
     * @return void
     */
    public function porPeriodo()
    {
        try {
            // Obter datas
            $dataInicio = $_GET['data_inicio'] ?? null;
            $dataFim = $_GET['data_fim'] ?? null;

            if (!$dataInicio || !$dataFim) {
                throw new \Exception('data_inicio e data_fim são obrigatórios');
            }

            $idsistema = $_GET['idsistema'] ?? null;
            $tipoLog = $_GET['tipo_log'] ?? null;

            // Chamar handler (Controller → Handler)
            $logs = LogsHandler::obterPorPeriodo($dataInicio, $dataFim, $idsistema, $tipoLog);

            // Retornar resultado
            ctrl::response([
                'logs' => $logs,
                'total' => count($logs),
                'periodo' => [
                    'inicio' => $dataInicio,
                    'fim' => $dataFim
                ]
            ], 200);

        } catch (\Exception $e) {
            ctrl::log("Erro em porPeriodo: " . $e->getMessage());
            ctrl::rejectResponse($e);
        }
    }

    /**
     * Limpa logs antigos
     * DELETE /api/logs/limpar-antigos?dias=90
     *
     * @return void
     */
    public function limparAntigos()
    {
        try {
            // Obter dias
            $dias = (int)($_GET['dias'] ?? 90);

            // Chamar handler (Controller → Handler)
            $resultado = LogsHandler::limparAntigos($dias);

            // Retornar resultado
            ctrl::response($resultado, 200);

        } catch (\Exception $e) {
            ctrl::log("Erro em limparAntigos: " . $e->getMessage());
            ctrl::rejectResponse($e);
        }
    }
}
