/**
 * Dashboard - Carregamento dinâmico de dados
 * Atualiza automaticamente a cada 30 segundos
 * 
 * @author MailJZTech
 * @date 2025-11-09
 */

let updateInterval = null;
let chartEmails = null;
let chartStatus = null;

/**
 * Inicializa o dashboard
 */
function initDashboard() {
    console.log('🚀 Inicializando Dashboard...');
    
    // Carrega dados iniciais
    carregarDadosDashboard();
    
    // Configura atualização automática a cada 30 segundos
    updateInterval = setInterval(() => {
        console.log('🔄 Atualizando dados do dashboard...');
        carregarDadosDashboard();
    }, 30000); // 30 segundos
    
    console.log('✅ Dashboard inicializado com sucesso');
}

/**
 * Carrega dados do dashboard via API
 */
async function carregarDadosDashboard() {
    try {
        // Captura possível filtro (ex: campo oculto ou select futuro)
        const filtroSistemaEl = document.querySelector('[data-filtro="idsistema"]');
        const idsistema = filtroSistemaEl ? filtroSistemaEl.value : '';
        const qs = idsistema ? `?idsistema=${encodeURIComponent(idsistema)}` : '';

        // Usa helper com token (auth.js)
        const response = await fetchComToken(`/dashboard/stats${qs}`, {
            method: 'GET'
        });

        if (!response.ok) {
            throw new Error(`Erro HTTP: ${response.status}`);
        }

        const data = await response.json();

        if (data.error) {
            console.error('❌ Erro ao carregar dados:', data.result);
            toastErro(data.result?.mensagem || 'Erro ao carregar dados do dashboard');
            return;
        }

        console.log('✅ Dados carregados:', data.result);
        
        // Atualiza interface
        atualizarEstatisticas(data.result.estatisticas);
        atualizarTabelaEmails(data.result.ultimos_emails);
        atualizarGraficos(data.result.estatisticas);

    } catch (error) {
        console.error('❌ Erro ao carregar dashboard:', error);
        toastErro('Erro ao conectar com o servidor');
    }
}

/**
 * Atualiza cards de estatísticas
 */
function atualizarEstatisticas(stats) {
    // Total de e-mails
    document.querySelector('[data-stat="total"]').textContent = stats.total || 0;
    
    // Enviados
    document.querySelector('[data-stat="enviados"]').textContent = stats.enviados || 0;
    
    // Erros
    document.querySelector('[data-stat="erros"]').textContent = stats.erros || 0;
    
    // Taxa de sucesso
    const total = stats.total || 0;
    const enviados = stats.enviados || 0;
    const taxa = total > 0 ? ((enviados / total) * 100).toFixed(1) : 0;
    document.querySelector('[data-stat="taxa"]').textContent = `${taxa}%`;
}

/**
 * Atualiza tabela de últimos e-mails
 */
function atualizarTabelaEmails(emails) {
    const tbody = document.querySelector('#tabelaEmails tbody');
    
    if (!emails || emails.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center text-muted">
                    <i class="fas fa-inbox"></i> Nenhum e-mail enviado ainda
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = emails.map(email => `
        <tr data-idemail="${email.idemail}" class="linha-email">
            <td><small>${escapeHtml(email.destinatario)}</small></td>
            <td><small>${escapeHtml(email.assunto.substring(0, 50))}${email.assunto.length > 50 ? '...' : ''}</small></td>
            <td>${getBadgeStatus(email.status)}</td>
            <td><small>${formatarData(email.data_envio || email.data_criacao)}</small></td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-primary btn-detalhe" data-idemail="${email.idemail}">
                    <i class="fas fa-eye"></i>
                </button>
            </td>
        </tr>
    `).join('');

    // Adiciona listeners para modal de detalhes
    tbody.querySelectorAll('.btn-detalhe').forEach(btn => {
        btn.addEventListener('click', () => abrirModalDetalhes(btn.getAttribute('data-idemail')));
    });
}

/**
 * Retorna badge HTML baseado no status
 */
function getBadgeStatus(status) {
    // Normaliza o status (lowercase e trim)
    const statusNormalizado = (status || '').toLowerCase().trim();
    
    const badges = {
        'enviado': '<span class="badge bg-success"><i class="fas fa-check"></i> Enviado</span>',
        'aceito': '<span class="badge bg-info"><i class="fas fa-check-circle"></i> Aceito</span>',
        'pendente': '<span class="badge bg-warning"><i class="fas fa-clock"></i> Pendente</span>',
        'processando': '<span class="badge bg-primary"><i class="fas fa-spinner fa-spin"></i> Processando</span>',
        'falha': '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i> Falha</span>',
        'rejeitado': '<span class="badge bg-dark"><i class="fas fa-ban"></i> Rejeitado</span>',
        'bounce': '<span class="badge bg-secondary"><i class="fas fa-undo"></i> Bounce</span>',
        'erro': '<span class="badge bg-danger"><i class="fas fa-times-circle"></i> Erro</span>'
    };
    return badges[statusNormalizado] || `<span class="badge bg-secondary">Desconhecido (${escapeHtml(status)})</span>`;
}

/**
 * Atualiza os gráficos
 */
function atualizarGraficos(stats) {
    // Gráfico de status (pizza)
    atualizarGraficoStatus(stats);
    
    // Gráfico de linha temporal (se houver dados de período)
    atualizarGraficoEmails(stats);
}

/**
 * Atualiza gráfico de status (doughnut)
 */
function atualizarGraficoStatus(stats) {
    const ctx = document.getElementById('statusChart');
    if (!ctx) return;

    const dados = {
        labels: ['Enviados', 'Erros', 'Pendentes'],
        datasets: [{
            data: [
                stats.enviados || 0,
                stats.erros || 0,
                stats.pendentes || 0
            ],
            backgroundColor: [
                'rgba(40, 167, 69, 0.8)',   // Verde
                'rgba(220, 53, 69, 0.8)',    // Vermelho
                'rgba(255, 193, 7, 0.8)'     // Amarelo
            ],
            borderColor: [
                'rgba(40, 167, 69, 1)',
                'rgba(220, 53, 69, 1)',
                'rgba(255, 193, 7, 1)'
            ],
            borderWidth: 1
        }]
    };

    if (chartStatus) {
        chartStatus.data = dados;
        chartStatus.update();
    } else {
        chartStatus = new Chart(ctx, {
            type: 'doughnut',
            data: dados,
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
}

/**
 * Atualiza gráfico de e-mails (linha temporal)
 */
function atualizarGraficoEmails(stats) {
    const ctx = document.getElementById('emailsChart');
    if (!ctx) return;

    // Gerar últimos 30 dias
    const labels = [];
    const dados = [];
    const hoje = new Date();
    
    for (let i = 29; i >= 0; i--) {
        const data = new Date(hoje);
        data.setDate(data.getDate() - i);
        labels.push(data.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' }));
        // Por enquanto, dados simulados - pode ser implementado no backend
        dados.push(Math.floor(Math.random() * (stats.total / 10 || 5)));
    }

    const config = {
        labels: labels,
        datasets: [{
            label: 'E-mails Enviados',
            data: dados,
            borderColor: 'rgba(0, 123, 255, 1)',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            tension: 0.4,
            fill: true
        }]
    };

    if (chartEmails) {
        chartEmails.data = config;
        chartEmails.update();
    } else {
        chartEmails = new Chart(ctx, {
            type: 'line',
            data: config,
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }
}

/**
 * Formata data para exibição
 */
function formatarData(dataString) {
    if (!dataString) return '-';
    
    const data = new Date(dataString);
    return data.toLocaleString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Escapa HTML para prevenir XSS
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

/**
 * Mostra mensagem de erro (ATUALIZADO para Toast)
 */
function mostrarErro(mensagem) {
    console.error('❌', mensagem);
    toastErro(mensagem);
}

// Modal detalhes (reuso do existente em view dashboard)
async function abrirModalDetalhes(idemail) {
    const modalEl = document.getElementById('detalhesModal');
    if (!modalEl) return;
    const modal = new bootstrap.Modal(modalEl);
    const content = document.getElementById('detalhesContent');
    content.innerHTML = `<div class="text-center py-4"><div class="spinner-border" role="status"></div><p class="mt-3 text-muted">Carregando detalhes...</p></div>`;
    modal.show();
    try {
        const resp = await fetchComToken(`/detalheEmail/${idemail}`);
        const data = await resp.json();
        if (data.error) {
            content.innerHTML = `<div class='alert alert-danger'><i class="fas fa-exclamation-circle"></i> ${escapeHtml(data.result?.mensagem || 'Erro ao carregar detalhes')}</div>`;
            toastErro('Erro ao carregar detalhes do e-mail');
            return;
        }
        const e = data.result;
        
        // Processar corpo do e-mail - usando iframe para isolar o HTML
        let corpoEmailHtml = '';
        let usarIframe = false;
        
        if (e.corpo_html && e.corpo_html.trim() !== '') {
            usarIframe = true;
            // Escapar aspas e barras invertidas para o srcdoc
            corpoEmailHtml = e.corpo_html
                .replace(/\\/g, '\\\\')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        } else if (e.corpo_texto && e.corpo_texto.trim() !== '') {
            corpoEmailHtml = `<pre style="white-space: pre-wrap; word-wrap: break-word; font-family: monospace; color: #333; margin: 0;">${escapeHtml(e.corpo_texto)}</pre>`;
            usarIframe = true;
        }
        
        // Formatar tamanho
        function formatarBytes(bytes) {
            if (!bytes || bytes === 0) return 'N/A';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        content.innerHTML = `
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="card border-0 border-start border-danger border-4">
                        <div class="card-body">
                            <p class="card-text text-muted small mb-2"><i class="fas fa-hashtag text-danger"></i> ID</p>
                            <h6 class="text-dark mb-0"><code class="text-danger">#${e.idemail}</code></h6>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card border-0 border-start border-info border-4">
                        <div class="card-body">
                            <p class="card-text text-muted small mb-2"><i class="fas fa-info-circle text-info"></i> Status</p>
                            ${getBadgeStatus(e.status)}
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 border-start border-danger border-4 mb-3" style="background: rgba(220, 53, 69, 0.05);">
                <div class="card-body">
                    <p class="card-text text-muted small mb-2"><i class="fas fa-user text-danger"></i> Destinatário</p>
                    <p class="mb-0" style="font-size: 1rem; color: #dc3545;">${escapeHtml(e.destinatario || '')}</p>
                </div>
            </div>

            <div class="card border-0 border-start border-primary border-4 mb-3" style="background: rgba(0, 123, 255, 0.05);">
                <div class="card-body">
                    <p class="card-text text-muted small mb-2"><i class="fas fa-heading text-primary"></i> Assunto</p>
                    <p class="mb-0" style="font-size: 1rem; color: #007bff;">${escapeHtml(e.assunto || '')}</p>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="card border-0 border-start border-warning border-4" style="background: rgba(255, 193, 7, 0.15);">
                        <div class="card-body">
                            <p class="card-text text-muted small mb-2"><i class="fas fa-paper-plane text-warning"></i> Data Envio</p>
                            <p class="mb-0" style="font-size: 1rem; color: #ffc107; font-weight: 500;">${formatarData(e.data_envio || e.data_criacao)}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="card border-0 border-start border-secondary border-4">
                        <div class="card-body">
                            <p class="card-text text-muted small mb-2"><i class="fas fa-server text-secondary"></i> Código SMTP</p>
                            <p class="mb-0"><code>${e.smtp_code || 'N/A'}</code></p>
                        </div>
                    </div>
                </div>
            </div>

            ${e.smtp_response ? `
                <div class="card border-0 border-start border-secondary border-4 mb-3">
                    <div class="card-body">
                        <p class="card-text text-muted small mb-2"><i class="fas fa-reply text-secondary"></i> Resposta SMTP</p>
                        <code class="text-muted">${escapeHtml(e.smtp_response)}</code>
                    </div>
                </div>
            ` : ''}

            <div class="row">
                ${e.tamanho_bytes ? `
                    <div class="col-md-6 mb-3">
                        <div class="card border-0 border-start border-info border-4">
                            <div class="card-body">
                                <p class="card-text text-muted small mb-2"><i class="fas fa-weight text-info"></i> Tamanho</p>
                                <p class="mb-0">${formatarBytes(e.tamanho_bytes)}</p>
                            </div>
                        </div>
                    </div>
                ` : ''}
                ${e.tentativas > 1 ? `
                    <div class="col-md-6 mb-3">
                        <div class="card border-0 border-start border-warning border-4">
                            <div class="card-body">
                                <p class="card-text text-muted small mb-2"><i class="fas fa-redo text-warning"></i> Tentativas</p>
                                <p class="mb-0">${e.tentativas}</p>
                            </div>
                        </div>
                    </div>
                ` : ''}
            </div>

            <div class="card border-0 border-top border-success border-4 mb-3">
                <div class="card-header bg-white border-0 pt-3">
                    <h6 class="mb-0"><i class="fas fa-envelope-open-text text-success"></i> Corpo do E-mail</h6>
                </div>
                <div class="card-body pt-0">
                    ${usarIframe && corpoEmailHtml ? `
                        <iframe 
                            class="email-iframe-preview" 
                            srcdoc="${corpoEmailHtml}"
                            sandbox="allow-same-origin"
                            title="Preview do E-mail"
                            style="width: 100%; min-height: 350px; max-height: 500px; border: 1px solid #dee2e6; border-radius: 0.375rem; background: #ffffff;"
                        ></iframe>
                    ` : `
                        <div style="background:#1e1e1e; border:1px solid #404040; border-radius:0.375rem; padding:1rem; max-height:350px; overflow-y:auto;">
                            <em class="text-muted">Sem conteúdo</em>
                        </div>
                    `}
                </div>
            </div>

            ${e.mensagem_erro ? `
                <div class="card border-0 border-start border-danger border-4">
                    <div class="card-header bg-danger bg-opacity-10 border-0">
                        <h6 class="mb-0 text-danger"><i class="fas fa-exclamation-circle"></i> Erro ao Enviar</h6>
                    </div>
                    <div class="card-body pt-2">
                        <p class="mb-0" style="color: #dc3545;"><code>${escapeHtml(e.mensagem_erro)}</code></p>
                    </div>
                </div>
            ` : ''}
        `;
    } catch (err) {
        console.error('Erro ao carregar detalhes:', err);
        content.innerHTML = `<div class='alert alert-danger'><i class="fas fa-exclamation-circle"></i> ${escapeHtml(err.message)}</div>`;
        toastErro('Erro ao carregar detalhes: ' + err.message);
    }
}

/**
 * Limpa interval quando sair da página
 */
window.addEventListener('beforeunload', () => {
    if (updateInterval) {
        clearInterval(updateInterval);
    }
});

// Inicializa quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', initDashboard);
