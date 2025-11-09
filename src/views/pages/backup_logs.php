<?php $render('header'); ?>

<div class="row mb-4">
    <div class="col-md-8">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $base; ?>/backup">Backups</a></li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?php echo htmlspecialchars($viewData['config']['nome_banco'] ?? 'Logs'); ?>
                </li>
            </ol>
        </nav>
        <h2 class="mb-2">Logs de Backup</h2>
        <p class="text-muted">Histórico de execuções para <strong><?php echo htmlspecialchars($viewData['config']['nome_banco'] ?? ''); ?></strong></p>
    </div>
    <div class="col-md-4 text-end">
        <button class="btn btn-success" onclick="executarBackupManual()">
            <i class="fas fa-play"></i> Executar Agora
        </button>
        <a href="<?php echo $base; ?>/backup" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<!-- Informações da Configuração -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1">Bucket / Pasta</p>
                <p class="mb-0">
                    <code><?php echo htmlspecialchars($viewData['config']['bucket_nome'] ?? ''); ?></code><br>
                    <small><?php echo htmlspecialchars($viewData['config']['pasta_base'] ?? ''); ?></small>
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1">Retenção</p>
                <h4 class="mb-0"><?php echo htmlspecialchars($viewData['config']['retencao_dias'] ?? 0); ?> dias</h4>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1">Último Backup</p>
                <p class="mb-0">
                    <?php 
                    $ultimo = $viewData['config']['ultimo_backup_em'] ?? null;
                    echo $ultimo ? '<small>' . date('d/m/Y H:i', strtotime($ultimo)) . '</small>' : '<small>-</small>'; 
                    ?>
                </p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-1">Total de Backups</p>
                <h4 class="mb-0"><?php echo htmlspecialchars($viewData['config']['total_backups'] ?? 0); ?></h4>
            </div>
        </div>
    </div>
</div>

<!-- Tabela de Logs -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="tabelaLogs">
                <thead>
                    <tr>
                        <th>Data/Hora Início</th>
                        <th>Duração</th>
                        <th>Status</th>
                        <th>Tamanho</th>
                        <th>Arquivo GCS</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                            <p class="mt-3 text-muted">Carregando logs...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Ver Erro -->
<div class="modal fade" id="modalErro" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle"></i> Detalhes do Erro
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre id="erroDetalhes" class="bg-light p-3 rounded" style="max-height: 400px; overflow-y: auto;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Definir variáveis globais para o backup.js
    const idConfig = <?php echo (int)($viewData['config']['idbackup_banco_config'] ?? 0); ?>;
    const nomeConfig = <?php echo json_encode($viewData['config']['nome_banco'] ?? ''); ?>;
</script>
<script src="<?php echo $base; ?>/assets/js/backup.js"></script>

<?php $render('footer'); ?>
