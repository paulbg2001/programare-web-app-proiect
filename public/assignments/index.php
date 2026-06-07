<?php
require_once __DIR__ . '/../../src/auth.php';
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/logger.php';

requireAuth('/auth/login.php');

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function cleanText(string $value, int $maxLength): string
{
    return substr(trim(strip_tags($value)), 0, $maxLength);
}

$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

$errors = [];
$systemError = '';
$formData = [
    'vehicle_id' => '',
    'driver_id' => '',
    'start_date' => date('Y-m-d'),
    'end_date' => '',
];
$vehicles = [];
$drivers = [];
$assignments = [];

try {
    $db = getDbConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $formData = [
            'vehicle_id' => (int) ($_POST['vehicle_id'] ?? 0),
            'driver_id' => (int) ($_POST['driver_id'] ?? 0),
            'start_date' => cleanText($_POST['start_date'] ?? '', 10),
            'end_date' => cleanText($_POST['end_date'] ?? '', 10),
        ];

        if ($formData['vehicle_id'] <= 0) {
            $errors[] = 'Selecteaza un vehicul.';
        }

        if ($formData['driver_id'] <= 0) {
            $errors[] = 'Selecteaza un sofer.';
        }

        if ($formData['start_date'] === '') {
            $errors[] = 'Data de inceput este obligatorie.';
        }

        if ($formData['end_date'] !== '' && $formData['end_date'] < $formData['start_date']) {
            $errors[] = 'Data de final trebuie sa fie dupa data de inceput.';
        }

        if (!$errors) {
            $stmt = $db->prepare(
                'INSERT INTO vehicle_assignments (vehicle_id, driver_id, start_date, end_date)
                 VALUES (:vehicle_id, :driver_id, :start_date, :end_date)'
            );
            $stmt->execute([
                'vehicle_id' => $formData['vehicle_id'],
                'driver_id' => $formData['driver_id'],
                'start_date' => $formData['start_date'],
                'end_date' => $formData['end_date'] === '' ? null : $formData['end_date'],
            ]);

            $_SESSION['success'] = 'Alocare adaugata.';
            header('Location: index.php');
            exit;
        }
    }

    $vehicles = $db->query(
        "SELECT id, registration_number, make, model
         FROM vehicles
         WHERE status <> 'inactive'
         ORDER BY registration_number"
    )->fetchAll();

    $drivers = $db->query(
        "SELECT id, full_name, license_number
         FROM drivers
         WHERE status = 'active'
         ORDER BY full_name"
    )->fetchAll();

    $assignments = $db->query(
        "SELECT va.id, va.start_date, va.end_date,
                v.registration_number, v.make, v.model,
                d.full_name, d.license_number
         FROM vehicle_assignments va
         INNER JOIN vehicles v ON v.id = va.vehicle_id
         INNER JOIN drivers d ON d.id = va.driver_id
         ORDER BY va.start_date DESC, va.id DESC"
    )->fetchAll();
} catch (PDOException $e) {
    appLog('Assignments page database error', [
        'user_id' => $_SESSION['user_id'],
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
    ]);

    $systemError = 'Alocarile nu au putut fi incarcate. Ruleaza migrate.php.';
}

$pageTitle = 'Alocari';
$pageKicker = 'Gestiune flota';
$activePage = 'assignments';

require __DIR__ . '/../includes/header.php';
?>
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
                <section class="page-panel management-form-panel">
                    <div class="section-title-row">
                        <h2>Adauga Alocare</h2>
                    </div>

                    <form class="management-form" method="post" action="index.php">
                        <div class="form-row">
                            <label for="vehicle_id">Vehicul</label>
                            <select id="vehicle_id" name="vehicle_id" required>
                                <option value="">Selecteaza...</option>
                                <?php foreach ($vehicles as $vehicle): ?>
                                    <option value="<?php echo (int) $vehicle['id']; ?>" <?php echo (int) $formData['vehicle_id'] === (int) $vehicle['id'] ? 'selected' : ''; ?>>
                                        <?php echo e($vehicle['registration_number'] . ' - ' . $vehicle['make'] . ' ' . $vehicle['model']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-row">
                            <label for="driver_id">Sofer</label>
                            <select id="driver_id" name="driver_id" required>
                                <option value="">Selecteaza...</option>
                                <?php foreach ($drivers as $driver): ?>
                                    <option value="<?php echo (int) $driver['id']; ?>" <?php echo (int) $formData['driver_id'] === (int) $driver['id'] ? 'selected' : ''; ?>>
                                        <?php echo e($driver['full_name'] . ' - ' . $driver['license_number']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-row grid-2">
                            <div class="form-row">
                                <label for="start_date">De la</label>
                                <input id="start_date" name="start_date" type="date" value="<?php echo e($formData['start_date']); ?>" required>
                            </div>
                            <div class="form-row">
                                <label for="end_date">Pana la</label>
                                <input id="end_date" name="end_date" type="date" value="<?php echo e($formData['end_date']); ?>">
                            </div>
                        </div>

                        <div class="form-actions">
                            <button class="primary-button compact-button" type="submit">Adauga</button>
                        </div>
                    </form>
                </section>

                <section class="page-panel management-list-panel">
                    <div class="section-title-row">
                        <h2>Lista Alocari</h2>
                        <span><?php echo count($assignments); ?> afisate</span>
                    </div>

                    <?php if ($assignments): ?>
                        <div class="documents-table-wrap">
                            <table class="documents-table management-table">
                                <thead>
                                    <tr>
                                        <th>Vehicul</th>
                                        <th>Sofer</th>
                                        <th>Perioada</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($assignments as $assignment): ?>
                                        <?php $active = $assignment['start_date'] <= date('Y-m-d') && (!$assignment['end_date'] || $assignment['end_date'] >= date('Y-m-d')); ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo e($assignment['registration_number']); ?></strong>
                                                <span class="vehicle-description"><?php echo e($assignment['make'] . ' ' . $assignment['model']); ?></span>
                                            </td>
                                            <td>
                                                <?php echo e($assignment['full_name']); ?>
                                                <span class="vehicle-description"><?php echo e($assignment['license_number']); ?></span>
                                            </td>
                                            <td>
                                                <?php echo e(date('d.m.Y', strtotime($assignment['start_date']))); ?>
                                                -
                                                <?php echo $assignment['end_date'] ? e(date('d.m.Y', strtotime($assignment['end_date']))) : 'prezent'; ?>
                                            </td>
                                            <td>
                                                <span class="status-pill status-<?php echo $active ? 'active' : 'inactive'; ?>">
                                                    <?php echo $active ? 'Activa' : 'Inactiva'; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="empty-state">Nu exista alocari adaugate.</p>
                    <?php endif; ?>
                </section>
            </div>
        </section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
