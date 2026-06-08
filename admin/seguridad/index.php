<?php
$page_title = 'Auditoría y Seguridad';
$page_css   = '../assets/css/seguridad.css';
$page_script = '../assets/js/seguridad.js';
require_once __DIR__ . '/../includes/header.php';

// ── Estadísticas de seguridad ───────────────────────────
$est_seg = [];
$stmt = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM log_accesos WHERE tipo_accion = 'login_exitoso') AS logins_exitosos,
        (SELECT COUNT(*) FROM log_accesos WHERE tipo_accion = 'login_fallido') AS logins_fallidos,
        (SELECT COUNT(*) FROM log_actividad_usuario) AS total_acciones_auditadas,
        (SELECT COUNT(*) FROM log_errores_sistema) AS total_errores,
        (SELECT COUNT(*) FROM ips_bloqueadas WHERE estado_activo = 1 AND (fecha_desbloqueo IS NULL OR fecha_desbloqueo > NOW())) AS ips_bloqueadas_activas,
        (SELECT COUNT(*) FROM log_intentos_fallidos WHERE estado_bloqueado = 1) AS cuentas_bloqueadas
");
$est_seg = $stmt->fetch() ?: [];

// ── Accesos diarios (30 días) ───────────────────────────
$accesos_dias = []; $accesos_exitosos = []; $accesos_fallidos = [];
$stmt = $pdo->query("
    SELECT DATE(fecha_acceso) AS dia,
           SUM(CASE WHEN tipo_accion = 'login_exitoso' THEN 1 ELSE 0 END) AS exitosos,
           SUM(CASE WHEN tipo_accion = 'login_fallido' THEN 1 ELSE 0 END) AS fallidos
    FROM log_accesos
    WHERE fecha_acceso >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(fecha_acceso)
    ORDER BY dia ASC
");
while ($r = $stmt->fetch()) {
    $accesos_dias[]      = date('d/m', strtotime($r['dia']));
    $accesos_exitosos[]  = (int)$r['exitosos'];
    $accesos_fallidos[]  = (int)$r['fallidos'];
}

// ── Errores diarios (30 días) ───────────────────────────
$errores_dias = []; $errores_valores = [];
$stmt = $pdo->query("
    SELECT DATE(fecha_error) AS dia, COUNT(*) AS total_errores
    FROM log_errores_sistema
    WHERE fecha_error >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(fecha_error)
    ORDER BY dia ASC
");
while ($r = $stmt->fetch()) {
    $errores_dias[]    = date('d/m', strtotime($r['dia']));
    $errores_valores[] = (int)$r['total_errores'];
}

// ── Tipos de acción ─────────────────────────────────────
$accion_labels = []; $accion_valores = [];
$stmt = $pdo->query("
    SELECT tipo_accion, COUNT(*) AS total
    FROM log_actividad_usuario
    GROUP BY tipo_accion
    ORDER BY total DESC
    LIMIT 5
");
while ($r = $stmt->fetch()) {
    $accion_labels[] = $r['tipo_accion'];
    $accion_valores[] = (int)$r['total'];
}

// ── Top actividad usuarios ──────────────────────────────
$top_user_labels = []; $top_user_valores = [];
$stmt = $pdo->query("
    SELECT u.id_usuario_pk, u.primer_nombre, u.primer_apellido,
           COUNT(l.id_log_acceso_pk) AS total_accesos
    FROM log_accesos l
    JOIN usuarios u ON u.id_usuario_pk = l.id_usuario_fk
    WHERE l.id_usuario_fk IS NOT NULL
    GROUP BY u.id_usuario_pk, u.primer_nombre, u.primer_apellido
    ORDER BY total_accesos DESC
    LIMIT 8
");
while ($r = $stmt->fetch()) {
    $top_user_labels[] = $r['primer_nombre'] . ' ' . substr($r['primer_apellido'], 0, 1) . '.';
    $top_user_valores[] = (int)$r['total_accesos'];
}

// ── Alertas ──────────────────────────────────────────────
$alertas = [];
$stmt = $pdo->query("
    SELECT 'login_fallido' AS tipo_alerta, COUNT(*) AS total, 'Intentos de login fallidos en la \u00faltima hora' AS descripcion
    FROM log_accesos WHERE tipo_accion = 'login_fallido' AND fecha_acceso >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    UNION ALL
    SELECT 'error_critico', COUNT(*), 'Errores del sistema en la \u00faltima hora'
    FROM log_errores_sistema WHERE fecha_error >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    UNION ALL
    SELECT 'ip_bloqueada', COUNT(*), 'IPs bloqueadas actualmente'
    FROM ips_bloqueadas WHERE estado_activo = 1 AND (fecha_desbloqueo IS NULL OR fecha_desbloqueo > NOW())
    UNION ALL
    SELECT 'cuenta_bloqueada', COUNT(*), 'Cuentas bloqueadas por intentos fallidos'
    FROM log_intentos_fallidos WHERE estado_bloqueado = 1
");
$alertas = $stmt->fetchAll();

// ── Actividad reciente (timeline) ────────────────────────
$actividad_reciente = [];
$stmt = $pdo->query("
    SELECT la.*, u.primer_nombre, u.primer_apellido, r.nombre_rol
    FROM log_actividad_usuario la
    JOIN usuarios u ON la.id_usuario_fk = u.id_usuario_pk
    JOIN roles r ON u.id_rol_fk = r.id_rol_pk
    ORDER BY la.fecha_actividad DESC
    LIMIT 10
");
$actividad_reciente = $stmt->fetchAll();

// ── IPs bloqueadas ────────────────────────────────────────
$ips_bloqueadas = [];
$stmt = $pdo->query("
    SELECT ib.*, u.primer_nombre, u.primer_apellido
    FROM ips_bloqueadas ib
    LEFT JOIN usuarios u ON u.id_usuario_pk = ib.id_usuario_bloqueo_fk
    WHERE ib.estado_activo = 1
    ORDER BY ib.fecha_bloqueo DESC
");
$ips_bloqueadas = $stmt->fetchAll();

// ── Accesos recientes ────────────────────────────────────
$accesos_recientes = [];
$stmt = $pdo->query("
    SELECT a.*, u.primer_nombre, u.primer_apellido, r.nombre_rol
    FROM log_accesos a
    LEFT JOIN usuarios u ON a.id_usuario_fk = u.id_usuario_pk
    LEFT JOIN roles r ON u.id_rol_fk = r.id_rol_pk
    ORDER BY a.fecha_acceso DESC
    LIMIT 15
");
$accesos_recientes = $stmt->fetchAll();

// ── Errores recientes ────────────────────────────────────
$errores_recientes = [];
$stmt = $pdo->query("
    SELECT * FROM log_errores_sistema
    ORDER BY fecha_error DESC
    LIMIT 15
");
$errores_recientes = $stmt->fetchAll();
?>
<script>
window.SEGURIDAD_DATA = {
    accesosDias: <?= json_encode($accesos_dias) ?>,
    accesosExitosos: <?= json_encode($accesos_exitosos) ?>,
    accesosFallidos: <?= json_encode($accesos_fallidos) ?>,
    accionLabels: <?= json_encode($accion_labels) ?>,
    accionValores: <?= json_encode($accion_valores) ?>,
    erroresDias: <?= json_encode($errores_dias) ?>,
    erroresValores: <?= json_encode($errores_valores) ?>,
    topUserLabels: <?= json_encode($top_user_labels) ?>,
    topUserValores: <?= json_encode($top_user_valores) ?>,
};
</script>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h3 fw-bold text-primary mb-1"><i class="fas fa-shield-alt me-2"></i>Auditoría y Seguridad</h1>
        <p class="text-muted m-0">Monitoreo de accesos, actividad del sistema y alertas de seguridad.</p>
    </div>
</div>

<!-- ── KPIs ────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="seguridad-kpi">
            <div class="kpi-number text-success"><?= number_format((int)($est_seg['logins_exitosos'] ?? 0)) ?></div>
            <div class="kpi-label">Logins exitosos</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="seguridad-kpi">
            <div class="kpi-number text-danger"><?= number_format((int)($est_seg['logins_fallidos'] ?? 0)) ?></div>
            <div class="kpi-label">Logins fallidos</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="seguridad-kpi">
            <div class="kpi-number text-warning"><?= number_format((int)($est_seg['total_errores'] ?? 0)) ?></div>
            <div class="kpi-label">Errores del sistema</div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="seguridad-kpi">
            <div class="kpi-number text-danger"><?= number_format((int)($est_seg['ips_bloqueadas_activas'] ?? 0)) ?></div>
            <div class="kpi-label">IPs bloqueadas</div>
        </div>
    </div>
</div>

<!-- ── ALERTAS ─────────────────────────────────────────── -->
<?php if (!empty($alertas)): ?>
<div class="row g-3 mb-4">
    <?php foreach ($alertas as $a):
        $nivel = 'baja';
        if ($a['tipo_alerta'] === 'login_fallido' && (int)$a['total'] > 5) $nivel = 'alta';
        elseif ($a['tipo_alerta'] === 'error_critico') $nivel = 'critica';
        elseif ($a['tipo_alerta'] === 'cuenta_bloqueada') $nivel = 'alta';
        $iconos = ['login_fallido' => 'fa-lock', 'error_critico' => 'fa-bug', 'ip_bloqueada' => 'fa-ban', 'cuenta_bloqueada' => 'fa-user-lock'];
    ?>
    <div class="col-lg-3 col-md-6">
        <div class="alerta-card <?= $nivel ?>">
            <div class="d-flex align-items-center gap-3">
                <div class="alerta-card-icon"><i class="fas <?= $iconos[$a['tipo_alerta']] ?? 'fa-shield' ?>"></i></div>
                <div>
                    <div class="alerta-numero"><?= (int)$a['total'] ?></div>
                    <div class="alerta-label"><?= sanitizar_html($a['descripcion']) ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── CHARTS ──────────────────────────────────────────── -->
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#1A3C6E,#2563EB);color:#fff;">
                <span class="fw-bold"><i class="fas fa-sign-in-alt me-2"></i>Accesos diarios (30 d\u00edas)</span>
            </div>
            <div class="card-body p-4"><canvas id="chartAccesos" height="250"></canvas></div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#1A3C6E,#2563EB);color:#fff;">
                <span class="fw-bold"><i class="fas fa-tasks me-2"></i>Tipos de acci\u00f3n</span>
            </div>
            <div class="card-body p-4 d-flex align-items-center"><canvas id="chartAccion" height="200"></canvas></div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#1A3C6E,#2563EB);color:#fff;">
                <span class="fw-bold"><i class="fas fa-bug me-2"></i>Errores diarios</span>
            </div>
            <div class="card-body p-4 d-flex align-items-center"><canvas id="chartErrores" height="200"></canvas></div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#1A3C6E,#2563EB);color:#fff;">
                <span class="fw-bold"><i class="fas fa-user-chart me-2"></i>Top actividad</span>
            </div>
            <div class="card-body p-4 d-flex align-items-center"><canvas id="chartTopActividad" height="250"></canvas></div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#1A3C6E,#2563EB);color:#fff;">
                <span class="fw-bold"><i class="fas fa-clock me-2"></i>Actividad reciente</span>
            </div>
            <div class="card-body p-4" style="max-height:300px;overflow-y:auto;">
                <?php if (empty($actividad_reciente)): ?>
                <p class="text-muted text-center py-4">No hay actividad registrada a\u00fan.</p>
                <?php else: ?>
                <div class="timeline">
                    <?php foreach ($actividad_reciente as $act):
                        $bullet = 'success';
                        if (strpos($act['tipo_accion'], 'error') !== false || strpos($act['tipo_accion'], 'fallido') !== false) $bullet = 'danger';
                        elseif (strpos($act['tipo_accion'], 'elimin') !== false) $bullet = 'warning';
                    ?>
                    <div class="timeline-item">
                        <div class="timeline-bullet <?= $bullet ?>"></div>
                        <div class="timeline-content">
                            <strong><?= sanitizar_html($act['primer_nombre'] . ' ' . $act['primer_apellido']) ?></strong>
                            <span class="text-muted">(<?= sanitizar_html($act['nombre_rol']) ?>)</span>
                            <span class="badge badge-log accion"><?= sanitizar_html($act['tipo_accion']) ?></span>
                            <div class="tl-time"><?= date('d/m/Y H:i', strtotime($act['fecha_actividad'])) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ── ACCESOS RECIENTES ───────────────────────────────── -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header border-0 py-3 px-4 d-flex justify-content-between align-items-center" style="background:linear-gradient(135deg,#1A3C6E,#2563EB);color:#fff;">
        <span class="fw-bold"><i class="fas fa-list me-2"></i>Registro de Accesos</span>
        <span class="badge bg-white text-primary fw-bold px-3 py-1 rounded-pill"><?= count($accesos_recientes) ?> registros</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 log-table">
            <thead style="background:#F8FAFC;font-size:.75rem;color:#64748B;text-transform:uppercase;letter-spacing:.5px;">
                <tr>
                    <th class="px-4 py-3">Usuario</th>
                    <th class="py-3">Rol</th>
                    <th class="py-3">Acci\u00f3n</th>
                    <th class="py-3">Direcci\u00f3n IP</th>
                    <th class="py-3">Dispositivo</th>
                    <th class="py-3">Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($accesos_recientes)): ?>
                <tr><td colspan="6" class="text-center py-4 text-muted">No hay accesos registrados.</td></tr>
                <?php else: foreach ($accesos_recientes as $a): ?>
                <tr>
                    <td class="px-4 py-3 fw-semibold"><?= sanitizar_html($a['primer_nombre'] . ' ' . $a['primer_apellido']) ?></td>
                    <td class="py-3"><span class="badge bg-secondary"><?= sanitizar_html($a['nombre_rol']) ?></span></td>
                    <td class="py-3">
                        <span class="badge badge-log <?= strpos($a['tipo_accion'], 'exitoso') !== false ? 'exitoso' : 'fallido' ?>"><?= sanitizar_html($a['tipo_accion']) ?></span>
                    </td>
                    <td class="py-3"><span class="ip-badge"><?= sanitizar_html($a['direccion_ip']) ?></span></td>
                    <td class="py-3 small text-muted" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= sanitizar_html($a['agente_navegador']) ?>"><?= sanitizar_html(substr($a['agente_navegador'], 0, 60)) ?></td>
                    <td class="py-3 small text-muted"><?= date('d/m/Y H:i', strtotime($a['fecha_acceso'])) ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── IPS BLOQUEADAS ──────────────────────────────────── -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header border-0 py-3 px-4 d-flex justify-content-between align-items-center" style="background:linear-gradient(135deg,#1A3C6E,#2563EB);color:#fff;">
        <span class="fw-bold"><i class="fas fa-ban me-2"></i>IPs Bloqueadas</span>
        <span class="badge bg-warning text-dark fw-bold px-3 py-1 rounded-pill"><?= count($ips_bloqueadas) ?> bloqueo(s)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 log-table">
            <thead style="background:#F8FAFC;font-size:.75rem;color:#64748B;text-transform:uppercase;letter-spacing:.5px;">
                <tr>
                    <th class="px-4 py-3">Direcci\u00f3n IP</th>
                    <th class="py-3">Motivo</th>
                    <th class="py-3">Bloqueado por</th>
                    <th class="py-3">Fecha bloqueo</th>
                    <th class="py-3">Desbloqueo</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ips_bloqueadas)): ?>
                <tr><td colspan="5" class="text-center py-4 text-muted">No hay IPs bloqueadas.</td></tr>
                <?php else: foreach ($ips_bloqueadas as $ip): ?>
                <tr>
                    <td class="px-4 py-3"><span class="ip-badge"><?= sanitizar_html($ip['direccion_ip']) ?></span></td>
                    <td class="py-3 small"><?= sanitizar_html($ip['motivo_bloqueo'] ?? '—') ?></td>
                    <td class="py-3 fw-semibold"><?= sanitizar_html(($ip['primer_nombre'] ?? 'Sistema') . ' ' . ($ip['primer_apellido'] ?? '')) ?></td>
                    <td class="py-3 small text-muted"><?= date('d/m/Y H:i', strtotime($ip['fecha_bloqueo'])) ?></td>
                    <td class="py-3 small text-muted"><?= $ip['fecha_desbloqueo'] ? date('d/m/Y H:i', strtotime($ip['fecha_desbloqueo'])) : 'Permanente' ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ── ERRORES RECIENTES ───────────────────────────────── -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header border-0 py-3 px-4 d-flex justify-content-between align-items-center" style="background:linear-gradient(135deg,#1A3C6E,#2563EB);color:#fff;">
        <span class="fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Errores del Sistema</span>
        <span class="badge bg-danger fw-bold px-3 py-1 rounded-pill"><?= count($errores_recientes) ?> error(es)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 log-table">
            <thead style="background:#F8FAFC;font-size:.75rem;color:#64748B;text-transform:uppercase;letter-spacing:.5px;">
                <tr>
                    <th class="px-4 py-3">Tipo</th>
                    <th class="py-3">Mensaje</th>
                    <th class="py-3">Archivo</th>
                    <th class="py-3">L\u00ednea</th>
                    <th class="py-3">IP</th>
                    <th class="py-3">Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($errores_recientes)): ?>
                <tr><td colspan="6" class="text-center py-4 text-muted">No hay errores registrados.</td></tr>
                <?php else: foreach ($errores_recientes as $err): ?>
                <tr>
                    <td class="px-4 py-3"><span class="badge badge-log error"><?= sanitizar_html($err['tipo_error']) ?></span></td>
                    <td class="py-3 small" style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= sanitizar_html($err['mensaje_error']) ?>"><?= sanitizar_html($err['mensaje_error']) ?></td>
                    <td class="py-3 small text-muted"><?= sanitizar_html(basename($err['archivo_error'] ?? '—')) ?></td>
                    <td class="py-3 small text-muted"><?= (int)($err['linea_error'] ?? 0) ?></td>
                    <td class="py-3"><span class="ip-badge"><?= sanitizar_html($err['direccion_ip'] ?? '—') ?></span></td>
                    <td class="py-3 small text-muted"><?= date('d/m/Y H:i', strtotime($err['fecha_error'])) ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
