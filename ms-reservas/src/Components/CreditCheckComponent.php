<?php

declare(strict_types=1);

namespace MsReservas\Components;

use MsReservas\Engine\ReservationRequest;

/**
 * Componente reutilizavel de analise de credito.
 *
 * Exemplo de REUSO POR COMPOSICAO: em vez de embutir a regra em uma subclasse
 * via heranca, a funcionalidade e encapsulada aqui e "plugada" na modalidade
 * que precisar dela (no caso, LongTermRent). Outras modalidades poderiam
 * reaproveitar o mesmo componente sem qualquer relacao de heranca.
 */
final class CreditCheckComponent
{
    /**
     * @param float $minIncomeRatio Renda mensal minima exigida como multiplo
     *                              do valor mensal do aluguel (ex.: 3x).
     */
    public function __construct(private readonly float $minIncomeRatio = 3.0)
    {
    }

    /**
     * Avalia se a renda informada cobre o aluguel mensal com folga.
     *
     * @return array{approved:bool,required_income:float,informed_income:float,reason:string}
     */
    public function evaluate(ReservationRequest $request): array
    {
        $monthlyRent    = $request->monthlyRate;
        $informedIncome = (float) $request->extra('monthly_income', 0.0);
        $requiredIncome = round($monthlyRent * $this->minIncomeRatio, 2);

        $approved = $informedIncome >= $requiredIncome;

        return [
            'approved'        => $approved,
            'required_income' => $requiredIncome,
            'informed_income' => $informedIncome,
            'reason'          => $approved
                ? 'Renda compativel com o aluguel.'
                : sprintf(
                    'Renda informada (R$ %.2f) inferior ao minimo exigido (R$ %.2f).',
                    $informedIncome,
                    $requiredIncome
                ),
        ];
    }
}
