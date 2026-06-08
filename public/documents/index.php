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

function documentStatus(string $date): string
{
    if ($date < date('Y-m-d')) {
        return 'expired';
    }

    if ($date <= date('Y-m-d', strtotime('+30 days'))) {
        return 'warning';
    }

    return 'valid';
}

function documentStatusLabel(string $status): string
{
    return [
        'expired' => 'Expirat',
        'warning' => 'Expira curand',
        'valid' => 'Valabil',
    ][$status] ?? 'Valabil';
}

$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

$errors = [];
$systemError = '';
$formData = [
    'vehicle_id' => '',
    'document_type' => 'ITP',
    'expiration_date' => '',
];
$vehicles = [];
$documents = [];

try {
    $db = getDbConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? 'create';

        if ($action === 'delete') {
            $documentId = (int) ($_POST['id'] ?? 0);

            if ($documentId <= 0) {
                $errors[] = 'Document invalid.';
            } else {
                $stmt = $db->prepare('DELETE FROM vehicle_documents WHERE id = :id');
                $stmt->execute(['id' => $documentId]);

                $_SESSION['success'] = 'Document sters.';
                header('Location: index.php');
                exit;
            }
        }

        if ($action === 'create') {
            $formData = [
                'vehicle_id' => (int) ($_POST['vehicle_id'] ?? 0),
                'document_type' => cleanText($_POST['document_type'] ?? '', 100),
                'expiration_date' => cleanText($_POST['expiration_date'] ?? '', 10),
            ];

            if ($formData['vehicle_id'] <= 0) {
                $errors[] = 'Selecteaza un vehicul.';
            }

            if ($formData['document_type'] === '') {
                $errors[] = 'Tipul documentului este obligatoriu.';
            }

            if ($formData['expiration_date'] === '') {
                $errors[] = 'Data expirarii este obligatorie.';
            }

            if (!$errors) {
                $stmt = $db->prepare(
                    'SELECT id
                     FROM vehicle_documents
                     WHERE vehicle_id = :vehicle_id
                       AND document_type = :document_type
                     LIMIT 1'
                );
                $stmt->execute([
                    'vehicle_id' => $formData['vehicle_id'],
                    'document_type' => $formData['document_type'],
                ]);

                if ($stmt->fetch()) {
                    $errors[] = 'Acest vehicul are deja un document de tip ' . $formData['document_type'] . '. Sterge documentul existent inainte sa adaugi altul.';
                }
            }

            if (!$errors) {
                $stmt = $db->prepare(
                    'INSERT INTO vehicle_documents (vehicle_id, document_type, expiration_date)
                     VALUES (:vehicle_id, :document_type, :expiration_date)'
                );
                $stmt->execute($formData);

                $_SESSION['success'] = 'Document adaugat.';
                header('Location: index.php');
                exit;
            }
        }

        if (!in_array($action, ['create', 'delete'], true)) {
            $errors[] = 'Actiune invalida.';
        }
    }

    $vehicles = $db->query(
        "SELECT id, registration_number, make, model
         FROM vehicles
         WHERE status <> 'inactive'
         ORDER BY registration_number"
    )->fetchAll();

    $documents = $db->query(
        "SELECT vd.id, vd.document_type, vd.expiration_date,
                v.registration_number, v.make, v.model
         FROM vehicle_documents vd
         INNER JOIN vehicles v ON v.id = vd.vehicle_id
         ORDER BY vd.expiration_date ASC, vd.id DESC"
    )->fetchAll();
} catch (PDOException $e) {
    appLog('Documents page database error', [
        'user_id' => $_SESSION['user_id'],
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
    ]);

    $systemError = 'Documentele nu au putut fi incarcate. Ruleaza migrate.php.';
}

$pageTitle = 'Documente';
$pageKicker = 'Gestiune flota';
$activePage = 'documents';

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
                        <h2>Adauga Document</h2>
                    </div>

                    <form class="management-form" method="post" action="index.php">
                        <input type="hidden" name="action" value="create">

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
                            <label for="document_type">Tip Document</label>
                            <select id="document_type" name="document_type" required>
                                <?php foreach (['ITP', 'RCA', 'CASCO', 'Rovinieta', 'Asigurare'] as $type): ?>
                                    <option value="<?php echo e($type); ?>" <?php echo $formData['document_type'] === $type ? 'selected' : ''; ?>>
                                        <?php echo e($type); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-row">
                            <label for="expiration_date">Data Expirarii</label>
                            <input id="expiration_date" name="expiration_date" type="date" value="<?php echo e($formData['expiration_date']); ?>" required>
                        </div>

                        <div class="form-actions">
                            <button class="primary-button compact-button" type="submit">Adauga</button>
                        </div>
                    </form>
                </section>

                <section class="page-panel management-list-panel">
                    <div class="section-title-row">
                        <h2>Lista Documente</h2>
                        <span><?php echo count($documents); ?> afisate</span>
                    </div>

                    <?php if ($documents): ?>
                        <div class="documents-table-wrap">
                            <table class="documents-table management-table">
                                <thead>
                                    <tr>
                                        <th>Vehicul</th>
                                        <th>Document</th>
                                        <th>Expira</th>
                                        <th>Status</th>
                                        <th>Actiuni</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($documents as $document): ?>
                                        <?php $status = documentStatus($document['expiration_date']); ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo e($document['registration_number']); ?></strong>
                                                <span class="vehicle-description"><?php echo e($document['make'] . ' ' . $document['model']); ?></span>
                                            </td>
                                            <td><?php echo e($document['document_type']); ?></td>
                                            <td><?php echo e(date('d.m.Y', strtotime($document['expiration_date']))); ?></td>
                                            <td>
                                                <span class="status-pill status-<?php echo e($status); ?>">
                                                    <?php echo e(documentStatusLabel($status)); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="inline-actions">
                                                    <form method="post" action="index.php" onsubmit="return confirm('Sigur stergi acest document?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?php echo (int) $document['id']; ?>">
                                                        <button class="danger-button" type="submit">Sterge</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="empty-state">Nu exista documente adaugate.</p>
                    <?php endif; ?>
                </section>
            </div>
        </section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
