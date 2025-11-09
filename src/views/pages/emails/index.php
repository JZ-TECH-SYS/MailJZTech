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

        let url = `<?php echo $base; ?>/listarEmails?limite=${limite}&pagina=${pagina}`;
        
        // Adicionar filtros
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
            renderizarEmails(data.result.emails || []);
            renderizarPaginacao(data.result.paginas_totais || 1, pagina);
            atualizarEstatisticas(data.result);
        } else {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4">
                        <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                        <p class="text-muted">${data.result || 'Erro ao carregar e-mails'}</p>
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
        const dataFormatada = formatarData(email.data_envio);
        
        return `
            <tr>
                <td>#${email.idemail}</td>
                <td>${email.sistema_nome || 'N/A'}</td>
                <td>${email.destinatario}</td>
                <td>${truncate(email.assunto, 50)}</td>
                <td>${statusBadge}</td>
                <td>${dataFormatada}</td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="verDetalhes(${email.idemail})">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
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

// Ver detalhes do e-mail
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
        const response = await fetchComToken(`<?php echo $base; ?>/detalheEmail?idemail=${idemail}`);
        const data = await response.json();

        if (!data.error && data.result) {
            const email = data.result;
            body.innerHTML = `
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <strong>ID:</strong><br>${email.idemail}
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Status:</strong><br>${getStatusBadge(email.status)}
                    </div>
                    <div class="col-md-12 mb-3">
                        <strong>Destinatário:</strong><br>${email.destinatario}
                    </div>
                    <div class="col-md-12 mb-3">
                        <strong>Assunto:</strong><br>${email.assunto}
                    </div>
                    <div class="col-md-12 mb-3">
                        <strong>Data de Envio:</strong><br>${formatarData(email.data_envio)}
                    </div>
                    <div class="col-md-12 mb-3">
                        <strong>Corpo do E-mail:</strong>
                        <div class="border rounded p-3 mt-2" style="max-height: 300px; overflow-y: auto;">
                            ${email.corpo_html || email.corpo_texto || 'N/A'}
                        </div>
                    </div>
                    ${email.mensagem_erro ? `
                        <div class="col-md-12">
                            <div class="alert alert-danger">
                                <strong><i class="fas fa-exclamation-triangle"></i> Erro:</strong><br>
                                ${email.mensagem_erro}
                            </div>
                        </div>
                    ` : ''}
                </div>
            `;
        } else {
            body.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle"></i> ${data.result || 'Erro ao carregar detalhes'}
                </div>
            `;
        }
    } catch (error) {
        body.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-times-circle"></i> Erro: ${error.message}
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
        'enviado': '<span class="badge badge-success">Enviado</span>',
        'erro': '<span class="badge badge-danger">Erro</span>',
        'pendente': '<span class="badge badge-warning">Pendente</span>'
    };
    return badges[status] || '<span class="badge badge-secondary">Desconhecido</span>';
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

// Inicializar ao carregar
document.addEventListener('DOMContentLoaded', () => {
    carregarSistemas();
    carregarEmails(1);
});
</script>

<?php $render('footer'); ?>
