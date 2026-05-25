<?php
session_start();

require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/logger.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($fullName === '' || $email === '' || $password === '') {
        $error = 'All fields are required.';
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
                'INSERT INTO users (full_name, email, password_hash)
                 VALUES (:full_name, :email, :password_hash)'
            );
            $stmt->execute([
                'full_name' => $fullName,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);

            $_SESSION['success'] = 'Account created. You can log in now.';
            header('Location: login.php');
            exit;
        } catch (PDOException $e) {
            appLog('Registration failed', [
                'email' => $email,
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ]);

            if ($e->getCode() === '23000') {
                $error = 'An account with this email already exists.';
            } else {
                $error = 'Registration failed. Check your MySQL settings.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register | Car Management</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <main class="auth-page">
        <section class="auth-card register-card" aria-labelledby="register-title">
            <h1 class="auth-title" id="register-title">Create Account</h1>

            <?php if ($error): ?>
                <p class="message error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <form class="auth-form" method="post" action="register.php" data-auth-form novalidate>
                <div class="form-row">
                    <label for="full_name">Full Name</label>
                    <input id="full_name" name="full_name" type="text" autocomplete="name" required>
                    <span class="field-error" aria-live="polite"></span>
                </div>

                <div class="form-row">
                    <label for="email">Email Address</label>
                    <input id="email" name="email" type="email" autocomplete="email" required>
                    <span class="field-error" aria-live="polite"></span>
                </div>

                <div class="form-row">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required>
                    <span class="field-error" aria-live="polite"></span>
                </div>

                <div class="form-row">
                    <label for="confirm_password">Confirm Password</label>
                    <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required>
                    <span class="field-error" aria-live="polite"></span>
                </div>

                <div class="button-row">
                    <button class="primary-button" type="submit">Sign Up</button>
                </div>
            </form>

            <p class="auth-switch">Already have an account? <a href="login.php">Login</a></p>
        </section>
    </main>

    <script src="../assets/js/auth.js"></script>
</body>
</html>
