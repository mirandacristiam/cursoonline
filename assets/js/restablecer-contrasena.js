/* /cursoonline/assets/js/restablecer-contrasena.js */
/* ============================================================
 * Lógica del lado del cliente para Restablecer la Contraseña
 * Proyecto: EduTech Academy
 * ============================================================ */

$(document).ready(function() {
    // Mostrar/Ocultar contraseñas
    $('.toggle-password').on('click', function() {
        const target = $($(this).data('target'));
        const icon = $(this).find('i');
        if (target.attr('type') === 'password') {
            target.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            target.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    $('#restablecerForm').on('submit', function(e) {
        e.preventDefault();
        
        if ($('#password').val() !== $('#password_confirm').val()) {
            $('#alert-error').html('<i class="fas fa-exclamation-circle me-2"></i> Las contraseñas no coinciden.').slideDown();
            return;
        }
        
        const form = $(this);
        const btn = $('#btnSubmit');
        
        $('#alert-error, #alert-success').hide();
        btn.prop('disabled', true);
        $('#spinner').show();
        $('#btnText').text('Guardando...');

        $.ajax({
            url: '../api/password.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.estado === 'ok') {
                    $('#alert-success').html('<i class="fas fa-check-circle me-2"></i> ' + response.mensaje).slideDown();
                    setTimeout(function() {
                        window.location.href = response.redirect;
                    }, 2000);
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false);
                $('#spinner').hide();
                $('#btnText').text('Guardar Nueva Contraseña');

                let msg = 'Error interno del servidor.';
                if (xhr.responseJSON && xhr.responseJSON.mensaje) msg = xhr.responseJSON.mensaje;
                
                $('#alert-error').html('<i class="fas fa-exclamation-circle me-2"></i> ' + msg).slideDown();
            }
        });
    });
});

/*
 * ============================================================
 * RESUMEN DEL ARCHIVO: /cursoonline/assets/js/restablecer-contrasena.js
 * ============================================================
 * Script de restablecimiento de contraseña mediante AJAX.
 * ============================================================
 */
