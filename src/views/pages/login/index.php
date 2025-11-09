<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MailJZTech</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/sweetalert2.min.css">
    <link href="<?php echo $base; ?>/assets/css/custom.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0a0e27 0%, #1a0f3a 100%);
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
            box-shadow: 0 0 30px rgba(0, 217, 255, 0.2);
            border: 2px solid rgba(0, 217, 255, 0.3);
            border-radius: 15px;
            overflow: hidden;
            background-color: #1a1f3a;
        }

        .login-header {
            background: linear-gradient(90deg, rgba(0, 217, 255, 0.1) 0%, rgba(179, 0, 255, 0.1) 100%);
            color: #00d9ff;
            padding: 30px 20px;
            text-align: center;
            border-bottom: 2px solid rgba(0, 217, 255, 0.3);
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
            background-color: #1a1f3a;
        }

        .form-control {
            border-radius: 8px;
            border: 2px solid rgba(0, 217, 255, 0.3);
            padding: 12px 15px;
            font-size: 15px;
            transition: all 0.3s ease;
            background-color: #141829;
            color: #ffffff;
        }

        .form-control::placeholder {
            color: #7a8199;
        }

        .form-control:focus {
            border-color: #00d9ff;
            background-color: #141829;
            color: #ffffff;
            box-shadow: 0 0 15px rgba(0, 217, 255, 0.3);
        }

        .btn-login {
            background: transparent;
            border: 2px solid #00d9ff;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            color: #00d9ff;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 0 10px rgba(0, 217, 255, 0.3);
        }

        .btn-login:hover {
            background: #00d9ff;
            color: #0a0e27;
            transform: translateY(-2px);
            box-shadow: 0 0 20px rgba(0, 217, 255, 0.6);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            color: #00d9ff;
            margin-bottom: 8px;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
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
            color: #00d9ff;
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
            padding: 10px;
            box-sizing: border-box;
        }

        .modal-2fa.show {
            display: flex;
        }

        .modal-2fa-content {
            background: #1a1f3a;
            border: 2px solid rgba(0, 217, 255, 0.3);
            border-radius: 15px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 0 30px rgba(0, 217, 255, 0.2);
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
            color: #00d9ff;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .modal-2fa-header p {
            color: #b0b8d4;
            font-size: 14px;
        }

        .qr-code-container {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background: #141829;
            border: 1px solid rgba(0, 217, 255, 0.2);
            border-radius: 10px;
        }

        .qr-code-container img {
            max-width: 250px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 217, 255, 0.3);
            background: white;
            padding: 10px;
        }

        .qr-code-container p {
            color: #b0b8d4;
            margin: 0;
        }

        .secret-container {
            background: #141829;
            border: 1px solid rgba(0, 217, 255, 0.2);
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .secret-container label {
            font-size: 13px;
            color: #00d9ff;
            font-weight: 600;
            display: block;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .secret-input {
            font-family: 'Courier New', monospace;
            font-size: 14px;
            text-align: center;
            letter-spacing: 2px;
            background: #0f1419;
            color: #00d9ff;
            border: 1px solid rgba(0, 217, 255, 0.3);
            padding: 12px;
            border-radius: 6px;
        }

        .secret-input:focus {
            background: #0f1419;
            color: #00d9ff;
            border-color: #00d9ff;
            box-shadow: 0 0 10px rgba(0, 217, 255, 0.3);
            outline: none;
        }

        .btn-copy-secret {
            margin-top: 10px;
            background: #00d9ff;
            color: #0a0e27;
            border: none;
            font-weight: 600;
        }

        .btn-copy-secret:hover {
            background: #00b8cc;
            color: #0a0e27;
        }

        .code-input {
            font-size: 24px;
            letter-spacing: 8px;
            text-align: center;
            font-weight: bold;
            font-family: 'Courier New', monospace;
            background: #0f1419;
            color: #00d9ff;
            border: 2px solid rgba(0, 217, 255, 0.4);
            padding: 15px;
            border-radius: 8px;
        }

        .code-input:focus {
            background: #0f1419;
            color: #00d9ff;
            border-color: #00d9ff;
            box-shadow: 0 0 15px rgba(0, 217, 255, 0.4);
            outline: none;
        }

        .backup-codes-container {
            background: rgba(255, 193, 7, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.5);
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            display: none;
        }

        .backup-codes-container.show {
            display: block;
        }

        .backup-codes-container strong {
            color: #ffc107;
            display: block;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .backup-codes-container p {
            color: #b0b8d4;
            font-size: 12px;
            margin: 10px 0;
        }

        .backup-codes-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }

        .backup-code {
            background: #0f1419;
            border: 1px solid rgba(255, 193, 7, 0.3);
            padding: 10px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #ffc107;
            text-align: center;
            font-weight: 600;
            word-break: break-all;
        }

        .loading-spinner {
            display: none;
        }

        .loading-spinner.show {
            display: inline-block;
        }

        /* Form 2FA no modal */
        #form2FA {
            display: block;
        }

        #form2FA .form-group {
            margin-bottom: 20px;
        }

        #form2FA .form-label {
            color: #00d9ff;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }

        #form2FA .form-control {
            border-radius: 8px;
            border: 2px solid rgba(0, 217, 255, 0.3);
            background: #0f1419;
            color: #00d9ff;
            padding: 12px 15px;
        }

        #form2FA .form-control:focus {
            background: #0f1419;
            color: #00d9ff;
            border-color: #00d9ff;
            box-shadow: 0 0 10px rgba(0, 217, 255, 0.3);
        }

        #form2FA button[type="submit"] {
            background: #00d9ff;
            border: none;
            color: #0a0e27;
            font-weight: 600;
            padding: 12px;
            border-radius: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        #form2FA button[type="submit"]:hover {
            background: #00b8cc;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 217, 255, 0.4);
        }

        #form2FA button[type="submit"]:active {
            transform: translateY(0);
        }

        #form2FA button[type="submit"]:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        /* ============================================
           RESPONSIVIDADE - MOBILE E TABLETS
           ============================================ */

        /* Tablets: 768px a 1024px */
        @media (max-width: 1024px) {
            .modal-2fa-content {
                max-width: 90% !important;
                padding: 30px 20px !important;
            }

            .qr-code-container img {
                max-width: 200px !important;
            }

            .code-input {
                font-size: 20px !important;
                letter-spacing: 6px !important;
            }

            .backup-codes-list {
                grid-template-columns: 1fr !important;
            }
        }

        /* Mobile: 480px a 768px */
        @media (max-width: 768px) {
            .modal-2fa {
                padding: 10px !important;
            }

            .modal-2fa-content {
                max-width: 100% !important;
                width: 100% !important;
                padding: 20px 15px !important;
                border-radius: 12px !important;
                max-height: 85vh !important;
            }

            .modal-2fa-header {
                margin-bottom: 15px !important;
            }

            .modal-2fa-header h2 {
                font-size: 18px !important;
                margin-bottom: 8px !important;
            }

            .modal-2fa-header p {
                font-size: 12px !important;
            }

            .qr-code-container {
                margin: 15px 0 !important;
                padding: 15px !important;
            }

            .qr-code-container img {
                max-width: 180px !important;
                padding: 8px !important;
            }

            .secret-container {
                padding: 15px !important;
                margin: 15px 0 !important;
            }

            .secret-container label {
                font-size: 11px !important;
            }

            .secret-input {
                font-size: 12px !important;
                letter-spacing: 1px !important;
                padding: 10px !important;
            }

            .btn-copy-secret {
                font-size: 12px !important;
                padding: 8px 12px !important;
            }

            .code-input {
                font-size: 18px !important;
                letter-spacing: 4px !important;
                padding: 12px !important;
            }

            #form2FA .form-label {
                font-size: 12px !important;
            }

            #form2FA .form-control {
                font-size: 14px !important;
                padding: 10px !important;
            }

            #form2FA button[type="submit"] {
                font-size: 13px !important;
                padding: 10px !important;
            }

            .backup-codes-container {
                padding: 12px !important;
                margin: 15px 0 !important;
            }

            .backup-codes-container strong {
                font-size: 12px !important;
            }

            .backup-codes-container p {
                font-size: 11px !important;
                margin: 8px 0 !important;
            }

            .backup-codes-list {
                grid-template-columns: 1fr 1fr !important;
                gap: 8px !important;
                margin-top: 8px !important;
            }

            .backup-code {
                padding: 8px !important;
                font-size: 11px !important;
            }

            .btn-outline-secondary {
                font-size: 12px !important;
                padding: 8px !important;
            }
        }

        /* Celular pequeno: até 480px */
        @media (max-width: 480px) {
            .modal-2fa {
                padding: 5px !important;
            }

            .modal-2fa-content {
                max-width: 100% !important;
                width: 100% !important;
                padding: 15px 12px !important;
                border-radius: 10px !important;
                max-height: 90vh !important;
            }

            .modal-2fa-header h2 {
                font-size: 16px !important;
                margin-bottom: 5px !important;
            }

            .modal-2fa-header p {
                font-size: 11px !important;
            }

            .qr-code-container {
                margin: 12px 0 !important;
                padding: 12px !important;
            }

            .qr-code-container img {
                max-width: 150px !important;
                padding: 6px !important;
            }

            .secret-container {
                padding: 12px !important;
                margin: 12px 0 !important;
            }

            .secret-container label {
                font-size: 10px !important;
            }

            .secret-input {
                font-size: 11px !important;
                letter-spacing: 0.5px !important;
                padding: 8px !important;
            }

            .btn-copy-secret {
                font-size: 11px !important;
                padding: 6px 8px !important;
            }

            .code-input {
                font-size: 16px !important;
                letter-spacing: 3px !important;
                padding: 10px !important;
            }

            #form2FA .form-group {
                margin-bottom: 12px !important;
            }

            #form2FA .form-label {
                font-size: 11px !important;
            }

            #form2FA .form-control {
                font-size: 13px !important;
                padding: 8px !important;
            }

            #form2FA small {
                font-size: 9px !important;
            }

            #form2FA button[type="submit"] {
                font-size: 12px !important;
                padding: 8px !important;
            }

            .backup-codes-container {
                padding: 10px !important;
                margin: 12px 0 !important;
            }

            .backup-codes-container strong {
                font-size: 11px !important;
            }

            .backup-codes-container p {
                font-size: 10px !important;
                margin: 5px 0 !important;
            }

            .backup-codes-list {
                grid-template-columns: 1fr !important;
                gap: 6px !important;
                margin-top: 6px !important;
            }

            .backup-code {
                padding: 6px !important;
                font-size: 10px !important;
            }

            .btn-outline-secondary {
                font-size: 11px !important;
                padding: 6px !important;
            }
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

            <div class="qr-code-container" id="qrCodeContainer" style="display: none;">
                <img id="qrCode" src="" alt="QR Code">
                <p id="qrLoading" style="color: #b0b8d4;"><i class="fas fa-spinner fa-spin"></i> Gerando QR Code...</p>
            </div>

            <div class="secret-container">
                <label><i class="fas fa-keyboard"></i> Ou insira manualmente:</label>
                <input type="text" class="form-control secret-input" id="secretCode" readonly>
                <button type="button" class="btn btn-sm btn-copy-secret" onclick="copiarSecret()">
                    <i class="fas fa-copy"></i> Copiar Secret
                </button>
                <small class="d-block mt-3" style="color: #b0b8d4; line-height: 1.6;">
                    <strong style="color: #00d9ff;">📱 Aplicativos Suportados:</strong><br>
                    • Google Authenticator<br>
                    • Microsoft Authenticator<br>
                    • Authy<br>
                    • 1Password
                </small>
            </div>

            <form id="form2FA" method="POST" action="<?php echo $base; ?>/confirmar-2fa">
                <input type="hidden" id="secret" name="secret">
                <input type="hidden" id="usuarioId" name="usuario_id">

                <div class="form-group">
                    <label for="codigoTOTP" class="form-label">
                        <i class="fas fa-lock"></i> Insira o código de 6 dígitos
                    </label>
                    <input type="text" class="form-control code-input" id="codigoTOTP" name="codigo" 
                           placeholder="000000" maxlength="6" pattern="\d{6}" required autocomplete="off">
                    <small class="d-block mt-2" style="color: #b0b8d4;">
                        Digite os 6 dígitos do seu autenticador
                    </small>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-check"></i> Verificar e Ativar 2FA
                </button>
            </form>

            <div class="backup-codes-container" id="backupCodesContainer">
                <strong><i class="fas fa-download"></i> Códigos de Backup</strong>
                <p>⚠️ Guarde estes códigos em local seguro. Use-os se perder acesso ao seu autenticador.</p>
                <div class="backup-codes-list" id="backupCodesList"></div>
                <button type="button" class="btn btn-sm btn-outline-secondary w-100 mt-3" onclick="copiarCodigosBackup()">
                    <i class="fas fa-copy"></i> Copiar Todos
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="<?php echo $base; ?>/assets/js/sweetalert2.js"></script>
    <script>
        const base = '<?php echo $base; ?>';

        // ========================================
        // GERENCIAMENTO DE TOKEN
        // ========================================

        /**
         * Salva token no localStorage
         */
        function salvarToken(token) {
            if (token) {
                localStorage.setItem('auth_token', token);
                console.debug('✓ Token salvo:', token.substring(0, 10) + '...');
            }
        }

        /**
         * Recupera token do localStorage
         */
        function obterToken() {
            return localStorage.getItem('auth_token') || null;
        }

        /**
         * Remove token do localStorage
         */
        function removerToken() {
            localStorage.removeItem('auth_token');
            console.debug('✓ Token removido');
        }

        /**
         * Realiza fetch com token no header Authorization
         */
        async function fetchComToken(url, options = {}) {
            const token = obterToken();
            const headers = options.headers || {};

            if (token) {
                headers['Authorization'] = `Bearer ${token}`;
            }

            headers['Content-Type'] = options.headers?.['Content-Type'] || 'application/json';

            return fetch(url, {
                ...options,
                headers
            });
        }

        /**
         * Expõe fetchComToken globalmente para uso em outras páginas
         */
        window.fetchComToken = fetchComToken;

        // Auto-formatar código TOTP
        document.getElementById('codigoTOTP').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Mostrar modal para CONFIGURAR 2FA (com QR/secret)
        function mostrarModal2FA(data) {
            const form = document.getElementById('form2FA');
            form.setAttribute('data-modo', 'config');
            document.getElementById('secretCode').value = data.secret_formatado;
            document.getElementById('secret').value = data.secret;
            document.getElementById('usuarioId').value = data.usuario_id;
            document.getElementById('qrCode').src = data.qr_code_url;
            document.getElementById('qrCodeContainer').style.display = 'block';
            document.getElementById('qrLoading').style.display = 'none';

            // Mostrar blocos de configuração
            document.querySelector('.secret-container').style.display = '';
            document.getElementById('backupCodesContainer').classList.add('show');
            document.querySelector('.modal-2fa-header h2').innerHTML = '<i class="fas fa-shield-alt"></i> Configurar Autenticação';
            document.querySelector('.modal-2fa-header p').textContent = 'Configure a autenticação de dois fatores para sua conta';

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
            }

            document.getElementById('modal2FA').classList.add('show');
            document.getElementById('codigoTOTP').focus();
        }

        // Mostrar modal para VERIFICAR 2FA (sem QR/secret)
        function mostrarModalVerificar2FA(dados) {
            const form = document.getElementById('form2FA');
            form.setAttribute('data-modo', 'verificar');
            document.getElementById('usuarioId').value = dados.idusuario;
            // Esconder QR e secret
            document.getElementById('qrCodeContainer').style.display = 'none';
            document.querySelector('.secret-container').style.display = 'none';
            document.getElementById('backupCodesContainer').classList.remove('show');
            // Título e descrição
            document.querySelector('.modal-2fa-header h2').innerHTML = '<i class="fas fa-shield-alt"></i> Verificar 2FA';
            document.querySelector('.modal-2fa-header p').textContent = 'Abra seu autenticador e informe o código de 6 dígitos';

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
                toastSucesso('Secret copiado!');
                setTimeout(() => {
                    btn.innerHTML = originalText;
                }, 2000);
            }).catch(() => {
                toastErro('Erro ao copiar secret');
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
                toastSucesso('Códigos de backup copiados!');
                setTimeout(() => {
                    btn.innerHTML = originalText;
                }, 2000);
            }).catch(() => {
                toastErro('Erro ao copiar códigos');
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
                const response = await fetch('<?php echo $base; ?>/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        email: document.getElementById('email').value,
                        senha: document.getElementById('senha').value
                    })
                });

                const envelope = await response.json();
                console.debug('LOGIN envelope:', envelope);
                const data = envelope.result || {};

                if (envelope.error) {
                    toastErro((data && data.mensagem) || 'Erro ao fazer login');
                } else {
                    // Salvar token temporário (será sobrescrito após 2FA)
                    if (data.token) {
                        salvarToken(data.token);
                    }

                    // Fluxo login OK
                    if (data.requer_2fa) {
                        // Usuário já tem 2FA: abrir modal somente para verificar código
                        mostrarModalVerificar2FA(data);
                    } else if (data.configurar_2fa) {
                        // Necessário configurar: chamar POST /login com acao=iniciar_2fa
                        const initResp = await fetch('<?php echo $base; ?>/login', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ 
                                email: document.getElementById('email').value,
                                senha: document.getElementById('senha').value,
                                acao: 'iniciar_2fa' 
                            })
                        });
                        const initEnvelope = await initResp.json();
                        console.debug('INICIAR-2FA envelope:', initEnvelope);
                        if (initEnvelope.error) {
                            toastErro('Falha ao iniciar configuração 2FA');
                        } else {
                            mostrarModal2FA(initEnvelope.result);
                        }
                    } else {
                        // Login sem 2FA exigido
                        window.location.href = '<?php echo $base; ?>/dashboard';
                    }
                }
            } catch (error) {
                toastErro('Erro ao conectar com o servidor');
                console.error(error);
            } finally {
                spinner.classList.remove('show');
                btnText.style.opacity = '1';
            }
        });

        // Processar 2FA (configurar ou verificar) no mesmo modal
        document.getElementById('form2FA').addEventListener('submit', async function(e) {
            e.preventDefault();

            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verificando...';

            try {
                const modo = this.getAttribute('data-modo') || 'config';
                const email = document.getElementById('email').value;
                const senha = document.getElementById('senha').value;
                const codigo = document.getElementById('codigoTOTP').value;

                let payload = {
                    email: email,
                    senha: senha
                };

                if (modo === 'config') {
                    // Confirmar configuração de 2FA
                    payload.acao = 'confirmar_2fa';
                    payload.secret = document.getElementById('secret').value;
                    payload.codigo = codigo;
                } else {
                    // Verificar 2FA durante login
                    payload.codigo_2fa = codigo;
                }

                const response = await fetch('<?php echo $base; ?>/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const envelope = await response.json();
                console.debug('CONFIRM/VERIFY 2FA envelope:', envelope);
                const data = envelope.result || {};

                if (!envelope.error && (data.success || envelope.result === 'Logout realizado com sucesso')) {
                    // Token já foi salvo no primeiro login, agora só redireciona
                    console.debug('✓ 2FA confirmado, redirecionando...');
                    toastSucesso('2FA confirmado com sucesso!', 1500);
                    setTimeout(() => {
                        window.location.href = '<?= $base; ?>/dashboard';
                    }, 1500);
                } else {
                    toastErro(data.mensagem || 'Código inválido');
                    document.getElementById('codigoTOTP').value = '';
                    document.getElementById('codigoTOTP').focus();
                }
            } catch (error) {
                toastErro('Erro ao verificar código');
                console.error(error);
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check"></i> Verificar e Ativar 2FA';
            }
        });
    </script>
</body>
</html>
