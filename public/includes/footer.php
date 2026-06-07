            </main>
        </div>
    </div>
    <?php if (isset($extraScripts)): ?>
        <?php foreach ($extraScripts as $script): ?>
            <script src="<?php echo htmlspecialchars($script, ENT_QUOTES, 'UTF-8'); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>

