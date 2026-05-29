<?php
// /cursoonline/teacher/mis-grupos.php
// ============================================================
// Vista de Mis Grupos — Panel del Profesor — EduTech Academy
// ============================================================

$page_title = 'Mis Grupos';
require_once 'includes/header.php';

// ── Parámetros de búsqueda y filtro ─────────────────────────
$busqueda    = isset($_GET['q'])       ? limpiar_entrada($_GET['q'])       : '';
$filtro_est  = isset($_GET['estado'])  ? limpiar_entrada($_GET['estado'])  : 'activo';
$id_grupo_sel = isset($_GET['grupo'])  ? (int)$_GET['grupo']               : 0;

// ── Obtener todos los grupos del profesor ────────────────────
$stmt_grupos = $pdo->prepare("
    SELECT g.id_grupo_pk, g.nombre_grupo, g.estado_activo,
           c.nombre_curso, c.id_curso_pk,
           COUNT(DISTINCT ei.id_usuario_fk) AS num_estudiantes,
           g.fecha_inicio, g.fecha_fin
    FROM grupos g
    INNER JOIN grupo_docente gd ON gd.id_grupo_fk = g.id_grupo_pk
    INNER JOIN cursos c ON c.id_curso_pk = g.id_curso_fk
    LEFT JOIN enrollments_inscripciones ei
           ON ei.id_grupo_fk = g.id_grupo_pk AND ei.estado_inscripcion = 'activo'
    WHERE gd.id_usuario_fk = :id
    GROUP BY g.id_grupo_pk, g.nombre_grupo, g.estado_activo,
             c.nombre_curso, c.id_curso_pk, g.fecha_inicio, g.fecha_fin
    ORDER BY g.estado_activo DESC, c.nombre_curso
");
$stmt_grupos->execute([':id' => $id_usuario]);
$grupos = $stmt_grupos->fetchAll();

// ── Si se seleccionó un grupo: cargar sus estudiantes ────────
$estudiantes = [];
$grupo_actual = null;
if ($id_grupo_sel) {
    // Verificar que el grupo pertenece a este profesor
    $stmt_chk = $pdo->prepare("
        SELECT g.id_grupo_pk, g.nombre_grupo, c.nombre_curso
        FROM grupos g
        INNER JOIN grupo_docente gd ON gd.id_grupo_fk = g.id_grupo_pk
        INNER JOIN cursos c ON c.id_curso_pk = g.id_curso_fk
        WHERE g.id_grupo_pk = :grupo AND gd.id_usuario_fk = :prof
    ");
    $stmt_chk->execute([':grupo' => $id_grupo_sel, ':prof' => $id_usuario]);
    $grupo_actual = $stmt_chk->fetch();

    if ($grupo_actual) {
        $sql_est = "
            SELECT u.id_usuario_pk, u.primer_nombre, u.primer_apellido,
                   u.correo_electronico, ei.fecha_inscripcion, ei.estado_inscripcion,
                   ROUND(AVG(ie.nota_obtenida), 1) AS promedio_notas,
                   COUNT(DISTINCT ie.id_intento_fk)   AS total_intentos
            FROM enrollments_inscripciones ei
            INNER JOIN usuarios u ON u.id_usuario_pk = ei.id_usuario_fk
            LEFT JOIN intentos_evaluacion ie ON ie.id_usuario_fk = u.id_usuario_pk
            WHERE ei.id_grupo_fk = :grupo
        ";
        $params_est = [':grupo' => $id_grupo_sel];

        if (!empty($busqueda)) {
            $sql_est .= " AND (u.primer_nombre LIKE :q OR u.primer_apellido LIKE :q OR u.correo_electronico LIKE :q)";
            $params_est[':q'] = "%{$busqueda}%";
        }
        if ($filtro_est !== 'todos') {
            $sql_est .= " AND ei.estado_inscripcion = :est";
            $params_est[':est'] = $filtro_est;
        }
        $sql_est .= " GROUP BY u.id_usuario_pk, u.primer_nombre, u.primer_apellido,
                               u.correo_electronico, ei.fecha_inscripcion, ei.estado_inscripcion
                      ORDER BY u.primer_apellido, u.primer_nombre";

        $stmt_est = $pdo->prepare($sql_est);
        $stmt_est->execute($params_est);
        $estudiantes = $stmt_est->fetchAll();
    }
}
?>

<!-- ── Page Header ─────────────────────────────────────────── -->
<div class="page-header">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Inicio</a></li>
            <li class="breadcrumb-item active">Mis Grupos</li>
        </ol>
    </nav>
    <h1><i class="fas fa-users me-2 text-success"></i>Mis Grupos</h1>
    <p>Gestiona tus grupos, revisa la lista de estudiantes y su progreso.</p>
</div>

<div class="row g-4">

    <!-- ── Lista de Grupos (Panel izquierdo) ───────────────── -->
    <div class="col-lg-4">
        <div class="teacher-card">
            <div class="card-header-custom">
                <h5><i class="fas fa-layer-group"></i> Grupos Asignados</h5>
                <span class="badge bg-success-subtle text-success rounded-pill"><?= count($grupos) ?></span>
            </div>
            <div class="card-body-custom p-0">
                <?php if (empty($grupos)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-folder-open fa-2x mb-3 d-block opacity-50"></i>
                        No tienes grupos asignados aún.
                    </div>
                <?php else: ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($grupos as $g): ?>
                        <li>
                            <a href="mis-grupos.php?grupo=<?= $g['id_grupo_pk'] ?>"
                               class="d-flex align-items-center gap-3 p-3 text-decoration-none border-bottom grupo-item
                                      <?= $id_grupo_sel === (int)$g['id_grupo_pk'] ? 'grupo-item-active' : '' ?>">
                                <div class="stat-icon teal flex-shrink-0"
                                     style="width:40px;height:40px;border-radius:10px;font-size:0.9rem;">
                                    <i class="fas fa-chalkboard"></i>
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="fw-semibold small text-truncate">
                                        <?= sanitizar_html($g['nombre_grupo']) ?>
                                    </div>
                                    <div class="text-muted" style="font-size:0.75rem;">
                                        <?= sanitizar_html($g['nombre_curso']) ?>
                                    </div>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <span class="badge bg-primary-subtle text-primary rounded-pill small">
                                        <?= (int)$g['num_estudiantes'] ?>
                                    </span>
                                    <?php if ((int)$g['estado_activo'] === 1): ?>
                                        <br><span class="badge-teacher activo" style="font-size:0.65rem;margin-top:3px;">Activo</span>
                                    <?php else: ?>
                                        <br><span class="badge-teacher inactivo" style="font-size:0.65rem;margin-top:3px;">Cerrado</span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── Detalle del Grupo Seleccionado ──────────────────── -->
    <div class="col-lg-8">
        <?php if (!$id_grupo_sel || !$grupo_actual): ?>
            <!-- Estado vacío -->
            <div class="teacher-card h-100">
                <div class="card-body-custom d-flex flex-column align-items-center justify-content-center py-5">
                    <i class="fas fa-hand-pointer fa-3x text-muted opacity-50 mb-3"></i>
                    <h5 class="text-muted">Selecciona un grupo</h5>
                    <p class="text-muted small">Haz clic en cualquier grupo de la lista para ver sus estudiantes.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="teacher-card">
                <div class="card-header-custom">
                    <div>
                        <h5><i class="fas fa-user-graduate"></i> <?= sanitizar_html($grupo_actual['nombre_grupo']) ?></h5>
                        <small class="text-muted"><?= sanitizar_html($grupo_actual['nombre_curso']) ?></small>
                    </div>
                    <a href="calificaciones.php?grupo=<?= $id_grupo_sel ?>" class="btn-teacher-primary btn-sm">
                        <i class="fas fa-pen-to-square"></i> Calificar
                    </a>
                </div>

                <!-- Barra de búsqueda y filtro -->
                <div class="card-body-custom border-bottom">
                    <form method="GET" class="d-flex gap-2 flex-wrap" id="formFiltro">
                        <input type="hidden" name="grupo" value="<?= $id_grupo_sel ?>">
                        <input type="text" class="form-control form-control-sm" name="q"
                               placeholder="Buscar estudiante..." value="<?= sanitizar_html($busqueda) ?>"
                               id="tableSearch" style="max-width:240px;">
                        <select name="estado" class="form-select form-select-sm" style="max-width:160px;"
                                onchange="this.form.submit()">
                            <option value="todos"  <?= $filtro_est === 'todos'  ? 'selected' : '' ?>>Todos</option>
                            <option value="activo" <?= $filtro_est === 'activo' ? 'selected' : '' ?>>Activos</option>
                            <option value="inactivo"<?= $filtro_est==='inactivo'? 'selected' : '' ?>>Inactivos</option>
                        </select>
                        <button type="submit" class="btn-teacher-outline btn-sm">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>

                <!-- Tabla de Estudiantes -->
                <div class="card-body-custom p-0">
                    <?php if (empty($estudiantes)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-user-slash fa-2x mb-3 d-block opacity-50"></i>
                            <?= !empty($busqueda) ? 'No se encontraron estudiantes con esa búsqueda.' : 'No hay estudiantes inscritos en este grupo.' ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table-teacher w-100" id="tablaEstudiantes">
                                <thead>
                                    <tr>
                                        <th>Estudiante</th>
                                        <th>Estado</th>
                                        <th>Promedio</th>
                                        <th>Intentos</th>
                                        <th>Inscripción</th>
                                        <th class="text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($estudiantes as $est): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="student-avatar">
                                                    <?= strtoupper(substr($est['primer_nombre'], 0, 1) . substr($est['primer_apellido'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold small">
                                                        <?= sanitizar_html($est['primer_nombre'] . ' ' . $est['primer_apellido']) ?>
                                                    </div>
                                                    <div class="text-muted" style="font-size:0.75rem;">
                                                        <?= sanitizar_html($est['correo_electronico']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php $est_estado = $est['estado_inscripcion']; ?>
                                            <span class="badge-teacher <?= $est_estado === 'activo' ? 'activo' : 'inactivo' ?>">
                                                <?= ucfirst(sanitizar_html($est_estado)) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php $prom = (float)($est['promedio_notas'] ?? 0); ?>
                                            <span class="fw-bold <?= $prom >= 60 ? 'text-success' : ($prom > 0 ? 'text-danger' : 'text-muted') ?>">
                                                <?= $prom > 0 ? number_format($prom, 1) : '—' ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary-subtle text-secondary rounded-pill">
                                                <?= (int)$est['total_intentos'] ?>
                                            </span>
                                        </td>
                                        <td class="text-muted small">
                                            <?= date('d/m/Y', strtotime($est['fecha_inscripcion'])) ?>
                                        </td>
                                        <td class="text-center">
                                            <a href="calificaciones.php?grupo=<?= $id_grupo_sel ?>&estudiante=<?= $est['id_usuario_pk'] ?>"
                                               class="btn-teacher-outline btn-sm"
                                               data-bs-toggle="tooltip" title="Ver / Calificar">
                                                <i class="fas fa-pen-to-square"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-body-custom border-top d-flex justify-content-between text-muted small">
                            <span><?= count($estudiantes) ?> estudiante(s) mostrado(s)</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<style>
/* Estilos específicos de la vista — se pueden mover a teacher.css */
.grupo-item { transition: background 0.15s; color: var(--text-primary); }
.grupo-item:hover { background: var(--light-bg); }
.grupo-item-active { background: rgba(20,184,166,0.08) !important; border-left: 3px solid var(--teacher-secondary) !important; }
</style>

<?php require_once 'includes/footer.php'; ?>
