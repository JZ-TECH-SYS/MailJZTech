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
                <option value="aceito">Aceito</option>
                <option value="pendente">Pendente</option>
                <option value="processando">Processando</option>
                <option value="falha">Falha</option>
                <option value="rejeitado">Rejeitado</option>
                <option value="bounce">Bounce</option>
                <option value="erro">Erro</option>
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
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-success" id="btnReenviarModal" disabled>
                    <i class="fas fa-redo"></i> Reenviar E-mail
                </button>
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
        const response = await fetchComToken('/listarSistemas');
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
        let url = `/listarEmails?limite=${limite}&pagina=${pagina}`;
        
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
                    <div class="btn-group" role="group">
                        <button class="btn btn-sm btn-info btn-ver-detalhe" data-idemail="${email.idemail}" title="Ver detalhes">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-success btn-reenviar" data-idemail="${email.idemail}" title="Reenviar e-mail">
                            <i class="fas fa-redo"></i>
                        </button>
                    </div>
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
    
    // Adicionar event listeners nos botões de reenviar
    tbody.querySelectorAll('.btn-reenviar').forEach(btn => {
        btn.addEventListener('click', () => {
            reenviarEmail(btn.getAttribute('data-idemail'));
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
        const response = await fetchComToken(`/detalheEmail/${idemail}`);
        const data = await response.json();

        if (!data.error && data.result) {
            const email = data.result;
            
            // Processar corpo do e-mail - usando iframe para isolar o HTML
            let corpoEmailHtml = '';
            let usarIframe = false;
            
            if (email.corpo_html && email.corpo_html.trim() !== '') {
                usarIframe = true;
                // Escapar aspas e barras invertidas para o srcdoc
                corpoEmailHtml = email.corpo_html
                    .replace(/\\/g, '\\\\')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#39;');
            } else if (email.corpo_texto && email.corpo_texto.trim() !== '') {
                corpoEmailHtml = `<pre style="white-space: pre-wrap; word-wrap: break-word; font-family: monospace; color: #333; margin: 0;">${escapeHtml(email.corpo_texto)}</pre>`;
                usarIframe = true;
            }
            
            // Calcular tamanho se não vier do backend
            let tamanhoExibir = email.tamanho_bytes;
            if (!tamanhoExibir && email.corpo_html) {
                tamanhoExibir = new Blob([email.corpo_html]).size;
            }
            
            body.innerHTML = `
                <div class="row g-3">
                    <!-- ID e Status -->
                    <div class="col-md-6">
                        <div class="detail-card">
                            <div class="detail-label"><i class="fas fa-hashtag text-info"></i> ID</div>
                            <div class="detail-value">#${email.idemail}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-card">
                            <div class="detail-label"><i class="fas fa-info-circle text-info"></i> Status</div>
                            <div class="detail-value">${getStatusBadge(email.status)}</div>
                        </div>
                    </div>
                    
                    <!-- Sistema e Data Criação -->
                    <div class="col-md-6">
                        <div class="detail-card">
                            <div class="detail-label"><i class="fas fa-cogs text-warning"></i> Sistema</div>
                            <div class="detail-value">${email.idsistema || 'N/A'}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-card">
                            <div class="detail-label"><i class="fas fa-calendar-plus text-success"></i> Data de Criação</div>
                            <div class="detail-value">${formatarData(email.data_criacao)}</div>
                        </div>
                    </div>
                    
                    <!-- Destinatário -->
                    <div class="col-12">
                        <div class="detail-card">
                            <div class="detail-label"><i class="fas fa-user text-danger"></i> Destinatário</div>
                            <div class="detail-value">${escapeHtml(email.destinatario)}</div>
                        </div>
                    </div>
                    
                    ${email.cc ? `
                    <div class="col-12">
                        <div class="detail-card">
                            <div class="detail-label"><i class="fas fa-users text-secondary"></i> CC</div>
                            <div class="detail-value">${escapeHtml(email.cc)}</div>
                        </div>
                    </div>
                    ` : ''}
                    
                    ${email.bcc ? `
                    <div class="col-12">
                        <div class="detail-card">
                            <div class="detail-label"><i class="fas fa-user-secret text-secondary"></i> BCC</div>
                            <div class="detail-value">${escapeHtml(email.bcc)}</div>
                        </div>
                    </div>
                    ` : ''}
                    
                    <!-- Assunto -->
                    <div class="col-12">
                        <div class="detail-card">
                            <div class="detail-label"><i class="fas fa-heading text-primary"></i> Assunto</div>
                            <div class="detail-value">${escapeHtml(email.assunto)}</div>
                        </div>
                    </div>
                    
                    <!-- Data Envio e SMTP -->
                    <div class="col-md-6">
                        <div class="detail-card">
                            <div class="detail-label"><i class="fas fa-paper-plane text-success"></i> Data de Envio</div>
                            <div class="detail-value">${formatarData(email.data_envio)}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="detail-card">
                            <div class="detail-label"><i class="fas fa-server text-secondary"></i> Código SMTP</div>
                            <div class="detail-value"><code>${email.smtp_code || 'N/A'}</code></div>
                        </div>
                    </div>
                    
                    ${email.smtp_response ? `
                    <div class="col-12">
                        <div class="detail-card">
                            <div class="detail-label"><i class="fas fa-reply text-secondary"></i> Resposta SMTP</div>
                            <div class="detail-value"><code>${escapeHtml(email.smtp_response)}</code></div>
                        </div>
                    </div>
                    ` : ''}
                    
                    <!-- Tamanho e Tentativas -->
                    <div class="col-md-6">
                        <div class="detail-card">
                            <div class="detail-label"><i class="fas fa-weight text-info"></i> Tamanho</div>
                            <div class="detail-value">${tamanhoExibir ? formatarBytes(tamanhoExibir) : 'N/A'}</div>
                        </div>
                    </div>
                    ${email.tentativas ? `
                    <div class="col-md-6">
                        <div class="detail-card">
                            <div class="detail-label"><i class="fas fa-redo text-warning"></i> Tentativas</div>
                            <div class="detail-value">${email.tentativas}</div>
                        </div>
                    </div>
                    ` : ''}
                    
                    <!-- Corpo do E-mail -->
                    <div class="col-12">
                        <div class="detail-label mb-2"><i class="fas fa-envelope-open-text text-success"></i> Corpo do E-mail</div>
                        ${usarIframe && corpoEmailHtml ? `
                        <iframe 
                            class="email-iframe-preview" 
                            srcdoc="${corpoEmailHtml}"
                            sandbox="allow-same-origin"
                            title="Preview do E-mail"
                        ></iframe>
                        ` : `
                        <div class="email-body-preview">
                            <em class="text-muted">Sem conteúdo</em>
                        </div>
                        `}
                    </div>
                    
                    ${email.mensagem_erro ? `
                    <div class="col-12">
                        <div class="alert alert-danger mb-0">
                            <strong><i class="fas fa-exclamation-triangle"></i> Erro de Envio:</strong><br>
                            <code>${escapeHtml(email.mensagem_erro)}</code>
                        </div>
                    </div>
                    ` : ''}
                    
                    ${email.payload_original ? `
                    <div class="col-12 mt-3">
                        <div class="detail-card">
                            <div class="detail-label mb-2">
                                <i class="fas fa-code text-info"></i> Payload Original (Debug)
                                <button class="btn btn-sm btn-outline-info ms-2" onclick="togglePayload()">
                                    <i class="fas fa-eye" id="payloadToggleIcon"></i>
                                </button>
                            </div>
                            <div id="payloadContent" style="display: none;">
                                <pre class="mb-0" style="background: #0d1b2a; color: #00d9ff; padding: 1rem; border-radius: 0.5rem; font-size: 0.85rem; max-height: 300px; overflow: auto;">${(() => {
                                    try {
                                        const payload = typeof email.payload_original === 'string' 
                                            ? JSON.parse(email.payload_original) 
                                            : email.payload_original;
                                        return escapeHtml(JSON.stringify(payload, null, 2));
                                    } catch(e) {
                                        return escapeHtml(email.payload_original);
                                    }
                                })()}</pre>
                            </div>
                        </div>
                    </div>
                    ` : ''}
                </div>
            `;
            
            // Habilitar botão de reenviar no modal
            emailAtualModal = email.idemail;
            const btnReenviar = document.getElementById('btnReenviarModal');
            btnReenviar.disabled = false;
            btnReenviar.onclick = () => reenviarEmail(emailAtualModal);
            
        } else {
            body.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle"></i> ${escapeHtml(data.result?.mensagem || 'Erro ao carregar detalhes')}
                </div>
            `;
            // Desabilitar botão se erro
            document.getElementById('btnReenviarModal').disabled = true;
        }
    } catch (error) {
        body.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-times-circle"></i> Erro: ${escapeHtml(error.message)}
            </div>
        `;
    }
}

// Variável para armazenar o ID do e-mail atual no modal
let emailAtualModal = null;

// Reenviar e-mail
async function reenviarEmail(idemail) {
    // Confirmação bonita com SweetAlert2
    const confirmResult = await Swal.fire({
        title: 'Reenviar E-mail?',
        text: 'Deseja reenviar este e-mail para o destinatário?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#00d9ff',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-paper-plane"></i> Sim, reenviar!',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
        background: '#0d1b2a',
        color: '#fff',
        customClass: {
            popup: 'swal-dark-popup',
            confirmButton: 'btn btn-primary',
            cancelButton: 'btn btn-secondary'
        }
    });
    
    if (!confirmResult.isConfirmed) {
        return;
    }
    
    try {
        // Mostrar loading
        const btnOriginal = document.querySelector(`.btn-reenviar[data-idemail="${idemail}"]`);
        if (btnOriginal) {
            btnOriginal.disabled = true;
            btnOriginal.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        }
        
        // Loading toast enquanto processa
        Swal.fire({
            title: 'Reenviando...',
            html: '<i class="fas fa-spinner fa-spin fa-2x"></i><br><br>Aguarde enquanto o e-mail é reenviado',
            allowOutsideClick: false,
            showConfirmButton: false,
            background: '#0d1b2a',
            color: '#fff'
        });
        
        // Buscar dados do e-mail original
        const responseDetalhe = await fetchComToken(`/detalheEmail/${idemail}`);
        const dataDetalhe = await responseDetalhe.json();
        
        if (dataDetalhe.error || !dataDetalhe.result) {
            throw new Error(dataDetalhe.result?.mensagem || 'Erro ao carregar dados do e-mail');
        }
        
        const emailOriginal = dataDetalhe.result;
        
        // Preparar payload para reenvio
        const payload = {
            idsistema: emailOriginal.idsistema,
            destinatario: emailOriginal.destinatario,
            assunto: emailOriginal.assunto,
            corpo_html: emailOriginal.corpo_html || null,
            corpo_texto: emailOriginal.corpo_texto || null,
            cc: emailOriginal.cc || null,
            bcc: emailOriginal.bcc || null,
            anexos: emailOriginal.anexos ? JSON.parse(emailOriginal.anexos) : null
        };
        
        // Enviar
        const response = await fetchComToken('/sendEmail', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        const data = await response.json();
        
        if (!data.error && data.result?.sucesso) {
            // Sucesso
            toastSucesso('E-mail reenviado com sucesso!');
            
            // Recarregar lista
            carregarEmails(paginaAtual);
            
            // Fechar modal se estiver aberto
            const modalEl = document.getElementById('modalDetalhesEmail');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
        } else {
            throw new Error(data.result?.mensagem || 'Erro ao reenviar e-mail');
        }
        
    } catch (error) {
        console.error('Erro ao reenviar:', error);
        toastErro('Erro ao reenviar: ' + error.message);
    } finally {
        // Restaurar botão
        const btnOriginal = document.querySelector(`.btn-reenviar[data-idemail="${idemail}"]`);
        if (btnOriginal) {
            btnOriginal.disabled = false;
            btnOriginal.innerHTML = '<i class="fas fa-redo"></i>';
        }
    }
}

// Toast de sucesso
function toastSucesso(mensagem) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: 'Sucesso!',
            text: mensagem,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            background: '#1a1f3a',
            color: '#fff'
        });
    } else {
        alert(mensagem);
    }
}

// Toast de erro
function toastErro(mensagem) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'error',
            title: 'Erro',
            text: mensagem,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 5000,
            background: '#1a1f3a',
            color: '#fff'
        });
    } else {
        alert(mensagem);
    }
}

// Toggle do payload original
function togglePayload() {
    const content = document.getElementById('payloadContent');
    const icon = document.getElementById('payloadToggleIcon');
    
    if (content.style.display === 'none') {
        content.style.display = 'block';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        content.style.display = 'none';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
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
    // Normaliza o status (lowercase e trim)
    const statusNormalizado = (status || '').toLowerCase().trim();
    
    const badges = {
        'enviado': '<span class="badge bg-success"><i class="fas fa-check"></i> Enviado</span>',
        'aceito': '<span class="badge bg-info"><i class="fas fa-check-circle"></i> Aceito</span>',
        'pendente': '<span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> Pendente</span>',
        'processando': '<span class="badge bg-primary"><i class="fas fa-spinner fa-spin"></i> Processando</span>',
        'falha': '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i> Falha</span>',
        'rejeitado': '<span class="badge bg-dark"><i class="fas fa-ban"></i> Rejeitado</span>',
        'bounce': '<span class="badge bg-secondary"><i class="fas fa-undo"></i> Bounce</span>',
        'erro': '<span class="badge bg-danger"><i class="fas fa-times"></i> Erro</span>'
    };
    return badges[statusNormalizado] || `<span class="badge bg-secondary">Desconhecido (${status})</span>`;
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

function formatarBytes(bytes) {
    if (!bytes || bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Inicializar ao carregar
document.addEventListener('DOMContentLoaded', () => {
    carregarSistemas();
    carregarEmails(1);
});
</script>

<?php $render('footer'); ?>
