<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/logger.php';
require_once __DIR__ . '/../src/DashboardService.php';

requireAuth('/auth/login.php');

$dashboard = [
    'vehicles' => [
        'total' => 0,
        'active' => 0,
        'inactive_service' => 0,
    ],
    'documents' => [
        'expired' => 0,
        'expiring_soon' => 0,
    ],
    'assignments' => [
        'active' => 0,
    ],
    'importantDocuments' => [],
];
$error = '';

try {
    $db = getDbConnection();
    $service = new DashboardService(new DashboardRepository($db));
    $dashboard = $service->getDashboardData();
} catch (PDOException $e) {
    appLog('Dashboard database error', [
        'user_id' => $_SESSION['user_id'],
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
    ]);

    $error = 'Dashboard data could not be loaded. Check that the database schema is up to date.';
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function documentStatusLabel(string $status): string
{
    return [
        'expired' => 'Expirat',
        'warning' => 'Expira curand',
        'valid' => 'Valabil',
    ][$status] ?? 'Valabil';
}

$pageTitle = 'Homepage';
$pageKicker = 'Sumar flota';
$activePage = 'dashboard';

require __DIR__ . '/includes/header.php';
?>
        <section class="dashboard-shell" aria-labelledby="dashboard-title">
            <div class="dashboard-heading">
                <div>
                    <p class="eyebrow">Status curent</p>
                    <h2 id="dashboard-title">Indicatori operationali</h2>
                </div>
                <p class="dashboard-date"><?php echo e(date('d.m.Y')); ?></p>
            </div>

            <?php if ($error): ?>
                <p class="message error dashboard-message"><?php echo e($error); ?></p>
            <?php endif; ?>

            <div class="stats-grid" aria-label="Fleet and document statistics">
                <article class="stat-card">
                    <span class="stat-label">Vehicule totale</span>
                    <strong><?php echo (int) $dashboard['vehicles']['total']; ?></strong>
                </article>
                <article class="stat-card">
                    <span class="stat-label">Vehicule active</span>
                    <strong><?php echo (int) $dashboard['vehicles']['active']; ?></strong>
                </article>
                <article class="stat-card">
                    <span class="stat-label">Inactive / service</span>
                    <strong><?php echo (int) $dashboard['vehicles']['inactive_service']; ?></strong>
                </article>
                <article class="stat-card danger-card">
                    <span class="stat-label">Documente expirate</span>
                    <strong><?php echo (int) $dashboard['documents']['expired']; ?></strong>
                </article>
                <article class="stat-card warning-card">
                    <span class="stat-label">Expira in 30 zile</span>
                    <strong><?php echo (int) $dashboard['documents']['expiring_soon']; ?></strong>
                </article>
                <article class="stat-card accent-card">
                    <span class="stat-label">Alocari active</span>
                    <strong><?php echo (int) $dashboard['assignments']['active']; ?></strong>
                </article>
            </div>

            <section class="documents-section" aria-labelledby="documents-title">
                <div class="section-title-row">
                    <h2 id="documents-title">Documente monitorizate</h2>
                    <span><?php echo count($dashboard['importantDocuments']); ?> afisate</span>
                </div>

                <?php if ($dashboard['importantDocuments']): ?>
                    <div class="documents-table-wrap">
                        <table class="documents-table">
                            <thead>
                                <tr>
                                    <th>Vehicul</th>
                                    <th>Document</th>
                                    <th>Data expirarii</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($dashboard['importantDocuments'] as $document): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo e($document['registration_number']); ?></strong>
                                            <span class="vehicle-description"><?php echo e($document['make'] . ' ' . $document['model']); ?></span>
                                        </td>
                                        <td><?php echo e($document['document_type']); ?></td>
                                        <td><?php echo e(date('M j, Y', strtotime($document['expiration_date']))); ?></td>
                                        <td>
                                            <span class="status-pill status-<?php echo e($document['document_status']); ?>">
                                                <?php echo e(documentStatusLabel($document['document_status'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="empty-state">Nu exista documente in baza de date.</p>
                <?php endif; ?>
            </section>
        </section>
<?php require __DIR__ . '/includes/footer.php'; ?>
