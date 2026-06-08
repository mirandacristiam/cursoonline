<?php
// /admin/usuarios/ver.php
// ============================================================
// Detalle de Usuario — Panel Administrativo — EduTech Academy
// ============================================================

$page_title = 'Detalle de Usuario';
$page_script = '../assets/js/usuarios.js';
$page_css    = '../assets/css/usuarios.css';
require_once __DIR__ . '/../includes/header.php';

$id_target = (int)($_GET['id'] ?? 0);
if (!$id_target) {
    header("Location: index.php");
    exit();
}

// ── Helfer: SP query ──────────────────────────────────────
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

// ── Obtener usuario ─────────────────────────────────────────
$usuario = sp_query_one($pdo, 'sp_admin_obtener_usuario', ['p_id_usuario' => $id_target]);
if (!$usuario) {
    header("Location: index.php?err=not_found");
    exit();
}

// ── Estadísticas ────────────────────────────────────────────
$stats = sp_query_one($pdo, 'sp_admin_estadisticas_usuario', ['p_id_usuario' => $id_target]);

// ── Inscripciones ───────────────────────────────────────────
$inscripciones = sp_query($pdo, 'sp_admin_inscripciones_usuario', ['p_id_usuario' => $id_target]);

// ── Actividad reciente (últimos 10 accesos) ─────────────────
$actividad = sp_query($pdo, 'sp_admin_actividad_reciente_usuario', ['p_id_usuario' => $id_target, 'p_limite' => 10]);

$nombre_completo = trim($usuario['primer_nombre'] . ' ' . $usuario['segundo_nombre'] . ' ' . $usuario['primer_apellido'] . ' ' . $usuario['segundo_apellido']);
$iniciales = strtoupper(substr($usuario['primer_nombre'], 0, 1) . substr($usuario['primer_apellido'], 0, 1));

$puede_modificar = ($id_target !== $id_usuario);
?>

<div class="page-header">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="index.php">Usuarios</a></li>
            <li class="breadcrumb-item active"><?= sanitizar_html($nombre_completo) ?></li>
        </ol>
    </nav>
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h1 class="mb-0"><i class="fas fa-user me-2 text-danger"></i><?= sanitizar_html($nombre_completo) ?></h1>
        <div class="d-flex gap-2">
            <a href="editar.php?id=<?= $id_target ?>" class="btn-admin-blue btn-sm">
                <i class="fas fa-edit me-1"></i>Editar
            </a>
            <a href="index.php" class="btn btn-outline-secondary btn-sm rounded-3">
                <i class="fas fa-arrow-left me-1"></i>Volver
            </a>
        </div>
    </div>
</div>

<!-- ── Alertas ─────────────────────────────────────────────── -->
<?php if (isset($_GET['updated'])): ?>
<div class="alert alert-success auto-dismiss rounded-3 mb-3"><i class="fas fa-check-circle me-2"></i>Usuario actualizado correctamente.</div>
<?php endif; ?>

<!-- ── Profile Header ──────────────────────────────────────── -->
<div class="profile-header-card">
    <div class="avatar-lg">
        <?php if (!empty($usuario['foto_perfil'])): ?>
        <img src="<?= sanitizar_html($usuario['foto_perfil']) ?>" alt="Avatar">
        <?php else: ?>
        <?= $iniciales ?>
        <?php endif; ?>
    </div>
    <div class="profile-header-info">
        <h2><?= sanitizar_html($nombre_completo) ?></h2>
        <p><i class="fas fa-envelope me-1"></i><?= sanitizar_html($usuario['correo_electronico']) ?></p>
        <div class="d-flex gap-2 mt-1">
            <span class="badge-admin <?= $usuario['id_rol_fk'] == ROL_ADMIN_TOTAL ? 'admin' : ($usuario['id_rol_fk'] == ROL_PROFESOR ? 'profesor' : 'estudiante') ?>">
                <?= sanitizar_html($usuario['nombre_rol']) ?>
            </span>
            <span class="badge-admin <?= (int)$usuario['estado_activo'] ? 'activo' : 'inactivo' ?>">
                <?= (int)$usuario['estado_activo'] ? 'Activo' : 'Inactivo' ?>
            </span>
        </div>
    </div>
</div>

<!-- ── Account Stats ───────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-2">
        <div class="account-stat">
            <div class="account-stat-value text-primary"><?= (int)$stats['total_inscripciones'] ?></div>
            <div class="account-stat-label">Inscripciones</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="account-stat">
            <div class="account-stat-value text-success"><?= (int)$stats['inscripciones_activas'] ?></div>
            <div class="account-stat-label">Activas</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="account-stat">
            <div class="account-stat-value text-info"><?= (int)$stats['inscripciones_completadas'] ?></div>
            <div class="account-stat-label">Completadas</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="account-stat">
            <div class="account-stat-value text-warning"><?= (int)$stats['inscripciones_canceladas'] ?></div>
            <div class="account-stat-label">Canceladas</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="account-stat">
            <div class="account-stat-value text-danger">$<?= number_format((float)$stats['total_gastado'], 0, ',', '.') ?></div>
            <div class="account-stat-label">Gastado</div>
        </div>
    </div>
    <div class="col-6 col-md-2">
        <div class="account-stat">
            <div class="account-stat-value text-purple" style="color:var(--admin-purple);"><?= (int)$stats['total_accesos'] ?></div>
            <div class="account-stat-label">Accesos</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- ── Columna izquierda: Info personal ──────────────── -->
    <div class="col-lg-5">
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="fas fa-id-card me-2"></i>Información Personal</span>
            </div>
            <div class="admin-card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-item-label">Primer Nombre</div>
                        <div class="info-item-value"><?= sanitizar_html($usuario['primer_nombre'] ?? '—') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-item-label">Segundo Nombre</div>
                        <div class="info-item-value"><?= sanitizar_html($usuario['segundo_nombre'] ?? '—') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-item-label">Primer Apellido</div>
                        <div class="info-item-value"><?= sanitizar_html($usuario['primer_apellido'] ?? '—') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-item-label">Segundo Apellido</div>
                        <div class="info-item-value"><?= sanitizar_html($usuario['segundo_apellido'] ?? '—') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-item-label">Correo Electrónico</div>
                        <div class="info-item-value" style="word-break:break-all;"><?= sanitizar_html($usuario['correo_electronico'] ?? '—') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-item-label">Teléfono</div>
                        <div class="info-item-value"><?= sanitizar_html($usuario['numero_telefono'] ?? '—') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-item-label">Tipo Documento</div>
                        <div class="info-item-value"><?= sanitizar_html($usuario['tipo_documento_identidad'] ?? '—') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-item-label">N° Documento</div>
                        <div class="info-item-value"><?= sanitizar_html($usuario['numero_documento_identidad'] ?? '—') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-item-label">Fecha Nacimiento</div>
                        <div class="info-item-value"><?= $usuario['fecha_nacimiento'] ? date('d/m/Y', strtotime($usuario['fecha_nacimiento'])) : '—' ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-item-label">País</div>
                        <div class="info-item-value"><?= sanitizar_html($usuario['pais_residencia'] ?? '—') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-item-label">Ciudad</div>
                        <div class="info-item-value"><?= sanitizar_html($usuario['ciudad_residencia'] ?? '—') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-item-label">Departamento</div>
                        <div class="info-item-value"><?= sanitizar_html($usuario['departamento_residencia'] ?? '—') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sistema info -->
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="fas fa-cogs me-2"></i>Información del Sistema</span>
            </div>
            <div class="admin-card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-item-label">Registrado</div>
                        <div class="info-item-value"><?= $usuario['fecha_creacion'] ? date('d/m/Y H:i', strtotime($usuario['fecha_creacion'])) : '—' ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-item-label">Último Acceso</div>
                        <div class="info-item-value"><?= $usuario['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($usuario['ultimo_acceso'])) : 'Nunca' ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Columna derecha: Inscripciones + Actividad ────── -->
    <div class="col-lg-7">
        <!-- Inscripciones -->
        <div class="admin-card mb-3">
            <div class="admin-card-header">
                <span><i class="fas fa-graduation-cap me-2"></i>Cursos Inscritos (<?= count($inscripciones) ?>)</span>
                <?php if (!empty($inscripciones)): ?>
                <input type="text" class="form-control form-control-sm" id="filterInscripciones"
                       placeholder="Filtrar..." style="max-width:160px;">
                <?php endif; ?>
            </div>
            <div class="admin-card-body p-0">
                <?php if (empty($inscripciones)): ?>
                <div class="text-center py-4 text-muted small">No tiene cursos inscritos.</div>
                <?php else: ?>
                <?php foreach ($inscripciones as $ins): ?>
                <div class="inscripcion-item">
                    <div class="curso-thumb">
                        <?php if (!empty($ins['imagen_portada'])): ?>
                        <img src="<?= sanitizar_html($ins['imagen_portada']) ?>" alt="">
                        <?php else: ?>
                        <i class="fas fa-book"></i>
                        <?php endif; ?>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div class="fw-semibold small text-truncate"><?= sanitizar_html($ins['titulo_curso']) ?></div>
                        <div class="text-muted" style="font-size:0.7rem;">
                            <?= sanitizar_html($ins['nombre_categoria'] ?? 'Sin categoría') ?> &middot;
                            Inscrito: <?= date('d/m/Y', strtotime($ins['fecha_inscripcion'])) ?>
                        </div>
                    </div>
                    <div class="text-end" style="flex-shrink:0;">
                        <span class="badge-admin <?= $ins['estado_inscripcion'] === 'activa' ? 'activo' : ($ins['estado_inscripcion'] === 'completada' ? 'aprobado' : 'inactivo') ?>">
                            <?= ucfirst($ins['estado_inscripcion']) ?>
                        </span>
                        <div class="small text-muted mt-1"><?= (int)$ins['porcentaje_progreso'] ?>%</div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Actividad Reciente -->
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="fas fa-history me-2"></i>Actividad Reciente (últimos accesos)</span>
            </div>
            <div class="admin-card-body p-0">
                <?php if (empty($actividad)): ?>
                <div class="text-center py-4 text-muted small">Sin actividad registrada.</div>
                <?php else: ?>
                <?php foreach ($actividad as $log): ?>
                <div class="activity-item">
                    <div class="activity-icon"
                         style="background:<?= $log['tipo_accion'] === 'login_exitoso' ? 'rgba(22,163,74,0.1)' : 'rgba(220,38,38,0.1)' ?>;
                                color:<?= $log['tipo_accion'] === 'login_exitoso' ? '#16A34A' : '#DC2626' ?>;">
                        <i class="fas fa-<?= $log['tipo_accion'] === 'login_exitoso' ? 'sign-in-alt' : ($log['tipo_accion'] === 'logout' ? 'sign-out-alt' : 'exclamation') ?>"></i>
                    </div>
                    <div style="flex:1;">
                        <div class="fw-semibold small">
                            <?php
                            $accion_labels = [
                                'login_exitoso' => 'Inicio de sesión',
                                'login_fallido' => 'Intento fallido',
                                'logout' => 'Cierre de sesión',
                                'sesion_expirada' => 'Sesión expirada',
                            ];
                            echo $accion_labels[$log['tipo_accion']] ?? $log['tipo_accion'];
                            ?>
                        </div>
                        <div class="text-muted" style="font-size:0.7rem;">
                            IP: <?= sanitizar_html($log['direccion_ip']) ?> &middot;
                            <?= date('d/m/Y H:i', strtotime($log['fecha_acceso'])) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
