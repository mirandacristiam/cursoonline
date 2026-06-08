<?php
// /cursoonline/teacher/includes/header.php
// ============================================================
// Cabecera del Panel del Profesor — EduTech Academy
// ============================================================

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/security.php';

// Validar que el usuario sea Docente / Profesor
iniciar_sesion_segura();
requerir_rol(ROL_PROFESOR);

$pdo = obtenerConexion();
$id_usuario = $_SESSION['id_usuario'];

// Obtener datos del profesor actual
$stmt_prof = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario_pk = :id AND estado_activo = 1");
$stmt_prof->execute([':id' => $id_usuario]);
$profesor = $stmt_prof->fetch();

if (!$profesor) {
    destruir_sesion();
    header("Location: " . BASE_URL . "auth/login.php?error=no_profesor");
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

$script_name = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Panel del Docente' ?> — EduTech Profesor</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom Teacher Styles (Separado) -->
    <link href="assets/css/teacher.css" rel="stylesheet">
</head>
<body>

    <div class="teacher-wrapper">
        <!-- SIDEBAR DE NAVEGACIÓN -->
        <aside class="teacher-sidebar">
            <div class="sidebar-header">
                <a href="../index.php" class="sidebar-brand">
                    <i class="fas fa-graduation-cap"></i> PROFESOR
                </a>
            </div>
            
            <ul class="sidebar-menu">
                <li class="sidebar-item <?= $script_name === 'dashboard.php' ? 'active' : '' ?>">
                    <a href="dashboard.php" class="sidebar-link">
                        <i class="fas fa-chart-bar"></i> Dashboard
                    </a>
                </li>
                <li class="sidebar-item <?= $script_name === 'mis-grupos.php' ? 'active' : '' ?>">
                    <a href="mis-grupos.php" class="sidebar-link">
                        <i class="fas fa-users"></i> Mis Grupos
                    </a>
                </li>
                <li class="sidebar-item <?= $script_name === 'calificaciones.php' ? 'active' : '' ?>">
                    <a href="calificaciones.php" class="sidebar-link">
                        <i class="fas fa-check-double"></i> Calificar Actividades
                    </a>
                </li>
            </ul>
        </aside>

        <!-- CONTENIDO PRINCIPAL -->
        <div class="teacher-main">
            <!-- Barra superior -->
            <header class="teacher-navbar">
                <button class="btn btn-outline-secondary d-lg-none" id="teacherSidebarCollapse">
                    <i class="fas fa-bars"></i>
                </button>
                <div>
                    <h2 class="h5 fw-bold text-muted mb-0">Portal Docente — EduTech Academy</h2>
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
                            <img src="<?= $profesor['foto_perfil'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?ixlib=rb-4.0.3&auto=format&fit=crop&w=80&q=80' ?>"
                                 alt="Avatar"
                                 class="rounded-circle border border-2 border-success"
                                 style="width:36px;height:36px;object-fit:cover;cursor:pointer;">
                            <span class="fw-bold text-muted small d-none d-sm-inline">Prof. <?= sanitizar_html($profesor['primer_nombre']) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm rounded-3 border-0">
                            <li><a class="dropdown-item" href="perfil.php"><i class="fas fa-user-tie me-2 text-success"></i>Mi Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Cerrar Sesión</a></li>
                        </ul>
                    </div>
                </div>
            </header>
            
            <main class="teacher-body">
