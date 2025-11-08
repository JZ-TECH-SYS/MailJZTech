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
                    <option value="envio">Envio</option>
                    <option value="criacao">Criação</option>
                    <option value="atualizacao">Atualização</option>
                    <option value="erro">Erro</option>
                    <option value="autenticacao">Autenticação</option>
                    <option value="validacao">Validação</option>
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
        <table class="table table-hover mb-0" id="tabelaLogs">
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
                <tr>
                    <td colspan="5" class="text-center py-5">
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

<!-- Paginação -->
<nav aria-label="Paginação" class="mt-4">
    <ul class="pagination justify-content-center" id="paginacaoLogs">
    </ul>
</nav>

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
    let paginaAtual = 1;
    const limitePorPagina = 20;

    // Carregar logs via AJAX
    async function carregarLogs(pagina = 1) {
        try {
            const tbody = document.querySelector('#tabelaLogs tbody');
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="mt-3 text-muted">Carregando logs...</p>
                    </td>
                </tr>
            `;

            // Construir URL com filtros
            let url = `<?php echo $base; ?>/api/logs/listar?pagina=${pagina}&limite=${limitePorPagina}`;
            
            const tipo = document.getElementById('filtroTipo').value;
            const dataInicial = document.getElementById('filtroDataInicial').value;
            const dataFinal = document.getElementById('filtroDataFinal').value;
            const busca = document.getElementById('filtroBusca').value;
            
            if (tipo) url += `&tipo=${encodeURIComponent(tipo)}`;
            if (dataInicial) url += `&data_inicial=${encodeURIComponent(dataInicial)}`;
            if (dataFinal) url += `&data_final=${encodeURIComponent(dataFinal)}`;
            if (busca) url += `&busca=${encodeURIComponent(busca)}`;

            const response = await fetchComToken(url);
            const data = await response.json();
            
            if (!data.error && data.result && data.result.logs) {
                renderizarLogs(data.result.logs);
                renderizarPaginacao(data.result.paginas_totais || 1, pagina);
                paginaAtual = pagina;
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Nenhum log encontrado</p>
                        </td>
                    </tr>
                `;
                document.getElementById('paginacaoLogs').innerHTML = '';
            }
        } catch (error) {
            console.error('Erro ao carregar logs:', error);
            const tbody = document.querySelector('#tabelaLogs tbody');
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-4">
                        <i class="fas fa-times-circle text-danger fa-3x mb-3"></i>
                        <p class="text-danger">Erro ao carregar logs: ${error.message}</p>
                        <button class="btn btn-primary" onclick="carregarLogs(${paginaAtual})">
                            <i class="fas fa-sync"></i> Tentar Novamente
                        </button>
                    </td>
                </tr>
            `;
        }
    }

    function renderizarLogs(logs) {
        const tbody = document.querySelector('#tabelaLogs tbody');
        
        if (logs.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Nenhum log encontrado</p>
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = logs.map(log => {
            const tipo = log.tipo_log || 'outro';
            const {badgeClass, icon} = getTipoBadge(tipo);
            const mensagem = escapeHtml(log.mensagem || '');
            const mensagemCurta = mensagem.length > 80 ? mensagem.substring(0, 80) + '...' : mensagem;
            
            return `
                <tr>
                    <td>
                        <small class="text-muted">
                            ${formatarDataHora(log.data_log)}
                        </small>
                    </td>
                    <td>
                        <span class="badge ${badgeClass}">
                            <i class="fas fa-${icon}"></i> ${formatarTipo(tipo)}
                        </span>
                    </td>
                    <td>
                        <small>${mensagemCurta}</small>
                    </td>
                    <td>
                        ${log.idemail ? `<code>${log.idemail}</code>` : '<span class="text-muted">-</span>'}
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" 
                                onclick="verDetalhesLog(${log.idlog})"
                                title="Ver Detalhes">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function renderizarPaginacao(totalPaginas, paginaAtiva) {
        const paginacao = document.getElementById('paginacaoLogs');
        
        if (totalPaginas <= 1) {
            paginacao.innerHTML = '';
            return;
        }

        let html = `
            <li class="page-item ${paginaAtiva === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="carregarLogs(${paginaAtiva - 1}); return false;">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
        `;

        for (let i = 1; i <= totalPaginas; i++) {
            if (i === 1 || i === totalPaginas || (i >= paginaAtiva - 2 && i <= paginaAtiva + 2)) {
                html += `
                    <li class="page-item ${i === paginaAtiva ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="carregarLogs(${i}); return false;">${i}</a>
                    </li>
                `;
            } else if (i === paginaAtiva - 3 || i === paginaAtiva + 3) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        html += `
            <li class="page-item ${paginaAtiva === totalPaginas ? 'disabled' : ''}">
                <a class="page-link" href="#" onclick="carregarLogs(${paginaAtiva + 1}); return false;">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        `;

        paginacao.innerHTML = html;
    }

    function getTipoBadge(tipo) {
        const tipos = {
            'envio': {badgeClass: 'badge-info', icon: 'envelope'},
            'criacao': {badgeClass: 'badge-success', icon: 'plus-circle'},
            'atualizacao': {badgeClass: 'badge-primary', icon: 'edit'},
            'erro': {badgeClass: 'badge-danger', icon: 'exclamation-circle'},
            'autenticacao': {badgeClass: 'badge-warning', icon: 'lock'},
            'validacao': {badgeClass: 'badge-info', icon: 'check-circle'}
        };
        return tipos[tipo] || {badgeClass: 'badge-secondary', icon: 'info-circle'};
    }

    function formatarTipo(tipo) {
        return tipo.split('_').map(palavra => 
            palavra.charAt(0).toUpperCase() + palavra.slice(1)
        ).join(' ');
    }

    function formatarDataHora(dataString) {
        if (!dataString) return 'N/A';
        const data = new Date(dataString);
        return data.toLocaleString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        });
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text?.toString().replace(/[&<>"']/g, m => map[m]) || '';
    }

    async function verDetalhesLog(idlog) {
        const modal = new bootstrap.Modal(document.getElementById('detalhesLogModal'));
        const content = document.getElementById('detalhesLogContent');
        
        content.innerHTML = `
            <div class="text-center">
                <div class="spinner-border" role="status"></div>
                <p class="mt-3 text-muted">Carregando detalhes...</p>
            </div>
        `;
        
        modal.show();

        try {
            const response = await fetchComToken(`<?php echo $base; ?>/api/logs/detalhe/${idlog}`);
            const data = await response.json();
            
            if (!data.error && data.result) {
                const log = data.result;
                const {badgeClass, icon} = getTipoBadge(log.tipo);
                
                content.innerHTML = `
                    <div class="mb-3">
                        <strong>ID do Log:</strong><br>
                        <code>${log.idlog}</code>
                    </div>
                    <div class="mb-3">
                        <strong>Tipo:</strong><br>
                        <span class="badge ${badgeClass}">
                            <i class="fas fa-${icon}"></i> ${formatarTipo(log.tipo_log)}
                        </span>
                    </div>
                    <div class="mb-3">
                        <strong>Mensagem:</strong><br>
                        ${escapeHtml(log.mensagem)}
                    </div>
                    <div class="mb-3">
                        <strong>Data/Hora:</strong><br>
                        ${formatarDataHora(log.data_log)}
                    </div>
                    ${log.idemail ? `
                        <div class="mb-3">
                            <strong>E-mail ID:</strong><br>
                            <code>${log.idemail}</code>
                        </div>
                    ` : ''}
                    ${log.dados_adicionais ? `
                        <div class="mb-3">
                            <strong>Dados Adicionais:</strong><br>
                            <pre class="bg-light p-2 rounded"><code>${JSON.stringify(JSON.parse(log.dados_adicionais), null, 2)}</code></pre>
                        </div>
                    ` : ''}
                `;
            } else {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        Erro ao carregar detalhes do log
                    </div>
                `;
            }
        } catch (error) {
            content.innerHTML = `
                <div class="alert alert-danger">
                    Erro: ${error.message}
                </div>
            `;
        }
    }

    function aplicarFiltros() {
        carregarLogs(1);
    }

    function limparFiltros() {
        document.getElementById('filtroTipo').value = '';
        document.getElementById('filtroDataInicial').value = '';
        document.getElementById('filtroDataFinal').value = '';
        document.getElementById('filtroBusca').value = '';
        carregarLogs(1);
    }

    // Carregar ao iniciar
    document.addEventListener('DOMContentLoaded', () => carregarLogs(1));
</script>

<?php $render('footer'); ?>
