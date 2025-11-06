<?php
// Não renderizar header/footer para página de login
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MailJZTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo $base; ?>/assets/css/custom.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }

        .login-card {
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .login-header h1 {
            font-size: 28px;
            font-weight: bold;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .login-header p {
            margin: 10px 0 0 0;
            font-size: 14px;
            opacity: 0.9;
        }

        .login-body {
            padding: 40px 30px;
        }

        .form-control {
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            padding: 12px 15px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: block;
        }

        .alert {
            border-radius: 8px;
            border: none;
            margin-bottom: 20px;
        }

        .spinner-border {
            width: 20px;
            height: 20px;
            margin-right: 8px;
        }

        /* Modal 2FA */
        .modal-2fa {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-2fa.show {
            display: flex;
        }

        .modal-2fa-content {
            background: white;
            border-radius: 15px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-2fa-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .modal-2fa-header h2 {
            color: #333;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .modal-2fa-header p {
            color: #666;
            font-size: 14px;
        }

        .qr-code-container {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 10px;
        }

        .qr-code-container img {
            max-width: 250px;
            border-radius: 8px;
        }

        .secret-container {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .secret-container label {
            font-size: 12px;
            color: #666;
            display: block;
            margin-bottom: 8px;
        }

        .secret-input {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            text-align: center;
            letter-spacing: 2px;
        }

        .btn-copy-secret {
            margin-top: 10px;
        }

        .code-input {
            font-size: 24px;
            letter-spacing: 8px;
            text-align: center;
            font-weight: bold;
            font-family: 'Courier New', monospace;
        }

        .backup-codes-container {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            display: none;
        }

        .backup-codes-container.show {
            display: block;
        }

        .backup-codes-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }

        .backup-code {
            background: white;
            padding: 8px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            text-align: center;
        }

        .loading-spinner {
            display: none;
        }

        .loading-spinner.show {
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="card login-card">
            <div class="login-header">
                <h1>
                    <i class="fas fa-envelope"></i> MailJZTech
                </h1>
                <p>Serviço de Envio de E-mails</p>
            </div>

            <div class="login-body">
                <?php if (!empty($mensagem_erro)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($mensagem_erro); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form id="formLogin" method="POST" action="<?php echo $base; ?>/login">
                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i> E-mail
                        </label>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="seu@email.com" required autocomplete="email">
                    </div>

                    <div class="form-group">
                        <label for="senha" class="form-label">
                            <i class="fas fa-lock"></i> Senha
                        </label>
                        <input type="password" class="form-control" id="senha" name="senha" 
                               placeholder="Sua senha" required autocomplete="current-password">
                    </div>

                    <button type="submit" class="btn btn-login w-100 text-white">
                        <span class="loading-spinner" id="spinner">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        </span>
                        <span id="btnText"><i class="fas fa-sign-in-alt"></i> Entrar</span>
                    </button>
                </form>

                <div class="text-center mt-4">
                    <p class="text-muted" style="font-size: 12px;">
                        <i class="fas fa-shield-alt"></i> Sua conexão é segura e criptografada
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal 2FA -->
    <div class="modal-2fa" id="modal2FA">
        <div class="modal-2fa-content">
            <div class="modal-2fa-header">
                <h2>
                    <i class="fas fa-shield-alt"></i> Configurar Autenticação
                </h2>
                <p>Configure a autenticação de dois fatores para sua conta</p>
            </div>

            <div class="qr-code-container">
                <img id="qrCode" src="" alt="QR Code" style="display: none;">
                <p id="qrLoading" style="color: #666;">Gerando QR Code...</p>
            </div>

            <div class="secret-container">
                <label>Ou insira manualmente:</label>
                <input type="text" class="form-control secret-input" id="secretCode" readonly>
                <button type="button" class="btn btn-sm btn-outline-primary btn-copy-secret" onclick="copiarSecret()">
                    <i class="fas fa-copy"></i> Copiar
                </button>
                <small class="d-block mt-2" style="color: #666;">
                    <strong>Aplicativos:</strong> Google Authenticator, Microsoft Authenticator, Authy
                </small>
            </div>

            <form id="form2FA" method="POST" action="<?php echo $base; ?>/confirmar-2fa">
                <input type="hidden" id="secret" name="secret">
                <input type="hidden" id="usuarioId" name="usuario_id">

                <div class="form-group">
                    <label for="codigoTOTP" class="form-label">
                        Insira o código de 6 dígitos
                    </label>
                    <input type="text" class="form-control code-input" id="codigoTOTP" name="codigo" 
                           placeholder="000000" maxlength="6" pattern="\d{6}" required autocomplete="off">
                    <small class="d-block mt-2" style="color: #666;">
                        Insira os 6 dígitos do seu autenticador
                    </small>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-check"></i> Verificar e Ativar 2FA
                </button>
            </form>

            <div class="backup-codes-container" id="backupCodesContainer">
                <strong><i class="fas fa-exclamation-triangle"></i> Códigos de Backup</strong>
                <p style="font-size: 12px; margin-top: 10px;">Guarde estes códigos em local seguro:</p>
                <div class="backup-codes-list" id="backupCodesList"></div>
                <button type="button" class="btn btn-sm btn-outline-secondary w-100 mt-3" onclick="copiarCodigosBackup()">
                    <i class="fas fa-copy"></i> Copiar Todos
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const base = '<?php echo $base; ?>';

        // Auto-formatar código TOTP
        document.getElementById('codigoTOTP').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Mostrar modal 2FA quando necessário
        function mostrarModal2FA(data) {
            document.getElementById('secretCode').value = data.secret_formatado;
            document.getElementById('secret').value = data.secret;
            document.getElementById('usuarioId').value = data.usuario_id;
            document.getElementById('qrCode').src = data.qr_code_url;
            document.getElementById('qrCode').style.display = 'block';
            document.getElementById('qrLoading').style.display = 'none';

            // Exibir códigos de backup
            if (data.backup_codes && data.backup_codes.length > 0) {
                const container = document.getElementById('backupCodesList');
                container.innerHTML = '';
                data.backup_codes.forEach(code => {
                    const div = document.createElement('div');
                    div.className = 'backup-code';
                    div.textContent = code;
                    container.appendChild(div);
                });
                document.getElementById('backupCodesContainer').classList.add('show');
            }

            document.getElementById('modal2FA').classList.add('show');
            document.getElementById('codigoTOTP').focus();
        }

        // Copiar secret
        function copiarSecret() {
            const secret = document.getElementById('secretCode').value.replace(/\s/g, '');
            navigator.clipboard.writeText(secret).then(() => {
                const btn = event.target.closest('button');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Copiado!';
                setTimeout(() => {
                    btn.innerHTML = originalText;
                }, 2000);
            });
        }

        // Copiar códigos de backup
        function copiarCodigosBackup() {
            const codigos = Array.from(document.querySelectorAll('.backup-code'))
                .map(el => el.textContent)
                .join('\n');
            navigator.clipboard.writeText(codigos).then(() => {
                const btn = event.target.closest('button');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-check"></i> Copiado!';
                setTimeout(() => {
                    btn.innerHTML = originalText;
                }, 2000);
            });
        }

        // Processar login
        document.getElementById('formLogin').addEventListener('submit', async function(e) {
            e.preventDefault();

            const spinner = document.getElementById('spinner');
            const btnText = document.getElementById('btnText');
            spinner.classList.add('show');
            btnText.style.opacity = '0.5';

            try {
                const response = await fetch('<?php echo $base; ?>/api/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        email: document.getElementById('email').value,
                        senha: document.getElementById('senha').value
                    })
                });

                const data = await response.json();

                if (data.success) {
                    if (data.requer_2fa) {
                        // Mostrar modal 2FA
                        mostrarModal2FA(data);
                    } else {
                        // Login bem-sucedido
                        window.location.href = '<?php echo $base; ?>/dashboard';
                    }
                } else {
                    // Erro no login
                    alert(data.mensagem || 'Erro ao fazer login');
                }
            } catch (error) {
                alert('Erro ao conectar com o servidor');
                console.error(error);
            } finally {
                spinner.classList.remove('show');
                btnText.style.opacity = '1';
            }
        });

        // Processar 2FA
        document.getElementById('form2FA').addEventListener('submit', async function(e) {
            e.preventDefault();

            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verificando...';

            try {
                const response = await fetch('<?php echo $base; ?>/api/confirmar-2fa', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        usuario_id: document.getElementById('usuarioId').value,
                        secret: document.getElementById('secret').value,
                        codigo: document.getElementById('codigoTOTP').value
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // 2FA confirmado, redirecionar para dashboard
                    window.location.href = '<?php echo $base; ?>/dashboard';
                } else {
                    alert(data.mensagem || 'Código inválido');
                    document.getElementById('codigoTOTP').value = '';
                    document.getElementById('codigoTOTP').focus();
                }
            } catch (error) {
                alert('Erro ao verificar código');
                console.error(error);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check"></i> Verificar e Ativar 2FA';
            }
        });
    </script>
</body>
</html>
