<?php
// /cursoonline/student/evaluaciones.php
// ============================================================
// Evaluaciones por Módulo — EduTech Academy
// ============================================================

$page_title = 'Mis Evaluaciones';
require_once __DIR__ . '/includes/header.php';

// ── Obtener cursos del estudiante con módulos + evaluaciones ──
$cursos_data = $pdo->prepare("
    SELECT c.id_curso_pk, c.titulo_curso, c.imagen_portada,
           i.id_inscripcion_pk,
           m.id_modulo_pk, m.titulo_modulo, m.orden_modulo,
           e.id_evaluacion_pk, e.titulo_evaluacion, e.descripcion_evaluacion,
           e.puntaje_maximo, e.puntaje_minimo_aprobacion,
           e.numero_clases_requeridas, e.intentos_permitidos, e.tiempo_limite_minutos,
           (SELECT COUNT(*) FROM clases_curso cc
            WHERE cc.id_modulo_fk = m.id_modulo_pk AND cc.estado_activo = 1) AS total_clases_modulo,
           (SELECT COUNT(*) FROM progreso_clases pc
            JOIN clases_curso cc ON pc.id_clase_fk = cc.id_clase_pk
            WHERE cc.id_modulo_fk = m.id_modulo_pk
              AND pc.id_inscripcion_fk = i.id_inscripcion_pk
              AND pc.estado_completada = 1) AS clases_completadas_modulo
    FROM inscripciones i
    JOIN cursos c ON i.id_curso_fk = c.id_curso_pk
    JOIN modulos_curso m ON m.id_curso_fk = c.id_curso_pk AND m.estado_activo = 1
    LEFT JOIN evaluaciones e ON e.id_curso_fk = c.id_curso_pk
        AND e.estado_activo = 1
        AND e.orden_evaluacion = m.orden_modulo
    WHERE i.id_usuario_fk = :uid
      AND i.estado_activo = 1
      AND i.estado_inscripcion IN ('activa','completada')
    ORDER BY c.titulo_curso, m.orden_modulo
");
$cursos_data->execute([':uid' => $id_usuario]);
$rows = $cursos_data->fetchAll();

// ── Agrupar ──
$cursos = [];
$modulos_por_curso = [];
foreach ($rows as $r) {
    $idc = $r['id_curso_pk'];
    $idm = $r['id_modulo_pk'];

    if (!isset($cursos[$idc])) {
        $cursos[$idc] = [
            'titulo'      => $r['titulo_curso'],
            'portada'     => $r['imagen_portada'],
            'inscripcion' => $r['id_inscripcion_pk'],
        ];
    }

    if (!isset($modulos_por_curso[$idc][$idm])) {
        $modulos_por_curso[$idc][$idm] = [
            'titulo'       => $r['titulo_modulo'],
            'orden'        => $r['orden_modulo'],
            'total_clases' => (int)$r['total_clases_modulo'],
            'completadas'  => (int)$r['clases_completadas_modulo'],
            'eval_id'      => $r['id_evaluacion_pk'],
            'eval_titulo'  => $r['titulo_evaluacion'],
            'eval_desc'    => $r['descripcion_evaluacion'],
            'eval_max'     => $r['puntaje_maximo'],
            'eval_min'     => $r['puntaje_minimo_aprobacion'],
            'eval_intentos'=> (int)$r['intentos_permitidos'],
            'eval_tiempo'  => $r['tiempo_limite_minutos'],
        ];
    }
}

// ── Intentos del estudiante ──
$intentos = [];
$ids_eval = array_unique(array_filter(array_column($rows, 'id_evaluacion_pk')));
if (!empty($ids_eval)) {
    $ph = implode(',', array_fill(0, count($ids_eval), '?'));
    $stmt_int = $pdo->prepare("
        SELECT id_evaluacion_fk, id_inscripcion_fk, numero_intento,
               puntaje_obtenido, estado_aprobado
        FROM intentos_evaluacion
        WHERE id_evaluacion_fk IN ($ph) AND estado_activo = 1
        ORDER BY id_evaluacion_fk, numero_intento DESC
    ");
    $stmt_int->execute(array_values($ids_eval));
    foreach ($stmt_int->fetchAll() as $int) {
        $key = $int['id_evaluacion_fk'] . '_' . $int['id_inscripcion_fk'];
        if (!isset($intentos[$key])) $intentos[$key] = [];
        $intentos[$key][] = $int;
    }
}
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 fw-bold text-primary m-0"><i class="fas fa-file-signature me-2"></i>Mis Evaluaciones</h1>
</div>

<?php if (empty($rows)): ?>
<div class="text-center py-5">
    <div class="card p-5 border border-dashed rounded-4">
        <i class="fas fa-file-signature text-muted fs-1 mb-3"></i>
        <h2 class="h4 fw-bold">No hay evaluaciones disponibles</h2>
        <p class="text-muted mb-0">Inscríbete en cursos y avanza en los módulos para desbloquear tus evaluaciones.</p>
    </div>
</div>
<?php else:

$ids_curso = array_keys($cursos);
$idx_curso = 0;
foreach ($ids_curso as $idc):
    $idx_curso++;
    $curso = $cursos[$idc];
    $modulos = $modulos_por_curso[$idc] ?? [];
    uasort($modulos, function($a, $b) { return $a['orden'] - $b['orden']; });

    $modulo_anterior_ok = true;
    $collapse_id = 'collapseEval' . $idc;
?>
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header border-0 px-4 py-3 d-flex align-items-center gap-3 <?= $idx_curso === 1 ? '' : 'collapsed' ?>"
         style="background:linear-gradient(135deg,#1A3C6E,#2563EB);color:#fff;cursor:pointer;"
         data-bs-toggle="collapse" data-bs-target="#<?= $collapse_id ?>" aria-expanded="<?= $idx_curso === 1 ? 'true' : 'false' ?>">
        <img src="<?= $curso['portada'] ?: 'assets/img/default-course.jpg' ?>"
             alt="" style="width:48px;height:32px;object-fit:cover;border-radius:6px;">
        <div class="flex-grow-1">
            <h2 class="h6 fw-bold m-0"><?= sanitizar_html($curso['titulo']) ?></h2>
            <small class="text-white-50"><?= count($modulos) ?> módulo<?= count($modulos) !== 1 ? 's' : '' ?></small>
        </div>
        <i class="fas fa-chevron-down transition-rotate" style="font-size:.85rem;opacity:.7;"></i>
    </div>

    <div class="collapse <?= $idx_curso === 1 ? 'show' : '' ?>" id="<?= $collapse_id ?>">
    <div class="list-group list-group-flush">
        <?php foreach ($modulos as $idm => $mod):
            $modulo_completo = $mod['total_clases'] > 0 && $mod['completadas'] >= $mod['total_clases'];
            $modulo_bloqueado = !$modulo_anterior_ok;
            if (!$modulo_bloqueado) {
                $modulo_anterior_ok = $modulo_completo || $mod['total_clases'] == 0;
            }

            $pct = $mod['total_clases'] > 0 ? round(($mod['completadas'] / $mod['total_clases']) * 100) : 100;
            $color_borde = $modulo_completo ? '#16A34A' : ($modulo_bloqueado ? '#94A3B8' : '#2563EB');
            $color_texto = $modulo_bloqueado ? '#94A3B8' : ($modulo_completo ? '#16A34A' : '#1E293B');
            $icono_mod   = $modulo_bloqueado ? 'lock' : ($modulo_completo ? 'check-circle' : 'book-open');

            $tiene_eval   = !empty($mod['eval_id']);
            $eval_bloq    = $modulo_bloqueado || !$modulo_completo;

            // Datos de intentos
            $key_int   = $mod['eval_id'] . '_' . $curso['inscripcion'];
            $int_mod   = $intentos[$key_int] ?? [];
            $aprobado  = false;
            $mejor     = null;
            $usados    = count($int_mod);
            foreach ($int_mod as $int) {
                if ($int['estado_aprobado'] == 1) $aprobado = true;
                if ($mejor === null || $int['puntaje_obtenido'] > $mejor) $mejor = $int['puntaje_obtenido'];
            }
            $sin_intentos = $mod['eval_intentos'] > 0 && $usados >= $mod['eval_intentos'] && !$aprobado;
        ?>
        <div class="list-group-item border-start border-4 px-4 py-3"
             style="border-left-color:<?= $color_borde ?> !important;background:<?= $modulo_bloqueado ? '#F8FAFC' : '#fff' ?>;">

            <div class="d-flex align-items-center gap-3">
                <i class="fas fa-<?= $icono_mod ?> text-<?= $modulo_bloqueado ? 'secondary' : ($modulo_completo ? 'success' : 'primary') ?>" style="width:20px;"></i>
                <div class="flex-grow-1">
                    <h3 class="fw-bold small text-uppercase m-0" style="font-size:.8rem;color:<?= $color_texto ?>;">
                        Módulo <?= $mod['orden'] ?>: <?= sanitizar_html($mod['titulo']) ?>
                    </h3>
                    <small class="text-muted">
                        <?= $mod['completadas'] ?>/<?= $mod['total_clases'] ?> clases
                        <?php if ($modulo_bloqueado): ?>
                            — <span class="text-warning">Completa el módulo anterior</span>
                        <?php elseif ($modulo_completo): ?>
                            — <span class="text-success">Completado</span>
                        <?php else: ?>
                            — <span class="text-primary"><?= $pct ?>%</span>
                        <?php endif; ?>
                    </small>
                    <?php if (!$modulo_bloqueado && !$modulo_completo && $mod['total_clases'] > 0): ?>
                    <div class="progress mt-1" style="height:4px;max-width:200px;">
                        <div class="progress-bar" style="width:<?= $pct ?>%;background:#2563EB;"></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Evaluación del módulo -->
            <div class="mt-2 ms-4 ps-3 border-start border-light">
                <?php if (!$tiene_eval): ?>
                    <small class="text-muted"><i class="fas fa-info-circle me-1"></i>Este módulo no tiene evaluación.</small>
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2 flex-grow-1">
                            <?php if ($eval_bloq): ?>
                                <i class="fas fa-lock text-secondary"></i>
                            <?php elseif ($aprobado): ?>
                                <i class="fas fa-check-circle text-success"></i>
                            <?php elseif ($sin_intentos): ?>
                                <i class="fas fa-times-circle text-danger"></i>
                            <?php else: ?>
                                <i class="fas fa-file-signature text-primary"></i>
                            <?php endif; ?>
                            <div>
                                <span class="fw-semibold small"><?= sanitizar_html($mod['eval_titulo']) ?></span>
                                <?php if ($mod['eval_desc']): ?>
                                <br><small class="text-muted"><?= sanitizar_html($mod['eval_desc']) ?></small>
                                <?php endif; ?>
                                <small class="text-muted d-block">
                                    <?php if ($eval_bloq): ?>
                                        Bloqueada — completa el módulo primero
                                    <?php elseif ($aprobado): ?>
                                        Aprobada — mejor nota: <strong class="text-success"><?= number_format($mejor, 1) ?>/<?= number_format($mod['eval_max'], 0) ?></strong>
                                    <?php elseif ($sin_intentos): ?>
                                        Agotada (<?= $usados ?>/<?= $mod['eval_intentos'] ?> intentos)
                                    <?php else: ?>
                                        Mín: <?= number_format($mod['eval_min'], 0) ?>/<?= number_format($mod['eval_max'], 0) ?> pts
                                        &middot; <?= (int)$mod['eval_intentos'] ?> intento<?= (int)$mod['eval_intentos'] !== 1 ? 's' : '' ?>
                                        <?php if ($mod['eval_tiempo']): ?>
                                        &middot; <?= (int)$mod['eval_tiempo'] ?> min
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                        <div>
                            <?php if ($tiene_eval && !$eval_bloq && !$aprobado && !$sin_intentos): ?>
                                <a href="<?= BASE_URL ?>student/presentar-evaluacion.php?id=<?= $mod['eval_id'] ?>"
                                   class="btn btn-primary btn-sm rounded-pill px-4 shadow-sm">
                                    <i class="fas fa-edit me-1"></i>Presentar
                                </a>
                            <?php elseif ($tiene_eval && $aprobado): ?>
                                <span class="badge bg-success rounded-pill px-3 py-2">Aprobada</span>
                            <?php elseif ($tiene_eval && $sin_intentos): ?>
                                <span class="badge bg-danger rounded-pill px-3 py-2">Agotada</span>
                            <?php elseif ($tiene_eval): ?>
                                <span class="badge bg-secondary rounded-pill px-3 py-2">Bloqueada</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    </div><!-- /collapse -->
</div>
<?php endforeach; endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
