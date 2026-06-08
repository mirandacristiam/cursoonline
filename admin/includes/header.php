<?php
// /cursoonline/admin/includes/header.php
// ============================================================
// Cabecera del Panel Administrativo — EduTech Academy
// Admin ubicado en: cursoonline/admin/
// ============================================================

ob_start(); // Output buffering para permitir header() después de includes

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
// Logout siempre con URL absoluta para evitar errores de profundidad de directorio
$ruta_logout    = BASE_URL . 'auth/logout.php';

// Notificaciones sin leer del admin
$stmt_nb = $pdo->prepare("
    SELECT COUNT(*)
    FROM notificaciones_usuario
    WHERE id_usuario_fk = :id
      AND estado_leida = 0
      AND estado_activo = 1
");
$stmt_nb->execute([':id' => $id_usuario]);
$nb_admin = (int)$stmt_nb->fetchColumn();
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
    <?php if (isset($page_css)): ?>
    <link href="<?= $page_css ?>" rel="stylesheet">
    <?php endif; ?>
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

    </aside>

    <!-- Overlay para cerrar sidebar en móvil -->
    <div class="sidebar-overlay" id="adminSidebarOverlay"></div>

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
                <!-- Dropdown Perfil / Cerrar Sesión (mismo estilo que student) -->
                <div class="dropdown">
                    <button class="btn p-0 border-0 dropdown-toggle d-flex align-items-center gap-2"
                            type="button" id="adminDropdownBtn" onclick="toggleAdminMenu()"
                            style="background:none;">
                        <div class="user-avatar" id="headerAvatar">
                            <img src="<?= $admin_user['foto_perfil'] ?: ADMIN_FOTO_URL . 'default-avatar.svg' ?>"
                                 style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                        </div>
                        <span class="fw-semibold d-none d-sm-inline text-dark"><?= sanitizar_html(explode(' ', $admin_user['primer_nombre'])[0]) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm rounded-3 border-0" id="adminDropdownMenu">
                        <li><a class="dropdown-item" href="<?= $prefix ?>perfil.php"><i class="fas fa-user-circle me-2 text-primary"></i>Mi Perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= $ruta_logout ?>"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <script>
        function toggleAdminMenu() {
            var m = document.getElementById('adminDropdownMenu');
            if (!m) return;
            m.style.display = (m.style.display === 'block') ? 'none' : 'block';
        }
        document.addEventListener('click', function(e) {
            var btn = document.getElementById('adminDropdownBtn');
            var men = document.getElementById('adminDropdownMenu');
            if (btn && men && men.style.display === 'block' && !btn.contains(e.target) && !men.contains(e.target)) {
                men.style.display = 'none';
            }
        });
        </script>

        <main class="admin-body">
