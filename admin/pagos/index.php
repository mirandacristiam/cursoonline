<?php
// /cursoonline/admin/pagos/index.php
// ============================================================
// Gestión de Transacciones y Aprobación de Pagos — Admin Panel
// EduTech Academy
// ============================================================

$page_title = 'Gestión de Pagos';
require_once __DIR__ . '/../includes/header.php';

// ── Filtros ────────────────────────────────────────────────────
$filtro_estado  = isset($_GET['estado'])  ? trim($_GET['estado'])   : 'pendiente';
$filtro_buscar  = isset($_GET['buscar'])  ? trim($_GET['buscar'])   : '';
$estados_validos = ['pendiente', 'aprobada', 'rechazada', 'cancelada', 'todos'];
if (!in_array($filtro_estado, $estados_validos)) $filtro_estado = 'pendiente';

// ── Query de transacciones ────────────────────────────────────
$where  = "WHERE t.estado_activo = 1";
$params = [];

if ($filtro_estado !== 'todos') {
    $where .= " AND t.estado_transaccion = :estado";
    $params[':estado'] = $filtro_estado;
}
if ($filtro_buscar !== '') {
    $where .= " AND (c.titulo_curso LIKE :buscar
                  OR CONCAT(u.primer_nombre,' ',u.primer_apellido) LIKE :buscar2
                  OR t.numero_referencia LIKE :buscar3)";
    $params[':buscar']  = '%' . $filtro_buscar . '%';
    $params[':buscar2'] = '%' . $filtro_buscar . '%';
    $params[':buscar3'] = '%' . $filtro_buscar . '%';
}

$stmt = $pdo->prepare("
    SELECT
        t.id_transaccion_pk,
        t.numero_referencia,
        t.monto_total,
        t.estado_transaccion,
        t.observaciones,
        t.fecha_creacion,
        t.fecha_modificacion,
        u.id_usuario_pk,
        CONCAT(u.primer_nombre, ' ', u.primer_apellido) AS nombre_estudiante,
        u.correo_electronico,
        c.id_curso_pk,
        c.titulo_curso,
        mp.nombre_medio_pago
    FROM transacciones_pago t
    JOIN usuarios u ON u.id_usuario_pk = t.id_usuario_fk
    JOIN cursos c   ON c.id_curso_pk   = t.id_curso_fk
    JOIN medios_pago mp ON mp.id_medio_pago_pk = t.id_medio_pago_fk
    $where
    ORDER BY
        CASE t.estado_transaccion WHEN 'pendiente' THEN 0 ELSE 1 END ASC,
        t.fecha_creacion DESC
    LIMIT 200
");
$stmt->execute($params);
$transacciones = $stmt->fetchAll();

// ── Contadores por estado ─────────────────────────────────────
$stmt_cnt = $pdo->query("
    SELECT estado_transaccion, COUNT(*) AS total
    FROM transacciones_pago
    WHERE estado_activo = 1
    GROUP BY estado_transaccion
");
$contadores = [];
foreach ($stmt_cnt->fetchAll() as $row) {
    $contadores[$row['estado_transaccion']] = (int)$row['total'];
}
$total_pendientes = $contadores['pendiente'] ?? 0;

// ── Mensajes de sesión ────────────────────────────────────────
$msg_ok  = $_SESSION['admin_msg_ok']  ?? '';
$msg_err = $_SESSION['admin_msg_err'] ?? '';
unset($_SESSION['admin_msg_ok'], $_SESSION['admin_msg_err']);
?>

<!-- ── ENCABEZADO ────────────────────────────────────────── -->
<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h3 fw-bold text-primary mb-1">
            <i class="fas fa-cash-register me-2"></i>Gestión de Pagos
        </h1>
        <p class="text-muted m-0">Revisa y aprueba las solicitudes de inscripción de los estudiantes.</p>
    </div>
    <?php if ($total_pendientes > 0): ?>
    <span class="badge bg-warning text-dark fs-6 px-3 py-2 rounded-pill shadow-sm">
        <i class="fas fa-exclamation-circle me-1"></i>
        <?= $total_pendientes ?> pendiente<?= $total_pendientes > 1 ? 's' : '' ?>
    </span>
    <?php endif; ?>
</div>

<!-- ── ALERTAS ───────────────────────────────────────────── -->
<?php if ($msg_ok): ?>
<div class="alert alert-success rounded-3 d-flex gap-3 align-items-center mb-4" role="alert">
    <i class="fas fa-check-circle fs-4"></i>
    <span><?= sanitizar_html($msg_ok) ?></span>
</div>
<?php endif; ?>
<?php if ($msg_err): ?>
<div class="alert alert-danger rounded-3 d-flex gap-3 align-items-center mb-4" role="alert">
    <i class="fas fa-exclamation-circle fs-4"></i>
    <span><?= sanitizar_html($msg_err) ?></span>
</div>
<?php endif; ?>

<!-- ── TARJETAS RESUMEN ─────────────────────────────────── -->
<div class="row g-3 mb-4">
    <?php
    $stats = [
        ['label' => 'Pendientes',  'key' => 'pendiente',  'color' => '#F59E0B', 'bg' => '#FFFBEB', 'icon' => 'fa-hourglass-half'],
        ['label' => 'Aprobadas',   'key' => 'aprobada',   'color' => '#16A34A', 'bg' => '#ECFDF5', 'icon' => 'fa-check-circle'],
        ['label' => 'Rechazadas',  'key' => 'rechazada',  'color' => '#DC2626', 'bg' => '#FEF2F2', 'icon' => 'fa-times-circle'],
        ['label' => 'Canceladas',  'key' => 'cancelada',  'color' => '#64748B', 'bg' => '#F8FAFC', 'icon' => 'fa-ban'],
    ];
    foreach ($stats as $s):
    ?>
    <div class="col-lg-3 col-md-6">
        <a href="?estado=<?= $s['key'] ?>" class="text-decoration-none">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100"
                 style="background:<?= $s['bg'] ?>;border-left:4px solid <?= $s['color'] ?> !important;transition:transform .2s;"
                 onmouseover="this.style.transform='translateY(-2px)'"
                 onmouseout="this.style.transform='translateY(0)'">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="small text-muted mb-1 text-uppercase fw-bold" style="font-size:.7rem;letter-spacing:.8px;">
                            <?= $s['label'] ?>
                        </p>
                        <h3 class="fw-bold mb-0" style="color:<?= $s['color'] ?>;font-size:2rem;">
                            <?= $contadores[$s['key']] ?? 0 ?>
                        </h3>
                    </div>
                    <div style="width:48px;height:48px;border-radius:50%;background:<?= $s['color'] ?>22;display:flex;align-items:center;justify-content:center;">
                        <i class="fas <?= $s['icon'] ?>" style="color:<?= $s['color'] ?>;font-size:1.3rem;"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <?php endforeach; ?>
</div>

<!-- ── FILTROS ───────────────────────────────────────────── -->
<div class="card border-0 shadow-sm rounded-4 mb-4 p-3">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-lg-5 col-md-6">
            <label class="form-label small fw-semibold text-muted" for="buscar">
                <i class="fas fa-search me-1"></i>Buscar
            </label>
            <input type="text" name="buscar" id="buscar"
                   class="form-control rounded-3"
                   placeholder="Estudiante, curso o referencia…"
                   value="<?= sanitizar_html($filtro_buscar) ?>">
        </div>
        <div class="col-lg-3 col-md-6">
            <label class="form-label small fw-semibold text-muted" for="estado">
                <i class="fas fa-filter me-1"></i>Estado
            </label>
            <select name="estado" id="estado" class="form-select rounded-3">
                <option value="todos"     <?= $filtro_estado === 'todos'     ? 'selected' : '' ?>>Todos</option>
                <option value="pendiente" <?= $filtro_estado === 'pendiente' ? 'selected' : '' ?>>⏳ Pendientes</option>
                <option value="aprobada"  <?= $filtro_estado === 'aprobada'  ? 'selected' : '' ?>>✅ Aprobadas</option>
                <option value="rechazada" <?= $filtro_estado === 'rechazada' ? 'selected' : '' ?>>❌ Rechazadas</option>
                <option value="cancelada" <?= $filtro_estado === 'cancelada' ? 'selected' : '' ?>>🚫 Canceladas</option>
            </select>
        </div>
        <div class="col-lg-4 col-md-12 d-flex gap-2">
            <button type="submit" class="btn btn-primary rounded-3 flex-grow-1" id="btnFiltrar">
                <i class="fas fa-filter me-1"></i>Filtrar
            </button>
            <a href="index.php" class="btn btn-outline-secondary rounded-3" title="Limpiar">
                <i class="fas fa-times"></i>
            </a>
        </div>
    </form>
</div>

<!-- ── TABLA DE TRANSACCIONES ──────────────────────────── -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="card-header border-0 py-3 px-4"
         style="background:linear-gradient(135deg,#1A3C6E,#2563EB);color:#fff;">
        <div class="d-flex justify-content-between align-items-center">
            <span class="fw-bold">
                <i class="fas fa-list me-2"></i>
                Transacciones
                <?php if ($filtro_estado !== 'todos'): ?>
                    — <span class="text-warning"><?= ucfirst($filtro_estado) ?></span>
                <?php endif; ?>
            </span>
            <span class="badge bg-white text-primary fw-bold px-3 py-1 rounded-pill">
                <?= count($transacciones) ?> registro<?= count($transacciones) !== 1 ? 's' : '' ?>
            </span>
        </div>
    </div>

    <?php if (empty($transacciones)): ?>
    <div class="text-center py-5">
        <i class="fas fa-search text-muted fs-1 mb-3 opacity-50"></i>
        <h3 class="h5 text-muted">No se encontraron transacciones</h3>
        <p class="text-muted small">Cambia los filtros para ver otras transacciones.</p>
    </div>

    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="tablaTransacciones">
            <thead style="background:#F8FAFC;font-size:.8rem;color:#64748B;text-transform:uppercase;letter-spacing:.5px;">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="py-3">Estudiante</th>
                    <th class="py-3">Curso</th>
                    <th class="py-3">Monto</th>
                    <th class="py-3">Medio de Pago</th>
                    <th class="py-3">Referencia</th>
                    <th class="py-3">Fecha</th>
                    <th class="py-3">Estado</th>
                    <th class="py-3 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transacciones as $tx):
                    $estado_cfg = [
                        'pendiente' => ['bg' => '#FEF3C7', 'color' => '#92400E', 'icon' => 'fa-hourglass-half', 'label' => 'Pendiente'],
                        'aprobada'  => ['bg' => '#DCFCE7', 'color' => '#14532D', 'icon' => 'fa-check-circle',   'label' => 'Aprobada'],
                        'rechazada' => ['bg' => '#FEE2E2', 'color' => '#7F1D1D', 'icon' => 'fa-times-circle',   'label' => 'Rechazada'],
                        'cancelada' => ['bg' => '#F1F5F9', 'color' => '#475569', 'icon' => 'fa-ban',            'label' => 'Cancelada'],
                    ];
                    $cfg = $estado_cfg[$tx['estado_transaccion']] ?? $estado_cfg['cancelada'];
                ?>
                <tr>
                    <td class="px-4 py-3 text-muted small fw-bold"><?= $tx['id_transaccion_pk'] ?></td>
                    <td class="py-3">
                        <div class="fw-semibold" style="font-size:.88rem;"><?= sanitizar_html($tx['nombre_estudiante']) ?></div>
                        <div class="text-muted" style="font-size:.75rem;"><?= sanitizar_html($tx['correo_electronico']) ?></div>
                    </td>
                    <td class="py-3" style="max-width:200px;">
                        <span style="font-size:.85rem;line-height:1.3;display:block;">
                            <?= sanitizar_html($tx['titulo_curso']) ?>
                        </span>
                    </td>
                    <td class="py-3 fw-bold text-primary">
                        <?php if ((float)$tx['monto_total'] > 0): ?>
                            <?= MONEDA_SIMBOLO . number_format((float)$tx['monto_total'], 0, ',', '.') ?>
                        <?php else: ?>
                            <span class="text-success">Gratis</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 small"><?= sanitizar_html($tx['nombre_medio_pago']) ?></td>
                    <td class="py-3">
                        <code style="font-size:.75rem;background:#F1F5F9;padding:3px 8px;border-radius:4px;">
                            <?= sanitizar_html($tx['numero_referencia']) ?>
                        </code>
                        <?php if ($tx['observaciones']): ?>
                        <div class="text-muted mt-1" style="font-size:.72rem;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                             title="<?= sanitizar_html($tx['observaciones']) ?>">
                            <?= sanitizar_html($tx['observaciones']) ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 small text-muted">
                        <?= date('d/m/Y', strtotime($tx['fecha_creacion'])) ?><br>
                        <span style="font-size:.7rem;"><?= date('H:i', strtotime($tx['fecha_creacion'])) ?></span>
                    </td>
                    <td class="py-3">
                        <span class="badge rounded-pill px-3 py-2"
                              style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['color'] ?>;font-size:.75rem;">
                            <i class="fas <?= $cfg['icon'] ?> me-1"></i><?= $cfg['label'] ?>
                        </span>
                    </td>
                    <td class="py-3 text-center">
                        <?php if ($tx['estado_transaccion'] === 'pendiente'): ?>
                        <div class="d-flex justify-content-center gap-2">
                            <!-- Botón Aprobar -->
                            <button type="button"
                                    class="btn btn-success btn-sm rounded-3 px-3"
                                    onclick="abrirModal('aprobar', <?= $tx['id_transaccion_pk'] ?>, '<?= addslashes(sanitizar_html($tx['nombre_estudiante'])) ?>', '<?= addslashes(sanitizar_html($tx['titulo_curso'])) ?>', '<?= MONEDA_SIMBOLO . number_format((float)$tx['monto_total'], 0, ',', '.') ?> COP')"
                                    id="btn-aprobar-<?= $tx['id_transaccion_pk'] ?>">
                                <i class="fas fa-check me-1"></i>Aprobar
                            </button>
                            <!-- Botón Rechazar -->
                            <button type="button"
                                    class="btn btn-danger btn-sm rounded-3 px-3"
                                    onclick="abrirModal('rechazar', <?= $tx['id_transaccion_pk'] ?>, '<?= addslashes(sanitizar_html($tx['nombre_estudiante'])) ?>', '<?= addslashes(sanitizar_html($tx['titulo_curso'])) ?>', '<?= MONEDA_SIMBOLO . number_format((float)$tx['monto_total'], 0, ',', '.') ?> COP')"
                                    id="btn-rechazar-<?= $tx['id_transaccion_pk'] ?>">
                                <i class="fas fa-times me-1"></i>Rechazar
                            </button>
                        </div>
                        <?php else: ?>
                        <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- ── MODAL DE CONFIRMACIÓN ───────────────────────────── -->
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

                    <!-- Info de la transacción -->
                    <div class="p-3 rounded-3 mb-4" id="modalInfo"
                         style="background:#F8FAFC;border:1px solid #E2E8F0;">
                        <div class="row g-2" style="font-size:.88rem;">
                            <div class="col-6 text-muted">Estudiante:</div>
                            <div class="col-6 fw-semibold" id="modalEstudiante"></div>
                            <div class="col-6 text-muted">Curso:</div>
                            <div class="col-6 fw-semibold" id="modalCurso"></div>
                            <div class="col-6 text-muted">Monto:</div>
                            <div class="col-6 fw-bold text-primary" id="modalMonto"></div>
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small" for="modalObs" id="labelObs">
                            Observaciones (opcional)
                        </label>
                        <textarea name="observaciones" id="modalObs"
                                  class="form-control rounded-3" rows="3"
                                  placeholder="Notas internas para esta transacción…"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4">
                    <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="submit" class="btn rounded-3 px-4 fw-bold" id="modalBtnConfirm">
                        Confirmar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
var modalEl = document.getElementById('modalProcesar');
var bsModal = new bootstrap.Modal(modalEl);

function abrirModal(accion, txId, estudiante, curso, monto) {
    document.getElementById('modalTxId').value    = txId;
    document.getElementById('modalAccion').value  = accion;
    document.getElementById('modalEstudiante').textContent = estudiante;
    document.getElementById('modalCurso').textContent      = curso;
    document.getElementById('modalMonto').textContent      = monto;
    document.getElementById('modalObs').value = '';

    var header = document.getElementById('modalHeader');
    var btn    = document.getElementById('modalBtnConfirm');
    var label  = document.getElementById('labelObs');
    var title  = document.getElementById('modalProcesarLabel');

    if (accion === 'aprobar') {
        header.style.background  = 'linear-gradient(135deg,#ECFDF5,#D1FAE5)';
        title.textContent        = '✅ Aprobar Pago';
        title.style.color        = '#14532D';
        btn.className            = 'btn btn-success rounded-3 px-4 fw-bold';
        btn.innerHTML            = '<i class="fas fa-check me-2"></i>Confirmar Aprobación';
        label.textContent        = 'Observaciones de aprobación (opcional)';
    } else {
        header.style.background  = 'linear-gradient(135deg,#FEF2F2,#FEE2E2)';
        title.textContent        = '❌ Rechazar Pago';
        title.style.color        = '#7F1D1D';
        btn.className            = 'btn btn-danger rounded-3 px-4 fw-bold';
        btn.innerHTML            = '<i class="fas fa-times me-2"></i>Confirmar Rechazo';
        label.textContent        = 'Motivo del rechazo (recomendado)';
    }

    bsModal.show();
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
