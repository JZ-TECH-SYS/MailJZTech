<?php

use core\Database;

// Autoload se necessário
require_once 'vendor/autoload.php';
require_once 'core/Database.php'; // ajuste o caminho conforme seu projeto

$pdo = Database::getInstance();

$migrationsPath = __DIR__ . '/migrations';
$migrationFiles = glob($migrationsPath . '/*.php');

// Garante que a tabela de controle exista
$pdo->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        run_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
");

foreach ($migrationFiles as $file) {
    $filename = basename($file);

    // Verifica se essa migration já foi executada
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM migrations WHERE migration = :name");
    $stmt->execute([':name' => $filename]);

    if ($stmt->fetchColumn() == 0) {
        echo "▶ Executando migration: $filename\n";
        try {
            require $file;
            $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (:name)");
            $stmt->execute([':name' => $filename]);
            echo "✔ Migration $filename executada com sucesso.\n";
        } catch (Throwable $e) {
            echo "❌ Erro na migration $filename: " . $e->getMessage() . "\n";
        }
    } else {
        echo "↷ Migration $filename já foi executada. Ignorando.\n";
    }
}
