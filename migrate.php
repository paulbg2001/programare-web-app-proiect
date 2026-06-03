<?php

require __DIR__ . '/src/db.php';

function columnExists(PDO $db, string $table, string $column): bool
{
    $stmt = $db->prepare(
        'SELECT COUNT(*)
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table_name
           AND COLUMN_NAME = :column_name'
    );
    $stmt->execute([
        'table_name' => $table,
        'column_name' => $column,
    ]);

    return (int) $stmt->fetchColumn() > 0;
}

function indexExists(PDO $db, string $table, string $index): bool
{
    $stmt = $db->prepare(
        'SELECT COUNT(*)
         FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table_name
           AND INDEX_NAME = :index_name'
    );
    $stmt->execute([
        'table_name' => $table,
        'index_name' => $index,
    ]);

    return (int) $stmt->fetchColumn() > 0;
}

$db = getDbConnection();

if (!columnExists($db, 'vehicles', 'vin')) {
    $db->exec('ALTER TABLE vehicles ADD COLUMN vin VARCHAR(17) NULL AFTER registration_number');
}

$db->exec(
    "UPDATE vehicles
     SET vin = CONCAT('LEGACY', LPAD(id, 11, '0'))
     WHERE vin IS NULL OR vin = ''"
);

$db->exec('ALTER TABLE vehicles MODIFY vin VARCHAR(17) NOT NULL');

if (!indexExists($db, 'vehicles', 'vin')) {
    $db->exec('CREATE UNIQUE INDEX vin ON vehicles (vin)');
}

if (!columnExists($db, 'vehicles', 'updated_at')) {
    $db->exec(
        'ALTER TABLE vehicles
         ADD COLUMN updated_at TIMESTAMP NOT NULL
         DEFAULT CURRENT_TIMESTAMP
         ON UPDATE CURRENT_TIMESTAMP'
    );
}

$db->exec(
    "CREATE TABLE IF NOT EXISTS drivers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        full_name VARCHAR(100) NOT NULL,
        license_number VARCHAR(40) NOT NULL UNIQUE,
        phone VARCHAR(30) DEFAULT NULL,
        email VARCHAR(150) DEFAULT NULL,
        status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

echo "Database migration completed.\n";
