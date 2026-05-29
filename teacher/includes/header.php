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
                <li class="sidebar-item <?= $script_name === 'notificaciones.php' ? 'active' : '' ?>">
                    <a href="notificaciones.php" class="sidebar-link">
                        <i class="fas fa-bell"></i> Notificaciones
                        <?php
                        $stmt_nb = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE id_usuario_fk = ? AND leida = 0");
                        $stmt_nb->execute([$id_usuario]);
                        $nb_count = (int)$stmt_nb->fetchColumn();
                        if ($nb_count > 0): ?>
                            <span class="badge bg-danger rounded-pill ms-auto" style="font-size:0.65rem;"><?= $nb_count ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="sidebar-item <?= $script_name === 'perfil.php' ? 'active' : '' ?>">
                    <a href="perfil.php" class="sidebar-link">
                        <i class="fas fa-user-tie"></i> Mi Perfil
                    </a>
                </li>
            </ul>
            
            <div class="sidebar-footer">
                <a href="#" class="sidebar-link text-danger" id="btnLogout">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
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
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold text-muted small">Prof: <?= sanitizar_html($profesor['primer_nombre'] . ' ' . $profesor['primer_apellido']) ?></span>
                    <i class="fas fa-user-tie text-success fs-5"></i>
                </div>
            </header>
            
            <main class="teacher-body">
