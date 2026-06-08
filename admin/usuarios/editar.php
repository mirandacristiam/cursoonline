<?php
// /admin/usuarios/editar.php
// ============================================================
// Editar Usuario — Panel Administrativo — EduTech Academy
// ============================================================

$page_title = 'Editar Usuario';
$page_script = '../assets/js/usuarios.js';
$page_css    = '../assets/css/usuarios.css';
require_once __DIR__ . '/../includes/header.php';

$id_target = (int)($_GET['id'] ?? 0);
if (!$id_target) {
    header("Location: index.php");
    exit();
}

$msg_ok = $msg_err = '';

// ── Helfer: SP query ──────────────────────────────────────
function sp_query($pdo, $sp_name, $params = []) {
    try {
        $placeholders = [];
        foreach ($params as $k => $v) {
            $placeholders[] = ':' . $k;
        }
        $sql = 'CALL ' . $sp_name . '(' . implode(',', $placeholders) . ')';
        $prev = $pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, $prev);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error en $sp_name: " . $e->getMessage());
        return [];
    }
}
function sp_query_one($pdo, $sp_name, $params = []) {
    $rows = sp_query($pdo, $sp_name, $params);
    return $rows[0] ?? [];
}

// Cargar datos actuales
$usuario = sp_query_one($pdo, 'sp_admin_obtener_usuario', ['p_id_usuario' => $id_target]);
if (!$usuario) {
    header("Location: index.php?err=not_found");
    exit();
}

$form = $usuario; // copia inicial
$puede_modificar = ($id_target !== $id_usuario);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validar_token_csrf($pdo, $token)) {
        $msg_err = 'Token de seguridad inválido.';
    } else {
        $form['primer_nombre']         = limpiar_entrada($_POST['primer_nombre'] ?? '');
        $form['segundo_nombre']        = limpiar_entrada($_POST['segundo_nombre'] ?? '');
        $form['primer_apellido']       = limpiar_entrada($_POST['primer_apellido'] ?? '');
        $form['segundo_apellido']      = limpiar_entrada($_POST['segundo_apellido'] ?? '');
        $form['correo_electronico']    = strtolower(limpiar_entrada($_POST['correo_electronico'] ?? ''));
        $form['numero_telefono']       = limpiar_entrada($_POST['numero_telefono'] ?? '');
        $form['id_rol_fk']             = (int)($_POST['id_rol_fk'] ?? ROL_ESTUDIANTE);
        $form['tipo_documento_identidad'] = limpiar_entrada($_POST['tipo_documento_identidad'] ?? '');
        $form['numero_documento_identidad'] = limpiar_entrada($_POST['numero_documento_identidad'] ?? '');
        $form['fecha_nacimiento']      = limpiar_entrada($_POST['fecha_nacimiento'] ?? '');
        $form['ciudad_residencia']     = limpiar_entrada($_POST['ciudad_residencia'] ?? '');
        $form['departamento_residencia'] = limpiar_entrada($_POST['departamento_residencia'] ?? '');
        $form['pais_residencia']       = limpiar_entrada($_POST['pais_residencia'] ?? 'Colombia');
        $form['estado_activo']         = (int)($_POST['estado_activo'] ?? 1);
        $password                      = $_POST['password'] ?? '';
        $password_confirm              = $_POST['password_confirm'] ?? '';

        if (empty($form['primer_nombre']) || empty($form['primer_apellido']) || empty($form['correo_electronico'])) {
            $msg_err = 'Los campos marcados con * son obligatorios.';
        } elseif (!filter_var($form['correo_electronico'], FILTER_VALIDATE_EMAIL)) {
            $msg_err = 'El correo electrónico no es válido.';
        } elseif (!empty($password) && $password !== $password_confirm) {
            $msg_err = 'Las contraseñas no coinciden.';
        } elseif (!empty($password) && strlen($password) < 8) {
            $msg_err = 'La contraseña debe tener mínimo 8 caracteres.';
        } else {
            // Verificar correo duplicado (excluyendo el usuario actual)
            $chk = $pdo->prepare("SELECT id_usuario_pk FROM usuarios WHERE correo_electronico = ? AND id_usuario_pk != ?");
            $chk->execute([$form['correo_electronico'], $id_target]);
            if ($chk->rowCount() > 0) {
                $msg_err = 'El correo electrónico ya está registrado por otro usuario.';
            } else {
                try {
                    $hash = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;

                    // Llamar al SP
                    $stmt = $pdo->prepare("CALL sp_admin_guardar_usuario(
                        :p_id_usuario, :p_primer_nombre, :p_segundo_nombre, :p_primer_apellido,
                        :p_segundo_apellido, :p_correo_electronico, :p_contrasena_hash,
                        :p_numero_telefono, :p_foto_perfil, :p_id_rol_fk,
                        :p_tipo_documento, :p_numero_documento, :p_fecha_nacimiento,
                        :p_ciudad, :p_departamento, :p_pais,
                        :p_estado_activo, :p_modificado_por, @p_id_nuevo
                    )");
                    $stmt->execute([
                        ':p_id_usuario'         => $id_target,
                        ':p_primer_nombre'      => $form['primer_nombre'],
                        ':p_segundo_nombre'     => $form['segundo_nombre'] ?: null,
                        ':p_primer_apellido'    => $form['primer_apellido'],
                        ':p_segundo_apellido'   => $form['segundo_apellido'] ?: null,
                        ':p_correo_electronico' => $form['correo_electronico'],
                        ':p_contrasena_hash'    => $hash,
                        ':p_numero_telefono'    => $form['numero_telefono'] ?: null,
                        ':p_foto_perfil'        => $form['foto_perfil'] ?? null,
                        ':p_id_rol_fk'          => $form['id_rol_fk'],
                        ':p_tipo_documento'     => $form['tipo_documento_identidad'] ?: null,
                        ':p_numero_documento'   => $form['numero_documento_identidad'] ?: null,
                        ':p_fecha_nacimiento'   => $form['fecha_nacimiento'] ?: null,
                        ':p_ciudad'             => $form['ciudad_residencia'] ?: null,
                        ':p_departamento'       => $form['departamento_residencia'] ?: null,
                        ':p_pais'               => $form['pais_residencia'] ?: null,
                        ':p_estado_activo'      => $form['estado_activo'],
                        ':p_modificado_por'     => $id_usuario,
                    ]);

                    header("Location: ver.php?id=$id_target&updated=1");
                    exit();
                } catch (PDOException $e) {
                    error_log('[ADMIN EDITAR USER] ' . $e->getMessage());
                    $msg_err = 'Error al actualizar el usuario. Intente nuevamente.';
                }
            }
        }
    }
}
?>

<div class="page-header">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="index.php">Usuarios</a></li>
            <li class="breadcrumb-item"><a href="ver.php?id=<?= $id_target ?>"><?= sanitizar_html($form['primer_nombre'] . ' ' . $form['primer_apellido']) ?></a></li>
            <li class="breadcrumb-item active">Editar</li>
        </ol>
    </nav>
    <h1><i class="fas fa-user-edit me-2 text-danger"></i>Editar Usuario</h1>
</div>

<?php if ($msg_err): ?>
<div class="alert alert-danger rounded-3 mb-4"><i class="fas fa-exclamation-circle me-2"></i><?= sanitizar_html($msg_err) ?></div>
<?php endif; ?>
<?php if ($msg_ok): ?>
<div class="alert alert-success auto-dismiss rounded-3 mb-4"><i class="fas fa-check-circle me-2"></i><?= sanitizar_html($msg_ok) ?></div>
<?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="fas fa-id-card me-2"></i>Datos del Usuario</span>
            </div>
            <div class="admin-card-body">
                <form method="POST" id="formEditarUsuario">
                    <?php imprimir_campo_csrf($pdo, 'editar_usuario'); ?>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Primer Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="primer_nombre"
                                   value="<?= sanitizar_html($form['primer_nombre']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Segundo Nombre</label>
                            <input type="text" class="form-control form-control-sm" name="segundo_nombre"
                                   value="<?= sanitizar_html($form['segundo_nombre'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Primer Apellido <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="primer_apellido"
                                   value="<?= sanitizar_html($form['primer_apellido']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Segundo Apellido</label>
                            <input type="text" class="form-control form-control-sm" name="segundo_apellido"
                                   value="<?= sanitizar_html($form['segundo_apellido'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Correo Electrónico <span class="text-danger">*</span></label>
                            <input type="email" class="form-control form-control-sm" name="correo_electronico"
                                   value="<?= sanitizar_html($form['correo_electronico']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Teléfono</label>
                            <input type="text" class="form-control form-control-sm" name="numero_telefono"
                                   value="<?= sanitizar_html($form['numero_telefono'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Rol</label>
                            <select class="form-select form-select-sm" name="id_rol_fk" <?= !$puede_modificar ? 'disabled' : '' ?>>
                                <option value="<?= ROL_ESTUDIANTE ?>" <?= (int)$form['id_rol_fk'] === ROL_ESTUDIANTE ? 'selected' : '' ?>>Estudiante</option>
                                <option value="<?= ROL_PROFESOR ?>"   <?= (int)$form['id_rol_fk'] === ROL_PROFESOR   ? 'selected' : '' ?>>Profesor</option>
                                <option value="<?= ROL_ADMIN_TOTAL ?>" <?= (int)$form['id_rol_fk'] === ROL_ADMIN_TOTAL ? 'selected' : '' ?>>Administrador</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Estado</label>
                            <select class="form-select form-select-sm" name="estado_activo">
                                <option value="1" <?= (int)$form['estado_activo'] === 1 ? 'selected' : '' ?>>Activo</option>
                                <option value="0" <?= (int)$form['estado_activo'] === 0 ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tipo Documento</label>
                            <select class="form-select form-select-sm" name="tipo_documento_identidad">
                                <option value="">—</option>
                                <option value="CC" <?= ($form['tipo_documento_identidad'] ?? '') === 'CC' ? 'selected' : '' ?>>Cédula Ciudadanía</option>
                                <option value="CE" <?= ($form['tipo_documento_identidad'] ?? '') === 'CE' ? 'selected' : '' ?>>Cédula Extranjería</option>
                                <option value="TI" <?= ($form['tipo_documento_identidad'] ?? '') === 'TI' ? 'selected' : '' ?>>Tarjeta Identidad</option>
                                <option value="PA" <?= ($form['tipo_documento_identidad'] ?? '') === 'PA' ? 'selected' : '' ?>>Pasaporte</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">N° Documento</label>
                            <input type="text" class="form-control form-control-sm" name="numero_documento_identidad"
                                   value="<?= sanitizar_html($form['numero_documento_identidad'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Fecha Nacimiento</label>
                            <input type="date" class="form-control form-control-sm" name="fecha_nacimiento"
                                   value="<?= $form['fecha_nacimiento'] ?? '' ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">País</label>
                            <input type="text" class="form-control form-control-sm" name="pais_residencia"
                                   value="<?= sanitizar_html($form['pais_residencia'] ?? 'Colombia') ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Ciudad</label>
                            <input type="text" class="form-control form-control-sm" name="ciudad_residencia"
                                   value="<?= sanitizar_html($form['ciudad_residencia'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Departamento</label>
                            <input type="text" class="form-control form-control-sm" name="departamento_residencia"
                                   value="<?= sanitizar_html($form['departamento_residencia'] ?? '') ?>">
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="fw-bold mb-3"><i class="fas fa-lock me-2"></i>Cambiar Contraseña <span class="text-muted fw-normal small">(dejar vacío para mantener la actual)</span></h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nueva Contraseña</label>
                            <input type="password" class="form-control form-control-sm" name="password" minlength="8"
                                   placeholder="Mínimo 8 caracteres"
                                   autocomplete="new-password">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Confirmar Contraseña</label>
                            <input type="password" class="form-control form-control-sm" name="password_confirm"
                                   placeholder="Repite la contraseña"
                                   autocomplete="new-password">
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end border-top pt-3">
                        <a href="ver.php?id=<?= $id_target ?>" class="btn btn-outline-secondary btn-sm rounded-3">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary btn-sm rounded-3 px-4">
                            <i class="fas fa-save me-1"></i>Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
