<?php
// /admin/cursos/index.php
// ============================================================
// Gestión de Cursos — Panel Administrador — EduTech Academy
// ============================================================

$page_title = 'Gestión de Cursos';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../cursoonline/includes/csrf.php';

$msg_ok = $msg_err = '';

// ── POST: Toggle estado del curso ─────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = limpiar_entrada($_POST['accion'] ?? '');
    $token  = $_POST['csrf_token'] ?? '';
    if (!validar_token_csrf($pdo, $token)) {
        $msg_err = 'Token de seguridad inválido.';
    } elseif ($accion === 'toggle_curso') {
        $id_curso     = (int)($_POST['id_curso']     ?? 0);
        $nuevo_estado = (int)($_POST['nuevo_estado'] ?? 0);
        if ($id_curso) {
            $pdo->prepare("UPDATE cursos SET estado_activo = ?, fecha_actualizacion = NOW() WHERE id_curso_pk = ?")
                ->execute([$nuevo_estado, $id_curso]);
            $msg_ok = $nuevo_estado ? 'Curso activado.' : 'Curso desactivado.';
        }
    }
}

// ── Filtros ───────────────────────────────────────────────────
$busqueda     = limpiar_entrada($_GET['q']    ?? '');
$filtro_cat   = (int)($_GET['cat']            ?? 0);
$filtro_est   = limpiar_entrada($_GET['est']  ?? 'todos');
$por_pagina   = 12;
$pagina       = max(1, (int)($_GET['p']       ?? 1));
$offset       = ($pagina - 1) * $por_pagina;

// Categorías para el filtro
$categorias = $pdo->query("SELECT id_categoria_pk, nombre_categoria FROM categorias_curso WHERE estado_activo = 1 ORDER BY nombre_categoria")->fetchAll();

// ── Consulta de cursos ────────────────────────────────────────
$where  = ['1=1'];
$params = [];

if (!empty($busqueda)) {
    $where[] = "(c.titulo_curso LIKE :q OR c.resumen_corto LIKE :q)";
    $params[':q'] = "%{$busqueda}%";
}
if ($filtro_cat) {
    $where[] = "c.id_categoria_fk = :cat";
    $params[':cat'] = $filtro_cat;
}
if ($filtro_est !== 'todos') {
    $where[] = "c.estado_activo = :est";
    $params[':est'] = $filtro_est === 'activo' ? 1 : 0;
}

$where_sql = implode(' AND ', $where);

$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM cursos c WHERE {$where_sql}");
$stmt_count->execute($params);
$total_cursos   = (int)$stmt_count->fetchColumn();
$total_paginas  = max(1, ceil($total_cursos / $por_pagina));

$stmt_cursos = $pdo->prepare("
    SELECT c.id_curso_pk, c.titulo_curso, c.resumen_corto, c.precio,
           c.estado_activo, c.fecha_creacion, c.nivel_dificultad,
           cat.nombre_categoria, cat.color_categoria,
           u.primer_nombre, u.primer_apellido,
           (SELECT COUNT(*) FROM inscripciones WHERE id_curso_fk = c.id_curso_pk AND estado_inscripcion = 'activa' AND estado_activo = 1) AS total_inscritos
    FROM cursos c
    LEFT JOIN categorias_curso cat ON cat.id_categoria_pk = c.id_categoria_fk
    LEFT JOIN usuarios u ON u.id_usuario_pk = c.id_profesor_fk
    WHERE {$where_sql}
    ORDER BY c.fecha_creacion DESC
    LIMIT :limit OFFSET :offset
");
foreach ($params as $key => $val) {
    $stmt_cursos->bindValue($key, $val);
}
$stmt_cursos->bindValue(':limit',  $por_pagina, PDO::PARAM_INT);
$stmt_cursos->bindValue(':offset', $offset,     PDO::PARAM_INT);
$stmt_cursos->execute();
$cursos = $stmt_cursos->fetchAll();
?>

<div class="page-header">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Cursos</li>
        </ol>
    </nav>
    <h1><i class="fas fa-graduation-cap me-2 text-danger"></i>Gestión de Cursos</h1>
    <p>Administra el catálogo de cursos, precios, docentes asignados y estado de publicación.</p>
</div>

<?php if ($msg_ok): ?><div class="alert alert-success auto-dismiss rounded-3 mb-3"><?= sanitizar_html($msg_ok) ?></div><?php endif; ?>
<?php if ($msg_err): ?><div class="alert alert-danger rounded-3 mb-3"><?= sanitizar_html($msg_err) ?></div><?php endif; ?>

<!-- Filtros -->
<div class="admin-card mb-4">
    <div class="admin-card-header">
        <span><i class="fas fa-filter me-2"></i>Filtros (<?= $total_cursos ?> cursos encontrados)</span>
        <a href="crear.php" class="btn-admin-primary btn-sm"><i class="fas fa-plus"></i> Nuevo Curso</a>
    </div>
    <div class="admin-card-body">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
            <input type="text" class="form-control form-control-sm" name="q"
                   value="<?= sanitizar_html($busqueda) ?>"
                   placeholder="Buscar por nombre o descripción..." id="tableSearch"
                   style="max-width:280px;">
            <select name="cat" class="form-select form-select-sm" style="max-width:200px;" onchange="this.form.submit()">
                <option value="0">Todas las categorías</option>
                <?php foreach ($categorias as $cat): ?>
                <option value="<?= $cat['id_categoria_pk'] ?>" <?= $filtro_cat === (int)$cat['id_categoria_pk'] ? 'selected' : '' ?>>
                    <?= sanitizar_html($cat['nombre_categoria']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <select name="est" class="form-select form-select-sm" style="max-width:130px;" onchange="this.form.submit()">
                <option value="todos"   <?= $filtro_est==='todos'   ?'selected':'' ?>>Todos</option>
                <option value="activo"  <?= $filtro_est==='activo'  ?'selected':'' ?>>Publicados</option>
                <option value="inactivo"<?= $filtro_est==='inactivo'?'selected':'' ?>>Despublicados</option>
            </select>
            <button type="submit" class="btn-admin-outline btn-sm"><i class="fas fa-search"></i></button>
        </form>
    </div>
</div>

<!-- Grid de Cursos -->
<div class="row g-3">
    <?php if (empty($cursos)): ?>
        <div class="col-12">
            <div class="admin-card">
                <div class="admin-card-body text-center py-5 text-muted">
                    <i class="fas fa-book-open fa-2x mb-3 d-block opacity-50"></i>
                    No se encontraron cursos con los filtros aplicados.
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($cursos as $c): ?>
        <div class="col-md-6 col-xl-4">
            <div class="admin-card h-100 mb-0">
                <!-- Header del curso con color de categoría -->
                <div class="p-3 pb-2" style="background:<?= sanitizar_html($c['color_categoria'] ?? '#2563EB') ?>1A;border-bottom:3px solid <?= sanitizar_html($c['color_categoria'] ?? '#2563EB') ?>;">
                    <div class="d-flex justify-content-between align-items-start">
                        <span class="badge rounded-pill small" style="background:<?= sanitizar_html($c['color_categoria'] ?? '#2563EB') ?>20;color:<?= sanitizar_html($c['color_categoria'] ?? '#2563EB') ?>;">
                            <?= sanitizar_html($c['nombre_categoria'] ?? 'Sin categoría') ?>
                        </span>
                        <span class="badge-admin <?= (int)$c['estado_activo'] ? 'activo' : 'inactivo' ?>">
                            <?= (int)$c['estado_activo'] ? 'Publicado' : 'Oculto' ?>
                        </span>
                    </div>
                    <h6 class="fw-bold mt-2 mb-1" style="font-size:0.9rem;">
                        <?= sanitizar_html($c['titulo_curso']) ?>
                    </h6>
                    <p class="text-muted mb-0" style="font-size:0.78rem;line-height:1.4;">
                        <?= sanitizar_html(mb_strimwidth($c['resumen_corto'] ?? '', 0, 80, '...')) ?>
                    </p>
                </div>
                <div class="admin-card-body py-2">
                    <div class="d-flex justify-content-between text-muted small mb-2">
                        <span><i class="fas fa-user-tie me-1"></i>
                            <?= isset($c['primer_nombre']) ? sanitizar_html($c['primer_nombre'] . ' ' . $c['primer_apellido']) : 'Sin docente' ?>
                        </span>
                        <span><i class="fas fa-users me-1"></i><?= (int)$c['total_inscritos'] ?> est.</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-success">
                            <?= $c['precio'] > 0 ? '$' . number_format((float)$c['precio'], 0, ',', '.') . ' COP' : 'Gratis' ?>
                        </span>
                        <div class="d-flex gap-1">
                            <a href="editar.php?id=<?= $c['id_curso_pk'] ?>"
                               class="btn btn-outline-primary btn-sm" style="border-radius:8px;" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" style="display:inline;">
                                <?php imprimir_campo_csrf($pdo, 'toggle_c'); ?>
                                <input type="hidden" name="accion" value="toggle_curso">
                                <input type="hidden" name="id_curso" value="<?= $c['id_curso_pk'] ?>">
                                <input type="hidden" name="nuevo_estado" value="<?= (int)$c['estado_activo'] ? 0 : 1 ?>">
                                <button type="submit"
                                        class="btn btn-sm <?= (int)$c['estado_activo'] ? 'btn-outline-warning' : 'btn-outline-success' ?> btn-confirm"
                                        data-confirm="<?= (int)$c['estado_activo'] ? '¿Despublicar este curso?' : '¿Publicar este curso?' ?>"
                                        style="border-radius:8px;" title="<?= (int)$c['estado_activo'] ? 'Despublicar' : 'Publicar' ?>">
                                    <i class="fas fa-<?= (int)$c['estado_activo'] ? 'eye-slash' : 'eye' ?>"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Paginación -->
<?php if ($total_paginas > 1): ?>
<div class="d-flex justify-content-center mt-4">
    <ul class="pagination pagination-sm">
        <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
        <li class="page-item <?= $p === $pagina ? 'active' : '' ?>">
            <a class="page-link" href="?p=<?= $p ?>&q=<?= urlencode($busqueda) ?>&cat=<?= $filtro_cat ?>&est=<?= $filtro_est ?>">
                <?= $p ?>
            </a>
        </li>
        <?php endfor; ?>
    </ul>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
