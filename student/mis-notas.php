<?php
// /cursoonline/student/mis-notas.php
// ============================================================
// Calificaciones de Talleres, Proyectos y Actividades del Estudiante
// ============================================================

$page_title = 'Mis Calificaciones';
require_once __DIR__ . '/includes/header.php';

// Obtener todas las calificaciones del estudiante agrupadas por curso
$stmt_grades = $pdo->prepare("
    SELECT c.nota_obtenida, c.observaciones_profesor, c.fecha_calificacion,
           ac.nombre_actividad, ac.puntaje_maximo, ac.porcentaje_nota_final, ac.tipo_actividad,
           cur.titulo_curso, cur.id_curso_pk,
           u.primer_nombre as prof_nombre, u.primer_apellido as prof_apellido
    FROM calificaciones c
    JOIN actividades_calificacion ac ON c.id_actividad_fk = ac.id_actividad_pk
    JOIN inscripciones i ON c.id_inscripcion_fk = i.id_inscripcion_pk
    JOIN cursos cur ON i.id_curso_fk = cur.id_curso_pk
    JOIN usuarios u ON c.id_profesor_fk = u.id_usuario_pk
    WHERE i.id_usuario_fk = :id_user 
      AND c.estado_activo = 1 
      AND i.estado_activo = 1
    ORDER BY cur.titulo_curso ASC, c.fecha_calificacion DESC
");
$stmt_grades->execute([':id_user' => $id_usuario]);
$calificaciones = $stmt_grades->fetchAll();

// Agrupar calificaciones por curso
$calificaciones_por_curso = [];
foreach ($calificaciones as $cal) {
    $calificaciones_por_curso[$cal['id_curso_pk']]['titulo'] = $cal['titulo_curso'];
    $calificaciones_por_curso[$cal['id_curso_pk']]['notas'][] = $cal;
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 fw-bold text-primary mb-1">Mis Calificaciones</h1>
        <p class="text-muted m-0">Revisa las notas y la retroalimentación cargada por tus docentes para cada actividad práctica.</p>
    </div>
</div>

<div class="row g-4">
    <?php if (empty($calificaciones_por_curso)): ?>
        <div class="col-12 text-center py-5">
            <div class="card p-5 border border-dashed rounded-4">
                <i class="fas fa-graduation-cap text-muted fs-1 mb-3"></i>
                <h2 class="h4 fw-bold">Aún no tienes notas cargadas</h2>
                <p class="text-muted m-0">Tus profesores subirán aquí tus calificaciones una vez que envíes y evalúen tus actividades prácticas.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($calificaciones_por_curso as $curso_id => $curso): ?>
            <div class="col-12">
                <div class="card-custom">
                    <div class="card-header-custom bg-light"><i class="fas fa-book me-2"></i> <?= sanitizar_html($curso['titulo']) ?></div>
                    <div class="card-body-custom p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle m-0 text-start">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Actividad / Taller</th>
                                        <th>Tipo</th>
                                        <th>Peso Nota</th>
                                        <th>Nota Obtenida</th>
                                        <th>Docente</th>
                                        <th>Observaciones del Docente</th>
                                        <th class="pe-4">Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($curso['notas'] as $nota): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-primary"><?= sanitizar_html($nota['nombre_actividad']) ?></td>
                                            <td>
                                                <span class="badge bg-secondary-subtle text-secondary text-uppercase small">
                                                    <?= sanitizar_html($nota['tipo_actividad']) ?>
                                                </span>
                                            </td>
                                            <td><?= number_format($nota['porcentaje_nota_final'], 1) ?>%</td>
                                            <td class="fw-bold">
                                                <span class="<?= $nota['nota_obtenida'] >= 60 ? 'text-success' : 'text-danger' ?>">
                                                    <?= number_format($nota['nota_obtenida'], 1) ?>
                                                </span>
                                                <span class="text-muted">/ <?= number_format($nota['puntaje_maximo'], 0) ?></span>
                                            </td>
                                            <td><?= sanitizar_html($nota['prof_nombre'] . ' ' . $nota['prof_apellido']) ?></td>
                                            <td>
                                                <span class="small text-muted italic">
                                                    <?= sanitizar_html($nota['observaciones_profesor'] ?: 'Sin comentarios registrados.') ?>
                                                </span>
                                            </td>
                                            <td class="pe-4 small text-muted"><?= date('d/m/Y', strtotime($nota['fecha_calificacion'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
