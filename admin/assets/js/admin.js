/* /cursoonline/admin/assets/js/admin.js */
$(document).ready(function () {

    // 1. Sidebar toggle móvil
    $('#adminSidebarCollapse').on('click', function () {
        $('.admin-sidebar').toggleClass('show');
    });
    $(document).on('click', function (e) {
        if ($(window).width() < 992) {
            if (!$(e.target).closest('.admin-sidebar, #adminSidebarCollapse').length) {
                $('.admin-sidebar').removeClass('show');
            }
        }
    });

    // 2. Tooltips Bootstrap
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el);
    });

    // 3. Confirmación de acciones destructivas
    $(document).on('click', '.btn-confirm', function (e) {
        const msg = $(this).data('confirm') || '¿Está seguro? Esta acción no se puede deshacer.';
        if (!confirm(msg)) { e.preventDefault(); e.stopPropagation(); }
    });

    // 4. Búsqueda en tabla en tiempo real
    $('#tableSearch').on('input', function () {
        const q = $(this).val().toLowerCase();
        $('.table-custom tbody tr').each(function () {
            $(this).toggle($(this).text().toLowerCase().includes(q));
        });
    });

    // 5. Auto-dismiss alertas de éxito
    setTimeout(function () { $('.alert-success.auto-dismiss').fadeOut('slow'); }, 4000);

    // 6. Preview de imagen al escribir URL
    $('#inputImagenPortada').on('input', function () {
        const url = $(this).val().trim();
        if (url) {
            $('#previewImagen').attr('src', url).removeClass('d-none');
        } else {
            $('#previewImagen').addClass('d-none');
        }
    });

});
