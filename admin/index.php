<?php
// /cursoonline/admin/index.php
// ============================================================
// Dashboard Global del Administrador — EduTech Academy
// Admin ahora en: cursoonline/admin/
// ============================================================

$page_title = 'Dashboard Global';
require_once __DIR__ . '/includes/header.php';

// ── Métricas clave ────────────────────────────────────────────
$total_cursos       = (int)$pdo->query("SELECT COUNT(*) FROM cursos WHERE estado_activo = 1")->fetchColumn();
$total_usuarios     = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE estado_activo = 1")->fetchColumn();
$total_estudiantes  = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE id_rol_fk = " . ROL_ESTUDIANTE . " AND estado_activo = 1")->fetchColumn();

// Facturación total aprobada
$total_ventas_raw = $pdo->query("SELECT COALESCE(SUM(monto_total),0) FROM transacciones_pago WHERE estado_transaccion = 'aprobada'")->fetchColumn();
$total_ventas = (float)$total_ventas_raw;

// Nuevos usuarios este mes
$nuevos_mes = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE MONTH(fecha_registro) = MONTH(NOW()) AND YEAR(fecha_registro) = YEAR(NOW())")->fetchColumn();

// Intentos de login fallidos bloqueados
try {
    $bloqueados = (int)$pdo->query("SELECT COUNT(*) FROM log_intentos_fallidos WHERE estado_bloqueado = 1")->fetchColumn();
} catch (PDOException $e) { $bloqueados = 0; }

// Últimas 8 transacciones
try {
    $stmt_txs = $pdo->query("
        SELECT t.numero_referencia, t.monto_total, t.estado_transaccion, t.fecha_transaccion,
               c.titulo_curso, u.primer_nombre, u.primer_apellido
        FROM transacciones_pago t
        INNER JOIN cursos c ON c.id_curso_pk = t.id_curso_fk
        INNER JOIN usuarios u ON u.id_usuario_pk = t.id_usuario_fk
        ORDER BY t.fecha_transaccion DESC LIMIT 8
    ");
    $transacciones = $stmt_txs->fetchAll();
} catch (PDOException $e) { $transacciones = []; }

// Últimos 5 usuarios registrados
$ultimos_usuarios = $pdo->query("
    SELECT u.primer_nombre, u.primer_apellido, u.correo_electronico,
           u.id_rol_fk, u.fecha_registro
    FROM usuarios u
    ORDER BY u.fecha_registro DESC LIMIT 5
")->fetchAll();

// Cursos más populares
$cursos_populares = $pdo->query("
    SELECT c.titulo_curso, c.precio, cat.nombre_categoria, cat.color_categoria,
           COUNT(ei.id_usuario_fk) AS inscritos
    FROM cursos c
    LEFT JOIN categorias_curso cat ON cat.id_categoria_pk = c.id_categoria_fk
    LEFT JOIN grupos g ON g.id_curso_fk = c.id_curso_pk
    LEFT JOIN enrollments_inscripciones ei ON ei.id_grupo_fk = g.id_grupo_pk
    WHERE c.estado_activo = 1
    GROUP BY c.id_curso_pk, c.titulo_curso, c.precio, cat.nombre_categoria, cat.color_categoria
    ORDER BY inscritos DESC LIMIT 5
")->fetchAll();
?>

<!-- ── Page Header ─────────────────────────────────────────── -->
<div class="page-header">
    <h1><i class="fas fa-tachometer-alt me-2 text-danger"></i>Dashboard Global</h1>
    <p>Monitoreo en tiempo real de métricas comerciales, académicas y de seguridad.</p>
</div>

<!-- ── Stat Cards ──────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
        <div class="admin-stat-card">
            <div><p>Cursos</p><h3><?= $total_cursos ?></h3></div>
            <div class="admin-stat-icon icon-blue"><i class="fas fa-graduation-cap"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="admin-stat-card">
            <div><p>Usuarios</p><h3><?= $total_usuarios ?></h3></div>
            <div class="admin-stat-icon icon-green"><i class="fas fa-users"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="admin-stat-card">
            <div><p>Estudiantes</p><h3><?= $total_estudiantes ?></h3></div>
            <div class="admin-stat-icon icon-purple"><i class="fas fa-user-graduate"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="admin-stat-card">
            <div><p>Nuevos/mes</p><h3><?= $nuevos_mes ?></h3></div>
            <div class="admin-stat-icon icon-amber"><i class="fas fa-user-plus"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="admin-stat-card">
            <div><p>Facturación</p><h3 style="font-size:1.1rem;">$<?= number_format($total_ventas, 0, ',', '.') ?></h3></div>
            <div class="admin-stat-icon icon-green"><i class="fas fa-dollar-sign"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="admin-stat-card">
            <div><p>Bloqueados</p><h3><?= $bloqueados ?></h3></div>
            <div class="admin-stat-icon icon-red"><i class="fas fa-shield-alt"></i></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- ── Últimas Transacciones ──────────────────────────── -->
    <div class="col-lg-8">
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
                            <th class="pe-4">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transacciones as $tx): ?>
                        <tr>
                            <td class="ps-4 text-muted small fw-bold"><?= sanitizar_html($tx['numero_referencia'] ?? '—') ?></td>
                            <td><?= sanitizar_html($tx['primer_nombre'] . ' ' . $tx['primer_apellido']) ?></td>
                            <td class="small"><?= sanitizar_html(mb_strimwidth($tx['titulo_curso'], 0, 30, '...')) ?></td>
                            <td class="fw-bold">$<?= number_format((float)$tx['monto_total'], 0, ',', '.') ?></td>
                            <td class="pe-4">
                                <span class="badge-admin <?= $tx['estado_transaccion'] === 'aprobada' ? 'aprobado' : 'pendiente' ?>">
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

        <!-- Cursos más populares -->
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="fas fa-fire me-2"></i>Cursos Más Populares</span>
                <a href="cursos/index.php" class="btn-admin-outline btn-sm">Gestionar</a>
            </div>
            <div class="admin-card-body p-0">
                <?php if (empty($cursos_populares)): ?>
                    <div class="text-center py-4 text-muted small p-3">Sin datos de cursos aún.</div>
                <?php else: ?>
                    <?php foreach ($cursos_populares as $i => $cp): ?>
                    <div class="d-flex align-items-center gap-3 p-3 border-bottom">
                        <div class="fw-bold text-muted" style="width:24px;text-align:center;"><?= $i + 1 ?></div>
                        <div style="width:6px;height:40px;background:<?= sanitizar_html($cp['color_categoria'] ?? '#2563EB') ?>;border-radius:3px;flex-shrink:0;"></div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold small"><?= sanitizar_html($cp['titulo_curso']) ?></div>
                            <div class="text-muted" style="font-size:0.75rem;"><?= sanitizar_html($cp['nombre_categoria'] ?? '—') ?></div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold small"><?= (int)$cp['inscritos'] ?> est.</div>
                            <div class="text-success" style="font-size:0.75rem;">$<?= number_format((float)$cp['precio'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── Panel Derecho ──────────────────────────────────── -->
    <div class="col-lg-4">
        <!-- Últimos usuarios -->
        <div class="admin-card mb-3">
            <div class="admin-card-header">
                <span><i class="fas fa-user-plus me-2"></i>Nuevos Usuarios</span>
                <a href="usuarios/index.php" class="btn-admin-outline btn-sm">Ver todos</a>
            </div>
            <div class="admin-card-body p-0">
                <?php foreach ($ultimos_usuarios as $uu): ?>
                <div class="d-flex align-items-center gap-2 p-3 border-bottom">
                    <div class="user-avatar flex-shrink-0">
                        <?= strtoupper(substr($uu['primer_nombre'], 0, 1) . substr($uu['primer_apellido'], 0, 1)) ?>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="fw-semibold small text-truncate"><?= sanitizar_html($uu['primer_nombre'] . ' ' . $uu['primer_apellido']) ?></div>
                        <div class="text-muted" style="font-size:0.72rem;"><?= date('d/m/Y', strtotime($uu['fecha_registro'])) ?></div>
                    </div>
                    <?php
                    $rol_map = [ROL_ADMIN_TOTAL => 'admin', ROL_PROFESOR => 'profesor', ROL_ESTUDIANTE => 'estudiante'];
                    $rol_lbl = [ROL_ADMIN_TOTAL => 'Admin', ROL_PROFESOR => 'Docente', ROL_ESTUDIANTE => 'Alumno'];
                    $r = $uu['id_rol_fk'];
                    ?>
                    <span class="badge-admin <?= $rol_map[$r] ?? 'inactivo' ?>"><?= $rol_lbl[$r] ?? '?' ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Accesos rápidos -->
        <div class="admin-card">
            <div class="admin-card-header"><span><i class="fas fa-bolt me-2"></i>Acciones Rápidas</span></div>
            <div class="admin-card-body d-flex flex-column gap-2">
                <a href="cursos/crear.php" class="btn-admin-primary w-100 justify-content-center">
                    <i class="fas fa-plus-circle"></i> Nuevo Curso
                </a>
                <a href="usuarios/crear.php" class="btn-admin-blue w-100 justify-content-center">
                    <i class="fas fa-user-plus"></i> Nuevo Usuario
                </a>
                <a href="<?= BASE_URL ?>index.php" target="_blank" class="btn btn-outline-secondary w-100 btn-sm rounded-3">
                    <i class="fas fa-external-link-alt me-1"></i> Ver Sitio Público
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
