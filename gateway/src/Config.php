<?php

declare(strict_types=1);

namespace Gateway;

/**
 * Carregador simples de configuracao a partir do arquivo .env.
 *
 * Mantem o Gateway desacoplado das URLs internas dos microsservicos,
 * permitindo reconfigurar o ecossistema sem alterar codigo.
 */
final class Config
{
    /** @var array<string,string> */
    private static array $values = [];

    private static bool $loaded = false;

    public static function load(string $basePath): void
    {
        if (self::$loaded) {
            return;
        }

        $envFile = $basePath . '/.env';
        if (is_file($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
                self::$values[trim($key)] = trim($value);
            }
        }

        self::$loaded = true;
    }

    public static function get(string $key, string $default = ''): string
    {
        return self::$values[$key] ?? getenv($key) ?: $default;
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::get($key, (string) $default);
        return $value === '' ? $default : (int) $value;
    }

    /**
     * Mapa logico: prefixo de rota -> URL base do microsservico.
     *
     * @return array<string,string>
     */
    public static function serviceMap(): array
    {
        return [
            'auth'     => self::get('MS_AUTH_URL', 'http://localhost:8000'),
            'imoveis'  => self::get('MS_CATALOGO_URL', 'http://localhost:8001'),
            'reservas' => self::get('MS_RESERVAS_URL', 'http://localhost:8002'),
        ];
    }
}
