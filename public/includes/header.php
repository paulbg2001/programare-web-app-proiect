<?php

$pageTitle = $pageTitle ?? 'Car Management';
$pageKicker = $pageKicker ?? 'Aplicatie flota';
$activePage = $activePage ?? '';
$userName = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Utilizator';
$safePageTitle = htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8');
$safePageKicker = htmlspecialchars($pageKicker, ENT_QUOTES, 'UTF-8');
$safeUserName = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');
$initials = strtoupper(substr(trim($userName), 0, 1) ?: 'U');

$navItems = [
    ['id' => 'dashboard', 'label' => 'Dashboard', 'href' => '/dashboard.php', 'mark' => 'D'],
    ['id' => 'vehicles', 'label' => 'Vehicule', 'href' => '/vehicles/index.php', 'mark' => 'V'],
    ['id' => 'drivers', 'label' => 'Soferi', 'href' => '/drivers/index.php', 'mark' => 'S'],
    ['id' => 'assignments', 'label' => 'Alocari', 'href' => '/assignments/index.php', 'mark' => 'A'],
    ['id' => 'documents', 'label' => 'Documente', 'href' => '/documents/index.php', 'mark' => 'Doc'],
    ['id' => 'maintenance', 'label' => 'Mentenanta', 'href' => '/maintenance/index.php', 'mark' => 'M'],
    ['id' => 'tires', 'label' => 'Anvelope', 'href' => '/tires/index.php', 'mark' => 'T'],
    ['id' => 'profile', 'label' => 'Profil', 'href' => '/profile/index.php', 'mark' => 'P'],
];
?>
<!doctype html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $safePageTitle; ?> | Car Management</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <div class="app-layout">
        <aside class="app-sidebar" aria-label="Navigatie principala">
            <a class="sidebar-brand" href="/dashboard.php">
                <span class="brand-mark">CM</span>
                <span>
                    <strong>Car Management</strong>
                    <small>Fleet workspace</small>
                </span>
            </a>

            <nav class="sidebar-nav">
                <?php foreach ($navItems as $item): ?>
                    <a href="<?php echo htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $activePage === $item['id'] ? 'aria-current="page"' : ''; ?>>
                        <span class="nav-mark"><?php echo htmlspecialchars($item['mark'], ENT_QUOTES, 'UTF-8'); ?></span>
                        <span><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <div class="app-content">
            <header class="app-header">
                <div class="page-heading">
                    <p class="eyebrow"><?php echo $safePageKicker; ?></p>
                    <h1><?php echo $safePageTitle; ?></h1>
                </div>

                <div class="user-area">
                    <span class="user-initials"><?php echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?></span>
                    <div class="user-copy">
                        <strong><?php echo $safeUserName; ?></strong>
                        <span>Conectat</span>
                    </div>
                    <a class="btn btn-outline-danger btn-sm" href="/auth/logout.php">Logout</a>
                </div>
            </header>

            <main class="app-main">
