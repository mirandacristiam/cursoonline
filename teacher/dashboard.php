<?php
// /cursoonline/teacher/dashboard.php
// ============================================================
// Dashboard Principal del Profesor — EduTech Academy
// ============================================================

$page_title  = 'Dashboard';
$page_script = 'assets/js/teacher.js';

require_once 'includes/header.php';

// ── Resumen estadístico del profesor ──────────────────────────
// Total grupos activos
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT g.id_grupo_pk) AS total_grupos
    FROM grupos g
    INNER JOIN grupo_docente gd ON gd.id_grupo_fk = g.id_grupo_pk
    WHERE gd.id_usuario_fk = :id AND g.estado_activo = 1
");
$stmt->execute([':id' => $id_usuario]);
$total_grupos = (int)($stmt->fetchColumn() ?? 0);

// Total estudiantes en todos sus grupos
$stmt2 = $pdo->prepare("
    SELECT COUNT(DISTINCT ei.id_usuario_fk) AS total_estudiantes
    FROM enrollments_inscripciones ei
    INNER JOIN grupos g ON g.id_grupo_pk = ei.id_grupo_fk
    INNER JOIN grupo_docente gd ON gd.id_grupo_fk = g.id_grupo_pk
    WHERE gd.id_usuario_fk = :id AND ei.estado_inscripcion = 'activo'
");
$stmt2->execute([':id' => $id_usuario]);
$total_estudiantes = (int)($stmt2->fetchColumn() ?? 0);

// Actividades pendientes de calificar
$stmt3 = $pdo->prepare("
    SELECT COUNT(*) AS pendientes
    FROM intentos_evaluacion ie
    INNER JOIN evaluaciones ev ON ev.id_evaluacion_pk = ie.id_evaluacion_fk
    INNER JOIN cursos c ON c.id_curso_pk = ev.id_curso_fk
    INNER JOIN grupo_docente gd ON gd.id_usuario_fk = :id
    WHERE ie.estado_revision = 'pendiente'
");
$stmt3->execute([':id' => $id_usuario]);
$pendientes_calificar = (int)($stmt3->fetchColumn() ?? 0);

// Promedio general de notas de sus estudiantes
$stmt4 = $pdo->prepare("
    SELECT ROUND(AVG(ie.nota_obtenida), 1) AS promedio
    FROM intentos_evaluacion ie
    INNER JOIN evaluaciones ev ON ev.id_evaluacion_pk = ie.id_evaluacion_fk
    INNER JOIN cursos c ON c.id_curso_pk = ev.id_curso_fk
    INNER JOIN grupo_docente gd ON gd.id_usuario_fk = :id
    WHERE ie.nota_obtenida IS NOT NULL
");
$stmt4->execute([':id' => $id_usuario]);
$promedio_notas = $stmt4->fetchColumn() ?? 0;

// Últimos 5 estudiantes inscritos en sus grupos
$stmt5 = $pdo->prepare("
    SELECT u.primer_nombre, u.primer_apellido, u.correo_electronico,
           ei.fecha_inscripcion, g.nombre_grupo, c.nombre_curso
    FROM enrollments_inscripciones ei
    INNER JOIN usuarios u ON u.id_usuario_pk = ei.id_usuario_fk
    INNER JOIN grupos g ON g.id_grupo_pk = ei.id_grupo_fk
    INNER JOIN cursos c ON c.id_curso_pk = g.id_curso_fk
    INNER JOIN grupo_docente gd ON gd.id_grupo_fk = g.id_grupo_pk
    WHERE gd.id_usuario_fk = :id
    ORDER BY ei.fecha_inscripcion DESC
    LIMIT 5
");
$stmt5->execute([':id' => $id_usuario]);
$ultimos_inscritos = $stmt5->fetchAll();

// Mis cursos asignados
$stmt6 = $pdo->prepare("
    SELECT DISTINCT c.id_curso_pk, c.nombre_curso, c.descripcion_corta,
           COUNT(DISTINCT ei.id_usuario_fk) AS num_estudiantes,
           g.nombre_grupo
    FROM grupo_docente gd
    INNER JOIN grupos g ON g.id_grupo_pk = gd.id_grupo_fk
    INNER JOIN cursos c ON c.id_curso_pk = g.id_curso_fk
    LEFT JOIN enrollments_inscripciones ei ON ei.id_grupo_fk = g.id_grupo_pk AND ei.estado_inscripcion = 'activo'
    WHERE gd.id_usuario_fk = :id AND g.estado_activo = 1
    GROUP BY c.id_curso_pk, c.nombre_curso, c.descripcion_corta, g.nombre_grupo
    ORDER BY c.nombre_curso
    LIMIT 6
");
$stmt6->execute([':id' => $id_usuario]);
$mis_cursos = $stmt6->fetchAll();
?>

<!-- ── Page Header ─────────────────────────────────────────── -->
<div class="page-header">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Inicio</a></li>
            <li class="breadcrumb-item active">Dashboard</li>
        </ol>
    </nav>
    <h1><i class="fas fa-chalkboard-teacher me-2 text-success"></i>Mi Panel Docente</h1>
    <p>Bienvenido, <strong><?= sanitizar_html($profesor['primer_nombre']) ?></strong>. Aquí tienes un resumen de tu actividad docente.</p>
</div>

<!-- ── Stat Cards ──────────────────────────────────────────── -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon teal"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <h3 data-target="<?= $total_grupos ?>"><?= $total_grupos ?></h3>
            <p>Grupos Activos</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon blue"><i class="fas fa-user-graduate"></i></div>
        <div class="stat-info">
            <h3 data-target="<?= $total_estudiantes ?>"><?= $total_estudiantes ?></h3>
            <p>Estudiantes a Cargo</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon amber"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
            <h3 data-target="<?= $pendientes_calificar ?>"><?= $pendientes_calificar ?></h3>
            <p>Pendientes de Calificar</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple"><i class="fas fa-star"></i></div>
        <div class="stat-info">
            <h3><?= number_format((float)$promedio_notas, 1) ?></h3>
            <p>Promedio General Grupo</p>
        </div>
    </div>
</div>

<div class="row g-4">

    <!-- ── Últimas inscripciones ─────────────────────────── -->
    <div class="col-lg-7">
        <div class="teacher-card">
            <div class="card-header-custom">
                <h5><i class="fas fa-user-plus"></i> Últimas Inscripciones</h5>
                <a href="mis-grupos.php" class="btn-teacher-outline btn-sm">Ver todos</a>
            </div>
            <div class="card-body-custom p-0">
                <?php if (empty($ultimos_inscritos)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fa-2x mb-3 d-block opacity-50"></i>
                        No hay estudiantes inscritos aún.
                    </div>
                <?php else: ?>
                    <table class="table-teacher w-100">
                        <thead>
                            <tr>
                                <th>Estudiante</th>
                                <th>Curso / Grupo</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimos_inscritos as $ins): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="student-avatar">
                                            <?= strtoupper(substr($ins['primer_nombre'], 0, 1) . substr($ins['primer_apellido'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold small"><?= sanitizar_html($ins['primer_nombre'] . ' ' . $ins['primer_apellido']) ?></div>
                                            <div class="text-muted" style="font-size:0.75rem;"><?= sanitizar_html($ins['correo_electronico']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="small fw-semibold"><?= sanitizar_html($ins['nombre_curso']) ?></div>
                                    <div class="text-muted" style="font-size:0.75rem;"><?= sanitizar_html($ins['nombre_grupo']) ?></div>
                                </td>
                                <td class="text-muted small"><?= date('d/m/Y', strtotime($ins['fecha_inscripcion'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── Mis Cursos Asignados ──────────────────────────── -->
    <div class="col-lg-5">
        <div class="teacher-card h-100">
            <div class="card-header-custom">
                <h5><i class="fas fa-book-open"></i> Mis Cursos Asignados</h5>
            </div>
            <div class="card-body-custom">
                <?php if (empty($mis_cursos)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-chalkboard fa-2x mb-3 d-block opacity-50"></i>
                        No tienes cursos asignados aún.
                    </div>
                <?php else: ?>
                    <?php foreach ($mis_cursos as $curso): ?>
                    <div class="d-flex align-items-start gap-3 mb-3 pb-3 border-bottom">
                        <div class="stat-icon teal flex-shrink-0" style="width:42px;height:42px;border-radius:10px;font-size:1rem;">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold small"><?= sanitizar_html($curso['nombre_curso']) ?></div>
                            <div class="text-muted" style="font-size:0.78rem;"><?= sanitizar_html($curso['nombre_grupo']) ?></div>
                        </div>
                        <span class="badge bg-primary-subtle text-primary rounded-pill small">
                            <?= (int)$curso['num_estudiantes'] ?> est.
                        </span>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <div class="mt-3">
                    <a href="mis-grupos.php" class="btn-teacher-primary w-100 justify-content-center">
                        <i class="fas fa-users"></i> Ver mis grupos
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once 'includes/footer.php'; ?>
