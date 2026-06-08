// /cursoonline/admin/assets/js/cursos.js
// Admin Courses — EduTech Academy

$(document).ready(function () {

  // ── 1. Confirmación con Bootstrap modal ──────────────
  var confirmModal = '\
<div class="modal fade" id="confirmModal" tabindex="-1">\
  <div class="modal-dialog modal-dialog-centered">\
    <div class="modal-content border-0 shadow" style="border-radius:16px;">\
      <div class="modal-header border-0 pb-1">\
        <h5 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Confirmar Acción</h5>\
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>\
      </div>\
      <div class="modal-body pt-2 pb-3" id="confirmModalMsg"></div>\
      <div class="modal-footer border-0 pt-0">\
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-3" data-bs-dismiss="modal">Cancelar</button>\
        <button type="button" class="btn btn-danger btn-sm rounded-3" id="confirmModalBtn"><i class="fas fa-check me-1"></i>Confirmar</button>\
      </div>\
    </div>\
  </div>\
</div>';

  if (!$('#confirmModal').length) {
    $('body').append(confirmModal);
  }

  $(document).on('click', '.btn-confirm-modal', function (e) {
    e.preventDefault();
    var btn = $(this);
    var msg = btn.data('confirm') || '¿Está seguro de realizar esta acción?';
    $('#confirmModalMsg').text(msg);
    $('#confirmModalBtn').off('click').on('click', function () {
      var form = btn.closest('form');
      if (form.length) { form[0].submit(); }
    });
    $('#confirmModal').modal('show');
  });

  // ── 2. Auto-dismiss alerts ──────────────────────────
  setTimeout(function () { $('.alert-success.auto-dismiss').fadeOut('slow'); }, 4000);

  // ── 3. Preview de imagen portada (file upload) ─────
  $(document).on('change', '#inputImagenPortada', function () {
    var file = this.files && this.files[0];
    if (file) {
      var reader = new FileReader();
      reader.onload = function (e) {
        $('#previewImagen').attr('src', e.target.result).removeClass('d-none');
      };
      reader.readAsDataURL(file);
    } else {
      $('#previewImagen').addClass('d-none');
    }
  });

  // ── 4. Filtros auto-submit en cursos/index ──────────
  $('.filter-auto').on('change', function () {
    $(this).closest('form').submit();
  });

  // ── 5. Tooltips ─────────────────────────────────────
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
    new bootstrap.Tooltip(el);
  });

  // ── 6. Manejo inline de módulos, clases y competencias (ver.php) ──

  // Abrir modal para crear/editar módulo
  $(document).on('click', '.btn-modulo-action', function () {
    var action = $(this).data('action');
    var idCurso = $(this).data('id-curso');
    var idModulo = $(this).data('id-modulo') || 0;
    var data = {};

    if (action === 'edit' && idModulo) {
      data = $(this).data();
    }

    $('#modalModuloTitulo').text(idModulo ? 'Editar Módulo' : 'Nuevo Módulo');
    $('#modulo_id_modulo').val(idModulo || 0);
    $('#modulo_id_curso_fk').val(idCurso);
    $('#modulo_titulo').val(data.titulo || '');
    $('#modulo_descripcion').val(data.descripcion || '');
    $('#modulo_horas').val(data.horas || 0);
    $('#modulo_orden').val(data.orden || 1);
    $('#modalModulo').modal('show');
  });

  // Abrir modal para crear/editar clase
  $(document).on('click', '.btn-clase-action', function () {
    var action = $(this).data('action');
    var idModulo = $(this).data('id-modulo');
    var idClase = $(this).data('id-clase') || 0;
    var data = {};

    if (action === 'edit' && idClase) {
      data = $(this).data();
    }

    $('#modalClaseTitulo').text(idClase ? 'Editar Clase' : 'Nueva Clase');
    $('#clase_id_clase').val(idClase || 0);
    $('#clase_id_modulo_fk').val(idModulo);
    $('#clase_titulo').val(data.titulo || '');
    $('#clase_descripcion').val(data.descripcion || '');
    $('#clase_url_video').val(data.urlVideo || '');
    $('#clase_duracion').val(data.duracion || 0);
    $('#clase_orden').val(data.orden || 1);
    $('#clase_gratuita').prop('checked', data.gratuita === 1 || data.gratuita === true || data.gratuita === '1');
    $('#clase_tipo_video').val(data.tipoVideo || 'youtube');
    $('#modalClase').modal('show');
  });

  // Abrir modal para crear/editar competencia
  $(document).on('click', '.btn-competencia-action', function () {
    var action = $(this).data('action');
    var idCurso = $(this).data('id-curso');
    var idCompetencia = $(this).data('id-competencia') || 0;
    var data = {};

    if (action === 'edit' && idCompetencia) {
      data = $(this).data();
    }

    var icono = data.icono || 'fa-check';
    $('#modalCompetenciaTitulo').text(idCompetencia ? 'Editar Competencia' : 'Nueva Competencia');
    $('#comp_id_competencia').val(idCompetencia || 0);
    $('#comp_id_curso_fk').val(idCurso);
    $('#comp_descripcion').val(data.descripcion || '');
    $('#comp_icono').val(icono);
    $('#comp_orden').val(data.orden || 1);
    $('#iconPickerGrid .icon-picker-item').removeClass('selected');
    $('#iconPickerGrid .icon-picker-item[data-icon="' + icono + '"]').addClass('selected');
    $('#modalCompetencia').modal('show');
  });

  // ── 7. Icon picker para competencias ────────────────
  $(document).on('click', '.icon-picker-item', function () {
    $(this).closest('.icon-picker-grid').find('.icon-picker-item').removeClass('selected');
    $(this).addClass('selected');
    $('#comp_icono').val($(this).data('icon'));
  });

  // ── 8. Reset icon picker when modal closes ──────────
  $('#modalCompetencia').on('hidden.bs.modal', function () {
    $('#comp_icono').val('fa-check');
    $('#iconPickerGrid .icon-picker-item').removeClass('selected');
    $('#iconPickerGrid .icon-picker-item[data-icon="fa-check"]').addClass('selected');
  });

  // ── 9. Confirmación de eliminación con modal ────────
  $(document).on('click', '.btn-delete-action', function (e) {
    e.preventDefault();
    var form = $(this).closest('form');
    var msg = $(this).data('confirm') || '¿Eliminar este elemento?';
    $('#confirmModalMsg').text(msg);
    $('#confirmModalBtn').off('click').on('click', function () {
      form[0].submit();
    });
    $('#confirmModal').modal('show');
  });

});
