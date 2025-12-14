<?php $render('header'); ?>

<div class="container-fluid py-4 fade-in">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0">
                <i class="fas fa-stethoscope text-info"></i> Diagnóstico de Entrega
            </h2>
            <p class="text-muted">Verifique se seus e-mails podem ser entregues corretamente</p>
        </div>
    </div>

    <!-- Formulário de Diagnóstico -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-search"></i> Testar Entrega</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">E-mail do Destinatário</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="emailDiagnostico" 
                                   placeholder="exemplo@gmail.com" autocomplete="off">
                            <button class="btn btn-primary" type="button" onclick="executarDiagnostico()">
                                <i class="fas fa-play"></i> Diagnosticar
                            </button>
                        </div>
                        <small class="text-muted">Digite o e-mail para verificar se pode receber mensagens</small>
                    </div>
                    
                    <!-- E-mails rápidos para teste -->
                    <div class="mt-3">
                        <small class="text-muted">Testar rapidamente:</small>
                        <div class="d-flex gap-2 mt-2 flex-wrap">
                            <button class="btn btn-sm btn-outline-secondary" onclick="testarRapido('gmail.com')">
                                <i class="fab fa-google"></i> Gmail
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="testarRapido('outlook.com')">
                                <i class="fab fa-microsoft"></i> Outlook
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="testarRapido('hotmail.com')">
                                <i class="fab fa-microsoft"></i> Hotmail
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="testarRapido('yahoo.com')">
                                <i class="fab fa-yahoo"></i> Yahoo
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Info sobre o que é verificado -->
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> O que é verificado?</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <i class="fas fa-server text-primary"></i> 
                            <strong>Conexão SMTP</strong> - Se conseguimos conectar ao servidor de e-mail
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-shield-alt text-success"></i> 
                            <strong>SPF</strong> - Sender Policy Framework (autorização de envio)
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-key text-warning"></i> 
                            <strong>DKIM</strong> - Assinatura digital do e-mail
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-exchange-alt text-info"></i> 
                            <strong>MX Records</strong> - Servidores de e-mail do destinatário
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-exclamation-triangle text-danger"></i> 
                            <strong>Filtros</strong> - Se o destino tem regras rigorosas (Gmail, Outlook)
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Resultado do Diagnóstico -->
    <div class="row" id="resultadoContainer" style="display: none;">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-clipboard-check"></i> Resultado do Diagnóstico</h5>
                    <span id="timestampDiagnostico" class="text-muted small"></span>
                </div>
                <div class="card-body">
                    <!-- Resumo -->
                    <div class="row mb-4" id="resumoCards">
                        <!-- Preenchido via JS -->
                    </div>
                    
                    <!-- Detalhes -->
                    <div class="row">
                        <!-- Problemas -->
                        <div class="col-md-4 mb-3">
                            <div class="card border-danger h-100">
                                <div class="card-header bg-danger bg-opacity-10">
                                    <h6 class="mb-0 text-danger"><i class="fas fa-times-circle"></i> Problemas</h6>
                                </div>
                                <div class="card-body" id="listaProblemas">
                                    <!-- Preenchido via JS -->
                                </div>
                            </div>
                        </div>
                        
                        <!-- Avisos -->
                        <div class="col-md-4 mb-3">
                            <div class="card border-warning h-100">
                                <div class="card-header bg-warning bg-opacity-10">
                                    <h6 class="mb-0 text-warning"><i class="fas fa-exclamation-triangle"></i> Avisos</h6>
                                </div>
                                <div class="card-body" id="listaAvisos">
                                    <!-- Preenchido via JS -->
                                </div>
                            </div>
                        </div>
                        
                        <!-- OK -->
                        <div class="col-md-4 mb-3">
                            <div class="card border-success h-100">
                                <div class="card-header bg-success bg-opacity-10">
                                    <h6 class="mb-0 text-success"><i class="fas fa-check-circle"></i> Tudo OK</h6>
                                </div>
                                <div class="card-body" id="listaOk">
                                    <!-- Preenchido via JS -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recomendações -->
                    <div id="recomendacoesContainer" style="display: none;">
                        <h6 class="mt-3"><i class="fas fa-lightbulb text-warning"></i> Recomendações</h6>
                        <div class="alert alert-info" id="listaRecomendacoes">
                            <!-- Preenchido via JS -->
                        </div>
                    </div>
                    
                    <!-- MX Records -->
                    <div id="mxContainer" style="display: none;">
                        <h6 class="mt-3"><i class="fas fa-server text-info"></i> Servidores de E-mail do Destinatário (MX)</h6>
                        <div id="listaMx" class="d-flex flex-wrap gap-2">
                            <!-- Preenchido via JS -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Loading -->
    <div class="row" id="loadingContainer" style="display: none;">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Analisando...</span>
                    </div>
                    <h5>Executando diagnóstico...</h5>
                    <p class="text-muted">Verificando SPF, DKIM, MX e conexão SMTP</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Testar com domínio rápido
function testarRapido(dominio) {
    document.getElementById('emailDiagnostico').value = `teste@${dominio}`;
    executarDiagnostico();
}

// Executar diagnóstico
async function executarDiagnostico() {
    const email = document.getElementById('emailDiagnostico').value.trim();
    
    if (!email) {
        toastErro('Digite um e-mail para diagnosticar');
        return;
    }
    
    // Validação básica
    if (!email.includes('@') || !email.includes('.')) {
        toastErro('E-mail inválido');
        return;
    }
    
    // Mostrar loading
    document.getElementById('loadingContainer').style.display = 'block';
    document.getElementById('resultadoContainer').style.display = 'none';
    
    try {
        const response = await fetchComToken(`/api/emails/diagnostico?destinatario=${encodeURIComponent(email)}`);
        const data = await response.json();
        
        if (data.error) {
            throw new Error(data.result?.mensagem || 'Erro ao executar diagnóstico');
        }
        
        exibirResultado(data.result);
        
    } catch (error) {
        console.error('Erro:', error);
        toastErro('Erro ao executar diagnóstico: ' + error.message);
    } finally {
        document.getElementById('loadingContainer').style.display = 'none';
    }
}

// Exibir resultado do diagnóstico
function exibirResultado(resultado) {
    const container = document.getElementById('resultadoContainer');
    container.style.display = 'block';
    
    // Timestamp
    document.getElementById('timestampDiagnostico').textContent = 
        `Diagnóstico de: ${resultado.destinatario} | ${resultado.timestamp}`;
    
    // Resumo em cards
    const resumo = resultado.resumo || {};
    document.getElementById('resumoCards').innerHTML = `
        <div class="col-md-3">
            <div class="card ${resumo.pode_enviar ? 'border-success' : 'border-danger'}">
                <div class="card-body text-center">
                    <i class="fas ${resumo.pode_enviar ? 'fa-check-circle text-success' : 'fa-times-circle text-danger'}" style="font-size: 2rem;"></i>
                    <h6 class="mt-2">${resumo.pode_enviar ? 'Pode Enviar' : 'Bloqueado'}</h6>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card ${resumo.entrega_garantida ? 'border-success' : 'border-warning'}">
                <div class="card-body text-center">
                    <i class="fas ${resumo.entrega_garantida ? 'fa-shield-alt text-success' : 'fa-exclamation-triangle text-warning'}" style="font-size: 2rem;"></i>
                    <h6 class="mt-2">${resumo.entrega_garantida ? 'Entrega Garantida' : 'Entrega Incerta'}</h6>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-danger">
                <div class="card-body text-center">
                    <h3 class="text-danger mb-0">${resumo.total_problemas || 0}</h3>
                    <small>Problemas</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h3 class="text-warning mb-0">${resumo.total_avisos || 0}</h3>
                    <small>Avisos</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h3 class="text-success mb-0">${resumo.total_ok || 0}</h3>
                    <small>OK</small>
                </div>
            </div>
        </div>
    `;
    
    // Problemas
    const problemas = resultado.problemas || [];
    document.getElementById('listaProblemas').innerHTML = problemas.length > 0
        ? problemas.map(p => `<div class="mb-2"><i class="fas fa-times text-danger me-2"></i>${escapeHtml(p)}</div>`).join('')
        : '<p class="text-muted mb-0"><i class="fas fa-check"></i> Nenhum problema encontrado</p>';
    
    // Avisos
    const avisos = resultado.avisos || [];
    document.getElementById('listaAvisos').innerHTML = avisos.length > 0
        ? avisos.map(a => `<div class="mb-2"><i class="fas fa-exclamation text-warning me-2"></i>${escapeHtml(a)}</div>`).join('')
        : '<p class="text-muted mb-0"><i class="fas fa-check"></i> Nenhum aviso</p>';
    
    // OK
    const ok = resultado.ok || [];
    document.getElementById('listaOk').innerHTML = ok.length > 0
        ? ok.map(o => `<div class="mb-2"><i class="fas fa-check text-success me-2"></i>${escapeHtml(o)}</div>`).join('')
        : '<p class="text-muted mb-0">Nenhuma verificação passou</p>';
    
    // Recomendações
    const recomendacoes = resultado.recomendacoes || [];
    if (recomendacoes.length > 0) {
        document.getElementById('recomendacoesContainer').style.display = 'block';
        document.getElementById('listaRecomendacoes').innerHTML = 
            '<ul class="mb-0">' + recomendacoes.map(r => `<li>${escapeHtml(r)}</li>`).join('') + '</ul>';
    } else {
        document.getElementById('recomendacoesContainer').style.display = 'none';
    }
    
    // MX Records
    const mx = resultado.mx_destinatario || [];
    if (mx.length > 0) {
        document.getElementById('mxContainer').style.display = 'block';
        document.getElementById('listaMx').innerHTML = 
            mx.map(m => `<span class="badge bg-info"><i class="fas fa-server"></i> ${escapeHtml(m)}</span>`).join('');
    } else {
        document.getElementById('mxContainer').style.display = 'none';
    }
    
    // Scroll para resultado
    container.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Toast de sucesso
function toastSucesso(mensagem) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: mensagem,
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
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: mensagem,
            showConfirmButton: false,
            timer: 4000,
            background: '#1a1f3a',
            color: '#fff'
        });
    } else {
        alert(mensagem);
    }
}

// Helper para escape HTML
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

// Enter para executar
document.getElementById('emailDiagnostico').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        executarDiagnostico();
    }
});
</script>

<?php $render('footer'); ?>
