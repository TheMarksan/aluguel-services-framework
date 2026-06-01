<?php

declare(strict_types=1);

namespace MsReservas\Engine;

/**
 * Resultado de uma reserva processada pelo Template Method.
 */
final class ReservationResult
{
    /**
     * @param array<string,mixed> $breakdown Detalhamento do calculo (transparencia de preco)
     */
    public function __construct(
        public readonly int $id,
        public readonly string $modality,
        public readonly int $nights,
        public readonly float $totalPrice,
        public readonly string $status,
        public readonly array $breakdown = []
    ) {
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'modality'    => $this->modality,
            'nights'      => $this->nights,
            'total_price' => round($this->totalPrice, 2),
            'status'      => $this->status,
            'breakdown'   => $this->breakdown,
        ];
    }
}
