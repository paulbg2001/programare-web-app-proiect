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

function normalizeTireSize(string $value): string
{
    $value = strtoupper(cleanText($value, 40));
    $value = preg_replace('/\s+/', '', $value) ?? '';

    if (preg_match('/^(\d+)\/(\d+)R(\d+)$/', $value, $matches)) {
        return $matches[1] . '/' . $matches[2] . ' R' . $matches[3];
    }

    return $value;
}

function tireStatus(string $date): string
{
    if ($date < date('Y-m-d')) {
        return 'expired';
    }

    if ($date <= date('Y-m-d', strtotime('+30 days'))) {
        return 'warning';
    }

    return 'valid';
}

function tireStatusLabel(string $status): string
{
    return [
        'expired' => 'Trebuie schimbate',
        'warning' => 'Schimbare curand',
        'valid' => 'Ok',
    ][$status] ?? 'Ok';
}

$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

$errors = [];
$systemError = '';
$formData = [
    'vehicle_id' => '',
    'tire_type' => 'Vara',
    'brand' => '',
    'size' => '',
    'installed_date' => date('Y-m-d'),
    'change_date' => '',
];
$vehicles = [];
$tires = [];

try {
    $db = getDbConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $formData = [
            'vehicle_id' => (int) ($_POST['vehicle_id'] ?? 0),
            'tire_type' => cleanText($_POST['tire_type'] ?? '', 40),
            'brand' => cleanText($_POST['brand'] ?? '', 80),
            'size' => normalizeTireSize($_POST['size'] ?? ''),
            'installed_date' => cleanText($_POST['installed_date'] ?? '', 10),
            'change_date' => cleanText($_POST['change_date'] ?? '', 10),
        ];

        if ($formData['vehicle_id'] <= 0) {
            $errors[] = 'Selecteaza un vehicul.';
        }

        if ($formData['tire_type'] === '') {
            $errors[] = 'Tipul anvelopelor este obligatoriu.';
        }

        if ($formData['size'] === '') {
            $errors[] = 'Dimensiunea anvelopelor este obligatorie.';
        } elseif (!preg_match('/^\d+\/\d+ R\d+$/', $formData['size'])) {
            $errors[] = 'Dimensiunea trebuie sa fie in formatul 205/55 R16.';
        }

        if ($formData['installed_date'] === '') {
            $errors[] = 'Data montarii este obligatorie.';
        }

        if ($formData['change_date'] === '') {
            $errors[] = 'Data schimbarii este obligatorie.';
        }

        if ($formData['change_date'] !== '' && $formData['installed_date'] !== '' && $formData['change_date'] < $formData['installed_date']) {
            $errors[] = 'Data schimbarii trebuie sa fie dupa montare.';
        }

        if (!$errors) {
            $stmt = $db->prepare(
                'INSERT INTO vehicle_tires (vehicle_id, tire_type, brand, size, installed_date, change_date)
                 VALUES (:vehicle_id, :tire_type, :brand, :size, :installed_date, :change_date)'
            );
            $stmt->execute([
                'vehicle_id' => $formData['vehicle_id'],
                'tire_type' => $formData['tire_type'],
                'brand' => $formData['brand'] === '' ? null : $formData['brand'],
                'size' => $formData['size'] === '' ? null : $formData['size'],
                'installed_date' => $formData['installed_date'],
                'change_date' => $formData['change_date'],
            ]);

            $_SESSION['success'] = 'Anvelope adaugate.';
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

    $tires = $db->query(
        "SELECT vt.id, vt.tire_type, vt.brand, vt.size, vt.installed_date, vt.change_date,
                v.registration_number, v.make, v.model
         FROM vehicle_tires vt
         INNER JOIN vehicles v ON v.id = vt.vehicle_id
         ORDER BY vt.change_date ASC, vt.id DESC"
    )->fetchAll();
} catch (PDOException $e) {
    appLog('Tires page database error', [
        'user_id' => $_SESSION['user_id'],
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
    ]);

    $systemError = 'Anvelopele nu au putut fi incarcate. Ruleaza migrate.php.';
}

$pageTitle = 'Anvelope';
$pageKicker = 'Gestiune flota';
$activePage = 'tires';

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
                        <h2>Adauga Anvelope</h2>
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
                            <label for="tire_type">Tip</label>
                            <select id="tire_type" name="tire_type" required>
                                <?php foreach (['Vara', 'Iarna', 'All Season'] as $type): ?>
                                    <option value="<?php echo e($type); ?>" <?php echo $formData['tire_type'] === $type ? 'selected' : ''; ?>>
                                        <?php echo e($type); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-row grid-2">
                            <div class="form-row">
                                <label for="brand">Marca</label>
                                <input id="brand" name="brand" type="text" value="<?php echo e($formData['brand']); ?>" placeholder="ex: Michelin">
                            </div>
                            <div class="form-row">
                                <label for="size">Dimensiune</label>
                                <input id="size" name="size" type="text" value="<?php echo e($formData['size']); ?>" placeholder="ex: 205/55 R16" pattern="[0-9]+/[0-9]+ R[0-9]+" title="Format: 205/55 R16" required>
                            </div>
                        </div>

                        <div class="form-row grid-2">
                            <div class="form-row">
                                <label for="installed_date">Montate la</label>
                                <input id="installed_date" name="installed_date" type="date" value="<?php echo e($formData['installed_date']); ?>" required>
                            </div>
                            <div class="form-row">
                                <label for="change_date">Schimbare la</label>
                                <input id="change_date" name="change_date" type="date" value="<?php echo e($formData['change_date']); ?>" required>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button class="primary-button compact-button" type="submit">Adauga</button>
                        </div>
                    </form>
                </section>

                <section class="page-panel management-list-panel">
                    <div class="section-title-row">
                        <h2>Lista Anvelope</h2>
                        <span><?php echo count($tires); ?> afisate</span>
                    </div>

                    <?php if ($tires): ?>
                        <div class="documents-table-wrap">
                            <table class="documents-table management-table">
                                <thead>
                                    <tr>
                                        <th>Vehicul</th>
                                        <th>Anvelope</th>
                                        <th>Montate</th>
                                        <th>Schimbare</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tires as $tire): ?>
                                        <?php $status = tireStatus($tire['change_date']); ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo e($tire['registration_number']); ?></strong>
                                                <span class="vehicle-description"><?php echo e($tire['make'] . ' ' . $tire['model']); ?></span>
                                            </td>
                                            <td>
                                                <?php echo e($tire['tire_type']); ?>
                                                <span class="vehicle-description"><?php echo e(trim(($tire['brand'] ?: '') . ' ' . ($tire['size'] ?: '')) ?: '-'); ?></span>
                                            </td>
                                            <td><?php echo e(date('d.m.Y', strtotime($tire['installed_date']))); ?></td>
                                            <td><?php echo e(date('d.m.Y', strtotime($tire['change_date']))); ?></td>
                                            <td>
                                                <span class="status-pill status-<?php echo e($status); ?>">
                                                    <?php echo e(tireStatusLabel($status)); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="empty-state">Nu exista anvelope adaugate.</p>
                    <?php endif; ?>
                </section>
            </div>
        </section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
