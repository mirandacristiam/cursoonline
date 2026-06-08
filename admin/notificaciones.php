<?php
// /cursoonline/admin/notificaciones.php
// ============================================================
// Notificaciones del Administrador — EduTech Academy
// ============================================================

$page_title = 'Notificaciones';
require_once __DIR__ . '/includes/header.php';

// Obtener notificaciones del admin
$stmt = $pdo->prepare("
    SELECT nu.id_notificacion_usuario_pk, nu.estado_leida, nu.fecha_lectura,
           n.titulo_notificacion, n.mensaje_notificacion, n.tipo_notificacion, n.fecha_creacion, n.url_accion
    FROM notificaciones_usuario nu
    JOIN notificaciones n ON nu.id_notificacion_fk = n.id_notificacion_pk
    WHERE nu.id_usuario_fk = :id_user
      AND nu.estado_activo = 1
      AND n.estado_activo = 1
    ORDER BY n.fecha_creacion DESC
");
$stmt->execute([':id_user' => $id_usuario]);
$notificaciones = $stmt->fetchAll();

// Marcar todas como leídas al entrar
if (!empty($notificaciones)) {
    $pdo->prepare("
        UPDATE notificaciones_usuario
        SET estado_leida = 1, fecha_lectura = NOW()
        WHERE id_usuario_fk = :id_user AND estado_leida = 0 AND estado_activo = 1
    ")->execute([':id_user' => $id_usuario]);
}
?>
<div class="page-header">
    <h1><i class="fas fa-bell me-2"></i>Notificaciones</h1>
    <p>Mensajes y alertas del sistema.</p>
</div>

<div class="admin-card">
    <div class="admin-card-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list me-2"></i>Historial de Mensajes</span>
    </div>
    <div class="admin-card-body p-0">
        <?php if (empty($notificaciones)): ?>
            <div class="text-center py-5">
                <i class="fas fa-bell-slash text-muted fs-1 mb-3"></i>
                <p class="text-muted m-0">No tienes notificaciones.</p>
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($notificaciones as $n): ?>
                    <div class="list-group-item d-flex gap-3 align-items-start p-3 border-bottom <?= !$n['estado_leida'] ? 'bg-light' : '' ?>">
                        <div class="mt-1">
                            <?php
                            $iconos = ['info'=>'fas fa-info-circle text-primary', 'alerta'=>'fas fa-exclamation-triangle text-warning',
                                       'exito'=>'fas fa-check-circle text-success', 'error'=>'fas fa-times-circle text-danger',
                                       'pago'=>'fas fa-credit-card text-info', 'evaluacion'=>'fas fa-file-alt text-purple',
                                       'calificacion'=>'fas fa-star text-amber', 'sistema'=>'fas fa-cog text-secondary'];
                            $icono = $iconos[$n['tipo_notificacion']] ?? 'fas fa-bell text-muted';
                            ?>
                            <i class="<?= $icono ?> fs-5"></i>
                        </div>
                        <div class="flex-grow-1">
                            <strong class="d-block small"><?= sanitizar_html($n['titulo_notificacion']) ?></strong>
                            <p class="small text-muted mb-1"><?= sanitizar_html($n['mensaje_notificacion']) ?></p>
                            <span class="small text-muted"><?= date('d/m/Y H:i', strtotime($n['fecha_creacion'])) ?></span>
                        </div>
                        <?php if ($n['url_accion']): ?>
                            <a href="<?= BASE_URL . $n['url_accion'] ?>" class="btn btn-outline-primary btn-sm rounded-pill flex-shrink-0">Ver</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
