<?php
// /cursoonline/student/historial-pagos.php
// ============================================================
// Historial de Pagos y Compras del Estudiante
// ============================================================

$page_title = 'Historial de Pagos';
require_once __DIR__ . '/includes/header.php';

// Obtener transacciones del estudiante
$stmt = $pdo->prepare("
    SELECT t.id_transaccion_pk, t.numero_referencia, t.monto_total, t.estado_transaccion, t.fecha_transaccion,
           c.titulo_curso, mp.nombre_medio_pago
    FROM transacciones_pago t
    JOIN cursos c ON t.id_curso_fk = c.id_curso_pk
    JOIN medios_pago mp ON t.id_medio_pago_fk = mp.id_medio_pago_pk
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
                                    <th class="ps-4">Referencia de Pago</th>
                                    <th>Curso Adquirido</th>
                                    <th>Método de Pago</th>
                                    <th>Monto Pagado</th>
                                    <th>Fecha de Pago</th>
                                    <th class="pe-4">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transacciones as $t): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-secondary"><?= sanitizar_html($t['numero_referencia']) ?></td>
                                        <td class="fw-bold text-primary"><?= sanitizar_html($t['titulo_curso']) ?></td>
                                        <td><?= sanitizar_html($t['nombre_medio_pago']) ?></td>
                                        <td class="fw-bold">$<?= number_format($t['monto_total'], 0, ',', '.') ?> COP</td>
                                        <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($t['fecha_transaccion'])) ?></td>
                                        <td class="pe-4">
                                            <span class="badge <?= $t['estado_transaccion'] === 'aprobada' ? 'badge-active' : 'badge-pending' ?>">
                                                <?= $t['estado_transaccion'] === 'aprobada' ? 'Aprobada' : 'Pendiente' ?>
                                            </span>
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

<?php
require_once __DIR__ . '/includes/footer.php';
?>
