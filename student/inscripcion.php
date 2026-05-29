<?php
// /cursoonline/student/inscripcion.php
// ============================================================
// Página de Inscripción / Checkout de Cursos — EduTech Academy
// ============================================================

$page_title = 'Inscripción al Curso';
require_once __DIR__ . '/includes/header.php';

$id_curso = isset($_GET['curso']) ? (int)$_GET['curso'] : 0;

if (!$id_curso) {
    header('Location: ../index.php#cursos');
    exit();
}

// 1. Obtener detalles del curso
$stmt_curso = $pdo->prepare("
    SELECT c.*, cat.nombre_categoria, cat.color_categoria
    FROM cursos c
    INNER JOIN categorias_curso cat ON cat.id_categoria_pk = c.id_categoria_fk
    WHERE c.id_curso_pk = :id AND c.estado_activo = 1
");
$stmt_curso->execute([':id' => $id_curso]);
$curso = $stmt_curso->fetch();

if (!$curso) {
    header('Location: ../index.php#cursos');
    exit();
}

// 2. Verificar si ya está inscrito
$stmt_ins = $pdo->prepare("
    SELECT 1 FROM inscripciones 
    WHERE id_curso_fk = :curso AND id_usuario_fk = :usr AND estado_inscripcion = 'activa' AND estado_activo = 1
");
$stmt_ins->execute([':curso' => $id_curso, ':usr' => $id_usuario]);
$ya_inscrito = (bool)$stmt_ins->fetchColumn();

if ($ya_inscrito) {
    header('Location: mis-cursos.php');
    exit();
}

// 3. Obtener medios de pago activos
$stmt_mp = $pdo->prepare("SELECT * FROM medios_pago WHERE es_medio_activo = 1 ORDER BY id_medio_pago_pk ASC");
$stmt_mp->execute();
$medios_pago = $stmt_mp->fetchAll();

// Calcular precios
$precio_original = (float)($curso['precio'] ?? 0);
$precio_final    = !empty($curso['precio_con_descuento']) ? (float)$curso['precio_con_descuento'] : $precio_original;
$tiene_descuento = !empty($curso['precio_con_descuento']) && $curso['precio_con_descuento'] < $precio_original;
$ahorro = $precio_original - $precio_final;

$error_msg = '';
$success_msg = '';

// 4. Procesar el pago simulado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $id_medio_pago = (int)($_POST['medio_pago'] ?? 0);

    if (!validar_token_csrf($pdo, $token)) {
        $error_msg = 'Token de seguridad inválido. Recargue la página e intente de nuevo.';
    } elseif ($id_medio_pago <= 0) {
        $error_msg = 'Por favor, seleccione un medio de pago válido.';
    } else {
        // Ejecutar inscripción transaccional mediante el Stored Procedure sp_inscribir_estudiante
        try {
            $referencia = 'EDU-' . strtoupper(bin2hex(random_bytes(4))) . '-' . time();
            
            $stmt_sp = $pdo->prepare("CALL sp_inscribir_estudiante(:usr, :curso, :monto, :medio, :ref, @resultado)");
            $stmt_sp->execute([
                ':usr'   => $id_usuario,
                ':curso' => $id_curso,
                ':monto' => $precio_final,
                ':medio' => $id_medio_pago,
                ':ref'   => $referencia
            ]);
            
            $res_out = $pdo->query("SELECT @resultado AS resultado")->fetch();
            $resultado_sp = $res_out['resultado'] ?? '';

            if (strpos($resultado_sp, 'OK:') === 0) {
                // Crear notificación interna para el estudiante
                $stmt_notif = $pdo->prepare("
                    INSERT INTO notificaciones (titulo_notificacion, mensaje_notificacion, tipo_notificacion, estado_activo) 
                    VALUES ('Inscripción Exitosa', :msg, 'exito', 1)
                ");
                $curso_titulo = $curso['titulo_curso'];
                $stmt_notif->execute([':msg' => "Te has inscrito correctamente en el curso '$curso_titulo'. ¡Que disfrutes tu aprendizaje!"]);
                $id_notif = $pdo->lastInsertId();

                $stmt_notif_usr = $pdo->prepare("
                    INSERT INTO notificaciones_usuario (id_notificacion_fk, id_usuario_fk, estado_leida, estado_activo) 
                    VALUES (?, ?, 0, 1)
                ");
                $stmt_notif_usr->execute([$id_notif, $id_usuario]);

                echo "<script>
                    window.onload = function() {
                        alert('¡Felicidades! Tu inscripción ha sido completada con éxito.');
                        window.location.href = 'mis-cursos.php';
                    }
                </script>";
                exit();
            } else {
                $error_msg = str_replace('ERROR:', '', $resultado_sp);
            }
        } catch (Exception $e) {
            $error_msg = 'Ocurrió un error inesperado al procesar la inscripción: ' . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 fw-bold text-primary mb-1">Confirmar Inscripción y Pago</h1>
            <p class="text-muted m-0">Estás a un paso de comenzar tu formación profesional en tecnología avanzada.</p>
        </div>
    </div>

    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?= sanitizar_html($error_msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Resumen del Curso y Detalles -->
        <div class="col-lg-5 order-lg-2">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div style="position: relative; padding-top: 56.25%; overflow: hidden;">
                    <img src="<?= $curso['imagen_portada'] ?: 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80' ?>" 
                         alt="<?= sanitizar_html($curso['titulo_curso']) ?>" 
                         style="position:absolute; top:0; left:0; width:100%; height:100%; object-fit:cover;">
                </div>
                <div class="card-body p-4">
                    <span class="badge mb-2" style="background-color: <?= $curso['color_categoria'] ?: '#2563EB' ?>;">
                        <?= sanitizar_html($curso['nombre_categoria']) ?>
                    </span>
                    <h2 class="h5 fw-bold text-primary mb-3"><?= sanitizar_html($curso['titulo_curso']) ?></h2>
                    <p class="text-muted small mb-4"><?= sanitizar_html($curso['resumen_corto']) ?></p>
                    
                    <hr class="text-muted opacity-25 mb-4">

                    <!-- Desglose de Precios -->
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Precio del Curso:</span>
                        <span class="fw-semibold text-muted text-decoration-line-through"><?= MONEDA_SIMBOLO . number_format($precio_original, 0, ',', '.') ?> COP</span>
                    </div>

                    <?php if ($tiene_descuento): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-success fw-medium">Descuento EduTech:</span>
                            <span class="fw-semibold text-success">- <?= MONEDA_SIMBOLO . number_format($ahorro, 0, ',', '.') ?> COP</span>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                        <span class="fw-bold fs-5 text-primary">Total a Pagar:</span>
                        <span class="fw-bold fs-4 text-primary"><?= MONEDA_SIMBOLO . number_format($precio_final, 0, ',', '.') ?> COP</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Opciones de Medios de Pago y Formulario -->
        <div class="col-lg-7 order-lg-1">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h3 class="h5 fw-bold text-primary mb-4"><i class="fas fa-wallet me-2 text-primary"></i>Selecciona un medio de pago</h3>
                
                <form method="POST" id="checkoutForm">
                    <?php imprimir_campo_csrf($pdo, 'inscripcion'); ?>

                    <div class="d-flex flex-column gap-3 mb-4">
                        <?php foreach ($medios_pago as $i => $mp): ?>
                            <label class="d-flex align-items-start gap-3 p-3 border rounded-3 cursor-pointer hover-shadow transition" style="cursor: pointer;">
                                <input type="radio" name="medio_pago" value="<?= $mp['id_medio_pago_pk'] ?>" class="mt-1" <?= $i === 0 ? 'checked' : '' ?>>
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="fw-bold text-primary"><?= sanitizar_html($mp['nombre_medio_pago']) ?></span>
                                        <span class="badge bg-secondary-subtle text-secondary small">Simulado</span>
                                    </div>
                                    <p class="text-muted small mb-0"><?= sanitizar_html($mp['descripcion_medio_pago']) ?></p>
                                    
                                    <?php if (!empty($mp['instrucciones_pago'])): ?>
                                        <div class="mt-2 p-2 bg-light rounded text-muted small border-start border-primary border-3" style="white-space: pre-wrap; font-size: 0.75rem;"><?= sanitizar_html($mp['instrucciones_pago']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="alert alert-info rounded-3 mb-4 small" role="alert">
                        <i class="fas fa-shield-alt me-2 text-info"></i>
                        <strong>Entorno Seguro</strong>: Toda la información de tu pago está protegida por encriptación avanzada y autenticación CSRF/PDO.
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill shadow" id="btnConfirmPay">
                            <i class="fas fa-lock me-2"></i> Pagar e Inscribirse
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
