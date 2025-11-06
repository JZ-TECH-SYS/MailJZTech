<?php

namespace src\handlers\NFEs;

use Exception;

class Gerenciador
{
    /**
     * Emite a NFe/NFCe de acordo com $pedido['operacao']
     */
    public static function operacao($pedido)
    {
        $class = __NAMESPACE__ . '\\' . ltrim($pedido['operacao'], '\\');

        if (!class_exists($class)) {
            throw new Exception("Operação '{$pedido['operacao']}' não suportada classificada como '{$class}'");
        }

        // 3) Chama o método estático gerar() da classe certa
        return $class::gerar($pedido);
    }
}
