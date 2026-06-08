<?php
// /cursoonline/student/perfil.php
// ============================================================
// Perfil de Usuario y Cambio de Contraseña del Estudiante
// ============================================================

$page_title = 'Mi Perfil';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/csrf.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 fw-bold text-primary mb-1">Mi Perfil Académico</h1>
        <p class="text-muted m-0">Gestiona tus datos personales, documento de identidad, foto de perfil y clave de acceso.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <!-- Formulario de Información Personal -->
        <div class="card-custom mb-4">
            <div class="card-header-custom"><i class="fas fa-id-card me-2"></i>Información Personal</div>
            <div class="card-body-custom">
                <div class="alert alert-success alert-custom" style="display:none" id="alert-perfil-success" role="alert"></div>
                <div class="alert alert-danger alert-custom" style="display:none" id="alert-perfil-error" role="alert"></div>

                <form id="perfilForm" autocomplete="off">
                    <?php imprimir_campo_csrf($pdo, 'perfil'); ?>
                    <input type="hidden" name="accion" value="actualizar_perfil">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="primer_nombre" class="form-label small fw-bold text-muted">Primer Nombre *</label>
                            <input type="text" class="form-control" id="primer_nombre" name="primer_nombre" value="<?= sanitizar_html($estudiante['primer_nombre']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="segundo_nombre" class="form-label small fw-bold text-muted">Segundo Nombre</label>
                            <input type="text" class="form-control" id="segundo_nombre" name="segundo_nombre" value="<?= sanitizar_html($estudiante['segundo_nombre'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="primer_apellido" class="form-label small fw-bold text-muted">Primer Apellido *</label>
                            <input type="text" class="form-control" id="primer_apellido" name="primer_apellido" value="<?= sanitizar_html($estudiante['primer_apellido']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="segundo_apellido" class="form-label small fw-bold text-muted">Segundo Apellido</label>
                            <input type="text" class="form-control" id="segundo_apellido" name="segundo_apellido" value="<?= sanitizar_html($estudiante['segundo_apellido'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="correo" class="form-label small fw-bold text-muted">Correo Electrónico (no editable)</label>
                        <input type="email" class="form-control bg-light" id="correo" value="<?= sanitizar_html($estudiante['correo_electronico']) ?>" disabled>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="tipo_documento" class="form-label small fw-bold text-muted">Tipo de Documento</label>
                            <select class="form-select" id="tipo_documento" name="tipo_documento">
                                <option value="">Seleccione...</option>
                                <?php foreach (['CC' => 'Cédula de Ciudadanía', 'CE' => 'Cédula de Extranjería', 'TI' => 'Tarjeta de Identidad', 'Pasaporte' => 'Pasaporte', 'NIT' => 'NIT', 'Otro' => 'Otro'] as $val => $label): ?>
                                    <option value="<?= $val ?>" <?= ($estudiante['tipo_documento_identidad'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="numero_documento" class="form-label small fw-bold text-muted">Número de Documento</label>
                            <input type="text" class="form-control" id="numero_documento" name="numero_documento" value="<?= sanitizar_html($estudiante['numero_documento_identidad'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="telefono" class="form-label small fw-bold text-muted">Teléfono Móvil</label>
                            <input type="text" class="form-control" id="telefono" name="telefono" value="<?= sanitizar_html($estudiante['numero_telefono'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label for="fecha_nacimiento" class="form-label small fw-bold text-muted">Fecha de Nacimiento</label>
                            <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" value="<?= sanitizar_html($estudiante['fecha_nacimiento'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="ciudad" class="form-label small fw-bold text-muted">Ciudad de Residencia</label>
                            <input type="text" class="form-control" id="ciudad" name="ciudad" value="<?= sanitizar_html($estudiante['ciudad_residencia'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="departamento" class="form-label small fw-bold text-muted">Departamento</label>
                            <input type="text" class="form-control" id="departamento" name="departamento" value="<?= sanitizar_html($estudiante['departamento_residencia'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="pais" class="form-label small fw-bold text-muted">País</label>
                        <input type="text" class="form-control" id="pais" name="pais" value="<?= sanitizar_html($estudiante['pais_residencia'] ?? 'Colombia') ?>">
                    </div>

                    <button type="submit" class="btn btn-primary rounded-pill shadow-sm" id="btnGuardarPerfil">Guardar Cambios</button>
                </form>
            </div>
        </div>

        <!-- Formulario de Cambio de Contraseña -->
        <div class="card-custom">
            <div class="card-header-custom"><i class="fas fa-key me-2"></i>Seguridad y Contraseña</div>
            <div class="card-body-custom">
                <div class="alert alert-success alert-custom" style="display:none" id="alert-pass-success" role="alert"></div>
                <div class="alert alert-danger alert-custom" style="display:none" id="alert-pass-error" role="alert"></div>

                <form id="passwordForm" autocomplete="off">
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

    <!-- Foto de Perfil -->
    <div class="col-lg-4">
        <div class="card-custom">
            <div class="card-header-custom"><i class="fas fa-camera me-2"></i>Foto de Perfil</div>
            <div class="card-body-custom text-center">
                <div class="alert alert-success alert-custom" style="display:none" id="alert-foto-success" role="alert"></div>
                <div class="alert alert-danger alert-custom" style="display:none" id="alert-foto-error" role="alert"></div>

                <div class="mb-3">
                    <img src="<?= $estudiante['foto_perfil'] ?: STUDENT_FOTO_URL . 'default-avatar.svg' ?>"
                         alt="Foto de Perfil"
                         id="fotoPreview"
                         class="rounded-circle border border-4 border-primary shadow-sm"
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

                <?php if (!empty($estudiante['foto_perfil'])): ?>
                <div class="mt-3">
                    <button type="button" class="btn btn-outline-danger rounded-pill btn-sm w-100" id="btnEliminarFoto"
                            data-csrf-token="<?= generar_token_csrf($pdo, 'foto') ?>">
                        <i class="fas fa-trash-alt me-1"></i>Eliminar Foto
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Resumen del Perfil -->
        <div class="card-custom mt-4">
            <div class="card-header-custom"><i class="fas fa-info-circle me-2"></i>Resumen</div>
            <div class="card-body-custom">
                <ul class="list-unstyled small mb-0">
                    <li class="mb-2"><strong>Rol:</strong> Estudiante</li>
                    <li class="mb-2"><strong>Miembro desde:</strong> <?= date('d/m/Y', strtotime($estudiante['fecha_creacion'])) ?></li>
                    <li class="mb-2"><strong>Último acceso:</strong> <?= $estudiante['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($estudiante['ultimo_acceso'])) : 'Nunca' ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
