/**
 * auth.js - Gerenciamento de Autenticação e Token
 * Disponibiliza funções para trabalhar com token Bearer
 */

/**
 * Salva token no localStorage
 * @param {string} token - Token JWT/Bearer
 */
function salvarToken(token) {
    if (token) {
        localStorage.setItem('auth_token', token);
        console.debug('✓ Token salvo');
    }
}

/**
 * Recupera token do localStorage
 * @returns {string|null} Token ou null
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
 * Verifica se há token válido
 * @returns {boolean} true se tem token
 */
function temToken() {
    return !!obterToken();
}

/**
 * Faz fetch com token no header Authorization
 * @param {string} url - URL da requisição
 * @param {object} options - Opções do fetch (method, body, etc)
 * @returns {Promise} Resposta do fetch
 */
async function fetchComToken(url, options = {}) {
    const token = obterToken();
    
    if (!token) {
        console.warn('⚠ Nenhum token encontrado. Faça login primeiro.');
        // Redirecionar para login se não houver token
        if (!window.location.pathname.includes('login')) {
            window.location.href = window.location.origin + '/';
        }
        throw new Error('Token não encontrado');
    }

    const headers = options.headers || {};
    headers['Authorization'] = `Bearer ${token}`;
    headers['Content-Type'] = options.headers?.['Content-Type'] || 'application/json';

    try {
        const response = await fetch(BASE_API + url, {
            ...options,
            headers
        });

        // Se 401, token inválido/expirado
        if (response.status === 401) {
            console.error('❌ Token inválido ou expirado');
            removerToken();
            // Redirecionar para login
            window.location.href = window.location.origin + '/';
            throw new Error('Token inválido');
        }

        return response;
    } catch (error) {
        console.error('Erro na requisição:', error);
        throw error;
    }
}

/**
 * Faz logout: remove token e redireciona
 */
function fazerLogout() {
    removerToken();
    window.location.href = window.location.origin + '/';
}

/**
 * Verifica autenticação ao carregar página
 * Se estiver em página privada sem token, redireciona para login
 */
function verificarAutenticacao() {
    const paginasPublicas = ['/', '/login'];
    const paginaAtual = window.location.pathname;
    const temTokenValido = temToken();

    // Se está em página pública (login), okay
    if (paginasPublicas.includes(paginaAtual)) {
        return;
    }

    // Se não tem token e está em página privada, redireciona
    if (!temTokenValido && !paginasPublicas.includes(paginaAtual)) {
        console.warn('⚠ Acesso negado. Redirecionando para login...');
        window.location.href = window.location.origin + '/';
    }
}

// Executar verificação ao carregar a página
document.addEventListener('DOMContentLoaded', verificarAutenticacao);

// Expor globalmente
window.Auth = {
    salvarToken,
    obterToken,
    removerToken,
    temToken,
    fetchComToken,
    fazerLogout,
    verificarAutenticacao
};
