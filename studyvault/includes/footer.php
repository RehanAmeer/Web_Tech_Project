<?php
$js_file = __DIR__ . '/../assets/js/script.js';
?>
</main>
<footer class="site-footer">
    <div class="footer-inner">
        <span><?= e(APP_NAME) ?>, a notes and resource exchange for students and teachers.</span>
        <span>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?></span>
    </div>
</footer>
</div>
<?php if (is_file($js_file)): ?>
<script src="<?= e(asset('js/script.js')) ?>?v=<?= (int) filemtime($js_file) ?>"></script>
<?php endif; ?>
</body>
</html>
