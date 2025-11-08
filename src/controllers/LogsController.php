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
     * Lista logs com paginação e filtros (API)
     * GET /api/logs/listar
     */
    public function listar()
    {
        try {
            $pagina = (int)($_GET['pagina'] ?? 1);
            $limite = (int)($_GET['limite'] ?? 20);
            $tipo = $_GET['tipo'] ?? null;
            $dataInicial = $_GET['data_inicial'] ?? null;
            $dataFinal = $_GET['data_final'] ?? null;
            $busca = $_GET['busca'] ?? null;

            $offset = ($pagina - 1) * $limite;

            // Construir consulta base
            $query = EmailLogs::select();

            // Aplicar filtros
            if ($tipo) {
                $query->where('tipo_log', $tipo);
            }
            
            if ($dataInicial && $dataFinal) {
                $query->where('data_log', '>=', $dataInicial . ' 00:00:00');
                $query->where('data_log', '<=', $dataFinal . ' 23:59:59');
            } elseif ($dataInicial) {
                $query->where('data_log', '>=', $dataInicial . ' 00:00:00');
            } elseif ($dataFinal) {
                $query->where('data_log', '<=', $dataFinal . ' 23:59:59');
            }

            if ($busca) {
                $query->where('mensagem', 'LIKE', '%' . $busca . '%');
            }

            // Contar total para paginação
            $total = count($query->get());
            $paginasTotais = ceil($total / $limite);

            // Buscar logs paginados
            $logs = EmailLogs::select()
                ->where(function($q) use ($tipo, $dataInicial, $dataFinal, $busca) {
                    if ($tipo) $q->where('tipo_log', $tipo);
                    if ($dataInicial && $dataFinal) {
                        $q->where('data_log', '>=', $dataInicial . ' 00:00:00')
                          ->where('data_log', '<=', $dataFinal . ' 23:59:59');
                    } elseif ($dataInicial) {
                        $q->where('data_log', '>=', $dataInicial . ' 00:00:00');
                    } elseif ($dataFinal) {
                        $q->where('data_log', '<=', $dataFinal . ' 23:59:59');
                    }
                    if ($busca) {
                        $q->where('mensagem', 'LIKE', '%' . $busca . '%');
                    }
                })
                ->orderBy('data_log', 'DESC')
                ->limit($limite)
                ->offset($offset)
                ->get();

            return ctrl::response([
                'logs' => $logs,
                'total' => $total,
                'pagina_atual' => $pagina,
                'paginas_totais' => $paginasTotais
            ], 200);

        } catch (\Exception $e) {
            return ctrl::rejectResponse($e);
        }
    }

    /**
     * Retorna detalhes de um log específico (API)
     * GET /api/logs/detalhe/{id}
     */
    public function detalhe($args)
    {
        try {
            $idlog = $args['id'] ?? null;

            if (!$idlog) {
                throw new \Exception('ID do log não fornecido');
            }

            $log = EmailLogs::select()
                ->where('idlog', $idlog)
                ->one();

            if (!$log) {
                return ctrl::response(['mensagem' => 'Log não encontrado'], 404);
            }

            return ctrl::response($log, 200);

        } catch (\Exception $e) {
            return ctrl::rejectResponse($e);
        }
    }
}
