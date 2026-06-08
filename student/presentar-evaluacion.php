<?php
// /cursoonline/student/presentar-evaluacion.php
// ============================================================
// Presentar Evaluación — EduTech Academy
// ============================================================

$page_title = 'Evaluación';
require_once __DIR__ . '/includes/header.php';

$id_evaluacion = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id_evaluacion) {
    header("Location: " . BASE_URL . "student/evaluaciones.php?error=invalida");
    exit();
}

// ── Obtener evaluación y verificar acceso ──
$stmt = $pdo->prepare("
    SELECT e.*, c.id_curso_pk, c.titulo_curso,
           i.id_inscripcion_pk,
           m.id_modulo_pk, m.titulo_modulo, m.orden_modulo,
           (SELECT COUNT(*) FROM clases_curso cc
            WHERE cc.id_modulo_fk = m.id_modulo_pk AND cc.estado_activo = 1) AS total_clases_modulo,
           (SELECT COUNT(*) FROM progreso_clases pc
            JOIN clases_curso cc ON pc.id_clase_fk = cc.id_clase_pk
            WHERE cc.id_modulo_fk = m.id_modulo_pk
              AND pc.id_inscripcion_fk = i.id_inscripcion_pk
              AND pc.estado_completada = 1) AS clases_completadas_modulo
    FROM evaluaciones e
    JOIN cursos c ON e.id_curso_fk = c.id_curso_pk
    JOIN inscripciones i ON i.id_curso_fk = c.id_curso_pk AND i.id_usuario_fk = :uid
    JOIN modulos_curso m ON m.id_curso_fk = c.id_curso_pk
        AND m.orden_modulo = e.orden_evaluacion
    WHERE e.id_evaluacion_pk = :id_eval
      AND i.estado_activo = 1
      AND i.estado_inscripcion IN ('activa','completada')
      AND m.estado_activo = 1
");
$stmt->execute([':uid' => $id_usuario, ':id_eval' => $id_evaluacion]);
$eval = $stmt->fetch();

if (!$eval) {
    header("Location: " . BASE_URL . "student/evaluaciones.php?error=no_acceso");
    exit();
}

// ── Verificar módulo completado ──
$modulo_completo = $eval['total_clases_modulo'] > 0 && $eval['clases_completadas_modulo'] >= $eval['total_clases_modulo'];

// Verificar módulos anteriores completados
$modulos_prev_ok = true;
if ($eval['orden_modulo'] > 1) {
    $st = $pdo->prepare("
        SELECT COUNT(*) FROM modulos_curso m
        WHERE m.id_curso_fk = :idc AND m.estado_activo = 1 AND m.orden_modulo < :ord
          AND (SELECT COUNT(*) FROM clases_curso cc WHERE cc.id_modulo_fk = m.id_modulo_pk AND cc.estado_activo = 1) > 0
          AND (SELECT COUNT(*) FROM progreso_clases pc
               JOIN clases_curso cc ON pc.id_clase_fk = cc.id_clase_pk
               WHERE cc.id_modulo_fk = m.id_modulo_pk
                 AND pc.id_inscripcion_fk = :insc AND pc.estado_completada = 1)
            >= (SELECT COUNT(*) FROM clases_curso cc WHERE cc.id_modulo_fk = m.id_modulo_pk AND cc.estado_activo = 1)
    ");
    $st->execute([':idc' => $eval['id_curso_pk'], ':ord' => $eval['orden_modulo'], ':insc' => $eval['id_inscripcion_pk']]);
    $modulos_prev_ok = $st->fetchColumn() > 0;
}

if (!$modulo_completo || !$modulos_prev_ok) {
    header("Location: " . BASE_URL . "student/evaluaciones.php?error=bloqueada");
    exit();
}

// ── Contar intentos ──
$st_int = $pdo->prepare("
    SELECT COUNT(*) FROM intentos_evaluacion
    WHERE id_evaluacion_fk = :id_eval AND id_inscripcion_fk = :insc AND estado_activo = 1
");
$st_int->execute([':id_eval' => $id_evaluacion, ':insc' => $eval['id_inscripcion_pk']]);
$intentos_usados = (int)$st_int->fetchColumn();

if ($eval['intentos_permitidos'] > 0 && $intentos_usados >= $eval['intentos_permitidos']) {
    header("Location: " . BASE_URL . "student/evaluaciones.php?error=sin_intentos");
    exit();
}

// ── Obtener preguntas ──
$preguntas = $pdo->prepare("
    SELECT p.*, GROUP_CONCAT(CONCAT(o.id_opcion_pk, '::', o.texto_opcion, '::', o.explicacion_opcion)
                             ORDER BY o.orden_opcion SEPARATOR '||') AS opciones_raw
    FROM preguntas_evaluacion p
    LEFT JOIN opciones_pregunta o ON o.id_pregunta_fk = p.id_pregunta_pk AND o.estado_activo = 1
    WHERE p.id_evaluacion_fk = :id_eval AND p.estado_activo = 1
    GROUP BY p.id_pregunta_pk
    ORDER BY p.orden_pregunta
");
$preguntas->execute([':id_eval' => $id_evaluacion]);
$preguntas = $preguntas->fetchAll();

if (empty($preguntas)) {
    header("Location: " . BASE_URL . "student/evaluaciones.php?error=sin_preguntas");
    exit();
}

// ── Procesar envío ──
$resultado = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_evaluacion'])) {
    $token_recibido = $_POST['csrf_token'] ?? '';
    if (!validar_token_csrf($pdo, $token_recibido)) {
        $error = 'Token de seguridad inválido. Intenta de nuevo.';
    } else {

    $pdo->beginTransaction();
    try {
        $num_intento = $intentos_usados + 1;

        $stmt_ins = $pdo->prepare("
            INSERT INTO intentos_evaluacion
                (id_inscripcion_fk, id_evaluacion_fk, numero_intento, fecha_inicio, estado_intento)
            VALUES (:insc, :eval, :num, NOW(), 'en_progreso')
        ");
        $stmt_ins->execute([
            ':insc' => $eval['id_inscripcion_pk'],
            ':eval' => $id_evaluacion,
            ':num'  => $num_intento,
        ]);
        $id_intento = $pdo->lastInsertId();

        $total_puntaje = 0;
        $max_puntaje = 0;
        $detalle = [];

        foreach ($preguntas as $pq) {
            $max_puntaje += (float)$pq['puntaje_pregunta'];
            $respuesta = $_POST['pregunta_' . $pq['id_pregunta_pk']] ?? null;
            if ($respuesta === null) continue;

            $es_correcta = 0;
            $puntaje_obtenido = 0;

            if ($pq['tipo_pregunta'] === 'verdadero_falso') {
                // Las opciones: 1=Verdadero (correcta si es 1), 2=Falso
                $opciones_data = [];
                if ($pq['opciones_raw']) {
                    foreach (explode('||', $pq['opciones_raw']) as $raw) {
                        $parts = explode('::', $raw, 3);
                        if (count($parts) >= 2) {
                            $opciones_data[(int)$parts[0]] = ['texto' => $parts[1], 'explicacion' => $parts[2] ?? ''];
                        }
                    }
                }
                $id_opc = (int)$respuesta;
                $stmt_opc = $pdo->prepare("SELECT es_respuesta_correcta FROM opciones_pregunta WHERE id_opcion_pk = ?");
                $stmt_opc->execute([$id_opc]);
                $opc_data = $stmt_opc->fetch();
                $es_correcta = $opc_data ? (int)$opc_data['es_respuesta_correcta'] : 0;
                $puntaje_obtenido = $es_correcta ? (float)$pq['puntaje_pregunta'] : 0;
                $total_puntaje += $puntaje_obtenido;

                $detalle[] = [
                    'pregunta'   => $pq['enunciado_pregunta'],
                    'correcta'   => $es_correcta,
                    'puntaje'    => $puntaje_obtenido,
                    'id_opcion'  => $id_opc,
                ];
            } else {
                $id_opc = (int)$respuesta;
                $stmt_opc = $pdo->prepare("SELECT es_respuesta_correcta, explicacion_opcion FROM opciones_pregunta WHERE id_opcion_pk = ?");
                $stmt_opc->execute([$id_opc]);
                $opc_data = $stmt_opc->fetch();
                $es_correcta = $opc_data ? (int)$opc_data['es_respuesta_correcta'] : 0;
                $puntaje_obtenido = $es_correcta ? (float)$pq['puntaje_pregunta'] : 0;
                $total_puntaje += $puntaje_obtenido;

                $detalle[] = [
                    'pregunta'   => $pq['enunciado_pregunta'],
                    'correcta'   => $es_correcta,
                    'puntaje'    => $puntaje_obtenido,
                    'id_opcion'  => $id_opc,
                ];
            }

            // Guardar respuesta
            $stmt_r = $pdo->prepare("
                INSERT INTO respuestas_evaluacion
                    (id_intento_fk, id_pregunta_fk, id_opcion_seleccionada_fk, es_correcta, puntaje_obtenido)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt_r->execute([$id_intento, $pq['id_pregunta_pk'], $_POST['pregunta_' . $pq['id_pregunta_pk']], $es_correcta, $puntaje_obtenido]);
        }

        $aprobado = $total_puntaje >= (float)$eval['puntaje_minimo_aprobacion'] ? 1 : 0;

        $stmt_upd = $pdo->prepare("
            UPDATE intentos_evaluacion
            SET fecha_fin = NOW(), puntaje_obtenido = :puntaje, estado_intento = 'completado', estado_aprobado = :apro
            WHERE id_intento_pk = :id
        ");
        $stmt_upd->execute([
            ':puntaje' => $total_puntaje,
            ':apro'    => $aprobado,
            ':id'      => $id_intento,
        ]);

        $pdo->commit();

        $resultado = [
            'intento_id'  => $id_intento,
            'numero'      => $num_intento,
            'puntaje'     => $total_puntaje,
            'maximo'      => $max_puntaje,
            'aprobado'    => $aprobado,
            'minimo'      => (float)$eval['puntaje_minimo_aprobacion'],
            'detalle'     => $detalle,
            'intentos_restantes' => $eval['intentos_permitidos'] > 0 ? max(0, $eval['intentos_permitidos'] - $num_intento) : -1,
        ];

    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Error al guardar la evaluación: ' . $e->getMessage();
    }
    }
}
?>
<div class="container py-4">

<?php if ($resultado): ?>
<!-- ── RESULTADO ── -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body text-center py-5">
        <?php if ($resultado['aprobado']): ?>
        <div class="mb-3"><i class="fas fa-check-circle text-success fs-1"></i></div>
        <h2 class="fw-bold text-success">¡Evaluación Aprobada!</h2>
        <?php else: ?>
        <div class="mb-3"><i class="fas fa-times-circle text-danger fs-1"></i></div>
        <h2 class="fw-bold text-danger">Evaluación No Aprobada</h2>
        <?php endif; ?>
        <p class="lead mb-2">
            Puntaje: <strong><?= number_format($resultado['puntaje'], 1) ?></strong> / <?= number_format($resultado['maximo'], 1) ?>
            <span class="text-muted">(Mín: <?= number_format($resultado['minimo'], 1) ?>)</span>
        </p>
        <p class="text-muted mb-0">Intento #<?= $resultado['numero'] ?>
            <?php if ($resultado['intentos_restantes'] > 0): ?>
                — Te quedan <strong><?= $resultado['intentos_restantes'] ?></strong> intento<?= $resultado['intentos_restantes'] !== 1 ? 's' : '' ?>
            <?php elseif ($resultado['intentos_restantes'] === 0): ?>
                — <span class="text-danger">No quedan más intentos</span>
            <?php else: ?>
                — Intentos ilimitados
            <?php endif; ?>
        </p>
    </div>
</div>

<!-- Detalle por pregunta -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-0 px-4 py-3">
        <h3 class="h6 fw-bold m-0">Detalle de respuestas</h3>
    </div>
    <div class="card-body p-0">
        <?php foreach ($resultado['detalle'] as $i => $d): 
            $pq = $preguntas[$i];
            // Obtener opciones para esta pregunta
            $opciones = [];
            if ($pq['opciones_raw']) {
                foreach (explode('||', $pq['opciones_raw']) as $raw) {
                    $parts = explode('::', $raw, 3);
                    if (count($parts) >= 2) {
                        $opciones[(int)$parts[0]] = [
                            'texto' => $parts[1],
                            'explicacion' => $parts[2] ?? '',
                        ];
                    }
                }
            }
            $opc_seleccionada = $opciones[$d['id_opcion']] ?? ['texto' => '—', 'explicacion' => ''];
        ?>
        <div class="px-4 py-3 border-bottom border-light">
            <div class="d-flex align-items-start gap-3">
                <span class="badge bg-<?= $d['correcta'] ? 'success' : 'danger' ?> rounded-pill mt-1" style="font-size:.75rem;">
                    <?= $d['correcta'] ? '+'.$d['puntaje'] : '0' ?>
                </span>
                <div class="flex-grow-1">
                    <p class="fw-semibold small mb-1"><?= sanitizar_html($pq['enunciado_pregunta']) ?></p>
                    <small class="text-muted d-block">
                        Tu respuesta: <strong><?= sanitizar_html($opc_seleccionada['texto']) ?></strong>
                    </small>
                    <?php if (!$d['correcta'] && $opc_seleccionada['explicacion']): ?>
                    <small class="text-info d-block mt-1">
                        <i class="fas fa-info-circle me-1"></i><?= sanitizar_html($opc_seleccionada['explicacion']) ?>
                    </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="text-center mb-4">
    <a href="<?= BASE_URL ?>student/evaluaciones.php" class="btn btn-outline-primary rounded-pill px-4">
        <i class="fas fa-arrow-left me-1"></i>Volver a evaluaciones
    </a>
    <?php if (!$resultado['aprobado'] && $resultado['intentos_restantes'] !== 0): ?>
    <a href="<?= BASE_URL ?>student/presentar-evaluacion.php?id=<?= $id_evaluacion ?>" class="btn btn-primary rounded-pill px-4 ms-2">
        <i class="fas fa-redo me-1"></i>Intentar de nuevo
    </a>
    <?php endif; ?>
</div>

<?php else: ?>
<!-- ── FORMULARIO DE EVALUACIÓN ── -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header border-0 px-4 py-3 d-flex justify-content-between align-items-center"
         style="background:linear-gradient(135deg,#1A3C6E,#2563EB);color:#fff;">
        <div>
            <h1 class="h5 fw-bold m-0"><?= sanitizar_html($eval['titulo_evaluacion']) ?></h1>
            <small class="text-white-50"><?= sanitizar_html($eval['titulo_curso']) ?> — <?= sanitizar_html($eval['titulo_modulo']) ?></small>
        </div>
        <div class="text-end">
            <small class="d-block fw-bold"><?= count($preguntas) ?> preguntas</small>
            <?php if ($eval['tiempo_limite_minutos']): ?>
            <small class="text-white-50"><i class="far fa-clock me-1"></i><?= (int)$eval['tiempo_limite_minutos'] ?> min</small>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body px-4 py-3 bg-light border-bottom">
        <small class="text-muted">
            <i class="fas fa-info-circle me-1"></i>
            Intento #<?= $intentos_usados + 1 ?> de <?= $eval['intentos_permitidos'] > 0 ? (int)$eval['intentos_permitidos'] : '∞' ?>
            &middot; Mínimo para aprobar: <strong><?= number_format($eval['puntaje_minimo_aprobacion'], 0) ?>/<?= number_format($eval['puntaje_maximo'], 0) ?></strong>
        </small>
    </div>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger m-3"><?= sanitizar_html($error) ?></div>
    <?php endif; ?>

    <form method="post" id="formEvaluacion" class="p-0">
        <?php imprimir_campo_csrf($pdo, 'evaluacion'); ?>

        <?php foreach ($preguntas as $i => $pq):
            $opciones = [];
            if ($pq['opciones_raw']) {
                foreach (explode('||', $pq['opciones_raw']) as $raw) {
                    $parts = explode('::', $raw, 3);
                    if (count($parts) >= 2) {
                        $opciones[(int)$parts[0]] = ['texto' => $parts[1], 'explicacion' => $parts[2] ?? ''];
                    }
                }
            }
        ?>
        <div class="px-4 py-3 <?= $i > 0 ? 'border-top border-light' : '' ?>">
            <p class="fw-semibold mb-2">
                <span class="badge bg-secondary me-1"><?= $i + 1 ?></span>
                <?= sanitizar_html($pq['enunciado_pregunta']) ?>
                <small class="text-muted fw-normal">(<?= number_format($pq['puntaje_pregunta'], 0) ?> pts)</small>
            </p>
            <?php if ($pq['tipo_pregunta'] === 'verdadero_falso'): ?>
                <div class="d-flex gap-3">
                <?php foreach ($opciones as $id_opc => $opc): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="radio"
                               name="pregunta_<?= $pq['id_pregunta_pk'] ?>"
                               value="<?= $id_opc ?>"
                               id="opc_<?= $pq['id_pregunta_pk'] ?>_<?= $id_opc ?>"
                               required>
                        <label class="form-check-label small" for="opc_<?= $pq['id_pregunta_pk'] ?>_<?= $id_opc ?>">
                            <?= sanitizar_html($opc['texto']) ?>
                        </label>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="d-flex flex-column gap-2">
                <?php foreach ($opciones as $id_opc => $opc): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="radio"
                               name="pregunta_<?= $pq['id_pregunta_pk'] ?>"
                               value="<?= $id_opc ?>"
                               id="opc_<?= $pq['id_pregunta_pk'] ?>_<?= $id_opc ?>"
                               required>
                        <label class="form-check-label small" for="opc_<?= $pq['id_pregunta_pk'] ?>_<?= $id_opc ?>">
                            <?= sanitizar_html($opc['texto']) ?>
                        </label>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <div class="px-4 py-3 border-top border-light bg-light text-center">
            <button type="submit" name="enviar_evaluacion" value="1"
                    class="btn btn-primary btn-lg rounded-pill px-5 shadow-sm"
                    onclick="return confirm('¿Estás seguro de enviar la evaluación? No podrás modificar las respuestas después de enviar.');">
                <i class="fas fa-paper-plane me-2"></i>Enviar evaluación
            </button>
        </div>
    </form>
</div>
<?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
