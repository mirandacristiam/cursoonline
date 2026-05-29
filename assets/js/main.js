/* /cursoonline/assets/js/main.js */
/* ============================================================
 * Interacciones de la Landing Page — EduTech Academy
 * Proyecto: EduTech Academy
 * ============================================================ */

$(document).ready(function() {
    
    // --- 1. Efecto Scroll en la Barra de Navegación ---
    $(window).on('scroll', function() {
        if ($(window).scrollTop() > 50) {
            $('.navbar-custom').addClass('navbar-scrolled');
        } else {
            $('.navbar-custom').removeClass('navbar-scrolled');
        }
    });

    // --- 2. Filtro de Categorías en el Catálogo ---
    $('.category-filter-btn').on('click', function() {
        // Remover activo de todos los botones y agregar al seleccionado
        $('.category-filter-btn').removeClass('active');
        $(this).addClass('active');

        const categoriaId = $(this).data('category');

        // Mostrar u ocultar las cards de curso
        if (categoriaId === 'all') {
            $('.course-card-col').fadeIn(300);
        } else {
            $('.course-card-col').hide();
            $(`.course-card-col[data-category-id="${categoriaId}"]`).fadeIn(300);
        }
    });

    // --- 3. Envío del Formulario de Contacto vía AJAX ---
    $('#contactoForm').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const btnSubmit = $('#btnContactSubmit');
        const alertError = $('#alert-contact-error');
        const alertSuccess = $('#alert-contact-success');

        // Resetear alertas
        alertError.slideUp();
        alertSuccess.slideUp();

        // Estado de carga en botón
        btnSubmit.prop('disabled', true).text('Enviando mensaje...');

        $.ajax({
            url: 'api/contacto.php', // Enrutador del backend para guardar contacto
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                btnSubmit.prop('disabled', false).text('Enviar Mensaje');
                
                if (response.estado === 'ok') {
                    alertSuccess.html(`<i class="fas fa-check-circle me-2"></i> ${response.mensaje}`).slideDown();
                    form[0].reset();
                } else {
                    alertError.html(`<i class="fas fa-exclamation-circle me-2"></i> ${response.mensaje}`).slideDown();
                }
            },
            error: function(xhr) {
                btnSubmit.prop('disabled', false).text('Enviar Mensaje');
                
                let msg = 'Error interno del servidor al procesar el mensaje.';
                if (xhr.responseJSON && xhr.responseJSON.mensaje) {
                    msg = xhr.responseJSON.mensaje;
                }
                
                alertError.html(`<i class="fas fa-exclamation-circle me-2"></i> ${msg}`).slideDown();
            }
        });
    });
});

/*
 * ============================================================
 * RESUMEN DEL ARCHIVO: /cursoonline/assets/js/main.js
 * ============================================================
 * Script que contiene la interactividad de la página de inicio,
 * control de scroll, filtrado dinámico de cursos y AJAX de contacto.
 * ============================================================
 */
