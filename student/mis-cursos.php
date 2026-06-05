<?php
// /cursoonline/student/mis-cursos.php
// ============================================================
// Catálogo de Cursos Comprados por el Estudiante
// ============================================================

$page_title = 'Mis Cursos';
require_once __DIR__ . '/includes/header.php';

// Obtener todas las inscripciones del estudiante con detalles del curso
$stmt = $pdo->prepare("
    SELECT i.id_inscripcion_pk, i.porcentaje_progreso, i.estado_inscripcion, i.fecha_inscripcion,
           c.titulo_curso, c.resumen_corto, c.imagen_portada, c.total_horas, cat.nombre_categoria, cat.color_categoria
    FROM inscripciones i
    JOIN cursos c ON i.id_curso_fk = c.id_curso_pk
    JOIN categorias_curso cat ON c.id_categoria_fk = cat.id_categoria_pk
    WHERE i.id_usuario_fk = :id_user 
      AND i.estado_activo = 1
    ORDER BY i.fecha_inscripcion DESC
");
$stmt->execute([':id_user' => $id_usuario]);
$inscripciones = $stmt->fetchAll();
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h3 fw-bold text-primary mb-1">Mis Cursos Activos</h1>
            <p class="text-muted m-0">Aquí tienes la lista completa de tus cursos y tu nivel de avance actual.</p>
        </div>
        <a href="explorar-cursos.php" class="btn btn-primary rounded-pill shadow-sm" id="btnComprarCursos">
            <i class="fas fa-compass me-1"></i> Explorar Cursos
        </a>
    </div>
</div>

<div class="row g-4">
    <?php if (empty($inscripciones)): ?>
        <div class="col-12 text-center py-5">
            <div class="card p-5 border border-dashed rounded-4">
                <i class="fas fa-graduation-cap text-muted fs-1 mb-3"></i>
                <h2 class="h4 fw-bold">Aún no tienes cursos inscritos</h2>
                <p class="text-muted mb-4">Descubre nuestro amplio catálogo de cursos de ingeniería e inteligencia artificial.</p>
                <a href="explorar-cursos.php" class="btn btn-primary btn-lg rounded-pill px-4" id="btnExplorarCursos">
                    <i class="fas fa-compass me-2"></i>Explorar Cursos
                </a>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($inscripciones as $item): ?>
            <div class="col-lg-4 col-md-6">
                <article class="card-custom h-100 d-flex flex-column">
                    <div style="position: relative; padding-top: 56.25%; overflow: hidden;">
                        <span class="badge position-absolute" style="top:1rem; left:1rem; background-color: <?= $item['color_categoria'] ?: '#2563EB' ?>; z-index:1;">
                            <?= sanitizar_html($item['nombre_categoria']) ?>
                        </span>
                        <img src="<?= $item['imagen_portada'] ?: 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80' ?>" 
                             alt="Portada" 
                             style="position:absolute; top:0; left:0; width:100%; height:100%; object-fit:cover;">
                    </div>
                    <div class="card-body-custom flex-grow-1 d-flex flex-direction-column justify-content-between" style="display:flex; flex-direction:column; justify-content:space-between;">
                        <div>
                            <h2 class="h5 fw-bold text-primary mb-2"><?= sanitizar_html($item['titulo_curso']) ?></h2>
                            <p class="text-muted small mb-4"><?= sanitizar_html($item['resumen_corto']) ?></p>
                        </div>
                        
                        <div class="mt-auto">
                            <!-- Barra de progreso -->
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small text-muted"><i class="far fa-clock me-1"></i> <?= $item['total_horas'] ?>h totales</span>
                                <span class="small fw-bold text-primary"><?= number_format($item['porcentaje_progreso'], 0) ?>% completado</span>
                            </div>
                            
                            <div class="progress-bar-custom mb-4">
                                <div class="progress-bar-fill" style="width: <?= (float)$item['porcentaje_progreso'] ?>%;"></div>
                            </div>
                            
                            <div class="d-grid">
                                <a href="ver-clase.php?inscripcion=<?= $item['id_inscripcion_pk'] ?>" class="btn btn-primary rounded-pill shadow-sm" id="btn-study-<?= $item['id_inscripcion_pk'] ?>">
                                    <i class="fas fa-play me-2"></i> Continuar Aprendiendo
                                </a>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
