<?php
// /cursoonline/admin/cursos/crear.php
// ============================================================
// Crear / Editar Curso — Panel Admin — EduTech Academy
// ============================================================

$page_title = 'Crear Curso';
require_once __DIR__ . '/../includes/header.php';

$msg_ok = $msg_err = '';

// ── Categorías y docentes para el formulario ─────────────────
$categorias = $pdo->query("SELECT id_categoria_pk, nombre_categoria FROM categorias_curso WHERE estado_activo = 1 ORDER BY nombre_categoria")->fetchAll();
$docentes   = $pdo->query("SELECT id_usuario_pk, primer_nombre, primer_apellido FROM usuarios WHERE id_rol_fk = " . ROL_PROFESOR . " AND estado_activo = 1 ORDER BY primer_apellido")->fetchAll();

// ── Valores por defecto del formulario ───────────────────────
$form = [
    'titulo_curso'       => '',
    'resumen_corto'      => '',
    'descripcion_detallada'  => '',
    'imagen_portada'     => '',
    'video_presentacion'  => '',
    'precio'             => '0',
    'precio_con_descuento'   => '',
    'nivel_dificultad'   => 'principiante',
    'total_horas'        => '',
    'id_categoria_fk'    => '',
    'id_profesor_fk'     => '',
    'requisitos_previos' => '',
    'lo_que_aprenderas'  => '',
    'para_quien_es'      => '',
    'idioma'             => 'Español',
    'estado_activo'      => 1,
];

// ── POST: Guardar nuevo curso ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!validar_token_csrf($pdo, $token)) {
        $msg_err = 'Token de seguridad inválido. Recarga la página.';
    } else {
        // Recoger y limpiar datos
        foreach ($form as $k => $_) {
            if (isset($_POST[$k])) {
                $form[$k] = in_array($k, ['descripcion_detallada', 'requisitos_previos', 'lo_que_aprenderas', 'para_quien_es'])
                    ? trim($_POST[$k])
                    : limpiar_entrada($_POST[$k]);
            }
        }
        $form['estado_activo'] = isset($_POST['estado_activo']) ? 1 : 0;
        $form['precio']        = (float)str_replace(['.', ','], ['', '.'], $form['precio']);
        $form['precio_descuento'] = $form['precio_con_descuento'] !== '' ? (float)str_replace(['.', ','], ['', '.'], $form['precio_descuento']) : null;
        $form['total_horas']   = $form['total_horas'] !== '' ? (float)$form['total_horas'] : null;
        $form['id_categoria_fk'] = (int)$form['id_categoria_fk'] ?: null;
        $form['id_profesor_fk']  = (int)$form['id_profesor_fk'] ?: null;

        // Validaciones básicas
        if (empty($form['titulo_curso'])) {
            $msg_err = 'El título del curso es obligatorio.';
        } elseif (empty($form['resumen_corto'])) {
            $msg_err = 'El resumen corto es obligatorio.';
        } elseif (!$form['id_categoria_fk']) {
            $msg_err = 'Debe seleccionar una categoría.';
        } else {
            try {
                // Usar procedimiento almacenado o INSERT directo (con fallback)
                try {
                    $stmt = $pdo->prepare("CALL sp_crear_curso(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,@nuevo_id)");
                    $stmt->execute([
                        $form['titulo_curso'], $form['resumen_corto'], $form['descripcion_detallada'],
                        $form['imagen_portada'] ?: null, $form['video_presentacion'] ?: null,
                        $form['precio'], $form['precio_con_descuento'],
                        $form['nivel_dificultad'], $form['total_horas'],
                        $form['id_categoria_fk'], $form['id_profesor_fk'],
                        $form['requisitos_previos'] ?: null, $form['lo_que_aprenderas'] ?: null,
                        $form['para_quien_es'] ?: null, $form['idioma'],
                        $form['estado_activo'], $id_usuario
                    ]);
                    $nuevo_id = $pdo->query("SELECT @nuevo_id")->fetchColumn();
                } catch (PDOException $sp_err) {
                    // Fallback: INSERT directo si el SP no existe
                    $stmt_ins = $pdo->prepare("
                        INSERT INTO cursos (titulo_curso, resumen_corto, descripcion_detallada, imagen_portada,
                            video_presentacion, precio, precio_con_descuento, nivel_dificultad, total_horas,
                            id_categoria_fk, id_profesor_fk, requisitos_previos, lo_que_aprenderas,
                            para_quien_es, idioma, estado_activo, modificado_por)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
                    ");
                    $stmt_ins->execute([
                        $form['titulo_curso'], $form['resumen_corto'], $form['descripcion_detallada'],
                        $form['imagen_portada'] ?: null, $form['video_presentacion'] ?: null,
                        $form['precio'], $form['precio_con_descuento'],
                        $form['nivel_dificultad'], $form['total_horas'],
                        $form['id_categoria_fk'], $form['id_profesor_fk'],
                        $form['requisitos_previos'] ?: null, $form['lo_que_aprenderas'] ?: null,
                        $form['para_quien_es'] ?: null, $form['idioma'],
                        $form['estado_activo'], $id_usuario
                    ]);
                    $nuevo_id = $pdo->lastInsertId();
                }
                header("Location: index.php?msg=creado&id=" . $nuevo_id);
                exit();
            } catch (PDOException $e) {
                error_log('[ADMIN CREAR CURSO] ' . $e->getMessage());
                $msg_err = 'Error al guardar el curso: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="page-header">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="index.php">Cursos</a></li>
            <li class="breadcrumb-item active">Crear Curso</li>
        </ol>
    </nav>
    <h1><i class="fas fa-plus-circle me-2 text-danger"></i>Crear Nuevo Curso</h1>
    <p>Completa toda la información para publicar el curso en el catálogo.</p>
</div>

<?php if ($msg_err): ?><div class="alert alert-danger rounded-3 mb-4"><i class="fas fa-exclamation-circle me-2"></i><?= sanitizar_html($msg_err) ?></div><?php endif; ?>

<form method="POST" id="formCurso">
    <?php imprimir_campo_csrf($pdo, 'crear_curso'); ?>

    <div class="row g-4">
        <!-- ── Columna Principal ─────────────────────────── -->
        <div class="col-lg-8">

            <!-- Información básica -->
            <div class="admin-card mb-4">
                <div class="admin-card-header"><span><i class="fas fa-book me-2"></i>Información Básica</span></div>
                <div class="admin-card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Título del Curso <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="titulo_curso"
                               value="<?= sanitizar_html($form['titulo_curso']) ?>"
                               placeholder="Ej: Machine Learning con Python" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Resumen Corto <span class="text-danger">*</span>
                            <span class="text-muted">(max 200 chars, visible en tarjetas)</span>
                        </label>
                        <textarea class="form-control" name="resumen_corto" rows="2" maxlength="500" required><?= sanitizar_html($form['resumen_corto']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Descripción Completa</label>
                        <textarea class="form-control" name="descripcion_larga" rows="8"
                                  placeholder="Descripción detallada del curso visible en la página de detalle..."><?= sanitizar_html($form['descripcion_larga']) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Contenido pedagógico -->
            <div class="admin-card mb-4">
                <div class="admin-card-header"><span><i class="fas fa-chalkboard me-2"></i>Contenido Pedagógico</span></div>
                <div class="admin-card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Lo que aprenderán</label>
                        <textarea class="form-control" name="lo_que_aprenderas" rows="5"
                                  placeholder="Un punto de aprendizaje por línea. Ej:&#10;Entender redes neuronales&#10;Implementar modelos en Python&#10;Usar TensorFlow y Keras"><?= sanitizar_html($form['lo_que_aprenderas']) ?></textarea>
                        <small class="text-muted">Escribe un elemento por línea. Se mostrará como lista de checkmarks en el detalle del curso.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Requisitos Previos</label>
                        <textarea class="form-control" name="requisitos_previos" rows="3"
                                  placeholder="Un requisito por línea..."><?= sanitizar_html($form['requisitos_previos']) ?></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold">¿Para quién es este curso?</label>
                        <textarea class="form-control" name="para_quien_es" rows="3"
                                  placeholder="Un perfil por línea..."><?= sanitizar_html($form['para_quien_es']) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Imagen y Video -->
            <div class="admin-card">
                <div class="admin-card-header"><span><i class="fas fa-image me-2"></i>Imagen y Video</span></div>
                <div class="admin-card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">URL de la Imagen de Portada</label>
                        <input type="url" class="form-control" name="imagen_portada" id="inputImagenPortada"
                               value="<?= sanitizar_html($form['imagen_portada']) ?>"
                               placeholder="https://images.unsplash.com/...">
                        <img id="previewImagen" src="" alt="Preview"
                             class="mt-2 rounded-3 d-none" style="max-height:180px;object-fit:cover;width:100%;">
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold">URL del Video Trailer</label>
                        <input type="url" class="form-control" name="video_trailer_url"
                               value="<?= sanitizar_html($form['video_trailer_url']) ?>"
                               placeholder="https://www.youtube.com/watch?v=...">
                    </div>
                </div>
            </div>

        </div>

        <!-- ── Columna Lateral ───────────────────────────── -->
        <div class="col-lg-4">

            <!-- Publicación -->
            <div class="admin-card mb-3">
                <div class="admin-card-header"><span><i class="fas fa-cog me-2"></i>Publicación</span></div>
                <div class="admin-card-body">
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="estado_activo"
                               id="switchEstado" <?= $form['estado_activo'] ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="switchEstado">
                            Publicar en catálogo
                        </label>
                    </div>
                    <button type="submit" class="btn-admin-primary w-100 justify-content-center mb-2">
                        <i class="fas fa-save me-1"></i> Guardar Curso
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
                            <option value="<?= $cat['id_categoria_pk'] ?>"
                                    <?= (int)$form['id_categoria_fk'] === (int)$cat['id_categoria_pk'] ? 'selected' : '' ?>>
                                <?= sanitizar_html($cat['nombre_categoria']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Nivel de Dificultad</label>
                        <select name="nivel_dificultad" class="form-select form-select-sm">
                            <option value="principiante" <?= $form['nivel_dificultad'] === 'principiante' ? 'selected' : '' ?>>🌱 Principiante</option>
                            <option value="intermedio"   <?= $form['nivel_dificultad'] === 'intermedio'   ? 'selected' : '' ?>>📚 Intermedio</option>
                            <option value="avanzado"     <?= $form['nivel_dificultad'] === 'avanzado'     ? 'selected' : '' ?>>🔥 Avanzado</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Docente Responsable</label>
                        <select name="id_profesor_fk" class="form-select form-select-sm">
                            <option value="">— Sin asignar —</option>
                            <?php foreach ($docentes as $d): ?>
                            <option value="<?= $d['id_usuario_pk'] ?>"
                                    <?= (int)$form['id_profesor_fk'] === (int)$d['id_usuario_pk'] ? 'selected' : '' ?>>
                                <?= sanitizar_html($d['primer_nombre'] . ' ' . $d['primer_apellido']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold">Idioma</label>
                        <select name="idioma" class="form-select form-select-sm">
                            <option value="Español"  <?= $form['idioma'] === 'Español'  ? 'selected' : '' ?>>Español</option>
                            <option value="Inglés"   <?= $form['idioma'] === 'Inglés'   ? 'selected' : '' ?>>Inglés</option>
                            <option value="Portugués"<?= $form['idioma'] === 'Portugués'? 'selected' : '' ?>>Portugués</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Precios -->
            <div class="admin-card">
                <div class="admin-card-header"><span><i class="fas fa-tag me-2"></i>Precio</span></div>
                <div class="admin-card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Precio (COP)</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" name="precio"
                                   value="<?= $form['precio'] ?>" min="0" step="1000" placeholder="0">
                        </div>
                        <small class="text-muted">Escribe 0 para curso gratuito.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Precio Antes del Descuento</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" name="precio_descuento"
                                   value="<?= $form['precio_descuento'] ?>" min="0" step="1000"
                                   placeholder="Precio original (opcional)">
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-semibold">Duración Total (horas)</label>
                        <input type="number" class="form-control form-control-sm" name="total_horas"
                               value="<?= $form['total_horas'] ?>" min="0" step="0.5"
                               placeholder="Ej: 12.5">
                    </div>
                </div>
            </div>

        </div>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
