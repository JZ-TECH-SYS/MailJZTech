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
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient border-bottom-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h5 class="modal-title text-white">
                    <i class="fas fa-file-alt"></i> Detalhes do Log
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="detalhesLogContent" style="max-height: 70vh; overflow-y: auto;">
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
            let url = `<?php echo $base; ?>/logs/listar?pagina=${pagina}&limite=${limitePorPagina}`;
            
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
            'envio': {badgeClass: 'bg-info', icon: 'envelope'},
            'criacao': {badgeClass: 'bg-success', icon: 'plus-circle'},
            'atualizacao': {badgeClass: 'bg-primary', icon: 'edit'},
            'erro': {badgeClass: 'bg-danger', icon: 'exclamation-circle'},
            'autenticacao': {badgeClass: 'bg-warning text-dark', icon: 'lock'},
            'validacao': {badgeClass: 'bg-info', icon: 'check-circle'}
        };
        return tipos[tipo] || {badgeClass: 'bg-secondary', icon: 'info-circle'};
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
            const response = await fetchComToken(`<?php echo $base; ?>/logs/detalhe/${idlog}`);
            const data = await response.json();
            
            if (!data.error && data.result) {
                const log = data.result;
                const {badgeClass, icon} = getTipoBadge(log.tipo_log || log.tipo);
                
                let dadosAdicionais = '';
                if (log.dados_adicionais) {
                    try {
                        const parsed = typeof log.dados_adicionais === 'string' 
                            ? JSON.parse(log.dados_adicionais)
                            : log.dados_adicionais;
                        const jsonStr = JSON.stringify(parsed, null, 2);
                        
                        // Syntax highlighting simples
                        let highlighted = escapeHtml(jsonStr)
                            .replace(/&quot;([^&]*?)&quot;:/g, '<span class="text-danger">&quot;$1&quot;</span>:')
                            .replace(/: &quot;([^&]*)&quot;/g, ': <span class="text-success">&quot;$1&quot;</span>')
                            .replace(/: (\d+)/g, ': <span class="text-info">$1</span>')
                            .replace(/: (true|false)/g, ': <span class="text-warning">$1</span>');
                        
                        dadosAdicionais = `
                            <div class="card border-0 border-top border-warning mb-0">
                                <div class="card-header bg-white border-0 pt-3">
                                    <h6 class="mb-0">
                                        <i class="fas fa-code text-warning"></i> Dados Adicionais
                                    </h6>
                                </div>
                                <div class="card-body pt-0">
                                    <div style="background:#1e1e1e; border:1px solid #404040; border-radius:0.375rem; padding:1rem; max-height:300px; overflow-y:auto; font-family: 'Courier New', monospace;">
                                        <pre style="margin:0; color:#d4d4d4; font-size:0.85rem; line-height:1.5;"><code>${highlighted}</code></pre>
                                    </div>
                                </div>
                            </div>
                        `;
                    } catch (e) {
                        // Se não conseguir fazer parse, exibe como texto
                        if (log.dados_adicionais) {
                            dadosAdicionais = `
                                <div class="card border-0 border-top border-warning mb-0">
                                    <div class="card-header bg-white border-0 pt-3">
                                        <h6 class="mb-0">
                                            <i class="fas fa-code text-warning"></i> Dados Adicionais
                                        </h6>
                                    </div>
                                    <div class="card-body pt-0">
                                        <div style="background:#1e1e1e; border:1px solid #404040; border-radius:0.375rem; padding:1rem; max-height:300px; overflow-y:auto; font-family: 'Courier New', monospace;">
                                            <pre style="margin:0; color:#d4d4d4; font-size:0.85rem; line-height:1.5;"><code>${escapeHtml(String(log.dados_adicionais))}</code></pre>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }
                    }
                }
                
                content.innerHTML = `
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card border-0 border-start border-danger border-4">
                                <div class="card-body">
                                    <p class="card-text text-muted small mb-2"><i class="fas fa-hashtag text-danger"></i> ID do Log</p>
                                    <h6 class="text-dark mb-0"><code class="text-danger" style="font-size: 1.1rem;">#${log.idlog}</code></h6>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card border-0 border-start border-info border-4">
                                <div class="card-body">
                                    <p class="card-text text-muted small mb-2"><i class="fas fa-tag text-info"></i> Tipo</p>
                                    <span class="badge ${badgeClass} p-2">
                                        <i class="fas fa-${icon}"></i> ${formatarTipo(log.tipo_log || log.tipo)}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 border-start border-warning border-4 mb-3" style="background: rgba(255, 193, 7, 0.15);">
                        <div class="card-body">
                            <p class="card-text text-muted small mb-2"><i class="fas fa-calendar-clock text-warning"></i> Data/Hora</p>
                            <h6 class="mb-0" style="font-size: 1.05rem; font-weight: 600; color: #ffc107;">${formatarDataHora(log.data_log)}</h6>
                        </div>
                    </div>

                    <div class="card border-0 border-start border-success border-4 mb-3" style="background: rgba(40, 167, 69, 0.15);">
                        <div class="card-body">
                            <p class="card-text text-muted small mb-2"><i class="fas fa-message text-success"></i> Mensagem</p>
                            <p class="mb-0" style="font-size: 1rem; line-height: 1.6; font-weight: 500; color: #28a745;">${escapeHtml(log.mensagem)}</p>
                        </div>
                    </div>

                    <div class="row">
                        ${log.idemail ? `
                            <div class="col-md-4 mb-3">
                                <div class="card border-0 border-start border-danger border-4">
                                    <div class="card-body">
                                        <p class="card-text text-muted small mb-2"><i class="fas fa-envelope text-danger"></i> E-mail ID</p>
                                        <h6 class="text-dark mb-0"><code class="text-danger">#${log.idemail}</code></h6>
                                    </div>
                                </div>
                            </div>
                        ` : ''}
                        ${log.idsistema ? `
                            <div class="col-md-4 mb-3">
                                <div class="card border-0 border-start border-info border-4">
                                    <div class="card-body">
                                        <p class="card-text text-muted small mb-2"><i class="fas fa-cogs text-info"></i> Sistema ID</p>
                                        <h6 class="text-dark mb-0"><code class="text-info">#${log.idsistema}</code></h6>
                                    </div>
                                </div>
                            </div>
                        ` : ''}
                        ${log.idusuario ? `
                            <div class="col-md-4 mb-3">
                                <div class="card border-0 border-start border-secondary border-4">
                                    <div class="card-body">
                                        <p class="card-text text-muted small mb-2"><i class="fas fa-user text-secondary"></i> Usuário ID</p>
                                        <h6 class="text-dark mb-0"><code class="text-secondary">#${log.idusuario}</code></h6>
                                    </div>
                                </div>
                            </div>
                        ` : ''}
                    </div>

                    ${dadosAdicionais ? `
                        <div class="mt-4">
                            ${dadosAdicionais}
                        </div>
                    ` : ''}
                `;
            } else {
                content.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle"></i> ${escapeHtml(data.result?.mensagem || 'Erro ao carregar detalhes do log')}
                    </div>
                `;
            }
        } catch (error) {
            content.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle"></i> Erro: ${escapeHtml(error.message)}
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
