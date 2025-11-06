# Implementação de 2FA Obrigatório no MailJZTech

## Visão Geral

O MailJZTech implementa autenticação de dois fatores (2FA) **obrigatória** usando TOTP (Time-based One-Time Password), compatível com:

- Google Authenticator
- Microsoft Authenticator
- Authy
- FreeOTP

## Fluxo de Autenticação

### 1. Primeiro Login (Sem 2FA Configurado)

```
Usuário faz login com email/senha
    ↓
Sistema verifica se totp_secret existe no banco
    ↓
NÃO existe → Redireciona para /configurar-2fa (OBRIGATÓRIO)
    ↓
Usuário escaneia QR Code com autenticador
    ↓
Insere código de 6 dígitos para verificar
    ↓
Sistema gera 10 códigos de backup
    ↓
2FA ativado e armazenado no banco
    ↓
Usuário redirecionado para dashboard
```

### 2. Logins Subsequentes (Com 2FA Ativado)

```
Usuário faz login com email/senha
    ↓
Sistema verifica se totp_secret existe
    ↓
SIM → Redireciona para /verificar-2fa
    ↓
Usuário insere código de 6 dígitos do autenticador
    ↓
Sistema verifica código TOTP
    ↓
Válido → Cria sessão e redireciona para dashboard
Inválido → Mostra erro e permite nova tentativa
    ↓
Opção: Usar código de backup se perdeu autenticador
```

## Estrutura de Dados

### Tabela: usuarios

Colunas adicionadas para 2FA:

```sql
ALTER TABLE usuarios ADD COLUMN totp_secret VARCHAR(255) NULL;
ALTER TABLE usuarios ADD COLUMN totp_habilitado BOOLEAN DEFAULT FALSE;
ALTER TABLE usuarios ADD COLUMN backup_codes JSON NULL;
ALTER TABLE usuarios ADD COLUMN data_2fa_alteracao TIMESTAMP NULL;
ALTER TABLE usuarios ADD COLUMN tentativas_login_falhas INT DEFAULT 0;
ALTER TABLE usuarios ADD COLUMN ultimo_login_sucesso TIMESTAMP NULL;
ALTER TABLE usuarios ADD COLUMN ultimo_ip_login VARCHAR(45) NULL;
```

### Exemplo de Dados

```json
{
  "id": 1,
  "email": "admin@jztech.com.br",
  "totp_secret": "JBSWY3DPEBLW64TMMQ======",
  "totp_habilitado": true,
  "backup_codes": [
    "A1B2C3D4",
    "E5F6G7H8",
    "I9J0K1L2",
    "M3N4O5P6",
    "Q7R8S9T0",
    "U1V2W3X4",
    "Y5Z6A7B8",
    "C9D0E1F2",
    "G3H4I5J6",
    "K7L8M9N0"
  ],
  "data_2fa_alteracao": "2025-11-06 10:30:00",
  "tentativas_login_falhas": 0,
  "ultimo_login_sucesso": "2025-11-06 14:25:00",
  "ultimo_ip_login": "192.168.1.100"
}
```

## Serviço: TwoFactorAuthService

### Métodos Principais

#### `generateSecret(): string`
Gera um novo secret TOTP em base32.

```php
$secret = TwoFactorAuthService::generateSecret();
// Resultado: "JBSWY3DPEBLW64TMMQ======"
```

#### `generateQRCode(string $email, string $secret): string`
Gera URL de QR Code para escanear com autenticador.

```php
$qrUrl = TwoFactorAuthService::generateQRCode(
    'usuario@example.com',
    $secret
);
// Resultado: URL do Google Charts API com QR Code
```

#### `verifyCode(string $secret, string $code): bool`
Verifica se um código TOTP é válido.

```php
$isValid = TwoFactorAuthService::verifyCode($secret, '123456');
// Resultado: true/false
```

#### `generateBackupCodes(): array`
Gera 10 códigos de backup.

```php
$backupCodes = TwoFactorAuthService::generateBackupCodes();
// Resultado: ['A1B2C3D4', 'E5F6G7H8', ...]
```

#### `verifyAndUseBackupCode(array $codes, string $code): array`
Verifica e remove um código de backup usado.

```php
$codigosRestantes = TwoFactorAuthService::verifyAndUseBackupCode(
    $backupCodes,
    'A1B2C3D4'
);
// Resultado: Array com código removido
```

## Páginas do Sistema

### 1. `/configurar-2fa`
**Arquivo:** `src/views/pages/configurar_2fa.php`

**Cenários:**

**A) Primeira Configuração (totp_habilitado = false)**
- Exibe QR Code para escanear
- Opção de inserir secret manualmente
- Campo para verificar código de 6 dígitos
- Exibe 10 códigos de backup
- Botões para copiar e imprimir códigos

**B) 2FA Já Ativado (totp_habilitado = true)**
- Mensagem informando que 2FA é obrigatório
- Aviso que não pode ser desativado
- Opção para gerar novos códigos de backup

### 2. `/verificar-2fa`
**Arquivo:** `src/views/pages/verificar_2fa.php`

**Funcionalidades:**
- Campo para inserir código TOTP (6 dígitos)
- Opção de usar código de backup
- Validação em tempo real
- Mensagens de erro claras
- Link para logout

### 3. `/gerar-backup-codes`
**Arquivo:** Não criado ainda (criar conforme necessário)

**Funcionalidades:**
- Gera novo conjunto de 10 códigos de backup
- Substitui códigos antigos no banco
- Exibe novos códigos para cópia/impressão

## Fluxo de Login (Pseudocódigo)

```php
// LoginController.php

public function login() {
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    
    // 1. Validar credenciais
    $usuario = Usuario::findByEmail($email);
    if (!$usuario || !password_verify($senha, $usuario->senha)) {
        return erro('Email ou senha inválidos');
    }
    
    // 2. Verificar se 2FA está configurado
    if (empty($usuario->totp_secret)) {
        // Primeira vez: redirecionar para configuração
        $_SESSION['usuario_id_temp'] = $usuario->id;
        
        // Gerar secret e QR Code
        $secret = TwoFactorAuthService::generateSecret();
        $qrCode = TwoFactorAuthService::generateQRCode(
            $usuario->email,
            $secret
        );
        $backupCodes = TwoFactorAuthService::generateBackupCodes();
        
        return render('configurar_2fa', [
            'secret' => $secret,
            'secret_formatado' => TwoFactorAuthService::formatSecret($secret),
            'qr_code_url' => $qrCode,
            'backup_codes' => $backupCodes,
            'totp_habilitado' => false
        ]);
    }
    
    // 3. 2FA já configurado: solicitar verificação
    $_SESSION['usuario_id_temp'] = $usuario->id;
    return render('verificar_2fa', [
        'usuario_id' => $usuario->id
    ]);
}

// Confirmação de 2FA (primeira vez)
public function confirmar2fa() {
    $secret = $_POST['secret'];
    $codigo = $_POST['codigo'];
    $usuarioId = $_SESSION['usuario_id_temp'];
    
    // Verificar código
    if (!TwoFactorAuthService::verifyCode($secret, $codigo)) {
        return erro('Código inválido');
    }
    
    // Gerar backup codes
    $backupCodes = TwoFactorAuthService::generateBackupCodes();
    
    // Salvar no banco
    $usuario = Usuario::find($usuarioId);
    $usuario->totp_secret = $secret;
    $usuario->totp_habilitado = true;
    $usuario->backup_codes = json_encode($backupCodes);
    $usuario->data_2fa_alteracao = date('Y-m-d H:i:s');
    $usuario->save();
    
    // Criar sessão
    $_SESSION['usuario_id'] = $usuarioId;
    unset($_SESSION['usuario_id_temp']);
    
    return redirect('/dashboard');
}

// Verificação de 2FA (logins subsequentes)
public function verificar2fa() {
    $codigo = $_POST['codigo_totp'] ?? $_POST['codigo_backup'];
    $usuarioId = $_SESSION['usuario_id_temp'];
    
    $usuario = Usuario::find($usuarioId);
    
    // Tentar TOTP
    if (isset($_POST['codigo_totp'])) {
        if (!TwoFactorAuthService::verifyCode($usuario->totp_secret, $codigo)) {
            $usuario->tentativas_login_falhas++;
            $usuario->save();
            return erro('Código TOTP inválido');
        }
    }
    
    // Tentar Backup Code
    if (isset($_POST['codigo_backup'])) {
        $backupCodes = json_decode($usuario->backup_codes, true);
        $novosCodigos = TwoFactorAuthService::verifyAndUseBackupCode(
            $backupCodes,
            $codigo
        );
        
        if (count($novosCodigos) === count($backupCodes)) {
            // Código não encontrado
            return erro('Código de backup inválido');
        }
        
        // Código válido: atualizar banco
        $usuario->backup_codes = json_encode($novosCodigos);
        $usuario->save();
    }
    
    // Login bem-sucedido
    $_SESSION['usuario_id'] = $usuarioId;
    $usuario->tentativas_login_falhas = 0;
    $usuario->ultimo_login_sucesso = date('Y-m-d H:i:s');
    $usuario->ultimo_ip_login = $_SERVER['REMOTE_ADDR'];
    $usuario->save();
    
    unset($_SESSION['usuario_id_temp']);
    return redirect('/dashboard');
}
```

## Segurança

### Boas Práticas Implementadas

1. **TOTP com Janela de Tolerância**
   - Aceita código do período atual e ±1 período anterior/próximo
   - Evita problemas de sincronização de relógio

2. **Códigos de Backup**
   - 10 códigos únicos gerados aleatoriamente
   - Cada código pode ser usado apenas uma vez
   - Armazenados em JSON no banco

3. **Rate Limiting**
   - Rastrear tentativas de login falhadas
   - Bloquear após N tentativas

4. **Auditoria**
   - Registrar último login bem-sucedido
   - Registrar IP do último login
   - Data/hora de alteração de 2FA

### Proteção contra Ataques

- **Brute Force:** Limite de tentativas de login
- **Phishing:** QR Code gerado no servidor, não pode ser interceptado
- **Perda de Autenticador:** Códigos de backup como fallback
- **Session Hijacking:** Usar HTTPS em produção

## Testes Recomendados

```bash
# 1. Primeiro login sem 2FA
curl -X POST http://localhost:8000/login \
  -d "email=user@example.com&senha=senha123"
# Esperado: Redireciona para /configurar-2fa

# 2. Escanear QR Code e verificar
# (Usar Google Authenticator ou similar)

# 3. Login subsequente com 2FA
curl -X POST http://localhost:8000/login \
  -d "email=user@example.com&senha=senha123"
# Esperado: Redireciona para /verificar-2fa

# 4. Inserir código TOTP
curl -X POST http://localhost:8000/verificar-2fa \
  -d "codigo_totp=123456"
# Esperado: Cria sessão e redireciona para /dashboard

# 5. Usar código de backup
curl -X POST http://localhost:8000/verificar-2fa \
  -d "codigo_backup=A1B2C3D4"
# Esperado: Cria sessão e remove código usado
```

## Próximas Melhorias

- [ ] Implementar rate limiting por IP
- [ ] Adicionar notificação de novo login
- [ ] Implementar recuperação de conta
- [ ] Adicionar WebAuthn como alternativa
- [ ] Implementar 2FA via SMS (opcional)
- [ ] Dashboard com histórico de logins

---

**Desenvolvido para MailJZTech**
**Data:** 6 de Novembro de 2025
