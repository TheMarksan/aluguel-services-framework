<?php

declare(strict_types=1);

/**
 * Front Controller do ms-catalogo (porta 8001).
 *
 * O Gateway encaminha /api/imoveis/* para ca, removendo o prefixo. Assim:
 *   /api/imoveis        -> "/"     (colecao)
 *   /api/imoveis/5      -> "/5"    (item)
 */

use MsCatalogo\CatalogController;
use MsCatalogo\Config;
use MsCatalogo\Database;
use MsCatalogo\PropertyRepository;

$basePath = dirname(__DIR__);

$composerAutoload = $basePath . '/vendor/autoload.php';
if (is_file($composerAutoload)) {
    require $composerAutoload;
} else {
    spl_autoload_register(static function (string $class) use ($basePath): void {
        $prefix = 'MsCatalogo\\';
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

// Extrai um eventual id numerico do path (ex.: /5).
$id = null;
if (preg_match('#^/(\d+)$#', $path, $m)) {
    $id = (int) $m[1];
}

try {
    $controller = new CatalogController(new PropertyRepository(Database::connection()));

    match (true) {
        $method === 'GET'  && $path === '/health'        => print(json_encode(['service' => 'ms-catalogo', 'status' => 'ok'])),
        $method === 'GET'    && $path === '/'            => $controller->index($_GET),
        $method === 'POST'   && $path === '/'            => $controller->store($readJsonBody()),
        $method === 'GET'    && $id !== null             => $controller->show($id),
        $method === 'PUT'    && $id !== null             => $controller->update($id, $readJsonBody()),
        $method === 'DELETE' && $id !== null             => $controller->destroy($id),
        default => (static function (): void {
            http_response_code(404);
            echo json_encode(['error' => 'Rota nao encontrada no ms-catalogo.'], JSON_UNESCAPED_UNICODE);
        })(),
    };
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno no ms-catalogo.', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
