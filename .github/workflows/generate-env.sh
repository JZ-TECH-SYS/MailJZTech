#!/bin/bash
# Script para gerar .env dinamicamente a partir de secrets/variáveis do GitHub Actions
# Chamado antes do deploy para garantir .env atualizado

cat > .env << 'EOF'
BASE_DIR="${{ secrets.BASE_DIR }}"
TOKEN_JV="${{ secrets.TOKEN_JV }}"

DB_DRIVER="${{ secrets.DB_DRIVER }}"
DB_HOST="${{ secrets.DB_HOST }}"
DB_PORT="${{ secrets.DB_PORT }}"
DB_DATABASE="${{ secrets.DB_DATABASE }}"
DB_USER="${{ secrets.DB_USER }}"
DB_PASS="${{ secrets.DB_PASS }}"

FRONT_URL="${{ secrets.FRONT_URL }}"

EMAIL_API="${{ secrets.EMAIL_API }}"
SENHA_EMAIL_API="${{ secrets.SENHA_EMAIL_API }}"
SMTP_PORT="${{ secrets.SMTP_PORT }}"
SMTP_HOST="${{ secrets.SMTP_HOST }}"

USER_MASTER_DB="${{ secrets.USER_MASTER_DB }}"
PASS_MASTER_DB="${{ secrets.PASS_MASTER_DB }}"
EOF

echo "✓ .env gerado com sucesso"
