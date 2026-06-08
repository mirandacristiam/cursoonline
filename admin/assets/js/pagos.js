// /cursoonline/admin/assets/js/pagos.js
// Pagos module — EduTech Academy Admin

document.addEventListener('DOMContentLoaded', function () {

  // ---------- Modal de confirmación (aprobar/rechazar) ----------
  var modalProcesar = document.getElementById('modalProcesar');
  if (modalProcesar) {
    window.abrirModal = function (accion, txId, estudiante, curso, monto) {
      document.getElementById('modalTxId').value = txId;
      document.getElementById('modalAccion').value = accion;
      document.getElementById('modalEstudiante').textContent = estudiante;
      document.getElementById('modalCurso').textContent = curso;
      document.getElementById('modalMonto').textContent = monto;
      document.getElementById('modalObs').value = '';

      var header = document.getElementById('modalHeader');
      var btn = document.getElementById('modalBtnConfirm');
      var label = document.getElementById('labelObs');
      var title = document.getElementById('modalProcesarLabel');

      if (accion === 'aprobar' || accion === 'reaprobar') {
        header.style.background = 'linear-gradient(135deg,#ECFDF5,#D1FAE5)';
        title.textContent = (accion === 'reaprobar' ? 'Re-Aprobar Pago' : 'Aprobar Pago');
        title.style.color = '#14532D';
        btn.className = 'btn btn-success rounded-3 px-4 fw-bold';
        btn.innerHTML = '<i class="fas fa-check me-2"></i>Confirmar Aprobaci\u00f3n';
        label.textContent = 'Observaciones de aprobaci\u00f3n (opcional)';
      } else if (accion === 'cancelar_def') {
        header.style.background = 'linear-gradient(135deg,#F1F5F9,#E2E8F0)';
        title.textContent = 'Cancelar Transacci\u00f3n';
        title.style.color = '#475569';
        btn.className = 'btn btn-secondary rounded-3 px-4 fw-bold';
        btn.innerHTML = '<i class="fas fa-ban me-2"></i>Confirmar Cancelaci\u00f3n';
        label.textContent = 'Motivo de la cancelaci\u00f3n (opcional)';
      } else {
        header.style.background = 'linear-gradient(135deg,#FEF2F2,#FEE2E2)';
        title.textContent = 'Rechazar Pago';
        title.style.color = '#7F1D1D';
        btn.className = 'btn btn-danger rounded-3 px-4 fw-bold';
        btn.innerHTML = '<i class="fas fa-times me-2"></i>Confirmar Rechazo';
        label.textContent = 'Motivo del rechazo (recomendado)';
      }

      var bsModal = bootstrap.Modal.getOrCreateInstance(modalProcesar);
      bsModal.show();
    };
  }
});
