<?php $render('header'); ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <h2 class="mb-4">
            <i class="fas fa-shield-alt"></i> Configurar Autenticação de Dois Fatores (2FA)
        </h2>

        <?php if (!empty($mensagem)): ?>
            <div class="alert alert-<?php echo $tipo_mensagem ?? 'info'; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensagem; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($totp_habilitado)): ?>
            <!-- Configuração de 2FA -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-qrcode"></i> Ativar 2FA
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Escaneie o código QR abaixo com seu aplicativo autenticador:</p>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="text-center mb-4">
                                <img src="<?php echo htmlspecialchars($qr_code_url ?? ''); ?>" alt="QR Code" class="img-fluid border rounded" style="max-width: 300px;">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <i class="fas fa-keyboard"></i> Inserir Manualmente
                                    </h6>
                                    <p class="text-muted small">Se não conseguir escanear o QR Code, insira este código manualmente:</p>
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control font-monospace" value="<?php echo htmlspecialchars($secret_formatado ?? ''); ?>" readonly>
                                        <button class="btn btn-outline-primary" type="button" onclick="copiarSecret()">
                                            <i class="fas fa-copy"></i> Copiar
                                        </button>
                                    </div>
                                    <small class="text-muted">
                                        <strong>Aplicativos suportados:</strong><br>
                                        • Google Authenticator<br>
                                        • Microsoft Authenticator<br>
                                        • Authy<br>
                                        • FreeOTP
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <h6 class="mt-4">Verificar Código</h6>
                    <p class="text-muted">Insira o código de 6 dígitos do seu aplicativo autenticador para confirmar:</p>

                    <form method="POST" action="<?php echo $base; ?>/confirmar2fa">
                        <input type="hidden" name="secret" value="<?php echo htmlspecialchars($secret ?? ''); ?>">

                        <div class="mb-3">
                            <label for="codigo" class="form-label">Código de Verificação</label>
                            <input type="text" class="form-control form-control-lg text-center font-monospace" 
                                   id="codigo" name="codigo" placeholder="000000" maxlength="6" 
                                   pattern="\d{6}" required autocomplete="off">
                            <small class="form-text text-muted">Insira os 6 dígitos do seu autenticador</small>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-check"></i> Verificar e Ativar 2FA
                        </button>
                    </form>
                </div>
            </div>

            <!-- Códigos de Backup -->
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle"></i> Códigos de Backup
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Guarde estes códigos em um local seguro. Você pode usá-los para acessar sua conta se perder o acesso ao seu autenticador.</p>

                    <div class="alert alert-warning">
                        <strong><i class="fas fa-info-circle"></i> Importante:</strong> Cada código pode ser usado apenas uma vez. Guarde-os em local seguro!
                    </div>

                    <div class="bg-light p-3 rounded">
                        <div class="row">
                            <?php if (!empty($backup_codes)): ?>
                                <?php foreach ($backup_codes as $code): ?>
                                    <div class="col-md-6 mb-2">
                                        <code class="font-monospace"><?php echo htmlspecialchars($code); ?></code>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button type="button" class="btn btn-outline-secondary" onclick="copiarCodigosBackup()">
                            <i class="fas fa-copy"></i> Copiar Todos
                        </button>
                        <a href="#" class="btn btn-outline-secondary" onclick="imprimirCodigosBackup(); return false;">
                            <i class="fas fa-print"></i> Imprimir
                        </a>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- 2FA Já Habilitado -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-check-circle"></i> 2FA Ativado
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-success">
                        <strong>Sua autenticação de dois fatores está ativa!</strong>
                    </p>
                    <p class="text-muted">Você precisará inserir um código do seu autenticador cada vez que fizer login.</p>

                    <div class="alert alert-info">
                        <strong><i class="fas fa-info-circle"></i> Informação:</strong> 
                        2FA foi ativado em <?php echo date('d/m/Y H:i', strtotime($data_2fa_alteracao ?? 'now')); ?>
                    </div>
                </div>
            </div>

            <!-- Opções de Gerenciamento -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-cog"></i> Gerenciar 2FA
                    </h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="<?php echo $base; ?>/gerar-backup-codes" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><i class="fas fa-redo"></i> Gerar Novos Códigos de Backup</h6>
                                    <p class="mb-0 text-muted small">Gere um novo conjunto de códigos de backup</p>
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>

                        <a href="<?php echo $base; ?>/reconfigurare-2fa" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><i class="fas fa-sync"></i> Reconfigurare 2FA</h6>
                                    <p class="mb-0 text-muted small">Desative e reative 2FA com um novo autenticador</p>
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>

                        <a href="<?php echo $base; ?>/desabilitar-2fa" class="list-group-item list-group-item-action text-danger" 
                           onclick="return confirm('Tem certeza que deseja desabilitar 2FA? Sua conta ficará menos segura.');">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1"><i class="fas fa-times-circle"></i> Desabilitar 2FA</h6>
                                    <p class="mb-0 text-muted small">Desative a autenticação de dois fatores</p>
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function copiarSecret() {
        const secret = '<?php echo htmlspecialchars($secret_formatado ?? ''); ?>';
        copyToClipboard(secret.replace(/\s/g, ''), event.target.closest('button'));
    }

    function copiarCodigosBackup() {
        const codigos = document.querySelectorAll('.bg-light code');
        let texto = '';
        codigos.forEach(codigo => {
            texto += codigo.textContent + '\n';
        });
        copyToClipboard(texto, event.target.closest('button'));
    }

    function imprimirCodigosBackup() {
        const conteudo = document.querySelector('.bg-light').innerHTML;
        const janela = window.open('', '', 'height=400,width=600');
        janela.document.write('<html><head><title>Códigos de Backup - MailJZTech</title>');
        janela.document.write('<style>body { font-family: Arial; } code { display: block; margin: 10px 0; }</style>');
        janela.document.write('</head><body>');
        janela.document.write('<h2>Códigos de Backup - MailJZTech</h2>');
        janela.document.write('<p>Guarde estes códigos em um local seguro:</p>');
        janela.document.write(conteudo);
        janela.document.write('</body></html>');
        janela.document.close();
        janela.print();
    }

    // Auto-focus no campo de código
    document.addEventListener('DOMContentLoaded', function() {
        const codigoInput = document.getElementById('codigo');
        if (codigoInput) {
            codigoInput.focus();
            // Permitir apenas números
            codigoInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }
    });
</script>

<?php $render('footer'); ?>
