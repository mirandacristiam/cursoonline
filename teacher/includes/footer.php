<?php
// /cursoonline/teacher/includes/footer.php
// ============================================================
// Pie de página del Panel del Profesor — EduTech Academy
// ============================================================
?>
            </main><!-- /.teacher-body -->
        </div><!-- /.teacher-main -->
    </div><!-- /.teacher-wrapper -->

    <!-- Footer -->
    <footer class="teacher-footer">
        &copy; <?= date('Y') ?> <strong>EduTech Academy</strong> — Portal Docente &nbsp;|&nbsp;
        <span>v<?= defined('APP_VERSION') ? APP_VERSION : '1.0.0' ?></span>
    </footer>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js (para gráficas en dashboard) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

    <!-- JS Global del Profesor (Separado) -->
    <script src="assets/js/teacher.js"></script>

    <!-- JS específico de la página (definido en la vista) -->
    <?php if (isset($page_script)): ?>
        <script src="<?= $page_script ?>"></script>
    <?php endif; ?>

</body>
</html>
<?php
/*
 * ============================================================
 * RESUMEN DEL ARCHIVO: /cursoonline/teacher/includes/footer.php
 * ============================================================
 * Cierra las etiquetas del layout del panel docente.
 * Carga jQuery, Bootstrap JS, Chart.js y teacher.js.
 * ============================================================
 */
?>
