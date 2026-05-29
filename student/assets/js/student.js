/* /cursoonline/student/assets/js/student.js */
/* ============================================================
 * Interacciones del Panel del Estudiante — EduTech Academy
 * Proyecto: EduTech Academy
 * ============================================================ */

$(document).ready(function() {
    
    // --- 1. Toggle Sidebar en Móviles ---
    $('#sidebarCollapse').on('click', function() {
        $('.sidebar').toggleClass('active');
    });

    // --- 2. Simulación de Progreso de Video para Clases ---
    // Cuando el estudiante ve una clase, simula el % de avance
    $('#btnSimularAvance').on('click', function() {
        const idInscripcion = $(this).data('inscripcion-id');
        const idClase = $(this).data('clase-id');
        const porcentaje = $('#selectPorcentaje').val();
        
        const btn = $(this);
        btn.prop('disabled', true).text('Guardando Progreso...');

        $.ajax({
            url: '../api/progreso.php',
            type: 'POST',
            data: {
                accion: 'actualizar_progreso',
                id_inscripcion: idInscripcion,
                id_clase: idClase,
                porcentaje: porcentaje
            },
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false).text('Simular Progreso de Vista');
                if (response.estado === 'ok') {
                    // Recargar página para reflejar el progreso recalculado
                    window.location.reload();
                } else {
                    alert('Error: ' + response.mensaje);
                }
            },
            error: function() {
                btn.prop('disabled', false).text('Simular Progreso de Vista');
                alert('Error al conectar con la API de progreso.');
            }
        });
    });

    // --- 3. Procesar Formulario de Perfil (Información Personal) ---
    $('#perfilForm').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const btn = $('#btnGuardarPerfil');
        const alertSuccess = $('#alert-perfil-success');
        const alertError = $('#alert-perfil-error');

        alertSuccess.slideUp();
        alertError.slideUp();
        btn.prop('disabled', true).text('Guardando...');

        $.ajax({
            url: '../api/perfil.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false).text('Guardar Cambios');
                if (response.estado === 'ok') {
                    alertSuccess.html(`<i class="fas fa-check-circle me-2"></i> ${response.mensaje}`).slideDown();
                } else {
                    alertError.html(`<i class="fas fa-exclamation-circle me-2"></i> ${response.mensaje}`).slideDown();
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).text('Guardar Cambios');
                let msg = 'Error interno del servidor.';
                if (xhr.responseJSON && xhr.responseJSON.mensaje) {
                    msg = xhr.responseJSON.mensaje;
                }
                alertError.html(`<i class="fas fa-exclamation-circle me-2"></i> ${msg}`).slideDown();
            }
        });
    });

    // --- 4. Procesar Cambio de Contraseña ---
    $('#passwordForm').on('submit', function(e) {
        e.preventDefault();

        if ($('#nueva_clave').val() !== $('#confirmar_clave').val()) {
            $('#alert-pass-error').html('<i class="fas fa-exclamation-circle me-2"></i> Las nuevas contraseñas no coinciden.').slideDown();
            return;
        }

        const form = $(this);
        const btn = $('#btnCambiarPass');
        const alertSuccess = $('#alert-pass-success');
        const alertError = $('#alert-pass-error');

        alertSuccess.slideUp();
        alertError.slideUp();
        btn.prop('disabled', true).text('Actualizando...');

        $.ajax({
            url: '../api/perfil.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false).text('Cambiar Contraseña');
                if (response.estado === 'ok') {
                    alertSuccess.html(`<i class="fas fa-check-circle me-2"></i> ${response.mensaje}`).slideDown();
                    form[0].reset();
                } else {
                    alertError.html(`<i class="fas fa-exclamation-circle me-2"></i> ${response.mensaje}`).slideDown();
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).text('Cambiar Contraseña');
                let msg = 'Error interno del servidor.';
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
 * RESUMEN DEL ARCHIVO: /cursoonline/student/assets/js/student.js
 * ============================================================
 * Script de interactividad del panel de estudiante.
 * ============================================================
 */
