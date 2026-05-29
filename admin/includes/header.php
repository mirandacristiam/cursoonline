<?php
// /cursoonline/admin/includes/header.php
// ============================================================
// Cabecera del Panel Administrativo — EduTech Academy
// Admin ubicado en: cursoonline/admin/
// ============================================================

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/csrf.php';

// Validar que el usuario sea Administrador Total
iniciar_sesion_segura();
requerir_rol(ROL_ADMIN_TOTAL);

$pdo        = obtenerConexion();
$id_usuario = $_SESSION['id_usuario'];

// Obtener datos del admin actual
$stmt_admin = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario_pk = :id AND estado_activo = 1");
$stmt_admin->execute([':id' => $id_usuario]);
$admin_user = $stmt_admin->fetch();

if (!$admin_user) {
    destruir_sesion();
    header("Location: " . BASE_URL . "auth/login.php?error=no_admin");
    exit();
}

$script_name = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// ── Rutas dinámicas según profundidad de subdirectorio ────────
// admin/          → current_dir = 'admin'
// admin/usuarios/ → current_dir = 'usuarios'
$is_root   = ($current_dir === 'admin');
$prefix    = $is_root ? '' : '../';

$ruta_css       = $prefix . 'assets/css/admin.css';
$ruta_logo      = $prefix . 'index.php';
$ruta_dash      = $prefix . 'index.php';
$ruta_usuarios  = $prefix . 'usuarios/index.php';
$ruta_cursos    = $prefix . 'cursos/index.php';
$ruta_pagos     = $prefix . 'pagos/index.php';
$ruta_reportes  = $prefix . 'reportes/index.php';
$ruta_seguridad = $prefix . 'seguridad/index.php';
$ruta_logout    = $prefix . '../auth/logout.php';

// Notificaciones sin leer del admin
$nb_admin = 0;
try {
    $stmt_nb = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE id_usuario_fk = ? AND leida = 0");
    $stmt_nb->execute([$id_usuario]);
    $nb_admin = (int)$stmt_nb->fetchColumn();
} catch (PDOException $e) { /* silencioso */ }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Panel Administrativo' ?> — EduTech Admin</title>

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Admin Stylesheet (Separado) -->
    <link href="<?= $ruta_css ?>" rel="stylesheet">
</head>
<body>

<div class="admin-wrapper">
    <!-- ── SIDEBAR ─────────────────────────────────────────── -->
    <aside class="admin-sidebar">
        <div class="sidebar-header">
            <a href="<?= $ruta_logo ?>" class="sidebar-brand">
                <i class="fas fa-shield-halved"></i> EDU-ADMIN
            </a>
        </div>

        <span class="sidebar-section-label">Principal</span>
        <ul class="sidebar-menu">
            <li class="sidebar-item <?= ($script_name === 'index.php' && $is_root) ? 'active' : '' ?>">
                <a href="<?= $ruta_dash ?>" class="sidebar-link">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
        </ul>

        <span class="sidebar-section-label">Catálogo</span>
        <ul class="sidebar-menu">
            <li class="sidebar-item <?= $current_dir === 'cursos' ? 'active' : '' ?>">
                <a href="<?= $ruta_cursos ?>" class="sidebar-link">
                    <i class="fas fa-graduation-cap"></i> Cursos
                </a>
            </li>
        </ul>

        <span class="sidebar-section-label">Personas</span>
        <ul class="sidebar-menu">
            <li class="sidebar-item <?= $current_dir === 'usuarios' ? 'active' : '' ?>">
                <a href="<?= $ruta_usuarios ?>" class="sidebar-link">
                    <i class="fas fa-users-cog"></i> Usuarios
                </a>
            </li>
        </ul>

        <span class="sidebar-section-label">Finanzas y Reportes</span>
        <ul class="sidebar-menu">
            <li class="sidebar-item <?= $current_dir === 'pagos' ? 'active' : '' ?>">
                <a href="<?= $ruta_pagos ?>" class="sidebar-link">
                    <i class="fas fa-cash-register"></i> Transacciones
                </a>
            </li>
            <li class="sidebar-item <?= $current_dir === 'reportes' ? 'active' : '' ?>">
                <a href="<?= $ruta_reportes ?>" class="sidebar-link">
                    <i class="fas fa-chart-bar"></i> Reportes
                </a>
            </li>
        </ul>

        <span class="sidebar-section-label">Sistema</span>
        <ul class="sidebar-menu">
            <li class="sidebar-item <?= $current_dir === 'seguridad' ? 'active' : '' ?>">
                <a href="<?= $ruta_seguridad ?>" class="sidebar-link">
                    <i class="fas fa-shield-alt"></i> Auditoría y Logs
                </a>
            </li>
        </ul>

        <div class="sidebar-footer">
            <a href="<?= $ruta_logout ?>" class="sidebar-link text-danger"
               onclick="return confirm('¿Cerrar sesión del panel de administración?')">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
            </a>
        </div>
    </aside>

    <!-- ── CONTENIDO PRINCIPAL ─────────────────────────────── -->
    <div class="admin-main">
        <!-- Navbar superior -->
        <header class="admin-navbar">
            <button class="btn btn-outline-secondary d-lg-none" id="adminSidebarCollapse">
                <i class="fas fa-bars"></i>
            </button>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-danger px-3 py-2 rounded-pill">
                    <i class="fas fa-shield-alt me-1"></i> PANEL TOTAL
                </span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <!-- Notificaciones -->
                <a href="<?= $prefix ?>notificaciones.php" class="position-relative text-muted" style="text-decoration:none;">
                    <i class="fas fa-bell fs-5"></i>
                    <?php if ($nb_admin > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                          style="font-size:0.6rem;"><?= $nb_admin ?></span>
                    <?php endif; ?>
                </a>
                <div class="d-flex align-items-center gap-2">
                    <div class="user-avatar" style="flex-shrink:0;">
                        <?= strtoupper(substr($admin_user['primer_nombre'], 0, 1) . substr($admin_user['primer_apellido'], 0, 1)) ?>
                    </div>
                    <div>
                        <div class="fw-bold small"><?= sanitizar_html($admin_user['primer_nombre'] . ' ' . $admin_user['primer_apellido']) ?></div>
                        <div class="text-muted" style="font-size:0.7rem;">Administrador Total</div>
                    </div>
                </div>
            </div>
        </header>

        <main class="admin-body">
