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
        $perPage = max(1, min($perPage, 100));
        $total = (int) $this->db->query('SELECT COUNT(*) FROM vehicles')->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        $stmt = $this->db->prepare(
            'SELECT id, registration_number, vin, make, model, year, fuel_type, status, created_at
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
            'SELECT id, registration_number, vin, make, model, year, fuel_type, status
             FROM vehicles
             WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $vehicle = $stmt->fetch();

        return $vehicle ?: null;
    }

    public function create(array $data): int
    {
        $data = $this->normalizeVehicleData($data);
        $this->validateVehicleData($data);
        $this->assertRegistrationIsUnique($data['registration_number']);
        $this->assertVinIsUnique($data['vin']);

        $stmt = $this->db->prepare(
            'INSERT INTO vehicles (registration_number, vin, make, model, year, fuel_type, status)
             VALUES (:registration_number, :vin, :make, :model, :year, :fuel_type, :status)'
        );
        $stmt->execute($data);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        if ($id <= 0 || $this->findById($id) === null) {
            throw new DomainException('Vehicle not found.');
        }

        $data = $this->normalizeVehicleData($data);
        $this->validateVehicleData($data);
        $this->assertRegistrationIsUnique($data['registration_number'], $id);
        $this->assertVinIsUnique($data['vin'], $id);

        $stmt = $this->db->prepare(
            'UPDATE vehicles
             SET registration_number = :registration_number,
                 vin = :vin,
                 make = :make,
                 model = :model,
                 year = :year,
                 fuel_type = :fuel_type,
                 status = :status
             WHERE id = :id'
        );
        $data['id'] = $id;
        $stmt->execute($data);
    }

    public function deactivate(int $id): void
    {
        if ($id <= 0 || $this->findById($id) === null) {
            throw new DomainException('Vehicle not found.');
        }

        $stmt = $this->db->prepare(
            "UPDATE vehicles
             SET status = 'inactive'
             WHERE id = :id"
        );
        $stmt->execute(['id' => $id]);
    }

    private function normalizeVehicleData(array $data): array
    {
        return [
            'registration_number' => strtoupper($this->cleanText((string) ($data['registration_number'] ?? ''), 30)),
            'vin' => strtoupper(str_replace(' ', '', $this->cleanText((string) ($data['vin'] ?? ''), 17))),
            'make' => $this->cleanText((string) ($data['make'] ?? ''), 80),
            'model' => $this->cleanText((string) ($data['model'] ?? ''), 80),
            'year' => (int) ($data['year'] ?? 0) ?: null,
            'fuel_type' => $this->cleanText((string) ($data['fuel_type'] ?? ''), 40) ?: null,
            'status' => $this->cleanText((string) ($data['status'] ?? 'active'), 20),
        ];
    }

    private function validateVehicleData(array $data): void
    {
        if (!preg_match('/^[A-Z0-9 -]{2,30}$/', $data['registration_number'])) {
            throw new DomainException('Registration number must contain 2-30 letters, numbers, spaces or dashes.');
        }

        if (!preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $data['vin'])) {
            throw new DomainException('VIN must contain exactly 17 valid characters.');
        }

        if ($data['make'] === '') {
            throw new DomainException('Make is required.');
        }

        if ($data['model'] === '') {
            throw new DomainException('Model is required.');
        }

        if ($data['year'] !== null && ($data['year'] < 1900 || $data['year'] > (int) date('Y') + 1)) {
            throw new DomainException('Enter a valid year.');
        }

        if (!in_array($data['status'], ['active', 'inactive', 'service'], true)) {
            throw new DomainException('Choose a valid status.');
        }
    }

    private function cleanText(string $value, int $maxLength): string
    {
        return substr(trim(strip_tags($value)), 0, $maxLength);
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
