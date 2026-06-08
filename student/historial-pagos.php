<?php
// /cursoonline/student/historial-pagos.php
// ============================================================
// Historial de Pagos y Compras del Estudiante
// ============================================================

$page_title = 'Historial de Pagos';
require_once __DIR__ . '/includes/header.php';

// Obtener transacciones del estudiante con datos completos para factura
$stmt = $pdo->prepare("
    SELECT t.id_transaccion_pk, t.numero_referencia, t.monto_total, 
           t.estado_transaccion, t.fecha_transaccion, t.fecha_creacion,
           t.observaciones, t.ip_origen_transaccion,
           c.titulo_curso, c.resumen_corto, c.precio, c.precio_con_descuento,
           mp.nombre_medio_pago, mp.descripcion_medio_pago,
           u.primer_nombre, u.primer_apellido, u.correo_electronico,
           u.numero_documento_identidad
    FROM transacciones_pago t
    JOIN cursos c ON t.id_curso_fk = c.id_curso_pk
    JOIN medios_pago mp ON t.id_medio_pago_fk = mp.id_medio_pago_pk
    JOIN usuarios u ON t.id_usuario_fk = u.id_usuario_pk
    WHERE t.id_usuario_fk = :id_user 
      AND t.estado_activo = 1
    ORDER BY t.fecha_transaccion DESC
");
$stmt->execute([':id_user' => $id_usuario]);
$transacciones = $stmt->fetchAll();
?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="h3 fw-bold text-primary mb-1">Historial de Pagos</h1>
        <p class="text-muted m-0">Aquí encontrarás todos tus recibos de pago y estado de compras en la plataforma.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-12">
        <div class="card-custom">
            <div class="card-header-custom bg-light"><i class="fas fa-history me-2"></i>Mis Transacciones</div>
            <div class="card-body-custom p-0">
                <?php if (empty($transacciones)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-receipt text-muted fs-1 mb-3"></i>
                        <p class="text-muted m-0">Aún no registras compras ni transacciones de pago en la plataforma.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle m-0 text-start">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Referencia</th>
                                    <th>Curso</th>
                                    <th>Método de Pago</th>
                                    <th>Monto</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th class="pe-4 text-center">Factura</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transacciones as $t): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-secondary small"><?= sanitizar_html($t['numero_referencia']) ?></td>
                                        <td class="fw-bold text-primary"><?= sanitizar_html($t['titulo_curso']) ?></td>
                                        <td><?= sanitizar_html($t['nombre_medio_pago']) ?></td>
                                        <td class="fw-bold">$<?= number_format($t['monto_total'], 0, ',', '.') ?> COP</td>
                                        <td class="small text-muted"><?= date('d/m/Y', strtotime($t['fecha_transaccion'])) ?></td>
                                        <td>
                                            <span class="badge <?= $t['estado_transaccion'] === 'aprobada' ? 'badge-active' : ($t['estado_transaccion'] === 'cancelada' ? 'bg-secondary' : 'badge-pending') ?>">
                                                <?= $t['estado_transaccion'] === 'aprobada' ? 'Aprobada' : ($t['estado_transaccion'] === 'rechazada' ? 'Rechazada' : ($t['estado_transaccion'] === 'cancelada' ? 'Cancelada' : 'Pendiente')) ?>
                                            </span>
                                        </td>
                                        <td class="pe-4 text-center">
                                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" 
                                                    data-bs-toggle="modal" data-bs-target="#facturaModal"
                                                    data-transaccion='<?= json_encode($t, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>'>
                                                <i class="fas fa-file-invoice me-1"></i>Ver Factura
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- MODAL FACTURA -->
<div class="modal fade" id="facturaModal" tabindex="-1" aria-labelledby="facturaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 bg-primary text-white rounded-top-4 py-3">
                <h5 class="modal-title fw-bold" id="facturaModalLabel">
                    <i class="fas fa-file-invoice me-2"></i>Factura Electrónica
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="facturaBody">
                <div class="text-center py-4 text-muted" id="facturaLoading">
                    <i class="fas fa-spinner fa-spin fs-3 mb-2"></i>
                    <p>Cargando factura...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById('facturaModal');
    if (!modal) return;

    modal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget;
        var data = JSON.parse(button.getAttribute('data-transaccion'));

        var loading = document.getElementById('facturaLoading');
        if (loading) loading.style.display = 'none';

        var body = document.getElementById('facturaBody');
        if (!body) return;

        var estado = data.estado_transaccion;
        var estadoLabel = estado === 'aprobada' ? 'Aprobada' : (estado === 'rechazada' ? 'Rechazada' : (estado === 'cancelada' ? 'Cancelada' : 'Pendiente'));
        var estadoBadgeClass = estado === 'aprobada' ? 'bg-success' : (estado === 'rechazada' ? 'bg-danger' : (estado === 'cancelada' ? 'bg-secondary' : 'bg-warning text-dark'));
        var precioOriginal = parseFloat(data.precio) || 0;
        var precioDescuento = parseFloat(data.precio_con_descuento) || 0;
        var tieneDescuento = precioDescuento > 0 && precioDescuento < precioOriginal;
        var descuentoAplicado = tieneDescuento ? (precioOriginal - precioDescuento) : 0;

        body.innerHTML = `
            <div class="row mb-4">
                <div class="col-6">
                    <h6 class="fw-bold text-muted small text-uppercase mb-1">Emisor</h6>
                    <p class="fw-bold mb-0" style="font-size:1.1rem;">EduTech Academy</p>
                    <p class="small text-muted mb-1">NIT: 901.123.456-7</p>
                    <p class="small text-muted mb-0">Colombia</p>
                </div>
                <div class="col-6 text-end">
                    <h6 class="fw-bold text-muted small text-uppercase mb-1">Factura No.</h6>
                    <p class="fw-bold mb-0 text-primary">${sanitizar(data.numero_referencia)}</p>
                    <p class="small text-muted mb-0">Fecha: ${formatDate(data.fecha_transaccion || data.fecha_creacion)}</p>
                    <span class="badge ${estadoBadgeClass} mt-1">${estadoLabel}</span>
                </div>
            </div>

            <hr class="opacity-25 my-3">

            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="fw-bold text-muted small text-uppercase mb-2">Cliente</h6>
                    <p class="fw-bold mb-1">${sanitizar(data.primer_nombre + ' ' + data.primer_apellido)}</p>
                    <p class="small text-muted mb-1">${sanitizar(data.correo_electronico)}</p>
                    ${data.numero_documento_identidad ? `<p class="small text-muted mb-0">CC: ${sanitizar(data.numero_documento_identidad)}</p>` : ''}
                </div>
            </div>

            <hr class="opacity-25 my-3">

            <h6 class="fw-bold text-muted small text-uppercase mb-3">Detalle de la Compra</h6>
            <table class="table table-bordered mb-4">
                <thead class="table-light">
                    <tr>
                        <th>Curso</th>
                        <th class="text-center" style="width:100px;">Precio</th>
                        <th class="text-center" style="width:80px;">Cant.</th>
                        <th class="text-end" style="width:130px;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <p class="fw-bold mb-0">${sanitizar(data.titulo_curso)}</p>
                            ${data.resumen_corto ? `<p class="small text-muted mb-0">${sanitizar(data.resumen_corto.substring(0, 80))}...</p>` : ''}
                        </td>
                        <td class="text-center align-middle">$${formatCOP(precioOriginal)}</td>
                        <td class="text-center align-middle">1</td>
                        <td class="text-end align-middle fw-bold">$${formatCOP(precioOriginal)}</td>
                    </tr>
                </tbody>
            </table>

            <div class="row justify-content-end">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless text-end">
                        ${tieneDescuento ? `
                        <tr>
                            <td class="text-muted">Descuento</td>
                            <td class="text-success fw-bold">- $${formatCOP(descuentoAplicado)}</td>
                        </tr>` : ''}
                        <tr>
                            <td class="text-muted">IVA (19%)</td>
                            <td class="fw-bold">$${formatCOP(Math.round(precioDescuento > 0 ? precioDescuento * 0.19 : precioOriginal * 0.19))}</td>
                        </tr>
                        <tr class="border-top">
                            <td class="fw-bold fs-6">Total</td>
                            <td class="fw-bold fs-6 text-primary">$${formatCOP(precioDescuento > 0 ? precioDescuento : precioOriginal)}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <hr class="opacity-25 my-3">

            <div class="row">
                <div class="col-6">
                    <h6 class="fw-bold text-muted small text-uppercase mb-1">Método de Pago</h6>
                    <p class="fw-bold mb-1">${sanitizar(data.nombre_medio_pago)}</p>
                    ${data.descripcion_medio_pago ? `<p class="small text-muted mb-0">${sanitizar(data.descripcion_medio_pago)}</p>` : ''}
                </div>
                <div class="col-6 text-end">
                    ${data.observaciones ? `
                    <h6 class="fw-bold text-muted small text-uppercase mb-1">Notas</h6>
                    <p class="small text-muted mb-0">${sanitizar(data.observaciones)}</p>` : ''}
                </div>
            </div>

            <div class="mt-4 p-3 rounded-3 text-center" style="background:#F0F9FF;border:1px solid #BFDBFE;">
                <p class="small text-muted mb-0">
                    <i class="fas fa-shield-alt text-primary me-1"></i>
                    Esta factura es un comprobante digital válido. Conserva tu número de referencia para cualquier reclamo o soporte.
                </p>
            </div>

            <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill" onclick="window.print()">
                    <i class="fas fa-print me-1"></i>Imprimir
                </button>
            </div>
        `;
    });

    modal.addEventListener('hidden.bs.modal', function() {
        var loading = document.getElementById('facturaLoading');
        if (loading) loading.style.display = '';
        var body = document.getElementById('facturaBody');
        if (body) body.innerHTML = '<div class="text-center py-4 text-muted" id="facturaLoading"><i class="fas fa-spinner fa-spin fs-3 mb-2"></i><p>Cargando factura...</p></div>';
    });
});

function sanitizar(str) {
    if (!str) return '';
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function formatCOP(valor) {
    if (isNaN(valor)) return '0';
    return Math.round(valor).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function formatDate(fecha) {
    if (!fecha) return '';
    var d = new Date(fecha);
    if (isNaN(d.getTime())) return fecha;
    return d.toLocaleDateString('es-CO', { year: 'numeric', month: '2-digit', day: '2-digit' });
}
</script>

<style>
@media print {
    .modal-dialog {
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .modal-content {
        border: none !important;
        border-radius: 0 !important;
        box-shadow: none !important;
    }
    .modal-header {
        background: #1A3C6E !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .btn-close, .btn-outline-primary {
        display: none !important;
    }
}
</style>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
