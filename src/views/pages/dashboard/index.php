<?php $render('header'); ?>

<h2 class="mb-4">
    <i class="fas fa-chart-line"></i> Dashboard
    <small class="text-muted" style="font-size: 0.6em;">
        <i class="fas fa-sync-alt"></i> Atualiza automaticamente a cada 30s
    </small>
</h2>

<!-- Estatísticas Principais -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card border-left-primary">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h6 text-muted mb-1">Total de E-mails</div>
                        <div class="h3 mb-0 text-primary" data-stat="total">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-primary opacity-50">
                        <i class="fas fa-envelope fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card border-left-success">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h6 text-muted mb-1">Enviados</div>
                        <div class="h3 mb-0 text-success" data-stat="enviados">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-success opacity-50">
                        <i class="fas fa-check-circle fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card border-left-danger">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h6 text-muted mb-1">Erros</div>
                        <div class="h3 mb-0 text-danger" data-stat="erros">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-danger opacity-50">
                        <i class="fas fa-times-circle fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card border-left-warning">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h6 text-muted mb-1">Taxa de Sucesso</div>
                        <div class="h3 mb-0 text-warning" data-stat="taxa">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-warning opacity-50">
                        <i class="fas fa-chart-pie fa-3x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Gráficos -->
<div class="row mb-4">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-bar"></i> E-mails Enviados (Últimos 30 dias)
                </h5>
            </div>
            <div class="card-body">
                <canvas id="emailsChart" height="80"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-doughnut"></i> Status dos E-mails
                </h5>
            </div>
            <div class="card-body">
                <canvas id="statusChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Últimos E-mails -->
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-history"></i> Últimos E-mails Enviados
            </h5>
            <a href="<?php echo $base; ?>/emails" class="btn btn-sm btn-outline-primary">
                Ver Todos <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="tabelaEmails">
            <thead>
                <tr>
                    <th>Destinatário</th>
                    <th>Assunto</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="5" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p class="text-muted mt-2 mb-0">Carregando dados...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de Detalhes -->
<div class="modal fade" id="detalhesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient border-bottom-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title text-white">
                    <i class="fas fa-envelope"></i> Detalhes do E-mail
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="detalhesContent" style="max-height: 70vh; overflow-y: auto;">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="mt-3 text-muted">Carregando detalhes...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .border-left-primary {
        border-left: 4px solid #667eea !important;
    }
    .border-left-success {
        border-left: 4px solid #4caf50 !important;
    }
    .border-left-danger {
        border-left: 4px solid #f44336 !important;
    }
    .border-left-warning {
        border-left: 4px solid #ff9800 !important;
    }
</style>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<!-- Dashboard JS - Carrega dados via API -->
<script src="<?php echo $base; ?>/assets/js/dashboard.js"></script>

<?php $render('footer'); ?>
