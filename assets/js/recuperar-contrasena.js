/* /cursoonline/assets/js/recuperar-contrasena.js */
/* ============================================================
 * Lógica del lado del cliente para Solicitar Recuperación de Contraseña
 * Proyecto: EduTech Academy
 * ============================================================ */

$(document).ready(function() {
    $('#recuperarForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const btn = $('#btnSubmit');
        
        $('#alert-error, #alert-success, #emailSimulator').hide();
        btn.prop('disabled', true);
        $('#spinner').show();
        $('#btnText').text('Enviando...');

        $.ajax({
            url: '../api/password.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false);
                $('#spinner').hide();
                $('#btnText').text('Enviar Enlace');
                
                if (response.estado === 'ok') {
                    $('#alert-success').html('<i class="fas fa-check-circle me-2"></i> ' + response.mensaje).slideDown();
                    form[0].reset();
                    
                    // Mostrar simulación del enlace (Solo desarrollo)
                    if (response.enlace_simulado) {
                        $('#linkRestablecer').attr('href', response.enlace_simulado).text(response.enlace_simulado);
                        $('#emailSimulator').slideDown();
                    }
                }
            },
            error: function(xhr) {
                btn.prop('disabled', false);
                $('#spinner').hide();
                $('#btnText').text('Enviar Enlace');

                let msg = 'Error interno del servidor.';
                if (xhr.responseJSON && xhr.responseJSON.mensaje) msg = xhr.responseJSON.mensaje;
                
                $('#alert-error').html('<i class="fas fa-exclamation-circle me-2"></i> ' + msg).slideDown();
            }
        });
    });
});

/*
 * ============================================================
 * RESUMEN DEL ARCHIVO: /cursoonline/assets/js/recuperar-contrasena.js
 * ============================================================
 * Script de solicitud de recuperación de contraseña con AJAX.
 * ============================================================
 */
