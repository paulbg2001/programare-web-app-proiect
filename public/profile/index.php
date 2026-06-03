<?php

require_once __DIR__ . '/../../src/auth.php';

requireAuth('/auth/login.php');

$pageTitle = 'Profil';
$pageKicker = 'Cont utilizator';
$activePage = 'profile';
$userName = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Utilizator';

require __DIR__ . '/../includes/header.php';
?>
        <section class="page-panel module-panel">
            <div class="section-title-row">
                <h2>Profil</h2>
                <span>Cont activ</span>
            </div>
            <dl class="profile-list">
                <div>
                    <dt>Nume</dt>
                    <dd><?php echo htmlspecialchars($userName, ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
            </dl>
        </section>
<?php require __DIR__ . '/../includes/footer.php'; ?>

