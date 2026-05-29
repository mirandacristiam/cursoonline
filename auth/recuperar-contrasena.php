<?php
// /cursoonline/auth/recuperar-contrasena.php
// ============================================================
// Vista de Solicitud de Recuperación de Contraseña — EduTech Academy
// ============================================================

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/session.php';
require_once '../includes/csrf.php';

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
    <title>Recuperar Contraseña — EduTech Academy</title>
    <meta name="description" content="Solicita la recuperación de tu contraseña de EduTech Academy ingresando tu correo electrónico registrado.">
    <meta name="keywords" content="recuperar contraseña, cambiar contraseña, edutech academy">
    <meta name="author" content="EduTech Academy">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom CSS (Separado — sin estilos inline aquí) -->
    <link href="../assets/css/auth.css" rel="stylesheet">
</head>
<body>

    <div class="auth-card-wrapper">
        <div class="auth-card">

            <a href="../index.php" class="brand-logo justify-content-center mb-4" id="lnk-logo-home-rec">
                <i class="fas fa-graduation-cap"></i> EduTech Academy
            </a>

            <div class="icon-wrapper">
                <i class="fas fa-key"></i>
            </div>

            <h1 class="h3 fw-bold mb-2">Recuperar Contraseña</h1>
            <p class="text-muted mb-4">
                Ingresa el correo electrónico asociado a tu cuenta y te enviaremos las instrucciones para restablecerla.
            </p>

            <!-- Alertas de estado -->
            <div class="alert alert-danger alert-custom" id="alert-error" role="alert"></div>
            <div class="alert alert-success alert-custom" id="alert-success" role="alert"></div>

            <form id="recuperarForm" autocomplete="off">
                <!-- Token CSRF Oculto -->
                <?php imprimir_campo_csrf($pdo, 'recuperar'); ?>
                <input type="hidden" name="accion" value="solicitar_recuperacion">

                <div class="form-floating mb-4 text-start">
                    <input type="email" class="form-control" id="correo" name="correo"
                           placeholder="name@example.com" required>
                    <label for="correo"><i class="fas fa-envelope me-2"></i>Correo Electrónico</label>
                </div>

                <button type="submit" class="btn btn-auth w-100 mb-3" id="btnSubmit">
                    <span class="spinner-border spinner-border-sm me-2 btn-spinner" id="spinner" role="status"></span>
                    <span id="btnText">Enviar Enlace de Recuperación</span>
                </button>
            </form>

            <a href="login.php" class="auth-link text-muted small" id="lnk-back-login">
                <i class="fas fa-arrow-left me-1"></i> Volver a Iniciar Sesión
            </a>

            <!-- Caja para simular el envío del correo en entorno local -->
            <div class="email-simulator" id="emailSimulator">
                <p class="fw-bold text-primary mb-1">
                    <i class="fas fa-info-circle"></i> Simulación de Correo (Desarrollo Local):
                </p>
                <p class="mb-1 text-muted">Haz clic en el siguiente enlace simulado para continuar el flujo:</p>
                <a href="#" id="linkRestablecer" class="fw-bold text-decoration-none"></a>
            </div>

        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS (Separado) -->
    <script src="../assets/js/recuperar-contrasena.js"></script>

</body>
</html>
<?php
/*
 * ============================================================
 * RESUMEN DEL ARCHIVO: /cursoonline/auth/recuperar-contrasena.php
 * ============================================================
 * Vista para solicitar la recuperación de contraseña.
 *
 * Características:
 *   - Sin CSS inline — todos los estilos en assets/css/auth.css.
 *   - Usa clases .auth-card-wrapper y .auth-card para centrar la tarjeta.
 *   - Lógica de AJAX en assets/js/recuperar-contrasena.js.
 *   - Caja de simulación de correo para desarrollo local.
 *   - Token CSRF generado en backend.
 * ============================================================
 */
?>
