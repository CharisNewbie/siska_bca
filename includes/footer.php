<?php
/**
 * SISKA BCA - Footer Template
 * Include di akhir setiap halaman
 */
?>

    </main>
    <!-- / Page Content -->

</div>
<!-- / siska-main -->

<footer class="siska-footer">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>&copy; <?= date('Y') ?> <?= APP_NAME ?> <?= APP_CABANG ?> &mdash; v<?= APP_VERSION ?></span>
        <span class="text-muted">Session: <?= date('H:i:s') ?> WIB</span>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- SISKA Global JS -->
<script src="assets/js/app.js?v=<?= APP_VERSION ?>"></script>

<?php if (isset($extraScripts)) echo $extraScripts; ?>

</body>
</html>