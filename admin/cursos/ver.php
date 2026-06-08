<?php
// /cursoonline/admin/cursos/ver.php
// ============================================================
// Detalle de Curso — Panel Administrativo
// PHP: llamadas a SP, lógica de negocio
// HTML, CSS, JS separados en sus respectivos archivos
// ============================================================

$page_title = 'Detalle del Curso';
$page_script = '../assets/js/cursos.js';
$page_css    = '../assets/css/cursos.css';
require_once __DIR__ . '/../includes/header.php';

// ── Helpers ─────────────────────────────────────────────────
function sp_exec($pdo, $sql, $params = []) {
    $emulate = $pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    $stmt = $pdo->prepare($sql);
    $r = $stmt->execute($params);
    $stmt->closeCursor();
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, $emulate);
    return $r;
}

function sp_cursos($pdo, $sp_name, $params = []) {
    $placeholders = [];
    foreach ($params as $k => $v) { $placeholders[] = ':' . $k; }
    $sql = 'CALL ' . $sp_name . '(' . implode(',', $placeholders) . ')';
    $emulate = $pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, $emulate);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    return $rows;
}

// ── ID del curso ────────────────────────────────────────────
$id_curso = (int)($_GET['id'] ?? $_POST['id_curso_fk'] ?? 0);
if (!$id_curso) { header('Location: index.php'); exit(); }

// ── Mensajes ────────────────────────────────────────────────
$msg_ok  = limpiar_entrada($_GET['msg'] ?? '');
$msg_err = '';

// ── POST ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validar_token_csrf($pdo, $token)) {
        $msg_err = 'Token de seguridad inválido.';
    } else {
        $accion = $_POST['accion'] ?? '';
        try {
            switch ($accion) {
                case 'guardar_modulo':
                    sp_exec($pdo, 'CALL sp_admin_guardar_modulo(:id, :curso, :titulo, :desc, :horas, :orden, :usr, @nid)', [
                        ':id'    => (int)($_POST['id_modulo'] ?? 0),
                        ':curso' => $id_curso,
                        ':titulo'=> limpiar_entrada($_POST['titulo'] ?? ''),
                        ':desc'  => limpiar_entrada($_POST['descripcion'] ?? ''),
                        ':horas' => (int)($_POST['horas'] ?? 0),
                        ':orden' => (int)($_POST['orden'] ?? 1),
                        ':usr'   => $id_usuario,
                    ]);
                    $msg_ok = 'Módulo guardado.';
                    break;
                case 'eliminar_modulo':
                    sp_exec($pdo, 'CALL sp_admin_eliminar_modulo(:id, :usr)', [':id' => (int)($_POST['id_modulo'] ?? 0), ':usr' => $id_usuario]);
                    $msg_ok = 'Módulo eliminado.';
                    break;
                case 'guardar_clase':
                    sp_exec($pdo, 'CALL sp_admin_guardar_clase(:id, :modulo, :titulo, :desc, :video, :tipo_video, :duracion, :orden, :gratis, :usr, @nid)', [
                        ':id'         => (int)($_POST['id_clase'] ?? 0),
                        ':modulo'     => (int)($_POST['id_modulo_fk'] ?? 0),
                        ':titulo'     => limpiar_entrada($_POST['titulo'] ?? ''),
                        ':desc'       => limpiar_entrada($_POST['descripcion'] ?? ''),
                        ':video'      => limpiar_entrada($_POST['url_video'] ?? ''),
                        ':tipo_video' => limpiar_entrada($_POST['tipo_video'] ?? 'youtube'),
                        ':duracion'   => (int)($_POST['duracion'] ?? 0),
                        ':orden'      => (int)($_POST['orden'] ?? 1),
                        ':gratis'     => isset($_POST['es_gratuita']) ? 1 : 0,
                        ':usr'        => $id_usuario,
                    ]);
                    $msg_ok = 'Clase guardada.';
                    break;
                case 'eliminar_clase':
                    sp_exec($pdo, 'CALL sp_admin_eliminar_clase(:id, :usr)', [':id' => (int)($_POST['id_clase'] ?? 0), ':usr' => $id_usuario]);
                    $msg_ok = 'Clase eliminada.';
                    break;
                case 'guardar_competencia':
                    sp_exec($pdo, 'CALL sp_admin_guardar_competencia(:id, :curso, :desc, :icono, :orden, :usr, @nid)', [
                        ':id'    => (int)($_POST['id_competencia'] ?? 0),
                        ':curso' => $id_curso,
                        ':desc'  => limpiar_entrada($_POST['descripcion'] ?? ''),
                        ':icono' => limpiar_entrada($_POST['icono'] ?? 'fa-check'),
                        ':orden' => (int)($_POST['orden'] ?? 1),
                        ':usr'   => $id_usuario,
                    ]);
                    $msg_ok = 'Competencia guardada.';
                    break;
                case 'eliminar_competencia':
                    sp_exec($pdo, 'CALL sp_admin_eliminar_competencia(:id, :usr)', [':id' => (int)($_POST['id_competencia'] ?? 0), ':usr' => $id_usuario]);
                    $msg_ok = 'Competencia eliminada.';
                    break;
                case 'toggle_curso':
                    $id_curso_toggle = (int)($_POST['id_curso'] ?? 0);
                    $nuevo_estado    = (int)($_POST['nuevo_estado'] ?? 0);
                    if ($id_curso_toggle) {
                        sp_exec($pdo, 'CALL sp_admin_cambiar_estado_curso(:id, :est, :usr)', [
                            ':id' => $id_curso_toggle, ':est' => $nuevo_estado, ':usr' => $id_usuario,
                        ]);
                        $msg_ok = $nuevo_estado ? 'Curso activado.' : 'Curso desactivado.';
                    }
                    break;
            }
        } catch (PDOException $e) {
            error_log('[ADMIN VER CURSO] ' . $e->getMessage());
            $msg_err = 'Error en la operación.';
        }
    }
}

// ── Datos del curso ─────────────────────────────────────────
$curso = sp_cursos($pdo, 'sp_admin_obtener_curso', ['p_id_curso' => $id_curso]);
if (empty($curso)) { header('Location: index.php?msg=notfound'); exit(); }
$curso = $curso[0];

$stats       = sp_cursos($pdo, 'sp_admin_estadisticas_curso', ['p_id_curso' => $id_curso]);
$stats       = $stats[0] ?? [];
$modulos     = sp_cursos($pdo, 'sp_admin_listar_modulos', ['p_id_curso' => $id_curso]);
$competencias = sp_cursos($pdo, 'sp_admin_listar_competencias', ['p_id_curso' => $id_curso]);

$clases_all = [];
foreach ($modulos as $m) {
    $clases_all[(int)$m['id_modulo_pk']] = sp_cursos($pdo, 'sp_admin_listar_clases', ['p_id_modulo' => (int)$m['id_modulo_pk']]);
}
?>
<!-- ════════════════════════════════════════════════════════════
     HTML — Detalle del Curso
     ════════════════════════════════════════════════════════════ -->

<div class="page-header d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="index.php">Cursos</a></li>
                <li class="breadcrumb-item active"><?= sanitizar_html(mb_strimwidth($curso['titulo_curso'], 0, 60, '...')) ?></li>
            </ol>
        </nav>
        <h1 class="mb-1"><i class="fas fa-graduation-cap me-2 text-danger"></i><?= sanitizar_html($curso['titulo_curso']) ?></h1>
        <p class="mb-0"><?= sanitizar_html(mb_strimwidth($curso['resumen_corto'] ?? '', 0, 200, '...')) ?></p>
    </div>
    <div class="d-flex gap-2 mt-2 mt-lg-0">
        <a href="editar.php?id=<?= $id_curso ?>" class="btn-admin-outline btn-sm"><i class="fas fa-edit"></i> Editar</a>
        <?php if ((int)$curso['estado_activo']): ?>
        <form method="POST" style="display:inline;">
            <?php imprimir_campo_csrf($pdo, 'tog' . $id_curso); ?>
            <input type="hidden" name="accion" value="toggle_curso">
            <input type="hidden" name="id_curso" value="<?= $id_curso ?>">
            <input type="hidden" name="nuevo_estado" value="0">
            <button type="submit" class="btn btn-outline-warning btn-sm btn-confirm-modal rounded-3"
                    data-confirm="¿Despublicar este curso?"><i class="fas fa-eye-slash"></i> Despublicar</button>
        </form>
        <?php else: ?>
        <form method="POST" style="display:inline;">
            <?php imprimir_campo_csrf($pdo, 'tog' . $id_curso); ?>
            <input type="hidden" name="accion" value="toggle_curso">
            <input type="hidden" name="id_curso" value="<?= $id_curso ?>">
            <input type="hidden" name="nuevo_estado" value="1">
            <button type="submit" class="btn btn-outline-success btn-sm btn-confirm-modal rounded-3"
                    data-confirm="¿Publicar este curso?"><i class="fas fa-eye"></i> Publicar</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($msg_ok): ?>
<div class="alert alert-success auto-dismiss rounded-3 mb-4"><i class="fas fa-check-circle me-2"></i><?= sanitizar_html($msg_ok) ?></div>
<?php endif; ?>
<?php if ($msg_err): ?>
<div class="alert alert-danger rounded-3 mb-4"><?= sanitizar_html($msg_err) ?></div>
<?php endif; ?>

<!-- ── Fila de estadísticas ───────────────────────────────── -->
<div class="row g-3 mb-4">
    <?php
    $stats_cards = [
        ['label' => 'Inscripciones',   'value' => (int)($stats['total_inscripciones'] ?? 0),       'color' => 'var(--admin-blue)'],
        ['label' => 'Activas',         'value' => (int)($stats['inscripciones_activas'] ?? 0),       'color' => 'var(--admin-green)'],
        ['label' => 'Completadas',     'value' => (int)($stats['inscripciones_completadas'] ?? 0),   'color' => 'var(--admin-blue)'],
        ['label' => 'Canceladas',      'value' => (int)($stats['inscripciones_canceladas'] ?? 0),    'color' => 'var(--admin-amber)'],
        ['label' => 'Ingresos',        'value' => '$' . number_format((int)($stats['ingresos_totales'] ?? 0), 0, ',', '.'), 'color' => 'var(--admin-accent)'],
        ['label' => 'Progreso Prom.',  'value' => number_format((float)($stats['progreso_promedio'] ?? 0), 1) . '%', 'color' => 'var(--admin-purple)'],
    ];
    foreach ($stats_cards as $sc):
    ?>
    <div class="col-4 col-md-2">
        <div class="course-stat-card">
            <div class="course-stat-value" style="color:<?= $sc['color'] ?>"><?= $sc['value'] ?></div>
            <div class="course-stat-label"><?= $sc['label'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <!-- ── Columna izquierda: Módulos y Clases ─────────── -->
    <div class="col-lg-8">
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <span><i class="fas fa-layer-group me-2"></i>Módulos del Curso (<?= count($modulos) ?>)</span>
                <button type="button" class="btn-admin-primary btn-sm btn-modulo-action"
                        data-action="add" data-id-curso="<?= $id_curso ?>">
                    <i class="fas fa-plus"></i> Añadir Módulo
                </button>
            </div>
            <div class="admin-card-body p-0 module-list-container">
                <?php if (empty($modulos)): ?>
                <div class="text-center py-4 text-muted small"><i class="fas fa-folder-open me-2"></i>No hay módulos. Crea el primero.</div>
                <?php else: ?>
                <div class="accordion module-accordion" id="accordionModulos">
                    <?php foreach ($modulos as $i => $m): $mid = (int)$m['id_modulo_pk']; ?>
                    <div class="accordion-item border-0">
                        <h2 class="accordion-header d-flex align-items-center" id="modH<?= $mid ?>">
                            <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?> flex-grow-1" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#modC<?= $mid ?>">
                                <span class="me-2 fw-bold" style="color:var(--text-muted);font-size:.8rem;"><?= (int)$m['orden_modulo'] ?>.</span>
                                <?= sanitizar_html($m['titulo_modulo']) ?>
                                <span class="ms-2 badge bg-light text-muted fw-normal" style="font-size:.7rem;">
                                    <?= (int)$m['total_clases'] ?> clases · <?= (int)$m['total_horas_modulo'] ?>h
                                </span>
                            </button>
                            <div class="d-flex gap-1 pe-3 flex-shrink-0">
                                <button type="button" class="btn btn-outline-primary btn-sm py-1 px-2 btn-modulo-action"
                                        data-action="edit" data-id-curso="<?= $id_curso ?>" data-id-modulo="<?= $mid ?>"
                                        data-titulo="<?= sanitizar_html($m['titulo_modulo']) ?>"
                                        data-descripcion="<?= sanitizar_html($m['descripcion_modulo'] ?? '') ?>"
                                        data-horas="<?= (int)$m['total_horas_modulo'] ?>"
                                        data-orden="<?= (int)$m['orden_modulo'] ?>"
                                        title="Editar módulo">
                                    <i class="fas fa-edit" style="font-size:.8rem;"></i>
                                </button>
                                <form method="POST" style="display:inline;">
                                    <?php imprimir_campo_csrf($pdo, 'delm' . $mid); ?>
                                    <input type="hidden" name="accion" value="eliminar_modulo">
                                    <input type="hidden" name="id_modulo" value="<?= $mid ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm py-1 px-2 btn-delete-action"
                                            data-confirm="¿Eliminar el módulo «<?= sanitizar_html($m['titulo_modulo']) ?>» y todas sus clases?"
                                            title="Eliminar módulo">
                                        <i class="fas fa-trash" style="font-size:.8rem;"></i>
                                    </button>
                                </form>
                            </div>
                        </h2>
                        <div id="modC<?= $mid ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>">
                            <div class="accordion-body p-0">
                                <?php if (!empty($m['descripcion_modulo'])): ?>
                                <div class="px-3 pt-3 pb-1 text-muted small"><?= sanitizar_html($m['descripcion_modulo']) ?></div>
                                <?php endif; ?>
                                <div class="class-list">
                                    <?php $clases_mod = $clases_all[$mid] ?? []; ?>
                                    <?php if (empty($clases_mod)): ?>
                                    <div class="px-3 py-2 text-muted small"><i class="fas fa-video me-1"></i>Sin clases aún.</div>
                                    <?php else: ?>
                                    <?php foreach ($clases_mod as $j => $cl): ?>
                                    <div class="class-item">
                                        <div class="class-number"><?= $j + 1 ?></div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="fw-semibold small text-truncate"><?= sanitizar_html($cl['titulo_clase']) ?></div>
                                            <div class="text-muted" style="font-size:.7rem;">
                                                <?= (int)$cl['duracion_minutos'] ?> min
                                                <?php if ((int)$cl['es_clase_gratuita']): ?><span class="badge bg-success ms-1" style="font-size:.6rem;">Preview</span><?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-1 flex-shrink-0">
                                            <button type="button" class="btn btn-outline-primary btn-sm py-0 px-1 btn-clase-action"
                                                    data-action="edit" data-id-modulo="<?= $mid ?>" data-id-clase="<?= (int)$cl['id_clase_pk'] ?>"
                                                    data-titulo="<?= sanitizar_html($cl['titulo_clase']) ?>"
                                                    data-descripcion="<?= sanitizar_html($cl['descripcion_clase']) ?>"
                                                    data-url-video="<?= sanitizar_html($cl['url_video']) ?>"
                                                    data-tipo-video="<?= sanitizar_html($cl['tipo_video']) ?>"
                                                    data-duracion="<?= (int)$cl['duracion_minutos'] ?>"
                                                    data-orden="<?= (int)$cl['orden_clase'] ?>"
                                                    data-gratuita="<?= (int)$cl['es_clase_gratuita'] ?>"
                                                    title="Editar clase">
                                                <i class="fas fa-edit" style="font-size:.75rem;"></i>
                                            </button>
                                            <form method="POST" style="display:inline;">
                                                <?php imprimir_campo_csrf($pdo, 'delc' . $cl['id_clase_pk']); ?>
                                                <input type="hidden" name="accion" value="eliminar_clase">
                                                <input type="hidden" name="id_clase" value="<?= (int)$cl['id_clase_pk'] ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-1 btn-delete-action"
                                                        data-confirm="¿Eliminar la clase «<?= sanitizar_html($cl['titulo_clase']) ?>»?"
                                                        title="Eliminar clase">
                                                    <i class="fas fa-trash" style="font-size:.75rem;"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                    <div class="p-2 text-center border-top" style="border-color:var(--border-color);">
                                        <button type="button" class="btn btn-outline-primary btn-sm btn-clase-action"
                                                data-action="add" data-id-modulo="<?= $mid ?>">
                                            <i class="fas fa-plus"></i> Añadir Clase
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── Columna derecha: Info + Competencias ────────── -->
    <div class="col-lg-4">
        <div class="admin-card mb-3">
            <div class="admin-card-header"><span><i class="fas fa-info-circle me-2"></i>Información</span></div>
            <div class="admin-card-body p-3">
                <?php
                $info_rows = [
                    ['Categoría',  sanitizar_html($curso['nombre_categoria'] ?? '—')],
                    ['Nivel',      sanitizar_html($curso['nivel_dificultad'] ?? '—')],
                    ['Precio',     (float)$curso['precio'] > 0 ? '$' . number_format((float)$curso['precio'], 0, ',', '.') : 'Gratis'],
                    ['Docente',    sanitizar_html(($curso['profesor_nombre'] ?? '') . ' ' . ($curso['profesor_apellido'] ?? '')) ?: '—'],
                    ['Módulos / Clases', (int)($stats['total_modulos'] ?? 0) . ' / ' . (int)($stats['total_clases'] ?? 0)],
                    ['Evaluaciones', (int)($stats['total_evaluaciones'] ?? 0)],
                ];
                foreach ($info_rows as $ir):
                ?>
                <div class="d-flex justify-content-between mb-2 pb-2 border-bottom small">
                    <span class="text-muted"><?= $ir[0] ?></span>
                    <span class="fw-semibold"><?= $ir[1] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="admin-card mb-3">
            <div class="admin-card-header">
                <span><i class="fas fa-tasks me-2"></i>Competencias</span>
                <button type="button" class="btn-admin-primary btn-sm btn-competencia-action"
                        data-action="add" data-id-curso="<?= $id_curso ?>">
                    <i class="fas fa-plus" style="color:#fff;font-weight:900;"></i>
                </button>
            </div>
            <div class="admin-card-body p-0">
                <?php if (empty($competencias)): ?>
                <div class="text-center py-3 text-muted small">Sin competencias definidas.</div>
                <?php else: ?>
                <?php foreach ($competencias as $cp): ?>
                <div class="comp-item">
                    <div class="comp-icon" style="background:rgba(37,99,235,0.1);color:#2563EB;">
                        <i class="fas <?= sanitizar_html($cp['icono_competencia'] ?? 'fa-check') ?>" style="font-size:.85rem;"></i>
                    </div>
                    <div class="comp-text">
                        <div class="small fw-semibold"><?= sanitizar_html($cp['descripcion_competencia']) ?></div>
                    </div>
                    <div class="comp-actions">
                        <button type="button" class="btn btn-outline-primary btn-sm py-0 px-1 btn-competencia-action"
                                data-action="edit" data-id-curso="<?= $id_curso ?>" data-id-competencia="<?= (int)$cp['id_competencia_pk'] ?>"
                                data-descripcion="<?= sanitizar_html($cp['descripcion_competencia']) ?>"
                                data-icono="<?= sanitizar_html($cp['icono_competencia']) ?>"
                                data-orden="<?= (int)$cp['orden_visualizacion'] ?>"
                                title="Editar">
                            <i class="fas fa-edit" style="font-size:.7rem;"></i>
                        </button>
                        <form method="POST" style="display:inline;">
                            <?php imprimir_campo_csrf($pdo, 'delcp' . $cp['id_competencia_pk']); ?>
                            <input type="hidden" name="accion" value="eliminar_competencia">
                            <input type="hidden" name="id_competencia" value="<?= (int)$cp['id_competencia_pk'] ?>">
                            <button type="submit" class="btn btn-outline-danger btn-sm py-0 px-1 btn-delete-action"
                                    data-confirm="¿Eliminar esta competencia?" title="Eliminar">
                                <i class="fas fa-trash" style="font-size:.7rem;"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="admin-card">
            <div class="admin-card-header"><span><i class="fas fa-bolt me-2"></i>Acciones</span></div>
            <div class="admin-card-body d-flex flex-column gap-2">
                <a href="editar.php?id=<?= $id_curso ?>" class="btn-admin-blue w-100 justify-content-center"><i class="fas fa-edit"></i> Editar Curso</a>
                <?php if ((int)$curso['estado_activo']): ?>
                <form method="POST">
                    <?php imprimir_campo_csrf($pdo, 'tog2_' . $id_curso); ?>
                    <input type="hidden" name="accion" value="toggle_curso">
                    <input type="hidden" name="id_curso" value="<?= $id_curso ?>">
                    <input type="hidden" name="nuevo_estado" value="0">
                    <button type="submit" class="btn btn-outline-warning w-100 btn-sm btn-confirm-modal rounded-3"
                            data-confirm="¿Despublicar este curso?"><i class="fas fa-eye-slash"></i> Despublicar</button>
                </form>
                <?php else: ?>
                <form method="POST">
                    <?php imprimir_campo_csrf($pdo, 'tog2_' . $id_curso); ?>
                    <input type="hidden" name="accion" value="toggle_curso">
                    <input type="hidden" name="id_curso" value="<?= $id_curso ?>">
                    <input type="hidden" name="nuevo_estado" value="1">
                    <button type="submit" class="btn btn-outline-success w-100 btn-sm btn-confirm-modal rounded-3"
                            data-confirm="¿Publicar este curso?"><i class="fas fa-eye"></i> Publicar</button>
                </form>
                <?php endif; ?>
                <a href="index.php" class="btn btn-outline-secondary w-100 btn-sm rounded-3"><i class="fas fa-arrow-left"></i> Volver al listado</a>
            </div>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     MODALES (Módulo, Clase, Competencia)
     ════════════════════════════════════════════════════════════ -->

<!-- Modal Módulo -->
<div class="modal fade" id="modalModulo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <?php imprimir_campo_csrf($pdo, 'modulo_form'); ?>
                <input type="hidden" name="accion" value="guardar_modulo">
                <input type="hidden" name="id_modulo" id="modulo_id_modulo" value="0">
                <input type="hidden" name="id_curso_fk" id="modulo_id_curso_fk" value="<?= $id_curso ?>">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalModuloTitulo">Nuevo Módulo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Título del Módulo</label>
                        <input type="text" class="form-control form-control-sm" name="titulo" id="modulo_titulo" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Descripción</label>
                        <textarea class="form-control form-control-sm" name="descripcion" id="modulo_descripcion" rows="4"></textarea>
                    </div>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Horas</label>
                            <input type="number" class="form-control form-control-sm" name="horas" id="modulo_horas" value="0" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Orden</label>
                            <input type="number" class="form-control form-control-sm" name="orden" id="modulo_orden" value="1" min="1">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm rounded-3"><i class="fas fa-save me-1"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Clase -->
<div class="modal fade" id="modalClase" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <?php imprimir_campo_csrf($pdo, 'clase_form'); ?>
                <input type="hidden" name="accion" value="guardar_clase">
                <input type="hidden" name="id_clase" id="clase_id_clase" value="0">
                <input type="hidden" name="id_modulo_fk" id="clase_id_modulo_fk" value="0">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalClaseTitulo">Nueva Clase</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Título de la Clase</label>
                        <input type="text" class="form-control form-control-sm" name="titulo" id="clase_titulo" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Descripción</label>
                        <textarea class="form-control form-control-sm" name="descripcion" id="clase_descripcion" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">URL del Video</label>
                        <input type="url" class="form-control form-control-sm" name="url_video" id="clase_url_video" placeholder="https://www.youtube.com/watch?v=...">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label small fw-semibold">Tipo Video</label>
                            <select name="tipo_video" id="clase_tipo_video" class="form-select form-select-sm">
                                <option value="youtube">YouTube</option>
                                <option value="vimeo">Vimeo</option>
                                <option value="servidor_propio">Servidor</option>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-semibold">Duración (min)</label>
                            <input type="number" class="form-control form-control-sm" name="duracion" id="clase_duracion" value="0" min="0">
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-semibold">Orden</label>
                            <input type="number" class="form-control form-control-sm" name="orden" id="clase_orden" value="1" min="1">
                        </div>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="es_gratuita" id="clase_gratuita">
                        <label class="form-check-label small fw-semibold" for="clase_gratuita">Clase gratuita (preview)</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm rounded-3"><i class="fas fa-save me-1"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Competencia -->
<div class="modal fade" id="modalCompetencia" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST">
                <?php imprimir_campo_csrf($pdo, 'comp_form'); ?>
                <input type="hidden" name="accion" value="guardar_competencia">
                <input type="hidden" name="id_competencia" id="comp_id_competencia" value="0">
                <input type="hidden" name="id_curso_fk" id="comp_id_curso_fk" value="<?= $id_curso ?>">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalCompetenciaTitulo">Nueva Competencia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Descripción</label>
                        <textarea class="form-control form-control-sm" name="descripcion" id="comp_descripcion" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Icono</label>
                        <div class="icon-picker-grid" id="iconPickerGrid">
                            <?php
                            $fa_icons = [
                                'fa-check', 'fa-star', 'fa-heart', 'fa-bolt', 'fa-fire',
                                'fa-crown', 'fa-trophy', 'fa-medal', 'fa-award', 'fa-gem',
                                'fa-flag', 'fa-book', 'fa-graduation-cap', 'fa-pencil', 'fa-brain',
                                'fa-cogs', 'fa-code', 'fa-laptop-code', 'fa-database', 'fa-server',
                                'fa-shield-alt', 'fa-lock', 'fa-key', 'fa-chart-line', 'fa-chart-bar',
                                'fa-chart-pie', 'fa-calculator', 'fa-ruler', 'fa-flask', 'fa-microscope',
                                'fa-globe', 'fa-leaf', 'fa-handshake', 'fa-users', 'fa-user-tie',
                                'fa-lightbulb', 'fa-magic', 'fa-rocket', 'fa-puzzle-piece', 'fa-tools',
                            ];
                            foreach ($fa_icons as $icon):
                            ?>
                            <div class="icon-picker-item <?= $icon === 'fa-check' ? 'selected' : '' ?>"
                                 data-icon="<?= $icon ?>"
                                 title="<?= $icon ?>">
                                <i class="fas <?= $icon ?>"></i>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="icono" id="comp_icono" value="fa-check">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Orden</label>
                        <input type="number" class="form-control form-control-sm" name="orden" id="comp_orden" value="1" min="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-3" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm rounded-3"><i class="fas fa-save me-1"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
