/**
 * sweetalert2.js - Funções de notificação padronizadas usando SweetAlert2
 * Projeto: MailJZTech
 * Data: 09/11/2025
 */

// Funções de Notificação
function mostrarSucesso(mensagem, titulo = 'Sucesso!') {
    Swal.fire({
        icon: 'success',
        title: titulo,
        text: mensagem,
        timer: 3000,
        showConfirmButton: false
    });
}

function mostrarErro(mensagem, titulo = 'Erro!') {
    Swal.fire({
        icon: 'error',
        title: titulo,
        text: mensagem,
        footer: 'Verifique o console para mais detalhes',
        showConfirmButton: true
    });
}

function mostrarInfo(mensagem, titulo = 'Informação') {
    Swal.fire({
        icon: 'info',
        title: titulo,
        text: mensagem,
        timer: 3000,
        showConfirmButton: false
    });
}

// Função de Confirmação
function confirmarAcao(mensagem, titulo = 'Tem certeza?', textoConfirmar = 'Sim, continuar!', textoCancelar = 'Cancelar') {
    return Swal.fire({
        title: titulo,
        text: mensagem,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: textoConfirmar,
        cancelButtonText: textoCancelar
    }).then((result) => {
        return result.isConfirmed;
    });
}

// Funções de Carregamento (Load)
function mostrarLoad(mensagem = 'Processando, por favor aguarde...') {
    Swal.fire({
        title: mensagem,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

function fecharLoad() {
    Swal.close();
}
