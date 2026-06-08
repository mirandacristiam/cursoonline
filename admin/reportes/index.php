<?php
$page_title = 'Reportes y Estadísticas';
$page_css   = '../assets/css/reportes.css';
$page_script = '../assets/js/reportes.js';
require_once __DIR__ . '/../includes/header.php';

// ── Métricas generales ───────────────────────────────────
$metricas = [];
$stmt = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM usuarios WHERE estado_activo = 1) AS total_usuarios,
        (SELECT COUNT(*) FROM usuarios WHERE id_rol_fk = 3 AND estado_activo = 1) AS total_estudiantes,
        (SELECT COUNT(*) FROM usuarios WHERE id_rol_fk = 2 AND estado_activo = 1) AS total_profesores,
        (SELECT COUNT(*) FROM cursos WHERE estado_activo = 1) AS total_cursos,
        (SELECT COUNT(*) FROM inscripciones WHERE estado_activo = 1 AND estado_inscripcion = 'activa') AS inscripciones_activas,
        (SELECT COUNT(*) FROM inscripciones WHERE estado_activo = 1 AND estado_inscripcion = 'completada') AS inscripciones_completadas,
        (SELECT COALESCE(SUM(monto_total), 0) FROM transacciones_pago WHERE estado_transaccion = 'aprobada') AS ingresos_totales
");
$metricas = $stmt->fetch() ?: [];

// ── Ingresos mensuales ───────────────────────────────────
$ingresos_meses = []; $ingresos_valores = [];
$stmt = $pdo->query("
    SELECT DATE_FORMAT(fecha_creacion, '%M %Y') AS mes_nombre, COALESCE(SUM(monto_total), 0) AS total_ingresos
    FROM transacciones_pago
    WHERE estado_transaccion = 'aprobada' AND fecha_creacion >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(fecha_creacion, '%Y-%m'), DATE_FORMAT(fecha_creacion, '%M %Y')
    ORDER BY MIN(fecha_creacion) ASC
");
while ($r = $stmt->fetch()) {
    $ingresos_meses[]   = $r['mes_nombre'];
    $ingresos_valores[] = (float)$r['total_ingresos'];
}

// ── Crecimiento usuarios ─────────────────────────────────
$crecimiento_meses = []; $crecimiento_estudiantes = []; $crecimiento_profesores = [];
$stmt = $pdo->query("
    SELECT DATE_FORMAT(fecha_creacion, '%M %Y') AS mes_nombre,
           SUM(CASE WHEN id_rol_fk = 3 THEN 1 ELSE 0 END) AS nuevos_estudiantes,
           SUM(CASE WHEN id_rol_fk = 2 THEN 1 ELSE 0 END) AS nuevos_profesores
    FROM usuarios
    WHERE fecha_creacion >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(fecha_creacion, '%Y-%m'), DATE_FORMAT(fecha_creacion, '%M %Y')
    ORDER BY MIN(fecha_creacion) ASC
");
while ($r = $stmt->fetch()) {
    $crecimiento_meses[]      = $r['mes_nombre'];
    $crecimiento_estudiantes[] = (int)$r['nuevos_estudiantes'];
    $crecimiento_profesores[] = (int)$r['nuevos_profesores'];
}

// ── Progreso académico ───────────────────────────────────
$progreso_labels = []; $progreso_valores = [];
$stmt = $pdo->query("
    SELECT
        COUNT(CASE WHEN porcentaje_progreso >= 100 THEN 1 END) AS completados_al_100,
        COUNT(CASE WHEN porcentaje_progreso >= 75 AND porcentaje_progreso < 100 THEN 1 END) AS entre_75_y_99,
        COUNT(CASE WHEN porcentaje_progreso >= 50 AND porcentaje_progreso < 75 THEN 1 END) AS entre_50_y_74,
        COUNT(CASE WHEN porcentaje_progreso >= 25 AND porcentaje_progreso < 50 THEN 1 END) AS entre_25_y_49,
        COUNT(CASE WHEN porcentaje_progreso > 0 AND porcentaje_progreso < 25 THEN 1 END) AS entre_1_y_24,
        COUNT(CASE WHEN porcentaje_progreso = 0 THEN 1 END) AS sin_iniciar
    FROM inscripciones
    WHERE estado_activo = 1 AND estado_inscripcion IN ('activa', 'completada')
");
if ($r = $stmt->fetch()) {
    $progreso_labels = ['100%', '75\u201399%', '50\u201374%', '25\u201349%', '1\u201324%', 'Sin iniciar'];
    $progreso_valores = [(int)$r['completados_al_100'], (int)$r['entre_75_y_99'], (int)$r['entre_50_y_74'], (int)$r['entre_25_y_49'], (int)$r['entre_1_y_24'], (int)$r['sin_iniciar']];
}

// ── Cursos top ───────────────────────────────────────────
$cursos_top_labels = []; $cursos_top_inscripciones = [];
$stmt = $pdo->query("
    SELECT c.titulo_curso, COUNT(DISTINCT i.id_inscripcion_pk) AS total_inscripciones
    FROM cursos c
    LEFT JOIN inscripciones i ON i.id_curso_fk = c.id_curso_pk AND i.estado_activo = 1
    WHERE c.estado_activo = 1
    GROUP BY c.id_curso_pk, c.titulo_curso
    ORDER BY total_inscripciones DESC
    LIMIT 10
");
while ($r = $stmt->fetch()) {
    $cursos_top_labels[] = $r['titulo_curso'];
    $cursos_top_inscripciones[] = (int)$r['total_inscripciones'];
}
?>
<script>
window.REPORTES_DATA = {
    ingresosMeses: <?= json_encode($ingresos_meses) ?>,
    ingresosValores: <?= json_encode($ingresos_valores) ?>,
    crecimientoMeses: <?= json_encode($crecimiento_meses) ?>,
    crecimientoEstudiantes: <?= json_encode($crecimiento_estudiantes) ?>,
    crecimientoProfesores: <?= json_encode($crecimiento_profesores) ?>,
    progresoLabels: <?= json_encode($progreso_labels) ?>,
    progresoValores: <?= json_encode($progreso_valores) ?>,
    cursosTopLabels: <?= json_encode($cursos_top_labels) ?>,
    cursosTopInscripciones: <?= json_encode($cursos_top_inscripciones) ?>,
};
</script>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h3 fw-bold text-primary mb-1"><i class="fas fa-chart-pie me-2"></i>Reportes y Estadísticas</h1>
        <p class="text-muted m-0">Indicadores clave de rendimiento del sistema educativo.</p>
    </div>
</div>

<!-- ── KPIs ────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="reportes-kpi">
            <div class="reportes-kpi-numero text-primary"><?= number_format((int)($metricas['total_estudiantes'] ?? 0)) ?></div>
            <div class="reportes-kpi-label">Estudiantes</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="reportes-kpi">
            <div class="reportes-kpi-numero text-success"><?= number_format((int)($metricas['inscripciones_activas'] ?? 0)) ?></div>
            <div class="reportes-kpi-label">Inscripciones activas</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="reportes-kpi">
            <div class="reportes-kpi-numero text-warning"><?= MONEDA_SIMBOLO . number_format((float)($metricas['ingresos_totales'] ?? 0), 0) ?></div>
            <div class="reportes-kpi-label">Ingresos totales</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="reportes-kpi">
            <div class="reportes-kpi-numero text-info"><?= number_format((float)($metricas['inscripciones_completadas'] ?? 0), 0) ?></div>
            <div class="reportes-kpi-label">Completados</div>
        </div>
    </div>
</div>

<!-- ── NAV DE PESTAÑAS ────────────────────────────────── -->
<ul class="nav reportes-nav mb-4 gap-1 flex-nowrap overflow-auto" id="reportesTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="tab-financiero" data-bs-toggle="tab" data-bs-target="#panel-financiero" type="button" role="tab"><i class="fas fa-dollar-sign me-1"></i>Financiero</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-usuarios" data-bs-toggle="tab" data-bs-target="#panel-usuarios" type="button" role="tab"><i class="fas fa-users me-1"></i>Usuarios</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-academico" data-bs-toggle="tab" data-bs-target="#panel-academico" type="button" role="tab"><i class="fas fa-graduation-cap me-1"></i>Académico</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="tab-cursos" data-bs-toggle="tab" data-bs-target="#panel-cursos" type="button" role="tab"><i class="fas fa-book me-1"></i>Cursos</button>
    </li>
</ul>

<div class="tab-content reportes-tab-content" id="reportesTabContent">
    <div class="tab-pane fade show active" id="panel-financiero" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#1A3C6E,#2563EB);color:#fff;">
                <span class="fw-bold"><i class="fas fa-chart-line me-2"></i>Ingresos Mensuales</span>
            </div>
            <div class="card-body p-4"><canvas id="chartIngresos" height="280"></canvas></div>
        </div>
    </div>
    <div class="tab-pane fade" id="panel-usuarios" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#1A3C6E,#2563EB);color:#fff;">
                <span class="fw-bold"><i class="fas fa-user-plus me-2"></i>Crecimiento de Usuarios</span>
            </div>
            <div class="card-body p-4"><canvas id="chartCrecimiento" height="280"></canvas></div>
        </div>
    </div>
    <div class="tab-pane fade" id="panel-academico" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#1A3C6E,#2563EB);color:#fff;">
                <span class="fw-bold"><i class="fas fa-chart-pie me-2"></i>Progreso Académico</span>
            </div>
            <div class="card-body p-4"><canvas id="chartProgreso" height="280"></canvas></div>
        </div>
    </div>
    <div class="tab-pane fade" id="panel-cursos" role="tabpanel">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#1A3C6E,#2563EB);color:#fff;">
                <span class="fw-bold"><i class="fas fa-trophy me-2"></i>Cursos Más Populares</span>
            </div>
            <div class="card-body p-4"><canvas id="chartCursosTop" height="280"></canvas></div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
