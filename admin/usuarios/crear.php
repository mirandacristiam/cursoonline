<?php
// /admin/usuarios/crear.php
// ============================================================
// Crear Nuevo Usuario — Panel Admin — EduTech Academy
// ============================================================

$page_title = 'Crear Usuario';
$page_css    = '../assets/css/usuarios.css';
require_once __DIR__ . '/../includes/header.php';

$msg_ok = $msg_err = '';
$form = ['primer_nombre' => '', 'segundo_nombre' => '', 'primer_apellido' => '',
         'segundo_apellido' => '', 'correo_electronico' => '', 'telefono' => '',
         'id_rol_fk' => ROL_ESTUDIANTE, 'estado_activo' => 1];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validar_token_csrf($pdo, $token)) {
        $msg_err = 'Token de seguridad inválido.';
    } else {
        $form['primer_nombre']    = limpiar_entrada($_POST['primer_nombre']    ?? '');
        $form['segundo_nombre']   = limpiar_entrada($_POST['segundo_nombre']   ?? '');
        $form['primer_apellido']  = limpiar_entrada($_POST['primer_apellido']  ?? '');
        $form['segundo_apellido'] = limpiar_entrada($_POST['segundo_apellido'] ?? '');
        $form['correo_electronico'] = strtolower(limpiar_entrada($_POST['correo_electronico'] ?? ''));
        $form['telefono']         = limpiar_entrada($_POST['telefono']         ?? '');
        $form['id_rol_fk']        = (int)($_POST['id_rol_fk']                 ?? ROL_ESTUDIANTE);
        $form['estado_activo']    = (int)($_POST['estado_activo']              ?? 1);
        $password                 = $_POST['password']                         ?? '';
        $password_confirm         = $_POST['password_confirm']                 ?? '';

        if (empty($form['primer_nombre']) || empty($form['primer_apellido']) || empty($form['correo_electronico']) || empty($password)) {
            $msg_err = 'Los campos marcados con * son obligatorios.';
        } elseif (!filter_var($form['correo_electronico'], FILTER_VALIDATE_EMAIL)) {
            $msg_err = 'El correo electrónico no es válido.';
        } elseif ($password !== $password_confirm) {
            $msg_err = 'Las contraseñas no coinciden.';
        } elseif (strlen($password) < 8) {
            $msg_err = 'La contraseña debe tener mínimo 8 caracteres.';
        } else {
            // Verificar correo duplicado
            $chk = $pdo->prepare("SELECT id_usuario_pk FROM usuarios WHERE correo_electronico = ?");
            $chk->execute([$form['correo_electronico']]);
            if ($chk->rowCount() > 0) {
                $msg_err = 'El correo electrónico ya está registrado en el sistema.';
            } else {
                try {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt_ins = $pdo->prepare("
                        INSERT INTO usuarios
                        (primer_nombre, segundo_nombre, primer_apellido, segundo_apellido,
                         correo_electronico, contrasena_hash, numero_telefono, id_rol_fk,
                         estado_activo, fecha_creacion)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt_ins->execute([
                        $form['primer_nombre'], $form['segundo_nombre'] ?: null,
                        $form['primer_apellido'], $form['segundo_apellido'] ?: null,
                        $form['correo_electronico'], $hash,
                        $form['telefono'] ?: null, $form['id_rol_fk'], $form['estado_activo']
                    ]);
                    header("Location: index.php?msg=creado");
                    exit();
                } catch (PDOException $e) {
                    error_log('[ADMIN CREAR USER] ' . $e->getMessage());
                    $msg_err = 'Error al crear el usuario. Intente nuevamente.';
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
            <li class="breadcrumb-item active">Crear Usuario</li>
        </ol>
    </nav>
    <h1><i class="fas fa-user-plus me-2 text-danger"></i>Crear Nuevo Usuario</h1>
    <p>Agrega un nuevo usuario al sistema con su rol y credenciales de acceso.</p>
</div>

<?php if ($msg_err): ?><div class="alert alert-danger rounded-3 mb-4"><i class="fas fa-exclamation-circle me-2"></i><?= sanitizar_html($msg_err) ?></div><?php endif; ?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="fas fa-id-card me-2"></i>Datos del Nuevo Usuario</span>
            </div>
            <div class="admin-card-body">
                <form method="POST" id="formCrearUsuario">
                    <?php imprimir_campo_csrf($pdo, 'crear_usuario'); ?>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Primer Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="primer_nombre"
                                   value="<?= sanitizar_html($form['primer_nombre']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Segundo Nombre</label>
                            <input type="text" class="form-control form-control-sm" name="segundo_nombre"
                                   value="<?= sanitizar_html($form['segundo_nombre']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Primer Apellido <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="primer_apellido"
                                   value="<?= sanitizar_html($form['primer_apellido']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Segundo Apellido</label>
                            <input type="text" class="form-control form-control-sm" name="segundo_apellido"
                                   value="<?= sanitizar_html($form['segundo_apellido']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Correo Electrónico <span class="text-danger">*</span></label>
                            <input type="email" class="form-control form-control-sm" name="correo_electronico"
                                   value="<?= sanitizar_html($form['correo_electronico']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Teléfono</label>
                            <input type="tel" class="form-control form-control-sm" name="telefono"
                                   value="<?= sanitizar_html($form['telefono']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Contraseña <span class="text-danger">*</span></label>
                            <input type="password" class="form-control form-control-sm" name="password"
                                   minlength="8" required placeholder="Mínimo 8 caracteres">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Confirmar Contraseña <span class="text-danger">*</span></label>
                            <input type="password" class="form-control form-control-sm" name="password_confirm"
                                   minlength="8" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Rol <span class="text-danger">*</span></label>
                            <select name="id_rol_fk" class="form-select form-select-sm" required>
                                <option value="<?= ROL_ESTUDIANTE ?>"  <?= $form['id_rol_fk'] === ROL_ESTUDIANTE  ? 'selected':'' ?>>Estudiante</option>
                                <option value="<?= ROL_PROFESOR ?>"    <?= $form['id_rol_fk'] === ROL_PROFESOR    ? 'selected':'' ?>>Docente / Profesor</option>
                                <option value="<?= ROL_ADMIN_TOTAL ?>" <?= $form['id_rol_fk'] === ROL_ADMIN_TOTAL ? 'selected':'' ?>>Administrador Total</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Estado Inicial</label>
                            <select name="estado_activo" class="form-select form-select-sm">
                                <option value="1" <?= $form['estado_activo'] ? 'selected' : '' ?>>Activo</option>
                                <option value="0" <?= !$form['estado_activo'] ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn-admin-primary">
                            <i class="fas fa-save me-1"></i> Crear Usuario
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
