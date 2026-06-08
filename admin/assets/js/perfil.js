// /cursoonline/admin/assets/js/perfil.js
// Admin Profile — EduTech Academy

$(document).ready(function () {

  // Ocultar alertas al hacer clic
  $(document).on('click', '.alert-custom', function () { $(this).slideUp(); });

  // ── 1. Guardar perfil ─────────────────────────────────
  $('#perfilForm').on('submit', function (e) {
    e.preventDefault();
    var btn = $(this).find('button[type="submit"]');
    var alertOk = $('#alert-perfil-success');
    var alertErr = $('#alert-perfil-error');
    alertOk.slideUp(); alertErr.slideUp();
    btn.prop('disabled', true).text('Guardando...');
    $.ajax({
      url: '../api/perfil.php',
      type: 'POST',
      data: $(this).serialize(),
      dataType: 'json',
      success: function (r) {
        btn.prop('disabled', false).text('Guardar Cambios');
        if (r.estado === 'ok') {
          alertOk.html('<i class="fas fa-check-circle me-2"></i>' + r.mensaje).slideDown();
        } else {
          alertErr.html('<i class="fas fa-exclamation-circle me-2"></i>' + r.mensaje).slideDown();
        }
      },
      error: function (xhr) {
        btn.prop('disabled', false).text('Guardar Cambios');
        var msg = 'Error interno del servidor.';
        try {
          var r = JSON.parse(xhr.responseText);
          if (r.mensaje) msg = r.mensaje;
        } catch (e) {}
        alertErr.html('<i class="fas fa-exclamation-circle me-2"></i>' + msg).slideDown();
      }
    });
  });

  // ── 2. Cambiar contraseña ────────────────────────────
  $('#passwordForm').on('submit', function (e) {
    e.preventDefault();
    if ($('input[name="nueva_clave"]').val() !== $('input[name="confirmar_clave"]').val()) {
      $('#alert-pass-error').html('<i class="fas fa-exclamation-circle me-2"></i>Las contrase\u00f1as no coinciden.').slideDown();
      return;
    }
    var btn = $(this).find('button[type="submit"]');
    var alertOk = $('#alert-pass-success');
    var alertErr = $('#alert-pass-error');
    alertOk.slideUp(); alertErr.slideUp();
    btn.prop('disabled', true).text('Actualizando...');
    $.ajax({
      url: '../api/perfil.php',
      type: 'POST',
      data: $(this).serialize(),
      dataType: 'json',
      success: function (r) {
        btn.prop('disabled', false).text('Cambiar Contrase\u00f1a');
        if (r.estado === 'ok') {
          alertOk.html('<i class="fas fa-check-circle me-2"></i>' + r.mensaje).slideDown();
          $('#passwordForm')[0].reset();
        } else {
          alertErr.html('<i class="fas fa-exclamation-circle me-2"></i>' + r.mensaje).slideDown();
        }
      },
      error: function (xhr) {
        btn.prop('disabled', false).text('Cambiar Contrase\u00f1a');
        var msg = 'Error interno del servidor.';
        try {
          var r = JSON.parse(xhr.responseText);
          if (r.mensaje) msg = r.mensaje;
        } catch (e) {}
        alertErr.html('<i class="fas fa-exclamation-circle me-2"></i>' + msg).slideDown();
      }
    });
  });

  // ── 3. Subir foto ────────────────────────────────────
  $('#fotoForm').on('submit', function (e) {
    e.preventDefault();
    var btn = $(this).find('button[type="submit"]');
    var alertOk = $('#alert-foto-success');
    var alertErr = $('#alert-foto-error');
    alertOk.slideUp(); alertErr.slideUp();
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Subiendo...');
    var fd = new FormData(this);
    $.ajax({
      url: '../api/perfil.php',
      type: 'POST',
      data: fd,
      processData: false,
      contentType: false,
      dataType: 'json',
      success: function (r) {
        btn.prop('disabled', false).html('<i class="fas fa-upload me-1"></i>Subir Foto');
        if (r.estado === 'ok') {
          $('#fotoPreview').attr('src', r.foto_url);
          $('#headerAvatar img').attr('src', r.foto_url);
          alertOk.html('<i class="fas fa-check-circle me-2"></i>' + r.mensaje).slideDown();
        } else {
          alertErr.html('<i class="fas fa-exclamation-circle me-2"></i>' + r.mensaje).slideDown();
        }
      },
      error: function () {
        btn.prop('disabled', false).html('<i class="fas fa-upload me-1"></i>Subir Foto');
        alertErr.html('<i class="fas fa-exclamation-circle me-2"></i>Error interno del servidor.').slideDown();
      }
    });
  });

  // ── 4. Eliminar foto ─────────────────────────────────
  $(document).on('click', '#btnEliminarFoto', function () {
    if (!confirm('\u00bfEliminar tu foto de perfil?')) return;
    var alertOk = $('#alert-foto-success');
    var alertErr = $('#alert-foto-error');
    alertOk.slideUp(); alertErr.slideUp();
    var token = $(this).data('csrf-token');
    $.ajax({
      url: '../api/perfil.php',
      type: 'POST',
      data: { accion: 'eliminar_foto', csrf_token: token },
      dataType: 'json',
      success: function (r) {
        if (r.estado === 'ok') {
          var def = 'assets/images/foto_perfil/default-avatar.svg';
          $('#fotoPreview').attr('src', def);
          $('#headerAvatar img').attr('src', def);
          $('#btnEliminarFoto').closest('div').remove();
          alertOk.html('<i class="fas fa-check-circle me-2"></i>' + r.mensaje).slideDown();
        } else {
          alertErr.html('<i class="fas fa-exclamation-circle me-2"></i>' + r.mensaje).slideDown();
        }
      },
      error: function () {
        alertErr.html('<i class="fas fa-exclamation-circle me-2"></i>Error interno del servidor.').slideDown();
      }
    });
  });

  // ── 5. Auto-dismiss alertas ──────────────────────────
  setTimeout(function () { $('.alert-success.auto-dismiss').fadeOut('slow'); }, 4000);
});
