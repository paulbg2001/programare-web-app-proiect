<?php
session_start();

require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/logger.php';
require_once __DIR__ . '/../../src/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_SESSION['user_id'])) {
    if (sessionUserExists()) {
        header('Location: ../dashboard.php');
        exit;
    }

    clearAuthSession();
    session_start();
}

$mode = ($_GET['mode'] ?? '') === 'register' ? 'register' : 'login';
$error = '';
$success = $_SESSION['success'] ?? '';
$values = [
    'username' => '',
    'email' => '',
];
unset($_SESSION['success']);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function cleanAuthText(string $value, int $maxLength): string
{
    return substr(trim(strip_tags($value)), 0, $maxLength);
}

function normalizeUsername(string $value): string
{
    return strtolower(cleanAuthText($value, 60));
}

function isValidUsername(string $username): bool
{
    return (bool) preg_match('/^[a-z0-9_]{3,40}$/', $username);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';
    $mode = $action === 'register' ? 'register' : 'login';

    if ($action === 'login') {
        $username = normalizeUsername($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $values['username'] = $username;

        if ($username === '' || $password === '') {
            $error = 'Username and password are required.';
        } else {
            try {
                $db = getDbConnection();
                $stmt = $db->prepare(
                    'SELECT id, username, full_name, email, password_hash
                     FROM users
                     WHERE username = :username OR email = :email
                     LIMIT 1'
                );
                $stmt->execute([
                    'username' => $username,
                    'email' => $username,
                ]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password_hash'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_name'] = $user['full_name'] ?: $user['username'];
                    header('Location: ../dashboard.php');
                    exit;
                }

                $error = 'Invalid username or password.';
            } catch (PDOException $e) {
                appLog('Login database error', [
                    'username' => $username,
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                ]);

                $error = 'Login failed. Check that the database schema is up to date.';
            }
        }
    }

    if ($action === 'register') {
        $username = normalizeUsername($_POST['username'] ?? '');
        $email = cleanAuthText($_POST['email'] ?? '', 150);
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $values['username'] = $username;
        $values['email'] = $email;

        if (!isValidUsername($username)) {
            $error = 'Username must have 3-40 lowercase letters, numbers or underscores.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must have at least 6 characters.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            try {
                $db = getDbConnection();
                $stmt = $db->prepare(
                    'INSERT INTO users (username, full_name, email, password_hash)
                     VALUES (:username, :full_name, :email, :password_hash)'
                );
                $stmt->execute([
                    'username' => $username,
                    'full_name' => $username,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                ]);

                $_SESSION['success'] = 'Account created. You can log in now.';
                header('Location: login.php');
                exit;
            } catch (PDOException $e) {
                appLog('Registration failed', [
                    'username' => $username,
                    'email' => $email,
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                ]);

                if ($e->getCode() === '23000') {
                    $error = 'Username or email is already used.';
                } else {
                    $error = 'Registration failed. Check that the database schema is up to date.';
                }
            }
        }
    }
}
?>
<!doctype html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Autentificare | Car Management</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body class="auth-body">
    <main class="auth-page">
        <section class="auth-card" aria-labelledby="auth-title">
            <header class="auth-header">
                <span class="brand-mark">CM</span>
                <div>
                    <p class="eyebrow">Car Management</p>
                    <h1 class="auth-title" id="auth-title">Autentificare</h1>
                </div>
            </header>

            <nav class="auth-tabs" aria-label="Schimba formularul">
                <a href="login.php" <?php echo $mode === 'login' ? 'aria-current="page"' : ''; ?>>Login</a>
                <a href="login.php?mode=register" <?php echo $mode === 'register' ? 'aria-current="page"' : ''; ?>>Register</a>
            </nav>

            <?php if ($error): ?>
                <p class="message error"><?php echo e($error); ?></p>
            <?php endif; ?>

            <?php if ($success): ?>
                <p class="message success"><?php echo e($success); ?></p>
            <?php endif; ?>

            <div class="auth-panel <?php echo $mode === 'login' ? 'is-active' : ''; ?>" <?php echo $mode === 'login' ? '' : 'aria-hidden="true"'; ?>>
                <form class="auth-form" method="post" action="login.php" data-auth-form novalidate>
                    <input type="hidden" name="action" value="login">

                    <div class="form-row">
                        <label for="login_username">Username</label>
                        <input id="login_username" name="username" type="text" autocomplete="username" placeholder="ex: admin" value="<?php echo e($mode === 'login' ? $values['username'] : ''); ?>" required>
                        <span class="field-error" aria-live="polite"></span>
                    </div>

                    <div class="form-row">
                        <label for="login_password">Password</label>
                        <input id="login_password" name="password" type="password" autocomplete="current-password" placeholder="Parola contului" required>
                        <span class="field-error" aria-live="polite"></span>
                    </div>

                    <div class="button-row">
                        <button class="primary-button" type="submit">Login</button>
                    </div>
                </form>
            </div>

            <div class="auth-panel <?php echo $mode === 'register' ? 'is-active' : ''; ?>" <?php echo $mode === 'register' ? '' : 'aria-hidden="true"'; ?>>
                <form class="auth-form" method="post" action="login.php?mode=register" data-auth-form novalidate>
                    <input type="hidden" name="action" value="register">

                    <div class="form-row">
                        <label for="register_username">Username</label>
                        <input id="register_username" name="username" type="text" autocomplete="username" placeholder="ex: popescu_ion" value="<?php echo e($mode === 'register' ? $values['username'] : ''); ?>" required>
                        <span class="field-error" aria-live="polite"></span>
                    </div>

                    <div class="form-row">
                        <label for="register_email">Email</label>
                        <input id="register_email" name="email" type="email" autocomplete="email" placeholder="nume@example.com" value="<?php echo e($mode === 'register' ? $values['email'] : ''); ?>" required>
                        <span class="field-error" aria-live="polite"></span>
                    </div>

                    <div class="form-row">
                        <label for="register_password">Password</label>
                        <input id="register_password" name="password" type="password" autocomplete="new-password" placeholder="Minimum 6 caractere" required>
                        <span class="field-error" aria-live="polite"></span>
                    </div>

                    <div class="form-row">
                        <label for="confirm_password">Confirm Password</label>
                        <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" placeholder="Repeta parola" required>
                        <span class="field-error" aria-live="polite"></span>
                    </div>

                    <div class="button-row">
                        <button class="primary-button" type="submit">Register</button>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <script src="../assets/js/auth.js"></script>
</body>
</html>
