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

$pdo      = obtenerConexion();
$id_curso = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Redirigir estudiantes al detalle dentro del panel
if ($id_curso > 0 && isset($_SESSION['id_usuario'], $_SESSION['id_rol']) && (int)$_SESSION['id_rol'] === ROL_ESTUDIANTE) {
    header("Location: ../student/detalle-curso.php?id=$id_curso");
    exit();
}

if (!$id_curso) {
    header('Location: ../index.php#cursos');
    exit();
}

// ── Función auxiliar para parsear listas (JSON o texto) ─────
function parse_lista($texto) {
    if (!$texto) return [];
    $arr = json_decode($texto, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($arr)) return $arr;
    $lineas = explode("\n", $texto);
    $resultado = [];
    foreach ($lineas as $l) {
        $l = trim($l);
        if ($l !== '') $resultado[] = $l;
    }
    return $resultado;
}

// ── 1. Obtener datos del curso con SQL DIRECTO ───────────────
// (No requiere stored procedures — funciona siempre)
$curso        = false;
$modulos      = [];
$clases_por_modulo = [];
$competencias = [];

// 1a. Datos principales del curso
try {
    $stmt = $pdo->prepare("
        SELECT
            c.id_curso_pk,
            c.titulo_curso,
            c.resumen_corto,
            c.descripcion_detallada,
            c.imagen_portada,
            c.video_presentacion,
            c.tipo_video,
            c.total_horas,
            c.total_clases_estimado,
            c.duracion_meses,
            c.precio,
            c.precio_con_descuento,
            c.nivel_dificultad,
            c.lenguaje_programacion,
            c.requisitos_previos,
            c.certificado_disponible,
            c.numero_estudiantes,
            c.calificacion_promedio,
            cat.nombre_categoria,
            cat.icono_categoria,
            cat.color_categoria,
            u.primer_nombre    AS profesor_nombre,
            u.primer_apellido  AS profesor_apellido,
            u.foto_perfil      AS profesor_foto
        FROM cursos c
        INNER JOIN categorias_curso cat ON cat.id_categoria_pk = c.id_categoria_fk
        LEFT  JOIN usuarios u           ON u.id_usuario_pk     = c.id_profesor_fk
        WHERE c.id_curso_pk = :id
          AND c.estado_activo = 1
    ");
    $stmt->execute([':id' => $id_curso]);
    $curso = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error DB curso ID=$id_curso: " . $e->getMessage());
    $curso = false;
}

// Si el curso no existe, mostrar error claro (NO redirigir silenciosamente)
if (!$curso) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
          <title>Curso no encontrado — EduTech Academy</title>
          <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
          </head><body class="d-flex align-items-center justify-content-center" style="min-height:100vh;background:#F1F5F9;">
          <div class="text-center p-5">
            <h1 class="display-1 fw-bold text-primary">404</h1>
            <h2 class="h4 mb-3">Curso no encontrado</h2>
            <p class="text-muted mb-4">El curso solicitado no existe o no está disponible.</p>
            <a href="../index.php#cursos" class="btn btn-primary rounded-pill px-4">Ver todos los cursos</a>
          </div></body></html>';
    exit();
}

// 1b. Columnas opcionales (existen solo si se ejecutó new_procedures.sql)
// Las leemos con un try separado para no romper si no existen aún
$curso['areas_laborales']   = '';
$curso['titulo_que_otorga'] = '';
$curso['nivel_formacion']   = '';
$curso['metodologia']       = '';
$curso['para_quien_es']     = '';
try {
    $stmt_ext = $pdo->prepare("
        SELECT areas_laborales, titulo_que_otorga, nivel_formacion, metodologia, para_quien_es
        FROM cursos WHERE id_curso_pk = :id
    ");
    $stmt_ext->execute([':id' => $id_curso]);
    $ext = $stmt_ext->fetch(PDO::FETCH_ASSOC);
    if ($ext) {
        $curso['areas_laborales']   = $ext['areas_laborales']   ?? '';
        $curso['titulo_que_otorga'] = $ext['titulo_que_otorga'] ?? '';
        $curso['nivel_formacion']   = $ext['nivel_formacion']   ?? '';
        $curso['metodologia']       = $ext['metodologia']       ?? '';
        $curso['para_quien_es']     = $ext['para_quien_es']     ?? '';
    }
} catch (Exception $e) {
    // Las columnas aún no existen — continúa sin ellas
}

// 1c. Módulos activos
try {
    $stmt_mod = $pdo->prepare("
        SELECT id_modulo_pk, titulo_modulo, descripcion_modulo,
               total_horas_modulo, orden_modulo
        FROM modulos_curso
        WHERE id_curso_fk = :id AND estado_activo = 1
        ORDER BY orden_modulo ASC
    ");
    $stmt_mod->execute([':id' => $id_curso]);
    $modulos = $stmt_mod->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $modulos = [];
}

// 1d. Clases activas (agrupadas por módulo)
try {
    $stmt_cl = $pdo->prepare("
        SELECT cl.id_clase_pk, cl.id_modulo_fk,
               cl.titulo_clase, cl.duracion_minutos, cl.orden_clase,
               cl.es_clase_gratuita
        FROM clases_curso cl
        INNER JOIN modulos_curso m ON m.id_modulo_pk = cl.id_modulo_fk
        WHERE m.id_curso_fk  = :id
          AND cl.estado_activo = 1
          AND m.estado_activo  = 1
        ORDER BY m.orden_modulo ASC, cl.orden_clase ASC
    ");
    $stmt_cl->execute([':id' => $id_curso]);
    $clases_raw = $stmt_cl->fetchAll(PDO::FETCH_ASSOC);
    foreach ($clases_raw as $c) {
        $clases_por_modulo[$c['id_modulo_fk']][] = $c;
    }
} catch (Exception $e) {
    $clases_por_modulo = [];
}

// Agregar conteo de clases a cada módulo
foreach ($modulos as &$mod) {
    $mod['total_clases_modulo'] = count($clases_por_modulo[$mod['id_modulo_pk']] ?? []);
}
unset($mod);

// 1e. Competencias
try {
    $stmt_comp = $pdo->prepare("
        SELECT id_competencia_pk, descripcion_competencia,
               icono_competencia, orden_visualizacion
        FROM competencias_curso
        WHERE id_curso_fk = :id AND estado_activo = 1
        ORDER BY orden_visualizacion ASC
    ");
    $stmt_comp->execute([':id' => $id_curso]);
    $competencias = $stmt_comp->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $competencias = [];
}

// ── 2. Verificar si el usuario está inscrito ─────────────────
$ya_inscrito = false;
if (isset($_SESSION['id_usuario'])) {
    try {
        $stmt_ins = $pdo->prepare("
            SELECT COUNT(*) FROM inscripciones
            WHERE id_usuario_fk      = :usr
              AND id_curso_fk        = :cur
              AND estado_inscripcion IN ('activa','completada')
              AND estado_activo      = 1
        ");
        $stmt_ins->execute([
            ':usr' => (int)$_SESSION['id_usuario'],
            ':cur' => $id_curso
        ]);
        $ya_inscrito = (int)$stmt_ins->fetchColumn() > 0;
    } catch (Exception $e) {
        $ya_inscrito = false;
    }
}

// ── 3. Datos derivados ────────────────────────────────────────
$precio_original = (float)($curso['precio'] ?? 0);
$precio_final    = (!empty($curso['precio_con_descuento']) && (float)$curso['precio_con_descuento'] > 0)
                   ? (float)$curso['precio_con_descuento']
                   : $precio_original;
$tiene_descuento = $precio_final > 0 && $precio_final < $precio_original;
$pct_descuento   = $tiene_descuento ? round((1 - $precio_final / $precio_original) * 100) : 0;

$cat_color  = $curso['color_categoria'] ?? '#2563EB';
$cat_icono  = $curso['icono_categoria'] ?? 'fa-book';

$total_clases   = 0;
foreach ($modulos as $mod) {
    $total_clases += (int)($mod['total_clases_modulo'] ?? 0);
}
$total_horas    = (float)($curso['total_horas'] ?? 0);
$duracion_meses = (int)($curso['duracion_meses'] ?? 0);
$total_horas_str = $total_horas > 0 ? number_format($total_horas, 1) . 'h' : '—';

// Nivel de dificultad con color
$nivel_map = [
    'Principiante' => ['color' => '#16A34A', 'bg' => '#DCFCE7', 'icon' => 'fa-seedling'],
    'Intermedio'   => ['color' => '#2563EB', 'bg' => '#DBEAFE', 'icon' => 'fa-layer-group'],
    'Avanzado'     => ['color' => '#DC2626', 'bg' => '#FEE2E2', 'icon' => 'fa-fire'],
];
$nivel_info = $nivel_map[$curso['nivel_dificultad'] ?? 'Principiante'] ?? $nivel_map['Principiante'];

// Listas
$lista_competencias  = $competencias;
$lista_areas         = parse_lista($curso['areas_laborales']   ?? '');
$lista_requisitos    = parse_lista($curso['requisitos_previos'] ?? '');
$lista_para_quien    = parse_lista($curso['para_quien_es']     ?? '');

// URL del botón de panel según rol
$panel_url = '../student/dashboard.php';
if (isset($_SESSION['id_rol'])) {
    if ((int)$_SESSION['id_rol'] === ROL_ADMIN_TOTAL) {
        $panel_url = '../../admin/index.php';
    } elseif ((int)$_SESSION['id_rol'] === ROL_PROFESOR) {
        $panel_url = '../teacher/dashboard.php';
    }
}

// Helper embed YouTube
function embed_youtube_url($url) {
    if (!$url) return '';
    $id = '';
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $url, $m)) {
        $id = $m[1];
    }
    if ($id) return 'https://www.youtube.com/embed/' . $id . '?rel=0&modestbranding=1';
    return $url;
}

$video_embed = '';
if (!empty($curso['video_presentacion'])) {
    $tipo_video = strtolower($curso['tipo_video'] ?? 'youtube');
    if ($tipo_video === 'youtube') {
        $video_embed = embed_youtube_url($curso['video_presentacion']);
    } elseif ($tipo_video === 'vimeo') {
        if (preg_match('/vimeo\.com\/(\d+)/', $curso['video_presentacion'], $m)) {
            $video_embed = 'https://player.vimeo.com/video/' . $m[1];
        }
    } else {
        $video_embed = $curso['video_presentacion'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitizar_html($curso['titulo_curso']) ?> — <?= SITE_NAME ?></title>
    <meta name="description" content="<?= sanitizar_html($curso['resumen_corto'] ?? '') ?>">

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
    /* ── Variables ──────────────────────────────────────── */
    :root {
        --cat-color: <?= $cat_color ?>;
        --primary:   #1A3C6E;
        --secondary: #2563EB;
        --accent:    #60A5FA;
        --dark:      #0F172A;
        --surface:   #F8FAFC;
        --text:      #334155;
        --muted:     #64748B;
        --radius:    14px;
        --shadow:    0 4px 24px rgba(0,0,0,.10);
        --shadow-lg: 0 8px 40px rgba(0,0,0,.16);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: 'Inter', sans-serif;
        background: #F1F5F9;
        color: var(--text);
        line-height: 1.7;
    }

    /* ── Navbar ─────────────────────────────────────────── */
    .dc-navbar {
        position: sticky;
        top: 0;
        z-index: 1000;
        background: rgba(15,23,42,.95);
        backdrop-filter: blur(14px);
        padding: 14px 0;
        border-bottom: 1px solid rgba(255,255,255,.08);
    }
    .dc-brand {
        font-size: 1.2rem;
        font-weight: 800;
        color: #fff;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
        letter-spacing: -.5px;
    }
    .dc-brand i { color: var(--accent); }

    /* ── Hero ───────────────────────────────────────────── */
    .dc-hero {
        background: linear-gradient(135deg, #0F172A 0%, #1A3C6E 55%, #1e4d8c 100%);
        padding: 60px 0 40px;
        color: #fff;
        position: relative;
        overflow: hidden;
    }
    .dc-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(ellipse 70% 60% at 70% 50%, rgba(96,165,250,.12) 0%, transparent 70%);
        pointer-events: none;
    }

    /* ── Breadcrumb ─────────────────────────────────────── */
    .dc-breadcrumb { font-size: .82rem; }
    .dc-breadcrumb a { color: var(--accent); text-decoration: none; }
    .dc-breadcrumb a:hover { text-decoration: underline; }
    .dc-breadcrumb .active { color: rgba(255,255,255,.7); }
    .breadcrumb-item + .breadcrumb-item::before { color: rgba(255,255,255,.4); }

    /* ── Hero text ──────────────────────────────────────── */
    .dc-category-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: var(--cat-color);
        color: #fff;
        font-size: .78rem;
        font-weight: 700;
        padding: 4px 14px;
        border-radius: 50px;
        letter-spacing: .5px;
        text-transform: uppercase;
        margin-bottom: 14px;
    }
    .dc-hero-title {
        font-size: clamp(1.6rem, 3.5vw, 2.6rem);
        font-weight: 800;
        line-height: 1.25;
        margin-bottom: 14px;
        letter-spacing: -.5px;
    }
    .dc-hero-subtitle {
        font-size: 1.05rem;
        color: rgba(255,255,255,.8);
        margin-bottom: 20px;
        max-width: 640px;
    }

    /* ── Metrics ────────────────────────────────────────── */
    .dc-metrics {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px 0;
        margin: 18px 0;
    }
    .dc-metric {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: .9rem;
        padding: 0 16px;
        border-right: 1px solid rgba(255,255,255,.2);
    }
    .dc-metric:first-child { padding-left: 0; }
    .dc-metric:last-child  { border-right: none; }
    .dc-metric strong { font-weight: 700; color: #fff; }
    .dc-metric span   { color: rgba(255,255,255,.65); }

    /* ── Level & Language badges ────────────────────────── */
    .dc-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 14px;
        border-radius: 50px;
        font-size: .78rem;
        font-weight: 700;
        margin-right: 6px;
    }

    /* ── Professor snippet in hero ──────────────────────── */
    .dc-professor {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-top: 14px;
    }
    .dc-professor-avatar {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid rgba(255,255,255,.4);
    }

    /* ── Buy Card ───────────────────────────────────────── */
    .dc-buy-card {
        background: #fff;
        border-radius: var(--radius);
        box-shadow: var(--shadow-lg);
        overflow: hidden;
        position: sticky;
        top: 90px;
    }
    .dc-buy-card-media {
        position: relative;
        padding-top: 56.25%;
        background: #0F172A;
        overflow: hidden;
    }
    .dc-buy-card-media img,
    .dc-buy-card-media iframe {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        border: none;
    }
    .dc-play-overlay {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: rgba(0,0,0,.45);
        color: #fff;
        gap: 8px;
        transition: background .2s;
    }
    .dc-play-overlay:hover { background: rgba(0,0,0,.6); }
    .dc-play-overlay i { font-size: 3.5rem; filter: drop-shadow(0 2px 8px rgba(0,0,0,.5)); }
    .dc-play-overlay span { font-size: .9rem; font-weight: 600; letter-spacing: .5px; }
    .dc-buy-card-body { padding: 24px; }

    /* Precio */
    .dc-price-block { display: flex; align-items: baseline; gap: 10px; flex-wrap: wrap; }
    .dc-price-main  { font-size: 2rem; font-weight: 800; color: var(--primary); line-height: 1; }
    .dc-price-old   { font-size: 1rem; color: var(--muted); text-decoration: line-through; }
    .dc-price-free  { font-size: 2rem; font-weight: 800; color: #16A34A; }
    .dc-discount-badge {
        background: #FEF3C7;
        color: #92400E;
        font-size: .75rem;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 50px;
    }

    /* Enroll button */
    .dc-btn-enroll {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        padding: 14px 20px;
        background: linear-gradient(135deg, #2563EB, #1A3C6E);
        color: #fff;
        font-size: 1rem;
        font-weight: 700;
        border-radius: 10px;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: transform .15s, box-shadow .15s;
        box-shadow: 0 4px 16px rgba(37,99,235,.35);
        margin-bottom: 10px;
    }
    .dc-btn-enroll:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(37,99,235,.45); color: #fff; }
    .dc-btn-enrolled {
        background: linear-gradient(135deg, #16A34A, #15803D);
        box-shadow: 0 4px 16px rgba(22,163,74,.35);
    }
    .dc-btn-login {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        padding: 11px 20px;
        border: 2px solid #CBD5E1;
        color: var(--text);
        font-size: .9rem;
        font-weight: 600;
        border-radius: 10px;
        text-decoration: none;
        transition: border-color .15s, color .15s;
    }
    .dc-btn-login:hover { border-color: var(--secondary); color: var(--secondary); }

    /* Includes list */
    .dc-includes-list {
        list-style: none;
        padding: 0;
        margin: 0;
        font-size: .85rem;
        color: var(--muted);
    }
    .dc-includes-list li {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 5px 0;
        border-bottom: 1px solid #F1F5F9;
    }
    .dc-includes-list li:last-child { border-bottom: none; }

    /* ── Main content ───────────────────────────────────── */
    .dc-main { padding: 50px 0 80px; }

    .dc-section {
        background: #fff;
        border-radius: var(--radius);
        padding: 32px 36px;
        box-shadow: var(--shadow);
        margin-bottom: 24px;
    }
    .dc-section-title {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--primary);
        margin-bottom: 22px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Video section */
    .dc-video-wrapper {
        position: relative;
        padding-top: 56.25%;
        background: #0F172A;
        border-radius: 10px;
        overflow: hidden;
    }
    .dc-video-wrapper iframe,
    .dc-video-wrapper img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        border: none;
    }
    .dc-video-placeholder {
        position: absolute;
        inset: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #1A3C6E, #0F172A);
        color: rgba(255,255,255,.5);
        gap: 12px;
    }
    .dc-video-placeholder i { font-size: 4rem; }
    .dc-video-placeholder span { font-size: .95rem; }

    /* Learn grid */
    .dc-learn-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 12px;
    }
    .dc-learn-item {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        padding: 12px 14px;
        background: #F8FAFC;
        border-radius: 8px;
        border: 1px solid #E2E8F0;
        font-size: .9rem;
        color: var(--text);
        transition: border-color .2s, box-shadow .2s;
    }
    .dc-learn-item:hover { border-color: var(--accent); box-shadow: 0 2px 10px rgba(96,165,250,.15); }
    .dc-learn-item i { color: #16A34A; flex-shrink: 0; margin-top: 3px; }

    /* Info cards */
    .dc-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 14px;
    }
    .dc-info-card {
        background: linear-gradient(135deg, #F8FAFC, #EFF6FF);
        border: 1px solid #DBEAFE;
        border-radius: 10px;
        padding: 18px 16px;
        text-align: center;
        transition: transform .2s, box-shadow .2s;
    }
    .dc-info-card:hover { transform: translateY(-3px); box-shadow: 0 6px 18px rgba(37,99,235,.12); }
    .dc-info-card-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        background: linear-gradient(135deg, #2563EB, #1A3C6E);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        margin: 0 auto 12px;
    }
    .dc-info-card-label {
        font-size: .73rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .8px;
        color: var(--muted);
        margin-bottom: 6px;
    }
    .dc-info-card-value {
        font-size: .95rem;
        font-weight: 700;
        color: var(--primary);
    }

    /* Areas laborales */
    .dc-areas-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    .dc-area-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: linear-gradient(135deg, #EFF6FF, #DBEAFE);
        color: #1E40AF;
        font-size: .84rem;
        font-weight: 600;
        padding: 8px 16px;
        border-radius: 50px;
        border: 1px solid #BFDBFE;
    }

    /* Bullet list */
    .dc-bullet-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .dc-bullet-list li {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 8px 0;
        border-bottom: 1px solid #F1F5F9;
        font-size: .9rem;
        color: var(--text);
    }
    .dc-bullet-list li:last-child { border-bottom: none; }

    /* Curriculum / Accordion */
    .dc-accordion-item { border: 1px solid #E2E8F0; border-radius: 10px; margin-bottom: 8px; overflow: hidden; }
    .dc-accordion-btn {
        width: 100%;
        background: #F8FAFC;
        border: none;
        padding: 14px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        font-size: .9rem;
        font-weight: 600;
        color: var(--primary);
        transition: background .2s;
    }
    .dc-accordion-btn:hover { background: #EFF6FF; }
    .dc-accordion-btn.open { background: #EFF6FF; }
    .dc-chevron { transition: transform .25s; font-size: .8rem; color: var(--muted); }
    .dc-accordion-btn.open .dc-chevron { transform: rotate(180deg); }
    .dc-accordion-body { display: none; border-top: 1px solid #E2E8F0; }
    .dc-accordion-body.open { display: block; }
    .dc-lesson-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 18px;
        font-size: .85rem;
        color: var(--text);
        border-bottom: 1px solid #F1F5F9;
        gap: 10px;
    }
    .dc-lesson-item:last-child { border-bottom: none; }
    .dc-lesson-item.preview { background: #FFFBEB; }
    .dc-preview-badge {
        background: #FEF3C7;
        color: #92400E;
        font-size: .7rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 50px;
        margin-left: 6px;
    }
    .dc-lesson-duration { color: var(--muted); white-space: nowrap; font-size: .8rem; }
    .dc-lesson-count { font-size: .8rem; color: var(--muted); }
    .dc-curriculum-summary { font-size: .85rem; color: var(--muted); }

    /* Description */
    .dc-description { font-size: .93rem; line-height: 1.85; color: var(--text); }
    .dc-desc-collapsed { max-height: 200px; overflow: hidden; position: relative; }
    .dc-desc-collapsed::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 80px;
        background: linear-gradient(transparent, #fff);
    }
    .dc-show-more {
        background: none;
        border: none;
        color: var(--secondary);
        font-weight: 700;
        font-size: .9rem;
        cursor: pointer;
        margin-top: 8px;
        padding: 0;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .dc-show-more:hover { text-decoration: underline; }

    /* Instructor */
    .dc-instructor-card {
        display: flex;
        gap: 20px;
        align-items: flex-start;
        background: #F8FAFC;
        border-radius: 10px;
        padding: 20px;
        border: 1px solid #E2E8F0;
    }
    .dc-instructor-avatar {
        width: 88px;
        height: 88px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
        border: 3px solid var(--secondary);
    }
    .dc-instructor-name { font-size: 1.1rem; font-weight: 800; color: var(--primary); margin-bottom: 6px; }
    .dc-instructor-bio  { font-size: .88rem; color: var(--muted); margin: 0; line-height: 1.7; }

    /* Share */
    .dc-share-btns { display: flex; gap: 8px; flex-wrap: wrap; }

    /* Footer */
    .dc-footer {
        background: #0F172A;
        color: rgba(255,255,255,.6);
        padding: 30px 0;
        border-top: 1px solid rgba(255,255,255,.06);
    }
    .dc-footer .dc-brand { justify-content: center; font-size: 1.05rem; }

    /* ── Responsive ─────────────────────────────────────── */
    @media (max-width: 991px) {
        .dc-section { padding: 22px 18px; }
        .dc-buy-card { position: static; }
        .dc-hero { padding: 40px 0 30px; }
    }
    @media (max-width: 575px) {
        .dc-metric { padding: 0 10px; font-size: .8rem; }
        .dc-hero-title { font-size: 1.5rem; }
        .dc-learn-grid { grid-template-columns: 1fr; }
        .dc-info-grid { grid-template-columns: repeat(2, 1fr); }
    }
    </style>
</head>
<body>

<!-- ── NAVBAR ────────────────────────────────────────────── -->
<nav class="dc-navbar">
    <div class="container d-flex align-items-center justify-content-between gap-3">
        <a href="../index.php" class="dc-brand">
            <i class="fas fa-graduation-cap"></i> <?= SITE_NAME ?>
        </a>
        <div class="d-flex align-items-center gap-2">
            <?php if (isset($_SESSION['id_usuario'])): ?>
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

<!-- ── HERO ───────────────────────────────────────────────── -->
<section class="dc-hero">
    <div class="container">
        <div class="row align-items-start g-4">

            <!-- Columna izquierda: texto -->
            <div class="col-lg-7">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-3">
                    <ol class="breadcrumb dc-breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="../index.php">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="../index.php#cursos">Cursos</a></li>
                        <li class="breadcrumb-item active"><?= sanitizar_html($curso['titulo_curso']) ?></li>
                    </ol>
                </nav>

                <!-- Categoría -->
                <span class="dc-category-badge">
                    <i class="fas <?= sanitizar_html($cat_icono) ?>"></i>
                    <?= sanitizar_html($curso['nombre_categoria']) ?>
                </span>

                <!-- Título -->
                <h1 class="dc-hero-title"><?= sanitizar_html($curso['titulo_curso']) ?></h1>
                <p class="dc-hero-subtitle"><?= sanitizar_html($curso['resumen_corto'] ?? '') ?></p>

                <!-- Métricas -->
                <div class="dc-metrics">
                    <div class="dc-metric">
                        <i class="fas fa-star text-warning"></i>
                        <strong><?= number_format((float)($curso['calificacion_promedio'] ?? 0), 1) ?></strong>
                        <span>Calificación</span>
                    </div>
                    <div class="dc-metric">
                        <i class="fas fa-users" style="color:#60A5FA;"></i>
                        <strong><?= number_format((int)($curso['numero_estudiantes'] ?? 0)) ?></strong>
                        <span>Estudiantes</span>
                    </div>
                    <div class="dc-metric">
                        <i class="fas fa-clock" style="color:#34D399;"></i>
                        <strong><?= $total_horas_str ?></strong>
                        <span>Contenido</span>
                    </div>
                    <div class="dc-metric">
                        <i class="fas fa-play-circle" style="color:#A78BFA;"></i>
                        <strong><?= $total_clases ?></strong>
                        <span>Clases</span>
                    </div>
                    <?php if ($duracion_meses > 0): ?>
                    <div class="dc-metric">
                        <i class="fas fa-calendar-alt" style="color:#FB923C;"></i>
                        <strong><?= $duracion_meses ?></strong>
                        <span>meses</span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Badges de nivel e idioma -->
                <div class="mt-3 d-flex flex-wrap gap-2">
                    <span class="dc-badge"
                          style="background:<?= $nivel_info['bg'] ?>;color:<?= $nivel_info['color'] ?>;">
                        <i class="fas <?= $nivel_info['icon'] ?>"></i>
                        <?= sanitizar_html($curso['nivel_dificultad']) ?>
                    </span>
                    <?php if (!empty($curso['lenguaje_programacion'])): ?>
                    <span class="dc-badge" style="background:#F1F5F9;color:#475569;">
                        <i class="fas fa-code"></i>
                        <?= sanitizar_html($curso['lenguaje_programacion']) ?>
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($curso['certificado_disponible'])): ?>
                    <span class="dc-badge" style="background:#FEF3C7;color:#92400E;">
                        <i class="fas fa-certificate"></i> Certificado
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Instructor -->
                <?php if (!empty($curso['profesor_nombre'])): ?>
                <div class="dc-professor">
                    <img src="<?= !empty($curso['profesor_foto']) ? sanitizar_html($curso['profesor_foto']) : 'https://ui-avatars.com/api/?name=' . urlencode($curso['profesor_nombre'] . ' ' . $curso['profesor_apellido']) . '&background=2563EB&color=fff&size=64' ?>"
                         alt="<?= sanitizar_html($curso['profesor_nombre']) ?>"
                         class="dc-professor-avatar">
                    <div>
                        <small style="color:rgba(255,255,255,.55);font-size:.75rem;display:block;">Instructor</small>
                        <span style="font-weight:600;color:#fff;">
                            <?= sanitizar_html($curso['profesor_nombre'] . ' ' . $curso['profesor_apellido']) ?>
                        </span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Columna derecha: Card de compra -->
            <div class="col-lg-5">
                <div class="dc-buy-card" id="buyCard">

                    <!-- Video / Imagen -->
                    <div class="dc-buy-card-media">
                        <?php if ($video_embed): ?>
                            <iframe src="<?= sanitizar_html($video_embed) ?>"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen
                                    title="Video de presentación: <?= sanitizar_html($curso['titulo_curso']) ?>"></iframe>
                        <?php elseif (!empty($curso['imagen_portada'])): ?>
                            <img src="<?= sanitizar_html($curso['imagen_portada']) ?>"
                                 alt="<?= sanitizar_html($curso['titulo_curso']) ?>">
                        <?php else: ?>
                            <div class="dc-play-overlay">
                                <i class="fas fa-graduation-cap"></i>
                                <span><?= sanitizar_html($curso['titulo_curso']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="dc-buy-card-body">
                        <!-- Precio -->
                        <div class="dc-price-block mb-3">
                            <?php if ($precio_final > 0): ?>
                                <span class="dc-price-main">
                                    <?= MONEDA_SIMBOLO . number_format($precio_final, 0, ',', '.') ?> COP
                                </span>
                                <?php if ($tiene_descuento): ?>
                                    <span class="dc-price-old">
                                        <?= MONEDA_SIMBOLO . number_format($precio_original, 0, ',', '.') ?>
                                    </span>
                                    <span class="dc-discount-badge">-<?= $pct_descuento ?>% OFF</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="dc-price-free">¡GRATIS!</span>
                            <?php endif; ?>
                        </div>

                        <!-- Botón principal -->
                        <?php if ($ya_inscrito): ?>
                            <a href="../student/mis-cursos.php" class="dc-btn-enroll dc-btn-enrolled">
                                <i class="fas fa-play-circle"></i> Continuar Aprendiendo
                            </a>
                        <?php elseif (isset($_SESSION['id_usuario'])): ?>
                            <a href="../student/inscripcion.php?curso=<?= $id_curso ?>" class="dc-btn-enroll" id="btnComprar">
                                <i class="fas fa-graduation-cap"></i>
                                <?= $precio_final > 0 ? 'Comprar e Inscribirme' : 'Inscribirme Gratis' ?>
                            </a>
                        <?php else: ?>
                            <a href="../auth/registro.php?redirect=<?= urlencode('cursos/detalle.php?id=' . $id_curso) ?>"
                               class="dc-btn-enroll" id="btnComprar">
                                <i class="fas fa-user-plus"></i> Registrarme e Inscribirme
                            </a>
                            <a href="../auth/login.php?redirect=<?= urlencode('cursos/detalle.php?id=' . $id_curso) ?>"
                               class="dc-btn-login">
                                Ya tengo cuenta — Iniciar sesión
                            </a>
                        <?php endif; ?>

                        <!-- Garantía -->
                        <p class="text-center text-muted small mt-3 mb-3">
                            <i class="fas fa-shield-alt me-1 text-success"></i>
                            Garantía de satisfacción · Acceso de por vida
                        </p>

                        <!-- Incluye -->
                        <ul class="dc-includes-list">
                            <?php if ($total_clases > 0): ?>
                            <li><i class="fas fa-play-circle text-primary"></i> <?= $total_clases ?> clases en video</li>
                            <?php endif; ?>
                            <?php if ($total_horas > 0): ?>
                            <li><i class="fas fa-clock text-success"></i> <?= $total_horas_str ?> de contenido</li>
                            <?php endif; ?>
                            <?php if ($duracion_meses > 0): ?>
                            <li><i class="fas fa-calendar text-info"></i> Duración: <?= $duracion_meses ?> meses</li>
                            <?php endif; ?>
                            <li><i class="fas fa-infinity text-warning"></i> Acceso de por vida</li>
                            <?php if (!empty($curso['certificado_disponible'])): ?>
                            <li><i class="fas fa-certificate text-danger"></i> Certificado de finalización</li>
                            <?php endif; ?>
                            <li><i class="fas fa-mobile-alt text-secondary"></i> Acceso en móvil y PC</li>
                        </ul>

                        <!-- Compartir -->
                        <div class="mt-3">
                            <p class="small text-muted mb-2"><i class="fas fa-share-alt me-1"></i> Compartir:</p>
                            <div class="dc-share-btns">
                                <a href="https://wa.me/?text=<?= urlencode($curso['titulo_curso'] . ' — ' . BASE_URL . 'cursos/detalle.php?id=' . $id_curso) ?>"
                                   target="_blank" class="btn btn-sm btn-outline-success rounded-pill">
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
            </div>

        </div>
    </div>
</section>

<!-- ── CONTENIDO PRINCIPAL ──────────────────────────────────── -->
<main class="dc-main">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-8">

                <!-- ① VIDEO / PRESENTACIÓN ─────────────────────── -->
                <?php if ($video_embed || !empty($curso['imagen_portada'])): ?>
                <section class="dc-section" id="video-presentacion">
                    <h2 class="dc-section-title">
                        <i class="fas fa-play-circle text-primary"></i> Video de Presentación
                    </h2>
                    <div class="dc-video-wrapper">
                        <?php if ($video_embed): ?>
                            <iframe src="<?= sanitizar_html($video_embed) ?>"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen
                                    title="Presentación del curso"></iframe>
                        <?php elseif (!empty($curso['imagen_portada'])): ?>
                            <img src="<?= sanitizar_html($curso['imagen_portada']) ?>"
                                 alt="<?= sanitizar_html($curso['titulo_curso']) ?>">
                        <?php endif; ?>
                    </div>
                </section>
                <?php else: ?>
                <!-- Placeholder si no hay video ni imagen -->
                <section class="dc-section" id="video-presentacion">
                    <h2 class="dc-section-title">
                        <i class="fas fa-play-circle text-primary"></i> Video de Presentación
                    </h2>
                    <div class="dc-video-wrapper">
                        <div class="dc-video-placeholder">
                            <i class="fas fa-film"></i>
                            <span>Video próximamente</span>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

                <!-- ② DESCRIPCIÓN DEL CURSO ─────────────────────── -->
                <?php if (!empty($curso['descripcion_detallada'])): ?>
                <section class="dc-section" id="descripcion">
                    <h2 class="dc-section-title">
                        <i class="fas fa-book-open text-primary"></i> Descripción del Curso
                    </h2>
                    <div class="dc-description dc-desc-collapsed" id="descContent">
                        <?= nl2br(sanitizar_html($curso['descripcion_detallada'])) ?>
                    </div>
                    <button class="dc-show-more" id="btnShowMore" onclick="toggleDesc()">
                        <i class="fas fa-chevron-down" id="descIcon"></i> Mostrar más
                    </button>
                </section>
                <?php endif; ?>

                <!-- ③ COMPETENCIAS A ADQUIRIR ────────────────────── -->
                <?php if (!empty($lista_competencias)): ?>
                <section class="dc-section" id="competencias">
                    <h2 class="dc-section-title">
                        <i class="fas fa-check-double text-success"></i> Competencias que Adquirirás
                    </h2>
                    <div class="dc-learn-grid">
                        <?php foreach ($lista_competencias as $comp): ?>
                        <div class="dc-learn-item">
                            <i class="fas <?= sanitizar_html($comp['icono_competencia'] ?? 'fa-check') ?>"></i>
                            <span><?= sanitizar_html($comp['descripcion_competencia']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- ④ ÁREAS LABORALES ────────────────────────────── -->
                <?php if (!empty($lista_areas)): ?>
                <section class="dc-section" id="areas-laborales">
                    <h2 class="dc-section-title">
                        <i class="fas fa-briefcase text-info"></i> Áreas de Desempeño Laboral
                    </h2>
                    <p class="text-muted small mb-3">Al completar este curso podrás desempeñarte en las siguientes áreas:</p>
                    <div class="dc-areas-grid">
                        <?php foreach ($lista_areas as $area): ?>
                        <span class="dc-area-chip">
                            <i class="fas fa-angle-right"></i>
                            <?= sanitizar_html($area) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- ⑤ INFORMACIÓN ACADÉMICA (Título, Nivel, Metodología, Duración) ── -->
                <section class="dc-section" id="informacion-academica">
                    <h2 class="dc-section-title">
                        <i class="fas fa-university text-primary"></i> Información Académica
                    </h2>
                    <div class="dc-info-grid">

                        <?php if (!empty($curso['titulo_que_otorga'])): ?>
                        <div class="dc-info-card">
                            <div class="dc-info-card-icon"><i class="fas fa-scroll"></i></div>
                            <div class="dc-info-card-label">Título que Otorga</div>
                            <div class="dc-info-card-value"><?= sanitizar_html($curso['titulo_que_otorga']) ?></div>
                        </div>
                        <?php else: ?>
                        <div class="dc-info-card">
                            <div class="dc-info-card-icon"><i class="fas fa-scroll"></i></div>
                            <div class="dc-info-card-label">Certificado</div>
                            <div class="dc-info-card-value">
                                <?= !empty($curso['certificado_disponible']) ? 'Certificado de Finalización' : 'No incluye certificado' ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="dc-info-card">
                            <div class="dc-info-card-icon"><i class="fas fa-signal"></i></div>
                            <div class="dc-info-card-label">Nivel de Formación</div>
                            <div class="dc-info-card-value">
                                <?= !empty($curso['nivel_formacion'])
                                    ? sanitizar_html($curso['nivel_formacion'])
                                    : sanitizar_html($curso['nivel_dificultad']) ?>
                            </div>
                        </div>

                        <div class="dc-info-card">
                            <div class="dc-info-card-icon"><i class="fas fa-clock"></i></div>
                            <div class="dc-info-card-label">Duración Estimada</div>
                            <div class="dc-info-card-value">
                                <?php
                                $dur_parts = [];
                                if ($duracion_meses > 0) $dur_parts[] = $duracion_meses . ' mes' . ($duracion_meses > 1 ? 'es' : '');
                                if ($total_horas > 0)   $dur_parts[] = $total_horas_str;
                                echo $dur_parts ? sanitizar_html(implode(' / ', $dur_parts)) : '— Por definir';
                                ?>
                            </div>
                        </div>

                        <div class="dc-info-card">
                            <div class="dc-info-card-icon"><i class="fas fa-laptop-code"></i></div>
                            <div class="dc-info-card-label">Modalidad</div>
                            <div class="dc-info-card-value">Virtual · 100% Online</div>
                        </div>

                        <?php if (!empty($curso['lenguaje_programacion'])): ?>
                        <div class="dc-info-card">
                            <div class="dc-info-card-icon"><i class="fas fa-code"></i></div>
                            <div class="dc-info-card-label">Tecnología</div>
                            <div class="dc-info-card-value"><?= sanitizar_html($curso['lenguaje_programacion']) ?></div>
                        </div>
                        <?php endif; ?>

                        <div class="dc-info-card">
                            <div class="dc-info-card-icon"><i class="fas fa-play"></i></div>
                            <div class="dc-info-card-label">Total de Clases</div>
                            <div class="dc-info-card-value"><?= $total_clases ?> clases</div>
                        </div>

                    </div>

                    <!-- Metodología -->
                    <?php if (!empty($curso['metodologia'])): ?>
                    <div class="mt-4 p-3 rounded-3" style="background:#F0F9FF;border:1px solid #BAE6FD;">
                        <h3 style="font-size:.95rem;font-weight:700;color:#0369A1;margin-bottom:8px;">
                            <i class="fas fa-chalkboard-teacher me-2"></i>Metodología
                        </h3>
                        <p style="font-size:.9rem;color:#0C4A6E;margin:0;line-height:1.75;">
                            <?= nl2br(sanitizar_html($curso['metodologia'])) ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </section>

                <!-- ⑥ CONTENIDO DEL CURSO (Módulos) ────────────────── -->
                <?php if (!empty($modulos)): ?>
                <section class="dc-section" id="contenido">
                    <h2 class="dc-section-title">
                        <i class="fas fa-list-ol text-primary"></i> Contenido del Curso
                    </h2>
                    <div class="dc-curriculum-summary mb-3">
                        <span><?= count($modulos) ?> módulo<?= count($modulos) > 1 ? 's' : '' ?></span>
                        <span class="mx-2">·</span>
                        <span><?= $total_clases ?> clases</span>
                        <span class="mx-2">·</span>
                        <span><?= $total_horas_str ?> en total</span>
                    </div>

                    <div id="curriculumAccordion">
                        <?php foreach ($modulos as $i => $mod):
                            $clases_mod = $clases_por_modulo[$mod['id_modulo_pk']] ?? [];
                        ?>
                        <div class="dc-accordion-item">
                            <button class="dc-accordion-btn <?= $i === 0 ? 'open' : '' ?>"
                                    onclick="toggleMod(this, 'mod-<?= $mod['id_modulo_pk'] ?>')"
                                    id="btn-mod-<?= $mod['id_modulo_pk'] ?>">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-folder text-warning"></i>
                                    <span><?= sanitizar_html($mod['titulo_modulo']) ?></span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="dc-lesson-count"><?= count($clases_mod) ?> clases</span>
                                    <i class="fas fa-chevron-down dc-chevron"></i>
                                </div>
                            </button>
                            <div class="dc-accordion-body <?= $i === 0 ? 'open' : '' ?>"
                                 id="mod-<?= $mod['id_modulo_pk'] ?>">
                                <?php foreach ($clases_mod as $clase): ?>
                                <div class="dc-lesson-item <?= $clase['es_clase_gratuita'] ? 'preview' : '' ?>">
                                    <div class="d-flex align-items-center gap-2 flex-grow-1">
                                        <i class="fas fa-play-circle text-primary"></i>
                                        <span><?= sanitizar_html($clase['titulo_clase']) ?></span>
                                        <?php if ($clase['es_clase_gratuita']): ?>
                                            <span class="dc-preview-badge">Vista previa</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($clase['duracion_minutos'] > 0): ?>
                                    <span class="dc-lesson-duration">
                                        <i class="fas fa-clock me-1"></i><?= (int)$clase['duracion_minutos'] ?>min
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                                <?php if (empty($clases_mod)): ?>
                                <div class="dc-lesson-item text-muted">
                                    <i class="fas fa-info-circle me-2"></i> Clases próximamente
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- ⑦ REQUISITOS PREVIOS ─────────────────────────── -->
                <?php if (!empty($lista_requisitos)): ?>
                <section class="dc-section" id="requisitos">
                    <h2 class="dc-section-title">
                        <i class="fas fa-clipboard-list text-warning"></i> Requisitos Previos
                    </h2>
                    <ul class="dc-bullet-list">
                        <?php foreach ($lista_requisitos as $req): ?>
                        <li>
                            <i class="fas fa-dot-circle text-muted"></i>
                            <span><?= sanitizar_html($req) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
                <?php endif; ?>

                <!-- ⑧ ¿PARA QUIÉN ES? ─────────────────────────────── -->
                <?php if (!empty($lista_para_quien)): ?>
                <section class="dc-section" id="para-quien">
                    <h2 class="dc-section-title">
                        <i class="fas fa-users text-info"></i> ¿Para Quién es Este Curso?
                    </h2>
                    <ul class="dc-bullet-list">
                        <?php foreach ($lista_para_quien as $pq): ?>
                        <li>
                            <i class="fas fa-user-check text-info"></i>
                            <span><?= sanitizar_html($pq) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
                <?php endif; ?>

                <!-- ⑨ INSTRUCTOR ──────────────────────────────────── -->
                <?php if (!empty($curso['profesor_nombre'])): ?>
                <section class="dc-section" id="instructor">
                    <h2 class="dc-section-title">
                        <i class="fas fa-chalkboard-teacher text-primary"></i> Tu Instructor
                    </h2>
                    <div class="dc-instructor-card">
                        <img src="<?= !empty($curso['profesor_foto']) ? sanitizar_html($curso['profesor_foto']) : 'https://ui-avatars.com/api/?name=' . urlencode($curso['profesor_nombre'] . ' ' . $curso['profesor_apellido']) . '&background=2563EB&color=fff&size=128' ?>"
                             alt="<?= sanitizar_html($curso['profesor_nombre']) ?>"
                             class="dc-instructor-avatar">
                        <div>
                            <div class="dc-instructor-name">
                                <?= sanitizar_html($curso['profesor_nombre'] . ' ' . $curso['profesor_apellido']) ?>
                            </div>
                            <p class="dc-instructor-bio">
                                Docente especialista de <?= SITE_NAME ?>, con amplia experiencia en su área de conocimiento.
                            </p>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

            </div><!-- /.col-lg-8 -->

            <!-- Sidebar (visible en desktop) -->
            <div class="col-lg-4 d-none d-lg-block">
                <div style="position:sticky;top:90px;">
                    <div class="dc-buy-card">
                        <div class="dc-buy-card-body">
                            <div class="dc-price-block mb-3">
                                <?php if ($precio_final > 0): ?>
                                    <span class="dc-price-main">
                                        <?= MONEDA_SIMBOLO . number_format($precio_final, 0, ',', '.') ?> COP
                                    </span>
                                    <?php if ($tiene_descuento): ?>
                                        <span class="dc-price-old">
                                            <?= MONEDA_SIMBOLO . number_format($precio_original, 0, ',', '.') ?>
                                        </span>
                                        <span class="dc-discount-badge">-<?= $pct_descuento ?>% OFF</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="dc-price-free">¡GRATIS!</span>
                                <?php endif; ?>
                            </div>

                            <?php if ($ya_inscrito): ?>
                                <a href="../student/mis-cursos.php" class="dc-btn-enroll dc-btn-enrolled">
                                    <i class="fas fa-play-circle"></i> Continuar Aprendiendo
                                </a>
                            <?php elseif (isset($_SESSION['id_usuario'])): ?>
                                <a href="../student/inscripcion.php?curso=<?= $id_curso ?>" class="dc-btn-enroll">
                                    <i class="fas fa-graduation-cap"></i>
                                    <?= $precio_final > 0 ? 'Comprar e Inscribirme' : 'Inscribirme Gratis' ?>
                                </a>
                            <?php else: ?>
                                <a href="../auth/registro.php?redirect=<?= urlencode('cursos/detalle.php?id=' . $id_curso) ?>"
                                   class="dc-btn-enroll">
                                    <i class="fas fa-user-plus"></i> Registrarme e Inscribirme
                                </a>
                            <?php endif; ?>

                            <p class="text-center text-muted small mt-2 mb-3">
                                <i class="fas fa-shield-alt me-1 text-success"></i>
                                Garantía de satisfacción incluida
                            </p>

                            <ul class="dc-includes-list">
                                <?php if ($total_clases > 0): ?>
                                <li><i class="fas fa-play-circle text-primary"></i> <?= $total_clases ?> clases en video</li>
                                <?php endif; ?>
                                <?php if ($total_horas > 0): ?>
                                <li><i class="fas fa-clock text-success"></i> <?= $total_horas_str ?> de contenido</li>
                                <?php endif; ?>
                                <li><i class="fas fa-infinity text-warning"></i> Acceso de por vida</li>
                                <?php if (!empty($curso['certificado_disponible'])): ?>
                                <li><i class="fas fa-certificate text-danger"></i> Certificado incluido</li>
                                <?php endif; ?>
                                <li><i class="fas fa-mobile-alt text-secondary"></i> Móvil y PC</li>
                            </ul>

                            <!-- Índice de navegación rápida -->
                            <div class="mt-4">
                                <p class="small fw-bold text-muted mb-2" style="text-transform:uppercase;letter-spacing:.7px;font-size:.72rem;">
                                    Navegar al apartado:
                                </p>
                                <div class="d-flex flex-column gap-1">
                                    <?php if ($video_embed || !empty($curso['imagen_portada'])): ?>
                                    <a href="#video-presentacion" class="btn btn-sm btn-outline-secondary text-start rounded-2">
                                        <i class="fas fa-play-circle me-2 text-primary"></i>Video presentación
                                    </a>
                                    <?php endif; ?>
                                    <?php if (!empty($curso['descripcion_detallada'])): ?>
                                    <a href="#descripcion" class="btn btn-sm btn-outline-secondary text-start rounded-2">
                                        <i class="fas fa-book-open me-2 text-primary"></i>Descripción
                                    </a>
                                    <?php endif; ?>
                                    <?php if (!empty($lista_competencias)): ?>
                                    <a href="#competencias" class="btn btn-sm btn-outline-secondary text-start rounded-2">
                                        <i class="fas fa-check-double me-2 text-success"></i>Competencias
                                    </a>
                                    <?php endif; ?>
                                    <?php if (!empty($lista_areas)): ?>
                                    <a href="#areas-laborales" class="btn btn-sm btn-outline-secondary text-start rounded-2">
                                        <i class="fas fa-briefcase me-2 text-info"></i>Áreas laborales
                                    </a>
                                    <?php endif; ?>
                                    <a href="#informacion-academica" class="btn btn-sm btn-outline-secondary text-start rounded-2">
                                        <i class="fas fa-university me-2 text-primary"></i>Info académica
                                    </a>
                                    <?php if (!empty($modulos)): ?>
                                    <a href="#contenido" class="btn btn-sm btn-outline-secondary text-start rounded-2">
                                        <i class="fas fa-list-ol me-2 text-primary"></i>Contenido
                                    </a>
                                    <?php endif; ?>
                                    <?php if (!empty($curso['profesor_nombre'])): ?>
                                    <a href="#instructor" class="btn btn-sm btn-outline-secondary text-start rounded-2">
                                        <i class="fas fa-chalkboard-teacher me-2 text-primary"></i>Instructor
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /.row -->
    </div><!-- /.container -->
</main>

<!-- ── FOOTER ──────────────────────────────────────────────── -->
<footer class="dc-footer">
    <div class="container text-center">
        <a href="../index.php" class="dc-brand mb-2">
            <i class="fas fa-graduation-cap"></i> <?= SITE_NAME ?>
        </a>
        <p class="small mb-0" style="color:rgba(255,255,255,.45);margin-top:8px;">
            &copy; <?= date('Y') ?> <?= SITE_NAME ?>. Todos los derechos reservados.
        </p>
    </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Toggle descripción ──────────────────────────────────────
var descExpanded = false;
function toggleDesc() {
    var content = document.getElementById('descContent');
    var btn     = document.getElementById('btnShowMore');
    var icon    = document.getElementById('descIcon');
    descExpanded = !descExpanded;
    if (descExpanded) {
        content.classList.remove('dc-desc-collapsed');
        btn.innerHTML = '<i class="fas fa-chevron-up" id="descIcon"></i> Mostrar menos';
    } else {
        content.classList.add('dc-desc-collapsed');
        btn.innerHTML = '<i class="fas fa-chevron-down" id="descIcon"></i> Mostrar más';
    }
}

// ── Toggle módulo del currículum ────────────────────────────
function toggleMod(btn, targetId) {
    var body = document.getElementById(targetId);
    if (!body) return;
    var isOpen = body.classList.contains('open');
    if (isOpen) {
        body.classList.remove('open');
        btn.classList.remove('open');
    } else {
        body.classList.add('open');
        btn.classList.add('open');
    }
}

// ── Smooth scroll para links del sidebar ────────────────────
document.querySelectorAll('a[href^="#"]').forEach(function(a) {
    a.addEventListener('click', function(e) {
        var target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    });
});
</script>
</body>
</html>
