# Correções de Rotas - MailJZTech

## Resumo
Foram identificadas e corrigidas várias chamadas de rota incorretas no front-end que não correspondiam às rotas definidas em `routes.php`.

## Correções Realizadas

### 1. **login.php** - Removido prefixo `/api`
- **Problema:** Chamadas de fetch usando `/api/login` e `/api/confirmar-2fa`
- **Solução:** Removido o prefixo `/api` das chamadas
- **Linhas alteradas:**
  - Linha 439: `fetch('<?php echo $base; ?>/api/login')` → `fetch('<?php echo $base; ?>/login')`
  - Linha 482: `fetch('<?php echo $base; ?>/api/confirmar-2fa')` → `fetch('<?php echo $base; ?>/confirmar-2fa')`

### 2. **custom.js** - Corrigidas rotas de sistemas
- **Problema:** Chamadas usando query string em vez de path parameters
- **Solução:** Alteradas para usar path parameters conforme definido em routes.php
- **Linhas alteradas:**
  - Linha 141: `/deletarSistema?idsistema=${idsistema}` → `/deletarSistema/${idsistema}`
  - Linha 161: `/regenerarChaveApi` → `/regenerarChaveApi/${idsistema}`

### 3. **SistemasController.php** - Atualizado para receber ID da URL
- **Problema:** Controller esperava ID no body, mas rotas definem ID na URL
- **Solução:** Alteradas assinaturas de métodos para receber ID como parâmetro
- **Métodos alterados:**
  - `atualizarSistema($idsistema)` - recebe ID da URL
  - `deletarSistema($idsistema)` - recebe ID da URL
  - `regenerarChaveApi($idsistema)` - recebe ID da URL

### 4. **editar_sistema.php** - Convertido para AJAX com PUT
- **Problema:** Formulário POST não correspondia à rota PUT
- **Solução:** Convertido para AJAX com método PUT e rota correta
- **Alterações:**
  - Adicionado ID `formAtualizarSistema` ao formulário
  - Adicionado script AJAX que faz PUT para `/atualizarSistema/{idsistema}`
  - Adicionado feedback visual com spinner e toast

### 5. **configurar_2fa.php** - Corrigida rota de confirmação
- **Problema:** Rota chamava `/confirmar2fa` (sem hífen)
- **Solução:** Alterada para `/confirmar-2fa` (com hífen)
- **Linha alterada:**
  - Linha 69: `action="<?php echo $base; ?>/confirmar2fa"` → `action="<?php echo $base; ?>/confirmar-2fa"`

### 6. **routes.php** - Adicionadas rotas de 2FA faltantes
- **Problema:** Rotas POST para verificação de 2FA não existiam
- **Solução:** Adicionadas rotas POST
- **Rotas adicionadas:**
  - `POST /verificar-2fa` → `LoginController@verificarDoisFatores`
  - `POST /verificar-2fa-backup` → `LoginController@verificarDoisFatoresBackup`

### 7. **LoginController.php** - Implementados métodos de verificação de 2FA
- **Problema:** Métodos para verificar 2FA durante login não existiam
- **Solução:** Implementados dois novos métodos
- **Métodos adicionados:**
  - `verificarDoisFatores()` - Verifica código TOTP durante login
  - `verificarDoisFatoresBackup()` - Verifica código de backup durante login

## Resumo das Mudanças por Arquivo

| Arquivo | Tipo | Mudanças |
|---------|------|----------|
| `src/views/pages/login.php` | View | 2 chamadas de fetch corrigidas |
| `public/assets/js/custom.js` | JavaScript | 2 chamadas de rota corrigidas |
| `src/controllers/SistemasController.php` | Controller | 3 métodos atualizados para receber ID da URL |
| `src/views/pages/editar_sistema.php` | View | Convertido para AJAX com PUT |
| `src/views/pages/configurar_2fa.php` | View | 1 rota corrigida |
| `src/routes.php` | Rotas | 2 rotas POST adicionadas |
| `src/controllers/LoginController.php` | Controller | 2 métodos adicionados |

## Status

✅ Todas as rotas foram revisadas e corrigidas
✅ Todas as chamadas de fetch/AJAX agora correspondem às rotas definidas
✅ Controllers foram atualizados para receber parâmetros corretamente
✅ Novos métodos foram implementados para funcionalidades faltantes

## Próximos Passos

1. Testar todas as funcionalidades de login e 2FA
2. Testar CRUD de sistemas (criar, editar, deletar)
3. Testar regeneração de chave de API
4. Melhorar UX/UI conforme design do JZ Tech
