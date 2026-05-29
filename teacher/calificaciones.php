<?php
// /cursoonline/teacher/calificaciones.php
// ============================================================
// Vista de Calificaciones — Panel del Profesor — EduTech Academy
// ============================================================

$page_title = 'Calificar Actividades';
require_once 'includes/header.php';

// ── Parámetros de filtro ──────────────────────────────────────
$id_grupo_sel   = isset($_GET['grupo'])      ? (int)$_GET['grupo']                    : 0;
$id_eval_sel    = isset($_GET['evaluacion']) ? (int)$_GET['evaluacion']               : 0;
$id_est_sel     = isset($_GET['estudiante']) ? (int)$_GET['estudiante']               : 0;
$msg_ok = $msg_err = '';

// ── POST: Guardar nota ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'calificar') {
    require_once '../includes/csrf.php';
    $token = $_POST['csrf_token'] ?? '';
    if (!validar_token_csrf($pdo, $token)) {
        $msg_err = 'Token de seguridad inválido. Recarga la página.';
    } else {
        $id_intento  = (int)($_POST['id_intento'] ?? 0);
        $nota        = isset($_POST['nota']) ? (float)$_POST['nota'] : null;
        $retroalim   = limpiar_entrada($_POST['retroalimentacion'] ?? '');

        if ($id_intento && $nota !== null && $nota >= 0 && $nota <= 100) {
            try {
                $stmt_up = $pdo->prepare("
                    UPDATE intentos_evaluacion
                    SET nota_obtenida      = :nota,
                        retroalimentacion  = :retro,
                        estado_revision    = 'revisado',
                        fecha_revision     = NOW()
                    WHERE id_intento_fk = :id
                ");
                $stmt_up->execute([':nota' => $nota, ':retro' => $retroalim, ':id' => $id_intento]);
                $msg_ok = '✅ Calificación guardada correctamente.';
            } catch (PDOException $e) {
                error_log('[CALIFICAR ERROR] ' . $e->getMessage());
                $msg_err = 'Error al guardar la calificación. Intente de nuevo.';
            }
        } else {
            $msg_err = 'Por favor ingresa una nota válida entre 0 y 100.';
        }
    }
}

// ── Grupos del profesor ────────────────────────────────────────
$stmt_grupos = $pdo->prepare("
    SELECT g.id_grupo_pk, g.nombre_grupo, c.nombre_curso
    FROM grupos g
    INNER JOIN grupo_docente gd ON gd.id_grupo_fk = g.id_grupo_pk
    INNER JOIN cursos c ON c.id_curso_pk = g.id_curso_fk
    WHERE gd.id_usuario_fk = :id AND g.estado_activo = 1
    ORDER BY c.nombre_curso
");
$stmt_grupos->execute([':id' => $id_usuario]);
$grupos = $stmt_grupos->fetchAll();

// ── Evaluaciones del grupo seleccionado ──────────────────────
$evaluaciones = [];
if ($id_grupo_sel) {
    $stmt_evals = $pdo->prepare("
        SELECT ev.id_evaluacion_pk, ev.nombre_evaluacion, ev.tipo_evaluacion,
               ev.puntaje_maximo,
               COUNT(ie.id_intento_fk) AS total_intentos,
               SUM(CASE WHEN ie.estado_revision='pendiente' THEN 1 ELSE 0 END) AS pendientes
        FROM evaluaciones ev
        INNER JOIN cursos c ON c.id_curso_pk = ev.id_curso_fk
        INNER JOIN grupos g ON g.id_curso_fk  = c.id_curso_pk AND g.id_grupo_pk = :grupo
        LEFT JOIN intentos_evaluacion ie ON ie.id_evaluacion_fk = ev.id_evaluacion_pk
        WHERE ev.estado_activo = 1
        GROUP BY ev.id_evaluacion_pk, ev.nombre_evaluacion, ev.tipo_evaluacion, ev.puntaje_maximo
        ORDER BY ev.nombre_evaluacion
    ");
    $stmt_evals->execute([':grupo' => $id_grupo_sel]);
    $evaluaciones = $stmt_evals->fetchAll();
}

// ── Intentos de estudiantes para la evaluación elegida ───────
$intentos = [];
if ($id_grupo_sel && $id_eval_sel) {
    $sql_intentos = "
        SELECT ie.id_intento_fk, ie.nota_obtenida, ie.estado_revision,
               ie.retroalimentacion, ie.fecha_intento, ie.fecha_revision,
               u.id_usuario_pk, u.primer_nombre, u.primer_apellido, u.correo_electronico,
               ev.puntaje_maximo, ev.nombre_evaluacion
        FROM intentos_evaluacion ie
        INNER JOIN usuarios u ON u.id_usuario_pk = ie.id_usuario_fk
        INNER JOIN evaluaciones ev ON ev.id_evaluacion_pk = ie.id_evaluacion_fk
        WHERE ie.id_evaluacion_fk = :eval
          AND ie.id_usuario_fk IN (
              SELECT ei.id_usuario_fk FROM enrollments_inscripciones ei WHERE ei.id_grupo_fk = :grupo
          )
    ";
    $params_int = [':eval' => $id_eval_sel, ':grupo' => $id_grupo_sel];
    if ($id_est_sel) {
        $sql_intentos .= " AND ie.id_usuario_fk = :est";
        $params_int[':est'] = $id_est_sel;
    }
    $sql_intentos .= " ORDER BY ie.estado_revision DESC, u.primer_apellido";
    $stmt_int = $pdo->prepare($sql_intentos);
    $stmt_int->execute($params_int);
    $intentos = $stmt_int->fetchAll();
}

// CSRF para el formulario de calificación
require_once '../includes/csrf.php';
?>

<!-- ── Page Header ─────────────────────────────────────────── -->
<div class="page-header">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Inicio</a></li>
            <li class="breadcrumb-item"><a href="mis-grupos.php">Mis Grupos</a></li>
            <li class="breadcrumb-item active">Calificaciones</li>
        </ol>
    </nav>
    <h1><i class="fas fa-check-double me-2 text-success"></i>Calificar Actividades</h1>
    <p>Selecciona un grupo y evaluación para revisar y calificar los intentos de tus estudiantes.</p>
</div>

<!-- Alertas globales -->
<?php if ($msg_ok): ?>
    <div class="alert alert-success rounded-3 mb-3"><i class="fas fa-check-circle me-2"></i><?= sanitizar_html($msg_ok) ?></div>
<?php endif; ?>
<?php if ($msg_err): ?>
    <div class="alert alert-danger rounded-3 mb-3"><i class="fas fa-exclamation-circle me-2"></i><?= sanitizar_html($msg_err) ?></div>
<?php endif; ?>

<!-- ── Filtros ─────────────────────────────────────────────── -->
<div class="teacher-card mb-4">
    <div class="card-header-custom">
        <h5><i class="fas fa-filter"></i> Filtrar por Grupo y Evaluación</h5>
    </div>
    <div class="card-body-custom">
        <form method="GET" class="row g-3 align-items-end" id="formFiltroCalif">
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted">Grupo</label>
                <select name="grupo" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">— Selecciona un grupo —</option>
                    <?php foreach ($grupos as $g): ?>
                    <option value="<?= $g['id_grupo_pk'] ?>" <?= $id_grupo_sel === (int)$g['id_grupo_pk'] ? 'selected' : '' ?>>
                        <?= sanitizar_html($g['nombre_grupo']) ?> — <?= sanitizar_html($g['nombre_curso']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($id_grupo_sel && !empty($evaluaciones)): ?>
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted">Evaluación</label>
                <select name="evaluacion" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">— Selecciona una evaluación —</option>
                    <?php foreach ($evaluaciones as $ev): ?>
                    <option value="<?= $ev['id_evaluacion_pk'] ?>"
                            <?= $id_eval_sel === (int)$ev['id_evaluacion_pk'] ? 'selected' : '' ?>>
                        <?= sanitizar_html($ev['nombre_evaluacion']) ?>
                        <?php if ((int)$ev['pendientes'] > 0): ?>
                            (⚠ <?= $ev['pendientes'] ?> pendiente<?= $ev['pendientes'] > 1 ? 's' : '' ?>)
                        <?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <?php if ($id_grupo_sel): ?>
                <input type="hidden" name="grupo" value="<?= $id_grupo_sel ?>">
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- ── Tabla de Intentos ───────────────────────────────────── -->
<?php if ($id_grupo_sel && $id_eval_sel): ?>
<div class="teacher-card">
    <div class="card-header-custom">
        <h5><i class="fas fa-list-check"></i>
            Intentos —
            <?= sanitizar_html($intentos[0]['nombre_evaluacion'] ?? 'Evaluación') ?>
        </h5>
        <span class="badge bg-info-subtle text-info rounded-pill">
            <?= count($intentos) ?> intento(s)
        </span>
    </div>
    <div class="card-body-custom p-0">
        <?php if (empty($intentos)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-inbox fa-2x mb-3 d-block opacity-50"></i>
                No hay intentos registrados para esta evaluación en este grupo.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table-teacher w-100">
                    <thead>
                        <tr>
                            <th>Estudiante</th>
                            <th>Fecha Intento</th>
                            <th>Estado</th>
                            <th>Nota Actual</th>
                            <th>Nota Máxima</th>
                            <th class="text-center">Calificar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($intentos as $intento): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="student-avatar">
                                        <?= strtoupper(substr($intento['primer_nombre'], 0, 1) . substr($intento['primer_apellido'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold small">
                                            <?= sanitizar_html($intento['primer_nombre'] . ' ' . $intento['primer_apellido']) ?>
                                        </div>
                                        <div class="text-muted" style="font-size:0.75rem;">
                                            <?= sanitizar_html($intento['correo_electronico']) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-muted small">
                                <?= date('d/m/Y H:i', strtotime($intento['fecha_intento'])) ?>
                            </td>
                            <td>
                                <?php $est_rev = $intento['estado_revision']; ?>
                                <span class="badge-teacher <?= $est_rev === 'revisado' ? 'aprobado' : 'pendiente' ?>">
                                    <?= $est_rev === 'revisado' ? 'Revisado' : 'Pendiente' ?>
                                </span>
                            </td>
                            <td>
                                <?php $nota_actual = $intento['nota_obtenida']; ?>
                                <?php if ($nota_actual !== null): ?>
                                    <span class="fw-bold <?= (float)$nota_actual >= 60 ? 'text-success' : 'text-danger' ?>">
                                        <?= number_format((float)$nota_actual, 1) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?= (int)$intento['puntaje_maximo'] ?></td>
                            <td class="text-center">
                                <button class="btn-teacher-primary btn-sm btn-calificar"
                                        data-id="<?= $intento['id_intento_fk'] ?>"
                                        data-nombre="<?= sanitizar_html($intento['primer_nombre'] . ' ' . $intento['primer_apellido']) ?>"
                                        data-nota="<?= $nota_actual ?? '' ?>"
                                        data-retro="<?= sanitizar_html($intento['retroalimentacion'] ?? '') ?>"
                                        data-max="<?= (int)$intento['puntaje_maximo'] ?>"
                                        data-bs-toggle="modal" data-bs-target="#modalCalificar">
                                    <i class="fas fa-pen"></i> Calificar
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── Modal de Calificación ──────────────────────────────── -->
<div class="modal fade" id="modalCalificar" tabindex="-1" aria-labelledby="modalCalificarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header" style="background:var(--teacher-primary);color:white;border-radius:12px 12px 0 0;">
                <h5 class="modal-title fw-bold" id="modalCalificarLabel">
                    <i class="fas fa-pen me-2"></i>Calificar Intento
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="calificaciones.php?grupo=<?= $id_grupo_sel ?>&evaluacion=<?= $id_eval_sel ?>">
                <div class="modal-body p-4">
                    <?php require_once '../includes/csrf.php'; ?>
                    <?php imprimir_campo_csrf($pdo, 'calificar'); ?>
                    <input type="hidden" name="accion" value="calificar">
                    <input type="hidden" name="id_intento" id="modal_id_intento">

                    <p class="mb-3 text-muted small">
                        Estudiante: <strong id="modal_nombre_est" class="text-dark"></strong>
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            Nota Obtenida <span id="modal_nota_max" class="text-muted small"></span>
                        </label>
                        <input type="number" class="form-control" name="nota" id="modal_nota"
                               min="0" max="100" step="0.1" required placeholder="Ej: 85.5">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Retroalimentación</label>
                        <textarea class="form-control" name="retroalimentacion" id="modal_retro"
                                  rows="4" placeholder="Comentarios al estudiante..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-teacher-primary">
                        <i class="fas fa-save me-1"></i> Guardar Nota
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Poblar modal con datos del intento al abrirlo
document.querySelectorAll('.btn-calificar').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('modal_id_intento').value = this.dataset.id;
        document.getElementById('modal_nombre_est').textContent  = this.dataset.nombre;
        document.getElementById('modal_nota').value       = this.dataset.nota;
        document.getElementById('modal_nota').max         = this.dataset.max;
        document.getElementById('modal_nota_max').textContent = '(Máximo: ' + this.dataset.max + ')';
        document.getElementById('modal_retro').value      = this.dataset.retro;
    });
});
</script>

<?php elseif ($id_grupo_sel && empty($evaluaciones)): ?>
<div class="teacher-card">
    <div class="card-body-custom text-center py-5 text-muted">
        <i class="fas fa-clipboard-list fa-2x mb-3 d-block opacity-50"></i>
        <p>Este grupo no tiene evaluaciones configuradas aún.</p>
    </div>
</div>
<?php elseif (!$id_grupo_sel): ?>
<div class="teacher-card">
    <div class="card-body-custom text-center py-5 text-muted">
        <i class="fas fa-hand-pointer fa-2x mb-3 d-block opacity-50"></i>
        <h5>Selecciona un grupo para comenzar</h5>
        <p class="small">Elige un grupo del filtro de arriba para ver las evaluaciones disponibles.</p>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
