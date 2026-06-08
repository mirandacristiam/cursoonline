<?php
// /cursoonline/student/mis-cursos.php
// ============================================================
// Mis Cursos — EduTech Academy
// ============================================================

$page_title = 'Mis Cursos';
require_once __DIR__ . '/includes/header.php';

$msg_ok  = $_SESSION['student_msg_ok']  ?? '';
$msg_err = $_SESSION['student_msg_err'] ?? '';
unset($_SESSION['student_msg_ok'], $_SESSION['student_msg_err']);

$csrf_cancelar = generar_token_csrf($pdo, 'cancelar');
$csrf_ocultar  = generar_token_csrf($pdo, 'ocultar');

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'activos';

// ── Consulta según pestaña ──
$where_extra = '';
if ($tab === 'archivados') {
    $where_extra = "AND i.estado_inscripcion = 'completada'";
} elseif ($tab === 'cancelados') {
    $where_extra = "AND i.estado_inscripcion = 'cancelada' AND i.visible_estudiante = 1";
} else {
    $where_extra = "AND i.estado_inscripcion IN ('activa','suspendida')";
}

$stmt = $pdo->prepare("
    SELECT i.id_inscripcion_pk, i.porcentaje_progreso, i.estado_inscripcion, i.fecha_inscripcion,
           c.titulo_curso, c.resumen_corto, c.imagen_portada, c.total_horas, cat.nombre_categoria, cat.color_categoria
    FROM inscripciones i
    JOIN cursos c ON i.id_curso_fk = c.id_curso_pk
    JOIN categorias_curso cat ON c.id_categoria_fk = cat.id_categoria_pk
    WHERE i.id_usuario_fk = :id_user
      AND i.estado_activo = 1
      AND i.visible_estudiante = 1
      $where_extra
    ORDER BY i.fecha_inscripcion DESC
");
$stmt->execute([':id_user' => $id_usuario]);
$inscripciones = $stmt->fetchAll();
?>
<?php if ($msg_ok): ?>
<div class="alert alert-success rounded-3 d-flex gap-3 align-items-center mb-4 shadow-sm" role="alert">
    <i class="fas fa-check-circle fs-4"></i>
    <span><?= sanitizar_html($msg_ok) ?></span>
</div>
<?php endif; ?>
<?php if ($msg_err): ?>
<div class="alert alert-danger rounded-3 d-flex gap-3 align-items-center mb-4 shadow-sm" role="alert">
    <i class="fas fa-exclamation-circle fs-4"></i>
    <span><?= sanitizar_html($msg_err) ?></span>
</div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 fw-bold text-primary m-0"><i class="fas fa-graduation-cap me-2"></i>Mis Cursos</h1>
    <a href="explorar-cursos.php" class="btn btn-primary rounded-pill shadow-sm">
        <i class="fas fa-compass me-1"></i> Explorar Cursos
    </a>
</div>

<!-- Pestañas -->
<ul class="nav nav-tabs border-0 mb-4 gap-2" style="border-bottom:2px solid #E2E8F0;">
    <li class="nav-item">
        <a class="nav-link rounded-3 px-4 fw-semibold <?= $tab === 'activos' ? 'active' : '' ?>"
           href="?tab=activos" style="<?= $tab === 'activos' ? 'background:#2563EB;color:#fff;' : 'color:#64748B;' ?>">
            <i class="fas fa-play-circle me-1"></i>Activos
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link rounded-3 px-4 fw-semibold <?= $tab === 'archivados' ? 'active' : '' ?>"
           href="?tab=archivados" style="<?= $tab === 'archivados' ? 'background:#2563EB;color:#fff;' : 'color:#64748B;' ?>">
            <i class="fas fa-archive me-1"></i>Archivados
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link rounded-3 px-4 fw-semibold <?= $tab === 'cancelados' ? 'active' : '' ?>"
           href="?tab=cancelados" style="<?= $tab === 'cancelados' ? 'background:#2563EB;color:#fff;' : 'color:#64748B;' ?>">
            <i class="fas fa-ban me-1"></i>Cancelados
        </a>
    </li>
</ul>

<div class="row g-4">
    <?php if (empty($inscripciones)): ?>
        <div class="col-12 text-center py-5">
            <div class="card p-5 border border-dashed rounded-4">
                <i class="fas fa-<?= $tab === 'archivados' ? 'archive' : ($tab === 'cancelados' ? 'ban' : 'graduation-cap') ?> text-muted fs-1 mb-3"></i>
                <h2 class="h4 fw-bold">
                    <?php if ($tab === 'archivados'): ?>No hay cursos archivados
                    <?php elseif ($tab === 'cancelados'): ?>No hay cursos cancelados
                    <?php else: ?>No tienes cursos activos<?php endif; ?>
                </h2>
                <p class="text-muted mb-4">
                    <?php if ($tab === 'archivados'): ?>Los cursos que completes aparecerán aquí.
                    <?php elseif ($tab === 'cancelados'): ?>Los cursos que canceles aparecerán aquí.
                    <?php else: ?>Inscríbete en un curso para comenzar a aprender.<?php endif; ?>
                </p>
                <?php if ($tab === 'activos'): ?>
                <a href="explorar-cursos.php" class="btn btn-primary btn-lg rounded-pill px-4">
                    <i class="fas fa-compass me-2"></i>Explorar Cursos
                </a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($inscripciones as $item): ?>
            <div class="col-lg-4 col-md-6">
                <article class="card-custom h-100 d-flex flex-column">
                    <div style="position:relative;padding-top:56.25%;overflow:hidden;">
                        <span class="badge position-absolute" style="top:1rem;left:1rem;background:<?= $item['color_categoria'] ?: '#2563EB' ?>;z-index:1;">
                            <?= sanitizar_html($item['nombre_categoria']) ?>
                        </span>
                        <?php if ($item['estado_inscripcion'] === 'suspendida'): ?>
                            <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
                                 style="background:rgba(0,0,0,0.45);z-index:2;">
                                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fs-6 shadow-sm">
                                    <i class="fas fa-hourglass-half me-1"></i> Pendiente de Aprobación
                                </span>
                            </div>
                        <?php elseif ($item['estado_inscripcion'] === 'cancelada'): ?>
                            <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
                                 style="background:rgba(0,0,0,0.45);z-index:2;">
                                <span class="badge bg-secondary px-3 py-2 rounded-pill fs-6 shadow-sm">
                                    <i class="fas fa-ban me-1"></i> Cancelado
                                </span>
                            </div>
                        <?php endif; ?>
                        <img src="<?= $item['imagen_portada'] ? BASE_URL . $item['imagen_portada'] : 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80' ?>"
                             alt="Portada"
                             style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;">
                    </div>
                    <div class="card-body-custom flex-grow-1 d-flex flex-column" style="justify-content:space-between;">
                        <div>
                            <h2 class="h5 fw-bold text-primary mb-2"><?= sanitizar_html($item['titulo_curso']) ?></h2>
                            <p class="text-muted small mb-4"><?= sanitizar_html($item['resumen_corto']) ?></p>
                        </div>
                        <div class="mt-auto">
                            <?php if ($item['estado_inscripcion'] === 'activa' || $item['estado_inscripcion'] === 'completada'): ?>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="small text-muted"><i class="far fa-clock me-1"></i> <?= $item['total_horas'] ?>h totales</span>
                                    <span class="small fw-bold text-primary"><?= number_format($item['porcentaje_progreso'], 0) ?>%</span>
                                </div>
                                <div class="progress-bar-custom mb-4">
                                    <div class="progress-bar-fill" style="width:<?= (float)$item['porcentaje_progreso'] ?>%;"></div>
                                </div>
                                <div class="d-grid">
                                    <a href="ver-clase.php?inscripcion=<?= $item['id_inscripcion_pk'] ?>" class="btn btn-primary rounded-pill shadow-sm">
                                        <i class="fas fa-play me-2"></i> Continuar Aprendiendo
                                    </a>
                                </div>
                            <?php elseif ($item['estado_inscripcion'] === 'suspendida'): ?>
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-secondary rounded-pill shadow-sm" disabled>
                                        <i class="fas fa-lock me-2"></i> Esperando Aprobación
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm rounded-pill w-100"
                                            onclick="confirmarCancelacion(<?= $item['id_inscripcion_pk'] ?>, 'suspendida')">
                                        <i class="fas fa-times me-1"></i>Cancelar Solicitud
                                    </button>
                                </div>
                                <p class="text-muted small text-center mt-2 mb-0">
                                    <i class="fas fa-info-circle me-1"></i>El administrador verificará tu pago
                                </p>
                            <?php elseif ($item['estado_inscripcion'] === 'cancelada'): ?>
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-secondary rounded-pill shadow-sm" disabled>
                                        <i class="fas fa-ban me-2"></i> Inscripción Cancelada
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill w-100"
                                            onclick="confirmarOcultar(<?= $item['id_inscripcion_pk'] ?>)">
                                        <i class="fas fa-eye-slash me-1"></i>Eliminar de mi listado
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal Cancelar -->
<div class="modal fade" id="modalCancelar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 pb-0" id="modalCancelarHeader">
                <h5 class="modal-title fw-bold" id="modalCancelarTitle">Cancelar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form method="POST" action="cancelar-inscripcion.php">
                <div class="modal-body px-4 pt-3">
                    <input type="hidden" name="csrf_token" id="cancelarCsrf" value="<?= $csrf_cancelar ?>">
                    <input type="hidden" name="id_inscripcion" id="cancelarId" value="">
                    <div id="modalCancelarMsg" class="text-center py-2"></div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Volver</button>
                    <button type="submit" class="btn rounded-3 px-4 fw-bold" id="modalCancelarBtn">Sí, Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmarCancelacion(id, tipo) {
    var el = document.getElementById('modalCancelar');
    if (!el) return;
    document.getElementById('cancelarId').value = id;
    var msg   = document.getElementById('modalCancelarMsg');
    var hdr   = document.getElementById('modalCancelarHeader');
    var title = document.getElementById('modalCancelarTitle');
    var btn   = document.getElementById('modalCancelarBtn');

    if (tipo === 'suspendida') {
        msg.innerHTML = '<i class="fas fa-times-circle text-warning" style="font-size:3rem;"></i>' +
            '<h6 class="fw-bold mt-3">¿Cancelar esta solicitud?</h6>' +
            '<p class="text-muted small mb-0">No se realizará ningún cobro. Puedes volver a solicitar la inscripción más tarde.</p>';
        hdr.style.background = 'linear-gradient(135deg,#FFFBEB,#FDE68A)';
        title.textContent    = 'Cancelar Solicitud';
        title.style.color    = '#92400E';
        btn.className        = 'btn btn-warning rounded-3 px-4 fw-bold';
        btn.innerHTML        = '<i class="fas fa-ban me-2"></i>Sí, Cancelar Solicitud';
    } else {
        msg.innerHTML = '<i class="fas fa-exclamation-triangle text-danger" style="font-size:3rem;"></i>' +
            '<h6 class="fw-bold mt-3">¿Cancelar este curso?</h6>' +
            '<p class="text-muted small mb-0">El pago ya realizado <strong>no será reembolsado</strong>. Perderás el acceso al contenido del curso.</p>';
        hdr.style.background = 'linear-gradient(135deg,#FEF2F2,#FEE2E2)';
        title.textContent    = 'Cancelar Curso';
        title.style.color    = '#7F1D1D';
        btn.className        = 'btn btn-danger rounded-3 px-4 fw-bold';
        btn.innerHTML        = '<i class="fas fa-times me-2"></i>Sí, Cancelar (sin reembolso)';
    }

    var modal = bootstrap.Modal.getInstance(el);
    if (!modal) modal = new bootstrap.Modal(el);
    modal.show();
}

function confirmarOcultar(id) {
    var el = document.getElementById('modalOcultar');
    if (!el) return;
    document.getElementById('ocultarId').value = id;
    var modal = bootstrap.Modal.getInstance(el);
    if (!modal) modal = new bootstrap.Modal(el);
    modal.show();
}
</script>

<!-- Modal Ocultar Inscripción -->
<div class="modal fade" id="modalOcultar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 pb-0" style="background:linear-gradient(135deg,#F8FAFC,#E2E8F0);">
                <h5 class="modal-title fw-bold" style="color:#475569;">Ocultar Curso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form method="POST" action="ocultar-inscripcion.php">
                <div class="modal-body px-4 pt-3 text-center py-3">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_ocultar ?>">
                    <input type="hidden" name="id_inscripcion" id="ocultarId" value="">
                    <i class="fas fa-trash-alt text-danger" style="font-size:3rem;"></i>
                    <h6 class="fw-bold mt-3">¿Eliminar este curso de tu listado?</h6>
                    <p class="text-muted small mb-0">El curso desaparecerá de tu panel, pero el registro permanecerá en el sistema para el administrador. Podrás reinscribirte más tarde si lo deseas.</p>
                </div>
                <div class="modal-footer border-0 pt-0 px-4">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Volver</button>
                    <button type="submit" class="btn btn-danger rounded-3 px-4 fw-bold">
                        <i class="fas fa-trash-alt me-2"></i>Sí, Eliminar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
