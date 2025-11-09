<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MailJZTech - Serviço de Envio de E-mails</title>
    <link rel="icon" href="<?php echo $base; ?>/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/bootstrap.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/custom.css">
    <!-- Font Awesome (opcional) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        const BASE_API = '<?php echo $base; ?>';
    </script>
    
    <!-- Auth JS (Token Manager) -->
    <script src="<?php echo $base; ?>/assets/js/auth.js"></script>
</head>
<body>
    <header class="bg-gradient">
        <div class="container">
            <h1 class="mb-0">📧 MailJZTech</h1>
            <p class="mb-0 mt-2 opacity-75">Serviço de Envio de E-mails com API</p>
        </div>
    </header>

    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $base; ?>/">
                <strong>MailJZTech</strong>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false ? 'active' : ''; ?>" href="<?php echo $base; ?>/dashboard">
                            <i class="fas fa-chart-line"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'sistemas') !== false ? 'active' : ''; ?>" href="<?php echo $base; ?>/sistemas">
                            <i class="fas fa-cogs"></i> Sistemas
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'emails') !== false ? 'active' : ''; ?>" href="<?php echo $base; ?>/emails">
                            <i class="fas fa-envelope"></i> E-mails
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'logs') !== false && strpos($_SERVER['REQUEST_URI'], 'backup') === false) ? 'active' : ''; ?>" href="<?php echo $base; ?>/logs">
                            <i class="fas fa-list"></i> Logs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'backup') !== false ? 'active' : ''; ?>" href="<?php echo $base; ?>/backup">
                            <i class="fas fa-database"></i> Backups
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'documentacao') !== false ? 'active' : ''; ?>" href="<?php echo $base; ?>/documentacao">
                            <i class="fas fa-book"></i> Documentação
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base; ?>/sair">
                            <i class="fas fa-sign-out-alt"></i> Sair
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <main class="py-4">
        <div class="container">
