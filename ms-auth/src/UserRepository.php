<?php

declare(strict_types=1);

namespace MsAuth;

use PDO;

/**
 * Acesso a dados da tabela users (db_auth).
 */
final class UserRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * @return array<string,mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare('SELECT 1 FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * @return int ID do usuario criado.
     */
    public function create(string $name, string $email, string $passwordHash, string $role): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role)
             VALUES (:name, :email, :password_hash, :role)'
        );
        $stmt->execute([
            'name'          => $name,
            'email'         => $email,
            'password_hash' => $passwordHash,
            'role'          => $role,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function revokeToken(string $token): void
    {
        $stmt = $this->pdo->prepare("INSERT IGNORE INTO revoked_tokens (token) VALUES (:token)");
        $stmt->execute(['token' => $token]);
    }

    public function isTokenRevoked(string $token): bool
    {
        $stmt = $this->pdo->prepare("SELECT 1 FROM revoked_tokens WHERE token = :token");
        $stmt->execute(['token' => $token]);
        return (bool) $stmt->fetchColumn();
    }
}
