<?php
// /cursoonline/admin/index.php
// ============================================================
// Dashboard Global del Administrador — EduTech Academy
// PHP: consultas mediante SP, preparación de datos para charts
// ============================================================

$page_title = 'Dashboard Global';
$page_script = 'assets/js/dashboard.js';
$page_css    = 'assets/css/dashboard.css';
require_once __DIR__ . '/includes/header.php';

// ── Helper: ejecutar SP y devolver resultados ─────────────
function sp_query($pdo, $sp_name, $params = []) {
    try {
        $placeholders = [];
        foreach ($params as $k => $v) {
            $placeholders[] = ':' . $k;
        }
        $sql = 'CALL ' . $sp_name . '(' . implode(',', $placeholders) . ')';
        $prev = $pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, $prev);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error en $sp_name: " . $e->getMessage());
        return [];
    }
}

function sp_query_one($pdo, $sp_name, $params = []) {
    $rows = sp_query($pdo, $sp_name, $params);
    return $rows[0] ?? [];
}

// ── 1. Métricas generales ─────────────────────────────────
$metricas = sp_query_one($pdo, 'sp_dashboard_metricas');

// ── 2. Ventas mensuales (chart línea) ─────────────────────
$ventas_mensuales = sp_query($pdo, 'sp_dashboard_ventas_mensuales');
$chart_ventas_meses    = [];
$chart_ventas_valores  = [];
$chart_ventas_count    = [];
foreach ($ventas_mensuales as $v) {
    $chart_ventas_meses[]   = $v['mes_nombre'];
    $chart_ventas_valores[] = (float)$v['total_ingresos'];
    $chart_ventas_count[]   = (int)$v['total_transacciones'];
}

// ── 3. Inscripciones mensuales (chart barras) ─────────────
$insc_mensuales = sp_query($pdo, 'sp_dashboard_inscripciones_mensuales');
$chart_insc_meses  = [];
$chart_insc_valores = [];
foreach ($insc_mensuales as $im) {
    $chart_insc_meses[]   = $im['mes_nombre'];
    $chart_insc_valores[] = (int)$im['total_inscripciones'];
}

// ── 4. Distribución roles (chart pastel) ──────────────────
$dist_roles = sp_query($pdo, 'sp_dashboard_distribucion_roles');
$chart_roles_nombres = [];
$chart_roles_valores = [];
$chart_roles_colores = ['#DC2626', '#2563EB', '#16A34A', '#D97706', '#7C3AED'];
foreach ($dist_roles as $dr) {
    $chart_roles_nombres[] = $dr['nombre_rol'];
    $chart_roles_valores[] = (int)$dr['total_usuarios'];
}

// ── 5. Ingresos por categoría (chart barras) ──────────────
$ing_categorias = sp_query($pdo, 'sp_dashboard_ingresos_por_categoria');
$chart_cat_nombres  = [];
$chart_cat_valores  = [];
$chart_cat_colores  = [];
foreach ($ing_categorias as $ic) {
    $chart_cat_nombres[] = $ic['nombre_categoria'];
    $chart_cat_valores[] = (float)$ic['total_ingresos'];
    $chart_cat_colores[] = $ic['color_categoria'] ?? '#2563EB';
}

// ── 6. Cursos populares ───────────────────────────────────
$cursos_populares = sp_query($pdo, 'sp_dashboard_cursos_populares', ['p_limit' => 5]);

// ── 7. Últimas transacciones ──────────────────────────────
$transacciones = sp_query($pdo, 'sp_dashboard_ultimas_transacciones', ['p_limit' => 8]);

// ── 8. Últimos usuarios ───────────────────────────────────
$ultimos_usuarios = sp_query($pdo, 'sp_dashboard_ultimos_usuarios', ['p_limit' => 5]);

// ── 9. Top estudiantes ────────────────────────────────────
$top_estudiantes = sp_query($pdo, 'sp_dashboard_estudiantes_top', ['p_limit' => 5]);

// ── 10. Estadísticas evaluaciones ─────────────────────────
$stats_eval = sp_query_one($pdo, 'sp_dashboard_estadisticas_evaluaciones');

// ── JSON para JavaScript ──────────────────────────────────
$chart_data = [
    'ventasMeses'   => $chart_ventas_meses,
    'ventasValores' => $chart_ventas_valores,
    'ventasCount'   => $chart_ventas_count,
    'inscMeses'     => $chart_insc_meses,
    'inscValores'   => $chart_insc_valores,
    'rolesNombres'  => $chart_roles_nombres,
    'rolesValores'  => $chart_roles_valores,
    'rolesColores'  => array_slice($chart_roles_colores, 0, count($chart_roles_nombres)),
    'catNombres'    => $chart_cat_nombres,
    'catValores'    => $chart_cat_valores,
    'catColores'    => $chart_cat_colores,
];
?>
<!-- ────────────────────────────────────────────────────────────
     HTML — Dashboard Global del Administrador
     ──────────────────────────────────────────────────────────── -->

<!-- Page Header -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <h1><i class="fas fa-tachometer-alt me-2 text-danger"></i>Dashboard Global</h1>
        <p class="mb-0">Monitoreo general del sistema — métricas, gráficos y accesos rápidos.</p>
    </div>
    <div class="text-muted small" id="clockHeader">
        <i class="fas fa-calendar-alt me-1"></i> <span id="currentDate"></span>
    </div>
</div>

<!-- ── Fila 1: Tarjetas de métricas rápidas ─────────────── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
        <div class="admin-stat-card">
            <div><p>Cursos</p><h3><?= (int)($metricas['total_cursos'] ?? 0) ?></h3></div>
            <div class="admin-stat-icon icon-blue"><i class="fas fa-graduation-cap"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="admin-stat-card">
            <div><p>Usuarios</p><h3><?= (int)($metricas['total_usuarios'] ?? 0) ?></h3></div>
            <div class="admin-stat-icon icon-green"><i class="fas fa-users"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="admin-stat-card">
            <div><p>Estudiantes</p><h3><?= (int)($metricas['total_estudiantes'] ?? 0) ?></h3></div>
            <div class="admin-stat-icon icon-purple"><i class="fas fa-user-graduate"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="admin-stat-card">
            <div><p>Inscriptos</p><h3><?= (int)($metricas['inscripciones_activas'] ?? 0) ?></h3></div>
            <div class="admin-stat-icon icon-amber"><i class="fas fa-book-open"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="admin-stat-card">
            <div><p>Facturación</p><h3 style="font-size:1.1rem;">$<?= number_format((float)($metricas['total_ventas'] ?? 0), 0, ',', '.') ?></h3></div>
            <div class="admin-stat-icon icon-green"><i class="fas fa-dollar-sign"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="admin-stat-card">
            <div><p>Nuevos/mes</p><h3><?= (int)($metricas['nuevos_este_mes'] ?? 0) ?></h3></div>
            <div class="admin-stat-icon icon-red"><i class="fas fa-user-plus"></i></div>
        </div>
    </div>
</div>

<!-- ── Fila 2: Reportes rápidos (mini cards adicionales) ── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="d-flex align-items-center gap-3 p-3 bg-white rounded-3 border" style="border-color:#E2E8F0;">
            <div style="width:42px;height:42px;border-radius:12px;background:rgba(37,99,235,0.1);display:flex;align-items:center;justify-content:center;color:#2563EB;font-size:1.1rem;flex-shrink:0;"><i class="fas fa-chalkboard-teacher"></i></div>
            <div><span class="text-muted d-block" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;">Profesores</span><span class="fw-bold fs-5"><?= (int)($metricas['total_profesores'] ?? 0) ?></span></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="d-flex align-items-center gap-3 p-3 bg-white rounded-3 border" style="border-color:#E2E8F0;">
            <div style="width:42px;height:42px;border-radius:12px;background:rgba(22,163,74,0.1);display:flex;align-items:center;justify-content:center;color:#16A34A;font-size:1.1rem;flex-shrink:0;"><i class="fas fa-file-signature"></i></div>
            <div><span class="text-muted d-block" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;">Evaluaciones</span><span class="fw-bold fs-5"><?= (int)($metricas['total_evaluaciones'] ?? 0) ?></span></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="d-flex align-items-center gap-3 p-3 bg-white rounded-3 border" style="border-color:#E2E8F0;">
            <div style="width:42px;height:42px;border-radius:12px;background:rgba(217,119,6,0.1);display:flex;align-items:center;justify-content:center;color:#D97706;font-size:1.1rem;flex-shrink:0;"><i class="fas fa-check-circle"></i></div>
            <div><span class="text-muted d-block" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;">Eval. Aprobadas</span><span class="fw-bold fs-5"><?= (int)($stats_eval['intentos_aprobados'] ?? 0) ?></span></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="d-flex align-items-center gap-3 p-3 bg-white rounded-3 border" style="border-color:#E2E8F0;">
            <div style="width:42px;height:42px;border-radius:12px;background:rgba(220,38,38,0.1);display:flex;align-items:center;justify-content:center;color:#DC2626;font-size:1.1rem;flex-shrink:0;"><i class="fas fa-shield-alt"></i></div>
            <div><span class="text-muted d-block" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.5px;">Bloqueados</span><span class="fw-bold fs-5"><?= (int)($metricas['intentos_bloqueados'] ?? 0) ?></span></div>
        </div>
    </div>
</div>

<!-- ── Fila 3: Gráficos ──────────────────────────────────── -->
<div class="row g-4 mb-4">
    <!-- Ventas mensuales (línea) -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="admin-card-header"><span><i class="fas fa-chart-line me-2 text-primary"></i>Ventas Mensuales</span></div>
            <div class="admin-card-body">
                <canvas id="chartVentas" height="200"></canvas>
            </div>
        </div>
    </div>
    <!-- Inscripciones mensuales (barras) -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="admin-card-header"><span><i class="fas fa-chart-bar me-2 text-success"></i>Inscripciones por Mes</span></div>
            <div class="admin-card-body">
                <canvas id="chartInscripciones" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Distribución por roles (pastel) -->
    <div class="col-lg-5">
        <div class="admin-card">
            <div class="admin-card-header"><span><i class="fas fa-chart-pie me-2 text-info"></i>Usuarios por Rol</span></div>
            <div class="admin-card-body">
                <canvas id="chartRoles" height="220"></canvas>
            </div>
        </div>
    </div>
    <!-- Ingresos por categoría (barras) -->
    <div class="col-lg-7">
        <div class="admin-card">
            <div class="admin-card-header"><span><i class="fas fa-chart-simple me-2 text-warning"></i>Ingresos por Categoría</span></div>
            <div class="admin-card-body">
                <canvas id="chartCategorias" height="220"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ── Fila 4: Tablas y listas ───────────────────────────── -->
<div class="row g-4">
    <div class="col-lg-8">
        <!-- Últimas transacciones -->
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="fas fa-receipt me-2"></i>Últimas Transacciones</span>
                <a href="pagos/index.php" class="btn-admin-outline btn-sm">Ver todas</a>
            </div>
            <div class="table-responsive">
                <?php if (empty($transacciones)): ?>
                    <div class="text-center py-4 text-muted small"><i class="fas fa-inbox me-2"></i>Sin transacciones aún.</div>
                <?php else: ?>
                <table class="table-custom w-100">
                    <thead>
                        <tr>
                            <th class="ps-4">Referencia</th>
                            <th>Estudiante</th>
                            <th>Curso</th>
                            <th>Monto</th>
                            <th>Medio Pago</th>
                            <th class="pe-4">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transacciones as $tx): ?>
                        <tr>
                            <td class="ps-4 text-muted small fw-bold"><?= sanitizar_html($tx['numero_referencia'] ?? '—') ?></td>
                            <td>
                                <div class="fw-semibold small"><?= sanitizar_html($tx['primer_nombre'] . ' ' . $tx['primer_apellido']) ?></div>
                                <div class="text-muted" style="font-size:.7rem;"><?= sanitizar_html($tx['correo_electronico'] ?? '') ?></div>
                            </td>
                            <td class="small"><?= sanitizar_html(mb_strimwidth($tx['titulo_curso'] ?? '', 0, 35, '...')) ?></td>
                            <td class="fw-bold">$<?= number_format((float)$tx['monto_total'], 0, ',', '.') ?></td>
                            <td class="small text-muted"><?= sanitizar_html($tx['nombre_medio_pago'] ?? '—') ?></td>
                            <td class="pe-4">
                                <span class="badge-admin <?= $tx['estado_transaccion'] === 'aprobada' ? 'aprobado' : ($tx['estado_transaccion'] === 'rechazada' ? 'rechazado' : 'pendiente') ?>">
                                    <?= sanitizar_html($tx['estado_transaccion']) ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cursos populares -->
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="fas fa-fire me-2"></i>Cursos Más Populares</span>
                <a href="cursos/index.php" class="btn-admin-outline btn-sm">Gestionar</a>
            </div>
            <div class="admin-card-body p-0">
                <?php if (empty($cursos_populares)): ?>
                    <div class="text-center py-4 text-muted small">Sin datos de cursos aún.</div>
                <?php else: ?>
                    <?php foreach ($cursos_populares as $i => $cp): ?>
                    <div class="d-flex align-items-center gap-3 p-3 border-bottom">
                        <div class="fw-bold text-muted" style="width:24px;text-align:center;font-size:.85rem;"><?= $i + 1 ?></div>
                        <div style="width:6px;height:40px;background:<?= sanitizar_html($cp['color_categoria'] ?? '#2563EB') ?>;border-radius:3px;flex-shrink:0;"></div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold small text-truncate"><?= sanitizar_html($cp['titulo_curso']) ?></div>
                            <div class="text-muted" style="font-size:.75rem;"><?= sanitizar_html($cp['nombre_categoria'] ?? '—') ?></div>
                        </div>
                        <div class="text-end flex-shrink-0">
                            <div class="fw-bold small"><?= (int)$cp['total_inscripciones'] ?> est.</div>
                            <div style="font-size:.75rem;color:#16A34A;">
                                <?php if ((float)$cp['precio'] > 0): ?>
                                    $<?= number_format((float)$cp['precio'], 0, ',', '.') ?>
                                <?php else: ?>
                                    Gratis
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Nuevos usuarios -->
        <div class="admin-card mb-3">
            <div class="admin-card-header">
                <span><i class="fas fa-user-plus me-2"></i>Últimos Registros</span>
                <a href="usuarios/index.php" class="btn-admin-outline btn-sm">Ver todos</a>
            </div>
            <div class="admin-card-body p-0">
                <?php foreach ($ultimos_usuarios as $uu): ?>
                <div class="d-flex align-items-center gap-2 p-3 border-bottom">
                    <?php if (!empty($uu['foto_perfil'])): ?>
                        <img src="<?= sanitizar_html($uu['foto_perfil']) ?>" alt="" style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                    <?php else: ?>
                        <div class="user-avatar flex-shrink-0"><?= strtoupper(substr($uu['primer_nombre'], 0, 1) . substr($uu['primer_apellido'], 0, 1)) ?></div>
                    <?php endif; ?>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="fw-semibold small text-truncate"><?= sanitizar_html($uu['primer_nombre'] . ' ' . $uu['primer_apellido']) ?></div>
                        <div class="text-muted" style="font-size:.72rem;"><?= date('d/m/Y', strtotime($uu['fecha_registro'])) ?></div>
                    </div>
                    <?php
                    $rol_cls = strtolower($uu['nombre_rol'] ?? '');
                    $rol_lbl = $uu['nombre_rol'] ?? '?';
                    ?>
                    <span class="badge-admin <?= $rol_cls ?>"><?= sanitizar_html($rol_lbl) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Top estudiantes -->
        <?php if (!empty($top_estudiantes)): ?>
        <div class="admin-card mb-3">
            <div class="admin-card-header"><span><i class="fas fa-trophy me-2 text-warning"></i>Mejores Estudiantes</span></div>
            <div class="admin-card-body p-0">
                <?php foreach ($top_estudiantes as $i => $te): ?>
                <div class="d-flex align-items-center gap-2 p-3 border-bottom">
                    <div class="fw-bold" style="width:20px;color:<?= $i === 0 ? '#D97706' : ($i === 1 ? '#94A3B8' : ($i === 2 ? '#CD7F32' : '#CBD5E1')) ?>;font-size:.85rem;"><?= $i + 1 ?></div>
                    <?php if (!empty($te['foto_perfil'])): ?>
                        <img src="<?= sanitizar_html($te['foto_perfil']) ?>" alt="" style="width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                    <?php else: ?>
                        <div class="user-avatar flex-shrink-0" style="width:32px;height:32px;font-size:.7rem;"><?= strtoupper(substr($te['primer_nombre'], 0, 1) . substr($te['primer_apellido'], 0, 1)) ?></div>
                    <?php endif; ?>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="small text-truncate fw-semibold"><?= sanitizar_html($te['primer_nombre'] . ' ' . $te['primer_apellido']) ?></div>
                        <div class="text-muted" style="font-size:.7rem;"><?= (int)$te['cursos_inscritos'] ?> cursos · <?= (float)$te['progreso_promedio'] ?>% avance</div>
                    </div>
                    <?php if ((int)$te['cursos_completados'] > 0): ?>
                    <span class="badge-admin aprobado" style="font-size:.65rem;"><?= (int)$te['cursos_completados'] ?> fin.</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Acciones rápidas -->
        <div class="admin-card">
            <div class="admin-card-header"><span><i class="fas fa-bolt me-2"></i>Acciones Rápidas</span></div>
            <div class="admin-card-body d-flex flex-column gap-2">
                <a href="cursos/crear.php" class="btn-admin-primary w-100 justify-content-center"><i class="fas fa-plus-circle"></i> Nuevo Curso</a>
                <a href="usuarios/crear.php" class="btn-admin-blue w-100 justify-content-center"><i class="fas fa-user-plus"></i> Nuevo Usuario</a>
                <a href="pagos/index.php" class="btn-admin-outline w-100 justify-content-center"><i class="fas fa-cash-register"></i> Gestionar Pagos</a>
                <a href="<?= BASE_URL ?>index.php" target="_blank" class="btn btn-outline-secondary w-100 btn-sm rounded-3"><i class="fas fa-external-link-alt me-1"></i> Ver Sitio Público</a>
            </div>
        </div>
    </div>
</div>

<!-- ── Datos JSON para los charts ─────────────────────────── -->
<script>
var DASHBOARD_DATA = <?= json_encode($chart_data) ?>;
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
