<?php

declare(strict_types=1);

/**
 * Front Controller do ms-reservas (porta 8002).
 *
 * O Gateway encaminha /api/reservas/* para ca, removendo o prefixo:
 *   /api/reservas       -> "/"     (colecao)
 *   /api/reservas/7     -> "/7"    (item)
 */

use MsReservas\Config;
use MsReservas\Database;
use MsReservas\ReservationController;
use MsReservas\ReservationRepository;

$basePath = dirname(__DIR__);

$composerAutoload = $basePath . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require $composerAutoload;
} else {
    spl_autoload_register(static function (string $class) use ($basePath): void {
        $prefix = 'MsReservas\\';
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

/** @return array<string,mixed> */
$readJsonBody = static function (): array {
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
};

header('Content-Type: application/json; charset=utf-8');

$id = null;
if (preg_match('#^/(\d+)$#', $path, $m)) {
    $id = (int) $m[1];
}

try {
    $controller = new ReservationController(new ReservationRepository(Database::connection()));

    match (true) {
        $method === 'GET'  && $path === '/health' => print(json_encode(['service' => 'ms-reservas', 'status' => 'ok'])),
        $method === 'POST' && $path === '/'       => $controller->store($readJsonBody()),
        $method === 'GET'  && $path === '/'       => $controller->index(),
        $method === 'GET'  && $id !== null        => $controller->show($id),
        default => (static function (): void {
            http_response_code(404);
            echo json_encode(['error' => 'Rota nao encontrada no ms-reservas.'], JSON_UNESCAPED_UNICODE);
        })(),
    };
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno no ms-reservas.', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
