<?php
// /cursoonline/student/mis-notas.php
// ============================================================
// Notas del Estudiante: Evaluaciones + Actividades
// ============================================================

$page_title = 'Mis Notas';
require_once __DIR__ . '/includes/header.php';

$id_usuario = $_SESSION['id_usuario'] ?? 0;

// ── 1. Obtener actividades (calificaciones cargadas por docentes) ──
$stmt_act = $pdo->prepare("
    SELECT c.nota_obtenida, c.observaciones_profesor, c.fecha_calificacion,
           ac.nombre_actividad, ac.puntaje_maximo, ac.porcentaje_nota_final, ac.tipo_actividad,
           cur.id_curso_pk, cur.titulo_curso,
           u.primer_nombre as prof_nombre, u.primer_apellido as prof_apellido
    FROM calificaciones c
    JOIN actividades_calificacion ac ON c.id_actividad_fk = ac.id_actividad_pk
    JOIN inscripciones i ON c.id_inscripcion_fk = i.id_inscripcion_pk
    JOIN cursos cur ON i.id_curso_fk = cur.id_curso_pk
    JOIN usuarios u ON c.id_profesor_fk = u.id_usuario_pk
    WHERE i.id_usuario_fk = :uid
      AND c.estado_activo = 1
      AND i.estado_activo = 1
    ORDER BY cur.titulo_curso, c.fecha_calificacion DESC
");
$stmt_act->execute([':uid' => $id_usuario]);
$actividades = $stmt_act->fetchAll();

// ── 2. Obtener intentos de evaluaciones ──
$stmt_chk = $pdo->query("SELECT 1 FROM information_schema.ROUTINES WHERE ROUTINE_NAME = 'sp_obtener_evaluaciones_notas' AND ROUTINE_SCHEMA = DATABASE()");
$sp_eval_exists = (bool)$stmt_chk->fetchColumn();

if ($sp_eval_exists) {
    $stmt_eval = $pdo->prepare("CALL sp_obtener_evaluaciones_notas(:uid)");
    $stmt_eval->execute([':uid' => $id_usuario]);
    $evaluaciones_raw = $stmt_eval->fetchAll();
    $stmt_eval->closeCursor();
} else {
    $stmt_eval = $pdo->prepare("
        SELECT cur.id_curso_pk, cur.titulo_curso,
               e.id_evaluacion_pk, e.titulo_evaluacion,
               e.puntaje_maximo AS eval_puntaje_maximo,
               e.puntaje_minimo_aprobacion AS eval_puntaje_minimo,
               ie.id_intento_pk, ie.numero_intento,
               ie.puntaje_obtenido, ie.estado_aprobado,
               ie.fecha_fin AS fecha_intento,
               i.id_inscripcion_pk,
               m.orden_modulo, m.titulo_modulo
        FROM inscripciones i
        JOIN cursos cur ON i.id_curso_fk = cur.id_curso_pk
        JOIN modulos_curso m ON m.id_curso_fk = cur.id_curso_pk AND m.estado_activo = 1
        JOIN evaluaciones e ON e.id_curso_fk = cur.id_curso_pk
            AND e.orden_evaluacion = m.orden_modulo
            AND e.estado_activo = 1
        LEFT JOIN intentos_evaluacion ie ON ie.id_evaluacion_fk = e.id_evaluacion_pk
            AND ie.id_inscripcion_fk = i.id_inscripcion_pk
            AND ie.estado_activo = 1
        WHERE i.id_usuario_fk = :uid
          AND i.estado_activo = 1
          AND i.estado_inscripcion IN ('activa','completada')
        ORDER BY cur.titulo_curso, m.orden_modulo, ie.numero_intento DESC
    ");
    $stmt_eval->execute([':uid' => $id_usuario]);
    $evaluaciones_raw = $stmt_eval->fetchAll();
}

// ── 3. Agrupar por curso ──
$cursos_data = [];

foreach ($actividades as $a) {
    $idc = $a['id_curso_pk'];
    if (!isset($cursos_data[$idc])) {
        $cursos_data[$idc] = ['titulo' => $a['titulo_curso'], 'actividades' => [], 'evaluaciones' => []];
    }
    $cursos_data[$idc]['actividades'][] = $a;
}

foreach ($evaluaciones_raw as $e) {
    $idc = $e['id_curso_pk'];
    if (!isset($cursos_data[$idc])) {
        $cursos_data[$idc] = ['titulo' => $e['titulo_curso'], 'actividades' => [], 'evaluaciones' => []];
    }
    // Solo mostrar si hay un intento registrado
    if ($e['id_intento_pk']) {
        $cursos_data[$idc]['evaluaciones'][] = $e;
    }
}

// Orden alfabético por título
ksort($cursos_data);
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 fw-bold text-primary m-0"><i class="fas fa-graduation-cap me-2"></i>Mis Notas</h1>
        <p class="text-muted m-0 small">Resultados de evaluaciones y calificaciones de tus actividades.</p>
    </div>
</div>

<?php if (empty($cursos_data)): ?>
<div class="text-center py-5">
    <div class="card p-5 border border-dashed rounded-4">
        <i class="fas fa-clipboard-list text-muted fs-1 mb-3"></i>
        <h2 class="h4 fw-bold">Aún no tienes notas</h2>
        <p class="text-muted mb-0">Completa evaluaciones y actividades para ver tus resultados aquí.</p>
    </div>
</div>
<?php else: ?>
<?php $idx_curso = 0; ?>
<div class="row g-4">
    <?php foreach ($cursos_data as $idc => $curso): $idx_curso++;
        $collapse_id = 'collapseNotas' . $idc;
    ?>
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-header border-0 px-4 py-3 d-flex align-items-center <?= $idx_curso === 1 ? '' : 'collapsed' ?>"
                 style="background:linear-gradient(135deg,#1A3C6E,#2563EB);color:#fff;cursor:pointer;"
                 data-bs-toggle="collapse" data-bs-target="#<?= $collapse_id ?>" aria-expanded="<?= $idx_curso === 1 ? 'true' : 'false' ?>">
                <div class="d-flex align-items-center gap-2 flex-grow-1">
                    <i class="fas fa-book"></i>
                    <h2 class="h6 fw-bold m-0"><?= sanitizar_html($curso['titulo']) ?></h2>
                </div>
                <?php
                $total_actividades = count($curso['actividades']);
                $total_evaluaciones = count($curso['evaluaciones']);
                ?>
                <small class="text-white-50 me-2">
                    <?= $total_evaluaciones ?> evaluación<?= $total_evaluaciones !== 1 ? 'es' : '' ?>
                    &middot; <?= $total_actividades ?> actividad<?= $total_actividades !== 1 ? 'es' : '' ?>
                </small>
                <i class="fas fa-chevron-down transition-rotate" style="font-size:.85rem;opacity:.7;"></i>
            </div>

            <div class="collapse <?= $idx_curso === 1 ? 'show' : '' ?>" id="<?= $collapse_id ?>">
            <div class="card-body p-0">

                <!-- Evaluaciones -->
                <?php if (!empty($curso['evaluaciones'])): ?>
                <div class="px-4 pt-3 pb-2">
                    <h3 class="small fw-bold text-uppercase text-secondary m-0" style="letter-spacing:.4px;">
                        <i class="fas fa-file-signature me-1"></i>Evaluaciones
                    </h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle m-0 text-start small">
                        <thead class="table-light" style="font-size:.75rem;">
                            <tr>
                                <th class="ps-4">Evaluación</th>
                                <th>Módulo</th>
                                <th>Intento</th>
                                <th>Puntaje</th>
                                <th>Estado</th>
                                <th class="pe-4">Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($curso['evaluaciones'] as $ev):
                                $pct = $ev['eval_puntaje_maximo'] > 0
                                    ? round(($ev['puntaje_obtenido'] / $ev['eval_puntaje_maximo']) * 100, 1)
                                    : 0;
                            ?>
                            <tr>
                                <td class="ps-4 fw-semibold"><?= sanitizar_html($ev['titulo_evaluacion']) ?></td>
                                <td><span class="badge bg-light text-dark"><?= sanitizar_html($ev['titulo_modulo']) ?></span></td>
                                <td>#<?= (int)$ev['numero_intento'] ?></td>
                                <td class="fw-bold">
                                    <span class="<?= $pct >= 70 ? 'text-success' : 'text-danger' ?>">
                                        <?= number_format($ev['puntaje_obtenido'], 1) ?>
                                    </span>
                                    <span class="text-muted">/ <?= number_format($ev['eval_puntaje_maximo'], 0) ?> (<?= $pct ?>%)</span>
                                </td>
                                <td>
                                    <?php if ($ev['estado_aprobado'] == 1): ?>
                                        <span class="badge bg-success rounded-pill px-3">Aprobado</span>
                                    <?php elseif ($ev['estado_aprobado'] == 0): ?>
                                        <span class="badge bg-danger rounded-pill px-3">Reprobado</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark rounded-pill px-3">Pendiente</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-muted"><?= $ev['fecha_intento'] ? date('d/m/Y', strtotime($ev['fecha_intento'])) : '—' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <!-- Actividades -->
                <?php if (!empty($curso['actividades'])): ?>
                <div class="px-4 pt-3 pb-2 <?= !empty($curso['evaluaciones']) ? 'border-top border-light mt-2' : '' ?>">
                    <h3 class="small fw-bold text-uppercase text-secondary m-0" style="letter-spacing:.4px;">
                        <i class="fas fa-tasks me-1"></i>Actividades y Talleres
                    </h3>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle m-0 text-start small">
                        <thead class="table-light" style="font-size:.75rem;">
                            <tr>
                                <th class="ps-4">Actividad</th>
                                <th>Tipo</th>
                                <th>Peso</th>
                                <th>Nota</th>
                                <th>Docente</th>
                                <th class="pe-4">Observaciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($curso['actividades'] as $ac):
                                $pct_act = $ac['puntaje_maximo'] > 0
                                    ? round(($ac['nota_obtenida'] / $ac['puntaje_maximo']) * 100, 1)
                                    : 0;
                            ?>
                            <tr>
                                <td class="ps-4 fw-semibold"><?= sanitizar_html($ac['nombre_actividad']) ?></td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary text-uppercase" style="font-size:.7rem;">
                                        <?= sanitizar_html($ac['tipo_actividad']) ?>
                                    </span>
                                </td>
                                <td><?= number_format($ac['porcentaje_nota_final'], 1) ?>%</td>
                                <td class="fw-bold">
                                    <span class="<?= $pct_act >= 60 ? 'text-success' : 'text-danger' ?>">
                                        <?= number_format($ac['nota_obtenida'], 1) ?>
                                    </span>
                                    <span class="text-muted">/ <?= number_format($ac['puntaje_maximo'], 0) ?> (<?= $pct_act ?>%)</span>
                                </td>
                                <td class="small"><?= sanitizar_html($ac['prof_nombre'] . ' ' . $ac['prof_apellido']) ?></td>
                                <td class="pe-4 small text-muted" style="max-width:220px;">
                                    <?= sanitizar_html($ac['observaciones_profesor'] ?: '—') ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <?php if (empty($curso['evaluaciones']) && empty($curso['actividades'])): ?>
                <div class="text-center py-4">
                    <small class="text-muted">Este curso aún no tiene notas registradas.</small>
                </div>
                <?php endif; ?>

            </div>
            </div><!-- /collapse -->
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
