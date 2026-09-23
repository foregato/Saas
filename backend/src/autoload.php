<?php

/**
 * Autoloader simples para o namespace App\*, mapeando para backend/src/*.
 * Evita dependência de Composer em hospedagem compartilhada básica.
 * (Se o ambiente tiver Composer disponível, pode-se substituir por vendor/autoload.php.)
 */
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});
