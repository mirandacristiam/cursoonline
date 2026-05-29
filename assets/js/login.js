/* /cursoonline/assets/js/login.js */
/* ============================================================
 * Lógica del lado del cliente para el Inicio de Sesión
 * Proyecto: EduTech Academy
 * ============================================================ */

$(document).ready(function() {
    // Mostrar/Ocultar contraseña
    $('#togglePassword').on('click', function() {
        const passInput = $('#password');
        const icon = $(this).find('i');
        
        if (passInput.attr('type') === 'password') {
            passInput.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passInput.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // Enviar formulario mediante AJAX
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        
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
        btnText.text('Verificando...');
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
                    
                    // Redirigir según indique el backend
                    setTimeout(function() {
                        window.location.href = response.redirect;
                    }, 1000);
                }
            },
            error: function(xhr) {
                // Restablecer botón
                btnSubmit.prop('disabled', false);
                btnText.text('Iniciar Sesión');
                spinner.hide();

                // Limpiar campo de contraseña por seguridad si falla
                $('#password').val('');
                
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
 * RESUMEN DEL ARCHIVO: /cursoonline/assets/js/login.js
 * ============================================================
 * Script de inicio de sesión público con jQuery y AJAX.
 * ============================================================
 */
