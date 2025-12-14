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

// Modal detalhes (espelho do modal da tela de e-mails)
let emailAtualModalDash = null;

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
        const email = data.result;
        emailAtualModalDash = email.idemail;
        
        // Processar corpo do e-mail - usando iframe para isolar o HTML
        let corpoEmailHtml = '';
        let usarIframe = false;
        
        if (email.corpo_html && email.corpo_html.trim() !== '') {
            usarIframe = true;
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
        
        content.innerHTML = `
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
                        <div class="detail-value">${getBadgeStatus(email.status)}</div>
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
                
                <!-- Corpo do E-mail com Abas -->
                <div class="col-12">
                    <div class="detail-label mb-2"><i class="fas fa-envelope-open-text text-success"></i> Corpo do E-mail</div>
                    
                    <ul class="nav nav-tabs email-tabs" id="emailTabs-${email.idemail}" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="preview-tab-${email.idemail}" data-bs-toggle="tab" data-bs-target="#preview-${email.idemail}" type="button" role="tab">
                                <i class="fas fa-eye"></i> Preview
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="html-tab-${email.idemail}" data-bs-toggle="tab" data-bs-target="#html-${email.idemail}" type="button" role="tab">
                                <i class="fas fa-code"></i> HTML Bruto
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="texto-tab-${email.idemail}" data-bs-toggle="tab" data-bs-target="#texto-${email.idemail}" type="button" role="tab">
                                <i class="fas fa-align-left"></i> Texto Bruto
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="payload-tab-${email.idemail}" data-bs-toggle="tab" data-bs-target="#payload-${email.idemail}" type="button" role="tab">
                                <i class="fas fa-file-code"></i> Payload Original
                                ${email.payload_original ? '<span class="badge bg-warning ms-1">JSON</span>' : '<span class="badge bg-secondary ms-1">N/A</span>'}
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="emailTabsContent-${email.idemail}">
                        <!-- Aba Preview -->
                        <div class="tab-pane fade show active" id="preview-${email.idemail}" role="tabpanel">
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
                        
                        <!-- Aba HTML Bruto -->
                        <div class="tab-pane fade" id="html-${email.idemail}" role="tabpanel">
                            <div class="code-preview-container">
                                <button class="btn btn-sm btn-outline-info btn-copy-code" onclick="copiarConteudoDash('html', ${email.idemail})">
                                    <i class="fas fa-copy"></i> Copiar
                                </button>
                                <pre class="code-preview" id="html-content-${email.idemail}">${escapeHtml(email.corpo_email || email.corpo_html || 'Sem conteúdo HTML')}</pre>
                            </div>
                        </div>
                        
                        <!-- Aba Texto Bruto -->
                        <div class="tab-pane fade" id="texto-${email.idemail}" role="tabpanel">
                            <div class="code-preview-container">
                                <button class="btn btn-sm btn-outline-info btn-copy-code" onclick="copiarConteudoDash('texto', ${email.idemail})">
                                    <i class="fas fa-copy"></i> Copiar
                                </button>
                                <pre class="code-preview" id="texto-content-${email.idemail}">${escapeHtml(email.corpo_texto || email.corpo_email || 'Sem conteúdo texto')}</pre>
                            </div>
                        </div>
                        
                        <!-- Aba Payload Original -->
                        <div class="tab-pane fade" id="payload-${email.idemail}" role="tabpanel">
                            <div class="code-preview-container">
                                <button class="btn btn-sm btn-outline-info btn-copy-code" onclick="copiarConteudoDash('payload', ${email.idemail})">
                                    <i class="fas fa-copy"></i> Copiar
                                </button>
                                <pre class="code-preview" id="payload-content-${email.idemail}">${(() => {
                                    if (!email.payload_original) return '<em class="text-muted">Payload não disponível (e-mails antigos podem não ter este dado)</em>';
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
                </div>
                
                ${email.mensagem_erro ? `
                <div class="col-12">
                    <div class="alert alert-danger mb-0">
                        <strong><i class="fas fa-exclamation-triangle"></i> Erro de Envio:</strong><br>
                        <code>${escapeHtml(email.mensagem_erro)}</code>
                    </div>
                </div>
                ` : ''}
            </div>
            
            <!-- Botão Reenviar -->
            <div class="mt-3 text-end">
                <button class="btn btn-primary" onclick="reenviarEmailDash(${email.idemail})">
                    <i class="fas fa-paper-plane"></i> Reenviar E-mail
                </button>
            </div>
        `;
    } catch (err) {
        console.error('Erro ao carregar detalhes:', err);
        content.innerHTML = `<div class='alert alert-danger'><i class="fas fa-exclamation-circle"></i> ${escapeHtml(err.message)}</div>`;
        toastErro('Erro ao carregar detalhes: ' + err.message);
    }
}

// Toggle do payload original (Dashboard)
function togglePayloadDash() {
    const content = document.getElementById('payloadContentDash');
    const icon = document.getElementById('payloadToggleIconDash');
    
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

// Copiar conteúdo HTML, Texto ou Payload (Dashboard)
function copiarConteudoDash(tipo, idemail) {
    let elementId;
    if (tipo === 'html') {
        elementId = `html-content-${idemail}`;
    } else if (tipo === 'texto') {
        elementId = `texto-content-${idemail}`;
    } else if (tipo === 'payload') {
        elementId = `payload-content-${idemail}`;
    }
    
    const element = document.getElementById(elementId);
    
    if (!element) {
        toastErro('Conteúdo não encontrado');
        return;
    }
    
    const texto = element.textContent || element.innerText;
    
    navigator.clipboard.writeText(texto).then(() => {
        toastSucesso(`${tipo.toUpperCase()} copiado para a área de transferência!`);
    }).catch(err => {
        console.error('Erro ao copiar:', err);
        toastErro('Erro ao copiar conteúdo');
    });
}

// Reenviar e-mail (Dashboard)
async function reenviarEmailDash(idemail) {
    // Confirmação com SweetAlert2
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
        color: '#fff'
    });
    
    if (!confirmResult.isConfirmed) {
        return;
    }
    
    try {
        // Loading
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
            reenvio_de: idemail
        };
        
        // Enviar
        const response = await fetchComToken('/sendEmail', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        
        const data = await response.json();
        
        if (!data.error && data.result?.sucesso) {
            Swal.fire({
                icon: 'success',
                title: 'E-mail reenviado!',
                text: `Novo ID: #${data.result.idemail}`,
                background: '#0d1b2a',
                color: '#fff',
                confirmButtonColor: '#00d9ff'
            });
            // Recarregar dashboard
            carregarDadosDashboard();
        } else {
            throw new Error(data.result?.mensagem || 'Erro ao reenviar');
        }
        
    } catch (error) {
        console.error('Erro ao reenviar:', error);
        Swal.fire({
            icon: 'error',
            title: 'Erro ao reenviar',
            text: error.message,
            background: '#0d1b2a',
            color: '#fff',
            confirmButtonColor: '#00d9ff'
        });
    }
}

// Formatar bytes
function formatarBytes(bytes) {
    if (!bytes || bytes === 0) return 'N/A';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
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
