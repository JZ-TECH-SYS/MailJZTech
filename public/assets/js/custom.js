// MailJZTech - Custom JavaScript

/**
 * Copiar texto para clipboard
 */
function copyToClipboard(text, buttonElement = null) {
    navigator.clipboard.writeText(text).then(() => {
        if (buttonElement) {
            const originalText = buttonElement.textContent;
            buttonElement.textContent = '✓ Copiado!';
            buttonElement.classList.add('btn-success');
            buttonElement.classList.remove('btn-primary');
            
            setTimeout(() => {
                buttonElement.textContent = originalText;
                buttonElement.classList.remove('btn-success');
                buttonElement.classList.add('btn-primary');
            }, 2000);
        }
    }).catch(err => {
        console.error('Erro ao copiar:', err);
        mostrarErro('Erro ao copiar para clipboard');
    });
}

/**
 * Confirmar exclusão
 */
function confirmDelete(message = 'Tem certeza que deseja deletar?') {
    // Esta função será substituída por confirmarAcao, mas mantida por enquanto
    // para compatibilidade, se necessário. O ideal é migrar todas as chamadas.
    return confirm(message);
}

/**
 * Mostrar notificação toast
 */
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} alert-dismissible fade show`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    toastContainer.appendChild(toast);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        toast.remove();
    }, 5000);
}

/**
 * Criar container de toasts
 */
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.style.position = 'fixed';
    container.style.top = '20px';
    container.style.right = '20px';
    container.style.zIndex = '9999';
    container.style.maxWidth = '400px';
    document.body.appendChild(container);
    return container;
}

/**
 * Validar formulário
 */
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.remove('is-invalid');
        }
    });
    
    return isValid;
}

/**
 * Formatar data
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pt-BR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Fazer requisição AJAX com token Bearer
 */
async function makeRequest(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json'
        }
    };
    
    const finalOptions = { ...defaultOptions, ...options };
    
    // Usar fetchComToken se tiver token disponível
    const token = window.Auth?.obterToken?.() || localStorage.getItem('auth_token');
    if (token) {
        finalOptions.headers['Authorization'] = `Bearer ${token}`;
    }
    
    try {
        const response = await fetch(url, finalOptions);
        const data = await response.json();
        
        // Se 401, limpar token
        if (response.status === 401) {
            console.error('❌ Unauthorized - removendo token');
            window.Auth?.removerToken?.();
            localStorage.removeItem('auth_token');
            // Redirecionar para login
            if (!window.location.pathname.includes('login')) {
                window.location.href = '/';
            }
        }
        
        if (!response.ok) {
            throw new Error(data.error || data.result?.mensagem || 'Erro na requisição');
        }
        
        return data;
    } catch (error) {
        console.error('Erro:', error);
        mostrarErro(error.message);
        throw error;
    }
}

/**
 * Deletar sistema
 */
async function deletarSistema(idsistema, nomeSistema, baseUrl) {
    if (await confirmarAcao(`Tem certeza que deseja deletar o sistema "${nomeSistema}"?`, 'Esta ação não pode ser desfeita.')) {
        try {
            const response = await makeRequest(`${baseUrl}/deletarSistema/${idsistema}`, {
                method: 'DELETE'
            });
            
            mostrarSucesso('Sistema deletado com sucesso!');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } catch (error) {
            mostrarErro('Erro ao deletar sistema: ' + error.message);
        }
    }
}

/**
 * Regenerar chave de API
 */
async function regenerarChave(idsistema, baseUrl) {
    if (await confirmarAcao('Tem certeza que deseja regenerar a chave de API?', 'A chave anterior não funcionará mais.')) {
        try {
            const response = await makeRequest(`${baseUrl}/regenerarChaveApi/${idsistema}`, {
                method: 'POST',
                body: JSON.stringify({ idsistema: idsistema })
            });
            
            if (response.result && response.result.chave_api) {
                mostrarSucesso('Chave regenerada com sucesso!');
                
                // Mostrar modal com a nova chave
                const modal = new bootstrap.Modal(document.getElementById('novaChaveModal'));
                document.getElementById('novaChaveInput').value = response.result.chave_api;
                modal.show();
                
                // Reload após fechar modal
                setTimeout(() => {
                    location.reload();
                }, 3000);
            }
        } catch (error) {
            mostrarErro('Erro ao regenerar chave: ' + error.message);
        }
    }
}

/**
 * Mostrar modal de chave de API
 */
function showChaveApiModal(idsistema, chaveApi, nomeSistema) {
    document.getElementById('sistemaNome').textContent = nomeSistema;
    document.getElementById('chaveApiInput').value = chaveApi;
    
    const modal = new bootstrap.Modal(document.getElementById('chaveApiModal'));
    modal.show();
}

/**
 * Copiar chave de API
 */
function copiarChaveApi() {
    const chaveInput = document.getElementById('chaveApiInput');
    const button = event.target;
    copyToClipboard(chaveInput.value, button);
}

/**
 * Inicializar tooltips do Bootstrap
 */
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Inicializar popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Remover classe is-invalid ao digitar
    const invalidFields = document.querySelectorAll('.is-invalid');
    invalidFields.forEach(field => {
        field.addEventListener('input', function() {
            this.classList.remove('is-invalid');
        });
    });
});

/**
 * Exportar dados para CSV
 */
function exportToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const csvRow = [];
        
        cols.forEach(col => {
            csvRow.push('"' + col.textContent.trim().replace(/"/g, '""') + '"');
        });
        
        csv.push(csvRow.join(','));
    });
    
    downloadCSV(csv.join('\n'), filename);
}

/**
 * Download CSV
 */
function downloadCSV(csv, filename) {
    const link = document.createElement('a');
    link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
    link.download = filename;
    link.click();
}

/**
 * Formatar número como moeda
 */
function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

/**
 * Debounce para requisições
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Throttle para eventos
 */
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * Deletar sistema
 */
async function deletarSistema(idsistema, nomeSistema, base) {
    if (!await confirmarAcao(`Tem certeza que deseja deletar o sistema "${nomeSistema}"?`, 'Esta ação não pode ser desfeita.')) {
        return;
    }
    
    try {
        const response = await fetchComToken(`${base}/deletarSistema/${idsistema}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (!data.error && data.result) {
            mostrarSucesso('Sistema deletado com sucesso!');
            window.location.reload();
        } else {
            mostrarErro(data.result || 'Erro ao deletar sistema');
        }
    } catch (error) {
        console.error('Erro:', error);
        mostrarErro('Erro ao deletar sistema: ' + error.message);
    }
}

/**
 * Regenerar chave de API
 */
async function regenerarChave(idsistema, base) {
    if (!await confirmarAcao('Tem certeza que deseja regenerar a chave de API?', 'A chave anterior não funcionará mais!')) {
        return;
    }
    
    try {
        const response = await fetchComToken(`${base}/regenerarChaveApi/${idsistema}`, {
            method: 'POST',
            body: JSON.stringify({})
        });
        
        const data = await response.json();
        
        if (!data.error && data.result) {
            // Mostrar modal com nova chave
            document.getElementById('novaChaveInput').value = data.result.chave_api;
            const modal = new bootstrap.Modal(document.getElementById('novaChaveModal'));
            modal.show();
        } else {
            mostrarErro(data.result || 'Erro ao regenerar chave');
        }
    } catch (error) {
        console.error('Erro:', error);
        mostrarErro('Erro ao regenerar chave: ' + error.message);
    }
}
