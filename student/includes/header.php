<?php
// /cursoonline/student/includes/header.php
// ============================================================
// Cabecera Común del Panel de Estudiante — EduTech Academy
// ============================================================

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../includes/security.php';

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
    <link href="assets/css/student.css" rel="stylesheet">
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
                <li class="sidebar-item <?= $script_name === 'perfil.php' ? 'active' : '' ?>">
                    <a href="perfil.php" class="sidebar-link">
                        <i class="fas fa-user-circle"></i> Perfil
                    </a>
                </li>
                <li class="sidebar-item <?= $script_name === 'notificaciones.php' ? 'active' : '' ?>">
                    <a href="notificaciones.php" class="sidebar-link">
                        <i class="fas fa-bell"></i> Notificaciones
                        <?php if ($notificaciones_no_leidas > 0): ?>
                            <span class="badge bg-danger ms-auto"><?= $notificaciones_no_leidas ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
            
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="sidebar-link text-danger">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </aside>

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
                    <!-- Notificaciones rápido -->
                    <a href="notificaciones.php" class="btn btn-light position-relative">
                        <i class="far fa-bell"></i>
                        <?php if ($notificaciones_no_leidas > 0): ?>
                            <span class="notification-badge-dot"></span>
                        <?php endif; ?>
                    </a>
                    <!-- Foto y Perfil -->
                    <div class="d-flex align-items-center gap-2">
                        <img src="<?= $estudiante['foto_perfil'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?ixlib=rb-4.0.3&auto=format&fit=crop&w=80&q=80' ?>" 
                             alt="Avatar" 
                             class="rounded-circle border border-2 border-primary" 
                             style="width: 36px; height: 36px; object-fit: cover;">
                        <span class="fw-semibold d-none d-sm-inline"><?= sanitizar_html($estudiante['primer_nombre']) ?></span>
                    </div>
                </div>
            </header>
            
            <main class="content-body">
