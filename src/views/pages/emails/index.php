<?php $render('header'); ?>

<div class="container-fluid py-4 fade-in">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0">
                <i class="fas fa-envelope"></i> Gerenciamento de E-mails
            </h2>
            <p class="text-muted">Histórico e estatísticas de e-mails enviados</p>
        </div>
    </div>

    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-md-3">
            <label class="form-label">Sistema</label>
            <select class="form-select" id="filtroSistema">
                <option value="">Todos os sistemas</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Status</label>
            <select class="form-select" id="filtroStatus">
                <option value="">Todos os status</option>
                <option value="enviado">Enviado</option>
                <option value="erro">Erro</option>
                <option value="pendente">Pendente</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Data Inicial</label>
            <input type="date" class="form-control" id="dataInicial">
        </div>
        <div class="col-md-3">
            <label class="form-label">Data Final</label>
            <input type="date" class="form-control" id="dataFinal">
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <button class="btn btn-primary" onclick="filtrarEmails()">
                <i class="fas fa-search"></i> Filtrar
            </button>
            <button class="btn btn-secondary" onclick="limparFiltros()">
                <i class="fas fa-eraser"></i> Limpar Filtros
            </button>
        </div>
    </div>

    <!-- Estatísticas Rápidas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Total de E-mails</h6>
                            <h3 class="mb-0" id="totalEmails">0</h3>
                        </div>
                        <i class="fas fa-envelope stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-success stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Enviados</h6>
                            <h3 class="mb-0 text-success" id="totalEnviados">0</h3>
                        </div>
                        <i class="fas fa-check-circle stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-danger stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Erros</h6>
                            <h3 class="mb-0 text-danger" id="totalErros">0</h3>
                        </div>
                        <i class="fas fa-times-circle stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-warning stat-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-1">Taxa de Sucesso</h6>
                            <h3 class="mb-0 text-warning" id="taxaSucesso">0%</h3>
                        </div>
                        <i class="fas fa-chart-pie stat-icon"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabela de E-mails -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Histórico de E-mails</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="tabelaEmails">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Sistema</th>
                                    <th>Destinatário</th>
                                    <th>Assunto</th>
                                    <th>Status</th>
                                    <th>Data de Envio</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Carregando...</span>
                                        </div>
                                        <p class="mt-3 text-muted">Carregando e-mails...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    <nav aria-label="Paginação de e-mails" class="mt-4">
                        <ul class="pagination justify-content-center" id="paginacao">
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Detalhes do E-mail -->
<div class="modal fade" id="modalDetalhesEmail" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-envelope-open-text"></i> Detalhes do E-mail
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalhesEmailBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-3 text-muted">Carregando detalhes...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Fechar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let paginaAtual = 1;
const limite = 20;

// Carregar sistemas para o filtro
async function carregarSistemas() {
    try {
        const response = await fetchComToken('<?php echo $base; ?>/listarSistemas');
        const data = await response.json();
        
        if (!data.error && data.result) {
            const select = document.getElementById('filtroSistema');
            data.result.forEach(sistema => {
                const option = document.createElement('option');
                option.value = sistema.idsistema;
                option.textContent = sistema.nome;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Erro ao carregar sistemas:', error);
    }
}

// Carregar e-mails
async function carregarEmails(pagina = 1) {
    try {
        const tbody = document.querySelector('#tabelaEmails tbody');
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="mt-3 text-muted">Carregando e-mails...</p>
                </td>
            </tr>
        `;

        // Construir URL com filtros
        let url = `<?php echo $base; ?>/listarEmails?limite=${limite}&pagina=${pagina}`;
        
        const idsistema = document.getElementById('filtroSistema').value;
        const status = document.getElementById('filtroStatus').value;
        const dataInicial = document.getElementById('dataInicial').value;
        const dataFinal = document.getElementById('dataFinal').value;
        
        if (idsistema) url += `&idsistema=${idsistema}`;
        if (status) url += `&status=${status}`;
        if (dataInicial) url += `&data_inicial=${dataInicial}`;
        if (dataFinal) url += `&data_final=${dataFinal}`;

        const response = await fetchComToken(url);
        const data = await response.json();

        if (!data.error && data.result) {
            paginaAtual = pagina;
            renderizarEmails(data.result.emails || []);
            renderizarPaginacao(data.result.paginas_totais || 1, pagina);
            atualizarEstatisticas(data.result);
        } else {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">Nenhum e-mail encontrado com os filtros aplicados</p>
                        <button class="btn btn-sm btn-secondary" onclick="limparFiltros()">
                            <i class="fas fa-eraser"></i> Limpar Filtros
                        </button>
                    </td>
                </tr>
            `;
        }
    } catch (error) {
        console.error('Erro ao carregar e-mails:', error);
        const tbody = document.querySelector('#tabelaEmails tbody');
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4">
                    <i class="fas fa-times-circle text-danger fa-3x mb-3"></i>
                    <p class="text-danger">Erro ao carregar e-mails: ${error.message}</p>
                    <button class="btn btn-sm btn-primary" onclick="carregarEmails(${paginaAtual})">
                        <i class="fas fa-redo"></i> Tentar Novamente
                    </button>
                </td>
            </tr>
        `;
    }
}

// Renderizar e-mails na tabela
function renderizarEmails(emails) {
    const tbody = document.querySelector('#tabelaEmails tbody');
    
    if (emails.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center py-4">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Nenhum e-mail encontrado</p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = emails.map(email => {
        const statusBadge = getStatusBadge(email.status);
        const dataFormatada = formatarData(email.data_envio || email.data_criacao);
        
        return `
            <tr>
                <td>#${email.idemail}</td>
                <td>${email.sistema_nome || email.idsistema || 'N/A'}</td>
                <td>${escapeHtml(email.destinatario)}</td>
                <td>${escapeHtml(truncate(email.assunto, 50))}</td>
                <td>${statusBadge}</td>
                <td>${dataFormatada}</td>
                <td>
                    <button class="btn btn-sm btn-info btn-ver-detalhe" data-idemail="${email.idemail}">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
    
    // Adicionar event listeners nos botões de detalhe
    tbody.querySelectorAll('.btn-ver-detalhe').forEach(btn => {
        btn.addEventListener('click', () => {
            verDetalhes(btn.getAttribute('data-idemail'));
        });
    });
}

// Renderizar paginação
function renderizarPaginacao(totalPaginas, paginaAtiva) {
    const paginacao = document.getElementById('paginacao');
    
    if (totalPaginas <= 1) {
        paginacao.innerHTML = '';
        return;
    }

    let html = `
        <li class="page-item ${paginaAtiva === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="carregarEmails(${paginaAtiva - 1}); return false;">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
    `;

    for (let i = 1; i <= totalPaginas; i++) {
        if (i === 1 || i === totalPaginas || (i >= paginaAtiva - 2 && i <= paginaAtiva + 2)) {
            html += `
                <li class="page-item ${i === paginaAtiva ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="carregarEmails(${i}); return false;">${i}</a>
                </li>
            `;
        } else if (i === paginaAtiva - 3 || i === paginaAtiva + 3) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    html += `
        <li class="page-item ${paginaAtiva === totalPaginas ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="carregarEmails(${paginaAtiva + 1}); return false;">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    `;

    paginacao.innerHTML = html;
}

// Atualizar estatísticas
function atualizarEstatisticas(dados) {
    document.getElementById('totalEmails').textContent = dados.total || 0;
    document.getElementById('totalEnviados').textContent = dados.enviados || 0;
    document.getElementById('totalErros').textContent = dados.erros || 0;
    
    const taxa = dados.total > 0 ? ((dados.enviados / dados.total) * 100).toFixed(1) : 0;
    document.getElementById('taxaSucesso').textContent = taxa + '%';
}

// Ver detalhes do e-mail via AJAX/Modal
async function verDetalhes(idemail) {
    const modal = new bootstrap.Modal(document.getElementById('modalDetalhesEmail'));
    const body = document.getElementById('detalhesEmailBody');
    
    body.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-3 text-muted">Carregando detalhes...</p>
        </div>
    `;
    
    modal.show();

    try {
        const response = await fetchComToken(`<?php echo $base; ?>/detalheEmail/${idemail}`);
        const data = await response.json();

        if (!data.error && data.result) {
            const email = data.result;
            
            // Processar corpo do e-mail
            let corpoEmail = 'Sem conteúdo';
            if (email.corpo_html && email.corpo_html.trim() !== '') {
                corpoEmail = email.corpo_html;
            } else if (email.corpo_texto && email.corpo_texto.trim() !== '') {
                corpoEmail = `<pre style="white-space: pre-wrap; word-wrap: break-word; font-family: inherit;">${escapeHtml(email.corpo_texto)}</pre>`;
            }
            
            body.innerHTML = `
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <strong><i class="fas fa-hashtag"></i> ID:</strong><br>
                        <span class="text-muted">#${email.idemail}</span>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong><i class="fas fa-info-circle"></i> Status:</strong><br>
                        ${getStatusBadge(email.status)}
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong><i class="fas fa-cogs"></i> Sistema:</strong><br>
                        <span class="text-muted">${email.idsistema || 'N/A'}</span>
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong><i class="fas fa-calendar-plus"></i> Data de Criação:</strong><br>
                        <span class="text-muted">${formatarData(email.data_criacao)}</span>
                    </div>
                    <div class="col-md-12 mb-3">
                        <strong><i class="fas fa-user"></i> Destinatário:</strong><br>
                        <span class="text-muted">${escapeHtml(email.destinatario)}</span>
                    </div>
                    ${email.cc ? `
                        <div class="col-md-12 mb-3">
                            <strong><i class="fas fa-users"></i> CC:</strong><br>
                            <span class="text-muted">${escapeHtml(email.cc)}</span>
                        </div>
                    ` : ''}
                    ${email.bcc ? `
                        <div class="col-md-12 mb-3">
                            <strong><i class="fas fa-user-secret"></i> BCC:</strong><br>
                            <span class="text-muted">${escapeHtml(email.bcc)}</span>
                        </div>
                    ` : ''}
                    <div class="col-md-12 mb-3">
                        <strong><i class="fas fa-heading"></i> Assunto:</strong><br>
                        <span class="text-muted">${escapeHtml(email.assunto)}</span>
                    </div>
                    <div class="col-md-12 mb-3">
                        <strong><i class="fas fa-paper-plane"></i> Data de Envio:</strong><br>
                        <span class="text-muted">${formatarData(email.data_envio)}</span>
                    </div>
                    <div class="col-md-12 mb-3">
                        <strong><i class="fas fa-envelope-open-text"></i> Corpo do E-mail:</strong>
                        <div class="border rounded p-3 mt-2 bg-white" style="max-height: 400px; overflow-y: auto;">
                            ${corpoEmail}
                        </div>
                    </div>
                    ${email.mensagem_erro ? `
                        <div class="col-md-12">
                            <div class="alert alert-danger mb-0">
                                <strong><i class="fas fa-exclamation-triangle"></i> Erro de Envio:</strong><br>
                                <code>${escapeHtml(email.mensagem_erro)}</code>
                            </div>
                        </div>
                    ` : ''}
                </div>
            `;
        } else {
            body.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle"></i> ${escapeHtml(data.result?.mensagem || 'Erro ao carregar detalhes')}
                </div>
            `;
        }
    } catch (error) {
        body.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-times-circle"></i> Erro: ${escapeHtml(error.message)}
            </div>
        `;
    }
}

// Filtrar e-mails
function filtrarEmails() {
    carregarEmails(1);
}

// Limpar filtros
function limparFiltros() {
    document.getElementById('filtroSistema').value = '';
    document.getElementById('filtroStatus').value = '';
    document.getElementById('dataInicial').value = '';
    document.getElementById('dataFinal').value = '';
    carregarEmails(1);
}

// Helpers
function getStatusBadge(status) {
    const badges = {
        'enviado': '<span class="badge bg-success">Enviado</span>',
        'erro': '<span class="badge bg-danger">Erro</span>',
        'pendente': '<span class="badge bg-warning text-dark">Pendente</span>'
    };
    return badges[status] || '<span class="badge bg-secondary">Desconhecido</span>';
}

function formatarData(dataString) {
    if (!dataString) return 'N/A';
    const data = new Date(dataString);
    return data.toLocaleString('pt-BR');
}

function truncate(str, length) {
    if (!str) return '';
    return str.length > length ? str.substring(0, length) + '...' : str;
}

function escapeHtml(text) {
    if (!text) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
}

// Inicializar ao carregar
document.addEventListener('DOMContentLoaded', () => {
    carregarSistemas();
    carregarEmails(1);
});
</script>

<?php $render('footer'); ?>
