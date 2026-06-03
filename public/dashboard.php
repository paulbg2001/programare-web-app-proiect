<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/logger.php';
require_once __DIR__ . '/../src/DashboardService.php';

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
        'expired' => 'Expired',
        'warning' => 'Expires soon',
        'valid' => 'Valid',
    ][$status] ?? 'Valid';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | Car Management</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <main class="app-page">
        <header class="topbar">
            <div>
                <p class="brand">
                    <span class="brand-mark">CM</span>
                    <span>Car Management</span>
                </p>
                <h1 class="topbar-title">Fleet Dashboard</h1>
            </div>
            <nav class="topbar-nav" aria-label="Main navigation">
                <a href="dashboard.php" aria-current="page">Dashboard</a>
                <a href="vehicles/index.php">Vehicles</a>
                <a href="drivers/index.php">Drivers</a>
                <a href="auth/logout.php">Logout</a>
            </nav>
        </header>

        <section class="dashboard-shell" aria-labelledby="dashboard-title">
            <div class="dashboard-heading">
                <div>
                    <p class="eyebrow">Current fleet status</p>
                    <h2 id="dashboard-title">Overview</h2>
                </div>
                <p class="dashboard-date"><?php echo e(date('F j, Y')); ?></p>
            </div>

            <?php if ($error): ?>
                <p class="message error dashboard-message"><?php echo e($error); ?></p>
            <?php endif; ?>

            <div class="stats-grid" aria-label="Fleet and document statistics">
                <article class="stat-card">
                    <span class="stat-label">Total vehicles</span>
                    <strong><?php echo (int) $dashboard['vehicles']['total']; ?></strong>
                </article>
                <article class="stat-card">
                    <span class="stat-label">Active vehicles</span>
                    <strong><?php echo (int) $dashboard['vehicles']['active']; ?></strong>
                </article>
                <article class="stat-card">
                    <span class="stat-label">Inactive / service</span>
                    <strong><?php echo (int) $dashboard['vehicles']['inactive_service']; ?></strong>
                </article>
                <article class="stat-card danger-card">
                    <span class="stat-label">Expired documents</span>
                    <strong><?php echo (int) $dashboard['documents']['expired']; ?></strong>
                </article>
                <article class="stat-card warning-card">
                    <span class="stat-label">Expiring in 30 days</span>
                    <strong><?php echo (int) $dashboard['documents']['expiring_soon']; ?></strong>
                </article>
            </div>

            <section class="documents-section" aria-labelledby="documents-title">
                <div class="section-title-row">
                    <h2 id="documents-title">Documents Requiring Attention</h2>
                    <span><?php echo count($dashboard['importantDocuments']); ?> shown</span>
                </div>

                <?php if ($dashboard['importantDocuments']): ?>
                    <div class="documents-table-wrap">
                        <table class="documents-table">
                            <thead>
                                <tr>
                                    <th>Vehicle</th>
                                    <th>Document</th>
                                    <th>Expiration date</th>
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
                    <p class="empty-state">No vehicle documents found in the database.</p>
                <?php endif; ?>
            </section>
        </section>
    </main>
</body>
</html>
