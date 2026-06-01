<?php

declare(strict_types=1);

namespace MsCatalogo;

/**
 * Controlador do catalogo de imoveis.
 *
 * Endpoints (consumidos via Gateway em /api/imoveis/*):
 *   GET    /            -> lista imoveis (filtros: city, type, available)
 *   GET    /{id}        -> detalha um imovel
 *   POST   /            -> cria imovel
 *   PUT    /{id}        -> atualiza imovel
 *   DELETE /{id}        -> remove imovel
 */
final class CatalogController
{
    public function __construct(private readonly PropertyRepository $properties)
    {
    }

    /**
     * @param array<string,string> $filters
     */
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

    /**
     * @param array<string,mixed> $input
     */
    public function store(array $input): void
    {
        $error = $this->validate($input);
        if ($error !== null) {
            $this->json(422, ['error' => $error]);
            return;
        }

        $id = $this->properties->create($input);
        $this->json(201, ['data' => $this->properties->find($id)]);
    }

    /**
     * @param array<string,mixed> $input
     */
    public function update(int $id, array $input): void
    {
        if ($this->properties->find($id) === null) {
            $this->json(404, ['error' => 'Imovel nao encontrado.']);
            return;
        }

        $error = $this->validate($input);
        if ($error !== null) {
            $this->json(422, ['error' => $error]);
            return;
        }

        $this->properties->update($id, $input);
        $this->json(200, ['data' => $this->properties->find($id)]);
    }

    public function destroy(int $id): void
    {
        if (!$this->properties->delete($id)) {
            $this->json(404, ['error' => 'Imovel nao encontrado.']);
            return;
        }
        $this->json(200, ['message' => 'Imovel removido com sucesso.']);
    }

    /**
     * @param array<string,mixed> $input
     */
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
        return null;
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
