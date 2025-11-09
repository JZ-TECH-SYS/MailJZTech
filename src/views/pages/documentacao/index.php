<!-- Carregar CSS das Tabs -->
<link rel="stylesheet" href="<?php echo $base; ?>/assets/css/tabs.css">

<style>
    /* Background e Estilos Globais */
    body {
        background: linear-gradient(135deg, #0f0c29 0%, #1a0033 100%) !important;
        min-height: 100vh;
        color: #e0e0e0;
    }

    /* Cards Glassmorphism */
    .card {
        background: rgba(255, 255, 255, 0.08);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    /* Texto secundário */
    .text-light-50 {
        color: rgba(224, 224, 224, 0.6) !important;
    }

    /* Code inline */
    code {
        background: rgba(0, 0, 0, 0.3);
        color: #87ceeb;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.9em;
    }

    /* Tables Dark */
    .table-dark {
        background: rgba(0, 0, 0, 0.3);
    }

    .table-dark thead {
        background: rgba(255, 255, 255, 0.1);
    }

    .table-dark tbody tr:hover {
        background: rgba(255, 255, 255, 0.05);
    }

    /* Navigation Sidebar */
    .sidebar-nav {
        position: sticky;
        top: 20px;
    }

    .sidebar-nav .list-group-item {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(255, 255, 255, 0.1);
        color: rgba(224, 224, 224, 0.8);
        transition: all 0.3s ease;
    }

    .sidebar-nav .list-group-item:hover {
        background: rgba(255, 255, 255, 0.1);
        color: #87ceeb;
        transform: translateX(5px);
    }

    .sidebar-nav .list-group-item i {
        width: 25px;
    }

    /* Smooth Scroll */
    html {
        scroll-behavior: smooth;
    }

    /* Section Spacing */
    section {
        scroll-margin-top: 30px;
    }
</style>

<?php $render('header'); ?>

<div class="container-fluid py-5">
    <h1 class="mb-5 text-center">
        <i class="fas fa-book text-info"></i>
        <span style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-size: 2.5rem; font-weight: 700;">
            Documentação da API MailJZTech
        </span>
    </h1>

    <div class="row">
        <!-- SIDEBAR DE NAVEGAÇÃO -->
        <div class="col-lg-3">
            <nav class="sidebar-nav">
                <div class="card" style="border-radius: 1rem;">
                    <div class="card-header text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 1rem 1rem 0 0;">
                        <h5 class="mb-0"><i class="fas fa-compass"></i> Navegação</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="#visao-geral" class="list-group-item list-group-item-action">
                            <i class="fas fa-info-circle text-primary"></i> Visão Geral
                        </a>
                        <a href="#autenticacao" class="list-group-item list-group-item-action">
                            <i class="fas fa-lock text-danger"></i> Autenticação
                        </a>
                        <a href="#enviar-email" class="list-group-item list-group-item-action">
                            <i class="fas fa-paper-plane text-info"></i> Enviar E-mail
                        </a>
                        <a href="#listar-emails" class="list-group-item list-group-item-action">
                            <i class="fas fa-list text-success"></i> Listar E-mails
                        </a>
                        <a href="#codigos-erro" class="list-group-item list-group-item-action">
                            <i class="fas fa-exclamation-triangle text-warning"></i> Códigos de Erro
                        </a>
                    </div>
                </div>
            </nav>
        </div>

        <!-- CONTEÚDO PRINCIPAL -->
        <div class="col-lg-9">
            <!-- Visão Geral -->
            <section id="visao-geral">
                <?php require __DIR__ . '/visao_geral.php'; ?>
            </section>

            <!-- Autenticação -->
            <section id="autenticacao">
                <?php require __DIR__ . '/autenticacao.php'; ?>
            </section>

            <!-- Enviar E-mail (COM TABS INTERATIVAS) -->
            <section id="enviar-email">
                <?php require __DIR__ . '/enviar_email.php'; ?>
            </section>

            <!-- Listar E-mails -->
            <section id="listar-emails">
                <?php require __DIR__ . '/listar_emails.php'; ?>
            </section>

            <!-- Códigos de Erro -->
            <section id="codigos-erro">
                <?php require __DIR__ . '/codigos_erro.php'; ?>
            </section>
        </div>
    </div>
</div>

<!-- Carregar JavaScript das Tabs -->
<script src="<?php echo $base; ?>/assets/js/tabs.js"></script>

<?php $render('footer'); ?>