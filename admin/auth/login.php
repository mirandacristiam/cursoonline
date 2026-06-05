<?php
// /admin/auth/login.php
// Panel de control - Solo acceso a administradores
// ============================================================

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../../cursoonline/includes/session.php';
require_once '../../cursoonline/includes/csrf.php';

iniciar_sesion_segura();

// Si ya tiene sesión, redirigir
if (isset($_SESSION['id_usuario'])) {
    if ($_SESSION['id_rol'] === ROL_ADMIN_TOTAL) {
        header("Location: " . ADMIN_URL . "index.php");
        exit();
    } else {
        // Si no es admin, se saca del área administrativa
        header("Location: " . BASE_URL . "index.php");
        exit();
    }
}

$pdo = obtenerConexion();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta Tags (Panel Administrativo) -->
    <title>Acceso Restringido — EduTech Admin</title>
    <meta name="description" content="Acceso seguro al panel de control de EduTech Academy. Exclusivo para administradores del sistema.">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Administrative Custom CSS (Separado) -->
    <link href="../assets/css/auth.css" rel="stylesheet">
</head>
<body>

    <div class="auth-card">
        <div class="text-center">
            <div class="security-badge">
                <i class="fas fa-shield-alt"></i> Entorno Seguro
            </div>
            <a href="#" class="brand-logo" id="lnk-admin-logo">
                <i class="fas fa-lock"></i> ADMINISTRACIÓN
            </a>
        </div>

        <!-- Alerta de Error en Login -->
        <div class="alert alert-danger alert-custom bg-danger text-white" id="alert-error" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> <span id="alert-error-text"></span>
        </div>

        <form id="adminLoginForm" autocomplete="off">
            <!-- CSRF Token -->
            <?php imprimir_campo_csrf($pdo, 'login_admin'); ?>
            <input type="hidden" name="accion" value="login_admin">

            <div class="form-floating mb-3">
                <input type="email" class="form-control" id="correo" name="correo" placeholder="Admin Email" required>
                <label for="correo"><i class="fas fa-user-shield me-2"></i>Correo Administrativo</label>
            </div>

            <div class="input-group mb-4">
                <div class="form-floating flex-grow-1">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                    <label for="password"><i class="fas fa-key me-2"></i>Clave de Acceso</label>
                </div>
                <span class="input-group-text" id="togglePassword">
                    <i class="fas fa-eye"></i>
                </span>
            </div>

            <button type="submit" class="btn btn-admin" id="btnSubmit">
                <span class="spinner-border spinner-border-sm me-2" id="spinner" style="display:none;" role="status"></span>
                <span id="btnText">Acceder al Sistema</span>
            </button>
        </form>

        <div class="text-center mt-4">
            <p class="text-muted small m-0"><i class="fas fa-exclamation-circle me-1"></i> El acceso no autorizado está estrictamente prohibido y será registrado.</p>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Administrative Custom JS (Separado) -->
    <script src="../assets/js/login.js"></script>

</body>
</html>
<?php
/*
 * ============================================================
 * RESUMEN DEL ARCHIVO: /admin/auth/login.php
 * ============================================================
 * Vista de inicio de sesión exclusivo para administradores.
 *
 * Características:
 *   - Llama a iniciar_sesion_segura() y restringe accesos no autorizados.
 *   - CSS y JS separados físicamente en assets/ para cumplir normativas de código limpio.
 *   - Prevención activa de indexado en buscadores (noindex).
 * ============================================================
 */
?>
