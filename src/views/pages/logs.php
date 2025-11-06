<?php $render('header'); ?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="mb-2">
            <i class="fas fa-list"></i> Logs do Sistema
        </h2>
        <p class="text-muted">Acompanhe todas as operações e eventos do sistema</p>
    </div>
    <div class="col-md-4 text-end">
        <button class="btn btn-outline-secondary" onclick="limparFiltros()">
            <i class="fas fa-redo"></i> Limpar Filtros
        </button>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-3">
                <label class="form-label">Tipo de Log</label>
                <select class="form-select" id="filtroTipo" onchange="aplicarFiltros()">
                    <option value="">Todos</option>
                    <option value="envio">Envio de E-mail</option>
                    <option value="criacao_sistema">Criação de Sistema</option>
                    <option value="atualizacao_sistema">Atualização de Sistema</option>
                    <option value="erro">Erro</option>
                    <option value="autenticacao">Autenticação</option>
                </select>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Data Inicial</label>
                <input type="date" class="form-control" id="filtroDataInicial" onchange="aplicarFiltros()">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Data Final</label>
                <input type="date" class="form-control" id="filtroDataFinal" onchange="aplicarFiltros()">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label">Buscar</label>
                <input type="text" class="form-control" id="filtroBusca" placeholder="Buscar por mensagem..." onkeyup="aplicarFiltros()">
            </div>
        </div>
    </div>
</div>

<!-- Tabela de Logs -->
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Tipo</th>
                    <th>Mensagem</th>
                    <th>E-mail ID</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>
                                <small class="text-muted">
                                    <?php echo date('d/m/Y H:i:s', strtotime($log['data_criacao'])); ?>
                                </small>
                            </td>
                            <td>
                                <?php 
                                $tipo = $log['tipo'] ?? 'outro';
                                $badge_class = match($tipo) {
                                    'envio' => 'bg-info',
                                    'criacao_sistema' => 'bg-success',
                                    'atualizacao_sistema' => 'bg-primary',
                                    'erro' => 'bg-danger',
                                    'autenticacao' => 'bg-warning',
                                    default => 'bg-secondary'
                                };
                                $icon = match($tipo) {
                                    'envio' => 'envelope',
                                    'criacao_sistema' => 'plus-circle',
                                    'atualizacao_sistema' => 'edit',
                                    'erro' => 'exclamation-circle',
                                    'autenticacao' => 'lock',
                                    default => 'info-circle'
                                };
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <i class="fas fa-<?php echo $icon; ?>"></i> <?php echo ucfirst(str_replace('_', ' ', $tipo)); ?>
                                </span>
                            </td>
                            <td>
                                <small><?php echo htmlspecialchars(substr($log['mensagem'], 0, 80)); ?></small>
                                <?php if (strlen($log['mensagem']) > 80): ?>
                                    <span class="text-muted">...</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($log['idemail'])): ?>
                                    <code><?php echo $log['idemail']; ?></code>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" 
                                        onclick="verDetalhesLog(<?php echo $log['idlog']; ?>)"
                                        data-bs-toggle="tooltip" title="Ver Detalhes">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            Nenhum log encontrado
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Paginação -->
<?php if (!empty($paginacao)): ?>
    <nav aria-label="Paginação" class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if ($paginacao['pagina'] > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?pagina=1">Primeira</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="?pagina=<?php echo $paginacao['pagina'] - 1; ?>">Anterior</a>
                </li>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $paginacao['paginas_totais']; $i++): ?>
                <li class="page-item <?php echo $i === $paginacao['pagina'] ? 'active' : ''; ?>">
                    <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($paginacao['pagina'] < $paginacao['paginas_totais']): ?>
                <li class="page-item">
                    <a class="page-link" href="?pagina=<?php echo $paginacao['pagina'] + 1; ?>">Próxima</a>
                </li>
                <li class="page-item">
                    <a class="page-link" href="?pagina=<?php echo $paginacao['paginas_totais']; ?>">Última</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
<?php endif; ?>

<!-- Modal de Detalhes -->
<div class="modal fade" id="detalhesLogModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes do Log</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalhesLogContent">
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function verDetalhesLog(idlog) {
        const modal = new bootstrap.Modal(document.getElementById('detalhesLogModal'));
        const content = document.getElementById('detalhesLogContent');
        
        // Aqui você faria uma requisição para obter os detalhes do log
        // Por enquanto, vou mostrar um exemplo
        content.innerHTML = `
            <div class="mb-3">
                <strong>ID do Log:</strong><br>
                <code>${idlog}</code>
            </div>
            <div class="mb-3">
                <strong>Tipo:</strong><br>
                <span class="badge bg-info">Envio de E-mail</span>
            </div>
            <div class="mb-3">
                <strong>Mensagem:</strong><br>
                E-mail enviado com sucesso para usuario@example.com
            </div>
            <div class="mb-3">
                <strong>Data/Hora:</strong><br>
                ${new Date().toLocaleString('pt-BR')}
            </div>
            <div class="mb-3">
                <strong>Dados Adicionais:</strong><br>
                <pre class="bg-light p-2 rounded"><code>{
  "idemail": 123,
  "idsistema": 1,
  "destinatario": "usuario@example.com",
  "status": "enviado"
}</code></pre>
            </div>
        `;
        
        modal.show();
    }

    function aplicarFiltros() {
        const tipo = document.getElementById('filtroTipo').value;
        const dataInicial = document.getElementById('filtroDataInicial').value;
        const dataFinal = document.getElementById('filtroDataFinal').value;
        const busca = document.getElementById('filtroBusca').value;
        
        const params = new URLSearchParams();
        if (tipo) params.append('tipo', tipo);
        if (dataInicial) params.append('data_inicial', dataInicial);
        if (dataFinal) params.append('data_final', dataFinal);
        if (busca) params.append('busca', busca);
        
        window.location.href = '<?php echo $base; ?>/logs?' + params.toString();
    }

    function limparFiltros() {
        document.getElementById('filtroTipo').value = '';
        document.getElementById('filtroDataInicial').value = '';
        document.getElementById('filtroDataFinal').value = '';
        document.getElementById('filtroBusca').value = '';
        window.location.href = '<?php echo $base; ?>/logs';
    }
</script>

<?php $render('footer'); ?>
