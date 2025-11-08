<?php $render('header'); ?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow-lg">
            <div class="card-header bg-primary text-white text-center py-4">
                <h4 class="mb-0">
                    <i class="fas fa-shield-alt"></i> Verificação de Segurança
                </h4>
            </div>
            <div class="card-body p-4">
                <p class="text-center text-muted mb-4">
                    Insira o código de 6 dígitos do seu autenticador para continuar
                </p>

                <?php if (!empty($mensagem_erro)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $mensagem_erro; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form id="form2fa">
                    <input type="hidden" id="usuario_id" value="<?php echo htmlspecialchars($usuario_id ?? ''); ?>">

                    <div class="mb-4">
                        <label for="codigo_totp" class="form-label">
                            <i class="fas fa-mobile-alt"></i> Código do Autenticador
                        </label>
                        <input type="text" class="form-control form-control-lg text-center font-monospace" 
                               id="codigo_totp" name="codigo_totp" placeholder="000000" maxlength="6" 
                               pattern="\d{6}" required autocomplete="off" autofocus>
                        <small class="form-text text-muted d-block mt-2">
                            Insira os 6 dígitos do Google Authenticator ou Microsoft Authenticator
                        </small>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 btn-lg mb-3" id="btnVerificar">
                        <i class="fas fa-check"></i> Verificar Código
                    </button>
                </form>

                <hr>

                <details class="mt-3">
                    <summary class="text-muted cursor-pointer">
                        <i class="fas fa-key"></i> Usar Código de Backup
                    </summary>
                    <form id="formBackup" class="mt-3">
                        <input type="hidden" id="usuario_id_backup" value="<?php echo htmlspecialchars($usuario_id ?? ''); ?>">
                        <div class="mb-3">
                            <label for="codigo_backup" class="form-label">Código de Backup</label>
                            <input type="text" class="form-control" id="codigo_backup" name="codigo_backup" 
                                   placeholder="XXXX-XXXX" required>
                            <small class="form-text text-muted">
                                Insira um dos seus códigos de backup (formato: XXXX-XXXX)
                            </small>
                        </div>
                        <button type="submit" class="btn btn-outline-secondary w-100" id="btnBackup">
                            <i class="fas fa-key"></i> Verificar Código de Backup
                        </button>
                    </form>
                </details>

                <div class="alert alert-info mt-4 mb-0">
                    <strong><i class="fas fa-info-circle"></i> Dica:</strong> 
                    Se você perdeu acesso ao seu autenticador, use um dos seus códigos de backup.
                </div>
            </div>
        </div>

        <div class="text-center mt-3">
            <a href="<?php echo $base; ?>/logout" class="text-muted small">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </div>
    </div>
</div>

<style>
    details summary {
        cursor: pointer;
        user-select: none;
    }
    
    details summary:hover {
        text-decoration: underline;
    }
</style>

<script>
    // Permitir apenas números no campo de código TOTP
    document.getElementById('codigo_totp').addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Permitir apenas números e hífen no campo de código de backup
    document.getElementById('codigo_backup').addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9\-]/g, '').toUpperCase();
    });

    // Formulário TOTP
    document.getElementById('form2fa').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const btn = document.getElementById('btnVerificar');
        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
        
        try {
            const dados = {
                codigo_totp: document.getElementById('codigo_totp').value,
                usuario_id: document.getElementById('usuario_id').value
            };
            
            const response = await fetch('<?php echo $base; ?>/verificar-2fa', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(dados)
            });
            
            const data = await response.json();
            
            if (!data.error && data.result) {
                window.location.href = '<?php echo $base; ?>/dashboard';
            } else {
                alert('❌ ' + (data.result || 'Código inválido'));
                btn.disabled = false;
                btn.innerHTML = originalHTML;
                document.getElementById('codigo_totp').value = '';
                document.getElementById('codigo_totp').focus();
            }
        } catch (error) {
            alert('❌ Erro: ' + error.message);
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        }
    });

    // Formulário Backup
    document.getElementById('formBackup').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const btn = document.getElementById('btnBackup');
        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
        
        try {
            const dados = {
                codigo_backup: document.getElementById('codigo_backup').value,
                usuario_id: document.getElementById('usuario_id_backup').value
            };
            
            const response = await fetch('<?php echo $base; ?>/verificar-2fa-backup', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(dados)
            });
            
            const data = await response.json();
            
            if (!data.error && data.result) {
                window.location.href = '<?php echo $base; ?>/dashboard';
            } else {
                alert('❌ ' + (data.result || 'Código de backup inválido'));
                btn.disabled = false;
                btn.innerHTML = originalHTML;
                document.getElementById('codigo_backup').value = '';
                document.getElementById('codigo_backup').focus();
            }
        } catch (error) {
            alert('❌ Erro: ' + error.message);
            btn.disabled = false;
            btn.innerHTML = originalHTML;
        }
    });
</script>

<?php $render('footer'); ?>
