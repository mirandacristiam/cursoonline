<?php
// /cursoonline/teacher/notificaciones.php
// ============================================================
// Notificaciones del Profesor — EduTech Academy
// ============================================================

$page_title = 'Notificaciones';
require_once 'includes/header.php';

// ── Paginación ────────────────────────────────────────────────
$por_pagina = 15;
$pagina     = max(1, (int)($_GET['p'] ?? 1));
$offset     = ($pagina - 1) * $por_pagina;

// ── Marcar como leída si se solicita ─────────────────────────
if (isset($_GET['marcar']) && (int)$_GET['marcar'] > 0) {
    $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE id_notificacion_pk = ? AND id_usuario_fk = ?")
        ->execute([(int)$_GET['marcar'], $id_usuario]);
    header("Location: notificaciones.php");
    exit();
}
if (isset($_GET['marcar_todas'])) {
    $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE id_usuario_fk = ?")
        ->execute([$id_usuario]);
    header("Location: notificaciones.php");
    exit();
}

// ── Contar totales ────────────────────────────────────────────
$stmt_total = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE id_usuario_fk = ?");
$stmt_total->execute([$id_usuario]);
$total_notif = (int)$stmt_total->fetchColumn();
$total_paginas = max(1, ceil($total_notif / $por_pagina));

$stmt_no_leidas = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE id_usuario_fk = ? AND leida = 0");
$stmt_no_leidas->execute([$id_usuario]);
$no_leidas = (int)$stmt_no_leidas->fetchColumn();

// ── Obtener notificaciones ────────────────────────────────────
$stmt_notif = $pdo->prepare("
    SELECT id_notificacion_pk, tipo, titulo, mensaje, leida, fecha_creacion
    FROM notificaciones
    WHERE id_usuario_fk = :id
    ORDER BY leida ASC, fecha_creacion DESC
    LIMIT :limit OFFSET :offset
");
$stmt_notif->bindValue(':id',     $id_usuario, PDO::PARAM_INT);
$stmt_notif->bindValue(':limit',  $por_pagina, PDO::PARAM_INT);
$stmt_notif->bindValue(':offset', $offset,     PDO::PARAM_INT);
$stmt_notif->execute();
$notificaciones = $stmt_notif->fetchAll();

// ── Mapa de íconos por tipo ───────────────────────────────────
$iconos_tipo = [
    'info'     => ['icon' => 'fas fa-info-circle',    'color' => 'blue'],
    'success'  => ['icon' => 'fas fa-check-circle',   'color' => 'teal'],
    'warning'  => ['icon' => 'fas fa-exclamation-triangle', 'color' => 'amber'],
    'error'    => ['icon' => 'fas fa-times-circle',   'color' => 'rose'],
    'sistema'  => ['icon' => 'fas fa-bell',            'color' => 'purple'],
];
?>

<!-- ── Page Header ─────────────────────────────────────────── -->
<div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Inicio</a></li>
                <li class="breadcrumb-item active">Notificaciones</li>
            </ol>
        </nav>
        <h1>
            <i class="fas fa-bell me-2 text-success"></i>Notificaciones
            <?php if ($no_leidas > 0): ?>
                <span class="badge bg-danger rounded-pill fs-6 ms-2"><?= $no_leidas ?></span>
            <?php endif; ?>
        </h1>
        <p>Tienes <strong><?= $total_notif ?></strong> notificacion(es) en total,
           <strong><?= $no_leidas ?></strong> sin leer.</p>
    </div>
    <?php if ($no_leidas > 0): ?>
    <div>
        <a href="notificaciones.php?marcar_todas=1"
           class="btn-teacher-outline"
           onclick="return confirm('¿Marcar todas como leídas?')">
            <i class="fas fa-check-double me-1"></i> Marcar todas como leídas
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- ── Lista de Notificaciones ─────────────────────────────── -->
<div class="teacher-card">
    <div class="card-body-custom p-0">
        <?php if (empty($notificaciones)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-bell-slash fa-3x mb-3 d-block opacity-40"></i>
                <h5>Sin notificaciones</h5>
                <p class="small">No tienes notificaciones pendientes. ¡Todo al día! 🎉</p>
            </div>
        <?php else: ?>
            <ul class="list-unstyled mb-0">
                <?php foreach ($notificaciones as $notif):
                    $tipo_data = $iconos_tipo[$notif['tipo']] ?? $iconos_tipo['info'];
                    $leida     = (int)$notif['leida'] === 1;
                ?>
                <li class="border-bottom <?= !$leida ? 'notif-unread' : '' ?>">
                    <div class="d-flex align-items-start gap-3 p-3">
                        <!-- Ícono de tipo -->
                        <div class="stat-icon <?= $tipo_data['color'] ?> flex-shrink-0 mt-1"
                             style="width:42px;height:42px;border-radius:10px;font-size:1rem;">
                            <i class="<?= $tipo_data['icon'] ?>"></i>
                        </div>

                        <!-- Contenido -->
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="fw-semibold small <?= !$leida ? 'text-dark' : 'text-muted' ?>">
                                    <?= sanitizar_html($notif['titulo']) ?>
                                </span>
                                <?php if (!$leida): ?>
                                    <span class="badge bg-danger rounded-pill" style="font-size:0.6rem;">NUEVA</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-muted small mb-1" style="line-height:1.5;">
                                <?= sanitizar_html($notif['mensaje']) ?>
                            </p>
                            <span class="text-muted" style="font-size:0.75rem;">
                                <i class="fas fa-clock me-1"></i>
                                <?= date('d/m/Y H:i', strtotime($notif['fecha_creacion'])) ?>
                            </span>
                        </div>

                        <!-- Marcar como leída -->
                        <?php if (!$leida): ?>
                        <div class="flex-shrink-0">
                            <a href="notificaciones.php?marcar=<?= $notif['id_notificacion_pk'] ?>"
                               class="btn-teacher-outline btn-sm"
                               data-bs-toggle="tooltip" title="Marcar como leída">
                                <i class="fas fa-check"></i>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>

            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
            <div class="card-body-custom border-top d-flex justify-content-center">
                <nav aria-label="Paginación notificaciones">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?p=<?= $pagina - 1 ?>">‹ Anterior</a>
                        </li>
                        <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
                            <li class="page-item <?= $p === $pagina ? 'active' : '' ?>">
                                <a class="page-link" href="?p=<?= $p ?>"><?= $p ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $pagina >= $total_paginas ? 'disabled' : '' ?>">
                            <a class="page-link" href="?p=<?= $pagina + 1 ?>">Siguiente ›</a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.notif-unread { background: rgba(20, 184, 166, 0.04); border-left: 3px solid var(--teacher-secondary) !important; }
</style>

<?php require_once 'includes/footer.php'; ?>
