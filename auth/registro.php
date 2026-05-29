<?php
// /cursoonline/auth/registro.php
// ============================================================
// Vista de Registro de Estudiantes — EduTech Academy
// ============================================================

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/session.php';
require_once '../includes/csrf.php';

// Si ya tiene sesión, redirigir
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
    <title>Registro de Estudiante — EduTech Academy</title>
    <meta name="description" content="Regístrate como estudiante en EduTech Academy. Empieza a aprender sobre Inteligencia Artificial, Machine Learning y Programación hoy.">
    <meta name="keywords" content="registro, crear cuenta, estudiante, edutech academy, cursos gratis">
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

    <div class="auth-wrapper auth-wrapper-reverse">
        <!-- Lado de la Imagen -->
        <div class="auth-image auth-image-registro">
            <div class="glass-circle circle-1"></div>
            <div class="glass-circle circle-2"></div>
            
            <h1 class="display-4 fw-bold mb-4 position-relative z-1">Invierte en tu futuro.</h1>
            <p class="fs-5 text-light opacity-75 position-relative z-1">
                Al registrarte obtienes acceso inmediato a material gratuito y la posibilidad de inscribirte en programas de nivel industrial.
            </p>
            
            <div class="mt-5 position-relative z-1">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <i class="fas fa-graduation-cap text-info fs-4"></i>
                    <span class="fs-5">Cursos enfocados en Ingeniería Real</span>
                </div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <i class="fas fa-trophy text-info fs-4"></i>
                    <span class="fs-5">Evaluaciones automatizadas y notas reales</span>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <i class="fas fa-users text-info fs-4"></i>
                    <span class="fs-5">Comunidad estudiantil e interacción</span>
                </div>
            </div>
        </div>

        <!-- Lado del Formulario -->
        <div class="auth-form-container">
            <div class="auth-form-wrapper">
                <a href="../index.php" class="brand-logo" id="lnk-logo-home-reg">
                    <i class="fas fa-graduation-cap"></i> EduTech Academy
                </a>

                <h2 class="fw-bold mb-2">Crear Cuenta 🚀</h2>
                <p class="text-muted mb-4">Únete como estudiante y comienza a aprender hoy mismo.</p>

                <!-- Alertas de estado en formulario -->
                <div class="alert alert-danger alert-custom" id="alert-error" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> <span id="alert-error-text"></span>
                </div>
                
                <div class="alert alert-success alert-custom" id="alert-success" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <span id="alert-success-text"></span>
                </div>

                <form id="registroForm" autocomplete="off">
                    <!-- Token CSRF Oculto -->
                    <?php imprimir_campo_csrf($pdo, 'registro'); ?>
                    <input type="hidden" name="accion" value="registro">

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="nombres" name="nombres" placeholder="Nombres" required>
                                <label for="nombres">Nombres <span class="text-danger">*</span></label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="apellidos" name="apellidos" placeholder="Apellidos" required>
                                <label for="apellidos">Apellidos <span class="text-danger">*</span></label>
                            </div>
                        </div>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="correo" name="correo" placeholder="name@example.com" required>
                        <label for="correo"><i class="fas fa-envelope me-2"></i>Correo Electrónico <span class="text-danger">*</span></label>
                    </div>

                    <div class="input-group mb-3">
                        <div class="form-floating flex-grow-1">
                            <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required minlength="8">
                            <label for="password"><i class="fas fa-lock me-2"></i>Contraseña <span class="text-danger">*</span></label>
                        </div>
                        <span class="input-group-text toggle-password" data-target="#password">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    
                    <div class="input-group mb-4">
                        <div class="form-floating flex-grow-1">
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Confirmar Contraseña" required minlength="8">
                            <label for="password_confirm"><i class="fas fa-lock me-2"></i>Confirmar Contraseña <span class="text-danger">*</span></label>
                        </div>
                        <span class="input-group-text toggle-password" data-target="#password_confirm">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>

                    <button type="submit" class="btn btn-auth w-100" id="btnSubmit">
                        <span class="spinner-border spinner-border-sm btn-spinner" role="status" aria-hidden="true"></span>
                        <span id="btnText">Registrarme Ahora</span>
                    </button>
                    
                    <div class="text-center mt-3 text-muted small">
                        Al registrarte, aceptas nuestros <a href="#" class="auth-link">Términos y Condiciones</a>.
                    </div>
                </form>

                <div class="text-center mt-4">
                    <p class="text-muted">¿Ya tienes una cuenta? <a href="login.php" class="auth-link" id="lnk-login-reg">Inicia sesión</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS (Separado) -->
    <script src="../assets/js/registro.js"></script>

</body>
</html>
<?php
/*
 * ============================================================
 * RESUMEN DEL ARCHIVO: /cursoonline/auth/registro.php
 * ============================================================
 * Vista del formulario de registro de estudiantes.
 *
 * Características:
 *   - Estilos CSS importados externamente desde assets/css/auth.css.
 *   - Lógica JS importada desde assets/js/registro.js.
 *   - Genera token CSRF para validación.
 *   - Diseño responsivo y moderno.
 * ============================================================
 */
?>
