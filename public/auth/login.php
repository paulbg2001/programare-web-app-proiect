<?php
session_start();

require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/logger.php';

$error = '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
    } else {
        try {
            $db = getDbConnection();
            $stmt = $db->prepare('SELECT id, full_name, email, password_hash FROM users WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                header('Location: ../dashboard.php');
                exit;
            }

            $error = 'Invalid email or password.';
        } catch (PDOException $e) {
            appLog('Login database error', [
                'email' => $email,
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ]);

            $error = 'Database connection failed. Check your MySQL settings.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Car Management</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <main class="auth-page">
        <section class="auth-card" aria-labelledby="login-title">
            <h1 class="auth-title" id="login-title">Welcome Back !</h1>

            <?php if ($error): ?>
                <p class="message error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <?php if ($success): ?>
                <p class="message success"><?php echo htmlspecialchars($success); ?></p>
            <?php endif; ?>

            <form class="auth-form" method="post" action="login.php" data-auth-form novalidate>
                <div class="form-row">
                    <label for="email">Email Address</label>
                    <input id="email" name="email" type="email" autocomplete="email" required>
                    <span class="field-error" aria-live="polite"></span>
                </div>

                <div class="form-row">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required>
                    <span class="field-error" aria-live="polite"></span>
                </div>

                <p class="form-help"><a href="#">Forgot Password?</a></p>

                <div class="button-row">
                    <button class="primary-button" type="submit">Login</button>
                </div>
            </form>

            <p class="auth-switch">Are you a new member? <a href="register.php">Sign Up</a></p>
        </section>
    </main>

    <script src="../assets/js/auth.js"></script>
</body>
</html>
