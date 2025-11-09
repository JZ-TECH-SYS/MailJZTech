<?php $render('header'); ?>

<h2 class="mb-4">
    <i class="fas fa-chart-line"></i> Dashboard
</h2>

<!-- Estatísticas Principais -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card border-left-primary">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="h6 text-muted mb-1">Total de E-mails</div>
                        <div class="h3 mb-0 text-primary"><?php echo $stats['total'] ?? 0; ?></div>
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
                        <div class="h3 mb-0 text-success"><?php echo $stats['enviados'] ?? 0; ?></div>
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
                        <div class="h3 mb-0 text-danger"><?php echo $stats['erros'] ?? 0; ?></div>
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
                        <div class="h3 mb-0 text-warning">
                            <?php 
                            $total = $stats['total'] ?? 0;
                            $enviados = $stats['enviados'] ?? 0;
                            $taxa = $total > 0 ? round(($enviados / $total) * 100, 1) : 0;
                            echo $taxa . '%';
                            ?>
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
        <table class="table table-hover mb-0">
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
                <?php if (!empty($ultimos_emails)): ?>
                    <?php foreach ($ultimos_emails as $email): ?>
                        <tr>
                            <td>
                                <small><?php echo htmlspecialchars($email['destinatario']); ?></small>
                            </td>
                            <td>
                                <small><?php echo htmlspecialchars(substr($email['assunto'], 0, 50)); ?></small>
                            </td>
                            <td>
                                <?php if ($email['status'] === 'enviado'): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle"></i> Enviado
                                    </span>
                                <?php elseif ($email['status'] === 'erro'): ?>
                                    <span class="badge bg-danger">
                                        <i class="fas fa-times-circle"></i> Erro
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning">
                                        <i class="fas fa-clock"></i> Pendente
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?php echo date('d/m/Y H:i', strtotime($email['data_envio'])); ?>
                                </small>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="verDetalhes(<?php echo $email['idemail']; ?>)"
                                        data-bs-toggle="tooltip" title="Ver Detalhes">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            Nenhum e-mail enviado ainda
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal de Detalhes -->
<div class="modal fade" id="detalhesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes do E-mail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalhesContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
    // Gráfico de E-mails Enviados
    const emailsCtx = document.getElementById('emailsChart').getContext('2d');
    const emailsChart = new Chart(emailsCtx, {
        type: 'line',
        data: {
            labels: ['Dia 1', 'Dia 2', 'Dia 3', 'Dia 4', 'Dia 5', 'Dia 6', 'Dia 7'],
            datasets: [
                {
                    label: 'Enviados',
                    data: [<?php echo isset($grafico_dados) ? implode(',', $grafico_dados['enviados'] ?? [0,0,0,0,0,0,0]) : '0,0,0,0,0,0,0'; ?>],
                    borderColor: '#4caf50',
                    backgroundColor: 'rgba(76, 175, 80, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Erros',
                    data: [<?php echo isset($grafico_dados) ? implode(',', $grafico_dados['erros'] ?? [0,0,0,0,0,0,0]) : '0,0,0,0,0,0,0'; ?>],
                    borderColor: '#f44336',
                    backgroundColor: 'rgba(244, 67, 54, 0.1)',
                    tension: 0.4,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Gráfico de Status
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: ['Enviados', 'Erros', 'Pendentes'],
            datasets: [{
                data: [
                    <?php echo $stats['enviados'] ?? 0; ?>,
                    <?php echo $stats['erros'] ?? 0; ?>,
                    <?php echo ($stats['total'] ?? 0) - ($stats['enviados'] ?? 0) - ($stats['erros'] ?? 0); ?>
                ],
                backgroundColor: [
                    '#4caf50',
                    '#f44336',
                    '#ff9800'
                ],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                }
            }
        }
    });

    function verDetalhes(idemail) {
        const modal = new bootstrap.Modal(document.getElementById('detalhesModal'));
        const content = document.getElementById('detalhesContent');
        
        fetch('<?php echo $base; ?>/detalheEmail?idemail=' + idemail)
            .then(response => response.json())
            .then(data => {
                if (!data.error && data.result) {
                    const email = data.result;
                    content.innerHTML = `
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Destinatário:</strong><br>
                                <code>${email.destinatario}</code>
                            </div>
                            <div class="col-md-6">
                                <strong>Status:</strong><br>
                                ${email.status === 'enviado' ? '<span class="badge bg-success">Enviado</span>' : 
                                  email.status === 'erro' ? '<span class="badge bg-danger">Erro</span>' : 
                                  '<span class="badge bg-warning">Pendente</span>'}
                            </div>
                        </div>
                        <div class="mb-3">
                            <strong>Assunto:</strong><br>
                            ${email.assunto}
                        </div>
                        <div class="mb-3">
                            <strong>Corpo (HTML):</strong><br>
                            <div class="border rounded p-2 bg-light" style="max-height: 300px; overflow-y: auto;">
                                ${email.corpo_html}
                            </div>
                        </div>
                        ${email.cc ? `<div class="mb-3"><strong>CC:</strong><br><code>${email.cc}</code></div>` : ''}
                        ${email.bcc ? `<div class="mb-3"><strong>BCC:</strong><br><code>${email.bcc}</code></div>` : ''}
                        <div class="mb-3">
                            <strong>Data de Envio:</strong><br>
                            ${new Date(email.data_envio).toLocaleString('pt-BR')}
                        </div>
                        ${email.mensagem_erro ? `<div class="alert alert-danger"><strong>Erro:</strong><br>${email.mensagem_erro}</div>` : ''}
                    `;
                }
                modal.show();
            })
            .catch(error => {
                console.error('Erro:', error);
                content.innerHTML = '<div class="alert alert-danger">Erro ao carregar detalhes</div>';
                modal.show();
            });
    }
</script>

<?php $render('footer'); ?>
