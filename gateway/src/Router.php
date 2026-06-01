<?php

declare(strict_types=1);

namespace Gateway;

/**
 * Roteador central stateless do Gateway.
 *
 * Estrategia de roteamento:
 *   - Rotas iniciadas em /api/{servico}/... sao encaminhadas (proxy) para o
 *     microsservico correspondente via cURL, preservando metodo, corpo e o
 *     cabecalho Authorization (token JWT). Por ser stateless, nenhum estado
 *     de sessao e mantido aqui: o token viaja em cada requisicao.
 *   - Demais rotas (sem /api) sao tratadas como views (Blade/HTML).
 */
final class Router
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $viewsPath
    ) {
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = '/' . trim($path, '/');

        if (str_starts_with($path, '/api/')) {
            $this->handleApi($method, $path, $uri);
            return;
        }

        $this->handleView($path);
    }

    /**
     * Encaminha chamadas de API para o microsservico correto.
     */
    private function handleApi(string $method, string $path, string $uri): void
    {
        // /api/{servico}/{resto...}
        $segments = explode('/', trim(substr($path, strlen('/api/')), '/'));
        $service  = $segments[0] ?? '';
        $rest     = implode('/', array_slice($segments, 1));

        $serviceMap = Config::serviceMap();
        if (!isset($serviceMap[$service])) {
            $this->json(404, [
                'error'   => 'Not Found',
                'message' => "Servico desconhecido: '{$service}'.",
            ]);
            return;
        }

        // Recompoe a URL interna preservando a query string original.
        $query      = parse_url($uri, PHP_URL_QUERY);
        $targetUrl  = rtrim($serviceMap[$service], '/') . '/' . $rest;
        if ($query) {
            $targetUrl .= '?' . $query;
        }

        $body     = file_get_contents('php://input') ?: '';
        $response = $this->http->forward($method, $targetUrl, $body, $this->forwardableHeaders());

        http_response_code($response['status']);
        header('Content-Type: ' . $response['content_type']);
        echo $response['body'];
    }

    /**
     * Renderiza views HTML do front-end (SPA via hash routing em home.php).
     * Rotas amigaveis /imovel, /meus-anuncios etc. também servem home.php.
     */
    private function handleView(string $path): void
    {
        $spaRoutes = ['', 'imovel', 'meus-anuncios', 'reservas'];
        $segment   = $path === '/' ? '' : explode('/', trim($path, '/'))[0];

        if (in_array($segment, $spaRoutes, true)) {
            $file = $this->viewsPath . '/home.php';
            if (is_file($file)) {
                header('Content-Type: text/html; charset=utf-8');
                require $file;
                return;
            }
        }

        $view = $path === '/' ? 'home' : trim($path, '/');
        $file = $this->viewsPath . '/' . basename($view) . '.php';

        if (is_file($file)) {
            header('Content-Type: text/html; charset=utf-8');
            require $file;
            return;
        }

        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        echo '<h1>404 - Pagina nao encontrada</h1>';
    }

    /**
     * Cabecalhos repassados aos microsservicos (mantendo o fluxo stateless).
     *
     * @return array<string,string>
     */
    private function forwardableHeaders(): array
    {
        $headers = [];
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        if ($auth !== '') {
            $headers['Authorization'] = $auth;
        }
        return $headers;
    }

    /**
     * @param array<string,mixed> $data
     */
    private function json(int $status, array $data): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
