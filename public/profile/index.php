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
$submittedAction = '';
$user = [
    'username' => '',
    'full_name' => '',
    'email' => '',
    'password_hash' => '',
];
$emailForm = [
    'email' => '',
];

try {
    $db = getDbConnection();
    $stmt = $db->prepare(
        'SELECT id, username, full_name, email, password_hash
         FROM users
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => (int) $_SESSION['user_id']]);
    $currentUser = $stmt->fetch();

    if (!$currentUser) {
        throw new RuntimeException('Contul autentificat nu a fost gasit.');
    }

    $user = array_merge($user, $currentUser);
    $emailForm['email'] = $user['email'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $submittedAction = $_POST['action'] ?? '';

        if ($submittedAction === 'update_email') {
            $emailForm['email'] = cleanText($_POST['email'] ?? '', 150);

            if (!filter_var($emailForm['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Introdu o adresa de email valida.';
            }

            if (!$errors) {
                $stmt = $db->prepare(
                    'SELECT id
                     FROM users
                     WHERE email = :email
                       AND id <> :id
                     LIMIT 1'
                );
                $stmt->execute([
                    'email' => $emailForm['email'],
                    'id' => (int) $_SESSION['user_id'],
                ]);

                if ($stmt->fetch()) {
                    $errors[] = 'Aceasta adresa de email este deja folosita.';
                }
            }

            if (!$errors) {
                $stmt = $db->prepare(
                    'UPDATE users
                     SET email = :email
                     WHERE id = :id'
                );
                $stmt->execute([
                    'email' => $emailForm['email'],
                    'id' => (int) $_SESSION['user_id'],
                ]);

                $_SESSION['success'] = 'Email actualizat.';
                header('Location: index.php');
                exit;
            }
        } elseif ($submittedAction === 'update_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
                $errors[] = 'Completeaza toate campurile pentru schimbarea parolei.';
            } elseif (!password_verify($currentPassword, $user['password_hash'])) {
                $errors[] = 'Parola curenta este incorecta.';
            } elseif (strlen($newPassword) < 6) {
                $errors[] = 'Parola noua trebuie sa aiba minimum 6 caractere.';
            } elseif ($newPassword !== $confirmPassword) {
                $errors[] = 'Parolele noi nu se potrivesc.';
            }

            if (!$errors) {
                $stmt = $db->prepare(
                    'UPDATE users
                     SET password_hash = :password_hash
                     WHERE id = :id'
                );
                $stmt->execute([
                    'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                    'id' => (int) $_SESSION['user_id'],
                ]);

                $_SESSION['success'] = 'Parola actualizata.';
                header('Location: index.php');
                exit;
            }
        } else {
            $errors[] = 'Actiune invalida.';
        }
    }

    $_SESSION['username'] = $user['username'];
    $_SESSION['user_name'] = $user['full_name'] ?: $user['username'];
} catch (PDOException $e) {
    appLog('Profile page database error', [
        'user_id' => $_SESSION['user_id'],
        'code' => $e->getCode(),
        'message' => $e->getMessage(),
    ]);

    $systemError = 'Profilul nu a putut fi incarcat. Verifica baza de date.';
} catch (RuntimeException $e) {
    $systemError = $e->getMessage();
}

$pageTitle = 'Profil';
$pageKicker = 'Cont utilizator';
$activePage = 'profile';
$userName = $user['full_name'] ?: ($user['username'] ?: 'Utilizator');

require __DIR__ . '/../includes/header.php';
?>
        <section class="profile-shell">
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

            <?php if (!$systemError): ?>
            <div class="profile-grid">
                <section class="page-panel module-panel">
                    <div class="section-title-row">
                        <h2>Date cont</h2>
                        <span>Cont activ</span>
                    </div>

                    <dl class="profile-list">
                        <div>
                            <dt>Nume</dt>
                            <dd><?php echo e($userName); ?></dd>
                        </div>
                        <div>
                            <dt>Username</dt>
                            <dd><?php echo e($user['username']); ?></dd>
                        </div>
                        <div>
                            <dt>Email</dt>
                            <dd><?php echo e($user['email']); ?></dd>
                        </div>
                    </dl>
                </section>

                <div class="profile-actions-stack">
                    <section class="page-panel module-panel">
                        <div class="section-title-row">
                            <h2>Editeaza email</h2>
                        </div>

                        <form class="management-form" method="post" action="index.php">
                            <input type="hidden" name="action" value="update_email">

                            <div class="form-row">
                                <label for="email">Email</label>
                                <input id="email" name="email" type="email" autocomplete="email" value="<?php echo e($emailForm['email']); ?>" required>
                            </div>

                            <div class="form-actions">
                                <button class="primary-button compact-button" type="submit">Salveaza</button>
                            </div>
                        </form>
                    </section>

                    <section class="page-panel module-panel">
                        <div class="section-title-row">
                            <h2>Schimba parola</h2>
                        </div>

                        <form class="management-form" method="post" action="index.php">
                            <input type="hidden" name="action" value="update_password">

                            <div class="form-row">
                                <label for="current_password">Parola curenta</label>
                                <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                            </div>

                            <div class="form-row grid-2">
                                <div class="form-row">
                                    <label for="new_password">Parola noua</label>
                                    <input id="new_password" name="new_password" type="password" autocomplete="new-password" minlength="6" required>
                                </div>
                                <div class="form-row">
                                    <label for="confirm_password">Confirma parola</label>
                                    <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" minlength="6" required>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button class="primary-button compact-button" type="submit">Actualizeaza</button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
            <?php endif; ?>
        </section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
