# Consolidação de Documentação – Checklist de Limpeza

## ✅ Arquivos para Remover da Raiz

Os seguintes arquivos foram consolidados em `docs/` e devem ser removidos:

### Documentação Principal (Consolidada)
- [ ] `SETUP.md` → Conteúdo em `docs/GUIA_IMPLANTACAO.md`
- [ ] `API_DOCUMENTATION.md` → Conteúdo em `docs/REFERENCIA_API.md`
- [ ] `QUICK_START.md` → Conteúdo em `README.md` e `docs/GUIA_IMPLANTACAO.md`
- [ ] `PRODUCTION_GUIDE.md` → Conteúdo em `docs/GUIA_IMPLANTACAO.md`
- [ ] `INFRASTRUCTURE.md` → Conteúdo em `docs/GUIA_IMPLANTACAO.md`

### Documentação de 2FA (Consolidada)
- [ ] `2FA_IMPLEMENTATION.md` → Conteúdo em `docs/VISAO_GERAL.md` + `docs/REFERENCIA_API.md`
- [ ] `README_TOKEN_2FA.md` → Conteúdo em `docs/VISAO_GERAL.md`

### Docker (Descontinuado)
- [ ] `DOCKER_SETUP.md` → Docker não está em produção

### Rastreabilidade/Histórico (Descontinuado)
- [ ] `TOKEN_CHANGES_SUMMARY.md` → Histórico, referência interna apenas
- [ ] `SESSION_SUMMARY.md` → Histórico, referência interna apenas
- [ ] `ROUTE_FIXES.md` → Histórico, referência interna apenas
- [ ] `TEST_2FA_FLOW.md` → Histórico, testes internos
- [ ] `IMPLEMENTATION_COMPLETE.md` → Histórico, checklist concluído

## ⚠️ Arquivos a Manter

- ✅ `README.md` – Simplificado, aponta para `docs/`
- ✅ `todo.md` – Lista de tarefas ativa
- ✅ `TEST_SCRIPT_CONSOLE.js` – Utilitário de teste

## 📂 Estrutura Final (Raiz)

```
MailJZTech/
├── README.md                    ← Único README (simplificado)
├── composer.json
├── .env                         (não versionado)
├── .gitignore
├── .github/
│   └── workflows/
│       └── deploy.yml
├── docs/                        ← TODA DOCUMENTAÇÃO AQUI
│   ├── INDEX.md                (índice e guia de navegação)
│   ├── VISAO_GERAL.md
│   ├── REFERENCIA_API.md
│   ├── GUIA_IMPLANTACAO.md
│   └── CONFIGURACAO_GITHUB_SECRETS.md
├── core/
├── src/
├── public/
├── SQL/
├── vendor/
├── logs/
└── todo.md                      (mantém)
```

## 🗑️ Passos para Limpeza

1. **Backup**: `git commit -m "docs: backup de arquivos antigos antes de consolidação"`
2. **Remover**: Após validar que tudo está em `docs/`, execute:
   ```bash
   rm SETUP.md API_DOCUMENTATION.md QUICK_START.md PRODUCTION_GUIDE.md INFRASTRUCTURE.md
   rm 2FA_IMPLEMENTATION.md README_TOKEN_2FA.md DOCKER_SETUP.md
   rm TOKEN_CHANGES_SUMMARY.md SESSION_SUMMARY.md ROUTE_FIXES.md TEST_2FA_FLOW.md IMPLEMENTATION_COMPLETE.md
   ```
3. **Commit**: `git commit -m "docs: consolidar em docs/ e remover duplicatas"`
4. **Push**: `git push origin main`

## ✨ Resultado

- ✅ Raiz limpa: apenas `README.md` + config files
- ✅ Documentação centralizada em `docs/` com índice
- ✅ Links internos atualizados
- ✅ Histórico preservado em `.git log` se necessário

