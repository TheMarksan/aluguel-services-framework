<?php

declare(strict_types=1);

namespace MsReservas\Modalities;

use MsReservas\Components\CreditCheckComponent;
use MsReservas\Engine\RentEngine;
use MsReservas\Engine\ReservationException;
use MsReservas\Engine\ReservationRequest;
use MsReservas\ReservationRepository;

/**
 * LongTermRent - Aluguel de longa duracao (residencial).
 *
 * Combina duas tecnicas de reuso:
 *   - HERANCA: reaproveita o Template Method da RentEngine (mesmo esqueleto
 *     da VacationRent), sobrescrevendo o calculo de preco.
 *   - COMPOSICAO: injeta o CreditCheckComponent e o aciona no hook
 *     applyBusinessRules(), evidenciando que regras especificas entram por
 *     pontos de variacao sem alterar o nucleo do framework.
 *
 * Regra de preco: valor mensal * numero de meses, com desconto progressivo
 * para contratos longos. A reserva so e confirmada se a analise de credito
 * for aprovada (caso contrario, fica 'pending' com a justificativa).
 */
final class LongTermRent extends RentEngine
{
    private const LONG_CONTRACT_MONTHS   = 12;
    private const LONG_CONTRACT_DISCOUNT = 0.10; // 10% para contratos >= 12 meses

    /** @var array{approved:bool,required_income:float,informed_income:float,reason:string}|null */
    private ?array $creditResult = null;

    public function __construct(
        ReservationRepository $repository,
        private readonly CreditCheckComponent $creditCheck = new CreditCheckComponent()
    ) {
        parent::__construct($repository);
    }

    protected function modalityName(): string
    {
        return 'long_term';
    }

    protected function calculatePrice(ReservationRequest $request): float
    {
        $months   = $request->months();
        $subtotal  = $months * $request->monthlyRate;

        if ($months >= self::LONG_CONTRACT_MONTHS) {
            $subtotal *= (1 - self::LONG_CONTRACT_DISCOUNT);
        }

        return $subtotal;
    }

    /**
     * HOOK sobrescrito: aciona o componente de analise de credito (composicao).
     */
    protected function applyBusinessRules(ReservationRequest $request, float $price): void
    {
        $this->creditResult = $this->creditCheck->evaluate($request);

        // Renda ausente/zerada e tratada como dado obrigatorio invalido.
        if ($this->creditResult['informed_income'] <= 0.0) {
            throw new ReservationException(
                'Informe a renda mensal (extras.monthly_income) para aluguel de longa duracao.',
                422
            );
        }
    }

    /**
     * HOOK sobrescrito: o resultado do credito define o status da reserva.
     */
    protected function resolveStatus(ReservationRequest $request, float $price): string
    {
        return ($this->creditResult['approved'] ?? false) ? 'confirmed' : 'pending';
    }

    /**
     * HOOK sobrescrito: registra a justificativa da analise de credito.
     */
    protected function reservationNotes(ReservationRequest $request): ?string
    {
        return $this->creditResult['reason'] ?? null;
    }

    /**
     * @return array<string,mixed>
     */
    protected function priceBreakdown(ReservationRequest $request, float $price): array
    {
        $months = $request->months();

        return [
            'modality'      => $this->modalityName(),
            'months'        => $months,
            'monthly_rate'  => round($request->monthlyRate, 2),
            'long_contract' => $months >= self::LONG_CONTRACT_MONTHS,
            'credit_check'  => $this->creditResult,
            'total'         => round($price, 2),
        ];
    }
}
