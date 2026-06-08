<?php
// /cursoonline/admin/perfil.php
$page_title = 'Mi Perfil';
$page_css   = 'assets/css/perfil.css';
$page_script = 'assets/js/perfil.js';
require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1><i class="fas fa-user-shield me-2"></i>Mi Perfil</h1>
    <p>Información personal, foto de perfil y seguridad.</p>
</div>

<div class="row g-4">
    <div class="col-lg-8">

        <!-- Información Personal -->
        <div class="admin-card mb-4">
            <div class="admin-card-header"><i class="fas fa-id-card me-2"></i>Información Personal</div>
            <div class="admin-card-body p-4">
                <div class="alert alert-success alert-custom auto-dismiss" id="alert-perfil-success" style="display:none;"></div>
                <div class="alert alert-danger alert-custom" id="alert-perfil-error" style="display:none;"></div>

                <form id="perfilForm">
                    <?php imprimir_campo_csrf($pdo, 'perfil'); ?>
                    <input type="hidden" name="accion" value="actualizar_perfil">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Primer Nombre *</label>
                            <input type="text" class="form-control" name="primer_nombre" value="<?= sanitizar_html($admin_user['primer_nombre']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Segundo Nombre</label>
                            <input type="text" class="form-control" name="segundo_nombre" value="<?= sanitizar_html($admin_user['segundo_nombre'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Primer Apellido *</label>
                            <input type="text" class="form-control" name="primer_apellido" value="<?= sanitizar_html($admin_user['primer_apellido']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Segundo Apellido</label>
                            <input type="text" class="form-control" name="segundo_apellido" value="<?= sanitizar_html($admin_user['segundo_apellido'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Correo Electrónico (no editable)</label>
                        <input type="email" class="form-control bg-light" value="<?= sanitizar_html($admin_user['correo_electronico']) ?>" disabled>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Tipo de Documento</label>
                            <select class="form-select" name="tipo_documento">
                                <option value="">Seleccione...</option>
                                <?php foreach (['CC' => 'Cédula de Ciudadanía', 'CE' => 'Cédula de Extranjería', 'TI' => 'Tarjeta de Identidad', 'Pasaporte' => 'Pasaporte', 'NIT' => 'NIT', 'Otro' => 'Otro'] as $val => $label): ?>
                                <option value="<?= $val ?>" <?= ($admin_user['tipo_documento_identidad'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Número de Documento</label>
                            <input type="text" class="form-control" name="numero_documento" value="<?= sanitizar_html($admin_user['numero_documento_identidad'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Teléfono Móvil</label>
                            <input type="text" class="form-control" name="telefono" value="<?= sanitizar_html($admin_user['numero_telefono'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" name="fecha_nacimiento" value="<?= sanitizar_html($admin_user['fecha_nacimiento'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Ciudad de Residencia</label>
                            <input type="text" class="form-control" name="ciudad" value="<?= sanitizar_html($admin_user['ciudad_residencia'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Departamento</label>
                            <input type="text" class="form-control" name="departamento" value="<?= sanitizar_html($admin_user['departamento_residencia'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold">País</label>
                        <input type="text" class="form-control" name="pais" value="<?= sanitizar_html($admin_user['pais_residencia'] ?? 'Colombia') ?>">
                    </div>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Guardar Cambios</button>
                </form>
            </div>
        </div>

        <!-- Seguridad -->
        <div class="admin-card">
            <div class="admin-card-header"><i class="fas fa-key me-2"></i>Seguridad y Contraseña</div>
            <div class="admin-card-body p-4">
                <div class="alert alert-success alert-custom auto-dismiss" id="alert-pass-success" style="display:none;"></div>
                <div class="alert alert-danger alert-custom" id="alert-pass-error" style="display:none;"></div>

                <form id="passwordForm">
                    <?php imprimir_campo_csrf($pdo, 'password'); ?>
                    <input type="hidden" name="accion" value="cambiar_password">

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Contraseña Actual *</label>
                        <input type="password" class="form-control" name="clave_actual" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Nueva Contraseña *</label>
                        <input type="password" class="form-control" name="nueva_clave" minlength="8" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Confirmar Nueva Contraseña *</label>
                        <input type="password" class="form-control" name="confirmar_clave" minlength="8" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-danger rounded-pill px-4">Cambiar Contraseña</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Columna derecha -->
    <div class="col-lg-4">

        <!-- Foto de Perfil -->
        <div class="admin-card mb-4">
            <div class="admin-card-header"><i class="fas fa-camera me-2"></i>Foto de Perfil</div>
            <div class="admin-card-body p-4 text-center">
                <div class="alert alert-success alert-custom" style="display:none" id="alert-foto-success"></div>
                <div class="alert alert-danger alert-custom" style="display:none" id="alert-foto-error"></div>

                <div class="mb-3">
                    <img src="<?= $admin_user['foto_perfil'] ?: ADMIN_FOTO_URL . 'default-avatar.svg' ?>"
                         alt="Foto de Perfil" id="fotoPreview"
                         class="rounded-circle border border-4 border-danger shadow-sm"
                         style="width:150px;height:150px;object-fit:cover;">
                </div>

                <form id="fotoForm">
                    <?php imprimir_campo_csrf($pdo, 'foto'); ?>
                    <input type="hidden" name="accion" value="subir_foto">
                    <div class="mb-3">
                        <input type="file" class="form-control form-control-sm" id="fotoInput" name="foto" accept="image/jpeg,image/png,image/gif,image/webp">
                        <div class="form-text small">JPG, PNG, GIF o WebP. Máx. 10 MB.</div>
                    </div>
                    <button type="submit" class="btn btn-outline-primary rounded-pill btn-sm w-100" id="btnSubirFoto">
                        <i class="fas fa-upload me-1"></i>Subir Foto
                    </button>
                </form>

                <?php if (!empty($admin_user['foto_perfil'])): ?>
                <div class="mt-3">
                    <button type="button" class="btn btn-outline-danger rounded-pill btn-sm w-100" id="btnEliminarFoto"
                            data-csrf-token="<?= generar_token_csrf($pdo, 'foto') ?>">
                        <i class="fas fa-trash-alt me-1"></i>Eliminar Foto
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Resumen -->
        <div class="admin-card">
            <div class="admin-card-header"><i class="fas fa-info-circle me-2"></i>Resumen</div>
            <div class="admin-card-body p-4">
                <div class="text-center mb-3">
                    <div class="user-avatar fs-1 mx-auto mb-2" style="width:80px;height:80px;font-size:1.8rem;display:flex;align-items:center;justify-content:center;">
                        <?= strtoupper(substr($admin_user['primer_nombre'], 0, 1) . substr($admin_user['primer_apellido'], 0, 1)) ?>
                    </div>
                    <h5 class="fw-bold"><?= sanitizar_html($admin_user['primer_nombre'] . ' ' . $admin_user['primer_apellido']) ?></h5>
                    <span class="badge bg-danger">Administrador Total</span>
                </div>
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2"><strong>Correo:</strong> <?= sanitizar_html($admin_user['correo_electronico']) ?></li>
                    <li class="mb-2"><strong>Miembro desde:</strong> <?= date('d/m/Y', strtotime($admin_user['fecha_creacion'])) ?></li>
                    <li class="mb-0"><strong>Último acceso:</strong> <?= $admin_user['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($admin_user['ultimo_acceso'])) : 'Nunca' ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
