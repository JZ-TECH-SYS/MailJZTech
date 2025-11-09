<?php $render('header'); ?>

<div class="row mb-4">
    <div class="col-md-8">
        <h2 class="mb-2">Gerenciamento de Sistemas</h2>
        <p class="text-muted">Cadastre e gerencie os sistemas que utilizam a API de envio de e-mails</p>
    </div>
    <div class="col-md-4 text-end">
        <a href="<?php echo $base; ?>/criar-sistema" class="btn btn-primary">
            <i class="fas fa-plus"></i> Novo Sistema
        </a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="tabelaSistemas">
            <thead>
                <tr>
                    <th>Nome do Sistema</th>
                    <th>Remetente</th>
                    <th>E-mail</th>
                    <th>Status</th>
                    <th>Data de Criação</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p class="mt-3 text-muted">Carregando sistemas...</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para exibir Chave de API -->
<div class="modal fade" id="chaveApiModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-key"></i> Chave de API
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Sistema</label>
                    <p class="form-control-plaintext"><strong id="sistemaNome"></strong></p>
                </div>
                <div class="mb-3">
                    <label for="chaveApiInput" class="form-label">Chave de API</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="chaveApiInput" readonly>
                        <button class="btn btn-outline-primary" type="button" onclick="copiarChaveApi()">
                            <i class="fas fa-copy"></i> Copiar
                        </button>
                    </div>
                </div>
                <div class="alert alert-warning mb-0">
                    <strong><i class="fas fa-exclamation-triangle"></i> Importante:</strong> Guarde esta chave em local seguro. Você não poderá vê-la novamente.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Carregar sistemas via AJAX
    async function carregarSistemas() {
        try {
            const response = await fetchComToken('/listarSistemas');
            const data = await response.json();
            
            const tbody = document.querySelector('#tabelaSistemas tbody');
            
            if (!data.error && data.result && data.result.length > 0) {
                const sistemas = data.result;
                tbody.innerHTML = sistemas.map(sistema => `
                    <tr>
                        <td><strong>${escapeHtml(sistema.nome)}</strong></td>
                        <td>${escapeHtml(sistema.nome_remetente)}</td>
                        <td><code>${escapeHtml(sistema.email_remetente)}</code></td>
                        <td>
                            ${sistema.status == 'ativo' ? 
                                '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Ativo</span>' : 
                                '<span class="badge badge-danger"><i class="fas fa-times-circle"></i> Inativo</span>'
                            }
                        </td>
                        <td>
                            <small class="text-muted">
                                ${formatarData(sistema.data_criacao)}
                            </small>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-primary" 
                                        onclick="showChaveApiModal('${sistema.idsistema}', '${escapeHtml(sistema.chave_api)}', '${escapeHtml(sistema.nome)}')"
                                        title="Ver Chave de API">
                                    <i class="fas fa-key"></i> Chave
                                </button>
                                <a href="<?php echo $base; ?>/editar-sistema/${sistema.idsistema}" 
                                   class="btn btn-outline-secondary" title="Editar Sistema">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                <button type="button" class="btn btn-outline-danger" 
                                        onclick="deletarSistema(${sistema.idsistema}, '${escapeHtml(sistema.nome)}', '<?php echo $base; ?>')"
                                        title="Deletar Sistema">
                                    <i class="fas fa-trash"></i> Deletar
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="empty-state">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted mb-3">Nenhum sistema cadastrado ainda.</p>
                                <a href="<?php echo $base; ?>/criar-sistema" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Criar Primeiro Sistema
                                </a>
                            </div>
                        </td>
                    </tr>
                `;
            }
        } catch (error) {
            console.error('Erro ao carregar sistemas:', error);
            const tbody = document.querySelector('#tabelaSistemas tbody');
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4">
                        <i class="fas fa-times-circle text-danger fa-3x mb-3"></i>
                        <p class="text-danger">Erro ao carregar sistemas: ${error.message}</p>
                        <button class="btn btn-primary" onclick="carregarSistemas()">
                            <i class="fas fa-sync"></i> Tentar Novamente
                        </button>
                    </td>
                </tr>
            `;
        }
    }

    function showChaveApiModal(idsistema, chaveApi, nomeSistema) {
        document.getElementById('sistemaNome').textContent = nomeSistema;
        document.getElementById('chaveApiInput').value = chaveApi;
        const modal = new bootstrap.Modal(document.getElementById('chaveApiModal'));
        modal.show();
    }

    function copiarChaveApi() {
        const chaveInput = document.getElementById('chaveApiInput');
        const button = event.target.closest('button');
        copyToClipboard(chaveInput.value, button);
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

    function formatarData(dataString) {
        if (!dataString) return 'N/A';
        const data = new Date(dataString);
        return data.toLocaleString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // Carregar ao iniciar a página
    document.addEventListener('DOMContentLoaded', carregarSistemas);
</script>

<?php $render('footer'); ?>
