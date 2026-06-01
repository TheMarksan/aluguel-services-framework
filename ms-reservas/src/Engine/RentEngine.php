<?php

declare(strict_types=1);

namespace MsReservas\Engine;

use MsReservas\ReservationRepository;

/**
 * RentEngine - Nucleo de reuso do framework (padrao Template Method, GoF).
 *
 * Define o ESQUELETO INVARIANTE do processo de reserva em
 * {@see RentEngine::processReservation()} (o "Template Method", marcado como
 * final para que subclasses nao alterem a ordem dos passos).
 *
 * Terminologia de frameworks:
 *   - FROZEN SPOTS (pontos congelados): passos fixos, implementados aqui e
 *     reutilizados por todas as modalidades — validacao base, verificacao de
 *     disponibilidade e persistencia.
 *   - HOT SPOTS (pontos quentes / pontos de variacao): passos que mudam por
 *     modalidade — sao metodos abstratos (obrigatorios) ou hooks (opcionais).
 *
 * Hotspots desta engine:
 *   - modalityName()       [abstract] -> identifica a modalidade.
 *   - calculatePrice()     [abstract] -> regra de precificacao especifica.
 *   - applyBusinessRules() [hook]     -> regras extra (ex.: analise de credito).
 *   - resolveStatus()      [hook]     -> status final da reserva.
 *   - afterReservation()   [hook]     -> pos-processamento (ex.: notificacao).
 */
abstract class RentEngine
{
    public function __construct(protected readonly ReservationRepository $repository)
    {
    }

    // ======================================================================
    // TEMPLATE METHOD (frozen) - esqueleto invariante, nao sobrescrevivel.
    // ======================================================================
    final public function processReservation(ReservationRequest $request): ReservationResult
    {
        // (1) FROZEN: validacao base, comum a todas as modalidades.
        $this->validateBaseRequest($request);

        // (2) FROZEN: verificacao de disponibilidade (conflito de datas).
        $this->ensureAvailability($request);

        // (3) HOTSPOT (abstract): cada modalidade calcula o preco a seu modo.
        $price = $this->calculatePrice($request);

        // (4) HOTSPOT (hook): regras de negocio adicionais e opcionais.
        $this->applyBusinessRules($request, $price);

        // (5) HOTSPOT (hook): define o status resultante (ex.: pending/confirmed).
        $status = $this->resolveStatus($request, $price);

        // (6) FROZEN: persistencia padronizada da reserva.
        $id = $this->repository->create(
            $request,
            $this->modalityName(),
            $price,
            $status,
            $this->reservationNotes($request)
        );

        // (7) HOTSPOT (hook): pos-processamento (ex.: enfileirar notificacao).
        $this->afterReservation($id, $request, $price);

        return new ReservationResult(
            id: $id,
            modality: $this->modalityName(),
            nights: $request->nights(),
            totalPrice: $price,
            status: $status,
            breakdown: $this->priceBreakdown($request, $price)
        );
    }

    // ======================================================================
    // FROZEN SPOTS - passos fixos reutilizados por todas as modalidades.
    // ======================================================================

    private function validateBaseRequest(ReservationRequest $request): void
    {
        if ($request->propertyId <= 0) {
            throw new ReservationException('Imovel (property_id) invalido.', 422);
        }
        if ($request->checkOut <= $request->checkIn) {
            throw new ReservationException('A data de check-out deve ser posterior ao check-in.', 422);
        }
        if ($request->guests < 1) {
            throw new ReservationException('Numero de hospedes invalido.', 422);
        }
    }

    private function ensureAvailability(ReservationRequest $request): void
    {
        $conflict = $this->repository->hasConflict(
            $request->propertyId,
            $request->checkIn->format('Y-m-d'),
            $request->checkOut->format('Y-m-d')
        );

        if ($conflict) {
            throw new ReservationException('Imovel indisponivel para o periodo selecionado.', 409);
        }
    }

    // ======================================================================
    // HOT SPOTS - pontos de variacao.
    // ======================================================================

    /** Identificador da modalidade (ex.: 'vacation', 'long_term'). */
    abstract protected function modalityName(): string;

    /** Regra de precificacao especifica da modalidade. */
    abstract protected function calculatePrice(ReservationRequest $request): float;

    /**
     * HOOK: regras de negocio adicionais. Implementacao padrao vazia
     * (modalidades simples nao precisam sobrescrever).
     */
    protected function applyBusinessRules(ReservationRequest $request, float $price): void
    {
        // noop por padrao.
    }

    /** HOOK: status final da reserva. Padrao: 'confirmed'. */
    protected function resolveStatus(ReservationRequest $request, float $price): string
    {
        return 'confirmed';
    }

    /** HOOK: pos-processamento apos persistir. Padrao: noop. */
    protected function afterReservation(int $reservationId, ReservationRequest $request, float $price): void
    {
        // noop por padrao.
    }

    /** HOOK: observacao gravada na reserva. Padrao: nenhuma. */
    protected function reservationNotes(ReservationRequest $request): ?string
    {
        return null;
    }

    /**
     * HOOK: detalhamento do preco para transparencia ao cliente.
     *
     * @return array<string,mixed>
     */
    protected function priceBreakdown(ReservationRequest $request, float $price): array
    {
        return [
            'modality' => $this->modalityName(),
            'nights'   => $request->nights(),
            'total'    => round($price, 2),
        ];
    }
}
