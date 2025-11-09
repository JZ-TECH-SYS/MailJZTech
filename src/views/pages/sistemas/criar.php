<?php $render('header'); ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <h2 class="mb-4">
            <i class="fas fa-plus-circle"></i> Criar Novo Sistema
        </h2>

        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?php echo $tipo_mensagem ?? 'info'; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensagem; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form id="formCriarSistema">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome do Sistema <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nome" name="nome" 
                               placeholder="Ex: ClickExpress, PapelZero, etc" required>
                        <small class="form-text text-muted">Nome que identifica o sistema na plataforma</small>
                    </div>

                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"
                                  placeholder="Descrição opcional do sistema"></textarea>
                        <small class="form-text text-muted">Informações adicionais sobre o sistema</small>
                    </div>

                    <div class="mb-3">
                        <label for="nome_remetente" class="form-label">Nome do Remetente <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nome_remetente" name="nome_remetente" 
                               placeholder="Ex: ClickExpress System" required>
                        <small class="form-text text-muted">Nome que aparecerá como remetente nos e-mails enviados</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">E-mail do Remetente</label>
                        <input type="text" class="form-control" value="contato@jztech.com.br" disabled>
                        <small class="form-text text-muted">E-mail padrão para todos os envios (não pode ser alterado)</small>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-between mt-4">
                        <a href="<?php echo $base; ?>/sistemas" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                        <button type="submit" class="btn btn-primary" id="btnSubmit">
                            <i class="fas fa-check"></i> Criar Sistema
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-4 bg-light">
            <div class="card-body">
                <h5 class="card-title text-primary">
                    <i class="fas fa-info-circle"></i> Como funciona
                </h5>
                <ol class="mb-0">
                    <li>Crie um novo sistema informando um nome e o nome do remetente</li>
                    <li>Uma chave de API será gerada automaticamente</li>
                    <li>Use essa chave para autenticar as requisições de envio de e-mail</li>
                    <li>Todos os e-mails sairão do endereço <code>contato@jztech.com.br</code></li>
                    <li>O nome do remetente será personalizado conforme você configurou</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Sucesso -->
<div class="modal fade" id="modalSucesso" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle"></i> Sistema Criado!
                </h5>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>IMPORTANTE:</strong> Copie e guarde a chave de API abaixo. Você não poderá vê-la novamente!
                </div>
                <div class="mb-3">
                    <label class="form-label"><strong>Nome do Sistema:</strong></label>
                    <p id="sistemaNome"></p>
                </div>
                <div class="mb-3">
                    <label class="form-label"><strong>Chave de API:</strong></label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="chaveApi" readonly>
                        <button class="btn btn-primary" onclick="copiarChave()">
                            <i class="fas fa-copy"></i> Copiar
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="<?php echo $base; ?>/sistemas" class="btn btn-success">
                    <i class="fas fa-list"></i> Ir para Sistemas
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('formCriarSistema').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const btnSubmit = document.getElementById('btnSubmit');
    const btnOriginal = btnSubmit.innerHTML;
    
    // Desabilitar botão e mostrar loading
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Criando...';
    
    try {
        const formData = {
            nome: document.getElementById('nome').value.trim(),
            descricao: document.getElementById('descricao').value.trim(),
            nome_remetente: document.getElementById('nome_remetente').value.trim()
        };
        
        const response = await fetchComToken('<?php echo $base; ?>/criarSistema', {
            method: 'POST',
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (!data.error && data.result) {
            // Mostrar modal com chave de API
            document.getElementById('sistemaNome').textContent = data.result.nome || formData.nome;
            document.getElementById('chaveApi').value = data.result.chave_api;
            
            const modal = new bootstrap.Modal(document.getElementById('modalSucesso'));
            modal.show();
        } else {
            alert('Erro: ' + (data.result || 'Erro ao criar sistema'));
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = btnOriginal;
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro ao criar sistema: ' + error.message);
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = btnOriginal;
    }
});

function copiarChave() {
    const input = document.getElementById('chaveApi');
    input.select();
    document.execCommand('copy');
    
    // Feedback visual
    const btn = event.target.closest('button');
    const originalHTML = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-check"></i> Copiado!';
    setTimeout(() => {
        btn.innerHTML = originalHTML;
    }, 2000);
}
</script>

<?php $render('footer'); ?>
