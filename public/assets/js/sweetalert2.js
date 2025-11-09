/**
 * sweetalert2.js - Funções de notificação padronizadas usando SweetAlert2
 * Projeto: MailJZTech
 * Data: 09/11/2025
 * Versão: 2.0
 */

// ========================================
// FUNÇÕES DE NOTIFICAÇÃO BÁSICAS
// ========================================

/**
 * Mostra alerta de sucesso
 * @param {string} mensagem - Mensagem a ser exibida
 * @param {string} titulo - Título do alerta (padrão: 'Sucesso!')
 * @param {number} timer - Tempo em ms para fechar automaticamente (padrão: 3000)
 */
function mostrarSucesso(mensagem, titulo = 'Sucesso!', timer = 3000) {
    Swal.fire({
        icon: 'success',
        title: titulo,
        text: mensagem,
        timer: timer,
        showConfirmButton: false,
        toast: false,
        position: 'center'
    });
}

/**
 * Mostra alerta de erro
 * @param {string} mensagem - Mensagem de erro
 * @param {string} titulo - Título do alerta (padrão: 'Erro!')
 */
function mostrarErro(mensagem, titulo = 'Erro!') {
    Swal.fire({
        icon: 'error',
        title: titulo,
        text: mensagem,
        footer: '<i class="fas fa-info-circle"></i> Verifique o console para mais detalhes',
        showConfirmButton: true,
        confirmButtonText: 'Entendi',
        confirmButtonColor: '#d33'
    });
}

/**
 * Mostra alerta informativo
 * @param {string} mensagem - Mensagem informativa
 * @param {string} titulo - Título do alerta (padrão: 'Informação')
 * @param {number} timer - Tempo em ms para fechar automaticamente (padrão: 3000)
 */
function mostrarInfo(mensagem, titulo = 'Informação', timer = 3000) {
    Swal.fire({
        icon: 'info',
        title: titulo,
        text: mensagem,
        timer: timer,
        showConfirmButton: false
    });
}

/**
 * Mostra alerta de aviso/atenção
 * @param {string} mensagem - Mensagem de aviso
 * @param {string} titulo - Título do alerta (padrão: 'Atenção!')
 */
function mostrarAviso(mensagem, titulo = 'Atenção!') {
    Swal.fire({
        icon: 'warning',
        title: titulo,
        text: mensagem,
        showConfirmButton: true,
        confirmButtonText: 'OK',
        confirmButtonColor: '#ffc107'
    });
}

// ========================================
// FUNÇÕES DE CONFIRMAÇÃO
// ========================================

/**
 * Solicita confirmação do usuário
 * @param {string} mensagem - Mensagem a ser exibida
 * @param {string} titulo - Título do alerta (padrão: 'Tem certeza?')
 * @param {string} textoConfirmar - Texto do botão de confirmação
 * @param {string} textoCancelar - Texto do botão de cancelamento
 * @returns {Promise<boolean>} true se confirmado, false se cancelado
 */
function confirmarAcao(mensagem, titulo = 'Tem certeza?', textoConfirmar = 'Sim, continuar!', textoCancelar = 'Cancelar') {
    return Swal.fire({
        title: titulo,
        text: mensagem,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#6c757d',
        confirmButtonText: textoConfirmar,
        cancelButtonText: textoCancelar,
        reverseButtons: true
    }).then((result) => {
        return result.isConfirmed;
    });
}

/**
 * Confirmação de exclusão com ênfase no perigo
 * @param {string} itemNome - Nome do item a ser excluído
 * @param {string} detalhes - Detalhes adicionais sobre a exclusão
 * @returns {Promise<boolean>}
 */
function confirmarExclusao(itemNome, detalhes = 'Esta ação não pode ser desfeita!') {
    return Swal.fire({
        title: 'Excluir?',
        html: `<p>Tem certeza que deseja excluir <strong>${itemNome}</strong>?</p><p class="text-muted">${detalhes}</p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="fas fa-trash"></i> Sim, excluir!',
        cancelButtonText: '<i class="fas fa-times"></i> Cancelar',
        reverseButtons: true
    }).then((result) => {
        return result.isConfirmed;
    });
}

// ========================================
// FUNÇÕES DE CARREGAMENTO (LOADING)
// ========================================

/**
 * Mostra indicador de carregamento
 * @param {string} mensagem - Mensagem durante o carregamento
 */
function mostrarLoad(mensagem = 'Processando, por favor aguarde...') {
    Swal.fire({
        title: mensagem,
        html: '<i class="fas fa-spinner fa-spin fa-3x text-primary"></i>',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

/**
 * Fecha o indicador de carregamento
 */
function fecharLoad() {
    Swal.close();
}

// ========================================
// TOAST (NOTIFICAÇÕES DISCRETAS)
// ========================================

/**
 * Mostra toast de sucesso (canto superior direito)
 * @param {string} mensagem - Mensagem do toast
 * @param {number} timer - Tempo em ms (padrão: 3000)
 */
function toastSucesso(mensagem, timer = 3000) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: timer,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });

    Toast.fire({
        icon: 'success',
        title: mensagem
    });
}

/**
 * Mostra toast de erro (canto superior direito)
 * @param {string} mensagem - Mensagem do toast
 * @param {number} timer - Tempo em ms (padrão: 4000)
 */
function toastErro(mensagem, timer = 4000) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: timer,
        timerProgressBar: true
    });

    Toast.fire({
        icon: 'error',
        title: mensagem
    });
}

/**
 * Mostra toast informativo (canto superior direito)
 * @param {string} mensagem - Mensagem do toast
 * @param {number} timer - Tempo em ms (padrão: 3000)
 */
function toastInfo(mensagem, timer = 3000) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: timer,
        timerProgressBar: true
    });

    Toast.fire({
        icon: 'info',
        title: mensagem
    });
}

/**
 * Mostra toast de aviso (canto superior direito)
 * @param {string} mensagem - Mensagem do toast
 * @param {number} timer - Tempo em ms (padrão: 3500)
 */
function toastAviso(mensagem, timer = 3500) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: timer,
        timerProgressBar: true
    });

    Toast.fire({
        icon: 'warning',
        title: mensagem
    });
}

// ========================================
// FUNÇÕES DE INPUT/PROMPT
// ========================================

/**
 * Solicita entrada de texto do usuário
 * @param {string} titulo - Título do prompt
 * @param {string} placeholder - Placeholder do input
 * @param {string} valorPadrao - Valor padrão do input
 * @returns {Promise<string|null>} Valor digitado ou null se cancelado
 */
async function solicitarTexto(titulo, placeholder = '', valorPadrao = '') {
    const { value } = await Swal.fire({
        title: titulo,
        input: 'text',
        inputPlaceholder: placeholder,
        inputValue: valorPadrao,
        showCancelButton: true,
        confirmButtonText: 'Confirmar',
        cancelButtonText: 'Cancelar',
        inputValidator: (value) => {
            if (!value) {
                return 'Você precisa digitar algo!';
            }
        }
    });
    return value || null;
}

/**
 * Solicita entrada de textarea do usuário
 * @param {string} titulo - Título do prompt
 * @param {string} placeholder - Placeholder do textarea
 * @returns {Promise<string|null>}
 */
async function solicitarTextoLongo(titulo, placeholder = '') {
    const { value } = await Swal.fire({
        title: titulo,
        input: 'textarea',
        inputPlaceholder: placeholder,
        showCancelButton: true,
        confirmButtonText: 'Confirmar',
        cancelButtonText: 'Cancelar',
        inputValidator: (value) => {
            if (!value) {
                return 'Você precisa digitar algo!';
            }
        }
    });
    return value || null;
}

// ========================================
// UTILITÁRIOS E HELPERS
// ========================================

/**
 * Mostra alerta com HTML customizado
 * @param {string} titulo - Título do alerta
 * @param {string} htmlContent - Conteúdo HTML
 * @param {string} icon - Ícone (success, error, warning, info, question)
 */
function mostrarAlertaHTML(titulo, htmlContent, icon = 'info') {
    Swal.fire({
        title: titulo,
        html: htmlContent,
        icon: icon,
        confirmButtonText: 'OK'
    });
}

/**
 * Mostra alerta de sucesso após operação e recarrega a página
 * @param {string} mensagem - Mensagem de sucesso
 * @param {number} delay - Delay antes de recarregar (padrão: 1500ms)
 */
function sucessoERecarregar(mensagem, delay = 1500) {
    mostrarSucesso(mensagem);
    setTimeout(() => {
        window.location.reload();
    }, delay);
}

/**
 * Mostra alerta de sucesso e redireciona para URL
 * @param {string} mensagem - Mensagem de sucesso
 * @param {string} url - URL de destino
 * @param {number} delay - Delay antes de redirecionar (padrão: 1500ms)
 */
function sucessoERedirecionar(mensagem, url, delay = 1500) {
    mostrarSucesso(mensagem);
    setTimeout(() => {
        window.location.href = url;
    }, delay);
}

// ========================================
// COMPATIBILIDADE / ALIASES
// ========================================

// Aliases para compatibilidade com código legado
window.showToast = toastInfo;
window.confirmDelete = confirmarExclusao;
