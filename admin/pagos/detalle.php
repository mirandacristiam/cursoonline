<?php
$page_title = 'Detalle de Transacción';
$page_css   = '../assets/css/pagos.css';
$page_script = '../assets/js/pagos.js';
require_once __DIR__ . '/../includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header('Location: index.php'); exit; }

$stmt = $pdo->prepare("
    SELECT t.*,
           CONCAT(u.primer_nombre, ' ', u.primer_apellido) AS nombre_estudiante,
           u.correo_electronico, u.numero_telefono, u.foto_perfil,
           c.titulo_curso, c.precio, c.imagen_portada,
           mp.nombre_medio_pago
    FROM transacciones_pago t
    JOIN usuarios u ON u.id_usuario_pk = t.id_usuario_fk
    JOIN cursos c ON c.id_curso_pk = t.id_curso_fk
    JOIN medios_pago mp ON mp.id_medio_pago_pk = t.id_medio_pago_fk
    WHERE t.id_transaccion_pk = :id AND t.estado_activo = 1
");
$stmt->execute([':id' => $id]);
$tx = $stmt->fetch();
if (!$tx) { header('Location: index.php'); exit; }

$estado_cfg = [
    'pendiente' => ['color' => '#F59E0B', 'label' => 'Pendiente', 'icon' => 'fa-hourglass-half'],
    'aprobada'  => ['color' => '#16A34A', 'label' => 'Aprobada',  'icon' => 'fa-check-circle'],
    'rechazada' => ['color' => '#DC2626', 'label' => 'Rechazada', 'icon' => 'fa-times-circle'],
    'cancelada' => ['color' => '#64748B', 'label' => 'Cancelada', 'icon' => 'fa-ban'],
];
$cfg = $estado_cfg[$tx['estado_transaccion']] ?? $estado_cfg['cancelada'];
?>
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h3 fw-bold text-primary mb-1">
            <i class="fas fa-receipt me-2"></i>Detalle de Transacción
        </h1>
        <p class="text-muted m-0">#<?= $tx['id_transaccion_pk'] ?> — <?= sanitizar_html($tx['numero_referencia']) ?></p>
    </div>
    <a href="index.php" class="btn btn-outline-secondary rounded-3">
        <i class="fas fa-arrow-left me-1"></i>Volver
    </a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#1E293B,#334155);color:#fff;">
                <span class="fw-bold"><i class="fas fa-info-circle me-2"></i>Información del Pago</span>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Estado</small>
                        <span class="badge rounded-pill px-3 py-2" style="background:<?= $cfg['color'] ?>22;color:<?= $cfg['color'] ?>;font-size:.85rem;">
                            <i class="fas <?= $cfg['icon'] ?> me-1"></i><?= $cfg['label'] ?>
                        </span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Número de Referencia</small>
                        <code style="font-size:.9rem;background:#F1F5F9;padding:4px 10px;border-radius:6px;"><?= sanitizar_html($tx['numero_referencia']) ?></code>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Monto Total</small>
                        <span class="fw-bold fs-5 text-primary"><?= MONEDA_SIMBOLO . number_format((float)$tx['monto_total'], 0, ',', '.') ?></span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Medio de Pago</small>
                        <span><?= sanitizar_html($tx['nombre_medio_pago']) ?></span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Fecha de Creación</small>
                        <span><?= date('d/m/Y H:i:s', strtotime($tx['fecha_creacion'])) ?></span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Última Modificación</small>
                        <span><?= $tx['fecha_modificacion'] ? date('d/m/Y H:i:s', strtotime($tx['fecha_modificacion'])) : '—' ?></span>
                    </div>
                    <?php if ($tx['observaciones']): ?>
                    <div class="col-12">
                        <small class="text-muted d-block">Observaciones</small>
                        <div class="p-3 rounded-3 mt-1" style="background:#F8FAFC;border:1px solid #E2E8F0;"><?= nl2br(sanitizar_html($tx['observaciones'])) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#1E3A5F,#2563EB);color:#fff;">
                <span class="fw-bold"><i class="fas fa-graduation-cap me-2"></i>Curso</span>
            </div>
            <div class="card-body p-4">
                <div class="d-flex gap-3 align-items-start">
                    <?php if ($tx['imagen_portada']): ?>
                    <img src="<?= sanitizar_html($tx['imagen_portada']) ?>" alt="" style="width:80px;height:60px;object-fit:cover;border-radius:8px;">
                    <?php endif; ?>
                    <div>
                        <h5 class="fw-bold mb-1"><?= sanitizar_html($tx['titulo_curso']) ?></h5>
                        <span class="text-muted small">Precio: <?= MONEDA_SIMBOLO . number_format((float)$tx['precio'], 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header border-0 py-3 px-4" style="background:linear-gradient(135deg,#0F2B4C,#1E40AF);color:#fff;">
                <span class="fw-bold"><i class="fas fa-user me-2"></i>Estudiante</span>
            </div>
            <div class="card-body p-4 text-center">
                <img src="<?= $tx['foto_perfil'] ?: ADMIN_FOTO_URL . 'default-avatar.svg' ?>"
                     alt="" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid #E2E8F0;">
                <h6 class="fw-bold mt-3 mb-1"><?= sanitizar_html($tx['nombre_estudiante']) ?></h6>
                <p class="text-muted small mb-2"><?= sanitizar_html($tx['correo_electronico']) ?></p>
                <?php if ($tx['numero_telefono']): ?>
                <p class="small mb-0"><i class="fas fa-phone me-1 text-muted"></i><?= sanitizar_html($tx['numero_telefono']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($tx['estado_transaccion'] === 'pendiente'): ?>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4 text-center">
                <p class="fw-semibold mb-3">Acciones disponibles</p>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-success btn-lg rounded-3"
                            onclick="abrirModal('aprobar', <?= $tx['id_transaccion_pk'] ?>, '<?= addslashes(sanitizar_html($tx['nombre_estudiante'])) ?>', '<?= addslashes(sanitizar_html($tx['titulo_curso'])) ?>', '<?= MONEDA_SIMBOLO . number_format((float)$tx['monto_total'], 0, ',', '.') ?> COP')">
                        <i class="fas fa-check me-1"></i>Aprobar Pago
                    </button>
                    <button type="button" class="btn btn-danger btn-lg rounded-3"
                            onclick="abrirModal('rechazar', <?= $tx['id_transaccion_pk'] ?>, '<?= addslashes(sanitizar_html($tx['nombre_estudiante'])) ?>', '<?= addslashes(sanitizar_html($tx['titulo_curso'])) ?>', '<?= MONEDA_SIMBOLO . number_format((float)$tx['monto_total'], 0, ',', '.') ?> COP')">
                        <i class="fas fa-times me-1"></i>Rechazar Pago
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODAL DE CONFIRMACIÓN (reutilizado del index.php) -->
<!-- ============================================================ -->
<div class="modal fade" id="modalProcesar" tabindex="-1" aria-labelledby="modalProcesarLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-0 pb-0" id="modalHeader">
                <h5 class="modal-title fw-bold" id="modalProcesarLabel">Procesar Pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form method="POST" action="procesar.php" id="formProcesar">
                <div class="modal-body px-4 pt-3">
                    <input type="hidden" name="id_transaccion" id="modalTxId">
                    <input type="hidden" name="accion" id="modalAccion">
                    <?php imprimir_campo_csrf($pdo, 'pago_admin'); ?>
                    <div class="p-3 rounded-3 mb-4" id="modalInfo" style="background:#F8FAFC;border:1px solid #E2E8F0;">
                        <div class="row g-2" style="font-size:.88rem;">
                            <div class="col-6 text-muted">Estudiante:</div>
                            <div class="col-6 fw-semibold" id="modalEstudiante"></div>
                            <div class="col-6 text-muted">Curso:</div>
                            <div class="col-6 fw-semibold" id="modalCurso"></div>
                            <div class="col-6 text-muted">Monto:</div>
                            <div class="col-6 fw-bold text-primary" id="modalMonto"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small" for="modalObs" id="labelObs">Observaciones (opcional)</label>
                        <textarea name="observaciones" id="modalObs" class="form-control rounded-3" rows="3" placeholder="Notas internas para esta transacción…"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn rounded-3 px-4 fw-bold" id="modalBtnConfirm">Confirmar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
