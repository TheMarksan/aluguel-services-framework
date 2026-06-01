<?php

declare(strict_types=1);

namespace MsCatalogo;

use PDO;

/**
 * Acesso a dados da tabela properties (db_catalogo).
 */
final class PropertyRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Lista imoveis com filtros opcionais (city, type, available).
     *
     * @param array<string,string> $filters
     * @return array<int,array<string,mixed>>
     */
    public function all(array $filters = []): array
    {
        $sql        = 'SELECT * FROM properties';
        $conditions = [];
        $params     = [];

        if (!empty($filters['city'])) {
            $conditions[] = 'city = :city';
            $params['city'] = $filters['city'];
        }
        if (!empty($filters['type'])) {
            $conditions[] = 'type = :type';
            $params['type'] = $filters['type'];
        }
        if (isset($filters['available']) && $filters['available'] !== '') {
            $conditions[] = 'available = :available';
            $params['available'] = (int) ((bool) $filters['available']);
        }

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY created_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * @return array<string,mixed>|null
     */
    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM properties WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO properties
                (owner_id, title, description, type, city, address, bedrooms, bathrooms, area_m2, daily_price, monthly_price, available)
             VALUES
                (:owner_id, :title, :description, :type, :city, :address, :bedrooms, :bathrooms, :area_m2, :daily_price, :monthly_price, :available)'
        );
        $stmt->execute($this->bind($data));

        return (int) $this->pdo->lastInsertId();
    }

    /**
     * @param array<string,mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        $params = $this->bind($data);
        $params['id'] = $id;

        $stmt = $this->pdo->prepare(
            'UPDATE properties SET
                owner_id = :owner_id, title = :title, description = :description, type = :type,
                city = :city, address = :address, bedrooms = :bedrooms, bathrooms = :bathrooms,
                area_m2 = :area_m2, daily_price = :daily_price, monthly_price = :monthly_price, available = :available
             WHERE id = :id'
        );
        $stmt->execute($params);

        return $stmt->rowCount() >= 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM properties WHERE id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Normaliza os campos para bind seguro.
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function bind(array $data): array
    {
        return [
            'owner_id'      => isset($data['owner_id']) ? (int) $data['owner_id'] : null,
            'title'         => (string) ($data['title'] ?? ''),
            'description'   => (string) ($data['description'] ?? ''),
            'type'          => in_array($data['type'] ?? '', ['casa', 'apartamento'], true) ? $data['type'] : 'apartamento',
            'city'          => (string) ($data['city'] ?? ''),
            'address'       => (string) ($data['address'] ?? ''),
            'bedrooms'      => (int) ($data['bedrooms'] ?? 1),
            'bathrooms'     => (int) ($data['bathrooms'] ?? 1),
            'area_m2'       => (float) ($data['area_m2'] ?? 0),
            'daily_price'   => (float) ($data['daily_price'] ?? 0),
            'monthly_price' => (float) ($data['monthly_price'] ?? 0),
            'available'     => (int) ((bool) ($data['available'] ?? true)),
        ];
    }
}
