<?php

namespace src\controllers;

use core\Controller as ctrl;
use src\handlers\BackupConfig;
use src\handlers\BackupExecucao;
use Exception;
use src\handlers\Emails;

/**
 * Controller para gerenciamento de backups de bancos de dados.
 * Implementa páginas web e endpoints da API REST.
 */
class BackupController extends ctrl
{
    // ==================== PÁGINAS WEB ====================

    /**
     * Página principal de gerenciamento de backups.
     */
    public function index(): void
    {
        $this->render('backup');
    }

    /**
     * Página de visualização de logs de um banco específico.
     *
     * @param array $args
     */
    public function logs($args = []): void
    {
        try {
            $id = $args['id'] ?? null;
            
            if (!$id) {
                self::redirect('/backup');
                return;
            }

            $config = BackupConfig::obterPorId($id);
            $this->render('backup_logs', [
                'config' => $config
            ]);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    // ==================== API - CRUD CONFIGURAÇÕES ====================

    /**
     * GET /api/backup/configuracoes
     * Lista todas as configurações de backup.
     */
    public function listar(): void
    {
        try {
            $apenasAtivos = isset($_GET['ativos']) ? (bool)$_GET['ativos'] : null;
            $configuracoes = BackupConfig::listarTodos($apenasAtivos);
            
            ctrl::response($configuracoes, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * GET /api/backup/configuracoes/{id}
     * Obtém uma configuração específica.
     *
     * @param array $args
     */
    public function obter($args = []): void
    {
        try {
            $id = $args['id'] ?? null;
            
            if (!$id) {
                throw new Exception("ID da configuração não informado");
            }

            $config = BackupConfig::obterPorId($id);
            ctrl::response($config, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * POST /api/backup/configuracoes
     * Cria uma nova configuração de backup.
     */
    public function criar(): void
    {
        try {
            $dados = ctrl::getBody(true);
            
            // Validar campos obrigatórios
            ctrl::verificarCamposVazios($dados, ['nome_banco', 'pasta_base']);

            $id = BackupConfig::criar($dados);

            ctrl::response([
                'mensagem' => 'Configuração de backup criada com sucesso',
                'id' => $id
            ], 201);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * PUT /api/backup/configuracoes/{id}
     * Atualiza uma configuração existente.
     *
     * @param array $args
     */
    public function atualizar($args = []): void
    {
        try {
            $id = $args['id'] ?? null;
            
            if (!$id) {
                throw new Exception("ID da configuração não informado");
            }

            $dados = ctrl::getBody(true);

            BackupConfig::atualizar($id, $dados);

            ctrl::response([
                'mensagem' => 'Configuração atualizada com sucesso'
            ], 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * DELETE /api/backup/configuracoes/{id}
     * Exclui uma configuração de backup.
     *
     * @param array $args
     */
    public function excluir($args = []): void
    {
        try {
            $id = $args['id'] ?? null;
            
            if (!$id) {
                throw new Exception("ID da configuração não informado");
            }

            BackupConfig::excluir($id);

            ctrl::response([
                'mensagem' => 'Configuração excluída com sucesso'
            ], 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    // ==================== API - EXECUÇÃO DE BACKUPS ====================

    /**
     * POST /api/backup/executar/{id}
     * Executa backup manual de um banco específico.
     *
     * @param array $args
     */
    public function executarManual($args = []): void
    {
        try {
            $id = $args['id'] ?? null;
            
            if (!$id) {
                throw new Exception("ID da configuração não informado");
            }

            $resultado = BackupExecucao::executarPorId($id);

            ctrl::response([
                'mensagem' => 'Backup executado com sucesso',
                'dados' => $resultado
            ], 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * POST /api/backup/executar
     * Executa backup de todos os bancos ativos (chamado pelo cron).
     * Requer autenticação via TOKEN_JV.
     */
    public function executarCron(): void
    {
        try {
            $resultados = BackupExecucao::executarTodos();

            // Contar sucessos e erros
            $sucesso = array_filter($resultados, fn($r) => $r['sucesso'] === true);
            $erros = array_filter($resultados, fn($r) => $r['sucesso'] === false);

            $data = [
                'mensagem' => 'Execução de backups concluída',
                'total' => count($resultados),
                'sucesso' => count($sucesso),
                'erros' => count($erros),
                'detalhes' => $resultados,
                'data_execucao' => date('Y-m-d H:i:s')
            ];
            
            Emails::enviarRelatorioBackupsCron($data);

            ctrl::response($data, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    // ==================== API - CONSULTAS ====================

    /**
     * GET /api/backup/logs/{id}
     * Obtém logs de execução de um banco específico.
     *
     * @param array $args
     */
    public function obterLogs($args = []): void
    {
        try {
            $id = $args['id'] ?? null;
            
            if (!$id) {
                throw new Exception("ID da configuração não informado");
            }

            $limite = isset($_GET['limite']) ? (int)$_GET['limite'] : 50;
            
            // Usar SQL complexo se solicitado
            $usarSqlComplexo = isset($_GET['detalhado']) && $_GET['detalhado'] === 'true';

            if ($usarSqlComplexo) {
                $logs = BackupExecucao::obterLogsDetalhados($id, $limite);
            } else {
                $logs = BackupExecucao::obterLogs($id, $limite);
            }

            ctrl::response($logs, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * GET /api/backup/estatisticas
     * Obtém estatísticas gerais dos backups (dashboard).
     */
    public function obterEstatisticas(): void
    {
        try {
            $stats = BackupExecucao::obterEstatisticas();
            ctrl::response($stats, 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }

    /**
     * POST /api/backup/limpar-antigos/{id}
     * Remove backups antigos baseado na retenção configurada.
     *
     * @param array $args
     */
    public function limparAntigos($args = []): void
    {
        try {
            $id = $args['id'] ?? null;
            
            if (!$id) {
                throw new Exception("ID da configuração não informado");
            }

            $resultado = BackupExecucao::limparBackupsAntigos($id);

            ctrl::response([
                'mensagem' => 'Limpeza de backups antigos executada',
                'dados' => $resultado
            ], 200);
        } catch (Exception $e) {
            ctrl::rejectResponse($e);
        }
    }
}
