<?php $render('header'); ?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="mb-2">Gerenciamento de Backups</h2>
        <p class="text-muted">Configure backups automatizados dos seus bancos de dados MySQL</p>
    </div>
    <div class="col-md-4 text-end">
        <button class="btn btn-primary" onclick="abrirModalCriar()">
            <i class="fas fa-plus"></i> Nova Configuração
        </button>
    </div>
</div>

<!-- Cards de Estatísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Total de Bancos</p>
                        <h3 class="mb-0" id="statTotalBancos">-</h3>
                    </div>
                    <div class="text-primary">
                        <i class="fas fa-database fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Backups Realizados</p>
                        <h3 class="mb-0" id="statTotalBackups">-</h3>
                    </div>
                    <div class="text-success">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Espaço Utilizado</p>
                        <h3 class="mb-0" id="statEspacoTotal">-</h3>
                    </div>
                    <div class="text-info">
                        <i class="fas fa-hdd fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Último Backup</p>
                        <h3 class="mb-0"><small id="statUltimoBackup">-</small></h3>
                    </div>
                    <div class="text-warning">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="tabelaBackups">
                <thead>
                    <tr>
                        <th>Nome do Banco</th>
                        <th>Pasta Base</th>
                        <th>Retenção (dias)</th>
                        <th>Último Backup</th>
                        <th>Total Backups</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                            <p class="mt-3 text-muted">Carregando configurações...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Criar/Editar Configuração -->
<div class="modal fade" id="modalBackup" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-database"></i> <span id="modalTitulo">Nova Configuração</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formBackup">
                    <input type="hidden" id="idbackup" value="">
                    
                    <div class="mb-3">
                        <label for="nome_banco" class="form-label">Nome do Banco <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nome_banco" required 
                               placeholder="ex: mailjztech_prod"
                               pattern="[a-zA-Z0-9_\-]+"
                               title="Use apenas letras, números, hífen e underscore">
                        <small class="form-text text-muted">Apenas letras, números, _ e -</small>
                    </div>

                    <div class="mb-3">
                        <label for="bucket_nome" class="form-label">Nome do Bucket GCS</label>
                        <input type="text" class="form-control" id="bucket_nome" value="dbjztech" 
                               placeholder="dbjztech">
                    </div>

                    <div class="mb-3">
                        <label for="pasta_base" class="form-label">Pasta Base no Bucket <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="pasta_base" required 
                               placeholder="ex: mailjztech_prod">
                        <small class="form-text text-muted">Será criado: pasta_base/YYYY/MM/DD/backup-*.sql.gz</small>
                    </div>

                    <div class="mb-3">
                        <label for="retencao_dias" class="form-label">Retenção (dias) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="retencao_dias" required 
                               value="7" min="1" max="365">
                        <small class="form-text text-muted">Backups mais antigos serão removidos automaticamente</small>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="ativo" checked>
                        <label class="form-check-label" for="ativo">
                            Ativo
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="salvarConfiguracao()">
                    <i class="fas fa-save"></i> Salvar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo $base; ?>/assets/js/backup.js"></script>

<?php $render('footer'); ?>
