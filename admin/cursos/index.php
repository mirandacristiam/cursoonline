<?php
// /cursoonline/admin/cursos/index.php
// ============================================================
// Gestión de Cursos — Panel Administrativo
// PHP: consultas vía SP, filtros, paginación
// ============================================================

$page_title = 'Gestión de Cursos';
$page_script = '../assets/js/cursos.js';
$page_css    = '../assets/css/cursos.css';
require_once __DIR__ . '/../includes/header.php';

// ── Helper: ejecutar SP con OUT params ──────────────────
function sp_admin_cursos($pdo, $sp_name, $params = [], $has_out = false) {
    $placeholders = [];
    foreach ($params as $k => $v) {
        $placeholders[] = ':' . $k;
    }
    if ($has_out) {
        $sql = 'CALL ' . $sp_name . '(' . implode(',', $placeholders) . ', @_out)';
    } else {
        $sql = 'CALL ' . $sp_name . '(' . implode(',', $placeholders) . ')';
    }
    $emulate = $pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, $emulate);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    if ($has_out) {
        $total = (int)$pdo->query('SELECT @_out')->fetchColumn();
        return [$rows, $total];
    }
    return $rows;
}

// ── Helper para SP sin filas de retorno ─────────────────
function sp_exec($pdo, $sql, $params = []) {
    $emulate = $pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    $stmt = $pdo->prepare($sql);
    $r = $stmt->execute($params);
    $stmt->closeCursor();
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, $emulate);
    return $r;
}

$msg_ok  = limpiar_entrada($_GET['msg'] ?? '');
$msg_err = '';

// ── POST: Toggle estado ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = limpiar_entrada($_POST['accion'] ?? '');
    $token  = $_POST['csrf_token'] ?? '';
    if (!validar_token_csrf($pdo, $token)) {
        $msg_err = 'Token de seguridad inválido.';
    } elseif ($accion === 'toggle_curso') {
        $id_curso     = (int)($_POST['id_curso'] ?? 0);
        $nuevo_estado = (int)($_POST['nuevo_estado'] ?? 0);
        if ($id_curso) {
            sp_exec($pdo, 'CALL sp_admin_cambiar_estado_curso(:id, :est, :usr)', [
                ':id' => $id_curso, ':est' => $nuevo_estado, ':usr' => $id_usuario,
            ]);
            $msg_ok = $nuevo_estado ? 'Curso activado correctamente.' : 'Curso desactivado.';
        }
    } elseif ($accion === 'eliminar_curso') {
        $id_curso = (int)($_POST['id_curso'] ?? 0);
        if ($id_curso) {
            sp_exec($pdo, 'CALL sp_admin_eliminar_curso(:id, :usr)', [':id' => $id_curso, ':usr' => $id_usuario]);
            $msg_ok = 'Curso eliminado (desactivado).';
        }
    }
}

// ── Filtros ─────────────────────────────────────────────
$busqueda   = limpiar_entrada($_GET['q']   ?? '');
$filtro_cat = (int)($_GET['cat']           ?? 0);
$filtro_est = limpiar_entrada($_GET['est'] ?? 'todos');
$pagina     = max(1, (int)($_GET['p']      ?? 1));
$por_pagina = 12;

list($cursos, $total_cursos) = sp_admin_cursos($pdo, 'sp_admin_listar_cursos', [
    'p_busqueda'    => $busqueda,
    'p_id_categoria' => $filtro_cat,
    'p_estado'      => $filtro_est,
    'p_pagina'      => $pagina,
    'p_por_pagina'  => $por_pagina,
], true);

$total_paginas = max(1, ceil($total_cursos / $por_pagina));

// ── Categorías para el filtro ───────────────────────────
$categorias = sp_admin_cursos($pdo, 'sp_admin_listar_categorias');
?>
<!-- ─── HTML: Listado de Cursos ─────────────────────────────── -->

<div class="page-header d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <h1><i class="fas fa-graduation-cap me-2 text-danger"></i>Gestión de Cursos</h1>
        <p class="mb-0">Administra el catálogo, precios, docentes y estado de publicación.</p>
    </div>
    <a href="crear.php" class="btn-admin-primary btn-sm"><i class="fas fa-plus"></i> Nuevo Curso</a>
</div>

<?php if ($msg_ok === 'creado'): ?>
<div class="alert alert-success auto-dismiss rounded-3 mb-4"><i class="fas fa-check-circle me-2"></i>Curso creado exitosamente.</div>
<?php elseif ($msg_ok === 'actualizado'): ?>
<div class="alert alert-success auto-dismiss rounded-3 mb-4"><i class="fas fa-check-circle me-2"></i>Curso actualizado correctamente.</div>
<?php elseif ($msg_ok): ?>
<div class="alert alert-success auto-dismiss rounded-3 mb-4"><i class="fas fa-check-circle me-2"></i><?= sanitizar_html($msg_ok) ?></div>
<?php endif; ?>
<?php if ($msg_err): ?>
<div class="alert alert-danger rounded-3 mb-4"><i class="fas fa-exclamation-circle me-2"></i><?= sanitizar_html($msg_err) ?></div>
<?php endif; ?>

<!-- Filtros -->
<div class="admin-card mb-4">
    <div class="admin-card-header">
        <span><i class="fas fa-filter me-2"></i>Filtros &mdash; <?= $total_cursos ?> cursos</span>
    </div>
    <div class="admin-card-body">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
            <input type="text" class="form-control form-control-sm" name="q"
                   value="<?= sanitizar_html($busqueda) ?>"
                   placeholder="Buscar por nombre..." style="max-width:260px;">
            <select name="cat" class="form-select form-select-sm filter-auto" style="max-width:190px;">
                <option value="0">Todas las categorías</option>
                <?php foreach ($categorias as $cat): ?>
                <option value="<?= $cat['id_categoria_pk'] ?>" <?= $filtro_cat === (int)$cat['id_categoria_pk'] ? 'selected' : '' ?>>
                    <?= sanitizar_html($cat['nombre_categoria']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <select name="est" class="form-select form-select-sm filter-auto" style="max-width:130px;">
                <option value="todos"   <?= $filtro_est === 'todos'   ? 'selected' : '' ?>>Todos</option>
                <option value="activo"  <?= $filtro_est === 'activo'  ? 'selected' : '' ?>>Publicados</option>
                <option value="inactivo"<?= $filtro_est === 'inactivo' ? 'selected' : '' ?>>Despublicados</option>
            </select>
            <button type="submit" class="btn-admin-outline btn-sm"><i class="fas fa-search"></i></button>
        </form>
    </div>
</div>

<!-- Grid de cursos -->
<?php if (empty($cursos)): ?>
<div class="admin-card">
    <div class="admin-card-body text-center py-5 text-muted">
        <i class="fas fa-book-open fa-2x mb-3 d-block opacity-50"></i>
        No se encontraron cursos con los filtros aplicados.
    </div>
</div>
<?php else: ?>
<div class="row g-3">
    <?php foreach ($cursos as $c): ?>
    <div class="col-md-6 col-xl-4">
        <div class="admin-card h-100 mb-0 curso-card">
            <div class="card-top-bar p-3 pb-2" style="background:<?= sanitizar_html($c['color_categoria'] ?? '#2563EB') ?>1A;">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <span class="badge small" style="background:<?= sanitizar_html($c['color_categoria'] ?? '#2563EB') ?>20;color:<?= sanitizar_html($c['color_categoria'] ?? '#2563EB') ?>;">
                        <?= sanitizar_html($c['nombre_categoria'] ?? '—') ?>
                    </span>
                    <span class="badge-admin <?= (int)$c['estado_activo'] ? 'activo' : 'inactivo' ?>">
                        <?= (int)$c['estado_activo'] ? 'Publicado' : 'Oculto' ?>
                    </span>
                </div>
                <h6 class="fw-bold mt-2 mb-1" style="font-size:.9rem;"><?= sanitizar_html($c['titulo_curso']) ?></h6>
                <p class="text-muted mb-0" style="font-size:.78rem;line-height:1.4;">
                    <?= sanitizar_html(mb_strimwidth($c['resumen_corto'] ?? '', 0, 80, '...')) ?>
                </p>
            </div>
            <div class="card-body py-2 px-3">
                <div class="d-flex justify-content-between text-muted small mb-2">
                    <span><i class="fas fa-user-tie me-1"></i>
                        <?= isset($c['prof_nombre']) ? sanitizar_html($c['prof_nombre'] . ' ' . $c['prof_apellido']) : 'Sin docente' ?>
                    </span>
                    <span><i class="fas fa-users me-1"></i><?= (int)$c['total_inscripciones'] ?> est.</span>
                </div>
                <div class="d-flex justify-content-between align-items-center pt-1 border-top" style="border-color:var(--border-color);">
                    <span class="fw-bold" style="color:var(--admin-green);font-size:.9rem;">
                        <?= (float)$c['precio'] > 0 ? '$' . number_format((float)$c['precio'], 0, ',', '.') : 'Gratis' ?>
                    </span>
                    <div class="d-flex gap-1">
                        <a href="ver.php?id=<?= $c['id_curso_pk'] ?>" class="btn btn-outline-info btn-sm" style="border-radius:8px;" title="Ver detalle">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="editar.php?id=<?= $c['id_curso_pk'] ?>" class="btn btn-outline-primary btn-sm" style="border-radius:8px;" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form method="POST" style="display:inline;">
                            <?php imprimir_campo_csrf($pdo, 'c' . $c['id_curso_pk']); ?>
                            <input type="hidden" name="accion" value="toggle_curso">
                            <input type="hidden" name="id_curso" value="<?= $c['id_curso_pk'] ?>">
                            <input type="hidden" name="nuevo_estado" value="<?= (int)$c['estado_activo'] ? 0 : 1 ?>">
                            <button type="submit"
                                    class="btn btn-sm <?= (int)$c['estado_activo'] ? 'btn-outline-warning' : 'btn-outline-success' ?> btn-confirm-modal"
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
</div>
<?php endif; ?>

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
