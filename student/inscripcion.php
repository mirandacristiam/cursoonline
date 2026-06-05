<?php
// /cursoonline/student/inscripcion.php
// ============================================================
// Solicitud de Inscripción y Pago — EduTech Academy
// Flujo: Estudiante solicita → Admin aprueba/rechaza
// Implementado con SQL DIRECTO (no requiere SPs instalados)
// ============================================================

$page_title = 'Solicitar Inscripción';
require_once __DIR__ . '/includes/header.php';

$id_curso = isset($_GET['curso']) ? (int)$_GET['curso'] : 0;

if (!$id_curso) {
    header('Location: explorar-cursos.php');
    exit();
}

// ── 1. Datos del curso ────────────────────────────────────────
$stmt_curso = $pdo->prepare("
    SELECT c.id_curso_pk, c.titulo_curso, c.resumen_corto, c.imagen_portada,
           c.precio, c.precio_con_descuento, c.nivel_dificultad,
           c.total_horas, c.certificado_disponible,
           cat.nombre_categoria, cat.color_categoria
    FROM cursos c
    INNER JOIN categorias_curso cat ON cat.id_categoria_pk = c.id_categoria_fk
    WHERE c.id_curso_pk = :id AND c.estado_activo = 1
");
$stmt_curso->execute([':id' => $id_curso]);
$curso = $stmt_curso->fetch(PDO::FETCH_ASSOC);

if (!$curso) {
    header('Location: explorar-cursos.php');
    exit();
}

// ── 2. Verificar estado actual de la inscripción ──────────────
$ya_activo    = false;
$tiene_pendiente = false;
$id_tx_pendiente = 0;

$stmt_ins = $pdo->prepare("
    SELECT estado_inscripcion FROM inscripciones
    WHERE id_usuario_fk = :usr AND id_curso_fk = :cur AND estado_activo = 1
    ORDER BY fecha_creacion DESC LIMIT 1
");
$stmt_ins->execute([':usr' => $id_usuario, ':cur' => $id_curso]);
$estado_ins = $stmt_ins->fetchColumn();

if ($estado_ins === 'activa' || $estado_ins === 'completada') {
    header('Location: mis-cursos.php');
    exit();
}
if ($estado_ins === 'suspendida') {
    // Verificar si hay transacción pendiente
    $stmt_tx = $pdo->prepare("
        SELECT id_transaccion_pk FROM transacciones_pago
        WHERE id_usuario_fk = :usr AND id_curso_fk = :cur
          AND estado_transaccion = 'pendiente' AND estado_activo = 1
        ORDER BY fecha_creacion DESC LIMIT 1
    ");
    $stmt_tx->execute([':usr' => $id_usuario, ':cur' => $id_curso]);
    $id_tx_pendiente = (int)$stmt_tx->fetchColumn();
    $tiene_pendiente = $id_tx_pendiente > 0;
}

// ── 3. Medios de pago activos ─────────────────────────────────
$medios_pago = [];
try {
    $stmt_mp = $pdo->prepare("
        SELECT id_medio_pago_pk, nombre_medio_pago, descripcion_medio_pago,
               instrucciones_pago, logo_medio_pago, tipo_integracion
        FROM medios_pago
        WHERE es_medio_activo = 1 AND estado_activo = 1
        ORDER BY id_medio_pago_pk ASC
    ");
    $stmt_mp->execute();
    $medios_pago = $stmt_mp->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $medios_pago = [];
}

// ── 4. Precios ────────────────────────────────────────────────
$precio_original = (float)($curso['precio'] ?? 0);
$precio_final    = (!empty($curso['precio_con_descuento']) && (float)$curso['precio_con_descuento'] > 0)
                   ? (float)$curso['precio_con_descuento'] : $precio_original;
$tiene_descuento = $precio_final < $precio_original && $precio_original > 0;
$ahorro          = $precio_original - $precio_final;
$es_gratis       = $precio_final <= 0;

$error_msg   = '';
$success_msg = '';

// ── 5. Procesar formulario ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$tiene_pendiente) {

    $token         = $_POST['csrf_token'] ?? '';
    $id_medio_pago = (int)($_POST['medio_pago'] ?? 0);
    $comprobante   = trim($_POST['comprobante'] ?? '');

    if (!validar_token_csrf($pdo, $token)) {
        $error_msg = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';

    } elseif (!$es_gratis && $id_medio_pago <= 0 && !empty($medios_pago)) {
        $error_msg = 'Por favor selecciona un medio de pago.';

    } else {

        // Para cursos gratis, usar primer medio disponible
        if ($es_gratis) {
            $id_medio_pago = !empty($medios_pago) ? (int)$medios_pago[0]['id_medio_pago_pk'] : 0;
            $precio_final  = 0.00;
        }

        // Verificar que el medio de pago exista si se requiere
        if (!$es_gratis && $id_medio_pago > 0) {
            $stmt_chk = $pdo->prepare("SELECT COUNT(*) FROM medios_pago WHERE id_medio_pago_pk = :id AND es_medio_activo = 1");
            $stmt_chk->execute([':id' => $id_medio_pago]);
            if (!(int)$stmt_chk->fetchColumn()) {
                $error_msg = 'El medio de pago seleccionado no es válido.';
            }
        }

        if (!$error_msg) {
            try {
                $pdo->beginTransaction();

                // Generar referencia única
                $referencia = 'EDU-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8)) . '-' . date('ymd');

                // Para cursos sin medio de pago configurado
                if ($id_medio_pago <= 0) {
                    // Insertar un medio genérico si es necesario (gratis sin medios configurados)
                    // usamos 1 como fallback
                    $id_medio_pago = 1;
                }

                // INSERT en transacciones_pago con estado PENDIENTE
                $stmt_ins_tx = $pdo->prepare("
                    INSERT INTO transacciones_pago
                        (id_usuario_fk, id_curso_fk, id_medio_pago_fk,
                         numero_referencia, monto_total, estado_transaccion,
                         observaciones, ip_origen_transaccion, estado_activo)
                    VALUES
                        (:usr, :cur, :medio,
                         :ref, :monto, 'pendiente',
                         :obs, :ip, 1)
                ");
                $stmt_ins_tx->execute([
                    ':usr'   => $id_usuario,
                    ':cur'   => $id_curso,
                    ':medio' => $id_medio_pago,
                    ':ref'   => $referencia,
                    ':monto' => $precio_final,
                    ':obs'   => $comprobante ?: 'Sin comprobante adjunto',
                    ':ip'    => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                ]);
                $id_tx_nuevo = (int)$pdo->lastInsertId();

                // INSERT/UPDATE inscripción en estado SUSPENDIDA
                $stmt_ins_curso = $pdo->prepare("
                    INSERT INTO inscripciones
                        (id_usuario_fk, id_curso_fk, fecha_inscripcion,
                         monto_pagado, estado_inscripcion, porcentaje_progreso, estado_activo)
                    VALUES
                        (:usr, :cur, NOW(), :monto, 'suspendida', 0.00, 1)
                    ON DUPLICATE KEY UPDATE
                        monto_pagado       = :monto2,
                        estado_inscripcion = 'suspendida',
                        fecha_modificacion = NOW()
                ");
                $stmt_ins_curso->execute([
                    ':usr'   => $id_usuario,
                    ':cur'   => $id_curso,
                    ':monto' => $precio_final,
                    ':monto2'=> $precio_final,
                ]);

                $pdo->commit();

                // Para cursos gratuitos → aprobar inmediatamente sin SP
                if ($es_gratis) {
                    $pdo->beginTransaction();

                    // Aprobar transacción
                    $pdo->prepare("
                        UPDATE transacciones_pago
                        SET estado_transaccion = 'aprobada',
                            observaciones      = 'Inscripción gratuita — aprobada automáticamente.'
                        WHERE id_transaccion_pk = :id
                    ")->execute([':id' => $id_tx_nuevo]);

                    // Activar inscripción
                    $pdo->prepare("
                        UPDATE inscripciones
                        SET estado_inscripcion = 'activa', fecha_modificacion = NOW()
                        WHERE id_usuario_fk = :usr AND id_curso_fk = :cur
                    ")->execute([':usr' => $id_usuario, ':cur' => $id_curso]);

                    // Incrementar contador de estudiantes
                    $pdo->prepare("
                        UPDATE cursos SET numero_estudiantes = numero_estudiantes + 1
                        WHERE id_curso_pk = :id
                    ")->execute([':id' => $id_curso]);

                    $pdo->commit();

                    header('Location: mis-cursos.php?inscripcion=ok');
                    exit();
                }

                // Curso de pago → solicitud enviada, espera aprobación
                $success_msg    = 'solicitud_enviada';
                $tiene_pendiente = true;
                $id_tx_pendiente = $id_tx_nuevo;

            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log('Error inscripcion.php usuario=' . $id_usuario . ' curso=' . $id_curso . ': ' . $e->getMessage());
                $error_msg = 'Ocurrió un error al procesar tu solicitud. Por favor intenta de nuevo.';
            }
        }
    }
}
?>

<!-- ── ENCABEZADO ─────────────────────────────────────────── -->
<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="explorar-cursos.php">Explorar Cursos</a></li>
                <li class="breadcrumb-item active">Inscripción</li>
            </ol>
        </nav>
        <h1 class="h3 fw-bold text-primary mb-1">
            <i class="fas fa-graduation-cap me-2"></i>Solicitar Inscripción
        </h1>
        <p class="text-muted m-0">
            El administrador revisará y aprobará tu pago para darte acceso al curso.
        </p>
    </div>
</div>

<!-- ── ALERTA DE ERROR ────────────────────────────────────── -->
<?php if (!empty($error_msg)): ?>
<div class="alert alert-danger rounded-3 d-flex gap-3 align-items-start mb-4 shadow-sm" role="alert">
    <i class="fas fa-exclamation-circle fs-4 mt-1 flex-shrink-0"></i>
    <div><?= sanitizar_html($error_msg) ?></div>
</div>
<?php endif; ?>

<!-- ── PANTALLA: SOLICITUD ENVIADA ───────────────────────── -->
<?php if ($success_msg === 'solicitud_enviada' || $tiene_pendiente): ?>

<div class="card border-0 shadow-sm rounded-4 p-5 text-center mb-4"
     style="background:linear-gradient(135deg,#ECFDF5,#D1FAE5);border-left:5px solid #16A34A;">
    <div class="mb-4">
        <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle"
             style="width:80px;height:80px;background:linear-gradient(135deg,#16A34A,#15803D);">
            <i class="fas fa-hourglass-half text-white" style="font-size:2rem;"></i>
        </div>
        <h2 class="h4 fw-bold text-success mb-2">¡Solicitud Enviada con Éxito!</h2>
        <p class="text-success fw-semibold mb-1">
            Tu solicitud para el curso <em><?= sanitizar_html($curso['titulo_curso']) ?></em> está pendiente de aprobación.
        </p>
        <p class="text-muted small mb-4">
            El administrador verificará tu comprobante de pago y te dará acceso en el menor tiempo posible.
            Recibirás una notificación en tu panel cuando sea aprobada o si necesita más información.
        </p>
    </div>

    <?php if ($id_tx_pendiente > 0): ?>
    <div class="d-inline-block px-4 py-2 rounded-pill mb-4"
         style="background:rgba(22,163,74,.12);border:1px solid #86EFAC;">
        <i class="fas fa-hashtag me-1 text-success"></i>
        Referencia de solicitud: <strong class="text-success">#<?= $id_tx_pendiente ?></strong>
    </div>
    <?php endif; ?>

    <div class="d-flex justify-content-center gap-3 flex-wrap">
        <a href="dashboard.php" class="btn btn-success rounded-pill px-4 shadow-sm" id="btnIrDashboard">
            <i class="fas fa-home me-2"></i>Ir a mi Dashboard
        </a>
        <a href="notificaciones.php" class="btn btn-outline-success rounded-pill px-4" id="btnVerNotifs">
            <i class="fas fa-bell me-2"></i>Ver Notificaciones
        </a>
    </div>
</div>

<?php else: ?>

<!-- ── FORMULARIO DE INSCRIPCIÓN ─────────────────────────── -->
<div class="row g-4">

    <!-- Columna DERECHA: Resumen del curso -->
    <div class="col-lg-5 order-lg-2">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden sticky-top" style="top:90px;">

            <!-- Portada -->
            <div style="position:relative;padding-top:52%;overflow:hidden;background:#0F172A;">
                <img src="<?= $curso['imagen_portada'] ?: 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=600&q=80' ?>"
                     alt="<?= sanitizar_html($curso['titulo_curso']) ?>"
                     style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;">
                <span class="badge position-absolute"
                      style="top:.75rem;left:.75rem;background:<?= $curso['color_categoria'] ?: '#2563EB' ?>;font-size:.75rem;">
                    <?= sanitizar_html($curso['nombre_categoria']) ?>
                </span>
            </div>

            <div class="card-body p-4">
                <h2 class="h5 fw-bold text-primary mb-2"><?= sanitizar_html($curso['titulo_curso']) ?></h2>
                <p class="text-muted small mb-4" style="line-height:1.5;">
                    <?= sanitizar_html(mb_substr($curso['resumen_corto'] ?? '', 0, 120)) ?>…
                </p>

                <!-- Desglose de precios -->
                <hr class="opacity-25 my-3">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted small">Precio del curso:</span>
                    <span class="fw-semibold <?= $tiene_descuento ? 'text-muted text-decoration-line-through' : '' ?>">
                        <?= $precio_original > 0 ? MONEDA_SIMBOLO . number_format($precio_original, 0, ',', '.') . ' COP' : 'Gratis' ?>
                    </span>
                </div>
                <?php if ($tiene_descuento): ?>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-success small fw-medium">Descuento aplicado:</span>
                    <span class="text-success fw-semibold">
                        − <?= MONEDA_SIMBOLO . number_format($ahorro, 0, ',', '.') ?> COP
                    </span>
                </div>
                <?php endif; ?>
                <div class="d-flex justify-content-between align-items-center pt-2 border-top mt-2">
                    <span class="fw-bold">Total a Pagar:</span>
                    <span class="fw-bold fs-5 <?= $es_gratis ? 'text-success' : 'text-primary' ?>">
                        <?= $es_gratis ? '¡GRATIS!' : MONEDA_SIMBOLO . number_format($precio_final, 0, ',', '.') . ' COP' ?>
                    </span>
                </div>

                <!-- Lo que incluye -->
                <div class="mt-4 p-3 rounded-3" style="background:#F8FAFC;border:1px solid #E2E8F0;">
                    <p class="fw-bold small mb-2 text-primary">
                        <i class="fas fa-gift me-1"></i>Incluye:
                    </p>
                    <ul class="list-unstyled small text-muted mb-0">
                        <?php if ($curso['total_horas'] > 0): ?>
                        <li class="mb-1"><i class="fas fa-check-circle text-success me-2"></i><?= number_format((float)$curso['total_horas'], 0) ?>h de contenido en video</li>
                        <?php endif; ?>
                        <li class="mb-1"><i class="fas fa-check-circle text-success me-2"></i>Acceso de por vida</li>
                        <?php if ($curso['certificado_disponible']): ?>
                        <li class="mb-1"><i class="fas fa-check-circle text-success me-2"></i>Certificado de finalización</li>
                        <?php endif; ?>
                        <li><i class="fas fa-check-circle text-success me-2"></i>Acceso en móvil y PC</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Columna IZQUIERDA: Formulario de pago -->
    <div class="col-lg-7 order-lg-1">

        <?php if ($es_gratis): ?>
        <!-- ── CURSO GRATIS ────────────────────────────────── -->
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h2 class="h5 fw-bold mb-4" style="color:#16A34A;">
                <i class="fas fa-gift me-2"></i>¡Este curso es completamente gratuito!
            </h2>
            <div class="alert rounded-3 mb-4" style="background:#ECFDF5;border-color:#86EFAC;color:#14532D;" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                Solo necesitas confirmar tu inscripción para comenzar inmediatamente.
            </div>
            <form method="POST" id="formGratis">
                <?php imprimir_campo_csrf($pdo, 'inscripcion'); ?>
                <input type="hidden" name="medio_pago" value="0">
                <div class="d-grid">
                    <button type="submit" class="btn btn-success btn-lg rounded-3 fw-bold" id="btnGratis">
                        <i class="fas fa-graduation-cap me-2"></i>Inscribirme Gratis Ahora
                    </button>
                </div>
            </form>
        </div>

        <?php else: ?>
        <!-- ── FORMULARIO DE PAGO ──────────────────────────── -->
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <h2 class="h5 fw-bold text-primary mb-4">
                <i class="fas fa-wallet me-2"></i>Selecciona tu método de pago
            </h2>

            <form method="POST" id="formPago">
                <?php imprimir_campo_csrf($pdo, 'inscripcion'); ?>

                <?php if (empty($medios_pago)): ?>
                <!-- Sin medios de pago configurados -->
                <div class="alert alert-warning rounded-3 mb-4" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    No hay métodos de pago configurados. Por favor contacta al administrador.
                </div>
                <?php else: ?>

                <!-- Lista de medios de pago -->
                <div class="d-flex flex-column gap-3 mb-4" id="listaMediosPago">
                    <?php foreach ($medios_pago as $i => $mp): ?>
                    <label class="d-flex align-items-start gap-3 p-3 border rounded-3 cursor-pointer mp-option"
                           for="mp-<?= $mp['id_medio_pago_pk'] ?>"
                           id="label-mp-<?= $mp['id_medio_pago_pk'] ?>"
                           style="cursor:pointer;transition:border-color .2s,background .2s,box-shadow .2s;">
                        <input type="radio" name="medio_pago"
                               id="mp-<?= $mp['id_medio_pago_pk'] ?>"
                               value="<?= $mp['id_medio_pago_pk'] ?>"
                               <?= $i === 0 ? 'checked' : '' ?>
                               class="mt-1 flex-shrink-0"
                               onchange="seleccionarMedio(<?= $mp['id_medio_pago_pk'] ?>)">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="fw-bold text-primary"><?= sanitizar_html($mp['nombre_medio_pago']) ?></span>
                                <?php
                                $tipos = ['pasarela_online'=>'Online','transferencia_bancaria'=>'Transferencia','pse'=>'PSE','efectivo'=>'Efectivo'];
                                $tipo_label = $tipos[$mp['tipo_integracion']] ?? 'Otro';
                                ?>
                                <span class="badge" style="background:#EFF6FF;color:#1E40AF;font-size:.7rem;font-weight:700;">
                                    <?= $tipo_label ?>
                                </span>
                            </div>
                            <?php if ($mp['descripcion_medio_pago']): ?>
                            <p class="text-muted small mb-2"><?= sanitizar_html($mp['descripcion_medio_pago']) ?></p>
                            <?php endif; ?>
                            <?php if ($mp['instrucciones_pago']): ?>
                            <div class="p-2 rounded-2"
                                 style="background:#F0F9FF;border-left:3px solid #2563EB;font-size:.78rem;color:#1E40AF;line-height:1.6;white-space:pre-wrap;">
                                <i class="fas fa-info-circle me-1"></i><?= sanitizar_html($mp['instrucciones_pago']) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>

                <!-- Comprobante de pago -->
                <div class="mb-4 p-3 rounded-3" style="background:#FFFBEB;border:1px solid #FDE68A;">
                    <label class="form-label fw-semibold small" for="comprobante">
                        <i class="fas fa-file-invoice me-1 text-warning"></i>
                        Número de comprobante o referencia de tu pago
                    </label>
                    <input type="text" name="comprobante" id="comprobante"
                           class="form-control rounded-3 mt-1"
                           placeholder="Ej: TXN123456, Número de transacción bancaria...">
                    <div class="form-text mt-1 small">
                        <i class="fas fa-lightbulb me-1 text-warning"></i>
                        Si realizaste una transferencia o depósito, escribe el número de comprobante para agilizar la aprobación.
                    </div>
                </div>

                <!-- ¿Cómo funciona? -->
                <div class="alert rounded-3 mb-4" style="background:#EFF6FF;border-color:#BFDBFE;color:#1E40AF;" role="alert">
                    <div class="d-flex gap-3">
                        <i class="fas fa-shield-alt fs-4 mt-1 flex-shrink-0"></i>
                        <div>
                            <strong class="d-block mb-1">¿Cómo funciona el proceso?</strong>
                            <ol class="mb-0 ps-3 small" style="line-height:1.9;">
                                <li>Seleccionas el método de pago y realizas el pago</li>
                                <li>Envías tu solicitud con el número de comprobante</li>
                                <li>El administrador verifica y aprueba el pago</li>
                                <li><strong>Recibes una notificación</strong> y accedes al curso inmediatamente</li>
                            </ol>
                        </div>
                    </div>
                </div>

                <!-- Botón de envío -->
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg rounded-3 fw-bold shadow-sm" id="btnEnviarSolicitud">
                        <i class="fas fa-paper-plane me-2"></i>Enviar Solicitud de Inscripción
                    </button>
                </div>
                <p class="text-center text-muted small mt-3 mb-0">
                    <i class="fas fa-lock me-1 text-success"></i>
                    Solicitud protegida con token de seguridad CSRF
                </p>

                <?php endif; // fin if medios_pago ?>
            </form>
        </div>
        <?php endif; // fin if es_gratis ?>

    </div>
</div>
<?php endif; // fin if tiene_pendiente ?>

<script>
function seleccionarMedio(id) {
    document.querySelectorAll('.mp-option').forEach(function(el) {
        el.style.borderColor = '#E2E8F0';
        el.style.boxShadow   = '';
        el.style.background  = '#fff';
    });
    var label = document.getElementById('label-mp-' + id);
    if (label) {
        label.style.borderColor = '#2563EB';
        label.style.boxShadow   = '0 0 0 3px rgba(37,99,235,.12)';
        label.style.background  = '#EFF6FF';
    }
}
// Inicializar primer medio seleccionado
<?php if (!empty($medios_pago)): ?>
document.addEventListener('DOMContentLoaded', function() {
    seleccionarMedio(<?= (int)$medios_pago[0]['id_medio_pago_pk'] ?>);
});
<?php endif; ?>
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
