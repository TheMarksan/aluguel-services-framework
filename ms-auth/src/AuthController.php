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
        $name = $input['name'] ?? '';
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';
        $role = $input['role'] ?? 'locatario';

        if (!$name || !$email || !$password) {
            $this->json(400, ['error' => 'Dados incompletos. Nome, email e password sao obrigatorios.']);
            return;
        }

        if ($role === 'admin') {
            $this->json(403, ['error' => 'Nao e permitido registar contas com o papel de administrador.']);
            return;
        }

        if (!in_array($role, ['locador', 'locatario'])) {
            $this->json(400, ['error' => 'Papel (role) invalido. Escolha locador ou locatario.']);
            return;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $id = $this->users->create($name, $email, $hash, $role);
            $this->json(201, [
                'message' => 'Utilizador registado com sucesso.',
                'data' => ['id' => $id, 'name' => $name, 'email' => $email, 'role' => $role]
            ]);
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                $this->json(409, ['error' => 'Este email ja se encontra registado no sistema.']);
            } else {
                $this->json(500, ['error' => 'Erro interno na base de dados.']);
            }
        }
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

    public function logout(string $authHeader): void
    {
        $token = str_replace('Bearer ', '', $authHeader);
        if (!$token) {
            $this->json(400, ['error' => 'Token ausente.']);
            return;
        }

        $this->users->revokeToken($token);
        $this->json(200, ['message' => 'Logout efetuado com sucesso. Token revogado.']);
    }

    public function refresh(string $authHeader): void
    {
        $claims = $this->resolveClaims($authHeader);
        if (!$claims) {
            return;
        }

        $oldToken = str_replace('Bearer ', '', $authHeader);
        $this->users->revokeToken($oldToken);

        $secret = getenv('JWT_SECRET');
        $issuer = getenv('JWT_ISSUER');
        $ttl = (int) getenv('JWT_TTL') ?: 3600;

        $newClaims = [
            'sub' => $claims['sub'],
            'role' => $claims['role']
        ];

        $newToken = Jwt::encode($newClaims, $secret, $issuer, $ttl);
        $this->json(200, ['token' => $newToken]);
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
        $token = str_replace('Bearer ', '', $authorizationHeader);
        
        if ($token && $this->users->isTokenRevoked($token)) {
            $this->json(401, ['error' => 'Token revogado. Faca login novamente.']);
            return;
        }

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
