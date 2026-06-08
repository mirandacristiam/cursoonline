// /cursoonline/admin/assets/js/reportes.js
// Reportes charts — EduTech Academy Admin
// Chart.js 4.4.0 (UMD)

document.addEventListener('DOMContentLoaded', function () {
  var d = window.REPORTES_DATA;
  if (!d) return;

  Chart.defaults.font.family = 'Inter, sans-serif';
  Chart.defaults.font.size   = 11;
  Chart.defaults.color       = '#64748B';

  // ---------- 1. Ingresos mensuales (barra) ----------
  var cIngresos = document.getElementById('chartIngresos');
  if (cIngresos && d.ingresosMeses && d.ingresosMeses.length) {
    new Chart(cIngresos, {
      type: 'bar',
      data: {
        labels: d.ingresosMeses,
        datasets: [{
          label: 'Ingresos ($)',
          data: d.ingresosValores,
          backgroundColor: 'rgba(37, 99, 235, 0.7)',
          borderColor: '#2563EB',
          borderWidth: 1,
          borderRadius: 4,
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, ticks: { callback: function(v) { return '$' + v.toLocaleString(); } } },
          x: { grid: { display: false } }
        }
      }
    });
  }

  // ---------- 2. Crecimiento de usuarios (línea) ----------
  var cCrecimiento = document.getElementById('chartCrecimiento');
  if (cCrecimiento && d.crecimientoMeses && d.crecimientoMeses.length) {
    new Chart(cCrecimiento, {
      type: 'line',
      data: {
        labels: d.crecimientoMeses,
        datasets: [{
          label: 'Nuevos estudiantes',
          data: d.crecimientoEstudiantes,
          borderColor: '#2563EB',
          backgroundColor: 'rgba(37, 99, 235, 0.08)',
          tension: 0.35, fill: true, pointRadius: 3,
        }, {
          label: 'Nuevos profesores',
          data: d.crecimientoProfesores,
          borderColor: '#8B5CF6',
          backgroundColor: 'rgba(139, 92, 246, 0.08)',
          tension: 0.35, fill: true, pointRadius: 3,
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        scales: { y: { beginAtZero: true } }
      }
    });
  }

  // ---------- 3. Progreso académico (doughnut) ----------
  var cProgreso = document.getElementById('chartProgreso');
  if (cProgreso && d.progresoLabels) {
    new Chart(cProgreso, {
      type: 'doughnut',
      data: {
        labels: d.progresoLabels,
        datasets: [{
          data: d.progresoValores,
          backgroundColor: ['#059669', '#10B981', '#F59E0B', '#F97316', '#EF4444', '#94A3B8'],
          borderWidth: 2, borderColor: '#fff',
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom', labels: { padding: 12, boxWidth: 12 } }
        }
      }
    });
  }

  // ---------- 4. Cursos top (horizontal bar) ----------
  var cCursosTop = document.getElementById('chartCursosTop');
  if (cCursosTop && d.cursosTopLabels) {
    new Chart(cCursosTop, {
      type: 'bar',
      data: {
        labels: d.cursosTopLabels,
        datasets: [{
          label: 'Inscripciones',
          data: d.cursosTopInscripciones,
          backgroundColor: 'rgba(37, 99, 235, 0.65)',
          borderColor: '#2563EB', borderWidth: 1, borderRadius: 4,
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, grid: { display: false } } }
      }
    });
  }
});
