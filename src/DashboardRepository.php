<?php

class DashboardRepository
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getVehicleStats(): array
    {
        $stmt = $this->db->query(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active,
                SUM(CASE WHEN status IN ('inactive', 'service') THEN 1 ELSE 0 END) AS inactive_service
             FROM vehicles"
        );

        $row = $stmt->fetch() ?: [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'active' => (int) ($row['active'] ?? 0),
            'inactive_service' => (int) ($row['inactive_service'] ?? 0),
        ];
    }

    public function getDocumentStats(): array
    {
        $stmt = $this->db->query(
            "SELECT
                SUM(CASE WHEN expiration_date < CURDATE() THEN 1 ELSE 0 END) AS expired,
                SUM(CASE WHEN expiration_date >= CURDATE()
                          AND expiration_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                         THEN 1 ELSE 0 END) AS expiring_soon
             FROM vehicle_documents"
        );

        $row = $stmt->fetch() ?: [];

        return [
            'expired' => (int) ($row['expired'] ?? 0),
            'expiring_soon' => (int) ($row['expiring_soon'] ?? 0),
        ];
    }

    public function getImportantDocuments(): array
    {
        $stmt = $this->db->query(
            "SELECT
                vd.document_type,
                vd.expiration_date,
                v.registration_number,
                v.make,
                v.model,
                CASE
                    WHEN vd.expiration_date < CURDATE() THEN 'expired'
                    WHEN vd.expiration_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'warning'
                    ELSE 'valid'
                END AS document_status
             FROM vehicle_documents vd
             INNER JOIN vehicles v ON v.id = vd.vehicle_id
             ORDER BY vd.expiration_date ASC, v.registration_number ASC
             LIMIT 10"
        );

        return $stmt->fetchAll();
    }
}
