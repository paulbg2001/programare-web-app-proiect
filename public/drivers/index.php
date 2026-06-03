<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/logger.php';
require_once __DIR__ . '/../../src/DriverRepository.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function cleanText(string $value, int $maxLength): string
{
    return substr(trim(strip_tags($value)), 0, $maxLength);
}

function driverInput(array $source): array
{
    $email = cleanText($source['email'] ?? '', 150);
    $phone = cleanText($source['phone'] ?? '', 30);

    return [
        'full_name' => cleanText($source['full_name'] ?? '', 100),
        'license_number' => strtoupper(cleanText($source['license_number'] ?? '', 40)),
        'phone' => $phone === '' ? null : $phone,
        'email' => $email === '' ? null : $email,
        'status' => cleanText($source['status'] ?? 'active', 20),
    ];
}

function validateDriver(array $data): array
{
    $errors = [];

    if (strlen($data['full_name']) < 2) {
        $errors[] = 'Full name is required.';
    }

    if (!preg_match('/^[A-Z0-9 -]{3,40}$/', $data['license_number'])) {
        $errors[] = 'License number must contain 3-40 letters, numbers, spaces or dashes.';
    }

    if ($data['email'] !== null && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }

    if (!in_array($data['status'], ['active', 'inactive'], true)) {
        $errors[] = 'Choose a valid status.';
    }

    return $errors;
}

function driverStatusLabel(string $status): string
{
    return [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ][$status] ?? 'Active';
}

$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

$errors = [];
$systemError = '';
$statusFilter = $_GET['status'] ?? 'all';
$statusFilter = in_array($statusFilter, ['all', 'active', 'inactive'], true) ? $statusFilter : 'all';
$formData = [
    'id' => '',
    'full_name' => '',
    'license_number' => '',
    'phone' => '',
    'email' => '',
    'status' => 'active',
];
$drivers = [];

try {
    $repository = new DriverRepository(getDbConnection());

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'deactivate') {
            $repository->deactivate((int) ($_POST['id'] ?? 0));
            $_SESSION['success'] = 'Driver marked as inactive.';
            header('Location: index.php?status=' . urlencode($statusFilter));
            exit;
        }

        if ($action === 'create' || $action === 'update') {
            $formData = array_merge($formData, driverInput($_POST));
            $formData['id'] = (string) ($_POST['id'] ?? '');
            $errors = validateDriver($formData);

            if (!$errors) {
                if ($action === 'create') {
                    $repository->create([
                        'full_name' => $formData['full_name'],
                        'license_number' => $formData['license_number'],
                        'phone' => $formData['phone'],
                        'email' => $formData['email'],
                        'status' => $formData['status'],
                    ]);
                    $_SESSION['success'] = 'Driver added.';
                } else {
                    $repository->update((int) $formData['id'], [
                        'full_name' => $formData['full_name'],
                        'license_number' => $formData['license_number'],
                        'phone' => $formData['phone'],
                        'email' => $formData['email'],
                        'status' => $formData['status'],
                    ]);
                    $_SESSION['success'] = 'Driver updated.';
                }

                header('Location: index.php?status=' . urlencode($statusFilter));
                exit;
            }
        }
    } elseif (isset($_GET['edit'])) {
        $driver = $repository->findById((int) $_GET['edit']);

        if ($driver) {
            $formData = array_merge($formData, $driver);
        } else {
            $errors[] = 'Driver not found.';
        }
    }

    $drivers = $repository->listByStatus($statusFilter === 'all' ? null : $statusFilter);
} catch (DomainException $e) {
    $errors[] = $e->getMessage();
    $drivers = isset($repository) ? $repository->listByStatus($statusFilter === 'all' ? null : $statusFilter) : $drivers;
} catch (PDOException $e) {
    appLog('Driver management database error', [
        'user_id' => $_SESSION['user_id'],
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
    ]);

    $systemError = 'Driver data could not be loaded. Check the database schema.';
}

$isEditing = $formData['id'] !== '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Drivers | Car Management</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <main class="app-page">
        <header class="topbar">
            <div>
                <p class="brand">
                    <span class="brand-mark">CM</span>
                    <span>Car Management</span>
                </p>
                <h1 class="topbar-title">Drivers</h1>
            </div>
            <nav class="topbar-nav" aria-label="Main navigation">
                <a href="../dashboard.php">Dashboard</a>
                <a href="../vehicles/index.php">Vehicles</a>
                <a href="index.php" aria-current="page">Drivers</a>
                <a href="../auth/logout.php">Logout</a>
            </nav>
        </header>

        <section class="management-shell">
            <?php if ($systemError): ?>
                <p class="message error dashboard-message"><?php echo e($systemError); ?></p>
            <?php endif; ?>

            <?php if ($success): ?>
                <p class="message success dashboard-message"><?php echo e($success); ?></p>
            <?php endif; ?>

            <?php if ($errors): ?>
                <div class="message error dashboard-message">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo e($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="management-grid">
                <section class="page-panel management-form-panel" aria-labelledby="driver-form-title">
                    <div class="section-title-row">
                        <h2 id="driver-form-title"><?php echo $isEditing ? 'Edit Driver' : 'Add Driver'; ?></h2>
                        <?php if ($isEditing): ?>
                            <a href="index.php?status=<?php echo e($statusFilter); ?>">Cancel</a>
                        <?php endif; ?>
                    </div>

                    <form class="management-form" method="post" action="index.php?status=<?php echo e($statusFilter); ?>">
                        <input type="hidden" name="action" value="<?php echo $isEditing ? 'update' : 'create'; ?>">
                        <input type="hidden" name="id" value="<?php echo e((string) $formData['id']); ?>">

                        <div class="form-row">
                            <label for="full_name">Full Name</label>
                            <input id="full_name" name="full_name" type="text" value="<?php echo e($formData['full_name']); ?>" required>
                        </div>

                        <div class="form-row">
                            <label for="license_number">License Number</label>
                            <input id="license_number" name="license_number" type="text" value="<?php echo e($formData['license_number']); ?>" required>
                        </div>

                        <div class="form-row">
                            <label for="phone">Phone</label>
                            <input id="phone" name="phone" type="text" value="<?php echo e((string) $formData['phone']); ?>">
                        </div>

                        <div class="form-row">
                            <label for="email">Email</label>
                            <input id="email" name="email" type="email" value="<?php echo e((string) $formData['email']); ?>">
                        </div>

                        <div class="form-row">
                            <label for="status">Status</label>
                            <select id="status" name="status" required>
                                <?php foreach (['active', 'inactive'] as $status): ?>
                                    <option value="<?php echo e($status); ?>" <?php echo $formData['status'] === $status ? 'selected' : ''; ?>>
                                        <?php echo e(driverStatusLabel($status)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-actions">
                            <button class="primary-button compact-button" type="submit">
                                <?php echo $isEditing ? 'Save' : 'Add'; ?>
                            </button>
                        </div>
                    </form>
                </section>

                <section class="page-panel management-list-panel" aria-labelledby="drivers-list-title">
                    <div class="section-title-row">
                        <h2 id="drivers-list-title">Driver List</h2>
                        <span><?php echo count($drivers); ?> shown</span>
                    </div>

                    <nav class="filter-row" aria-label="Driver filters">
                        <?php foreach (['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive'] as $value => $label): ?>
                            <a href="index.php?status=<?php echo e($value); ?>" <?php echo $statusFilter === $value ? 'aria-current="page"' : ''; ?>>
                                <?php echo e($label); ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>

                    <?php if ($drivers): ?>
                        <div class="documents-table-wrap">
                            <table class="documents-table management-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>License</th>
                                        <th>Contact</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($drivers as $driver): ?>
                                        <tr>
                                            <td><strong><?php echo e($driver['full_name']); ?></strong></td>
                                            <td><?php echo e($driver['license_number']); ?></td>
                                            <td>
                                                <?php echo e($driver['phone'] ?: 'No phone'); ?>
                                                <span class="vehicle-description"><?php echo e($driver['email'] ?: 'No email'); ?></span>
                                            </td>
                                            <td>
                                                <span class="status-pill status-<?php echo e($driver['status']); ?>">
                                                    <?php echo e(driverStatusLabel($driver['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="inline-actions">
                                                    <a class="secondary-button" href="index.php?edit=<?php echo (int) $driver['id']; ?>&status=<?php echo e($statusFilter); ?>">Edit</a>
                                                    <form method="post" action="index.php?status=<?php echo e($statusFilter); ?>">
                                                        <input type="hidden" name="action" value="deactivate">
                                                        <input type="hidden" name="id" value="<?php echo (int) $driver['id']; ?>">
                                                        <button class="danger-button" type="submit">Deactivate</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="empty-state">No drivers found for this filter.</p>
                    <?php endif; ?>
                </section>
            </div>
        </section>
    </main>
</body>
</html>
