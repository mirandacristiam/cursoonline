<?php
// /cursoonline/teacher/perfil.php
// ============================================================
// Perfil del Profesor — EduTech Academy
// ============================================================

$page_title = 'Mi Perfil';
require_once 'includes/header.php';
require_once '../includes/csrf.php';

$msg_ok = $msg_err = '';

// ── POST: Actualizar perfil ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = limpiar_entrada($_POST['accion'] ?? '');
    $token  = $_POST['csrf_token'] ?? '';

    if (!validar_token_csrf($pdo, $token)) {
        $msg_err = 'Token de seguridad inválido. Recarga la página.';
    } elseif ($accion === 'actualizar_perfil') {
        $primer_nombre    = limpiar_entrada($_POST['primer_nombre']    ?? '');
        $segundo_nombre   = limpiar_entrada($_POST['segundo_nombre']   ?? '');
        $primer_apellido  = limpiar_entrada($_POST['primer_apellido']  ?? '');
        $segundo_apellido = limpiar_entrada($_POST['segundo_apellido'] ?? '');
        $telefono         = limpiar_entrada($_POST['telefono']         ?? '');

        if (empty($primer_nombre) || empty($primer_apellido)) {
            $msg_err = 'El nombre y apellido son obligatorios.';
        } else {
            try {
                $stmt_up = $pdo->prepare("
                    UPDATE usuarios
                    SET primer_nombre    = :pn,
                        segundo_nombre   = :sn,
                        primer_apellido  = :pa,
                        segundo_apellido = :sa,
                        telefono         = :tel,
                        fecha_actualizacion = NOW()
                    WHERE id_usuario_pk = :id
                ");
                $stmt_up->execute([
                    ':pn'  => $primer_nombre,
                    ':sn'  => $segundo_nombre ?: null,
                    ':pa'  => $primer_apellido,
                    ':sa'  => $segundo_apellido ?: null,
                    ':tel' => $telefono ?: null,
                    ':id'  => $id_usuario
                ]);
                $_SESSION['nombre'] = $primer_nombre . ' ' . $primer_apellido;
                $msg_ok = 'Perfil actualizado correctamente.';
                // Recargar datos del profesor
                $stmt_prof->execute([':id' => $id_usuario]);
                $profesor = $stmt_prof->fetch();
            } catch (PDOException $e) {
                $msg_err = 'Error al actualizar el perfil.';
            }
        }
    } elseif ($accion === 'cambiar_password') {
        $pass_actual  = $_POST['password_actual']  ?? '';
        $pass_nueva   = $_POST['password_nueva']   ?? '';
        $pass_confirm = $_POST['password_confirm'] ?? '';

        if (empty($pass_actual) || empty($pass_nueva) || empty($pass_confirm)) {
            $msg_err = 'Todos los campos de contraseña son obligatorios.';
        } elseif ($pass_nueva !== $pass_confirm) {
            $msg_err = 'Las contraseñas nuevas no coinciden.';
        } elseif (strlen($pass_nueva) < 8) {
            $msg_err = 'La contraseña nueva debe tener al menos 8 caracteres.';
        } else {
            $stmt_hash = $pdo->prepare("SELECT contrasena_hash FROM usuarios WHERE id_usuario_pk = ?");
            $stmt_hash->execute([$id_usuario]);
            $hash_actual = $stmt_hash->fetchColumn();

            if (!password_verify($pass_actual, $hash_actual)) {
                $msg_err = 'La contraseña actual es incorrecta.';
            } else {
                $nuevo_hash = password_hash($pass_nueva, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE usuarios SET contrasena_hash = ? WHERE id_usuario_pk = ?")->execute([$nuevo_hash, $id_usuario]);
                $msg_ok = 'Contraseña actualizada exitosamente.';
            }
        }
    }
}
?>

<!-- ── Page Header ─────────────────────────────────────────── -->
<div class="page-header">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Inicio</a></li>
            <li class="breadcrumb-item active">Mi Perfil</li>
        </ol>
    </nav>
    <h1><i class="fas fa-user-tie me-2 text-success"></i>Mi Perfil Docente</h1>
    <p>Actualiza tu información personal y configura tu contraseña.</p>
</div>

<?php if ($msg_ok): ?>
    <div class="alert alert-success rounded-3 mb-4"><i class="fas fa-check-circle me-2"></i><?= sanitizar_html($msg_ok) ?></div>
<?php endif; ?>
<?php if ($msg_err): ?>
    <div class="alert alert-danger rounded-3 mb-4"><i class="fas fa-exclamation-circle me-2"></i><?= sanitizar_html($msg_err) ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- ── Avatar y resumen ────────────────────────────────── -->
    <div class="col-lg-4">
        <div class="teacher-card text-center p-4">
            <div style="width:90px;height:90px;border-radius:50%;background:linear-gradient(135deg,var(--teacher-primary),var(--teacher-secondary));color:white;font-size:2rem;font-weight:700;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                <?= strtoupper(substr($profesor['primer_nombre'], 0, 1) . substr($profesor['primer_apellido'], 0, 1)) ?>
            </div>
            <h5 class="fw-bold mb-1"><?= sanitizar_html($profesor['primer_nombre'] . ' ' . $profesor['primer_apellido']) ?></h5>
            <p class="text-muted small mb-2"><?= sanitizar_html($profesor['correo_electronico']) ?></p>
            <span class="badge bg-success-subtle text-success rounded-pill px-3 py-2">
                <i class="fas fa-chalkboard-teacher me-1"></i> Docente
            </span>
            <hr class="my-3">
            <div class="text-muted small text-start">
                <div class="mb-2"><i class="fas fa-calendar-alt me-2 text-success"></i>
                    Miembro desde: <?= isset($profesor['fecha_registro']) ? date('d/m/Y', strtotime($profesor['fecha_registro'])) : '—' ?>
                </div>
                <div class="mb-2"><i class="fas fa-clock me-2 text-success"></i>
                    Último acceso: <?= isset($profesor['ultimo_acceso']) && $profesor['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($profesor['ultimo_acceso'])) : '—' ?>
                </div>
                <div><i class="fas fa-phone me-2 text-success"></i>
                    Teléfono: <?= sanitizar_html($profesor['telefono'] ?? '—') ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Formularios ─────────────────────────────────────── -->
    <div class="col-lg-8">
        <!-- Datos personales -->
        <div class="teacher-card mb-4">
            <div class="card-header-custom">
                <h5><i class="fas fa-id-card"></i> Datos Personales</h5>
            </div>
            <div class="card-body-custom">
                <form method="POST" id="formPerfil">
                    <?php imprimir_campo_csrf($pdo, 'perfil'); ?>
                    <input type="hidden" name="accion" value="actualizar_perfil">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Primer Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="primer_nombre"
                                   value="<?= sanitizar_html($profesor['primer_nombre']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Segundo Nombre</label>
                            <input type="text" class="form-control form-control-sm" name="segundo_nombre"
                                   value="<?= sanitizar_html($profesor['segundo_nombre'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Primer Apellido <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="primer_apellido"
                                   value="<?= sanitizar_html($profesor['primer_apellido']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Segundo Apellido</label>
                            <input type="text" class="form-control form-control-sm" name="segundo_apellido"
                                   value="<?= sanitizar_html($profesor['segundo_apellido'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Correo Electrónico</label>
                            <input type="email" class="form-control form-control-sm"
                                   value="<?= sanitizar_html($profesor['correo_electronico']) ?>"
                                   disabled title="El correo no se puede cambiar desde aquí.">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Teléfono</label>
                            <input type="tel" class="form-control form-control-sm" name="telefono"
                                   value="<?= sanitizar_html($profesor['telefono'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn-teacher-primary">
                                <i class="fas fa-save me-1"></i> Guardar Cambios
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Cambiar contraseña -->
        <div class="teacher-card">
            <div class="card-header-custom">
                <h5><i class="fas fa-lock"></i> Cambiar Contraseña</h5>
            </div>
            <div class="card-body-custom">
                <form method="POST" id="formPassword">
                    <?php imprimir_campo_csrf($pdo, 'cambiar_pass'); ?>
                    <input type="hidden" name="accion" value="cambiar_password">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Contraseña Actual <span class="text-danger">*</span></label>
                            <input type="password" class="form-control form-control-sm" name="password_actual" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nueva Contraseña <span class="text-danger">*</span></label>
                            <input type="password" class="form-control form-control-sm" name="password_nueva"
                                   minlength="8" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Confirmar Nueva Contraseña <span class="text-danger">*</span></label>
                            <input type="password" class="form-control form-control-sm" name="password_confirm"
                                   minlength="8" required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn-teacher-primary">
                                <i class="fas fa-key me-1"></i> Actualizar Contraseña
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
