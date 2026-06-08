<?php
// /cursoonline/student/includes/header.php
// ============================================================
// Cabecera Común del Panel de Estudiante — EduTech Academy
// ============================================================

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/csrf.php';

// Validar que el usuario tenga el rol de estudiante
iniciar_sesion_segura();
requerir_rol(ROL_ESTUDIANTE);

$pdo = obtenerConexion();
$id_usuario = $_SESSION['id_usuario'];

// Obtener datos frescos del usuario estudiante
$stmt_user = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario_pk = :id AND estado_activo = 1");
$stmt_user->execute([':id' => $id_usuario]);
$estudiante = $stmt_user->fetch();

if (!$estudiante) {
    destruir_sesion();
    header("Location: " . BASE_URL . "auth/login.php?error=no_usuario");
    exit();
}

// Obtener número de notificaciones no leídas
$stmt_notif = $pdo->prepare("
    SELECT COUNT(*) 
    FROM notificaciones_usuario 
    WHERE id_usuario_fk = :id 
      AND estado_leida = 0 
      AND estado_activo = 1
");
$stmt_notif->execute([':id' => $id_usuario]);
$notificaciones_no_leidas = (int)$stmt_notif->fetchColumn();

// Página activa helper para colorear menú
$script_name = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Panel de Estudiante' ?> — EduTech Academy</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Student Custom Styles (Separado) -->
    <link href="<?= BASE_URL ?>student/assets/css/student.css" rel="stylesheet">
    <style>
    /* ── Fijar layout: body/html nunca scroll ──────────── */
    html, body {
        height: 100% !important;
        overflow: hidden !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    .dashboard-wrapper {
        height: 100vh !important;
        overflow: hidden !important;
    }
    .sidebar {
        position: fixed !important;
        top: 0 !important; left: 0 !important; bottom: 0 !important;
        width: 260px !important;
        z-index: 1050 !important;
        overflow-y: auto !important;
        display: flex !important;
        flex-direction: column !important;
    }
    .top-navbar {
        position: fixed !important;
        top: 0 !important;
        left: 260px !important;
        right: 0 !important;
        height: 64px !important;
        z-index: 900 !important;
        background: #fff !important;
        border-bottom: 1px solid #E2E8F0 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        padding: 0 1.5rem !important;
        box-shadow: 0 1px 4px rgba(0,0,0,0.06) !important;
    }
    .main-content {
        margin: 64px 0 0 260px !important;
        height: calc(100vh - 64px) !important;
        overflow: hidden !important;
    }
    .content-body {
        height: 100% !important;
        overflow-y: auto !important;
        padding: 1.75rem !important;
        background: #F1F5F9 !important;
        -webkit-overflow-scrolling: touch;
    }
    @media (max-width: 991.98px) {
        .sidebar { transform: translateX(-100%); transition: transform 0.3s ease; }
        .sidebar.active { transform: translateX(0); }
        .sidebar-overlay.active { display: block; }
        .top-navbar { left: 0 !important; }
        .main-content { margin: 64px 0 0 0 !important; }
    }
    </style>
</head>
<body>

    <div class="dashboard-wrapper">
        <!-- SIDEBAR DE NAVEGACIÓN -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="../index.php" class="sidebar-brand">
                    <i class="fas fa-graduation-cap"></i> EduTech
                </a>
            </div>
            
            <ul class="sidebar-menu">
                <li class="sidebar-item <?= $script_name === 'dashboard.php' ? 'active' : '' ?>">
                    <a href="dashboard.php" class="sidebar-link">
                        <i class="fas fa-chart-line"></i> Dashboard
                    </a>
                </li>
                <li class="sidebar-item <?= $script_name === 'mis-cursos.php' || $script_name === 'ver-clase.php' ? 'active' : '' ?>">
                    <a href="mis-cursos.php" class="sidebar-link">
                        <i class="fas fa-book-reader"></i> Mis Cursos
                    </a>
                </li>
                <li class="sidebar-item <?= $script_name === 'explorar-cursos.php' ? 'active' : '' ?>">
                    <a href="explorar-cursos.php" class="sidebar-link">
                        <i class="fas fa-compass"></i> Explorar Cursos
                    </a>
                </li>
                <li class="sidebar-item <?= $script_name === 'evaluaciones.php' ? 'active' : '' ?>">
                    <a href="evaluaciones.php" class="sidebar-link">
                        <i class="fas fa-file-signature"></i> Evaluaciones
                    </a>
                </li>
                <li class="sidebar-item <?= $script_name === 'mis-notas.php' ? 'active' : '' ?>">
                    <a href="mis-notas.php" class="sidebar-link">
                        <i class="fas fa-graduation-cap"></i> Mis Notas
                    </a>
                </li>
                <li class="sidebar-item <?= $script_name === 'historial-pagos.php' ? 'active' : '' ?>">
                    <a href="historial-pagos.php" class="sidebar-link">
                        <i class="fas fa-wallet"></i> Historial Pagos
                    </a>
                </li>
            </ul>
        </aside>

        <!-- Overlay para cerrar sidebar en móvil -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <!-- CONTENIDO PRINCIPAL -->
        <div class="main-content">
            <!-- Barra superior del panel -->
            <header class="top-navbar">
                <button class="btn btn-outline-secondary d-lg-none" id="sidebarCollapse">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="d-none d-md-block">
                    <h2 class="h5 fw-bold text-muted mb-0">Bienvenido de nuevo, <?= sanitizar_html($estudiante['primer_nombre'] . ' ' . $estudiante['primer_apellido']) ?></h2>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <!-- Notificaciones -->
                    <a href="notificaciones.php" class="position-relative text-muted" style="text-decoration:none;">
                        <i class="fas fa-bell fs-5"></i>
                        <?php if ($notificaciones_no_leidas > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                  style="font-size:0.6rem;"><?= $notificaciones_no_leidas ?></span>
                        <?php endif; ?>
                    </a>
                    <!-- Dropdown Perfil / Cerrar Sesión -->
                    <div class="dropdown">
                        <button class="btn p-0 border-0 dropdown-toggle d-flex align-items-center gap-2"
                                type="button" data-bs-toggle="dropdown" aria-expanded="false"
                                style="background:none;">
                            <img src="<?= $estudiante['foto_perfil'] ?: STUDENT_FOTO_URL . 'default-avatar.svg' ?>"
                                 alt="Avatar" id="headerAvatar"
                                 class="rounded-circle border border-2 border-primary"
                                 style="width:36px;height:36px;object-fit:cover;cursor:pointer;">
                            <span class="fw-semibold d-none d-sm-inline text-dark"><?= sanitizar_html($estudiante['primer_nombre']) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm rounded-3 border-0">
                            <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user-circle me-2 text-primary"></i>Mi Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                        </ul>
                    </div>
                </div>
            </header>
            
            <main class="content-body">
