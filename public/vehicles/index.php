<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/logger.php';
require_once __DIR__ . '/../../src/VehicleRepository.php';

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function cleanText(string $value, int $maxLength): string
{
    return substr(trim(strip_tags($value)), 0, $maxLength);
}

function vehicleInput(array $source): array
{
    return [
        'registration_number' => strtoupper(cleanText($source['registration_number'] ?? '', 30)),
        'vin' => strtoupper(str_replace(' ', '', cleanText($source['vin'] ?? '', 17))),
        'make' => cleanText($source['make'] ?? '', 80),
        'model' => cleanText($source['model'] ?? '', 80),
        'status' => cleanText($source['status'] ?? 'active', 20),
    ];
}

function validateVehicle(array $data): array
{
    $errors = [];

    if (!preg_match('/^[A-Z0-9 -]{2,30}$/', $data['registration_number'])) {
        $errors[] = 'Registration number must contain 2-30 letters, numbers, spaces or dashes.';
    }

    if (!preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $data['vin'])) {
        $errors[] = 'VIN must contain exactly 17 valid characters.';
    }

    if ($data['make'] === '') {
        $errors[] = 'Make is required.';
    }

    if ($data['model'] === '') {
        $errors[] = 'Model is required.';
    }

    if (!in_array($data['status'], ['active', 'inactive', 'service'], true)) {
        $errors[] = 'Choose a valid status.';
    }

    return $errors;
}

function vehicleStatusLabel(string $status): string
{
    return [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'service' => 'Service',
    ][$status] ?? 'Active';
}

$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

$errors = [];
$systemError = '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$formData = [
    'id' => '',
    'registration_number' => '',
    'vin' => '',
    'make' => '',
    'model' => '',
    'status' => 'active',
];
$vehicles = [
    'items' => [],
    'page' => 1,
    'total_pages' => 1,
    'total' => 0,
];

try {
    $repository = new VehicleRepository(getDbConnection());

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'deactivate') {
            $repository->deactivate((int) ($_POST['id'] ?? 0));
            $_SESSION['success'] = 'Vehicle marked as inactive.';
            header('Location: index.php?page=' . $page);
            exit;
        }

        if ($action === 'create' || $action === 'update') {
            $formData = array_merge($formData, vehicleInput($_POST));
            $formData['id'] = (string) ($_POST['id'] ?? '');
            $errors = validateVehicle($formData);

            if (!$errors) {
                if ($action === 'create') {
                    $repository->create([
                        'registration_number' => $formData['registration_number'],
                        'vin' => $formData['vin'],
                        'make' => $formData['make'],
                        'model' => $formData['model'],
                        'status' => $formData['status'],
                    ]);
                    $_SESSION['success'] = 'Vehicle added.';
                } else {
                    $repository->update((int) $formData['id'], [
                        'registration_number' => $formData['registration_number'],
                        'vin' => $formData['vin'],
                        'make' => $formData['make'],
                        'model' => $formData['model'],
                        'status' => $formData['status'],
                    ]);
                    $_SESSION['success'] = 'Vehicle updated.';
                }

                header('Location: index.php?page=' . $page);
                exit;
            }
        }
    } elseif (isset($_GET['edit'])) {
        $vehicle = $repository->findById((int) $_GET['edit']);

        if ($vehicle) {
            $formData = array_merge($formData, $vehicle);
        } else {
            $errors[] = 'Vehicle not found.';
        }
    }

    $vehicles = $repository->listPaginated($page, 8);
} catch (DomainException $e) {
    $errors[] = $e->getMessage();
    $vehicles = isset($repository) ? $repository->listPaginated($page, 8) : $vehicles;
} catch (PDOException $e) {
    appLog('Vehicle management database error', [
        'user_id' => $_SESSION['user_id'],
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
    ]);

    $systemError = 'Vehicle data could not be loaded. Check the database schema.';
}

$isEditing = $formData['id'] !== '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vehicles | Car Management</title>
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
                <h1 class="topbar-title">Vehicles</h1>
            </div>
            <nav class="topbar-nav" aria-label="Main navigation">
                <a href="../dashboard.php">Dashboard</a>
                <a href="index.php" aria-current="page">Vehicles</a>
                <a href="../drivers/index.php">Drivers</a>
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
                <section class="page-panel management-form-panel" aria-labelledby="vehicle-form-title">
                    <div class="section-title-row">
                        <h2 id="vehicle-form-title"><?php echo $isEditing ? 'Edit Vehicle' : 'Add Vehicle'; ?></h2>
                        <?php if ($isEditing): ?>
                            <a href="index.php?page=<?php echo (int) $vehicles['page']; ?>">Cancel</a>
                        <?php endif; ?>
                    </div>

                    <form class="management-form" method="post" action="index.php?page=<?php echo (int) $page; ?>">
                        <input type="hidden" name="action" value="<?php echo $isEditing ? 'update' : 'create'; ?>">
                        <input type="hidden" name="id" value="<?php echo e((string) $formData['id']); ?>">

                        <div class="form-row">
                            <label for="registration_number">Registration Number</label>
                            <input id="registration_number" name="registration_number" type="text" value="<?php echo e($formData['registration_number']); ?>" required>
                        </div>

                        <div class="form-row">
                            <label for="vin">VIN</label>
                            <input id="vin" name="vin" type="text" maxlength="17" value="<?php echo e($formData['vin']); ?>" required>
                        </div>

                        <div class="form-row">
                            <label for="make">Make</label>
                            <input id="make" name="make" type="text" value="<?php echo e($formData['make']); ?>" required>
                        </div>

                        <div class="form-row">
                            <label for="model">Model</label>
                            <input id="model" name="model" type="text" value="<?php echo e($formData['model']); ?>" required>
                        </div>

                        <div class="form-row">
                            <label for="status">Status</label>
                            <select id="status" name="status" required>
                                <?php foreach (['active', 'service', 'inactive'] as $status): ?>
                                    <option value="<?php echo e($status); ?>" <?php echo $formData['status'] === $status ? 'selected' : ''; ?>>
                                        <?php echo e(vehicleStatusLabel($status)); ?>
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

                <section class="page-panel management-list-panel" aria-labelledby="vehicles-list-title">
                    <div class="section-title-row">
                        <h2 id="vehicles-list-title">Vehicle List</h2>
                        <span><?php echo (int) $vehicles['total']; ?> total</span>
                    </div>

                    <?php if ($vehicles['items']): ?>
                        <div class="documents-table-wrap">
                            <table class="documents-table management-table">
                                <thead>
                                    <tr>
                                        <th>Registration</th>
                                        <th>VIN</th>
                                        <th>Vehicle</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vehicles['items'] as $vehicle): ?>
                                        <tr>
                                            <td><strong><?php echo e($vehicle['registration_number']); ?></strong></td>
                                            <td><?php echo e($vehicle['vin']); ?></td>
                                            <td><?php echo e($vehicle['make'] . ' ' . $vehicle['model']); ?></td>
                                            <td>
                                                <span class="status-pill status-<?php echo e($vehicle['status']); ?>">
                                                    <?php echo e(vehicleStatusLabel($vehicle['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="inline-actions">
                                                    <a class="secondary-button" href="index.php?edit=<?php echo (int) $vehicle['id']; ?>&page=<?php echo (int) $vehicles['page']; ?>">Edit</a>
                                                    <form method="post" action="index.php?page=<?php echo (int) $vehicles['page']; ?>">
                                                        <input type="hidden" name="action" value="deactivate">
                                                        <input type="hidden" name="id" value="<?php echo (int) $vehicle['id']; ?>">
                                                        <button class="danger-button" type="submit">Deactivate</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <nav class="pagination" aria-label="Vehicle pages">
                            <?php for ($i = 1; $i <= $vehicles['total_pages']; $i++): ?>
                                <a href="index.php?page=<?php echo $i; ?>" <?php echo $i === $vehicles['page'] ? 'aria-current="page"' : ''; ?>>
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </nav>
                    <?php else: ?>
                        <p class="empty-state">No vehicles found.</p>
                    <?php endif; ?>
                </section>
            </div>
        </section>
    </main>
</body>
</html>
