            </main>
            <footer class="app-footer" aria-label="Informatii de contact">
                <span>Contact clienti</span>
                <a href="tel:+40722000000">0722 000 000</a>
                <a href="mailto:contact@car-management.ro">contact@car-management.ro</a>
            </footer>
        </div>
    </div>
    <?php if (isset($extraScripts)): ?>
        <?php foreach ($extraScripts as $script): ?>
            <script src="<?php echo htmlspecialchars($script, ENT_QUOTES, 'UTF-8'); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>

