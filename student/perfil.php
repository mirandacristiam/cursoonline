<?php
// /cursoonline/student/perfil.php
// ============================================================
// Perfil de Usuario y Cambio de Contraseña del Estudiante
// ============================================================

$page_title = 'Mi Perfil';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../../includes/csrf.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 fw-bold text-primary mb-1">Mi Perfil Académico</h1>
        <p class="text-muted m-0">Gestiona tus datos personales de contacto, documento de identidad y claves de acceso.</p>
    </div>
</div>

<div class="row g-4">
    <!-- Formulario de Información Personal -->
    <div class="col-lg-7">
        <div class="card-custom">
            <div class="card-header-custom"><i class="fas fa-id-card me-2"></i>Información Personal</div>
            <div class="card-body-custom">
                <!-- Alertas de respuesta AJAX -->
                <div class="alert alert-success alert-custom" id="alert-perfil-success" role="alert"></div>
                <div class="alert alert-danger alert-custom" id="alert-perfil-error" role="alert"></div>

                <form id="perfilForm" autocomplete="off">
                    <!-- Token CSRF -->
                    <?php imprimir_campo_csrf($pdo, 'perfil'); ?>
                    <input type="hidden" name="accion" value="actualizar_perfil">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="nombres" class="form-label small fw-bold text-muted">Nombres *</label>
                            <input type="text" class="form-control" id="nombres" name="nombres" value="<?= sanitizar_html($estudiante['primer_nombre']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="apellidos" class="form-label small fw-bold text-muted">Apellidos *</label>
                            <input type="text" class="form-control" id="apellidos" name="apellidos" value="<?= sanitizar_html($estudiante['primer_apellido']) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="correo" class="form-label small fw-bold text-muted">Correo Electrónico (No editable)</label>
                        <input type="email" class="form-control bg-light" id="correo" value="<?= sanitizar_html($estudiante['correo_electronico']) ?>" disabled>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="documento" class="form-label small fw-bold text-muted">Documento de Identidad</label>
                            <input type="text" class="form-control" id="documento" name="documento" value="<?= sanitizar_html($estudiante['numero_documento_identidad']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="telefono" class="form-label small fw-bold text-muted">Teléfono Móvil</label>
                            <input type="text" class="form-control" id="telefono" name="telefono" value="<?= sanitizar_html($estudiante['numero_telefono']) ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="ciudad" class="form-label small fw-bold text-muted">Ciudad de Residencia</label>
                        <input type="text" class="form-control" id="ciudad" name="ciudad" value="<?= sanitizar_html($estudiante['ciudad_residencia']) ?>">
                    </div>

                    <button type="submit" class="btn btn-primary rounded-pill shadow-sm" id="btnGuardarPerfil">Guardar Cambios</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Formulario de Cambio de Contraseña -->
    <div class="col-lg-5">
        <div class="card-custom">
            <div class="card-header-custom"><i class="fas fa-key me-2"></i>Seguridad y Contraseña</div>
            <div class="card-body-custom">
                <!-- Alertas de respuesta AJAX -->
                <div class="alert alert-success alert-custom" id="alert-pass-success" role="alert"></div>
                <div class="alert alert-danger alert-custom" id="alert-pass-error" role="alert"></div>

                <form id="passwordForm" autocomplete="off">
                    <!-- Token CSRF -->
                    <?php imprimir_campo_csrf($pdo, 'password'); ?>
                    <input type="hidden" name="accion" value="cambiar_password">

                    <div class="mb-3">
                        <label for="clave_actual" class="form-label small fw-bold text-muted">Contraseña Actual *</label>
                        <input type="password" class="form-control" id="clave_actual" name="clave_actual" required>
                    </div>

                    <div class="mb-3">
                        <label for="nueva_clave" class="form-label small fw-bold text-muted">Nueva Contraseña *</label>
                        <input type="password" class="form-control" id="nueva_clave" name="nueva_clave" minlength="8" required>
                    </div>

                    <div class="mb-4">
                        <label for="confirmar_clave" class="form-label small fw-bold text-muted">Confirmar Nueva Contraseña *</label>
                        <input type="password" class="form-control" id="confirmar_clave" name="confirmar_clave" minlength="8" required>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-danger rounded-pill shadow-sm" id="btnCambiarPass">Cambiar Contraseña</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
