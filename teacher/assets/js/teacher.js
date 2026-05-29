/* /cursoonline/teacher/assets/js/teacher.js */
/* ============================================================
 * JavaScript del Panel del Profesor — EduTech Academy
 * ============================================================ */

$(document).ready(function () {

    // ----------------------------------------------------------
    // 1. Toggle del Sidebar en móvil
    // ----------------------------------------------------------
    $('#teacherSidebarCollapse').on('click', function () {
        $('.teacher-sidebar').toggleClass('show');
    });

    // Cerrar sidebar al hacer clic fuera (móvil)
    $(document).on('click', function (e) {
        if ($(window).width() < 992) {
            if (!$(e.target).closest('.teacher-sidebar, #teacherSidebarCollapse').length) {
                $('.teacher-sidebar').removeClass('show');
            }
        }
    });

    // ----------------------------------------------------------
    // 2. Logout via AJAX
    // ----------------------------------------------------------
    $('#btnLogout').on('click', function (e) {
        e.preventDefault();
        $.post('../api/auth.php', { accion: 'logout' }, function (res) {
            window.location.href = res.redirect || '../auth/login.php';
        }, 'json').fail(function () {
            window.location.href = '../auth/login.php';
        });
    });

    // ----------------------------------------------------------
    // 3. Animación de números en stat cards al entrar en viewport
    // ----------------------------------------------------------
    function animateNumber(el) {
        const target = parseInt(el.data('target') || el.text(), 10);
        if (isNaN(target)) return;
        let current = 0;
        const step  = Math.max(1, Math.floor(target / 40));
        const timer = setInterval(function () {
            current += step;
            if (current >= target) { current = target; clearInterval(timer); }
            el.text(current.toLocaleString('es-CO'));
        }, 25);
    }

    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                animateNumber($(entry.target));
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    $('.stat-card h3[data-target]').each(function () {
        observer.observe(this);
    });

    // ----------------------------------------------------------
    // 4. Tooltip de Bootstrap en elementos con data-bs-toggle
    // ----------------------------------------------------------
    const tooltipEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipEls.forEach(function (el) {
        new bootstrap.Tooltip(el);
    });

    // ----------------------------------------------------------
    // 5. Confirmación de acciones destructivas
    // ----------------------------------------------------------
    $(document).on('click', '.btn-confirm-action', function (e) {
        const msg = $(this).data('confirm') || '¿Está seguro de realizar esta acción?';
        if (!confirm(msg)) {
            e.preventDefault();
            e.stopPropagation();
        }
    });

    // ----------------------------------------------------------
    // 6. Búsqueda en tabla (input con id #tableSearch)
    // ----------------------------------------------------------
    $('#tableSearch').on('input', function () {
        const query = $(this).val().toLowerCase();
        $('.table-teacher tbody tr').each(function () {
            const rowText = $(this).text().toLowerCase();
            $(this).toggle(rowText.includes(query));
        });
    });

});

/*
 * ============================================================
 * RESUMEN: /cursoonline/teacher/assets/js/teacher.js
 * ============================================================
 * JS global del Portal del Profesor:
 *   - Toggle sidebar móvil
 *   - Logout AJAX
 *   - Animación contadores en stat cards
 *   - Tooltips Bootstrap
 *   - Confirmación de acciones destructivas
 *   - Búsqueda en tiempo real en tablas
 * ============================================================
 */
