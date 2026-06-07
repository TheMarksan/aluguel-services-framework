<?php

declare(strict_types=1);

namespace MsCatalogo;

final class CatalogController
{
    public function __construct(private readonly PropertyRepository $properties)
    {
    }

    private function getAuthUser(): ?array
    {
        $id = $_SERVER['HTTP_X_USER_ID'] ?? null;
        $role = $_SERVER['HTTP_X_USER_ROLE'] ?? null;
        return $id ? ['id' => (int) $id, 'role' => $role] : null;
    }

    public function index(array $filters): void
    {
        $this->json(200, ['data' => $this->properties->all($filters)]);
    }

    public function show(int $id): void
    {
        $property = $this->properties->find($id);
        if ($property === null) {
            $this->json(404, ['error' => 'Imovel nao encontrado.']);
            return;
        }
        $this->json(200, ['data' => $property]);
    }

    public function store(array $input): void
    {
        $user = $this->getAuthUser();
        if (!$user) {
            $this->json(401, ['error' => 'Acesso negado. Requisicao nao validada pelo Gateway.']);
            return;
        }
        
        if (!in_array($user['role'], ['admin', 'locador'], true)) {
            $this->json(403, ['error' => 'Apenas locadores ou administradores podem anunciar imoveis.']);
            return;
        }

        $error = $this->validate($input);
        if ($error !== null) {
            $this->json(422, ['error' => $error]);
            return;
        }

        $input['owner_id'] = $user['id'];

        $id = $this->properties->create($input);
        $this->json(201, ['data' => $this->properties->find($id)]);
    }

    public function update(int $id, array $input): void
    {
        $user = $this->getAuthUser();
        if (!$user) {
            $this->json(401, ['error' => 'Acesso negado. Requisicao nao validada pelo Gateway.']);
            return;
        }

        $property = $this->properties->find($id);
        if ($property === null) {
            $this->json(404, ['error' => 'Imovel nao encontrado.']);
            return;
        }

        if ($user['role'] !== 'admin' && (int)$property['owner_id'] !== $user['id']) {
            $this->json(403, ['error' => 'Acesso negado. Apenas o dono do anuncio pode edita-lo.']);
            return;
        }

        $error = $this->validate($input);
        if ($error !== null) {
            $this->json(422, ['error' => $error]);
            return;
        }

        $input['owner_id'] = $property['owner_id'];

        $this->properties->update($id, $input);
        $this->json(200, ['data' => $this->properties->find($id)]);
    }

    public function destroy(int $id): void
    {
        $user = $this->getAuthUser();
        if (!$user) {
            $this->json(401, ['error' => 'Acesso negado.']);
            return;
        }

        $property = $this->properties->find($id);
        if ($property === null) {
            $this->json(404, ['error' => 'Imovel nao encontrado.']);
            return;
        }

        if ($user['role'] !== 'admin' && (int)$property['owner_id'] !== $user['id']) {
            $this->json(403, ['error' => 'Acesso negado. Apenas o dono do anuncio pode apaga-lo.']);
            return;
        }

        $this->properties->delete($id);
        $this->json(200, ['message' => 'Imovel removido com sucesso.']);
    }

    private function validate(array $input): ?string
    {
        if (trim((string) ($input['title'] ?? '')) === '') {
            return 'O campo title e obrigatorio.';
        }
        if (trim((string) ($input['city'] ?? '')) === '') {
            return 'O campo city e obrigatorio.';
        }
        if (trim((string) ($input['address'] ?? '')) === '') {
            return 'O campo address e obrigatorio.';
        }
        if (isset($input['type']) && !in_array($input['type'], ['casa', 'apartamento'], true)) {
            return 'O tipo deve ser "casa" ou "apartamento".';
        }
        if (isset($input['daily_price']) && (float)$input['daily_price'] < 0) {
            return 'O preco da diaria nao pode ser negativo.';
        }
        if (isset($input['monthly_price']) && (float)$input['monthly_price'] < 0) {
            return 'O preco mensal nao pode ser negativo.';
        }
        return null;
    }

    private function json(int $status, array $data): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}