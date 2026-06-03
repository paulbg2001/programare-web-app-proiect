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

    public function create(array $data): int
    {
        $data = $this->normalizeDriverData($data);
        $this->validateDriverData($data);
        $this->assertLicenseNumberIsUnique($data['license_number']);

        $stmt = $this->db->prepare(
            'INSERT INTO drivers (full_name, license_number, phone, email, status)
             VALUES (:full_name, :license_number, :phone, :email, :status)'
        );
        $stmt->execute($data);

        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        if ($id <= 0 || $this->findById($id) === null) {
            throw new DomainException('Driver not found.');
        }

        $data = $this->normalizeDriverData($data);
        $this->validateDriverData($data);
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
        if ($id <= 0 || $this->findById($id) === null) {
            throw new DomainException('Driver not found.');
        }

        $stmt = $this->db->prepare(
            "UPDATE drivers
             SET status = 'inactive'
             WHERE id = :id"
        );
        $stmt->execute(['id' => $id]);
    }

    private function normalizeDriverData(array $data): array
    {
        $email = $this->cleanText((string) ($data['email'] ?? ''), 150);
        $phone = $this->cleanText((string) ($data['phone'] ?? ''), 30);

        return [
            'full_name' => $this->cleanText((string) ($data['full_name'] ?? ''), 100),
            'license_number' => strtoupper($this->cleanText((string) ($data['license_number'] ?? ''), 40)),
            'phone' => $phone === '' ? null : $phone,
            'email' => $email === '' ? null : $email,
            'status' => $this->cleanText((string) ($data['status'] ?? 'active'), 20),
        ];
    }

    private function validateDriverData(array $data): void
    {
        if (strlen($data['full_name']) < 2) {
            throw new DomainException('Full name is required.');
        }

        if (!preg_match('/^[A-Z0-9 -]{3,40}$/', $data['license_number'])) {
            throw new DomainException('License number must contain 3-40 letters, numbers, spaces or dashes.');
        }

        if ($data['email'] !== null && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Enter a valid email address.');
        }

        if (!in_array($data['status'], ['active', 'inactive'], true)) {
            throw new DomainException('Choose a valid status.');
        }
    }

    private function cleanText(string $value, int $maxLength): string
    {
        return substr(trim(strip_tags($value)), 0, $maxLength);
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
