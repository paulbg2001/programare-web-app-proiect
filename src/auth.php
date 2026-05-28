<?php

function requireAuth(string $loginPath = '/auth/login.php'): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . $loginPath);
        exit;
    }
}

