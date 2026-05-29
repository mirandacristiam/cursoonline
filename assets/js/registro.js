/* /cursoonline/assets/js/registro.js */
/* ============================================================
 * Lógica del lado del cliente para el Registro de Estudiantes
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

    // Enviar formulario mediante AJAX
    $('#registroForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validación del lado del cliente para coincidencia de claves
        if ($('#password').val() !== $('#password_confirm').val()) {
            $('#alert-error-text').text('Las contraseñas ingresadas no coinciden.');
            $('#alert-error').slideDown();
            return;
        }

        const form = $(this);
        const btnSubmit = $('#btnSubmit');
        const btnText = $('#btnText');
        const spinner = $('.btn-spinner');
        const alertError = $('#alert-error');
        const alertSuccess = $('#alert-success');

        // Ocultar alertas previas
        alertError.slideUp();
        alertSuccess.slideUp();

        // Estado de carga
        btnSubmit.prop('disabled', true);
        btnText.text('Creando cuenta...');
        spinner.show();

        $.ajax({
            url: '../api/auth.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.estado === 'ok') {
                    alertSuccess.find('#alert-success-text').text(response.mensaje);
                    alertSuccess.slideDown();
                    form[0].reset();
                    
                    setTimeout(function() {
                        window.location.href = response.redirect;
                    }, 2000);
                }
            },
            error: function(xhr) {
                // Restablecer botón
                btnSubmit.prop('disabled', false);
                btnText.text('Registrarme Ahora');
                spinner.hide();

                let msg = 'Error interno del servidor.';
                if (xhr.responseJSON && xhr.responseJSON.mensaje) {
                    msg = xhr.responseJSON.mensaje;
                }
                
                alertError.find('#alert-error-text').text(msg);
                alertError.slideDown();
            }
        });
    });
});

/*
 * ============================================================
 * RESUMEN DEL ARCHIVO: /cursoonline/assets/js/registro.js
 * ============================================================
 * Script de registro de estudiantes público con jQuery y AJAX.
 * ============================================================
 */
