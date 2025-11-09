/**
 * Sistema de Tabs Interativas
 * Permite alternar entre diferentes linguagens de programação
 */

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar todas as tabs
    initializeTabs();
    
    // Adicionar funcionalidade de copiar código
    initializeCopyButtons();
});

/**
 * Inicializa o sistema de tabs
 */
function initializeTabs() {
    const tabGroups = document.querySelectorAll('.tab-group');
    
    tabGroups.forEach(group => {
        const buttons = group.querySelectorAll('.tab-button');
        const contents = group.querySelectorAll('.tab-content');
        
        buttons.forEach(button => {
            button.addEventListener('click', () => {
                const targetLang = button.dataset.lang;
                
                // Remover active de todos os botões e conteúdos
                buttons.forEach(btn => btn.classList.remove('active'));
                contents.forEach(content => content.classList.remove('active'));
                
                // Ativar o botão clicado
                button.classList.add('active');
                
                // Ativar o conteúdo correspondente
                const targetContent = group.querySelector(`.tab-content[data-lang="${targetLang}"]`);
                if (targetContent) {
                    targetContent.classList.add('active');
                }
                
                // Salvar preferência no localStorage
                localStorage.setItem('preferred-language', targetLang);
            });
        });
        
        // Verificar preferência salva
        const preferredLang = localStorage.getItem('preferred-language');
        if (preferredLang) {
            const preferredButton = group.querySelector(`.tab-button[data-lang="${preferredLang}"]`);
            if (preferredButton) {
                preferredButton.click();
            }
        }
    });
}

/**
 * Inicializa os botões de copiar código
 */
function initializeCopyButtons() {
    const copyButtons = document.querySelectorAll('.copy-button');
    
    copyButtons.forEach(button => {
        button.addEventListener('click', async function() {
            const codeBlock = this.parentElement.querySelector('code');
            const text = codeBlock.textContent;
            
            try {
                await navigator.clipboard.writeText(text);
                
                // Feedback visual
                const originalHTML = this.innerHTML;
                this.innerHTML = '<i class="fas fa-check"></i> Copiado!';
                this.classList.add('copied');
                
                setTimeout(() => {
                    this.innerHTML = originalHTML;
                    this.classList.remove('copied');
                }, 2000);
                
            } catch (err) {
                console.error('Erro ao copiar:', err);
                alert('Erro ao copiar código. Tente selecionar manualmente.');
            }
        });
    });
}

/**
 * Sincroniza todas as tabs quando uma é alterada
 * Útil quando há múltiplos grupos de tabs na mesma página
 */
function syncAllTabs(language) {
    const allButtons = document.querySelectorAll('.tab-button');
    const allContents = document.querySelectorAll('.tab-content');
    
    allButtons.forEach(button => {
        if (button.dataset.lang === language) {
            button.classList.add('active');
        } else {
            button.classList.remove('active');
        }
    });
    
    allContents.forEach(content => {
        if (content.dataset.lang === language) {
            content.classList.add('active');
        } else {
            content.classList.remove('active');
        }
    });
}
