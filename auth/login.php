<?php
// /cursoonline/auth/login.php
// ============================================================
// Vista de Inicio de Sesión — EduTech Academy
// ============================================================

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/session.php';
require_once '../includes/csrf.php';

// Si ya tiene sesión activa, redirigir a su dashboard
iniciar_sesion_segura();
if (isset($_SESSION['id_usuario'])) {
    redirigir_segun_rol();
}

$pdo = obtenerConexion();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta Tags -->
    <title>Iniciar Sesión — EduTech Academy</title>
    <meta name="description" content="Ingresa a tu cuenta en EduTech Academy para acceder a tus cursos online de Ingeniería e Inteligencia Artificial.">
    <meta name="keywords" content="login, iniciar sesion, edutech academy, educacion online">
    <meta name="author" content="EduTech Academy">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS (Separado) -->
    <link href="../assets/css/auth.css" rel="stylesheet">
</head>
<body>

    <div class="auth-wrapper">
        <!-- Lado de la Imagen (Solo visible en pantallas medianas y grandes) -->
        <div class="auth-image auth-image-login">
            <div class="glass-circle circle-1"></div>
            <div class="glass-circle circle-2"></div>
            
            <h1 class="display-4 fw-bold mb-4 position-relative z-1">Aprende sin límites.</h1>
            <p class="fs-5 text-light opacity-75 position-relative z-1">
                Únete a la academia tecnológica más avanzada de Latinoamérica. Domina las habilidades del futuro hoy.
            </p>
            
            <div class="mt-5 position-relative z-1">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <i class="fas fa-check-circle text-info fs-4"></i>
                    <span class="fs-5">Cursos actualizados constantemente</span>
                </div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <i class="fas fa-check-circle text-info fs-4"></i>
                    <span class="fs-5">Profesores expertos en la industria</span>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <i class="fas fa-check-circle text-info fs-4"></i>
                    <span class="fs-5">Certificación con validez profesional</span>
                </div>
            </div>
        </div>

        <!-- Lado del Formulario -->
        <div class="auth-form-container">
            <div class="auth-form-wrapper">
                <a href="../index.php" class="brand-logo" id="lnk-logo-home">
                    <i class="fas fa-graduation-cap"></i> EduTech Academy
                </a>

                <h2 class="fw-bold mb-2">¡Bienvenido de nuevo! 👋</h2>
                <p class="text-muted mb-4">Ingresa tus credenciales para acceder a tu cuenta.</p>

                <!-- Alertas de estado en formulario -->
                <div class="alert alert-danger alert-custom" id="alert-error" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <span id="alert-error-text"></span>
                </div>
                
                <div class="alert alert-success alert-custom" id="alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <span id="alert-success-text"></span>
                </div>

                <form id="loginForm" autocomplete="off">
                    <!-- Token CSRF Oculto para validación de origen en backend -->
                    <?php imprimir_campo_csrf($pdo, 'login'); ?>
                    <input type="hidden" name="accion" value="login">

                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="correo" name="correo" placeholder="name@example.com" required>
                        <label for="correo"><i class="fas fa-envelope me-2"></i>Correo Electrónico</label>
                    </div>

                    <div class="input-group mb-3">
                        <div class="form-floating flex-grow-1">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                            <label for="password"><i class="fas fa-lock me-2"></i>Contraseña</label>
                        </div>
                        <span class="input-group-text" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="rememberMe">
                            <label class="form-check-label text-muted" for="rememberMe">
                                Recordarme
                            </label>
                        </div>
                        <a href="recuperar-contrasena.php" class="auth-link small" id="lnk-forgot-pass">¿Olvidaste tu contraseña?</a>
                    </div>

                    <button type="submit" class="btn btn-auth w-100" id="btnSubmit">
                        <span class="spinner-border spinner-border-sm btn-spinner" role="status" aria-hidden="true"></span>
                        <span id="btnText">Iniciar Sesión</span>
                    </button>
                </form>

                <div class="text-center mt-4">
                    <p class="text-muted">¿No tienes una cuenta? <a href="registro.php" class="auth-link" id="lnk-register">Regístrate aquí</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS (Separado) -->
    <script src="../assets/js/login.js"></script>

</body>
</html>
<?php
/*
 * ============================================================
 * RESUMEN DEL ARCHIVO: /cursoonline/auth/login.php
 * ============================================================
 * Vista del formulario de inicio de sesión público.
 *
 * Características:
 *   - Llama a iniciar_sesion_segura() y redirige si hay sesión.
 *   - Carga el token CSRF mediante imprimir_campo_csrf().
 *   - Estilos CSS importados externamente desde assets/css/auth.css.
 *   - Script JS importado externamente desde assets/js/login.js.
 *   - Implementación SEO y semántica correcta.
 * ============================================================
 */
?>
