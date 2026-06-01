<?php

declare(strict_types=1);

namespace MsReservas\Engine;

use DateTimeImmutable;

/**
 * Objeto de transporte (DTO) com os dados de entrada de uma reserva.
 *
 * Imutavel: representa a intencao de reserva recebida do cliente,
 * independente da modalidade. As modalidades concretas leem destes campos
 * (incluindo o saco de "extras") para implementar seus hotspots.
 */
final class ReservationRequest
{
    /**
     * @param array<string,mixed> $extras Campos adicionais usados por hotspots
     *                                     (ex.: 'monthly_income' para analise de credito).
     */
    public function __construct(
        public readonly int $propertyId,
        public readonly ?int $userId,
        public readonly DateTimeImmutable $checkIn,
        public readonly DateTimeImmutable $checkOut,
        public readonly float $dailyRate,
        public readonly float $monthlyRate,
        public readonly int $guests = 1,
        public readonly array $extras = []
    ) {
    }

    /**
     * Numero de diarias entre check-in e check-out (minimo 1).
     */
    public function nights(): int
    {
        $diff = $this->checkIn->diff($this->checkOut)->days;
        return max(1, (int) $diff);
    }

    /**
     * Numero aproximado de meses (para tarifas mensais).
     */
    public function months(): int
    {
        return max(1, (int) ceil($this->nights() / 30));
    }

    public function extra(string $key, mixed $default = null): mixed
    {
        return $this->extras[$key] ?? $default;
    }

    /**
     * Cria a partir de um payload JSON decodificado.
     *
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            propertyId: (int) ($data['property_id'] ?? 0),
            userId: isset($data['user_id']) ? (int) $data['user_id'] : null,
            checkIn: new DateTimeImmutable((string) ($data['check_in'] ?? 'now')),
            checkOut: new DateTimeImmutable((string) ($data['check_out'] ?? 'now')),
            dailyRate: (float) ($data['daily_rate'] ?? 0),
            monthlyRate: (float) ($data['monthly_rate'] ?? 0),
            guests: (int) ($data['guests'] ?? 1),
            extras: is_array($data['extras'] ?? null) ? $data['extras'] : []
        );
    }
}
