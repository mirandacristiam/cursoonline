<?php
// /cursoonline/student/evaluaciones.php
// ============================================================
// Evaluaciones Habilitadas y Calificaciones de Exámenes
// ============================================================

$page_title = 'Mis Evaluaciones';
require_once __DIR__ . '/includes/header.php';

// Obtener las evaluaciones vinculadas a los cursos donde el estudiante está inscrito
// y calcular cuántas clases ha visto el estudiante en cada curso para ver si se habilitan
$stmt_evals = $pdo->prepare("
    SELECT e.id_evaluacion_pk, e.titulo_evaluacion, e.descripcion_evaluacion, 
           e.numero_clases_requeridas, e.puntaje_maximo, e.puntaje_minimo_aprobacion,
           c.titulo_curso, c.id_curso_pk, i.id_inscripcion_pk, i.porcentaje_progreso
    FROM inscripciones i
    JOIN cursos c ON i.id_curso_fk = c.id_curso_pk
    JOIN evaluaciones e ON c.id_curso_pk = e.id_curso_fk
    WHERE i.id_usuario_fk = :id_user 
      AND i.estado_activo = 1 
      AND e.estado_activo = 1
    ORDER BY c.titulo_curso ASC, e.orden_evaluacion ASC
");
$stmt_evals->execute([':id_user' => $id_usuario]);
$evaluaciones = $stmt_evals->fetchAll();

// Guardar en array con datos procesados de habilitación y calificaciones previas
$lista_evaluaciones = [];
foreach ($evaluaciones as $eval) {
    // 1. Contar total de clases vistas en este curso por este estudiante
    $stmt_vistas = $pdo->prepare("
        SELECT COUNT(*) 
        FROM progreso_clases pc
        JOIN clases_curso cc ON pc.id_clase_fk = cc.id_clase_pk
        JOIN modulos_curso mc ON cc.id_modulo_fk = mc.id_modulo_pk
        WHERE pc.id_inscripcion_fk = :id_ins 
          AND pc.estado_completada = 1 
          AND pc.estado_activo = 1 
          AND cc.estado_activo = 1
    ");
    $stmt_vistas->execute([':id_ins' => $eval['id_inscripcion_pk']]);
    $clases_vistas = (int)$stmt_vistas->fetchColumn();

    // 2. Obtener intentos realizados para esta evaluación
    $stmt_intentos = $pdo->prepare("
        SELECT id_intento_pk, numero_intento, puntaje_obtenido, estado_intento, estado_aprobado, fecha_inicio
        FROM intentos_evaluacion
        WHERE id_inscripcion_fk = :id_ins 
          AND id_evaluacion_fk = :id_eval 
          AND estado_activo = 1
        ORDER BY numero_intento DESC
    ");
    $stmt_intentos->execute([
        ':id_ins'  => $eval['id_inscripcion_pk'],
        ':id_eval' => $eval['id_evaluacion_pk']
    ]);
    $intentos = $stmt_intentos->fetchAll();

    $habilitada = ($clases_vistas >= (int)$eval['numero_clases_requeridas']);

    $lista_evaluaciones[] = [
        'eval'          => $eval,
        'clases_vistas' => $clases_vistas,
        'intentos'      => $intentos,
        'habilitada'    => $habilitada
    ];
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 fw-bold text-primary mb-1">Mis Evaluaciones</h1>
        <p class="text-muted m-0">Aquí encontrarás las evaluaciones y exámenes de tus cursos inscritos.</p>
    </div>
</div>

<div class="row g-4">
    <?php if (empty($lista_evaluaciones)): ?>
        <div class="col-12 text-center py-5">
            <div class="card p-5 border border-dashed rounded-4">
                <i class="fas fa-file-signature text-muted fs-1 mb-3"></i>
                <h2 class="h4 fw-bold">No hay evaluaciones disponibles</h2>
                <p class="text-muted m-0">Inscríbete en cursos para comenzar tu formación y desbloquear tus exámenes.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($lista_evaluaciones as $item): ?>
            <?php 
            $eval = $item['eval'];
            $habilitada = $item['habilitada'];
            $intentos = $item['intentos'];
            $clases_vistas = $item['clases_vistas'];
            
            // Determinar si ya aprobó
            $aprobado = false;
            $mejor_nota = null;
            foreach ($intentos as $int) {
                if ($int['estado_aprobado'] == 1) {
                    $aprobado = true;
                }
                if ($mejor_nota === null || $int['puntaje_obtenido'] > $mejor_nota) {
                    $mejor_nota = $int['puntaje_obtenido'];
                }
            }
            ?>
            <div class="col-lg-6">
                <article class="card-custom h-100">
                    <div class="card-header-custom d-flex justify-content-between align-items-center">
                        <div>
                            <span class="xsmall text-muted text-uppercase fw-semibold d-block"><?= sanitizar_html($eval['titulo_curso']) ?></span>
                            <span class="fw-bold fs-5 text-primary"><?= sanitizar_html($eval['titulo_evaluacion']) ?></span>
                        </div>
                        <span class="badge <?= $habilitada ? ($aprobado ? 'badge-active' : 'badge-pending') : 'badge-cancelled' ?>">
                            <?php if (!$habilitada): ?>
                                <i class="fas fa-lock"></i> Bloqueada
                            <?php elseif ($aprobado): ?>
                                <i class="fas fa-check-circle"></i> Aprobada
                            <?php else: ?>
                                <i class="fas fa-unlock"></i> Habilitada
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="card-body-custom">
                        <p class="text-muted small mb-3"><?= sanitizar_html($eval['descripcion_evaluacion']) ?></p>
                        
                        <div class="row g-3 p-3 bg-light rounded-3 mb-3 small">
                            <div class="col-6">
                                <span class="text-muted d-block">Clases Requeridas:</span>
                                <span class="fw-semibold"><?= $eval['numero_clases_requeridas'] ?> clases</span>
                            </div>
                            <div class="col-6">
                                <span class="text-muted d-block">Tus Clases Vistas:</span>
                                <span class="fw-semibold"><?= $clases_vistas ?> vistas</span>
                            </div>
                            <div class="col-6">
                                <span class="text-muted d-block">Mínimo para aprobar:</span>
                                <span class="fw-semibold text-danger"><?= number_format($eval['puntaje_minimo_aprobacion'], 0) ?> / <?= number_format($eval['puntaje_maximo'], 0) ?> pts</span>
                            </div>
                            <div class="col-6">
                                <span class="text-muted d-block">Tu mejor puntaje:</span>
                                <span class="fw-semibold text-success"><?= $mejor_nota !== null ? number_format($mejor_nota, 2) . ' pts' : 'Sin intentos' ?></span>
                            </div>
                        </div>

                        <?php if (!$habilitada): ?>
                            <div class="alert alert-warning small m-0">
                                <i class="fas fa-exclamation-triangle me-1"></i> Debes completar al menos <strong><?= $eval['numero_clases_requeridas'] ?> clases</strong> en el aula virtual para desbloquear esta evaluación académica.
                            </div>
                        <?php else: ?>
                            <h3 class="h6 fw-bold mb-2">Intentos Recientes:</h3>
                            <?php if (empty($intentos)): ?>
                                <p class="text-muted small m-0 mb-3">No has realizado ningún intento todavía.</p>
                            <?php else: ?>
                                <div class="table-responsive mb-3">
                                    <table class="table table-sm table-hover align-middle small m-0">
                                        <thead>
                                            <tr>
                                                <th>Intento</th>
                                                <th>Fecha</th>
                                                <th>Puntaje</th>
                                                <th>Resultado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($intentos as $int): ?>
                                                <tr>
                                                    <td>#<?= $int['numero_intento'] ?></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($int['fecha_inicio'])) ?></td>
                                                    <td class="fw-semibold"><?= number_format($int['puntaje_obtenido'], 1) ?> pts</td>
                                                    <td>
                                                        <span class="badge <?= $int['estado_aprobado'] ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>">
                                                            <?= $int['estado_aprobado'] ? 'Aprobado' : 'Reprobado' ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!$aprobado): ?>
                                <div class="d-grid">
                                    <a href="#" class="btn btn-primary rounded-pill shadow-sm disabled" onclick="alert('Módulo de cuestionarios interactivos. Habilitado en la siguiente fase.'); return false;">
                                        <i class="fas fa-edit me-1"></i> Presentar Examen
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-success small m-0 text-center">
                                    <i class="fas fa-check-double me-1"></i> ¡Felicitaciones! Has aprobado esta evaluación académica.
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
