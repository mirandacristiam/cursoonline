<?php
// /cursoonline/admin/cursos/editar.php
// ============================================================
// Editar Curso — Panel Administrativo
// ============================================================

$page_title = 'Editar Curso';
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
$id_curso = (int)($_GET['id'] ?? $_POST['id_curso'] ?? 0);

if (!$id_curso) {
    header('Location: index.php');
    exit();
}

// ── Cargar curso existente ───────────────────────────────
$curso = sp_admin_cursos($pdo, 'sp_admin_obtener_curso', ['p_id_curso' => $id_curso]);
if (empty($curso)) {
    header('Location: index.php?msg=notfound');
    exit();
}
$curso = $curso[0];

$form = [
    'id_curso_pk'           => (int)$curso['id_curso_pk'],
    'titulo_curso'          => $curso['titulo_curso'],
    'resumen_corto'         => $curso['resumen_corto'],
    'descripcion_detallada' => $curso['descripcion_detallada'],
    'imagen_portada'        => $curso['imagen_portada'],
    'video_presentacion'    => $curso['video_presentacion'],
    'tipo_video'            => $curso['tipo_video'] ?? 'youtube',
    'precio'                => $curso['precio'],
    'precio_con_descuento'  => $curso['precio_con_descuento'],
    'nivel_dificultad'      => $curso['nivel_dificultad'],
    'total_horas'           => $curso['total_horas'],
    'total_clases_estimado' => $curso['total_clases_estimado'],
    'duracion_meses'        => $curso['duracion_meses'] ?? 6,
    'id_categoria_fk'       => $curso['id_categoria_fk'],
    'id_profesor_fk'        => $curso['id_profesor_fk'],
    'lenguaje_programacion' => $curso['lenguaje_programacion'],
    'requisitos_previos'    => $curso['requisitos_previos'],
    'certificado_disponible'=> $curso['certificado_disponible'] ?? 1,
    'areas_laborales'       => $curso['areas_laborales'],
    'titulo_que_otorga'     => $curso['titulo_que_otorga'],
    'nivel_formacion'       => $curso['nivel_formacion'],
    'metodologia'           => $curso['metodologia'],
    'para_quien_es'         => $curso['para_quien_es'],
    'estado_activo'         => $curso['estado_activo'],
];

// ── POST: Actualizar curso ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validar_token_csrf($pdo, $token)) {
        $msg_err = 'Token de seguridad inválido. Recarga la página.';
    } else {
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

        if (empty($form['titulo_curso'])) $msg_err = 'El título del curso es obligatorio.';
        elseif (empty($form['resumen_corto'])) $msg_err = 'El resumen corto es obligatorio.';
        elseif (!$form['id_categoria_fk']) $msg_err = 'Debe seleccionar una categoría.';
        else {
            // ── Subir imagen ─────────────────────────────
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
                        $form['imagen_portada'] = 'admin/assets/images/cursos/' . $nombre;
                    } else {
                        $msg_err = 'Error al subir la imagen.';
                    }
                }
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
                    ':id_curso'     => $id_curso,
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
                header('Location: index.php?msg=actualizado');
                exit();
            } catch (PDOException $e) {
                error_log('[ADMIN EDITAR CURSO] ' . $e->getMessage());
                $msg_err = 'Error al actualizar: ' . $e->getMessage();
            }
        }
    }
}
}
?>
<!-- ─── HTML: Editar Curso ──────────────────────────────────── -->

<div class="page-header">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="index.php">Cursos</a></li>
            <li class="breadcrumb-item active">Editar: <?= sanitizar_html(mb_strimwidth($form['titulo_curso'], 0, 50, '...')) ?></li>
        </ol>
    </nav>
    <h1><i class="fas fa-edit me-2 text-primary"></i>Editar Curso</h1>
    <p>Modifica la información del curso.</p>
</div>

<?php if ($msg_err): ?>
<div class="alert alert-danger rounded-3 mb-4"><i class="fas fa-exclamation-circle me-2"></i><?= sanitizar_html($msg_err) ?></div>
<?php endif; ?>

<?php
$es_edicion = true;
include __DIR__ . '/_curso_form.php';
?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
