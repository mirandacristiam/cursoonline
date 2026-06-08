// /cursoonline/admin/assets/js/dashboard.js
// Dashboard charts — EduTech Academy Admin
// Chart.js 4.4.0 (UMD) — Chart global

document.addEventListener('DOMContentLoaded', function () {

  var d = window.DASHBOARD_DATA;
  if (!d) return;

  // ---------- Shared defaults ----------
  Chart.defaults.font.family = 'Inter, sans-serif';
  Chart.defaults.font.size   = 11;
  Chart.defaults.color       = '#64748B';

  // ---------- 1. Ventas mensuales (línea) ----------
  var ctxVentas = document.getElementById('chartVentas');
  if (ctxVentas && d.ventasMeses && d.ventasMeses.length) {
    new Chart(ctxVentas, {
      type: 'line',
      data: {
        labels: d.ventasMeses,
        datasets: [
          {
            label: 'Ingresos ($)',
            data: d.ventasValores,
            borderColor: '#2563EB',
            backgroundColor: 'rgba(37, 99, 235, 0.08)',
            borderWidth: 3,
            pointRadius: 4,
            pointBackgroundColor: '#2563EB',
            tension: 0.35,
            fill: true,
            yAxisID: 'y',
            order: 1,
          },
          {
            label: 'Transacciones',
            data: d.ventasCount,
            borderColor: '#DC2626',
            backgroundColor: 'rgba(220, 38, 38, 0.06)',
            borderWidth: 2,
            borderDash: [6, 4],
            pointRadius: 3,
            pointBackgroundColor: '#DC2626',
            tension: 0.35,
            fill: false,
            yAxisID: 'y1',
            order: 2,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
          legend: {
            position: 'top',
            labels: { boxWidth: 14, padding: 14, usePointStyle: true },
          },
          tooltip: {
            callbacks: {
              label: function (ctx) {
                if (ctx.datasetIndex === 0) return '$ ' + Number(ctx.parsed.y).toLocaleString('es-CO');
                return ctx.parsed.y + ' transacciones';
              },
            },
          },
        },
        scales: {
          y: {
            position: 'left',
            beginAtZero: true,
            grid: { color: 'rgba(0,0,0,0.05)' },
            ticks: {
              callback: function (v) { return '$' + v.toLocaleString('es-CO'); },
            },
          },
          y1: {
            position: 'right',
            beginAtZero: true,
            grid: { display: false },
            ticks: { display: false },
          },
        },
      },
    });
  }

  // ---------- 2. Inscripciones mensuales (barras) ----------
  var ctxInsc = document.getElementById('chartInscripciones');
  if (ctxInsc && d.inscMeses && d.inscMeses.length) {
    new Chart(ctxInsc, {
      type: 'bar',
      data: {
        labels: d.inscMeses,
        datasets: [{
          label: 'Inscripciones',
          data: d.inscValores,
          backgroundColor: [
            'rgba(22, 163, 74, 0.7)',
            'rgba(37, 99, 235, 0.7)',
            'rgba(217, 119, 6, 0.7)',
            'rgba(124, 58, 237, 0.7)',
            'rgba(220, 38, 38, 0.7)',
            'rgba(6, 182, 212, 0.7)',
          ],
          borderColor: '#16A34A',
          borderRadius: 6,
          borderSkipped: false,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function (ctx) { return ctx.parsed.y + ' inscripciones'; },
            },
          },
        },
        scales: {
          y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' } },
          x: { grid: { display: false } },
        },
      },
    });
  }

  // ---------- 3. Distribución roles (doughnut) ----------
  var ctxRoles = document.getElementById('chartRoles');
  if (ctxRoles && d.rolesNombres && d.rolesNombres.length) {
    new Chart(ctxRoles, {
      type: 'doughnut',
      data: {
        labels: d.rolesNombres,
        datasets: [{
          data: d.rolesValores,
          backgroundColor: d.rolesColores,
          borderWidth: 0,
        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: { boxWidth: 12, padding: 14, usePointStyle: true },
          },
          tooltip: {
            callbacks: {
              label: function (ctx) {
                var total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                var pct = ((ctx.parsed / total) * 100).toFixed(1);
                return ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
              },
            },
          },
        },
      },
    });
  }

  // ---------- 4. Ingresos por categoría (barras horizontales) ----------
  var ctxCat = document.getElementById('chartCategorias');
  if (ctxCat && d.catNombres && d.catNombres.length) {
    new Chart(ctxCat, {
      type: 'bar',
      data: {
        labels: d.catNombres,
        datasets: [{
          label: 'Ingresos ($)',
          data: d.catValores,
          backgroundColor: d.catColores.map(function (c) {
            try { return c.replace(')', ', 0.75)').replace('rgb', 'rgba'); }
            catch (e) { return c; }
          }),
          borderRadius: 6,
          borderSkipped: false,
        }],
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function (ctx) { return '$ ' + Number(ctx.parsed.x).toLocaleString('es-CO'); },
            },
          },
        },
        scales: {
          x: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' } },
          y: { grid: { display: false } },
        },
      },
    });
  }

  // ---------- 5. Reloj del header ----------
  var dateEl = document.getElementById('currentDate');
  if (dateEl) {
    var now = new Date();
    var opts = { year: 'numeric', month: 'long', day: 'numeric' };
    dateEl.textContent = now.toLocaleDateString('es-ES', opts);
  }

});
