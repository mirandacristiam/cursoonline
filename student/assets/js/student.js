/* /cursoonline/student/assets/js/student.js */
/* ============================================================
 * Interacciones del Panel del Estudiante — EduTech Academy
 * Proyecto: EduTech Academy
 * ============================================================ */

$(document).ready(function() {
    
    // --- 1. Toggle Sidebar en Móviles ---
    $('#sidebarCollapse').on('click', function() {
        $('.sidebar').toggleClass('active');
        $('#sidebarOverlay').toggleClass('active');
    });

    $('#sidebarOverlay').on('click', function() {
        $('.sidebar').removeClass('active');
        $(this).removeClass('active');
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
            url: '../api/perfil_estudiante.php',
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

    // --- 4. Subir Foto de Perfil ---
    $('#fotoForm').on('submit', function(e) {
        e.preventDefault();

        if (!$('#fotoInput')[0].files.length) {
            $('#alert-foto-error').html('<i class="fas fa-exclamation-circle me-2"></i> Selecciona un archivo de imagen.').slideDown();
            return;
        }

        const formData = new FormData(this);
        const btn = $('#btnSubirFoto');
        const alertSuccess = $('#alert-foto-success');
        const alertError = $('#alert-foto-error');

        alertSuccess.slideUp();
        alertError.slideUp();
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Subiendo...');

        $.ajax({
            url: '../api/perfil_estudiante.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false).html('<i class="fas fa-upload me-1"></i>Subir Foto');
                if (response.estado === 'ok') {
                    $('#fotoPreview').attr('src', response.foto_url + '?t=' + Date.now());
                    $('#headerAvatar').attr('src', response.foto_url + '?t=' + Date.now());
                    alertSuccess.html(`<i class="fas fa-check-circle me-2"></i> ${response.mensaje}`).slideDown();
                } else {
                    alertError.html(`<i class="fas fa-exclamation-circle me-2"></i> ${response.mensaje}`).slideDown();
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-upload me-1"></i>Subir Foto');
                let msg = 'Error interno del servidor.';
                if (xhr.responseJSON && xhr.responseJSON.mensaje) {
                    msg = xhr.responseJSON.mensaje;
                }
                alertError.html(`<i class="fas fa-exclamation-circle me-2"></i> ${msg}`).slideDown();
            }
        });
    });

    // --- 5. Eliminar Foto de Perfil ---
    $('#btnEliminarFoto').on('click', function() {
        if (!confirm('¿Estás seguro de eliminar tu foto de perfil?')) return;

        const btn = $(this);
        const alertSuccess = $('#alert-foto-success');
        const alertError = $('#alert-foto-error');

        alertSuccess.slideUp();
        alertError.slideUp();
        btn.prop('disabled', true).text('Eliminando...');

        const csrfToken = $('#btnEliminarFoto').data('csrf-token');

        $.ajax({
            url: '../api/perfil_estudiante.php',
            type: 'POST',
            data: { accion: 'eliminar_foto', csrf_token: csrfToken },
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false).html('<i class="fas fa-trash-alt me-1"></i>Eliminar Foto');
                if (response.estado === 'ok') {
                    var def = 'assets/images/foto_perfil/default-avatar.svg';
                    $('#fotoPreview').attr('src', def);
                    $('#headerAvatar').attr('src', def);
                    alertSuccess.html(`<i class="fas fa-check-circle me-2"></i> ${response.mensaje}`).slideDown();
                    btn.remove();
                } else {
                    alertError.html(`<i class="fas fa-exclamation-circle me-2"></i> ${response.mensaje}`).slideDown();
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false).html('<i class="fas fa-trash-alt me-1"></i>Eliminar Foto');
                let msg = 'Error interno del servidor.';
                if (xhr.responseJSON && xhr.responseJSON.mensaje) {
                    msg = xhr.responseJSON.mensaje;
                }
                alertError.html(`<i class="fas fa-exclamation-circle me-2"></i> ${msg}`).slideDown();
            }
        });
    });

    // --- 6. Procesar Cambio de Contraseña ---
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
            url: '../api/perfil_estudiante.php',
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
