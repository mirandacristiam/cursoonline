<?php
// /cursoonline/admin/includes/footer.php
// ============================================================
// Pie de página del Panel Administrativo — EduTech Academy
// ============================================================
?>
            <footer class="admin-footer">
                &copy; <?= date('Y') ?> <strong>EduTech Academy</strong> &nbsp;|&nbsp;
                Panel Administrativo v<?= defined('APP_VERSION') ? APP_VERSION : '1.0.0' ?> &nbsp;|&nbsp;
                <span class="text-danger fw-bold"><i class="fas fa-shield-alt me-1"></i>Acceso Restringido</span>
            </footer>
        </main><!-- /.admin-body -->
    </div><!-- /.admin-main -->
</div><!-- /.admin-wrapper -->

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- Admin JS Global -->
    <script src="<?= $prefix ?>assets/js/admin.js?v=<?= filemtime(__DIR__ . '/../assets/js/admin.js') ?>"></script>
    <?php if (isset($page_script)): ?>
        <script src="<?= $page_script ?>?v=<?= filemtime(__DIR__ . '/../' . $page_script) ?>"></script>
    <?php endif; ?>
</body>
</html>
