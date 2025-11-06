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
                <form method="POST" action="<?php echo $base; ?>/criarSistema">
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
                        <button type="submit" class="btn btn-primary">
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

<?php $render('footer'); ?>
