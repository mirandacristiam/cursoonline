<?php
// /cursoonline/student/notificaciones.php
// ============================================================
// Historial de Notificaciones y Alertas del Estudiante
// ============================================================

$page_title = 'Mis Notificaciones';
require_once __DIR__ . '/includes/header.php';

// Obtener notificaciones del estudiante
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

// Opcional: Marcar todas como leídas al entrar a esta vista para comodidad del usuario
if (!empty($notificaciones)) {
    $stmt_read = $pdo->prepare("
        UPDATE notificaciones_usuario 
        SET estado_leida = 1, 
            fecha_lectura = NOW() 
        WHERE id_usuario_fk = :id_user 
          AND estado_leida = 0 
          AND estado_activo = 1
    ");
    $stmt_read->execute([':id_user' => $id_usuario]);
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 fw-bold text-primary mb-1">Mis Notificaciones</h1>
        <p class="text-muted m-0">Mantente al día con las novedades, calificaciones y alertas del sistema.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-12">
        <div class="card-custom">
            <div class="card-header-custom bg-light"><i class="fas fa-bell me-2"></i>Historial de Mensajes</div>
            <div class="card-body-custom">
                <?php if (empty($notificaciones)): ?>
                    <div class="text-center py-5">
                        <i class="far fa-bell-slash text-muted fs-1 mb-3"></i>
                        <p class="text-muted m-0">No tienes notificaciones registradas en tu historial.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($notificaciones as $notif): ?>
                            <?php 
                            $bg_color = 'bg-light';
                            $icon = 'fa-info-circle text-primary';
                            if ($notif['tipo_notificacion'] === 'exito' || $notif['tipo_notificacion'] === 'calificacion') {
                                $icon = 'fa-check-circle text-success';
                            } elseif ($notif['tipo_notificacion'] === 'alerta') {
                                $icon = 'fa-exclamation-triangle text-warning';
                            } elseif ($notif['tipo_notificacion'] === 'error') {
                                $icon = 'fa-times-circle text-danger';
                            }
                            ?>
                            <div class="list-group-item p-3 border border-1 rounded-3 mb-2 <?= $notif['estado_leida'] ? 'opacity-75' : 'bg-primary-subtle border-primary-subtle' ?>">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="fs-4 mt-1"><i class="fas <?= $icon ?>"></i></div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h4 class="h6 fw-bold m-0 text-primary"><?= sanitizar_html($notif['titulo_notificacion']) ?></h4>
                                            <span class="small text-muted"><i class="far fa-calendar-alt me-1"></i> <?= date('d/m/Y H:i', strtotime($notif['fecha_creacion'])) ?></span>
                                        </div>
                                        <p class="m-0 mt-1 small text-dark"><?= sanitizar_html($notif['mensaje_notificacion']) ?></p>
                                        
                                        <?php if (!empty($notif['url_accion'])): ?>
                                            <a href="<?= BASE_URL . $notif['url_accion'] ?>" class="btn btn-primary btn-sm rounded-pill mt-2">Ir a ver</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
