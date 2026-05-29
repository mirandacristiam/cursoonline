<?php
// /cursoonline/auth/restablecer-contrasena.php
// ============================================================
// Vista de Restablecimiento de Contraseña — EduTech Academy
// ============================================================

require_once '../config/constants.php';
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/csrf.php';
require_once '../includes/session.php';

iniciar_sesion_segura();
// Si ya tiene sesión activa, redirigir a su dashboard
if (isset($_SESSION['id_usuario'])) {
    redirigir_segun_rol();
}

$pdo = obtenerConexion();
$token = isset($_GET['token']) ? limpiar_entrada($_GET['token']) : '';
$token_valido = false;

if (!empty($token)) {
    // Validar el token antes de mostrar el formulario
    $stmt = $pdo->prepare("
        SELECT id_token_pk 
        FROM tokens_recuperacion_clave 
        WHERE token_recuperacion = :token 
          AND estado_usado = 0 
          AND fecha_expiracion > NOW()
    ");
    $stmt->execute([':token' => $token]);
    if ($stmt->rowCount() > 0) {
        $token_valido = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO Meta Tags -->
    <title>Restablecer Contraseña — EduTech Academy</title>
    <meta name="description" content="Establece una nueva contraseña segura para tu cuenta de EduTech Academy.">
    <meta name="keywords" content="cambiar contraseña, restablecer contraseña, edutech academy">
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

            <a href="../index.php" class="brand-logo justify-content-center mb-4" id="lnk-logo-home-res">
                <i class="fas fa-graduation-cap"></i> EduTech Academy
            </a>

            <?php if ($token_valido): ?>
                <!-- ======== FORMULARIO VÁLIDO ======== -->
                <div class="icon-wrapper">
                    <i class="fas fa-lock-open"></i>
                </div>

                <h1 class="h3 fw-bold mb-2">Crear nueva contraseña</h1>
                <p class="text-muted mb-4">
                    Ingresa tu nueva contraseña a continuación. Asegúrate de que sea fuerte y segura (mínimo 8 caracteres).
                </p>

                <!-- Alertas de estado -->
                <div class="alert alert-danger alert-custom" id="alert-error" role="alert"></div>
                <div class="alert alert-success alert-custom" id="alert-success" role="alert"></div>

                <form id="restablecerForm" autocomplete="off">
                    <!-- Token CSRF Oculto -->
                    <?php imprimir_campo_csrf($pdo, 'restablecer'); ?>
                    <input type="hidden" name="accion" value="restablecer_contrasena">
                    <input type="hidden" name="token_recuperacion" value="<?= sanitizar_html($token) ?>">

                    <div class="input-group mb-3 text-start">
                        <div class="form-floating flex-grow-1">
                            <input type="password" class="form-control" id="password" name="password"
                                   placeholder="Nueva Contraseña" required minlength="8">
                            <label for="password"><i class="fas fa-lock me-2"></i>Nueva Contraseña</label>
                        </div>
                        <span class="input-group-text toggle-password" data-target="#password">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>
                    <!-- Indicador de fortaleza -->
                    <div class="password-strength mb-3" id="strengthBar"></div>

                    <div class="input-group mb-4 text-start">
                        <div class="form-floating flex-grow-1">
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm"
                                   placeholder="Confirmar Contraseña" required minlength="8">
                            <label for="password_confirm"><i class="fas fa-lock me-2"></i>Confirmar Contraseña</label>
                        </div>
                        <span class="input-group-text toggle-password" data-target="#password_confirm">
                            <i class="fas fa-eye"></i>
                        </span>
                    </div>

                    <button type="submit" class="btn btn-auth w-100 mb-3" id="btnSubmit">
                        <span class="spinner-border spinner-border-sm me-2 btn-spinner" id="spinner" role="status"></span>
                        <span id="btnText">Guardar Nueva Contraseña</span>
                    </button>
                </form>

            <?php else: ?>
                <!-- ======== TOKEN INVÁLIDO O EXPIRADO ======== -->
                <div class="icon-wrapper icon-error">
                    <i class="fas fa-times-circle"></i>
                </div>
                <h1 class="h3 fw-bold mb-2">Enlace Inválido</h1>
                <p class="text-muted mb-4">
                    El enlace para restablecer la contraseña es inválido o ha expirado. Por seguridad,
                    los enlaces de recuperación solo duran 1 hora y son de un solo uso.
                </p>
                <a href="recuperar-contrasena.php" class="btn btn-auth w-100 mb-3" id="btn-request-new">
                    <i class="fas fa-redo me-2"></i>Solicitar nuevo enlace
                </a>

            <?php endif; ?>

            <a href="login.php" class="auth-link text-muted small d-block mt-2" id="lnk-back-login-res">
                <i class="fas fa-arrow-left me-1"></i> Volver a Iniciar Sesión
            </a>

        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS (Separado) -->
    <script src="../assets/js/restablecer-contrasena.js"></script>

</body>
</html>
<?php
/*
 * ============================================================
 * RESUMEN DEL ARCHIVO: /cursoonline/auth/restablecer-contrasena.php
 * ============================================================
 * Vista para ingresar la nueva contraseña usando el token temporal.
 *
 * Características:
 *   - Sin CSS inline — todos los estilos en assets/css/auth.css.
 *   - Valida token de recuperación en backend antes de renderizar el formulario.
 *   - Si el token es inválido/expirado, muestra mensaje de error con enlace para reintentar.
 *   - Indicador de fortaleza de contraseña (via JS).
 *   - Lógica AJAX en assets/js/restablecer-contrasena.js.
 * ============================================================
 */
?>
