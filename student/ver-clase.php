<?php
// /cursoonline/student/ver-clase.php
// ============================================================
// Aula Virtual: Reproductor de Clases y Materiales
// ============================================================

$page_title = 'Ver Clase';
require_once __DIR__ . '/includes/header.php';

$id_inscripcion = isset($_GET['inscripcion']) ? (int)$_GET['inscripcion'] : 0;
$id_clase_seleccionada = isset($_GET['clase']) ? (int)$_GET['clase'] : 0;

if ($id_inscripcion <= 0) {
    echo "<div class='alert alert-danger'>Inscripción no válida.</div>";
    require_once __DIR__ . '/includes/footer.php';
    exit();
}

// 1. Obtener la inscripción y verificar propiedad
$stmt_ins = $pdo->prepare("
    SELECT i.*, c.titulo_curso, c.id_curso_pk 
    FROM inscripciones i
    JOIN cursos c ON i.id_curso_fk = c.id_curso_pk
    WHERE i.id_inscripcion_pk = :id_ins 
      AND i.id_usuario_fk = :id_user 
      AND i.estado_activo = 1
");
$stmt_ins->execute([
    ':id_ins'  => $id_inscripcion,
    ':id_user' => $id_usuario
]);
$inscripcion = $stmt_ins->fetch();

if (!$inscripcion) {
    echo "<div class='alert alert-danger'>Acceso no autorizado a este curso.</div>";
    require_once __DIR__ . '/includes/footer.php';
    exit();
}

// 2. Obtener todos los módulos del curso
$stmt_mod = $pdo->prepare("
    SELECT * 
    FROM modulos_curso 
    WHERE id_curso_fk = :id_curso 
      AND estado_activo = 1 
    ORDER BY orden_modulo ASC
");
$stmt_mod->execute([':id_curso' => $inscripcion['id_curso_pk']]);
$modulos = $stmt_mod->fetchAll();

// 3. Obtener clases por cada módulo y organizar
$clases_por_modulo = [];
$clase_activa = null;
$primera_clase = null;

$stmt_clases = $pdo->prepare("
    SELECT cc.*, 
           COALESCE(pc.porcentaje_completado, 0) as porcentaje_completado,
           COALESCE(pc.estado_completada, 0) as estado_completada
    FROM clases_curso cc
    LEFT JOIN progreso_clases pc ON cc.id_clase_pk = pc.id_clase_fk AND pc.id_inscripcion_fk = :id_ins AND pc.estado_activo = 1
    WHERE cc.id_modulo_fk = :id_modulo 
      AND cc.estado_activo = 1
    ORDER BY cc.orden_clase ASC
");

foreach ($modulos as $mod) {
    $stmt_clases->execute([
        ':id_modulo' => $mod['id_modulo_pk'],
        ':id_ins'    => $id_inscripcion
    ]);
    $clases = $stmt_clases->fetchAll();
    $clases_por_modulo[$mod['id_modulo_pk']] = $clases;
    
    // Identificar clase activa
    foreach ($clases as $c) {
        if ($primera_clase === null) {
            $primera_clase = $c;
        }
        if ($id_clase_seleccionada > 0 && (int)$c['id_clase_pk'] === $id_clase_seleccionada) {
            $clase_activa = $c;
        }
    }
}

// Si no se especificó clase, activar la primera por defecto
if ($clase_activa === null) {
    $clase_activa = $primera_clase;
}

// 4. Obtener materiales de la clase activa
$materiales = [];
if ($clase_activa !== null) {
    $stmt_mat = $pdo->prepare("
        SELECT * 
        FROM materiales_curso 
        WHERE id_clase_fk = :id_clase 
          AND estado_activo = 1
    ");
    $stmt_mat->execute([':id_clase' => $clase_activa['id_clase_pk']]);
    $materiales = $stmt_mat->fetchAll();
}
?>

<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Inicio</a></li>
                <li class="breadcrumb-item"><a href="mis-cursos.php" class="text-decoration-none">Mis Cursos</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= sanitizar_html($inscripcion['titulo_curso']) ?></li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-primary mb-1"><?= sanitizar_html($inscripcion['titulo_curso']) ?></h1>
    </div>
</div>

<div class="row g-4">
    <!-- Reproductor y Materiales -->
    <div class="col-lg-8">
        <?php if ($clase_activa === null): ?>
            <div class="alert alert-info">Este curso aún no tiene clases cargadas.</div>
        <?php else: ?>
            <!-- REPRODUCTOR -->
            <div class="card-custom mb-4">
                <div class="video-container">
                    <?php if (!empty($clase_activa['url_video'])): ?>
                        <!-- Si es youtube -->
                        <?php if (stripos($clase_activa['url_video'], 'youtube.com') !== false || stripos($clase_activa['url_video'], 'youtu.be') !== false): ?>
                            <?php 
                            // Obtener id de video de youtube
                            $video_id = '';
                            if (preg_match('/(??:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i', $clase_activa['url_video'], $match)) {
                                $video_id = $match[1];
                            }
                            ?>
                            <iframe src="https://www.youtube.com/embed/<?= $video_id ?>" title="Reproductor de clase" frameborder="0" allowfullscreen></iframe>
                        <?php else: ?>
                            <video src="<?= sanitizar_html($clase_activa['url_video']) ?>" controls></video>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Placeholder elegante de video -->
                        <div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column align-items-center justify-content-center text-white" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
                            <i class="fas fa-play-circle fs-1 text-primary mb-3"></i>
                            <p class="m-0 text-white-50">Video representativo de clase</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="card-body-custom">
                    <h2 class="h4 fw-bold text-primary mb-2"><?= sanitizar_html($clase_activa['titulo_clase']) ?></h2>
                    <p class="text-muted"><?= sanitizar_html($clase_activa['descripcion_clase'] ?: 'Sin descripción adicional para esta clase.') ?></p>
                    
                    <hr class="my-4">
                    
                    <!-- PANEL DE SIMULACIÓN DE AVANCE (INDISPENSABLE PARA PRUEBAS LOCALES) -->
                    <div class="p-3 border border-dashed border-primary rounded-3 bg-light">
                        <h4 class="h6 fw-bold text-primary mb-2"><i class="fas fa-tools"></i> Simulador de Progreso Académico (Pruebas Locales)</h4>
                        <p class="small text-muted">Selecciona el porcentaje de la clase que has reproducido. Si marcas 80% o más, el sistema activará esta clase como completada y recalculará dinámicamente tu progreso del curso.</p>
                        <div class="row g-3 align-items-center">
                            <div class="col-sm-4">
                                <select class="form-select form-select-sm" id="selectPorcentaje">
                                    <option value="20" <?= (int)$clase_activa['porcentaje_completado'] === 20 ? 'selected' : '' ?>>20% - Introducción vista</option>
                                    <option value="50" <?= (int)$clase_activa['porcentaje_completado'] === 50 ? 'selected' : '' ?>>50% - Mitad de clase vista</option>
                                    <option value="80" <?= (int)$clase_activa['porcentaje_completado'] === 80 ? 'selected' : '' ?>>80% - ¡Marcar como Completada!</option>
                                    <option value="100" <?= (int)$clase_activa['porcentaje_completado'] === 100 ? 'selected' : '' ?>>100% - Clase vista completa</option>
                                </select>
                            </div>
                            <div class="col-sm-5">
                                <button type="button" 
                                        class="btn btn-primary btn-sm rounded-pill w-100 shadow-sm" 
                                        id="btnSimularAvance" 
                                        data-inscripcion-id="<?= $id_inscripcion ?>" 
                                        data-clase-id="<?= $clase_activa['id_clase_pk'] ?>">
                                    Simular Progreso de Vista
                                </button>
                            </div>
                            <div class="col-sm-3 text-end">
                                <span class="badge <?= $clase_activa['estado_completada'] ? 'badge-active' : 'badge-pending' ?>">
                                    <?= $clase_activa['estado_completada'] ? '<i class="fas fa-check"></i> Completada' : 'Pendiente' ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- MATERIALES DE APOYO -->
            <div class="card-custom">
                <div class="card-header-custom"><i class="fas fa-folder-open me-2"></i>Materiales de Apoyo y Recursos</div>
                <div class="card-body-custom">
                    <?php if (empty($materiales)): ?>
                        <p class="text-muted m-0">Esta clase no cuenta con recursos descargables adicionales.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($materiales as $mat): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div>
                                        <h4 class="h6 fw-bold m-0"><i class="far fa-file-pdf text-danger me-2"></i> <?= sanitizar_html($mat['nombre_material']) ?></h4>
                                        <span class="xsmall text-muted"><?= sanitizar_html($mat['descripcion_material']) ?></span>
                                    </div>
                                    <a href="<?= BASE_URL . $mat['url_archivo'] ?>" target="_blank" class="btn btn-light btn-sm shadow-sm" download><i class="fas fa-download text-primary"></i> Descargar</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Temario y Módulos -->
    <div class="col-lg-4">
        <div class="card-custom">
            <div class="card-header-custom"><i class="fas fa-list-ol me-2"></i>Temario del Curso</div>
            <div class="card-body-custom p-0">
                <div class="accordion accordion-flush" id="accordionTemario">
                    <?php foreach ($modulos as $index => $mod): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading-<?= $mod['id_modulo_pk'] ?>">
                                <button class="accordion-button <?= $index === 0 ? '' : 'collapsed' ?> fw-bold small py-3" 
                                        type="button" 
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#collapse-<?= $mod['id_modulo_pk'] ?>" 
                                        aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" 
                                        aria-controls="collapse-<?= $mod['id_modulo_pk'] ?>">
                                    Módulo <?= $mod['orden_modulo'] ?>: <?= sanitizar_html($mod['titulo_modulo']) ?>
                                </button>
                            </h2>
                            <div id="collapse-<?= $mod['id_modulo_pk'] ?>" 
                                 class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" 
                                 aria-labelledby="heading-<?= $mod['id_modulo_pk'] ?>" 
                                 data-bs-parent="#accordionTemario">
                                <div class="accordion-body p-0">
                                    <div class="class-list-group">
                                        <?php if (empty($clases_por_modulo[$mod['id_modulo_pk']])): ?>
                                            <p class="p-3 text-muted small m-0">Sin clases en este módulo.</p>
                                        <?php else: ?>
                                            <?php foreach ($clases_por_modulo[$mod['id_modulo_pk']] as $c): ?>
                                                <a href="ver-clase.php?inscripcion=<?= $id_inscripcion ?>&clase=<?= $c['id_clase_pk'] ?>" 
                                                   class="class-item <?= (int)$c['id_clase_pk'] === (int)$clase_activa['id_clase_pk'] ? 'active' : '' ?>">
                                                    <div>
                                                        <span class="small fw-semibold d-block"><?= sanitizar_html($c['titulo_clase']) ?></span>
                                                        <span class="xsmall text-muted"><i class="far fa-play-circle me-1"></i> <?= $c['duracion_minutos'] ?> min</span>
                                                    </div>
                                                    <span>
                                                        <?php if ($c['estado_completada']): ?>
                                                            <i class="fas fa-check-circle text-success"></i>
                                                        <?php else: ?>
                                                            <i class="far fa-circle text-muted"></i>
                                                        <?php endif; ?>
                                                    </span>
                                                </a>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
