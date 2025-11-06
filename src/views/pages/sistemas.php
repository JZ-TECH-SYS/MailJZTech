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

<?php if (!empty($mensagem)): ?>
    <div class="alert alert-<?php echo $tipo_mensagem ?? 'info'; ?> alert-dismissible fade show" role="alert">
        <?php echo $mensagem; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($sistemas)): ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
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
                    <?php foreach ($sistemas as $sistema): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($sistema['nome']); ?></strong></td>
                            <td><?php echo htmlspecialchars($sistema['nome_remetente']); ?></td>
                            <td><code><?php echo htmlspecialchars($sistema['email_remetente']); ?></code></td>
                            <td>
                                <?php if ($sistema['ativo']): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle"></i> Ativo
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger">
                                        <i class="fas fa-times-circle"></i> Inativo
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?php echo date('d/m/Y H:i', strtotime($sistema['data_criacao'])); ?>
                                </small>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-primary" 
                                            onclick="showChaveApiModal('<?php echo htmlspecialchars($sistema['idsistema']); ?>', '<?php echo htmlspecialchars($sistema['chave_api']); ?>', '<?php echo htmlspecialchars($sistema['nome']); ?>')"
                                            data-bs-toggle="tooltip" title="Ver Chave de API">
                                        <i class="fas fa-key"></i> Chave
                                    </button>
                                    <a href="<?php echo $base; ?>/editar-sistema?id=<?php echo $sistema['idsistema']; ?>" 
                                       class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="Editar Sistema">
                                        <i class="fas fa-edit"></i> Editar
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" 
                                            onclick="deletarSistema(<?php echo $sistema['idsistema']; ?>, '<?php echo htmlspecialchars($sistema['nome']); ?>', '<?php echo $base; ?>')"
                                            data-bs-toggle="tooltip" title="Deletar Sistema">
                                        <i class="fas fa-trash"></i> Deletar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="card text-center py-5">
        <div class="empty-state">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <p class="text-muted mb-3">Nenhum sistema cadastrado ainda.</p>
            <a href="<?php echo $base; ?>/criar-sistema" class="btn btn-primary">
                <i class="fas fa-plus"></i> Criar Primeiro Sistema
            </a>
        </div>
    </div>
<?php endif; ?>

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
</script>

<?php $render('footer'); ?>
