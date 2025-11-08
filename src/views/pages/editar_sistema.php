<?php $render('header'); ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <h2 class="mb-4">
            <i class="fas fa-edit"></i> Editar Sistema
        </h2>

        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?php echo $tipo_mensagem ?? 'info'; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensagem; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($sistema)): ?>
            <div class="card">
                <div class="card-body">
                    <form id="formAtualizarSistema" method="POST">
                        <input type="hidden" name="idsistema" value="<?php echo $sistema['idsistema']; ?>">

                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome do Sistema <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nome" name="nome" 
                                   value="<?php echo htmlspecialchars($sistema['nome']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="descricao" class="form-label">Descrição</label>
                            <textarea class="form-control" id="descricao" name="descricao" rows="3"><?php echo htmlspecialchars($sistema['descricao'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="nome_remetente" class="form-label">Nome do Remetente <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nome_remetente" name="nome_remetente" 
                                   value="<?php echo htmlspecialchars($sistema['nome_remetente']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">E-mail do Remetente</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($sistema['email_remetente']); ?>" disabled>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="ativo" name="ativo" value="1" 
                                   <?php echo $sistema['status'] === 'ativo' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="ativo">
                                Sistema Ativo
                            </label>
                            <small class="form-text d-block text-muted">Desmarque para desativar o sistema (ele não poderá enviar e-mails)</small>
                        </div>

                        <div class="mb-4">
                            <label for="chaveApi" class="form-label">Chave de API</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="chaveApi" 
                                       value="<?php echo htmlspecialchars($sistema['chave_api']); ?>" readonly>
                                <button class="btn btn-outline-primary" type="button" 
                                        onclick="copyToClipboard('<?php echo htmlspecialchars($sistema['chave_api']); ?>', this)">
                                    <i class="fas fa-copy"></i> Copiar
                                </button>
                            </div>
                            <small class="form-text text-muted">Use esta chave para autenticar as requisições da API</small>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-between">
                            <a href="<?php echo $base; ?>/sistemas" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Voltar
                            </a>
                            <button type="submit" class="btn btn-primary" id="btnAtualizar">
                                <i class="fas fa-check"></i> Atualizar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle"></i> Zona de Perigo
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Regenerar Chave de API</label>
                        <p class="text-muted small">Gere uma nova chave de API. A chave anterior não funcionará mais.</p>
                        <button type="button" class="btn btn-danger" 
                                onclick="regenerarChave(<?php echo $sistema['idsistema']; ?>, '<?php echo $base; ?>')">
                            <i class="fas fa-sync"></i> Regenerar Chave
                        </button>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-times-circle"></i> Sistema não encontrado.
            </div>
            <a href="<?php echo $base; ?>/sistemas" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Modal para nova chave de API -->
<div class="modal fade" id="novaChaveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle"></i> Chave Regenerada
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">Sua nova chave de API foi gerada com sucesso!</p>
                <div class="input-group mb-3">
                    <input type="text" class="form-control" id="novaChaveInput" readonly>
                    <button class="btn btn-outline-primary" type="button" 
                            onclick="copyToClipboard(document.getElementById('novaChaveInput').value, this)">
                        <i class="fas fa-copy"></i> Copiar
                    </button>
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
    document.getElementById('formAtualizarSistema').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const idsistema = document.querySelector('input[name="idsistema"]').value;
        const btnAtualizar = document.getElementById('btnAtualizar');
        const originalText = btnAtualizar.innerHTML;
        
        btnAtualizar.disabled = true;
        btnAtualizar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Atualizando...';
        
        try {
            const dados = {
                nome: document.getElementById('nome').value,
                descricao: document.getElementById('descricao').value,
                nome_remetente: document.getElementById('nome_remetente').value,
                ativo: document.getElementById('ativo').checked ? 1 : 0
            };
            
            const response = await fetchComToken(`<?php echo $base; ?>/atualizarSistema/${idsistema}`, {
                method: 'PUT',
                body: JSON.stringify(dados)
            });
            
            const data = await response.json();
            
            if (!data.error && data.result) {
                alert('✅ ' + (data.result.mensagem || 'Sistema atualizado com sucesso!'));
                window.location.href = '<?php echo $base; ?>/sistemas';
            } else {
                alert('❌ ' + (data.result || 'Erro ao atualizar sistema'));
            }
        } catch (error) {
            alert('❌ Erro ao conectar com o servidor: ' + error.message);
            console.error(error);
        } finally {
            btnAtualizar.disabled = false;
            btnAtualizar.innerHTML = originalText;
        }
    });
</script>

<?php $render('footer'); ?>
