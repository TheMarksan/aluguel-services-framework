<?php

declare(strict_types=1);

namespace MsReservas;

use MsReservas\Engine\EngineFactory;
use MsReservas\Engine\ReservationException;
use MsReservas\Engine\ReservationRequest;

/**
 * Controlador de reservas.
 *
 * Recebe o payload, seleciona a modalidade via EngineFactory e delega o
 * processamento ao Template Method da RentEngine concreta.
 *
 * Endpoints (consumidos via Gateway em /api/reservas/*):
 *   POST /          -> cria uma reserva (campo 'modality' define a engine)
 *   GET  /          -> lista reservas
 *   GET  /{id}      -> detalha uma reserva
 */
final class ReservationController
{
    public function __construct(private readonly ReservationRepository $repository)
    {
    }

    private function getAuthUser(): ?array
    {
        $id = $_SERVER['HTTP_X_USER_ID'] ?? null;
        $role = $_SERVER['HTTP_X_USER_ROLE'] ?? null;
        return $id ? ['id' => (int) $id, 'role' => $role] : null;
    }

    /**
     * @param array<string,mixed> $input
     */
    public function store(array $input): void
    {
        $user = $this->getAuthUser();
        if (!$user) {
            $this->json(401, ['error' => 'Acesso negado. Requisicao nao validada pelo Gateway.']);
            return;
        }

        if (!in_array($user['role'], ['admin', 'locatario'], true)) {
            $this->json(403, ['error' => 'Apenas locatarios podem solicitar novas reservas.']);
            return;
        }

        $modality = (string) ($input['modality'] ?? '');
        if ($modality === '') {
            $this->json(422, ['error' => "O campo 'modality' e obrigatorio."]);
            return;
        }

        $propertyId = (int) ($input['property_id'] ?? 0);
        if ($propertyId <= 0) {
            $this->json(422, ['error' => 'ID do imovel invalido.']);
            return;
        }

        $property = $this->fetchPropertyFromCatalog($propertyId);
        if (!$property) {
            $this->json(404, ['error' => 'Imovel nao encontrado no catalogo oficial.']);
            return;
        }

        $input['daily_rate'] = (float) $property['daily_price'];
        $input['monthly_rate'] = (float) $property['monthly_price'];

        $input['user_id'] = $user['id'];

        try {
            $engine  = EngineFactory::create($modality, $this->repository);
            $request = ReservationRequest::fromArray($input);
            $result  = $engine->processReservation($request);

            $this->json(201, ['data' => $result->toArray()]);
        } catch (ReservationException $e) {
            $this->json($e->httpStatus(), ['error' => $e->getMessage()]);
        }
    }

    public function index(): void
    {
        $user = $this->getAuthUser();
        if (!$user) {
            $this->json(401, ['error' => 'Acesso negado.']);
            return;
        }

        $allReservations = $this->repository->all();

        if ($user['role'] === 'locatario') {
            $allReservations = array_values(array_filter(
                $allReservations, 
                fn($r) => (int)$r['user_id'] === $user['id']
            ));
        }

        $this->json(200, [
            'data'                  => $allReservations,
            'supported_modalities'  => EngineFactory::supportedModalities(),
        ]);
    }

    public function show(int $id): void
    {
        $user = $this->getAuthUser();
        if (!$user) {
            $this->json(401, ['error' => 'Acesso negado.']);
            return;
        }

        $reservation = $this->repository->find($id);
        if ($reservation === null) {
            $this->json(404, ['error' => 'Reserva nao encontrada.']);
            return;
        }

        if ($user['role'] === 'locatario' && (int)$reservation['user_id'] !== $user['id']) {
            $this->json(403, ['error' => 'Acesso negado. Nao tem permissao para ver esta reserva.']);
            return;
        }

        $this->json(200, ['data' => $reservation]);
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

    /** Comunica com o ms-catalogo para buscar os precos oficiais */
    private function fetchPropertyFromCatalog(int $propertyId): ?array
    {
        $url = "http://localhost:8001/" . $propertyId;
        
        $context = stream_context_create(['http' => ['ignore_errors' => true]]);
        $response = @file_get_contents($url, false, $context);
        
        if (!$response) return null;

        $data = json_decode($response, true);
        return $data['data'] ?? null;
    }
}