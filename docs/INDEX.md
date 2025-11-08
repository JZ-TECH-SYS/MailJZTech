# 📖 Índice de Documentação – MailJZTech

Bem-vindo à documentação centralizada do MailJZTech. Todos os arquivos estão organizados em PT-BR para facilitar a compreensão.

## 🎯 Comece Aqui

- **Primeira vez?** → Leia [VISAO_GERAL.md](VISAO_GERAL.md)
- **Precisa fazer uma requisição?** → [REFERENCIA_API.md](REFERENCIA_API.md)
- **Quer colocar em produção?** → [GUIA_IMPLANTACAO.md](GUIA_IMPLANTACAO.md)
- **Configurando CI/CD?** → [CONFIGURACAO_GITHUB_SECRETS.md](CONFIGURACAO_GITHUB_SECRETS.md)

## 📚 Documentos

### Conceitual
- **[VISAO_GERAL.md](VISAO_GERAL.md)** – Arquitetura, fluxos, padrões de código, convenções

### Prático
- **[REFERENCIA_API.md](REFERENCIA_API.md)** – Endpoints, autenticação, exemplos de requisição em cURL/JS/PowerShell
- **[GUIA_IMPLANTACAO.md](GUIA_IMPLANTACAO.md)** – Setup local, produção, backup, observabilidade

### DevOps/CI-CD
- **[CONFIGURACAO_GITHUB_SECRETS.md](CONFIGURACAO_GITHUB_SECRETS.md)** – Secrets no GitHub Actions, variáveis de ambiente, deploy automático

## 🗂️ Estrutura da Raiz

```
MailJZTech/
├── README.md                  # Início rápido (aponta para docs/)
├── composer.json              # Dependências PHP
├── .env                       # Variáveis de ambiente (não versionado)
├── .github/
│   └── workflows/
│       └── deploy.yml         # CI/CD automático
├── docs/                      # ← DOCUMENTAÇÃO CENTRALIZADA AQUI
│   ├── INDEX.md              # Este arquivo
│   ├── VISAO_GERAL.md
│   ├── REFERENCIA_API.md
│   ├── GUIA_IMPLANTACAO.md
│   └── CONFIGURACAO_GITHUB_SECRETS.md
├── core/                      # Framework base
├── src/                       # Código-fonte (controllers, models, views)
├── public/                    # Web root (index.php, assets)
└── SQL/                       # Scripts de banco de dados
```

## 🔍 Buscar por Tópico

| Tópico | Documento |
|--------|-----------|
| Estrutura do projeto | [VISAO_GERAL.md](VISAO_GERAL.md) |
| Camadas (MVC) | [VISAO_GERAL.md](VISAO_GERAL.md) |
| Autenticação | [VISAO_GERAL.md](VISAO_GERAL.md) + [REFERENCIA_API.md](REFERENCIA_API.md) |
| 2FA (TOTP) | [VISAO_GERAL.md](VISAO_GERAL.md) |
| Endpoints | [REFERENCIA_API.md](REFERENCIA_API.md) |
| Exemplos de requisição | [REFERENCIA_API.md](REFERENCIA_API.md) |
| Setup local | [GUIA_IMPLANTACAO.md](GUIA_IMPLANTACAO.md) |
| Produção | [GUIA_IMPLANTACAO.md](GUIA_IMPLANTACAO.md) |
| Backup | [GUIA_IMPLANTACAO.md](GUIA_IMPLANTACAO.md) |
| GitHub Actions | [CONFIGURACAO_GITHUB_SECRETS.md](CONFIGURACAO_GITHUB_SECRETS.md) |
| Variáveis de ambiente | [CONFIGURACAO_GITHUB_SECRETS.md](CONFIGURACAO_GITHUB_SECRETS.md) |

## 📝 Convenções

- **Todos os documentos estão em PT-BR**
- **Seções principais com `##`**, subseções com `###`
- **Exemplos de código sempre com fenced blocks** (```bash, ```js, etc.)
- **Links internos usam caminhos relativos** (`[arquivo](arquivo.md)`)
- **URLs externas são evitadas** (preferir leitura local)

## 🤝 Contribuindo

Ao adicionar documentação:

1. Mantenha **PT-BR** (português brasileiro)
2. Coloque em `docs/`
3. Atualize este `INDEX.md` com link e tema
4. Use **markdown semântico** (headings, listas, code blocks)
5. Revise links antes de commitar

## 📞 Dúvidas?

Consulte a [VISAO_GERAL.md](VISAO_GERAL.md) ou a página de **Documentação integrada** no dashboard em `/documentacao`.

