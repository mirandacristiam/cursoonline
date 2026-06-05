<?php
// /admin/seguridad/index.php
// ============================================================
// Módulo de Seguridad, Auditoría y Logs del Sistema
// ============================================================

$page_title = 'Auditoría y Seguridad';
require_once __DIR__ . '/../includes/header.php';

// --- 1. CONSULTA DE BITÁCORA DE ACTIVIDAD DE USUARIOS ---
$stmt_activity = $pdo->query("
    SELECT l.*, u.primer_nombre, u.primer_apellido, r.nombre_rol
    FROM log_actividad_usuario l
    JOIN usuarios u ON l.id_usuario_fk = u.id_usuario_pk
    JOIN roles r ON u.id_rol_fk = r.id_rol_pk
    ORDER BY l.fecha_actividad DESC
    LIMIT 15
");
$logs_actividad = $stmt_activity->fetchAll();

// --- 2. CONSULTA DE BITÁCORA DE INICIO DE SESIÓN ---
$stmt_access = $pdo->query("
    SELECT a.*, u.primer_nombre, u.primer_apellido, r.nombre_rol
    FROM log_accesos a
    JOIN usuarios u ON a.id_usuario_fk = u.id_usuario_pk
    JOIN roles r ON u.id_rol_fk = r.id_rol_pk
    ORDER BY a.fecha_acceso DESC
    LIMIT 15
");
$logs_accesos = $stmt_access->fetchAll();
?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 fw-bold text-primary mb-1">Centro de Auditoría y Seguridad</h1>
        <p class="text-muted m-0">Revisa las acciones de usuario críticas, inicios de sesión y logs del servidor.</p>
    </div>
</div>

<div class="row g-4">
    <!-- Log de Actividades de Usuario -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="admin-card-header"><i class="fas fa-history me-2"></i>Bitácora de Acciones Críticas (Auditoría)</div>
            <div class="admin-card-body p-0">
                <?php if (empty($logs_actividad)): ?>
                    <p class="text-muted p-4 text-center m-0">No hay registros de actividades críticas recientes.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-custom m-0 text-start">
                            <thead>
                                <tr>
                                    <th class="ps-3">Usuario</th>
                                    <th>Acción</th>
                                    <th>Descripción</th>
                                    <th>IP</th>
                                    <th class="pe-3">Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs_actividad as $log): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <span class="fw-bold d-block text-dark"><?= sanitizar_html($log['primer_nombre'] . ' ' . $log['primer_apellido']) ?></span>
                                            <span class="xsmall text-muted text-uppercase"><?= sanitizar_html($log['nombre_rol']) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger-subtle text-danger text-uppercase xsmall">
                                                <?= sanitizar_html($log['tipo_accion']) ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted"><?= sanitizar_html($log['descripcion_accion']) ?></td>
                                        <td class="small"><?= sanitizar_html($log['direccion_ip'] ?: '127.0.0.1') ?></td>
                                        <td class="pe-3 small text-muted"><?= date('d/m/Y H:i', strtotime($log['fecha_actividad'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Log de Accesos (Logins/Logouts) -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="admin-card-header"><i class="fas fa-user-shield me-2"></i>Historial de Accesos (Logins / Logouts)</div>
            <div class="admin-card-body p-0">
                <?php if (empty($logs_accesos)): ?>
                    <p class="text-muted p-4 text-center m-0">No hay registros de inicios de sesión.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-custom m-0 text-start">
                            <thead>
                                <tr>
                                    <th class="ps-3">Usuario</th>
                                    <th>Resultado</th>
                                    <th>Dispositivo/Navegador</th>
                                    <th>IP</th>
                                    <th class="pe-3">Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs_accesos as $acc): ?>
                                    <tr>
                                        <td class="ps-3">
                                            <span class="fw-bold d-block text-dark"><?= sanitizar_html($acc['primer_nombre'] . ' ' . $acc['primer_apellido']) ?></span>
                                            <span class="xsmall text-muted text-uppercase"><?= sanitizar_html($acc['nombre_rol']) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge <?= $acc['resultado_acceso'] === 'EXITOSO' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' ?> text-uppercase xsmall">
                                                <?= sanitizar_html($acc['resultado_acceso']) ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?= sanitizar_html($acc['detalles_dispositivo'] ?: 'Desconocido') ?>
                                        </td>
                                        <td class="small"><?= sanitizar_html($acc['direccion_ip']) ?></td>
                                        <td class="pe-3 small text-muted"><?= date('d/m/Y H:i', strtotime($acc['fecha_acceso'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
