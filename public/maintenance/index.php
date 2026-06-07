<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/logger.php';

requireAuth('/auth/login.php');

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function dueStatus(string $date): string
{
    if ($date < date('Y-m-d')) {
        return 'expired';
    }

    if ($date <= date('Y-m-d', strtotime('+30 days'))) {
        return 'warning';
    }

    return 'valid';
}

function dueStatusLabel(string $status): string
{
    return [
        'expired' => 'Depasit',
        'warning' => 'In curand',
        'valid' => 'Planificat',
    ][$status] ?? 'Planificat';
}

$systemError = '';
$documents = [];
$tires = [];

try {
    $db = getDbConnection();

    $documents = $db->query(
        "SELECT vd.document_type, vd.expiration_date,
                v.registration_number, v.make, v.model
         FROM vehicle_documents vd
         INNER JOIN vehicles v ON v.id = vd.vehicle_id
         WHERE vd.expiration_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
         ORDER BY vd.expiration_date ASC"
    )->fetchAll();

    $tires = $db->query(
        "SELECT vt.tire_type, vt.brand, vt.size, vt.change_date,
                v.registration_number, v.make, v.model
         FROM vehicle_tires vt
         INNER JOIN vehicles v ON v.id = vt.vehicle_id
         WHERE vt.change_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
         ORDER BY vt.change_date ASC"
    )->fetchAll();
} catch (PDOException $e) {
    appLog('Maintenance page database error', [
        'user_id' => $_SESSION['user_id'],
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
    ]);

    $systemError = 'Mentenanta nu a putut fi incarcata. Ruleaza migrate.php.';
}

$pageTitle = 'Mentenanta';
$pageKicker = 'Gestiune flota';
$activePage = 'maintenance';

require __DIR__ . '/../includes/header.php';
?>
        <section class="management-shell">
            <?php if ($systemError): ?>
                <p class="message error dashboard-message"><?php echo e($systemError); ?></p>
            <?php endif; ?>

            <section class="page-panel management-list-panel">
                <div class="section-title-row">
                    <h2>Documente de verificat</h2>
                    <span><?php echo count($documents); ?> afisate</span>
                </div>

                <?php if ($documents): ?>
                    <div class="documents-table-wrap">
                        <table class="documents-table">
                            <thead>
                                <tr>
                                    <th>Vehicul</th>
                                    <th>Lucrare</th>
                                    <th>Data</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $document): ?>
                                    <?php $status = dueStatus($document['expiration_date']); ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo e($document['registration_number']); ?></strong>
                                            <span class="vehicle-description"><?php echo e($document['make'] . ' ' . $document['model']); ?></span>
                                        </td>
                                        <td>Expira <?php echo e($document['document_type']); ?></td>
                                        <td><?php echo e(date('d.m.Y', strtotime($document['expiration_date']))); ?></td>
                                        <td>
                                            <span class="status-pill status-<?php echo e($status); ?>">
                                                <?php echo e(dueStatusLabel($status)); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="empty-state">Nu sunt documente expirate sau care expira in urmatoarele 30 de zile.</p>
                <?php endif; ?>
            </section>

            <section class="page-panel management-list-panel maintenance-section">
                <div class="section-title-row">
                    <h2>Anvelope de schimbat</h2>
                    <span><?php echo count($tires); ?> afisate</span>
                </div>

                <?php if ($tires): ?>
                    <div class="documents-table-wrap">
                        <table class="documents-table">
                            <thead>
                                <tr>
                                    <th>Vehicul</th>
                                    <th>Anvelope</th>
                                    <th>Schimbare</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tires as $tire): ?>
                                    <?php $status = dueStatus($tire['change_date']); ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo e($tire['registration_number']); ?></strong>
                                            <span class="vehicle-description"><?php echo e($tire['make'] . ' ' . $tire['model']); ?></span>
                                        </td>
                                        <td>
                                            <?php echo e($tire['tire_type']); ?>
                                            <span class="vehicle-description"><?php echo e(trim(($tire['brand'] ?: '') . ' ' . ($tire['size'] ?: '')) ?: '-'); ?></span>
                                        </td>
                                        <td><?php echo e(date('d.m.Y', strtotime($tire['change_date']))); ?></td>
                                        <td>
                                            <span class="status-pill status-<?php echo e($status); ?>">
                                                <?php echo e(dueStatusLabel($status)); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="empty-state">Nu sunt anvelope de schimbat in urmatoarele 30 de zile.</p>
                <?php endif; ?>
            </section>
        </section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
