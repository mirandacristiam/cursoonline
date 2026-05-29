<?php
// /cursoonline/cursos/detalle.php
// ============================================================
// Página de Detalle de Curso — EduTech Academy
// ============================================================

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/session.php';
require_once '../includes/csrf.php';

iniciar_sesion_segura();

$pdo = obtenerConexion();
$id_curso = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id_curso) {
    header('Location: ../index.php#cursos');
    exit();
}

// ── Obtener datos del curso (Consulta Directa a Base de Datos) ────
try {
    $stmt_curso = $pdo->prepare("
        SELECT c.*, c.numero_estudiantes AS total_estudiantes_inscritos,
               'Español' AS idioma,
               cat.nombre_categoria, cat.icono_categoria, cat.color_categoria,
               u.primer_nombre AS profesor_nombre, u.primer_apellido AS profesor_apellido,
               u.foto_perfil AS profesor_foto, u.descripcion_bio AS profesor_bio
        FROM cursos c
        INNER JOIN categorias_curso cat ON cat.id_categoria_pk = c.id_categoria_fk
        LEFT JOIN usuarios u ON u.id_usuario_pk = c.id_profesor_fk
        WHERE c.id_curso_pk = :id AND c.estado_activo = 1
    ");
    $stmt_curso->execute([':id' => $id_curso]);
    $curso = $stmt_curso->fetch();

    if ($curso) {
        // Obtener competencias (Lo que aprenderás)
        $stmt_comp = $pdo->prepare("
            SELECT descripcion_competencia 
            FROM competencias_curso 
            WHERE id_curso_fk = :id AND estado_activo = 1
            ORDER BY orden_visualizacion ASC
        ");
        $stmt_comp->execute([':id' => $id_curso]);
        $lo_que_aprenderas = $stmt_comp->fetchAll(PDO::FETCH_COLUMN);

        // Obtener módulos
        $stmt_mod = $pdo->prepare("
            SELECT m.id_modulo_pk, m.titulo_modulo, m.orden_modulo
            FROM modulos_curso m
            WHERE m.id_curso_fk = :id AND m.estado_activo = 1
            ORDER BY m.orden_modulo ASC
        ");
        $stmt_mod->execute([':id' => $id_curso]);
        $modulos = $stmt_mod->fetchAll();

        // Obtener clases
        $stmt_clase = $pdo->prepare("
            SELECT cl.id_clase_pk, cl.id_modulo_fk, cl.titulo_clase,
                   cl.duracion_minutos, 'video' AS tipo_clase, cl.es_clase_gratuita AS es_preview_gratuito, cl.orden_clase
            FROM clases_curso cl
            INNER JOIN modulos_curso m ON m.id_modulo_pk = cl.id_modulo_fk
            WHERE m.id_curso_fk = :id AND cl.estado_activo = 1 AND m.estado_activo = 1
            ORDER BY m.orden_modulo, cl.orden_clase
        ");
        $stmt_clase->execute([':id' => $id_curso]);
        $clases = $stmt_clase->fetchAll();

        // Agrupar clases por módulo
        $clases_por_modulo = [];
        foreach ($clases as $cl) {
            $clases_por_modulo[$cl['id_modulo_fk']][] = $cl;
        }

        // Agregar conteo de clases estimado a los módulos para compatibilidad
        foreach ($modulos as &$m) {
            $m['nombre_modulo'] = $m['titulo_modulo']; // alias para compatibilidad visual
            $m['total_clases_modulo'] = isset($clases_por_modulo[$m['id_modulo_pk']]) ? count($clases_por_modulo[$m['id_modulo_pk']]) : 0;
        }
        unset($m);
    }
} catch (Exception $e) {
    error_log("Error al cargar detalle del curso: " . $e->getMessage());
    $curso = false;
}

if (!$curso) {
    header('Location: ../index.php#cursos');
    exit();
}

// ── ¿El usuario ya está inscrito? ───────────────────────────
$ya_inscrito = false;
if (isset($_SESSION['id_usuario'])) {
    $stmt_ins = $pdo->prepare("
        SELECT 1 FROM inscripciones
        WHERE id_curso_fk = :curso AND id_usuario_fk = :usr AND estado_inscripcion = 'activa' AND estado_activo = 1
    ");
    $stmt_ins->execute([':curso' => $id_curso, ':usr' => $_SESSION['id_usuario']]);
    $ya_inscrito = (bool)$stmt_ins->fetchColumn();
}

// ── Procesar JSON de texto enriquecido ──────────────────────
function parse_lista($texto) {
    if (!$texto) return [];
    // Soporta JSON array o texto simple por líneas
    $arr = json_decode($texto, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($arr)) return $arr;
    return array_filter(array_map('trim', explode("\n", $texto)));
}

// $lo_que_aprenderas ya viene cargado de competencias_curso en el try
if (!isset($lo_que_aprenderas) || empty($lo_que_aprenderas)) {
    $lo_que_aprenderas = parse_lista($curso['lo_que_aprenderas'] ?? '');
}
$requisitos        = parse_lista($curso['requisitos_previos'] ?? '');
$para_quien        = parse_lista($curso['para_quien_es'] ?? '');

// Precio con descuento
$precio_original = (float)($curso['precio'] ?? 0);
$precio_final    = !empty($curso['precio_con_descuento']) ? (float)$curso['precio_con_descuento'] : $precio_original;
$tiene_descuento = !empty($curso['precio_con_descuento']) && (float)$curso['precio_con_descuento'] < $precio_original;
$pct_descuento   = $tiene_descuento ? round((1 - $precio_final / $precio_original) * 100) : 0;

// Colores de nivel
$nivel_colores = [
    'principiante' => ['color' => '#16A34A', 'bg' => '#DCFCE7', 'icon' => 'fa-seedling',   'label' => 'Principiante'],
    'intermedio'   => ['color' => '#2563EB', 'bg' => '#DBEAFE', 'icon' => 'fa-layer-group', 'label' => 'Intermedio'],
    'avanzado'     => ['color' => '#DC2626', 'bg' => '#FEE2E2', 'icon' => 'fa-fire',        'label' => 'Avanzado'],
];
$nivel = $nivel_colores[strtolower($curso['nivel_dificultad'])] ?? $nivel_colores['principiante'];

$cat_color = $curso['color_categoria'] ?? '#2563EB';

// Total de clases y duración
$total_clases  = array_sum(array_column($modulos, 'total_clases_modulo'));
$total_horas   = (float)($curso['total_horas'] ?? 0);
$total_min_str = $total_horas > 0 ? number_format($total_horas, 1) . 'h' : '—';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitizar_html($curso['titulo_curso']) ?> — EduTech Academy</title>
    <meta name="description" content="<?= sanitizar_html($curso['resumen_corto'] ?? '') ?>">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- CSS del Detalle (Separado) -->
    <link href="../assets/css/detalle-curso.css" rel="stylesheet">
</head>
<body>

<!-- ── NAVBAR ─────────────────────────────────────────────── -->
<nav class="dc-navbar">
    <div class="container d-flex align-items-center justify-content-between gap-3">
        <a href="../index.php" class="dc-brand">
            <i class="fas fa-graduation-cap"></i> EduTech Academy
        </a>
        <div class="d-flex align-items-center gap-2">
            <?php if (isset($_SESSION['id_usuario'])): ?>
                <?php
                $panel_url = '../student/dashboard.php';
                switch ((int)($_SESSION['id_rol'] ?? 0)) {
                    case ROL_ADMIN_TOTAL:
                        $panel_url = '../../admin/index.php';
                        break;
                    case ROL_PROFESOR:
                        $panel_url = '../teacher/dashboard.php';
                        break;
                }
                ?>
                <a href="<?= $panel_url ?>" class="btn btn-outline-light btn-sm rounded-pill">
                    <i class="fas fa-th-large me-1"></i> Mi Panel
                </a>
                <a href="../auth/logout.php" class="btn btn-outline-danger btn-sm rounded-pill">
                    <i class="fas fa-sign-out-alt me-1"></i> Salir
                </a>
            <?php else: ?>
                <a href="../auth/login.php" class="btn btn-outline-light btn-sm rounded-pill me-1">Iniciar sesión</a>
                <a href="../auth/registro.php" class="btn btn-warning btn-sm rounded-pill fw-bold">Registrarme</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- ── HERO DEL CURSO ─────────────────────────────────────── -->
<section class="dc-hero" style="--cat-color: <?= $cat_color ?>;">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb dc-breadcrumb">
                        <li class="breadcrumb-item"><a href="../index.php">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="../index.php#cursos">Cursos</a></li>
                        <li class="breadcrumb-item active"><?= sanitizar_html($curso['titulo_curso']) ?></li>
                    </ol>
                </nav>

                <!-- Categoría badge -->
                <span class="dc-category-badge">
                    <i class="fas <?= sanitizar_html($curso['icono_categoria'] ?? 'fa-book') ?> me-1"></i>
                    <?= sanitizar_html($curso['nombre_categoria']) ?>
                </span>

                <h1 class="dc-hero-title"><?= sanitizar_html($curso['titulo_curso']) ?></h1>
                <p class="dc-hero-subtitle"><?= sanitizar_html($curso['resumen_corto'] ?? '') ?></p>

                <!-- Métricas del curso -->
                <div class="dc-metrics">
                    <div class="dc-metric">
                        <i class="fas fa-star text-warning"></i>
                        <strong><?= number_format((float)($curso['calificacion_promedio'] ?? 0), 1) ?></strong>
                        <span>Calificación</span>
                    </div>
                    <div class="dc-metric-sep"></div>
                    <div class="dc-metric">
                        <i class="fas fa-users text-info"></i>
                        <strong><?= number_format((int)($curso['total_estudiantes_inscritos'] ?? 0)) ?></strong>
                        <span>Estudiantes</span>
                    </div>
                    <div class="dc-metric-sep"></div>
                    <div class="dc-metric">
                        <i class="fas fa-clock text-success"></i>
                        <strong><?= $total_min_str ?></strong>
                        <span>de contenido</span>
                    </div>
                    <div class="dc-metric-sep"></div>
                    <div class="dc-metric">
                        <i class="fas fa-play-circle text-purple"></i>
                        <strong><?= $total_clases ?></strong>
                        <span>clases</span>
                    </div>
                </div>

                <!-- Docente -->
                <?php if ($curso['profesor_nombre']): ?>
                <div class="dc-professor mt-3">
                    <img src="<?= $curso['profesor_foto'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($curso['profesor_nombre'] . '+' . $curso['profesor_apellido']) . '&background=2563EB&color=fff&size=64' ?>"
                         alt="<?= sanitizar_html($curso['profesor_nombre']) ?>"
                         class="dc-professor-avatar">
                    <div>
                        <small class="text-muted d-block" style="font-size:0.78rem;">Instructor</small>
                        <span class="fw-semibold text-white">
                            <?= sanitizar_html($curso['profesor_nombre'] . ' ' . $curso['profesor_apellido']) ?>
                        </span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Nivel -->
                <div class="mt-3">
                    <span class="dc-level-badge" style="background:<?= $nivel['bg'] ?>;color:<?= $nivel['color'] ?>;">
                        <i class="fas <?= $nivel['icon'] ?> me-1"></i> <?= $nivel['label'] ?>
                    </span>
                    <?php if ($curso['idioma']): ?>
                    <span class="dc-level-badge ms-2" style="background:#F1F5F9;color:#475569;">
                        <i class="fas fa-language me-1"></i> <?= sanitizar_html($curso['idioma']) ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── Card de Compra ──────────────────────────── -->
            <div class="col-lg-5">
                <div class="dc-buy-card" id="buyCard">
                    <!-- Imagen / Video preview -->
                    <div class="dc-buy-card-img">
                        <?php if (!empty($curso['video_presentacion'])): ?>
                            <a href="<?= sanitizar_html($curso['video_presentacion']) ?>" target="_blank" class="dc-play-btn">
                                <img src="<?= sanitizar_html($curso['imagen_portada'] ?: 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=600&q=80') ?>"
                                     alt="<?= sanitizar_html($curso['titulo_curso']) ?>">
                                <div class="dc-play-overlay">
                                    <i class="fas fa-play-circle"></i>
                                    <span>Ver Preview</span>
                                </div>
                            </a>
                        <?php else: ?>
                            <img src="<?= sanitizar_html($curso['imagen_portada'] ?: 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=600&q=80') ?>"
                                 alt="<?= sanitizar_html($curso['titulo_curso']) ?>">
                        <?php endif; ?>
                    </div>

                    <div class="dc-buy-card-body">
                        <!-- Precio -->
                        <div class="dc-price-block mb-3">
                            <?php if ($precio_final > 0): ?>
                                <span class="dc-price-main"><?= MONEDA_SIMBOLO . number_format($precio_final, 0, ',', '.') ?> COP</span>
                                <?php if ($tiene_descuento): ?>
                                    <span class="dc-price-old"><?= MONEDA_SIMBOLO . number_format($precio_original, 0, ',', '.') ?></span>
                                    <span class="dc-price-pct">-<?= $pct_descuento ?>%</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="dc-price-main text-success">¡GRATIS!</span>
                            <?php endif; ?>
                        </div>

                        <!-- Botón de acción -->
                        <?php if ($ya_inscrito): ?>
                            <a href="../student/mis-cursos.php" class="dc-btn-enroll dc-btn-enrolled">
                                <i class="fas fa-play me-2"></i> Continuar Aprendiendo
                            </a>
                        <?php elseif (isset($_SESSION['id_usuario'])): ?>
                            <a href="../student/inscripcion.php?curso=<?= $id_curso ?>"
                               class="dc-btn-enroll">
                                <i class="fas fa-graduation-cap me-2"></i>
                                <?= $precio_final > 0 ? 'Inscribirme Ahora' : 'Inscribirme Gratis' ?>
                            </a>
                        <?php else: ?>
                            <a href="../auth/registro.php?redirect=cursos/detalle.php?id=<?= $id_curso ?>"
                               class="dc-btn-enroll">
                                <i class="fas fa-user-plus me-2"></i> Registrarme e Inscribirme
                            </a>
                            <a href="../auth/login.php?redirect=cursos/detalle.php?id=<?= $id_curso ?>"
                               class="dc-btn-login mt-2">
                                Ya tengo cuenta — Iniciar sesión
                            </a>
                        <?php endif; ?>

                        <!-- Incluye -->
                        <ul class="dc-includes-list mt-3">
                            <li><i class="fas fa-play-circle text-primary"></i> <?= $total_clases ?> clases en video</li>
                            <li><i class="fas fa-clock text-success"></i> <?= $total_min_str ?> de contenido</li>
                            <li><i class="fas fa-infinity text-warning"></i> Acceso de por vida</li>
                            <li><i class="fas fa-certificate text-danger"></i> Certificado de finalización</li>
                            <li><i class="fas fa-mobile-alt text-info"></i> Acceso en móvil y PC</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ── CONTENIDO PRINCIPAL ────────────────────────────────── -->
<main class="dc-main">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-8">

                <!-- ── LO QUE APRENDERÁS ──────────────────── -->
                <?php if (!empty($lo_que_aprenderas)): ?>
                <section class="dc-section" id="aprenderas">
                    <h2 class="dc-section-title"><i class="fas fa-check-circle text-success me-2"></i>Lo que aprenderás</h2>
                    <div class="dc-learn-grid">
                        <?php foreach ($lo_que_aprenderas as $item): ?>
                        <div class="dc-learn-item">
                            <i class="fas fa-check text-success flex-shrink-0 mt-1"></i>
                            <span><?= sanitizar_html($item) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- ── DESCRIPCIÓN COMPLETA ───────────────── -->
                <?php if (!empty($curso['descripcion_detallada'])): ?>
                <section class="dc-section" id="descripcion">
                    <h2 class="dc-section-title"><i class="fas fa-book-open text-primary me-2"></i>Descripción del curso</h2>
                    <div class="dc-description" id="descriptionContent">
                        <?= nl2br(sanitizar_html($curso['descripcion_detallada'])) ?>
                    </div>
                    <button class="dc-show-more" id="btnShowMore">
                        <i class="fas fa-chevron-down me-1"></i> Mostrar más
                    </button>
                </section>
                <?php endif; ?>

                <!-- ── CONTENIDO DEL CURSO (MÓDULOS) ─────── -->
                <?php if (!empty($modulos)): ?>
                <section class="dc-section" id="contenido">
                    <h2 class="dc-section-title"><i class="fas fa-list-ol text-primary me-2"></i>Contenido del curso</h2>
                    <div class="dc-curriculum-summary mb-3">
                        <span><?= count($modulos) ?> módulo<?= count($modulos) > 1 ? 's' : '' ?></span>
                        <span class="mx-2">·</span>
                        <span><?= $total_clases ?> clases</span>
                        <span class="mx-2">·</span>
                        <span><?= $total_min_str ?> en total</span>
                    </div>

                    <div class="dc-accordion" id="curriculumAccordion">
                        <?php foreach ($modulos as $i => $modulo):
                            $clases_mod = $clases_por_modulo[$modulo['id_modulo_pk']] ?? [];
                        ?>
                        <div class="dc-accordion-item">
                            <button class="dc-accordion-btn <?= $i === 0 ? '' : 'collapsed' ?>"
                                    data-target="mod-<?= $modulo['id_modulo_pk'] ?>">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-folder text-warning"></i>
                                    <span class="fw-semibold"><?= sanitizar_html($modulo['nombre_modulo']) ?></span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="dc-lesson-count"><?= count($clases_mod) ?> clases</span>
                                    <i class="fas fa-chevron-down dc-chevron"></i>
                                </div>
                            </button>
                            <div class="dc-accordion-body <?= $i === 0 ? 'open' : '' ?>" id="mod-<?= $modulo['id_modulo_pk'] ?>">
                                <?php foreach ($clases_mod as $clase): ?>
                                <div class="dc-lesson-item <?= $clase['es_preview_gratuito'] ? 'preview' : '' ?>">
                                    <div class="d-flex align-items-center gap-2">
                                        <?php
                                        $tipo_icon = 'fa-circle text-muted';
                                        switch ($clase['tipo_clase']) {
                                            case 'video':
                                                $tipo_icon = 'fa-play-circle text-primary';
                                                break;
                                            case 'quiz':
                                                $tipo_icon = 'fa-question-circle text-warning';
                                                break;
                                            case 'tarea':
                                                $tipo_icon = 'fa-file-alt text-success';
                                                break;
                                        }
                                        ?>
                                        <i class="fas <?= $tipo_icon ?>"></i>
                                        <span><?= sanitizar_html($clase['titulo_clase']) ?></span>
                                        <?php if ($clase['es_preview_gratuito']): ?>
                                            <span class="dc-preview-badge">Vista previa</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($clase['duracion_minutos']): ?>
                                    <span class="dc-lesson-duration">
                                        <i class="fas fa-clock me-1"></i>
                                        <?= $clase['duracion_minutos'] ?>min
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                                <?php if (empty($clases_mod)): ?>
                                    <div class="dc-lesson-item text-muted small">
                                        <i class="fas fa-info-circle me-1"></i> Clases próximamente
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- ── REQUISITOS ─────────────────────────── -->
                <?php if (!empty($requisitos)): ?>
                <section class="dc-section" id="requisitos">
                    <h2 class="dc-section-title"><i class="fas fa-clipboard-list text-warning me-2"></i>Requisitos previos</h2>
                    <ul class="dc-bullet-list">
                        <?php foreach ($requisitos as $req): ?>
                        <li><i class="fas fa-dot-circle text-muted me-2"></i><?= sanitizar_html($req) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
                <?php endif; ?>

                <!-- ── PARA QUIÉN ES ──────────────────────── -->
                <?php if (!empty($para_quien)): ?>
                <section class="dc-section" id="para-quien">
                    <h2 class="dc-section-title"><i class="fas fa-users text-info me-2"></i>¿Para quién es este curso?</h2>
                    <ul class="dc-bullet-list">
                        <?php foreach ($para_quien as $pq): ?>
                        <li><i class="fas fa-user-check text-info me-2"></i><?= sanitizar_html($pq) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </section>
                <?php endif; ?>

                <!-- ── INSTRUCTOR ─────────────────────────── -->
                <?php if ($curso['profesor_nombre']): ?>
                <section class="dc-section" id="instructor">
                    <h2 class="dc-section-title"><i class="fas fa-chalkboard-teacher text-primary me-2"></i>Tu instructor</h2>
                    <div class="dc-instructor-card">
                        <img src="<?= $curso['profesor_foto'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($curso['profesor_nombre'] . '+' . $curso['profesor_apellido']) . '&background=2563EB&color=fff&size=128' ?>"
                             alt="<?= sanitizar_html($curso['profesor_nombre']) ?>"
                             class="dc-instructor-avatar">
                        <div class="dc-instructor-info">
                            <h3 class="dc-instructor-name">
                                <?= sanitizar_html($curso['profesor_nombre'] . ' ' . $curso['profesor_apellido']) ?>
                            </h3>
                            <p class="dc-instructor-bio">
                                <?= $curso['profesor_bio'] ? nl2br(sanitizar_html($curso['profesor_bio'])) : 'Docente experto en EduTech Academy con amplia experiencia en su área.' ?>
                            </p>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

            </div><!-- /.col-lg-8 -->

            <!-- ── SIDEBAR FLOTANTE (escritorio) ──────────── -->
            <div class="col-lg-4 d-none d-lg-block">
                <div class="dc-sticky-sidebar">
                    <!-- La card de compra se repite aquí para pantallas grandes -->
                    <div class="dc-buy-card-mini">
                        <div class="dc-price-block mb-3">
                            <?php if ($precio_final > 0): ?>
                                <span class="dc-price-main"><?= MONEDA_SIMBOLO . number_format($precio_final, 0, ',', '.') ?> COP</span>
                                <?php if ($tiene_descuento): ?>
                                    <span class="dc-price-old"><?= MONEDA_SIMBOLO . number_format($precio_original, 0, ',', '.') ?></span>
                                    <span class="dc-price-pct">-<?= $pct_descuento ?>%</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="dc-price-main text-success">¡GRATIS!</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($ya_inscrito): ?>
                            <a href="../student/mis-cursos.php" class="dc-btn-enroll dc-btn-enrolled">
                                <i class="fas fa-play me-2"></i> Continuar Aprendiendo
                            </a>
                        <?php elseif (isset($_SESSION['id_usuario'])): ?>
                            <a href="../student/inscripcion.php?curso=<?= $id_curso ?>" class="dc-btn-enroll">
                                <i class="fas fa-graduation-cap me-2"></i>
                                <?= $precio_final > 0 ? 'Inscribirme Ahora' : 'Inscribirme Gratis' ?>
                            </a>
                        <?php else: ?>
                            <a href="../auth/registro.php" class="dc-btn-enroll">
                                <i class="fas fa-user-plus me-2"></i> Registrarme e Inscribirme
                            </a>
                        <?php endif; ?>
                        <ul class="dc-includes-list mt-3">
                            <li><i class="fas fa-play-circle text-primary"></i> <?= $total_clases ?> clases en video</li>
                            <li><i class="fas fa-clock text-success"></i> <?= $total_min_str ?> de contenido</li>
                            <li><i class="fas fa-infinity text-warning"></i> Acceso de por vida</li>
                            <li><i class="fas fa-certificate text-danger"></i> Certificado incluido</li>
                        </ul>
                        <div class="dc-share-btns mt-3">
                            <p class="small text-muted mb-2">Compartir este curso:</p>
                            <a href="https://wa.me/?text=<?= urlencode($curso['titulo_curso'] . ' ' . BASE_URL . 'cursos/detalle.php?id=' . $id_curso) ?>"
                               target="_blank" class="btn btn-sm btn-outline-success rounded-pill me-1">
                                <i class="fab fa-whatsapp"></i> WhatsApp
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(BASE_URL . 'cursos/detalle.php?id=' . $id_curso) ?>"
                               target="_blank" class="btn btn-sm btn-outline-primary rounded-pill">
                                <i class="fab fa-facebook"></i> Facebook
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /.row -->
    </div><!-- /.container -->
</main>

<!-- ── FOOTER ─────────────────────────────────────────────── -->
<footer class="dc-footer">
    <div class="container text-center">
        <a href="../index.php" class="dc-brand mb-2 d-inline-block">
            <i class="fas fa-graduation-cap"></i> EduTech Academy
        </a>
        <p class="text-muted small mb-0">&copy; <?= date('Y') ?> EduTech Academy. Todos los derechos reservados.</p>
    </div>
</footer>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/detalle-curso.js"></script>
</body>
</html>
