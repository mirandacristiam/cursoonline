        </main>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Student Custom JS -->
<script src="assets/js/student.js?v=<?= filemtime(__DIR__ . '/../assets/js/student.js') ?>"></script>
<?php if (isset($page_script)): ?>
<script src="<?= $page_script ?>"></script>
<?php endif; ?>

</body>
</html>
