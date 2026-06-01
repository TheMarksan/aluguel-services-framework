<?php

declare(strict_types=1);

namespace MsReservas\Modalities;

use MsReservas\Engine\RentEngine;
use MsReservas\Engine\ReservationRequest;

/**
 * VacationRent - Aluguel por temporada.
 *
 * Demonstra o reuso por HERANCA: reaproveita integralmente o esqueleto do
 * Template Method (validacao, disponibilidade, persistencia) e sobrescreve
 * apenas os hotspots minimos obrigatorios. Por ser uma modalidade simples,
 * nao precisa dos hooks opcionais (usa o comportamento default da RentEngine).
 *
 * Regra de preco: diaria * numero de noites, com acrescimo de alta temporada
 * para reservas curtas (ate 3 noites) e taxa de limpeza fixa.
 */
final class VacationRent extends RentEngine
{
    private const CLEANING_FEE      = 120.0;
    private const HIGH_SEASON_BONUS = 0.15; // 15% para estadias curtas

    protected function modalityName(): string
    {
        return 'vacation';
    }

    protected function calculatePrice(ReservationRequest $request): float
    {
        $nights   = $request->nights();
        $subtotal = $nights * $request->dailyRate;

        if ($nights <= 3) {
            $subtotal *= (1 + self::HIGH_SEASON_BONUS);
        }

        return $subtotal + self::CLEANING_FEE;
    }

    /**
     * Detalhamento transparente do preco (sobrescreve o hook de breakdown).
     *
     * @return array<string,mixed>
     */
    protected function priceBreakdown(ReservationRequest $request, float $price): array
    {
        $nights = $request->nights();

        return [
            'modality'         => $this->modalityName(),
            'nights'           => $nights,
            'daily_rate'       => round($request->dailyRate, 2),
            'cleaning_fee'     => self::CLEANING_FEE,
            'high_season'      => $nights <= 3,
            'total'            => round($price, 2),
        ];
    }
}
