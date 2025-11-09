/**
 * backup.js - JavaScript para gerenciamento de backups de bancos de dados
 * Projeto: MailJZTech
 * Data: 09/11/2025
 */

// ========================================
// VARIÁVEIS GLOBAIS
// ========================================
let modalBackup;
let modalErro;
let configuracaoAtual = null;

// ========================================
// INICIALIZAÇÃO
// ========================================
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar modais Bootstrap
    const modalBackupEl = document.getElementById('modalBackup');
    if (modalBackupEl) {
        modalBackup = new bootstrap.Modal(modalBackupEl);
    }

    const modalErroEl = document.getElementById('modalErro');
    if (modalErroEl) {
        modalErro = new bootstrap.Modal(modalErroEl);
    }

    // Carregar dados conforme a página
    if (document.getElementById('tabelaBackups')) {
        carregarConfiguracoes();
        carregarEstatisticas();
        
        // Atualizar a cada 30 segundos
        setInterval(() => {
            carregarConfiguracoes();
            carregarEstatisticas();
        }, 30000);
    }

    if (document.getElementById('tabelaLogs') && typeof idConfig !== 'undefined') {
        carregarLogs(idConfig);
        
        // Atualizar logs a cada 10 segundos
        setInterval(() => {
            carregarLogs(idConfig);
        }, 10000);
    }
});

// ========================================
// ESTATÍSTICAS (Dashboard)
// ========================================
async function carregarEstatisticas() {
    try {
        const response = await fetchComToken('/backup/estatisticas');
        const data = await response.json();

        if (!data.error && data.result) {
            const stats = data.result;
            
            document.getElementById('statTotalBancos').textContent = stats.total_bancos || 0;
            document.getElementById('statTotalBackups').textContent = stats.total_backups || 0;
            document.getElementById('statEspacoTotal').textContent = stats.espaco_total_mb 
                ? `${stats.espaco_total_mb} MB` 
                : '0 MB';
            
            const ultimoBackup = stats.ultimo_backup 
                ? formatarDataHora(stats.ultimo_backup) 
                : 'Nunca';
            document.getElementById('statUltimoBackup').textContent = ultimoBackup;
        }
    } catch (error) {
        console.error('Erro ao carregar estatísticas:', error);
        toastErro('Erro ao carregar estatísticas');
    }
}

// ========================================
// CONFIGURAÇÕES (CRUD)
// ========================================
async function carregarConfiguracoes() {
    try {
        const response = await fetchComToken('/backup/configuracoes');
        const data = await response.json();

        const tbody = document.querySelector('#tabelaBackups tbody');

        if (!data.error && data.result && data.result.length > 0) {
            const configuracoes = data.result;
            tbody.innerHTML = configuracoes.map(config => `
                <tr>
                    <td><strong>${escapeHtml(config.nome_banco)}</strong></td>
                    <td><code>${escapeHtml(config.pasta_base)}</code></td>
                    <td class="text-center">${config.retencao_dias}</td>
                    <td>
                        ${config.ultimo_backup_em 
                            ? `<small>${formatarDataHora(config.ultimo_backup_em)}</small>` 
                            : '<small class="text-muted">Nunca</small>'
                        }
                    </td>
                    <td class="text-center">
                        <span class="badge bg-secondary">${config.total_backups}</span>
                    </td>
                    <td>
                        ${config.ativo == 1 
                            ? '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Ativo</span>' 
                            : '<span class="badge bg-danger"><i class="fas fa-times-circle"></i> Inativo</span>'
                        }
                    </td>
                    <td>
                        <div class="btn-group" role="group">
                            <button class="btn btn-sm btn-primary" onclick="executarBackup(${config.idbackup_banco_config})" 
                                    title="Executar Backup">
                                <i class="fas fa-play"></i>
                            </button>
                            <button class="btn btn-sm btn-info" onclick="verLogs(${config.idbackup_banco_config})" 
                                    title="Ver Logs">
                                <i class="fas fa-list"></i>
                            </button>
                            <button class="btn btn-sm btn-warning" onclick="abrirModalEditar(${config.idbackup_banco_config})" 
                                    title="Editar">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="excluirConfiguracao(${config.idbackup_banco_config}, '${escapeHtml(config.nome_banco)}')" 
                                    title="Excluir">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="fas fa-database fa-3x mb-3"></i>
                        <p>Nenhuma configuração de backup cadastrada</p>
                        <button class="btn btn-primary btn-sm" onclick="abrirModalCriar()">
                            <i class="fas fa-plus"></i> Criar Primeira Configuração
                        </button>
                    </td>
                </tr>
            `;
        }
    } catch (error) {
        console.error('Erro ao carregar configurações:', error);
        toastErro('Erro ao carregar configurações: ' + error.message);
    }
}

function abrirModalCriar() {
    configuracaoAtual = null;
    document.getElementById('modalTitulo').textContent = 'Nova Configuração';
    document.getElementById('formBackup').reset();
    document.getElementById('idbackup').value = '';
    document.getElementById('ativo').checked = true;
    document.getElementById('bucket_nome').value = 'dbjztech';
    document.getElementById('retencao_dias').value = '7';
    modalBackup.show();
}

async function abrirModalEditar(id) {
    try {
        const response = await fetchComToken(`/backup/configuracoes/${id}`);
        const data = await response.json();

        if (!data.error && data.result) {
            configuracaoAtual = data.result;
            document.getElementById('modalTitulo').textContent = 'Editar Configuração';
            document.getElementById('idbackup').value = configuracaoAtual.idbackup_banco_config;
            document.getElementById('nome_banco').value = configuracaoAtual.nome_banco;
            document.getElementById('bucket_nome').value = configuracaoAtual.bucket_nome;
            document.getElementById('pasta_base').value = configuracaoAtual.pasta_base;
            document.getElementById('retencao_dias').value = configuracaoAtual.retencao_dias;
            document.getElementById('ativo').checked = configuracaoAtual.ativo == 1;
            modalBackup.show();
        }
    } catch (error) {
        console.error('Erro ao carregar configuração:', error);
        toastErro('Erro ao carregar configuração: ' + error.message);
    }
}

async function salvarConfiguracao() {
    const id = document.getElementById('idbackup').value;
    const dados = {
        nome_banco: document.getElementById('nome_banco').value.trim(),
        bucket_nome: document.getElementById('bucket_nome').value.trim(),
        pasta_base: document.getElementById('pasta_base').value.trim(),
        retencao_dias: parseInt(document.getElementById('retencao_dias').value),
        ativo: document.getElementById('ativo').checked ? 1 : 0
    };

    // Validações
    if (!dados.nome_banco || !dados.pasta_base) {
        mostrarErro('Preencha todos os campos obrigatórios');
        return;
    }

    if (!/^[a-zA-Z0-9_\-]+$/.test(dados.nome_banco)) {
        mostrarErro('Nome do banco contém caracteres inválidos. Use apenas letras, números, _ e -');
        return;
    }

    if (dados.retencao_dias < 1 || dados.retencao_dias > 365) {
        mostrarErro('Retenção deve estar entre 1 e 365 dias');
        return;
    }

    try {
        let response;
        if (id) {
            // Atualizar
            response = await fetchComToken(`/backup/configuracoes/${id}`, {
                method: 'PUT',
                body: JSON.stringify(dados)
            });
        } else {
            // Criar
            response = await fetchComToken('/backup/configuracoes', {
                method: 'POST',
                body: JSON.stringify(dados)
            });
        }

        const data = await response.json();

        if (!data.error) {
            mostrarSucesso(data.result.mensagem || 'Configuração salva com sucesso!');
            modalBackup.hide();
            carregarConfiguracoes();
            carregarEstatisticas();
        } else {
            mostrarErro(data.result || 'Erro ao salvar configuração');
        }
    } catch (error) {
        console.error('Erro ao salvar configuração:', error);
        mostrarErro('Erro ao salvar configuração: ' + error.message);
    }
}

async function excluirConfiguracao(id, nome) {
    if (!await confirmarExclusao(nome, 'Todos os logs associados também serão removidos.')) {
        return;
    }

    try {
        const response = await fetchComToken(`/backup/configuracoes/${id}`, {
            method: 'DELETE'
        });

        const data = await response.json();

        if (!data.error) {
            mostrarSucesso('Configuração excluída com sucesso!');
            carregarConfiguracoes();
            carregarEstatisticas();
        } else {
            mostrarErro(data.result || 'Erro ao excluir configuração');
        }
    } catch (error) {
        console.error('Erro ao excluir configuração:', error);
        toastErro('Erro ao excluir configuração: ' + error.message);
    }
}

// ========================================
// EXECUÇÃO DE BACKUPS
// ========================================
async function executarBackup(id) {
    if (!await confirmarAcao('Deseja executar o backup agora?', 'Execução de Backup', 'Sim, executar!', 'Cancelar')) {
        return;
    }

    mostrarLoad('Executando backup... Por favor aguarde.');

    try {
        const response = await fetchComToken(`/backup/executar/${id}`, {
            method: 'POST'
        });

        const data = await response.json();

        if (!data.error) {
            fecharLoad();
            mostrarSucesso('Backup executado com sucesso!');
            carregarConfiguracoes();
            carregarEstatisticas();
        } else {
            fecharLoad();
            toastErro(data.result || 'Erro ao executar backup');
        }
    } catch (error) {
        console.error('Erro ao executar backup:', error);
        fecharLoad();
        toastErro('Erro ao executar backup: ' + error.message);
    }
}

async function executarBackupManual() {
    if (typeof idConfig === 'undefined') return;
    
    if (!await confirmarAcao(`Deseja executar o backup de "${nomeConfig}"?`, 'Execução Manual', 'Sim, executar!', 'Cancelar')) {
        return;
    }

    mostrarLoad('Executando backup... Por favor aguarde.');

    try {
        const response = await fetchComToken(`/backup/executar/${idConfig}`, {
            method: 'POST'
        });

        const data = await response.json();

        if (!data.error) {
            fecharLoad();
            mostrarSucesso('Backup executado com sucesso!');
            setTimeout(() => carregarLogs(idConfig), 2000);
        } else {
            fecharLoad();
            toastErro(data.result || 'Erro ao executar backup');
        }
    } catch (error) {
        console.error('Erro ao executar backup:', error);
        fecharLoad();
        toastErro('Erro ao executar backup: ' + error.message);
    }
}

function verLogs(id) {
    window.location.href = `${BASE_API}/backup/logs/${id}`;
}

// ========================================
// LOGS
// ========================================
async function carregarLogs(idConfig) {
    try {
        const response = await fetchComToken(`/backup/logs/${idConfig}?detalhado=true&limite=100`);
        const data = await response.json();

        const tbody = document.querySelector('#tabelaLogs tbody');

        if (!data.error && data.result && data.result.length > 0) {
            const logs = data.result;
            tbody.innerHTML = logs.map((log, index) => {
                const statusBadge = getStatusBadge(log.status);
                const tamanho = log.tamanho_mb ? `${log.tamanho_mb} MB` : '-';
                const duracao = log.duracao_segundos ? `${log.duracao_segundos}s` : '-';

                // Armazenar mensagem de erro em um atributo data
                let btnErro = '';
                if (log.status === 'error') {
                    const erroId = `erro-${log.idbackup_execucao_log || index}`;
                    btnErro = `<button class="btn btn-sm btn-danger" 
                                       onclick="verErroById('${erroId}')" 
                                       data-erro-msg="${escapeHtml(log.mensagem_erro || 'Sem detalhes')}"
                                       id="${erroId}">
                                    <i class="fas fa-exclamation-triangle"></i> Ver Erro
                               </button>`;
                }

                return `
                    <tr>
                        <td><small>${formatarDataHora(log.iniciado_em)}</small></td>
                        <td>${duracao}</td>
                        <td>${statusBadge}</td>
                        <td>${tamanho}</td>
                        <td>
                            ${log.gcs_objeto 
                                ? `<small><code title="${escapeHtml(log.gcs_objeto)}">${escapeHtml(log.gcs_objeto.substring(log.gcs_objeto.lastIndexOf('/') + 1))}</code></small>` 
                                : '-'
                            }
                        </td>
                        <td>${btnErro}</td>
                    </tr>
                `;
            }).join('');
        } else {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <i class="fas fa-list fa-3x mb-3"></i>
                        <p>Nenhum log de backup encontrado</p>
                    </td>
                </tr>
            `;
        }
    } catch (error) {
        console.error('Erro ao carregar logs:', error);
        toastErro('Erro ao carregar logs: ' + error.message);
    }
}

function verErroById(erroId) {
    const btn = document.getElementById(erroId);
    if (btn) {
        const mensagemErro = btn.getAttribute('data-erro-msg');
        verErro(mensagemErro);
    }
}

function verErro(mensagemErro) {
    // Tentar parsear se for JSON
    let mensagemFormatada = mensagemErro || 'Sem detalhes do erro';
    
    try {
        const obj = JSON.parse(mensagemErro);
        mensagemFormatada = JSON.stringify(obj, null, 2);
    } catch (e) {
        // Não é JSON, manter como texto
        mensagemFormatada = mensagemErro;
    }
    
    mostrarAlertaHTML(
        'Erro no Backup',
        `<pre style="text-align: left; background: #f8d7da; padding: 15px; border-radius: 5px; color: #721c24; max-height: 300px; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word;">${escapeHtml(mensagemFormatada)}</pre>`,
        'error'
    );
}

// ========================================
// UTILITÁRIOS
// ========================================
function getStatusBadge(status) {
    const badges = {
        'success': '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Sucesso</span>',
        'error': '<span class="badge bg-danger"><i class="fas fa-times-circle"></i> Erro</span>',
        'running': '<span class="badge bg-primary"><i class="fas fa-spinner fa-spin"></i> Executando</span>',
        'pruned': '<span class="badge bg-secondary"><i class="fas fa-trash"></i> Removido</span>'
    };
    return badges[status] || `<span class="badge bg-secondary">${status}</span>`;
}

function formatarDataHora(dataString) {
    if (!dataString) return '-';
    const data = new Date(dataString);
    return data.toLocaleString('pt-BR');
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
    return text.replace(/[&<>"']/g, m => map[m]);
}


