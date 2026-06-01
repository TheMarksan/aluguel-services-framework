<?php

declare(strict_types=1);

namespace MsAuth;

/**
 * Controlador de autenticacao do ms-auth.
 *
 * Endpoints expostos (consumidos via Gateway em /api/auth/*):
 *   POST /register  -> cria usuario
 *   POST /login     -> autentica e devolve token JWT
 *   GET  /me        -> retorna dados do usuario do token (Authorization)
 *   POST /validate  -> valida um token (uso interno pelos demais servicos)
 */
final class AuthController
{
    public function __construct(private readonly UserRepository $users)
    {
    }

    /**
     * @param array<string,mixed> $input
     */
    public function register(array $input): void
    {
        $name     = trim((string) ($input['name'] ?? ''));
        $email    = trim((string) ($input['email'] ?? ''));
        $password = (string) ($input['password'] ?? '');
        $role     = (string) ($input['role'] ?? 'locatario');

        if ($name === '' || $email === '' || strlen($password) < 6) {
            $this->json(422, ['error' => 'Dados invalidos. Nome, email e senha (min. 6) sao obrigatorios.']);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(422, ['error' => 'Email invalido.']);
            return;
        }

        if (!in_array($role, ['admin', 'locador', 'locatario'], true)) {
            $role = 'locatario';
        }

        if ($this->users->emailExists($email)) {
            $this->json(409, ['error' => 'Email ja cadastrado.']);
            return;
        }

        $id = $this->users->create(
            $name,
            $email,
            password_hash($password, PASSWORD_BCRYPT),
            $role
        );

        $this->json(201, [
            'id'    => $id,
            'name'  => $name,
            'email' => $email,
            'role'  => $role,
        ]);
    }

    /**
     * @param array<string,mixed> $input
     */
    public function login(array $input): void
    {
        $email    = trim((string) ($input['email'] ?? ''));
        $password = (string) ($input['password'] ?? '');

        $user = $email !== '' ? $this->users->findByEmail($email) : null;

        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            $this->json(401, ['error' => 'Credenciais invalidas.']);
            return;
        }

        $token = Jwt::encode(
            [
                'sub'   => (int) $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ],
            Config::get('JWT_SECRET'),
            Config::get('JWT_ISSUER', 'ms-auth'),
            Config::int('JWT_TTL', 3600)
        );

        $this->json(200, [
            'token'      => $token,
            'token_type' => 'Bearer',
            'expires_in' => Config::int('JWT_TTL', 3600),
            'user'       => [
                'id'    => (int) $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role'],
            ],
        ]);
    }

    public function me(string $authorizationHeader): void
    {
        $claims = $this->resolveClaims($authorizationHeader);
        if ($claims === null) {
            $this->json(401, ['error' => 'Token ausente ou invalido.']);
            return;
        }

        $this->json(200, [
            'id'    => $claims['sub'] ?? null,
            'name'  => $claims['name'] ?? null,
            'email' => $claims['email'] ?? null,
            'role'  => $claims['role'] ?? null,
        ]);
    }

    /**
     * Validacao de token para uso interno por outros microsservicos.
     */
    public function validate(string $authorizationHeader): void
    {
        $claims = $this->resolveClaims($authorizationHeader);
        if ($claims === null) {
            $this->json(401, ['valid' => false]);
            return;
        }

        $this->json(200, ['valid' => true, 'claims' => $claims]);
    }

    /**
     * @return array<string,mixed>|null
     */
    private function resolveClaims(string $authorizationHeader): ?array
    {
        if (!preg_match('/Bearer\s+(\S+)/i', $authorizationHeader, $m)) {
            return null;
        }

        return Jwt::decode($m[1], Config::get('JWT_SECRET'));
    }

    /**
     * @param array<string,mixed> $data
     */
    private function json(int $status, array $data): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
