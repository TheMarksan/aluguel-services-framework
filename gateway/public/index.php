<?php

declare(strict_types=1);

/**
 * Front Controller do API Gateway (porta 8003).
 *
 * Ponto de entrada unico do ecossistema. Toda requisicao do cliente passa por
 * aqui e e roteada (stateless) para o microsservico adequado via cURL.
 */

use Gateway\Config;
use Gateway\HttpClient;
use Gateway\Router;

$basePath = dirname(__DIR__);

// Autoloader: usa o do Composer se existir; senao, registra um PSR-4 minimo.
$composerAutoload = $basePath . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require $composerAutoload;
} else {
    spl_autoload_register(static function (string $class) use ($basePath): void {
        $prefix = 'Gateway\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $file = $basePath . '/src/' . $relative . '.php';
        if (is_file($file)) {
            require $file;
        }
    });
}

Config::load($basePath);

$router = new Router(
    new HttpClient(Config::int('HTTP_TIMEOUT', 10)),
    $basePath . '/views'
);

$router->dispatch(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $_SERVER['REQUEST_URI'] ?? '/'
);
