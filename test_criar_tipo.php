<?php

/**
 * Script de teste: criar tipo_despesa usando Database::switchParams
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Env.php';

use src\models\Tipo_despesa;

echo "🧪 Testando criação de tipo_despesa com Database::switchParams\n\n";

$dados = [
    'idempresa' => 1,
    'nome' => 'Teste Database switchParams - ' . date('H:i:s')
];

echo "📝 Dados: \n";
print_r($dados);

echo "\n🔄 Executando Tipo_despesa::criar()...\n";

$idCriado = Tipo_despesa::criar($dados);

if ($idCriado) {
    echo "✅ Sucesso! ID criado: $idCriado\n";
    
    // Buscar o tipo criado
    $tipo = Tipo_despesa::getById($idCriado, $dados['idempresa']);
    echo "\n📋 Tipo criado:\n";
    print_r($tipo);
} else {
    echo "❌ Erro ao criar tipo_despesa\n";
}
