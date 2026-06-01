<?php

declare(strict_types=1);

/**
 * Front Controller do ms-auth (porta 8000).
 *
 * Servico de autenticacao JWT. E acessado internamente pelo Gateway,
 * mas tambem pode ser testado diretamente em http://localhost:8000.
 */

use MsAuth\AuthController;
use MsAuth\Config;
use MsAuth\Database;
use MsAuth\UserRepository;

$basePath = dirname(__DIR__);

$composerAutoload = $basePath . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require $composerAutoload;
} else {
    spl_autoload_register(static function (string $class) use ($basePath): void {
        $prefix = 'MsAuth\\';
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

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path   = '/' . trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/');
$auth   = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');

/** @return array<string,mixed> */
$readJsonBody = static function (): array {
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
};

header('Content-Type: application/json; charset=utf-8');

try {
    $controller = new AuthController(new UserRepository(Database::connection()));

    match (true) {
        $method === 'POST' && $path === '/register' => $controller->register($readJsonBody()),
        $method === 'POST' && $path === '/login'    => $controller->login($readJsonBody()),
        $method === 'GET'  && $path === '/me'       => $controller->me($auth),
        $method === 'POST' && $path === '/validate' => $controller->validate($auth),
        $method === 'GET'  && $path === '/health'   => print(json_encode(['service' => 'ms-auth', 'status' => 'ok'])),
        default => (static function (): void {
            http_response_code(404);
            echo json_encode(['error' => 'Rota nao encontrada no ms-auth.'], JSON_UNESCAPED_UNICODE);
        })(),
    };
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno no ms-auth.', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
