<?php
// /cursoonline/student/explorar-cursos.php
// ============================================================
// Catálogo de Cursos para Explorar — Panel Estudiante
// EduTech Academy
// ============================================================

$page_title = 'Explorar Cursos';
require_once __DIR__ . '/includes/header.php';

// ── 1. Filtros ────────────────────────────────────────────────
$filtro_categoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$filtro_nivel     = isset($_GET['nivel'])     ? trim($_GET['nivel'])    : '';
$filtro_buscar    = isset($_GET['buscar'])    ? trim($_GET['buscar'])   : '';

// ── 2. Categorías para el filtro ─────────────────────────────
$stmt_cat = $pdo->query("SELECT id_categoria_pk, nombre_categoria, icono_categoria, color_categoria FROM categorias_curso WHERE estado_activo = 1 ORDER BY nombre_categoria ASC");
$categorias = $stmt_cat->fetchAll();

// ── 3. Cursos con filtros dinámicos ──────────────────────────
$where  = "WHERE c.estado_activo = 1";
$params = [];

if ($filtro_categoria > 0) {
    $where .= " AND c.id_categoria_fk = :cat";
    $params[':cat'] = $filtro_categoria;
}
if ($filtro_nivel !== '') {
    $where .= " AND c.nivel_dificultad = :nivel";
    $params[':nivel'] = $filtro_nivel;
}
if ($filtro_buscar !== '') {
    $where .= " AND (c.titulo_curso LIKE :buscar OR c.resumen_corto LIKE :buscar2)";
    $params[':buscar']  = '%' . $filtro_buscar . '%';
    $params[':buscar2'] = '%' . $filtro_buscar . '%';
}

$stmt_cursos = $pdo->prepare("
    SELECT c.id_curso_pk, c.titulo_curso, c.resumen_corto, c.imagen_portada,
           c.precio, c.precio_con_descuento, c.nivel_dificultad,
           c.total_horas, c.numero_estudiantes, c.calificacion_promedio,
           cat.nombre_categoria, cat.icono_categoria, cat.color_categoria,
           u.primer_nombre AS prof_nombre, u.primer_apellido AS prof_apellido
    FROM cursos c
    JOIN categorias_curso cat ON c.id_categoria_fk = cat.id_categoria_pk
    LEFT JOIN usuarios u ON c.id_profesor_fk = u.id_usuario_pk
    $where
    AND NOT EXISTS (
        SELECT 1 FROM inscripciones i
        WHERE i.id_curso_fk = c.id_curso_pk
          AND i.id_usuario_fk = :id_user
          AND i.estado_activo = 1
    )
    ORDER BY c.calificacion_promedio DESC, c.titulo_curso ASC
");
$params[':id_user'] = $id_usuario;
$stmt_cursos->execute($params);
$cursos = $stmt_cursos->fetchAll();

// Niveles de dificultad
$niveles = ['Principiante', 'Intermedio', 'Avanzado'];
?>

<!-- ── TÍTULO DE SECCIÓN ──────────────────────────────────── -->
<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 fw-bold text-primary mb-1">
            <i class="fas fa-compass me-2"></i>Explorar Cursos
        </h1>
        <p class="text-muted m-0">Descubre todos los cursos disponibles e inscríbete directamente desde aquí.</p>
    </div>
</div>

<!-- ── FILTROS ────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm rounded-4 mb-4 p-3">
    <form method="GET" id="formFiltros" class="row g-3 align-items-end">
        <!-- Buscar -->
        <div class="col-lg-4 col-md-6">
            <label class="form-label small fw-semibold text-muted" for="buscar">
                <i class="fas fa-search me-1"></i>Buscar curso
            </label>
            <input type="text" name="buscar" id="buscar"
                   class="form-control rounded-3"
                   placeholder="Ej: Python, Machine Learning…"
                   value="<?= sanitizar_html($filtro_buscar) ?>">
        </div>
        <!-- Categoría -->
        <div class="col-lg-3 col-md-6">
            <label class="form-label small fw-semibold text-muted" for="categoria">
                <i class="fas fa-tag me-1"></i>Categoría
            </label>
            <select name="categoria" id="categoria" class="form-select rounded-3">
                <option value="0">Todas las categorías</option>
                <?php foreach ($categorias as $cat): ?>
                <option value="<?= $cat['id_categoria_pk'] ?>"
                    <?= $filtro_categoria === (int)$cat['id_categoria_pk'] ? 'selected' : '' ?>>
                    <?= sanitizar_html($cat['nombre_categoria']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Nivel -->
        <div class="col-lg-3 col-md-6">
            <label class="form-label small fw-semibold text-muted" for="nivel">
                <i class="fas fa-signal me-1"></i>Nivel
            </label>
            <select name="nivel" id="nivel" class="form-select rounded-3">
                <option value="">Todos los niveles</option>
                <?php foreach ($niveles as $niv): ?>
                <option value="<?= $niv ?>" <?= $filtro_nivel === $niv ? 'selected' : '' ?>>
                    <?= $niv ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <!-- Botones -->
        <div class="col-lg-2 col-md-6 d-flex gap-2">
            <button type="submit" class="btn btn-primary rounded-3 flex-grow-1" id="btnFiltrar">
                <i class="fas fa-filter me-1"></i>Filtrar
            </button>
            <a href="explorar-cursos.php" class="btn btn-outline-secondary rounded-3" title="Limpiar filtros">
                <i class="fas fa-times"></i>
            </a>
        </div>
    </form>
</div>

<!-- ── RESULTADOS ─────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <span class="text-muted small">
        <i class="fas fa-list me-1"></i>
        <strong><?= count($cursos) ?></strong> curso<?= count($cursos) !== 1 ? 's' : '' ?> encontrado<?= count($cursos) !== 1 ? 's' : '' ?>
        <?php if ($filtro_buscar || $filtro_categoria || $filtro_nivel): ?>
            — <a href="explorar-cursos.php" class="text-danger text-decoration-none small">
                <i class="fas fa-times me-1"></i>Quitar filtros
            </a>
        <?php endif; ?>
    </span>
</div>

<?php if (empty($cursos)): ?>
<!-- Sin resultados -->
<div class="text-center py-5">
    <div class="card border-0 shadow-sm rounded-4 p-5">
        <i class="fas fa-search text-muted fs-1 mb-3 opacity-50"></i>
        <h2 class="h5 fw-bold">No se encontraron cursos</h2>
        <p class="text-muted mb-4">Prueba con otros términos de búsqueda o cambia los filtros.</p>
        <a href="explorar-cursos.php" class="btn btn-primary rounded-pill px-4" id="btnVerTodos">
            Ver todos los cursos
        </a>
    </div>
</div>

<?php else: ?>
<!-- Grid de cursos -->
<div class="row g-4">
    <?php foreach ($cursos as $curso):
        $precio_orig  = (float)($curso['precio'] ?? 0);
        $precio_final = (!empty($curso['precio_con_descuento']) && (float)$curso['precio_con_descuento'] > 0)
                        ? (float)$curso['precio_con_descuento'] : $precio_orig;
        $tiene_desc   = $precio_final < $precio_orig && $precio_orig > 0;
        $pct_desc     = $tiene_desc ? round((1 - $precio_final / $precio_orig) * 100) : 0;
        $nivel_map = [
            'Principiante' => ['color' => '#16A34A', 'bg' => '#DCFCE7'],
            'Intermedio'   => ['color' => '#2563EB', 'bg' => '#DBEAFE'],
            'Avanzado'     => ['color' => '#DC2626', 'bg' => '#FEE2E2'],
        ];
        $nivel_info = $nivel_map[$curso['nivel_dificultad']] ?? $nivel_map['Principiante'];
        $cat_color  = $curso['color_categoria'] ?? '#2563EB';
    ?>
    <div class="col-xl-4 col-lg-6 col-md-6">
        <article class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden"
                 style="transition: transform .2s, box-shadow .2s;"
                 onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 32px rgba(0,0,0,.12)';"
                 onmouseout="this.style.transform='translateY(0)';this.style.boxShadow='';">

            <!-- Imagen portada -->
            <div style="position:relative;padding-top:52%;overflow:hidden;background:#0F172A;">
                <img src="<?= $curso['imagen_portada'] ? BASE_URL . $curso['imagen_portada'] : 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=600&q=80' ?>"
                     alt="<?= sanitizar_html($curso['titulo_curso']) ?>"
                     style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;">
                <!-- Badge categoría -->
                <span class="badge position-absolute"
                      style="top:.75rem;left:.75rem;background:<?= $cat_color ?>;font-size:.72rem;padding:5px 10px;">
                    <i class="fas <?= sanitizar_html($curso['icono_categoria'] ?? 'fa-book') ?> me-1"></i>
                    <?= sanitizar_html($curso['nombre_categoria']) ?>
                </span>
                <!-- Badge descuento -->
                <?php if ($tiene_desc): ?>
                <span class="badge bg-danger position-absolute"
                      style="top:.75rem;right:.75rem;font-size:.72rem;">
                    -<?= $pct_desc ?>% OFF
                </span>
                <?php endif; ?>
            </div>

            <div class="card-body d-flex flex-column p-4">
                <!-- Nivel -->
                <span class="badge mb-2 align-self-start"
                      style="background:<?= $nivel_info['bg'] ?>;color:<?= $nivel_info['color'] ?>;font-size:.72rem;">
                    <?= sanitizar_html($curso['nivel_dificultad']) ?>
                </span>

                <!-- Título -->
                <h2 class="h6 fw-bold text-primary mb-1" style="line-height:1.4;">
                    <?= sanitizar_html($curso['titulo_curso']) ?>
                </h2>
                <p class="text-muted small mb-3" style="line-height:1.5;flex-grow:1;">
                    <?= sanitizar_html(mb_substr($curso['resumen_corto'] ?? '', 0, 100)) ?>…
                </p>

                <!-- Stats -->
                <div class="d-flex align-items-center gap-3 mb-3" style="font-size:.8rem;color:#64748B;">
                    <?php if ((float)$curso['calificacion_promedio'] > 0): ?>
                    <span><i class="fas fa-star text-warning me-1"></i><?= number_format((float)$curso['calificacion_promedio'], 1) ?></span>
                    <?php endif; ?>
                    <?php if ((int)$curso['numero_estudiantes'] > 0): ?>
                    <span><i class="fas fa-users me-1"></i><?= number_format((int)$curso['numero_estudiantes']) ?></span>
                    <?php endif; ?>
                    <?php if ((float)$curso['total_horas'] > 0): ?>
                    <span><i class="fas fa-clock me-1"></i><?= number_format((float)$curso['total_horas'], 1) ?>h</span>
                    <?php endif; ?>
                </div>

                <!-- Precio -->
                <div class="d-flex align-items-baseline gap-2 mb-3">
                    <?php if ($precio_final > 0): ?>
                        <span class="fw-bold text-primary fs-6">
                            <?= MONEDA_SIMBOLO . number_format($precio_final, 0, ',', '.') ?> COP
                        </span>
                        <?php if ($tiene_desc): ?>
                        <span class="text-muted text-decoration-line-through small">
                            <?= MONEDA_SIMBOLO . number_format($precio_orig, 0, ',', '.') ?>
                        </span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="fw-bold text-success fs-6">¡GRATIS!</span>
                    <?php endif; ?>
                </div>

                <!-- Botones de acción -->
                <div class="d-grid gap-2 mt-auto">
                    <a href="detalle-curso.php?id=<?= $curso['id_curso_pk'] ?>"
                       class="btn btn-primary rounded-3">
                        <i class="fas fa-eye me-2"></i>Ver Curso
                    </a>
                    <a href="inscripcion.php?curso=<?= $curso['id_curso_pk'] ?>"
                       class="btn btn-outline-primary rounded-3">
                        <i class="fas fa-graduation-cap me-2"></i>
                        <?= $precio_final > 0 ? 'Inscribirme' : 'Inscribirme Gratis' ?>
                    </a>
                </div>
            </div>
        </article>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
