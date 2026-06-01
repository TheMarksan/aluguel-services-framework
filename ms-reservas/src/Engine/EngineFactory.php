<?php

declare(strict_types=1);

namespace MsReservas\Engine;

use MsReservas\ReservationRepository;

/**
 * Fabrica de engines de reserva.
 *
 * Resolve a modalidade solicitada para a subclasse concreta de {@see RentEngine}.
 * Este e o ponto de extensao do framework: novas modalidades sao registradas
 * aqui sem alterar o nucleo (Open/Closed Principle).
 *
 * Obs.: as modalidades concretas (VacationRent, LongTermRent) sao adicionadas
 * na Etapa 6. Ate la, qualquer modalidade retorna erro 400.
 */
final class EngineFactory
{
    public static function create(string $modality, ReservationRepository $repository): RentEngine
    {
        return match ($modality) {
            // Registrado na Etapa 6 (classes concretas):
            // 'vacation'  => new \MsReservas\Modalities\VacationRent($repository),
            // 'long_term' => new \MsReservas\Modalities\LongTermRent($repository),
            default => throw new ReservationException(
                "Modalidade de aluguel '{$modality}' nao suportada.",
                400
            ),
        };
    }

    /**
     * Modalidades atualmente disponiveis.
     *
     * @return array<int,string>
     */
    public static function supportedModalities(): array
    {
        return [];
    }
}
