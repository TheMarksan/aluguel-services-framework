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

    /**
     * @param array<string,mixed> $input
     */
    public function store(array $input): void
    {
        $modality = (string) ($input['modality'] ?? '');
        if ($modality === '') {
            $this->json(422, ['error' => "O campo 'modality' e obrigatorio."]);
            return;
        }

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
        $this->json(200, [
            'data'                  => $this->repository->all(),
            'supported_modalities'  => EngineFactory::supportedModalities(),
        ]);
    }

    public function show(int $id): void
    {
        $reservation = $this->repository->find($id);
        if ($reservation === null) {
            $this->json(404, ['error' => 'Reserva nao encontrada.']);
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
}
