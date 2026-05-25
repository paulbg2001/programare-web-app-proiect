<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
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
            <a href="../auth/logout.php">Logout</a>
        </header>

        <section class="page-panel">
            <p>Vehicle management page is ready for the next implementation step.</p>
        </section>
    </main>
</body>
</html>

