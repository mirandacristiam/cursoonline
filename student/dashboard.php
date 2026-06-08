<?php
// /cursoonline/student/dashboard.php
// ============================================================
// Dashboard Central del Estudiante — EduTech Academy
// ============================================================

$page_title = 'Mi Dashboard';
require_once __DIR__ . '/includes/header.php';

// --- 1. CONSULTA DE ESTADÍSTICAS ---

// Total de cursos inscritos
$stmt_enrolled = $pdo->prepare("
    SELECT COUNT(*) 
    FROM inscripciones 
    WHERE id_usuario_fk = :id_user 
      AND estado_inscripcion IN ('activa','completada')
      AND estado_activo = 1
");
$stmt_enrolled->execute([':id_user' => $id_usuario]);
$total_cursos = (int)$stmt_enrolled->fetchColumn();

// Cursos completados (100% de progreso)
$stmt_completed = $pdo->prepare("
    SELECT COUNT(*) 
    FROM inscripciones 
    WHERE id_usuario_fk = :id_user 
      AND porcentaje_progreso >= 100 
      AND estado_activo = 1
");
$stmt_completed->execute([':id_user' => $id_usuario]);
$total_completados = (int)$stmt_completed->fetchColumn();

// Promedio de calificaciones
$stmt_avg = $pdo->prepare("
    SELECT AVG(c.nota_obtenida) 
    FROM calificaciones c
    JOIN inscripciones i ON c.id_inscripcion_fk = i.id_inscripcion_pk
    WHERE i.id_usuario_fk = :id_user 
      AND c.estado_activo = 1 
      AND i.estado_activo = 1
");
$stmt_avg->execute([':id_user' => $id_usuario]);
$promedio_notas = $stmt_avg->fetchColumn();
$promedio_notas = $promedio_notas !== null ? (float)$promedio_notas : 0.0;

// Total invertido en cursos APROBADOS (COP)
$stmt_spent = $pdo->prepare("
    SELECT COALESCE(SUM(monto_pagado), 0)
    FROM inscripciones i
    WHERE i.id_usuario_fk = :id_user
      AND i.estado_activo = 1
      AND i.estado_inscripcion IN ('activa','completada')
");
$stmt_spent->execute([':id_user' => $id_usuario]);
$total_invertido = (float)$stmt_spent->fetchColumn();

// Total pendiente de aprobación (solicitudes en proceso)
$stmt_pending_amount = $pdo->prepare("
    SELECT COALESCE(SUM(t.monto_total), 0)
    FROM transacciones_pago t
    WHERE t.id_usuario_fk = :id_user
      AND t.estado_transaccion = 'pendiente'
      AND t.estado_activo = 1
");
$stmt_pending_amount->execute([':id_user' => $id_usuario]);
$total_pendiente = (float)$stmt_pending_amount->fetchColumn();

// Conteo de cursos pendientes de aprobación
$stmt_pending_count = $pdo->prepare("
    SELECT COUNT(*)
    FROM inscripciones
    WHERE id_usuario_fk = :id_user
      AND estado_inscripcion = 'suspendida'
      AND estado_activo = 1
");
$stmt_pending_count->execute([':id_user' => $id_usuario]);
$total_pendientes_count = (int)$stmt_pending_count->fetchColumn();

// --- 2. CONSULTA DE CURSOS RECIENTES ---
$stmt_recent = $pdo->prepare("
    SELECT i.id_inscripcion_pk, i.porcentaje_progreso, i.estado_inscripcion,
           c.titulo_curso, c.imagen_portada, c.total_horas, cat.nombre_categoria
    FROM inscripciones i
    JOIN cursos c ON i.id_curso_fk = c.id_curso_pk
    JOIN categorias_curso cat ON c.id_categoria_fk = cat.id_categoria_pk
    WHERE i.id_usuario_fk = :id_user 
      AND i.estado_inscripcion IN ('activa','completada')
      AND i.estado_activo = 1
    ORDER BY i.fecha_inscripcion DESC
    LIMIT 3
");
$stmt_recent->execute([':id_user' => $id_usuario]);
$cursos_recientes = $stmt_recent->fetchAll();
?>

<!-- BANNER DE BIENVENIDA -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 bg-primary text-white p-4 rounded-4 shadow-sm" style="background: linear-gradient(135deg, #1A3C6E 0%, #2563EB 100%) !important;">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="h3 fw-bold mb-2">¡Hola, <?= sanitizar_html($estudiante['primer_nombre']) ?>! 👋</h1>
                    <p class="m-0 text-white-50">Bienvenido a tu panel estudiantil en EduTech Academy. Aquí podrás llevar el control de tus cursos, ver tus clases, descargar material y realizar tus evaluaciones.</p>
                </div>
                <div class="col-md-4 text-end d-none d-md-block">
                    <i class="fas fa-user-graduate fs-1 opacity-25"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- TARJETAS DE ESTADÍSTICAS -->
<div class="row g-4 mb-4">
    <!-- Cursos Activos -->
    <div class="col-md-3 col-sm-6">
        <article class="stat-card">
            <div>
                <p class="text-muted small fw-semibold text-uppercase m-0">Mis Cursos</p>
                <h3 class="fw-bold m-0 mt-1"><?= $total_cursos ?></h3>
            </div>
            <div class="stat-icon stat-icon-blue">
                <i class="fas fa-book-open"></i>
            </div>
        </article>
    </div>
    <!-- Cursos Completados -->
    <div class="col-md-3 col-sm-6">
        <article class="stat-card">
            <div>
                <p class="text-muted small fw-semibold text-uppercase m-0">Completados</p>
                <h3 class="fw-bold m-0 mt-1"><?= $total_completados ?></h3>
            </div>
            <div class="stat-icon stat-icon-green">
                <i class="fas fa-award"></i>
            </div>
        </article>
    </div>
    <!-- Promedio -->
    <div class="col-md-3 col-sm-6">
        <article class="stat-card">
            <div>
                <p class="text-muted small fw-semibold text-uppercase m-0">Mi Promedio</p>
                <h3 class="fw-bold m-0 mt-1"><?= number_format($promedio_notas, 2) ?></h3>
            </div>
            <div class="stat-icon stat-icon-amber">
                <i class="fas fa-star"></i>
            </div>
        </article>
    </div>
    <!-- Inversión Total -->
    <div class="col-md-3 col-sm-6">
        <article class="stat-card">
            <div>
                <p class="text-muted small fw-semibold text-uppercase m-0">Total Invertido</p>
                <h3 class="fw-bold m-0 mt-1">$<?= number_format($total_invertido, 0, ',', '.') ?></h3>
            </div>
            <div class="stat-icon stat-icon-red">
                <i class="fas fa-receipt"></i>
            </div>
        </article>
    </div>
    <!-- Por Aprobación -->
    <div class="col-md-3 col-sm-6">
        <article class="stat-card" style="border-left:4px solid #F59E0B;">
            <div>
                <p class="text-muted small fw-semibold text-uppercase m-0">Por Aprobación</p>
                <h3 class="fw-bold m-0 mt-1">
                    <?php if ($total_pendiente > 0): ?>
                    $<?= number_format($total_pendiente, 0, ',', '.') ?>
                    <?php else: ?>
                    $0
                    <?php endif; ?>
                </h3>
                <?php if ($total_pendientes_count > 0): ?>
                <small class="text-warning fw-semibold"><?= $total_pendientes_count ?> curso<?= $total_pendientes_count !== 1 ? 's' : '' ?></small>
                <?php endif; ?>
            </div>
            <div class="stat-icon" style="background:rgba(245,158,11,0.1);color:#F59E0B;">
                <i class="fas fa-hourglass-half"></i>
            </div>
        </article>
    </div>
</div>

<div class="row g-4">
    <!-- Cursos Recientes con Progreso -->
    <div class="col-lg-8">
        <div class="card-custom">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <span><i class="fas fa-graduation-cap me-2"></i>Mis Cursos Recientes</span>
                <a href="mis-cursos.php" class="btn btn-outline-primary btn-sm rounded-pill">Ver Todos</a>
            </div>
            <div class="card-body-custom">
                <?php if (empty($cursos_recientes)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-book-reader text-muted fs-1 mb-3"></i>
                        <p class="text-muted">Aún no estás inscrito en ningún curso.</p>
                        <a href="explorar-cursos.php" class="btn btn-primary rounded-pill" id="btnExploraCatalogo">
                            <i class="fas fa-compass me-1"></i>Explorar Catálogo
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($cursos_recientes as $item): ?>
                            <div class="col-12">
                                <div class="d-flex align-items-center gap-3 p-3 border border-1 rounded-3">
                                    <img src="<?= $item['imagen_portada'] ? BASE_URL . $item['imagen_portada'] : 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=300&q=80' ?>" 
                                         alt="Portada" 
                                         class="rounded" 
                                         style="width: 80px; height: 50px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <span class="badge bg-light text-primary mb-1"><?= sanitizar_html($item['nombre_categoria']) ?></span>
                                        <h4 class="h6 fw-bold m-0 mb-2"><?= sanitizar_html($item['titulo_curso']) ?></h4>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="progress-bar-custom flex-grow-1">
                                                <div class="progress-bar-fill" style="width: <?= (float)$item['porcentaje_progreso'] ?>%;"></div>
                                            </div>
                                            <span class="small fw-semibold"><?= number_format($item['porcentaje_progreso'], 0) ?>%</span>
                                        </div>
                                    </div>
                                    <a href="ver-clase.php?inscripcion=<?= $item['id_inscripcion_pk'] ?>" class="btn btn-light btn-sm rounded-pill shadow-sm" id="btn-resume-<?= $item['id_inscripcion_pk'] ?>"><i class="fas fa-play text-primary"></i> Estudiar</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Barra Lateral de Enlaces Rápidos y Accesos -->
    <div class="col-lg-4">
        <div class="card-custom mb-4">
            <div class="card-header-custom"><i class="fas fa-life-ring me-2"></i>Accesos Rápidos</div>
            <div class="card-body-custom">
                <div class="d-grid gap-2">
                    <a href="perfil.php" class="btn btn-light text-start p-3 border rounded-3 d-flex align-items-center gap-3">
                        <i class="fas fa-user-edit text-primary fs-5"></i>
                        <div>
                            <p class="fw-bold m-0 small">Mi Perfil</p>
                            <p class="text-muted m-0 xsmall">Configura tus datos y contraseña.</p>
                        </div>
                    </a>
                    <a href="evaluaciones.php" class="btn btn-light text-start p-3 border rounded-3 d-flex align-items-center gap-3">
                        <i class="fas fa-file-invoice text-success fs-5"></i>
                        <div>
                            <p class="fw-bold m-0 small">Mis Exámenes</p>
                            <p class="text-muted m-0 xsmall">Toma tus pruebas automatizadas.</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
