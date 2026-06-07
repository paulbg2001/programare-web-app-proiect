<?php

require_once __DIR__ . '/db.php';

function clearAuthSession(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

function sessionUserExists(): bool
{
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    try {
        $db = getDbConnection();
        $stmt = $db->prepare(
            'SELECT id, username, full_name
             FROM users
             WHERE id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => (int) $_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user) {
            return false;
        }

        $_SESSION['username'] = $user['username'];
        $_SESSION['user_name'] = $user['full_name'] ?: $user['username'];

        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function requireAuth(string $loginPath = '/auth/login.php'): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!sessionUserExists()) {
        clearAuthSession();
        header('Location: ' . $loginPath);
        exit;
    }
}
