<?php

require_once __DIR__ . '/../../src/auth.php';

requireAuth('/auth/login.php');

$pageTitle = 'Alocari';
$pageKicker = 'Gestiune flota';
$activePage = 'assignments';

require __DIR__ . '/../includes/header.php';
?>
        <section class="page-panel module-panel">
            <div class="section-title-row">
                <h2>Alocari</h2>
                <span>0 afisate</span>
            </div>
            <p class="empty-state">Nu exista inregistrari disponibile.</p>
        </section>
<?php require __DIR__ . '/../includes/footer.php'; ?>

