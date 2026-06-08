/* /cursoonline/admin/assets/js/usuarios.js */
/* Gestión de Usuarios — EduTech Academy */

$(document).ready(function () {

    // ── 1. Confirmación de eliminación con modal Bootstrap ──
    $(document).on('click', '.btn-delete-user', function (e) {
        e.preventDefault();
        var form = $(this).closest('form');
        var msg = $(this).data('confirm') || '¿Eliminar este usuario? Esta acción es reversible desde "Inactivos".';
        // Reutilizar modal global de confirmación
        if (typeof confirmModal !== 'undefined') {
            // Usar modal si existe
        }
        if (confirm(msg)) {
            form[0].submit();
        }
    });

    // ── 2. Confirmación de toggle estado ────────────────────
    $(document).on('click', '.btn-toggle-estado', function (e) {
        e.preventDefault();
        var form = $(this).closest('form');
        var msg = $(this).data('confirm') || '¿Cambiar el estado de este usuario?';
        if (confirm(msg)) {
            form[0].submit();
        }
    });

    // ── 3. Búsqueda en tabla de inscripciones (ver.php) ────
    $('#filterInscripciones').on('input', function () {
        var q = $(this).val().toLowerCase();
        $('.inscripcion-item').each(function () {
            $(this).toggle($(this).text().toLowerCase().includes(q));
        });
    });

});
