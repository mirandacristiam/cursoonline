<?php
// /cursoonline/admin/cursos/crear.php
// ============================================================
// Crear Curso — Panel Administrativo
// ============================================================

$page_title = 'Crear Curso';
$page_script = '../assets/js/cursos.js';
$page_css    = '../assets/css/cursos.css';
require_once __DIR__ . '/../includes/header.php';

// ── Helper ───────────────────────────────────────────────
function sp_admin_cursos($pdo, $sp_name, $params = [], $has_out = false) {
    $placeholders = [];
    foreach ($params as $k => $v) { $placeholders[] = ':' . $k; }
    $sql = 'CALL ' . $sp_name . '(' . implode(',', $placeholders) . ($has_out ? ', @_out' : '') . ')';
    $emulate = $pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, $emulate);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    return $has_out ? [$rows, (int)$pdo->query('SELECT @_out')->fetchColumn()] : $rows;
}

function sp_exec($pdo, $sql, $params = []) {
    $emulate = $pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    $stmt = $pdo->prepare($sql);
    $r = $stmt->execute($params);
    $stmt->closeCursor();
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, $emulate);
    return $r;
}

$msg_err = '';

// ── Valores por defecto ──────────────────────────────────
$form = [
    'id_curso_pk'           => 0,
    'titulo_curso'          => '',
    'resumen_corto'         => '',
    'descripcion_detallada' => '',
    'imagen_portada'        => '',
    'video_presentacion'    => '',
    'tipo_video'            => 'youtube',
    'precio'                => 0,
    'precio_con_descuento'  => null,
    'nivel_dificultad'      => 'Principiante',
    'total_horas'           => 0,
    'total_clases_estimado' => 0,
    'duracion_meses'        => 6,
    'id_categoria_fk'       => 0,
    'id_profesor_fk'        => 0,
    'lenguaje_programacion' => '',
    'requisitos_previos'    => '',
    'certificado_disponible' => 1,
    'areas_laborales'       => '',
    'titulo_que_otorga'     => '',
    'nivel_formacion'       => '',
    'metodologia'           => '',
    'para_quien_es'         => '',
    'estado_activo'         => 1,
];

// ── POST: Guardar nuevo curso ────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validar_token_csrf($pdo, $token)) {
        $msg_err = 'Token de seguridad inválido. Recarga la página.';
    } else {
        // Recoger datos
        $campos_texto = ['descripcion_detallada', 'requisitos_previos', 'areas_laborales', 'metodologia', 'para_quien_es'];
        foreach ($form as $k => $v) {
            if (isset($_POST[$k])) {
                $form[$k] = in_array($k, $campos_texto) ? trim($_POST[$k]) : limpiar_entrada($_POST[$k]);
            }
        }
        $form['estado_activo']         = isset($_POST['estado_activo']) ? 1 : 0;
        $form['certificado_disponible'] = isset($_POST['certificado_disponible']) ? 1 : 0;
        $form['precio']                = (float)($form['precio'] ?? 0);
        $form['precio_con_descuento']  = $form['precio_con_descuento'] !== '' && $form['precio_con_descuento'] !== null ? (float)$form['precio_con_descuento'] : null;
        $form['total_horas']           = (int)($form['total_horas'] ?? 0);
        $form['total_clases_estimado'] = (int)($form['total_clases_estimado'] ?? 0);
        $form['duracion_meses']        = (int)($form['duracion_meses'] ?? 6);
        $form['id_categoria_fk']       = (int)($form['id_categoria_fk'] ?? 0);
        $form['id_profesor_fk']        = (int)($form['id_profesor_fk'] ?? 0);

        // Validar
        if (empty($form['titulo_curso']))     $msg_err = 'El título del curso es obligatorio.';
        elseif (empty($form['resumen_corto']))     $msg_err = 'El resumen corto es obligatorio.';
        elseif (!$form['id_categoria_fk'])     $msg_err = 'Debe seleccionar una categoría.';
        else {
            // ── Subir imagen ─────────────────────────────
            $ruta_imagen = '';
            if (!empty($_FILES['imagen_portada']['name']) && $_FILES['imagen_portada']['error'] === UPLOAD_ERR_OK) {
                $img_dir = __DIR__ . '/../assets/images/cursos/';
                $ext = strtolower(pathinfo($_FILES['imagen_portada']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $msg_err = 'Formato de imagen no válido (JPG, PNG, WEBP).';
                } elseif ($_FILES['imagen_portada']['size'] > 2097152) {
                    $msg_err = 'La imagen supera los 2 MB.';
                } else {
                    $nombre = uniqid('curso_') . '.' . $ext;
                    if (move_uploaded_file($_FILES['imagen_portada']['tmp_name'], $img_dir . $nombre)) {
                        $ruta_imagen = 'admin/assets/images/cursos/' . $nombre;
                    } else {
                        $msg_err = 'Error al subir la imagen.';
                    }
                }
            }
            if (!$msg_err) {
                $form['imagen_portada'] = $ruta_imagen;
            }
            if (!$msg_err) {
                try {
                $sql = 'CALL sp_admin_guardar_curso(
                    :id_curso, :titulo, :resumen, :descripcion, :imagen, :video, :tipo_video,
                    :precio, :precio_desc, :nivel, :horas, :clases_est, :meses,
                    :categoria, :profesor, :lenguaje, :requisitos, :certificado,
                    :areas, :titulo_otorga, :nivel_form, :metodologia, :para_quien,
                    :estado, :modificado, @nuevo_id)';

                $p_duracion = max(1, min(60, $form['duracion_meses']));

                $emulate = $pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES);
                $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id_curso'     => 0,
                    ':titulo'       => $form['titulo_curso'],
                    ':resumen'      => $form['resumen_corto'],
                    ':descripcion'  => $form['descripcion_detallada'] ?: null,
                    ':imagen'       => $form['imagen_portada'] ?: null,
                    ':video'        => $form['video_presentacion'] ?: null,
                    ':tipo_video'   => $form['tipo_video'] ?: 'youtube',
                    ':precio'       => $form['precio'],
                    ':precio_desc'  => $form['precio_con_descuento'],
                    ':nivel'        => $form['nivel_dificultad'] ?: 'Principiante',
                    ':horas'        => $form['total_horas'],
                    ':clases_est'   => $form['total_clases_estimado'],
                    ':meses'        => $p_duracion,
                    ':categoria'    => $form['id_categoria_fk'],
                    ':profesor'     => $form['id_profesor_fk'] ?: null,
                    ':lenguaje'     => $form['lenguaje_programacion'] ?: null,
                    ':requisitos'   => $form['requisitos_previos'] ?: null,
                    ':certificado'  => $form['certificado_disponible'],
                    ':areas'        => $form['areas_laborales'] ?: null,
                    ':titulo_otorga'=> $form['titulo_que_otorga'] ?: null,
                    ':nivel_form'   => $form['nivel_formacion'] ?: null,
                    ':metodologia'  => $form['metodologia'] ?: null,
                    ':para_quien'   => $form['para_quien_es'] ?: null,
                    ':estado'       => $form['estado_activo'],
                    ':modificado'   => $id_usuario,
                ]);
                $stmt->closeCursor();
                $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, $emulate);
                $nuevo_id = (int)$pdo->query('SELECT @nuevo_id')->fetchColumn();
                header('Location: index.php?msg=creado&id=' . $nuevo_id);
                exit();
            } catch (PDOException $e) {
                error_log('[ADMIN CREAR CURSO] ' . $e->getMessage());
                $msg_err = 'Error al guardar el curso: ' . $e->getMessage();
            }
        }
    }
}
}
?>
<!-- ─── HTML: Crear Curso ───────────────────────────────────── -->

<div class="page-header">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="index.php">Cursos</a></li>
            <li class="breadcrumb-item active">Crear Curso</li>
        </ol>
    </nav>
    <h1><i class="fas fa-plus-circle me-2 text-danger"></i>Crear Nuevo Curso</h1>
    <p>Completa la información para publicar el curso en el catálogo.</p>
</div>

<?php if ($msg_err): ?>
<div class="alert alert-danger rounded-3 mb-4"><i class="fas fa-exclamation-circle me-2"></i><?= sanitizar_html($msg_err) ?></div>
<?php endif; ?>

<?php
$es_edicion = false;
include __DIR__ . '/_curso_form.php';
?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
