<?php

declare(strict_types=1);

namespace MsReservas;

use MsReservas\Engine\ReservationRequest;
use PDO;

/**
 * Acesso a dados da tabela reservations (db_reservas).
 */
final class ReservationRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Verifica se existe sobreposicao de datas para o mesmo imovel
     * (reservas nao canceladas/rejeitadas).
     */
    public function hasConflict(int $propertyId, string $checkIn, string $checkOut): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM reservations
             WHERE property_id = :pid
               AND status IN (\'pending\', \'confirmed\')
               AND check_in < :check_out
               AND check_out > :check_in
             LIMIT 1'
        );
        $stmt->execute([
            'pid'       => $propertyId,
            'check_in'  => $checkIn,
            'check_out' => $checkOut,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function create(
        ReservationRequest $req,
        string $modality,
        float $totalPrice,
        string $status,
        ?string $notes = null
    ): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO reservations
                (property_id, user_id, modality, check_in, check_out, nights, total_price, status, notes)
             VALUES
                (:property_id, :user_id, :modality, :check_in, :check_out, :nights, :total_price, :status, :notes)'
        );
        $stmt->execute([
            'property_id' => $req->propertyId,
            'user_id'     => $req->userId,
            'modality'    => $modality,
            'check_in'    => $req->checkIn->format('Y-m-d'),
            'check_out'   => $req->checkOut->format('Y-m-d'),
            'nights'      => $req->nights(),
            'total_price' => round($totalPrice, 2),
            'status'      => $status,
            'notes'       => $notes,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function all(): array
    {
        return $this->pdo->query('SELECT * FROM reservations ORDER BY created_at DESC')->fetchAll();
    }

    /**
     * @return array<string,mixed>|null
     */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM reservations WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }
}
