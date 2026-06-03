<?php

class VehicleRepository
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function listPaginated(int $page, int $perPage): array
    {
        $total = (int) $this->db->query('SELECT COUNT(*) FROM vehicles')->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $stmt = $this->db->prepare(
            'SELECT id, registration_number, vin, make, model, status, created_at
             FROM vehicles
             ORDER BY created_at DESC, id DESC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'page' => $page,
            'total_pages' => $totalPages,
            'total' => $total,
        ];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, registration_number, vin, make, model, status
             FROM vehicles
             WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $vehicle = $stmt->fetch();

        return $vehicle ?: null;
    }

    public function create(array $data): void
    {
        $this->assertRegistrationIsUnique($data['registration_number']);
        $this->assertVinIsUnique($data['vin']);

        $stmt = $this->db->prepare(
            'INSERT INTO vehicles (registration_number, vin, make, model, status)
             VALUES (:registration_number, :vin, :make, :model, :status)'
        );
        $stmt->execute($data);
    }

    public function update(int $id, array $data): void
    {
        $this->assertRegistrationIsUnique($data['registration_number'], $id);
        $this->assertVinIsUnique($data['vin'], $id);

        $stmt = $this->db->prepare(
            'UPDATE vehicles
             SET registration_number = :registration_number,
                 vin = :vin,
                 make = :make,
                 model = :model,
                 status = :status
             WHERE id = :id'
        );
        $stmt->execute([
            'registration_number' => $data['registration_number'],
            'vin' => $data['vin'],
            'make' => $data['make'],
            'model' => $data['model'],
            'status' => $data['status'],
            'id' => $id,
        ]);
    }

    public function deactivate(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE vehicles
             SET status = 'inactive'
             WHERE id = :id"
        );
        $stmt->execute(['id' => $id]);
    }

    private function assertRegistrationIsUnique(string $registrationNumber, ?int $exceptId = null): void
    {
        $sql = 'SELECT COUNT(*) FROM vehicles WHERE registration_number = :registration_number';
        $params = ['registration_number' => $registrationNumber];

        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        if ((int) $stmt->fetchColumn() > 0) {
            throw new DomainException('A vehicle with this registration number already exists.');
        }
    }

    private function assertVinIsUnique(string $vin, ?int $exceptId = null): void
    {
        $sql = 'SELECT COUNT(*) FROM vehicles WHERE vin = :vin';
        $params = ['vin' => $vin];

        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        if ((int) $stmt->fetchColumn() > 0) {
            throw new DomainException('A vehicle with this VIN already exists.');
        }
    }
}
