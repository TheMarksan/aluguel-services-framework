<?php

declare(strict_types=1);

namespace MsReservas;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Fabrica de conexao PDO com o banco de dados.
 */
final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            Config::get('DB_HOST', '127.0.0.1'),
            Config::get('DB_PORT', '3306'),
            Config::get('DB_NAME', 'defaultdb')
        );

        try {
            self::$connection = new PDO(
                $dsn,
                Config::get('DB_USER', 'root'),
                Config::get('DB_PASSWORD', ''),
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (PDOException $e) {
            throw new RuntimeException('Falha ao conectar ao banco de dados: ' . $e->getMessage());
        }

        return self::$connection;
    }
}