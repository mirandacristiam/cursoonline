<?php
// /cursoonline/admin/cursos/_curso_form.php
// ============================================================
// Formulario compartido para crear/editar cursos
// Define $form (array con valores) ANTES de incluir este archivo
// ============================================================

// ── Helper seguro para valor del form ────────────────────
function fv($campo) { global $form; return sanitizar_html($form[$campo] ?? ''); }

// ── Categorías y docentes ────────────────────────────────
$categorias = sp_admin_cursos($pdo, 'sp_admin_listar_categorias');
$docentes   = sp_admin_cursos($pdo, 'sp_admin_listar_profesores');
?>
<form method="POST" id="formCurso" enctype="multipart/form-data">
    <?php if (!empty($es_edicion) && !empty($form['id_curso_pk'])): ?>
        <input type="hidden" name="id_curso" value="<?= (int)$form['id_curso_pk'] ?>">
    <?php endif; ?>
    <?php $token_key = !empty($es_edicion) ? 'edit_curso_' . ($form['id_curso_pk'] ?? 0) : 'crear_curso'; ?>
    <?php imprimir_campo_csrf($pdo, $token_key); ?>

    <div class="row g-4">
        <!-- ── Columna Principal ─────────────────────── -->
        <div class="col-lg-8">

            <!-- Información Básica -->
            <div class="admin-card mb-4">
                <div class="admin-card-header"><span><i class="fas fa-book me-2"></i>Información Básica</span></div>
                <div class="admin-card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Título del Curso <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="titulo_curso" value="<?= fv('titulo_curso') ?>" placeholder="Ej: Machine Learning con Python" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Resumen Corto <span class="text-danger">*</span>
                            <span class="text-muted fw-normal">(máx. 300 caracteres)</span>
                        </label>
                        <textarea class="form-control" name="resumen_corto" rows="2" maxlength="500" required><?= fv('resumen_corto') ?></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold">Descripción Detallada</label>
                        <textarea class="form-control" name="descripcion_detallada" rows="8" placeholder="Descripción completa del curso..."><?= fv('descripcion_detallada') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Contenido Pedagógico -->
            <div class="admin-card mb-4">
                <div class="admin-card-header"><span><i class="fas fa-chalkboard me-2"></i>Contenido Pedagógico</span></div>
                <div class="admin-card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Requisitos Previos</label>
                        <textarea class="form-control" name="requisitos_previos" rows="3" placeholder="Un requisito por línea..."><?= fv('requisitos_previos') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Áreas Laborales</label>
                        <textarea class="form-control" name="areas_laborales" rows="3" placeholder="Un área por línea..."><?= fv('areas_laborales') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">¿Para quién es este curso?</label>
                        <textarea class="form-control" name="para_quien_es" rows="3" placeholder="Un perfil por línea..."><?= fv('para_quien_es') ?></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold">Metodología</label>
                        <textarea class="form-control" name="metodologia" rows="3" placeholder="Descripción de la metodología del curso..."><?= fv('metodologia') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Imagen y Video -->
            <div class="admin-card mb-4">
                <div class="admin-card-header"><span><i class="fas fa-image me-2"></i>Imagen y Video</span></div>
                <div class="admin-card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Imagen de Portada</label>
                        <input type="file" class="form-control" name="imagen_portada" id="inputImagenPortada" accept="image/*">
                        <div class="mt-2">
                            <?php if ($es_edicion && fv('imagen_portada')): ?>
                            <img src="/cursoonline/<?= sanitizar_html(fv('imagen_portada')) ?>" alt="Portada actual" class="rounded-3" style="max-height:120px;object-fit:cover;">
                            <div class="text-muted small mt-1">Imagen actual. Sube un archivo para reemplazarla.</div>
                            <?php endif; ?>
                            <img id="previewImagen" src="" alt="Preview" class="mt-1 rounded-3 d-none" style="max-height:180px;object-fit:cover;width:100%;">
                        </div>
                        <div class="text-muted small mt-1">Formatos: JPG, PNG, WEBP. Máximo 2 MB.</div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold">URL del Video de Presentación</label>
                        <input type="url" class="form-control" name="video_presentacion" value="<?= fv('video_presentacion') ?>" placeholder="https://www.youtube.com/watch?v=...">
                    </div>
                </div>
            </div>

            <!-- Información Académica -->
            <div class="admin-card">
                <div class="admin-card-header"><span><i class="fas fa-university me-2"></i>Información Académica</span></div>
                <div class="admin-card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Título que Otorga</label>
                            <input type="text" class="form-control form-control-sm" name="titulo_que_otorga" value="<?= fv('titulo_que_otorga') ?>" placeholder="Ej: Certificado en Machine Learning">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Nivel de Formación</label>
                            <input type="text" class="form-control form-control-sm" name="nivel_formacion" value="<?= fv('nivel_formacion') ?>" placeholder="Ej: Especialización, Diplomado">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Duración (meses)</label>
                            <input type="number" class="form-control form-control-sm" name="duracion_meses" value="<?= fv('duracion_meses') ?: 6 ?>" min="1" max="60">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Total Horas Estimadas</label>
                            <input type="number" class="form-control form-control-sm" name="total_horas" value="<?= fv('total_horas') ?>" min="0" step="0.5">
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ── Columna Lateral ────────────────────────── -->
        <div class="col-lg-4">

            <!-- Publicación -->
            <div class="admin-card mb-3">
                <div class="admin-card-header"><span><i class="fas fa-cog me-2"></i>Publicación</span></div>
                <div class="admin-card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="estado_activo" id="switchEstado" <?= ($form['estado_activo'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="switchEstado">Publicar en catálogo</label>
                    </div>
                    <button type="submit" class="btn-admin-primary w-100 justify-content-center mb-2">
                        <i class="fas fa-save me-1"></i> <?= !empty($es_edicion) ? 'Actualizar Curso' : 'Guardar Curso' ?>
                    </button>
                    <a href="index.php" class="btn btn-outline-secondary w-100 btn-sm rounded-3">Cancelar</a>
                </div>
            </div>

            <!-- Clasificación -->
            <div class="admin-card mb-3">
                <div class="admin-card-header"><span><i class="fas fa-tags me-2"></i>Clasificación</span></div>
                <div class="admin-card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Categoría <span class="text-danger">*</span></label>
                        <select name="id_categoria_fk" class="form-select form-select-sm" required>
                            <option value="">— Seleccionar —</option>
                            <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id_categoria_pk'] ?>" <?= (int)($form['id_categoria_fk'] ?? 0) === (int)$cat['id_categoria_pk'] ? 'selected' : '' ?>>
                                <?= sanitizar_html($cat['nombre_categoria']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nivel de Dificultad</label>
                        <select name="nivel_dificultad" class="form-select form-select-sm">
                            <option value="Principiante" <?= ($form['nivel_dificultad'] ?? '') === 'Principiante' ? 'selected' : '' ?>>🌱 Principiante</option>
                            <option value="Intermedio"   <?= ($form['nivel_dificultad'] ?? '') === 'Intermedio'   ? 'selected' : '' ?>>📚 Intermedio</option>
                            <option value="Avanzado"     <?= ($form['nivel_dificultad'] ?? '') === 'Avanzado'     ? 'selected' : '' ?>>🔥 Avanzado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Docente Responsable</label>
                        <select name="id_profesor_fk" class="form-select form-select-sm">
                            <option value="">— Sin asignar —</option>
                            <?php foreach ($docentes as $d): ?>
                            <option value="<?= $d['id_usuario_pk'] ?>" <?= (int)($form['id_profesor_fk'] ?? 0) === (int)$d['id_usuario_pk'] ? 'selected' : '' ?>>
                                <?= sanitizar_html($d['primer_nombre'] . ' ' . $d['primer_apellido']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Lenguaje de Programación</label>
                        <input type="text" class="form-control form-control-sm" name="lenguaje_programacion" value="<?= fv('lenguaje_programacion') ?>" placeholder="Ej: Python, PHP, JavaScript">
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold">Tipo de Video</label>
                        <select name="tipo_video" class="form-select form-select-sm">
                            <option value="youtube" <?= ($form['tipo_video'] ?? '') === 'youtube' ? 'selected' : '' ?>>YouTube</option>
                            <option value="vimeo"   <?= ($form['tipo_video'] ?? '') === 'vimeo'   ? 'selected' : '' ?>>Vimeo</option>
                            <option value="servidor_propio" <?= ($form['tipo_video'] ?? '') === 'servidor_propio' ? 'selected' : '' ?>>Servidor Propio</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Precio -->
            <div class="admin-card mb-3">
                <div class="admin-card-header"><span><i class="fas fa-tag me-2"></i>Precio</span></div>
                <div class="admin-card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Precio (COP)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" name="precio" value="<?= fv('precio') ?: 0 ?>" min="0" step="1000">
                        </div>
                        <small class="text-muted">0 = curso gratuito.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Precio con Descuento</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" name="precio_con_descuento" value="<?= fv('precio_con_descuento') ?>" min="0" step="1000" placeholder="Opcional">
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold">Clases Estimadas</label>
                        <input type="number" class="form-control form-control-sm" name="total_clases_estimado" value="<?= fv('total_clases_estimado') ?: 0 ?>" min="0">
                    </div>
                </div>
            </div>

            <!-- Certificado -->
            <div class="admin-card">
                <div class="admin-card-header"><span><i class="fas fa-certificate me-2"></i>Certificado</span></div>
                <div class="admin-card-body">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="certificado_disponible" id="switchCertificado" <?= ($form['certificado_disponible'] ?? 1) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="switchCertificado">Certificado disponible</label>
                    </div>
                </div>
            </div>

        </div>
    </div>
</form>
