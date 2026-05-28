<?php

class DriverRepository
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function listByStatus(?string $status): array
    {
        $sql = 'SELECT id, full_name, license_number, phone, email, status, created_at
                FROM drivers';
        $params = [];

        if ($status === 'active' || $status === 'inactive') {
            $sql .= ' WHERE status = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY full_name ASC, id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, full_name, license_number, phone, email, status
             FROM drivers
             WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $driver = $stmt->fetch();

        return $driver ?: null;
    }

    public function create(array $data): void
    {
        $this->assertLicenseNumberIsUnique($data['license_number']);

        $stmt = $this->db->prepare(
            'INSERT INTO drivers (full_name, license_number, phone, email, status)
             VALUES (:full_name, :license_number, :phone, :email, :status)'
        );
        $stmt->execute($data);
    }

    public function update(int $id, array $data): void
    {
        $this->assertLicenseNumberIsUnique($data['license_number'], $id);

        $stmt = $this->db->prepare(
            'UPDATE drivers
             SET full_name = :full_name,
                 license_number = :license_number,
                 phone = :phone,
                 email = :email,
                 status = :status
             WHERE id = :id'
        );
        $stmt->execute([
            'full_name' => $data['full_name'],
            'license_number' => $data['license_number'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'status' => $data['status'],
            'id' => $id,
        ]);
    }

    public function deactivate(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE drivers
             SET status = 'inactive'
             WHERE id = :id"
        );
        $stmt->execute(['id' => $id]);
    }

    private function assertLicenseNumberIsUnique(string $licenseNumber, ?int $exceptId = null): void
    {
        $sql = 'SELECT COUNT(*) FROM drivers WHERE license_number = :license_number';
        $params = ['license_number' => $licenseNumber];

        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        if ((int) $stmt->fetchColumn() > 0) {
            throw new DomainException('A driver with this license number already exists.');
        }
    }
}
