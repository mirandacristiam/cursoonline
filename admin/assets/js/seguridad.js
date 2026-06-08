// /cursoonline/admin/assets/js/seguridad.js
// Seguridad charts — EduTech Academy Admin
// Chart.js 4.4.0 (UMD)

document.addEventListener('DOMContentLoaded', function () {
  var d = window.SEGURIDAD_DATA;
  if (!d) return;

  Chart.defaults.font.family = 'Inter, sans-serif';
  Chart.defaults.font.size   = 11;
  Chart.defaults.color       = '#64748B';

  // ---------- 1. Accesos diarios (línea) ----------
  var cAccesos = document.getElementById('chartAccesos');
  if (cAccesos && d.accesosDias && d.accesosDias.length) {
    new Chart(cAccesos, {
      type: 'line',
      data: {
        labels: d.accesosDias,
        datasets: [{
          label: 'Exitosos',
          data: d.accesosExitosos,
          borderColor: '#059669',
          backgroundColor: 'rgba(5, 150, 105, 0.08)',
          tension: 0.35, fill: true, pointRadius: 3,
        }, {
          label: 'Fallidos',
          data: d.accesosFallidos,
          borderColor: '#DC2626',
          backgroundColor: 'rgba(220, 38, 38, 0.08)',
          tension: 0.35, fill: true, pointRadius: 3,
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        scales: { y: { beginAtZero: true } },
        plugins: {
          tooltip: { mode: 'index', intersect: false },
          legend: { position: 'top', labels: { boxWidth: 12, padding: 12 } }
        }
      }
    });
  }

  // ---------- 2. Tipos de acción (doughnut) ----------
  var cAccion = document.getElementById('chartAccion');
  if (cAccion && d.accionLabels) {
    new Chart(cAccion, {
      type: 'doughnut',
      data: {
        labels: d.accionLabels,
        datasets: [{
          data: d.accionValores,
          backgroundColor: ['#059669', '#DC2626', '#3B82F6', '#8B5CF6', '#F59E0B'],
          borderWidth: 2, borderColor: '#fff',
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom', labels: { padding: 10, boxWidth: 12 } }
        }
      }
    });
  }

  // ---------- 3. Errores diarios (barra) ----------
  var cErrores = document.getElementById('chartErrores');
  if (cErrores && d.erroresDias && d.erroresDias.length) {
    new Chart(cErrores, {
      type: 'bar',
      data: {
        labels: d.erroresDias,
        datasets: [{
          label: 'Errores',
          data: d.erroresValores,
          backgroundColor: 'rgba(245, 158, 11, 0.6)',
          borderColor: '#F59E0B', borderWidth: 1, borderRadius: 3,
        }]
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
      }
    });
  }

  // ---------- 4. Top actividad usuarios (barra horizontal) ----------
  var cTopActividad = document.getElementById('chartTopActividad');
  if (cTopActividad && d.topUserLabels) {
    new Chart(cTopActividad, {
      type: 'bar',
      data: {
        labels: d.topUserLabels,
        datasets: [{
          label: 'Accesos',
          data: d.topUserValores,
          backgroundColor: 'rgba(99, 102, 241, 0.6)',
          borderColor: '#6366F1', borderWidth: 1, borderRadius: 3,
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
