<?php

declare(strict_types=1);

namespace MsReservas\Engine;

use RuntimeException;

/**
 * Erro de regra de negocio durante o processamento de uma reserva.
 *
 * Carrega um codigo HTTP sugerido para que a camada de apresentacao
 * (controller) traduza a falha em uma resposta adequada.
 */
final class ReservationException extends RuntimeException
{
    public function __construct(string $message, private readonly int $httpStatus = 422)
    {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }
}
