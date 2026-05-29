<?php
// /cursoonline/index.php
// ============================================================
// Landing Page Pública — EduTech Academy
// ============================================================

require_once 'config/constants.php';
require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'includes/csrf.php';
require_once 'includes/security.php';

iniciar_sesion_segura();

$pdo = obtenerConexion();

// --- 1. CONSULTA DE CATEGORÍAS ---
$stmt_cat = $pdo->query("
    SELECT * 
    FROM categorias_curso 
    WHERE estado_activo = 1 
    ORDER BY nombre_categoria ASC
");
$categorias = $stmt_cat->fetchAll();

// --- 2. CONSULTA DE CURSOS DIVERSIFICADOS ---
$stmt_cursos = $pdo->query("
    SELECT c.*, cat.nombre_categoria, cat.icono_categoria, cat.color_categoria,
           u.primer_nombre, u.primer_apellido
    FROM cursos c
    JOIN categorias_curso cat ON c.id_categoria_fk = cat.id_categoria_pk
    LEFT JOIN usuarios u ON c.id_profesor_fk = u.id_usuario_pk
    WHERE c.estado_activo = 1
    ORDER BY c.calificacion_promedio DESC, c.titulo_curso ASC
");
$cursos = $stmt_cursos->fetchAll();

// --- 3. CONSULTA DE TESTIMONIOS ---
$stmt_test = $pdo->query("
    SELECT t.*, u.primer_nombre, u.primer_apellido, u.foto_perfil
    FROM testimonios t
    JOIN usuarios u ON t.id_usuario_fk = u.id_usuario_pk
    WHERE t.estado_activo = 1
    ORDER BY t.fecha_creacion DESC
    LIMIT 3
");
$testimonios = $stmt_test->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta Tags -->
    <title>EduTech Academy — Cursos Online de Ingeniería e IA</title>
    <meta name="description" content="<?= SITE_DESCRIPTION ?>">
    <meta name="keywords" content="<?= SITE_KEYWORDS ?>">
    <meta name="author" content="<?= SITE_AUTHOR ?>">
    
    <!-- Open Graph (Facebook / LinkedIn) -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="EduTech Academy — Domina la Ingeniería y la IA">
    <meta property="og:description" content="<?= SITE_DESCRIPTION ?>">
    <meta property="og:url" content="<?= BASE_URL ?>">
    <meta property="og:image" content="<?= BASE_URL ?>assets/images/og-image.jpg">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom Style (Separado) -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

    <!-- BARRA DE NAVEGACIÓN -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top navbar-custom" id="main-nav">
        <div class="container">
            <a class="navbar-brand navbar-brand-custom" href="index.php" id="lnk-brand">
                <i class="fas fa-graduation-cap"></i> EduTech Academy
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom" href="#inicio">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom" href="#beneficios">Beneficios</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom" href="#cursos">Cursos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom" href="#testimonios">Testimonios</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom me-3" href="#contacto">Contacto</a>
                    </li>
                    
                    <?php if (isset($_SESSION['id_usuario'])): ?>
                        <li class="nav-item">
                            <a class="btn btn-custom-primary" href="auth/login.php" id="btn-panel"><i class="fas fa-desktop me-1"></i> Mi Panel</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item me-2">
                            <a class="nav-link nav-link-custom text-white" href="auth/login.php" id="lnk-login">Ingresar</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-custom-primary" href="auth/registro.php" id="btn-register">Registrarme</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- SECCIÓN HERO (PORTADA PRINCIPAL) -->
    <header class="hero-section" id="inicio">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="hero-title">Domina la <span class="hero-tag">Ingeniería</span> y la <span class="hero-tag">Inteligencia Artificial</span></h1>
                    <p class="hero-subtitle">Estudia a tu propio ritmo con cursos diseñados por expertos de la industria. Proyectos reales de nivel industrial con código real.</p>
                    <div class="d-flex gap-3">
                        <a href="#cursos" class="btn btn-custom-primary btn-lg" id="btn-hero-courses">Ver Cursos</a>
                        <a href="#contacto" class="btn btn-custom-outline btn-lg" id="btn-hero-contact">Consultar dudas</a>
                    </div>
                </div>
                <div class="col-lg-6 text-center d-none d-lg-block">
                    <!-- SVG de ilustración premium o mockup -->
                    <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80" alt="Plataforma de estudio EduTech" class="img-fluid rounded-4 shadow-lg border border-2 border-primary">
                </div>
            </div>
        </div>
    </header>

    <!-- SECCIÓN BENEFICIOS -->
    <section class="features-section" id="beneficios">
        <div class="container text-center">
            <span class="section-tagline">¿Por qué elegirnos?</span>
            <h2 class="section-title">El modelo de aprendizaje más avanzado</h2>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <article class="feature-card">
                        <div class="feature-icon-wrapper">
                            <i class="fas fa-brain"></i>
                        </div>
                        <h3 class="h4 fw-bold mb-3">Enfoque Industrial Real</h3>
                        <p class="text-muted m-0">No enseñamos solo teoría. Escribimos código funcional listo para integraciones, apis y algoritmos avanzados.</p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="feature-card">
                        <div class="feature-icon-wrapper">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <h3 class="h4 fw-bold mb-3">Evaluaciones Inteligentes</h3>
                        <p class="text-muted m-0">Nuestros quizzes y exámenes automatizados se habilitan solo cuando estás listo académico y técnicamente.</p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="feature-card">
                        <div class="feature-icon-wrapper">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h3 class="h4 fw-bold mb-3">Docentes con Experiencia</h3>
                        <p class="text-muted m-0">Aprende de ingenieros de sistemas expertos que han desarrollado arquitecturas para el sector corporativo.</p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <!-- SECCIÓN CURSOS (CATÁLOGO DINÁMICO) -->
    <section class="courses-section" id="cursos">
        <div class="container text-center">
            <span class="section-tagline">Nuestros Programas Académicos</span>
            <h2 class="section-title">Encuentra tu próximo desafío intelectual</h2>
            
            <!-- Filtros de categoría -->
            <div class="d-flex justify-content-center flex-wrap mb-5">
                <button class="category-filter-btn active" data-category="all" id="btn-filter-all">Todos los Cursos</button>
                <?php foreach ($categorias as $cat): ?>
                    <button class="category-filter-btn" 
                            data-category="<?= $cat['id_categoria_pk'] ?>" 
                            id="btn-filter-<?= $cat['id_categoria_pk'] ?>">
                        <?= sanitizar_html($cat['nombre_categoria']) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- Listado de Cursos -->
            <div class="row g-4 text-start">
                <?php if (empty($cursos)): ?>
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-info-circle text-muted fs-1 mb-3"></i>
                        <p class="text-muted">No hay cursos disponibles en este momento. Por favor, intenta más tarde.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($cursos as $curso): ?>
                        <div class="col-lg-4 col-md-6 course-card-col" data-category-id="<?= $curso['id_categoria_fk'] ?>">
                            <article class="course-card">
                                <div class="course-card-img-wrapper">
                                    <span class="course-card-badge" style="background-color: <?= $curso['color_categoria'] ?: '#2563EB' ?>;">
                                        <?= sanitizar_html($curso['nombre_categoria']) ?>
                                    </span>
                                    <a href="cursos/detalle.php?id=<?= $curso['id_curso_pk'] ?>">
                                        <img src="<?= $curso['imagen_portada'] ?: 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80' ?>" 
                                             alt="<?= sanitizar_html($curso['titulo_curso']) ?>" 
                                             class="course-card-img">
                                    </a>
                                </div>
                                <div class="course-card-body">
                                    <div class="course-card-meta">
                                        <span><i class="far fa-clock me-1"></i> <?= $curso['total_horas'] ?? '—' ?>h</span>
                                        <span><i class="far fa-star text-warning me-1"></i> <?= number_format((float)($curso['calificacion_promedio'] ?? 0), 1) ?></span>
                                    </div>
                                    <h3 class="course-card-title">
                                        <a href="cursos/detalle.php?id=<?= $curso['id_curso_pk'] ?>" class="text-decoration-none text-dark">
                                            <?= sanitizar_html($curso['titulo_curso']) ?>
                                        </a>
                                    </h3>
                                    <p class="course-card-text"><?= sanitizar_html($curso['resumen_corto']) ?></p>
                                    <div class="course-card-footer">
                                        <span class="course-price">
                                            <?php if ($curso['precio'] > 0): ?>
                                                <?= MONEDA_SIMBOLO . number_format((float)$curso['precio'], 0, ',', '.') ?> COP
                                            <?php else: ?>
                                                <span class="text-success fw-bold">Gratis</span>
                                            <?php endif; ?>
                                        </span>
                                        <a href="cursos/detalle.php?id=<?= $curso['id_curso_pk'] ?>" 
                                           class="btn btn-primary btn-sm rounded-pill px-3" 
                                           id="btn-detail-<?= $curso['id_curso_pk'] ?>">
                                            Ver Curso
                                        </a>
                                    </div>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- SECCIÓN TESTIMONIOS -->
    <section class="testimonials-section" id="testimonios">
        <div class="container text-center">
            <span class="section-tagline">Nuestra Comunidad Opina</span>
            <h2 class="section-title">Historias de éxito de estudiantes reales</h2>
            
            <div class="row g-4 text-start">
                <?php if (empty($testimonios)): ?>
                    <!-- Testimonios Mockup si no hay en la BD -->
                    <div class="col-md-4">
                        <div class="testimony-card">
                            <span class="testimony-quote">“</span>
                            <p class="testimony-text">El curso de Algoritmos Genéticos me cambió la perspectiva. Explicado con un rigor matemático y código ejecutable real. ¡Altamente recomendado!</p>
                            <div class="testimony-student">
                                <img src="https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?ixlib=rb-4.0.3&auto=format&fit=crop&w=120&q=80" alt="Juan Gómez" class="testimony-avatar">
                                <div>
                                    <h4 class="testimony-name">Juan Carlos Gómez</h4>
                                    <p class="testimony-role">Ingeniero de Sistemas</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="testimony-card">
                            <span class="testimony-quote">“</span>
                            <p class="testimony-text">La automatización de notas y el panel de profesor es muy limpio. Como docente se me hace super fácil dar el seguimiento académico a mis estudiantes.</p>
                            <div class="testimony-student">
                                <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-4.0.3&auto=format&fit=crop&w=120&q=80" alt="Diana López" class="testimony-avatar">
                                <div>
                                    <h4 class="testimony-name">Diana María López</h4>
                                    <p class="testimony-role">Docente Investigadora</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="testimony-card">
                            <span class="testimony-quote">“</span>
                            <p class="testimony-text">Me registré gratis y compré el curso de Machine Learning. El soporte es increíble y el avance por módulos es muy estructurado. Aprendí muchísimo.</p>
                            <div class="testimony-student">
                                <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=120&q=80" alt="Mateo Pérez" class="testimony-avatar">
                                <div>
                                    <h4 class="testimony-name">Mateo Pérez</h4>
                                    <p class="testimony-role">Estudiante de Pregrado</p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($testimonios as $test): ?>
                        <div class="col-md-4">
                            <div class="testimony-card">
                                <span class="testimony-quote">“</span>
                                <p class="testimony-text"><?= sanitizar_html($test['mensaje_testimonio']) ?></p>
                                <div class="testimony-student">
                                    <img src="<?= $test['foto_perfil'] ?: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?ixlib=rb-4.0.3&auto=format&fit=crop&w=120&q=80' ?>" 
                                         alt="<?= sanitizar_html($test['primer_nombre']) ?>" 
                                         class="testimony-avatar">
                                    <div>
                                        <h4 class="testimony-name"><?= sanitizar_html($test['primer_nombre'] . ' ' . $test['primer_apellido']) ?></h4>
                                        <p class="testimony-role">Estudiante Certificado</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- SECCIÓN CONTACTO -->
    <section class="contact-section" id="contacto">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-5 mb-5 mb-lg-0">
                    <span class="section-tagline">Contáctanos</span>
                    <h2 class="fw-bold text-primary display-5 mb-4">¿Tienes alguna pregunta sobre nuestros programas?</h2>
                    <p class="text-muted mb-4">Nuestro equipo de ingenieros y asesores académicos está listo para darte toda la información de soporte para tu formación.</p>
                    
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="feature-icon-wrapper m-0"><i class="fas fa-envelope"></i></div>
                        <div>
                            <p class="fw-bold m-0">Correo Electrónico</p>
                            <a href="mailto:<?= SITE_EMAIL ?>" class="text-muted text-decoration-none"><?= SITE_EMAIL ?></a>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center gap-3">
                        <div class="feature-icon-wrapper m-0"><i class="fas fa-phone-alt"></i></div>
                        <div>
                            <p class="fw-bold m-0">Teléfono Corporativo</p>
                            <a href="tel:<?= SITE_PHONE ?>" class="text-muted text-decoration-none"><?= SITE_PHONE ?></a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-7">
                    <div class="contact-card">
                        <h3 class="fw-bold mb-4">Envíanos un mensaje directo</h3>
                        
                        <!-- Alertas de AJAX -->
                        <div class="alert alert-danger alert-custom" id="alert-contact-error" role="alert"></div>
                        <div class="alert alert-success alert-custom" id="alert-contact-success" role="alert"></div>

                        <form id="contactoForm" autocomplete="off">
                            <!-- Token CSRF -->
                            <?php imprimir_campo_csrf($pdo, 'contacto'); ?>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Tu Nombre Completo" required>
                                        <label for="nombre">Nombre Completo *</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="email" class="form-control" id="correo_contacto" name="correo" placeholder="name@example.com" required>
                                        <label for="correo_contacto">Correo Electrónico *</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="telefono" name="telefono" placeholder="Número de contacto">
                                        <label for="telefono">Teléfono (Opcional)</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="asunto" name="asunto" placeholder="Asunto del mensaje" required>
                                        <label for="asunto">Asunto del Mensaje *</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-floating mb-4">
                                <textarea class="form-control" placeholder="Escribe tu mensaje aquí..." id="mensaje" name="mensaje" style="height: 120px" required></textarea>
                                <label for="mensaje">Escribe tu mensaje aquí *</label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100 rounded-3" id="btnContactSubmit">Enviar Mensaje</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- PIE DE PÁGINA (FOOTER) -->
    <footer class="footer-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <a class="footer-brand" href="index.php">
                        <i class="fas fa-graduation-cap"></i> EduTech Academy
                    </a>
                    <p class="mt-3">Impulsando tu carrera técnica en programación, arquitectura de datos e Inteligencia Artificial.</p>
                    <div class="footer-socials">
                        <a href="#" class="footer-social-btn" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="footer-social-btn" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="footer-social-btn" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="footer-social-btn" aria-label="GitHub"><i class="fab fa-github"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-4">
                    <h4 class="footer-title">Enlaces Rápidos</h4>
                    <ul class="footer-links">
                        <li><a href="#inicio">Inicio</a></li>
                        <li><a href="#beneficios">Beneficios</a></li>
                        <li><a href="#cursos">Cursos</a></li>
                        <li><a href="#testimonios">Testimonios</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-4">
                    <h4 class="footer-title">Área Legal</h4>
                    <ul class="footer-links">
                        <li><a href="#">Términos de Servicio</a></li>
                        <li><a href="#">Políticas de Privacidad</a></li>
                        <li><a href="#">Políticas de Cookies</a></li>
                        <li><a href="#">Soporte Técnico</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-4">
                    <h4 class="footer-title">Contacto Académico</h4>
                    <p class="m-0"><i class="fas fa-map-marker-alt me-2 text-info"></i> <?= SITE_ADDRESS ?></p>
                    <p class="my-2"><i class="fas fa-envelope me-2 text-info"></i> <?= SITE_EMAIL ?></p>
                    <p class="m-0"><i class="fas fa-phone-alt me-2 text-info"></i> <?= SITE_PHONE ?></p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p class="m-0">&copy; <?= date('Y') ?> EduTech Academy. Todos los derechos reservados. Diseñado por The Builders.</p>
            </div>
        </div>
    </footer>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS (Separado) -->
    <script src="assets/js/main.js"></script>

</body>
</html>
<?php
/*
 * ============================================================
 * RESUMEN DEL ARCHIVO: /cursoonline/index.php
 * ============================================================
 * Portada principal y Landing Page del aplicativo EduTech Academy.
 *
 * Características:
 *   - Llama a iniciar_sesion_segura() para validar accesos rápidos.
 *   - Consulta dinámica de categorías, cursos y testimonios.
 *   - Estructura HTML5 semántica y SEO integrado (Meta tags, H1).
 *   - Diseño premium responsive con paleta de colores corporativos.
 *   - Separación física de estilos CSS (assets/css/style.css) e interacciones JS (assets/js/main.js).
 * ============================================================
 */
?>
