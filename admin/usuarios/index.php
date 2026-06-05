<?php
// /admin/usuarios/index.php
// ============================================================
// Gestión de Usuarios — Panel Administrador — EduTech Academy
// ============================================================

$page_title = 'Gestión de Usuarios';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../cursoonline/includes/csrf.php';

$msg_ok = $msg_err = '';

// ── POST: Cambiar estado (activar/inactivar) ─────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = limpiar_entrada($_POST['accion'] ?? '');
    $token  = $_POST['csrf_token'] ?? '';

    if (!validar_token_csrf($pdo, $token)) {
        $msg_err = 'Token de seguridad inválido.';
    } elseif ($accion === 'toggle_estado') {
        $id_target   = (int)($_POST['id_usuario'] ?? 0);
        $nuevo_estado = (int)($_POST['nuevo_estado'] ?? 0);
        if ($id_target && $id_target !== $id_usuario) { // No puede cambiarse a sí mismo
            $pdo->prepare("UPDATE usuarios SET estado_activo = ?, fecha_actualizacion = NOW() WHERE id_usuario_pk = ?")
                ->execute([$nuevo_estado, $id_target]);
            $msg_ok = $nuevo_estado ? 'Usuario activado correctamente.' : 'Usuario desactivado correctamente.';
        } else {
            $msg_err = 'No puedes cambiar el estado de tu propio usuario.';
        }
    } elseif ($accion === 'cambiar_rol') {
        $id_target  = (int)($_POST['id_usuario'] ?? 0);
        $nuevo_rol  = (int)($_POST['nuevo_rol']  ?? 0);
        if ($id_target && $id_target !== $id_usuario && in_array($nuevo_rol, [ROL_ADMIN_TOTAL, ROL_PROFESOR, ROL_ESTUDIANTE])) {
            $pdo->prepare("UPDATE usuarios SET id_rol_fk = ?, fecha_actualizacion = NOW() WHERE id_usuario_pk = ?")
                ->execute([$nuevo_rol, $id_target]);
            $msg_ok = 'Rol actualizado correctamente.';
        } else {
            $msg_err = 'Operación no permitida.';
        }
    }
}

// ── Parámetros de búsqueda y filtro ─────────────────────────
$busqueda  = limpiar_entrada($_GET['q']    ?? '');
$filtro_rol = (int)($_GET['rol']           ?? 0);
$filtro_est = limpiar_entrada($_GET['est'] ?? 'todos');
$por_pagina = 15;
$pagina     = max(1, (int)($_GET['p'] ?? 1));
$offset     = ($pagina - 1) * $por_pagina;

// ── Consulta principal con filtros ───────────────────────────
$where  = ['1=1'];
$params = [];

if (!empty($busqueda)) {
    $where[] = "(u.primer_nombre LIKE :q OR u.primer_apellido LIKE :q OR u.correo_electronico LIKE :q)";
    $params[':q'] = "%{$busqueda}%";
}
if ($filtro_rol) {
    $where[] = "u.id_rol_fk = :rol";
    $params[':rol'] = $filtro_rol;
}
if ($filtro_est !== 'todos') {
    $where[] = "u.estado_activo = :est";
    $params[':est'] = $filtro_est === 'activo' ? 1 : 0;
}

$where_sql = implode(' AND ', $where);

// Contar total
$stmt_count = $pdo->prepare("SELECT COUNT(*) FROM usuarios u WHERE {$where_sql}");
$stmt_count->execute($params);
$total_registros = (int)$stmt_count->fetchColumn();
$total_paginas   = max(1, ceil($total_registros / $por_pagina));

// Obtener usuarios
$stmt_users = $pdo->prepare("
    SELECT u.id_usuario_pk, u.primer_nombre, u.segundo_nombre,
           u.primer_apellido, u.segundo_apellido,
           u.correo_electronico, u.id_rol_fk, r.nombre_rol,
           u.estado_activo, u.fecha_registro, u.ultimo_acceso, u.telefono
    FROM usuarios u
    INNER JOIN roles r ON r.id_rol_pk = u.id_rol_fk
    WHERE {$where_sql}
    ORDER BY u.fecha_registro DESC
    LIMIT :limit OFFSET :offset
");
foreach ($params as $key => $val) {
    $stmt_users->bindValue($key, $val);
}
$stmt_users->bindValue(':limit',  $por_pagina, PDO::PARAM_INT);
$stmt_users->bindValue(':offset', $offset,     PDO::PARAM_INT);
$stmt_users->execute();
$usuarios = $stmt_users->fetchAll();

// ── Estadísticas rápidas ─────────────────────────────────────
$stats = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN id_rol_fk = " . ROL_ADMIN_TOTAL . " THEN 1 ELSE 0 END) AS admins,
        SUM(CASE WHEN id_rol_fk = " . ROL_PROFESOR    . " THEN 1 ELSE 0 END) AS profesores,
        SUM(CASE WHEN id_rol_fk = " . ROL_ESTUDIANTE  . " THEN 1 ELSE 0 END) AS estudiantes,
        SUM(CASE WHEN estado_activo = 0 THEN 1 ELSE 0 END) AS inactivos
    FROM usuarios
")->fetch();
?>

<!-- ── Page Header ─────────────────────────────────────────── -->
<div class="page-header">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Usuarios</li>
        </ol>
    </nav>
    <h1><i class="fas fa-users-cog me-2 text-danger"></i>Gestión de Usuarios</h1>
    <p>Administra todos los usuarios del sistema: estudiantes, docentes y administradores.</p>
</div>

<!-- Alertas -->
<?php if ($msg_ok): ?><div class="alert alert-success auto-dismiss rounded-3 mb-3"><i class="fas fa-check-circle me-2"></i><?= sanitizar_html($msg_ok) ?></div><?php endif; ?>
<?php if ($msg_err): ?><div class="alert alert-danger rounded-3 mb-3"><i class="fas fa-exclamation-circle me-2"></i><?= sanitizar_html($msg_err) ?></div><?php endif; ?>

<!-- ── Mini Stats ─────────────────────────────────────────── -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="admin-stat-card">
            <div><p>Total Usuarios</p><h3><?= $stats['total'] ?></h3></div>
            <div class="admin-stat-icon icon-blue"><i class="fas fa-users"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="admin-stat-card">
            <div><p>Estudiantes</p><h3><?= $stats['estudiantes'] ?></h3></div>
            <div class="admin-stat-icon icon-green"><i class="fas fa-user-graduate"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="admin-stat-card">
            <div><p>Docentes</p><h3><?= $stats['profesores'] ?></h3></div>
            <div class="admin-stat-icon icon-blue"><i class="fas fa-chalkboard-teacher"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="admin-stat-card">
            <div><p>Inactivos</p><h3><?= $stats['inactivos'] ?></h3></div>
            <div class="admin-stat-icon icon-red"><i class="fas fa-user-slash"></i></div>
        </div>
    </div>
</div>

<!-- ── Tabla de Usuarios ──────────────────────────────────── -->
<div class="admin-card">
    <div class="admin-card-header">
        <span><i class="fas fa-list-ul me-2"></i>Lista de Usuarios (<?= $total_registros ?> registros)</span>
        <a href="crear.php" class="btn-admin-primary btn-sm"><i class="fas fa-plus"></i> Nuevo Usuario</a>
    </div>

    <!-- Filtros -->
    <div class="admin-card-body border-bottom py-3">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-end">
            <input type="text" class="form-control form-control-sm" name="q"
                   value="<?= sanitizar_html($busqueda) ?>"
                   placeholder="Buscar por nombre, apellido o correo..."
                   id="tableSearch" style="max-width:280px;">
            <select name="rol" class="form-select form-select-sm" style="max-width:160px;" onchange="this.form.submit()">
                <option value="0">Todos los roles</option>
                <option value="<?= ROL_ADMIN_TOTAL ?>"  <?= $filtro_rol === ROL_ADMIN_TOTAL  ? 'selected' : '' ?>>Administrador</option>
                <option value="<?= ROL_PROFESOR ?>"     <?= $filtro_rol === ROL_PROFESOR     ? 'selected' : '' ?>>Docente</option>
                <option value="<?= ROL_ESTUDIANTE ?>"   <?= $filtro_rol === ROL_ESTUDIANTE   ? 'selected' : '' ?>>Estudiante</option>
            </select>
            <select name="est" class="form-select form-select-sm" style="max-width:130px;" onchange="this.form.submit()">
                <option value="todos"   <?= $filtro_est === 'todos'    ? 'selected' : '' ?>>Todos</option>
                <option value="activo"  <?= $filtro_est === 'activo'   ? 'selected' : '' ?>>Activos</option>
                <option value="inactivo"<?= $filtro_est === 'inactivo' ? 'selected' : '' ?>>Inactivos</option>
            </select>
            <button type="submit" class="btn-admin-outline btn-sm"><i class="fas fa-search"></i></button>
            <?php if ($busqueda || $filtro_rol || $filtro_est !== 'todos'): ?>
                <a href="index.php" class="btn btn-outline-secondary btn-sm">✕ Limpiar</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabla -->
    <div class="table-responsive">
        <?php if (empty($usuarios)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-user-slash fa-2x mb-3 d-block opacity-50"></i>
                No se encontraron usuarios con los filtros seleccionados.
            </div>
        <?php else: ?>
        <table class="table-custom w-100">
            <thead>
                <tr>
                    <th class="ps-4">Usuario</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Registro</th>
                    <th>Último acceso</th>
                    <th class="text-center pe-4">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td class="ps-4">
                        <div class="d-flex align-items-center gap-2">
                            <div class="user-avatar">
                                <?= strtoupper(substr($u['primer_nombre'], 0, 1) . substr($u['primer_apellido'], 0, 1)) ?>
                            </div>
                            <div>
                                <div class="fw-semibold small">
                                    <?= sanitizar_html($u['primer_nombre'] . ' ' . $u['primer_apellido']) ?>
                                </div>
                                <div class="text-muted" style="font-size:0.75rem;">
                                    <?= sanitizar_html($u['correo_electronico']) ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php
                        $rol_map = [
                            ROL_ADMIN_TOTAL => ['label' => 'Admin',    'class' => 'admin'],
                            ROL_PROFESOR    => ['label' => 'Docente',  'class' => 'profesor'],
                            ROL_ESTUDIANTE  => ['label' => 'Estudiante', 'class' => 'estudiante'],
                        ];
                        $rol_data = $rol_map[$u['id_rol_fk']] ?? ['label' => 'Desconocido', 'class' => 'inactivo'];
                        ?>
                        <span class="badge-admin <?= $rol_data['class'] ?>"><?= $rol_data['label'] ?></span>
                    </td>
                    <td>
                        <span class="badge-admin <?= (int)$u['estado_activo'] ? 'activo' : 'inactivo' ?>">
                            <?= (int)$u['estado_activo'] ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td class="text-muted small">
                        <?= $u['fecha_registro'] ? date('d/m/Y', strtotime($u['fecha_registro'])) : '—' ?>
                    </td>
                    <td class="text-muted small">
                        <?= $u['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($u['ultimo_acceso'])) : 'Nunca' ?>
                    </td>
                    <td class="text-center pe-4">
                        <div class="d-flex gap-1 justify-content-center">
                            <!-- Ver / Editar -->
                            <a href="editar.php?id=<?= $u['id_usuario_pk'] ?>"
                               class="btn-admin-blue btn-sm"
                               data-bs-toggle="tooltip" title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <!-- Toggle estado -->
                            <?php if ((int)$u['id_usuario_pk'] !== $id_usuario): ?>
                            <form method="POST" style="display:inline;">
                                <?php imprimir_campo_csrf($pdo, 'toggle_est'); ?>
                                <input type="hidden" name="accion" value="toggle_estado">
                                <input type="hidden" name="id_usuario" value="<?= $u['id_usuario_pk'] ?>">
                                <input type="hidden" name="nuevo_estado" value="<?= (int)$u['estado_activo'] ? 0 : 1 ?>">
                                <button type="submit"
                                        class="btn btn-sm <?= (int)$u['estado_activo'] ? 'btn-outline-warning' : 'btn-outline-success' ?> btn-confirm"
                                        data-confirm="<?= (int)$u['estado_activo'] ? '¿Desactivar este usuario?' : '¿Activar este usuario?' ?>"
                                        data-bs-toggle="tooltip"
                                        title="<?= (int)$u['estado_activo'] ? 'Desactivar' : 'Activar' ?>"
                                        style="border-radius:8px;">
                                    <i class="fas fa-<?= (int)$u['estado_activo'] ? 'ban' : 'check' ?>"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <!-- Paginación -->
    <?php if ($total_paginas > 1): ?>
    <div class="admin-card-body border-top d-flex justify-content-between align-items-center">
        <span class="text-muted small">
            Mostrando <?= min($offset + 1, $total_registros) ?>–<?= min($offset + $por_pagina, $total_registros) ?>
            de <?= $total_registros ?> usuarios
        </span>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
                <li class="page-item <?= $p === $pagina ? 'active' : '' ?>">
                    <a class="page-link" href="?p=<?= $p ?>&q=<?= urlencode($busqueda) ?>&rol=<?= $filtro_rol ?>&est=<?= $filtro_est ?>">
                        <?= $p ?>
                    </a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
