<?php
// /cursoonline/database/seed.php
// Compatible con PHP 7.3+
// ============================================================
// Script de inicialización de datos — EduTech Academy
// IMPORTANTE: Ejecutar UNA SOLA VEZ desde el navegador.
//             Eliminar o proteger este archivo después de usarlo.
//
// Acceso: http://localhost/cursoonline/database/seed.php
// ============================================================

// --- Mostrar errores solo durante el setup ---
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('America/Bogota');

// ============================================================
// CONFIGURACIÓN DE CONEXIÓN (directa, sin depender de config/)
// ============================================================
$db_host    = 'localhost';
$db_name    = 'db_edutechacademy';
$db_user    = 'root';
$db_pass    = '123456789';
$db_charset = 'utf8mb4';

// --- Registrar mensajes del proceso ---
$log_mensajes = [];

/**
 * Agrega un mensaje al log visual del proceso.
 */
function log_mensaje($tipo, $mensaje) {
    global $log_mensajes;
    $log_mensajes[] = ['tipo' => $tipo, 'texto' => $mensaje, 'hora' => date('H:i:s')];
    flush();
}

// ============================================================
// PASO 1: CREAR LA BASE DE DATOS SI NO EXISTE
// ============================================================
try {
    $pdo_sin_db = new PDO(
        "mysql:host={$db_host};charset={$db_charset}",
        $db_user,
        $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo_sin_db->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    log_mensaje('ok', "Base de datos '{$db_name}' verificada/creada correctamente.");
} catch (PDOException $e) {
    log_mensaje('error', "Error al crear la base de datos: " . $e->getMessage());
    mostrar_resultado_y_salir();
}

// ============================================================
// PASO 2: CONECTAR A LA BASE DE DATOS
// ============================================================
try {
    $pdo = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset={$db_charset}",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
    log_mensaje('ok', "Conexión a la base de datos establecida.");
} catch (PDOException $e) {
    log_mensaje('error', "Error de conexión: " . $e->getMessage());
    mostrar_resultado_y_salir();
}

// ============================================================
// PASO 3: EJECUTAR EL ESQUEMA SQL
// ============================================================
$ruta_schema = __DIR__ . '/schema.sql';
if (!file_exists($ruta_schema)) {
    log_mensaje('error', "No se encontró el archivo schema.sql en: {$ruta_schema}");
    mostrar_resultado_y_salir();
}

try {
    $sql_schema = file_get_contents($ruta_schema);
    // Dividir el SQL en sentencias individuales
    $sentencias_raw = explode(';', $sql_schema);
    $sentencias = array();
    foreach ($sentencias_raw as $s) {
        $s = trim($s);
        if (!empty($s) && substr($s, 0, 2) !== '--' && substr($s, 0, 2) !== '/*') {
            $sentencias[] = $s;
        }
    }

    foreach ($sentencias as $sentencia) {
        if (!empty(trim($sentencia))) {
            $pdo->exec($sentencia);
        }
    }
    log_mensaje('ok', "Esquema de base de datos ejecutado correctamente (" . count($sentencias) . " sentencias).");
} catch (PDOException $e) {
    log_mensaje('advertencia', "Advertencia en esquema (puede ser normal si las tablas ya existen): " . $e->getMessage());
}

// ============================================================
// VERIFICAR SI YA HAY DATOS PARA NO DUPLICAR
// ============================================================
$total_roles = (int)$pdo->query("SELECT COUNT(*) FROM roles")->fetchColumn();
if ($total_roles > 0) {
    log_mensaje('advertencia', "Ya existen datos en la base de datos. El seed no volverá a insertar para evitar duplicados.");
    log_mensaje('info', "Si deseas reiniciar los datos, elimina las tablas y vuelve a ejecutar este script.");
    mostrar_resultado_y_salir();
}

// ============================================================
// PASO 4: INSERTAR ROLES DEL SISTEMA
// ============================================================
log_mensaje('info', "Insertando roles del sistema...");

$roles = [
    ['admin_total', 'Administrador total del sistema. Acceso completo a todas las funcionalidades, incluyendo configuración, seguridad y gestión global.'],
    ['profesor',    'Profesor o docente. Puede gestionar sus grupos de estudiantes, cargar calificaciones y ver estadísticas académicas de sus cursos.'],
    ['estudiante',  'Estudiante de la plataforma. Puede comprar cursos, acceder al contenido, tomar evaluaciones y ver sus calificaciones.'],
];

$stmt_rol = $pdo->prepare("INSERT INTO roles (nombre_rol, descripcion_rol) VALUES (?, ?)");
foreach ($roles as $rol) {
    $stmt_rol->execute($rol);
}
log_mensaje('ok', "3 roles insertados: admin_total, profesor, estudiante.");

// ============================================================
// PASO 5: INSERTAR USUARIOS DE PRUEBA
// Contraseña: abc12345 para todos los usuarios
// ============================================================
log_mensaje('info', "Insertando usuarios de prueba (contraseña: abc12345)...");

// --- Generar el hash de la contraseña 'abc12345' ---
$contrasena_prueba = 'abc12345';
$contrasena_hash   = password_hash($contrasena_prueba, PASSWORD_DEFAULT);

$usuarios_prueba = [
    // [primer_nombre, segundo_nombre, primer_apellido, segundo_apellido, correo, hash, id_rol]
    ['Administrador', null, 'Total',     'Sistema',  'admin@edutechacademy.com',      $contrasena_hash, 1],
    ['Carlos',        'Andrés', 'Martínez', 'López',  'profesor@edutechacademy.com',   $contrasena_hash, 2],
    ['María',         'José',   'González', 'Pérez',  'estudiante@edutechacademy.com', $contrasena_hash, 3],
];

$stmt_usuario = $pdo->prepare("
    INSERT INTO usuarios
        (primer_nombre, segundo_nombre, primer_apellido, segundo_apellido,
         correo_electronico, contrasena_hash, id_rol_fk, estado_activo)
    VALUES (?, ?, ?, ?, ?, ?, ?, 1)
");

foreach ($usuarios_prueba as $usuario) {
    $stmt_usuario->execute($usuario);
}
log_mensaje('ok', "3 usuarios de prueba insertados (admin, profesor, estudiante).");
log_mensaje('info', "Emails: admin@edutechacademy.com | profesor@edutechacademy.com | estudiante@edutechacademy.com");
log_mensaje('info', "Contraseña para todos: abc12345");

// ============================================================
// PASO 6: INSERTAR CATEGORÍAS DE CURSOS
// ============================================================
log_mensaje('info', "Insertando categorías de cursos...");

$categorias = [
    ['Inteligencia Artificial',       'Cursos especializados en IA, Machine Learning, Deep Learning y Computación Evolutiva.',          'fas fa-brain',     '#2563EB'],
    ['Ingeniería Informática',        'Fundamentos y programación avanzada en lenguajes como Python, PHP, C# y más.',                   'fas fa-code',      '#1A3C6E'],
    ['Ingeniería de Sistemas',        'Bases de datos, redes, arquitectura de software y gestión de sistemas de información.',           'fas fa-server',    '#0E7490'],
    ['Seguridad Informática',         'Ciberseguridad, ethical hacking, auditorías de código y protección de sistemas.',                'fas fa-shield-alt','#1E40AF'],
];

$stmt_cat = $pdo->prepare("
    INSERT INTO categorias_curso (nombre_categoria, descripcion_categoria, icono_categoria, color_categoria)
    VALUES (?, ?, ?, ?)
");
foreach ($categorias as $cat) {
    $stmt_cat->execute($cat);
}
log_mensaje('ok', "4 categorías insertadas.");

// ============================================================
// PASO 7: INSERTAR MEDIOS DE PAGO
// ============================================================
log_mensaje('info', "Insertando medios de pago...");

$medios_pago = [
    ['PayU Colombia',         'Pasarela de pago líder en Colombia. Acepta tarjetas crédito/débito, PSE y efectivo (Efecty/Baloto).',
     'pasarela_online',       null,
     'Pago seguro mediante PayU. Acepta todas las tarjetas y PSE.',                 'assets/images/payu-logo.png'],
    ['ePayco',                'Plataforma de pagos colombiana con múltiples métodos de pago.',
     'pasarela_online',       null,
     'Pago seguro mediante ePayco.',                                                 'assets/images/epayco-logo.png'],
    ['Transferencia Bancaria','Transferencia directa a cuenta bancaria. El acceso al curso se habilita tras verificación manual.',
     'transferencia_bancaria',null,
     "Banco: Bancolombia\nTipo de cuenta: Ahorros\nNúmero: 123-456789-00\nNIT: 900.000.001-1\nNombre: EduTech Academy SAS\n\nEnviar comprobante a: pagos@edutechacademy.com",
     'assets/images/bank-logo.png'],
    ['PSE',                   'Pago en línea directo desde tu cuenta bancaria colombiana a través de PSE.',
     'pse',                   null,
     'Pago seguro a través de PSE (Pagos Seguros en Línea).',                       'assets/images/pse-logo.png'],
];

$stmt_mp = $pdo->prepare("
    INSERT INTO medios_pago
        (nombre_medio_pago, descripcion_medio_pago, tipo_integracion,
         credenciales_configuracion, instrucciones_pago, logo_medio_pago, es_medio_activo)
    VALUES (?, ?, ?, ?, ?, ?, 1)
");
foreach ($medios_pago as $mp) {
    $stmt_mp->execute($mp);
}
log_mensaje('ok', "4 medios de pago insertados.");

// ============================================================
// PASO 8: INSERTAR CONFIGURACIÓN DEL SISTEMA
// ============================================================
log_mensaje('info', "Insertando configuración del sistema...");

$configuraciones = [
    ['site_name',          'EduTech Academy',              'Nombre del sitio web',                           'texto'],
    ['site_email',         'contacto@edutechacademy.com',  'Email principal de contacto',                    'texto'],
    ['site_telefono',      '+57 300 000 0000',             'Teléfono de contacto',                           'texto'],
    ['moneda_codigo',      'COP',                          'Código de moneda (ISO 4217)',                    'texto'],
    ['moneda_simbolo',     '$',                            'Símbolo de la moneda',                          'texto'],
    ['max_intentos_login', '5',                            'Número máximo de intentos de login',            'numero'],
    ['tiempo_bloqueo',     '900',                          'Tiempo de bloqueo en segundos (15 minutos)',     'numero'],
    ['iva_porcentaje',     '0',                            'Porcentaje de IVA aplicado a los cursos',       'numero'],
    ['mantenimiento',      '0',                            'Modo mantenimiento (1=activo, 0=inactivo)',      'boolean'],
    ['version_sistema',    '1.0.0',                        'Versión actual del sistema',                    'texto'],
    ['color_primario',     '#1A3C6E',                      'Color primario de la paleta del sitio',         'color'],
    ['color_secundario',   '#2563EB',                      'Color secundario de la paleta del sitio',       'color'],
];

$stmt_conf = $pdo->prepare("
    INSERT INTO configuracion_sistema (clave_configuracion, valor_configuracion, descripcion_configuracion, tipo_dato)
    VALUES (?, ?, ?, ?)
");
foreach ($configuraciones as $conf) {
    $stmt_conf->execute($conf);
}
log_mensaje('ok', count($configuraciones) . " parámetros de configuración insertados.");

// ============================================================
// PASO 9: INSERTAR CURSOS INICIALES (8 cursos)
// ============================================================
log_mensaje('info', "Insertando catálogo de cursos...");

// id_categoria_fk: 1=IA, 2=Informática, 3=Sistemas, 4=Seguridad
// id_profesor_fk: 2 (Carlos Martínez)
$cursos = [
    // --- CURSO 1: Algoritmos Genéticos ---
    [
        'id_categoria_fk'         => 1,
        'id_profesor_fk'          => 2,
        'titulo_curso'            => 'Algoritmos Genéticos y Computación Evolutiva',
        'resumen_corto'           => 'Aprende a diseñar e implementar algoritmos de optimización inspirados en la evolución biológica. Domina los algoritmos genéticos aplicados a problemas reales de IA con Python.',
        'descripcion_detallada'   => 'Los Algoritmos Genéticos (AG) son una de las técnicas más poderosas y versátiles de la Inteligencia Artificial inspirada en la naturaleza. Este curso te llevará desde los fundamentos teóricos de la computación evolutiva hasta implementaciones profesionales en Python para resolver problemas complejos de optimización.

Aprenderás cómo funciona la selección natural aplicada a la computación, diseñarás operadores genéticos (selección, cruzamiento y mutación), y los aplicarás en problemas reales como el Problema del Viajante (TSP), optimización de hiperparámetros en Machine Learning, diseño de redes neuronales y más.

El curso incluye prácticas con bibliotecas especializadas como DEAP y PyGAD, visualización de la convergencia de los algoritmos con Matplotlib, y un proyecto final donde aplicarás todo lo aprendido en un problema real de ingeniería.

Al finalizar, estarás en capacidad de diseñar, implementar y ajustar algoritmos genéticos para resolver cualquier problema de optimización combinatoria o numérica que se te presente en tu carrera como ingeniero.',
        'imagen_portada'          => 'assets/images/cursos/algoritmos-geneticos.jpg',
        'video_presentacion'      => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'tipo_video'              => 'youtube',
        'total_horas'             => 120,
        'total_clases_estimado'   => 48,
        'duracion_meses'          => 6,
        'precio'                  => 450000.00,
        'precio_con_descuento'    => 350000.00,
        'nivel_dificultad'        => 'Avanzado',
        'lenguaje_programacion'   => 'Python',
        'requisitos_previos'      => 'Programación en Python (nivel intermedio), conocimientos básicos de probabilidad y estadística, fundamentos de estructuras de datos.',
        'certificado_disponible'  => 1,
    ],
    // --- CURSO 2: Machine Learning ---
    [
        'id_categoria_fk'         => 1,
        'id_profesor_fk'          => 2,
        'titulo_curso'            => 'Machine Learning con Python desde Cero',
        'resumen_corto'           => 'Domina los fundamentos y técnicas avanzadas de Machine Learning. Desde regresión lineal hasta algoritmos de ensamble, con proyectos reales usando scikit-learn y Pandas.',
        'descripcion_detallada'   => 'Este curso es tu puerta de entrada al mundo del Machine Learning. Aprenderás los algoritmos más importantes del aprendizaje automático, cómo preprocesar datos, evaluar modelos y desplegar soluciones inteligentes.

Cubriremos algoritmos supervisados (regresión, clasificación), no supervisados (clustering, reducción de dimensionalidad) y de ensamble (Random Forest, XGBoost). Todo con Python, scikit-learn, Pandas y Matplotlib.

Proyectos reales: predicción de precios, clasificación de imágenes, detección de anomalías y más.',
        'imagen_portada'          => 'assets/images/cursos/machine-learning.jpg',
        'video_presentacion'      => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'tipo_video'              => 'youtube',
        'total_horas'             => 160,
        'total_clases_estimado'   => 64,
        'duracion_meses'          => 6,
        'precio'                  => 480000.00,
        'precio_con_descuento'    => 380000.00,
        'nivel_dificultad'        => 'Intermedio',
        'lenguaje_programacion'   => 'Python',
        'requisitos_previos'      => 'Python básico, matemáticas de bachillerato, ganas de aprender.',
        'certificado_disponible'  => 1,
    ],
    // --- CURSO 3: Deep Learning ---
    [
        'id_categoria_fk'         => 1,
        'id_profesor_fk'          => 2,
        'titulo_curso'            => 'Deep Learning y Redes Neuronales con TensorFlow y Keras',
        'resumen_corto'           => 'Construye redes neuronales profundas para visión por computadora, procesamiento de lenguaje natural y más. Domina TensorFlow 2.x y Keras en proyectos industriales.',
        'descripcion_detallada'   => 'Sumérgete en el mundo del Deep Learning con uno de los frameworks más poderosos: TensorFlow 2.x con Keras. Aprenderás a construir, entrenar y desplegar redes neuronales artificiales para resolver problemas del mundo real.

Cubriremos redes densas (MLP), redes convolucionales (CNN) para visión, redes recurrentes (RNN/LSTM) para series de tiempo y texto, y transformers modernos. Proyectos: reconocimiento de imágenes, análisis de sentimientos, predicción de series de tiempo.',
        'imagen_portada'          => 'assets/images/cursos/deep-learning.jpg',
        'video_presentacion'      => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'tipo_video'              => 'youtube',
        'total_horas'             => 180,
        'total_clases_estimado'   => 72,
        'duracion_meses'          => 6,
        'precio'                  => 520000.00,
        'precio_con_descuento'    => 420000.00,
        'nivel_dificultad'        => 'Avanzado',
        'lenguaje_programacion'   => 'Python',
        'requisitos_previos'      => 'Machine Learning básico, Python intermedio, álgebra lineal básica.',
        'certificado_disponible'  => 1,
    ],
    // --- CURSO 4: Python ---
    [
        'id_categoria_fk'         => 2,
        'id_profesor_fk'          => 2,
        'titulo_curso'            => 'Programación en Python para Ingeniería',
        'resumen_corto'           => 'Aprende Python desde cero con enfoque en aplicaciones de ingeniería: automatización, análisis de datos, algoritmos y estructuras de datos. El lenguaje favorito de los ingenieros.',
        'descripcion_detallada'   => 'Python es el lenguaje más versátil y demandado en la industria tecnológica. Este curso está diseñado específicamente para ingenieros que quieren dominar Python para aplicaciones reales.

Aprenderás desde la sintaxis básica hasta programación orientada a objetos, manejo de archivos, APIs REST, automatización de tareas, análisis de datos con Pandas y visualización con Matplotlib. Ideal como base para cursos de IA y ML.',
        'imagen_portada'          => 'assets/images/cursos/python-ingenieria.jpg',
        'video_presentacion'      => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'tipo_video'              => 'youtube',
        'total_horas'             => 100,
        'total_clases_estimado'   => 40,
        'duracion_meses'          => 6,
        'precio'                  => 320000.00,
        'precio_con_descuento'    => null,
        'nivel_dificultad'        => 'Principiante',
        'lenguaje_programacion'   => 'Python',
        'requisitos_previos'      => 'Ninguno. Actitud y ganas de aprender.',
        'certificado_disponible'  => 1,
    ],
    // --- CURSO 5: PHP y MySQL ---
    [
        'id_categoria_fk'         => 3,
        'id_profesor_fk'          => 2,
        'titulo_curso'            => 'Desarrollo Web con PHP 8 y MySQL',
        'resumen_corto'           => 'Desarrolla aplicaciones web profesionales con PHP 8 y MySQL. Aprende MVC, APIs REST, autenticación segura, PDO y buenas prácticas de desarrollo backend.',
        'descripcion_detallada'   => 'Conviértete en un desarrollador backend profesional con PHP 8 y MySQL. Este curso te enseña a construir aplicaciones web robustas, seguras y escalables desde cero.

Aprenderás el patrón MVC, creación de APIs REST, autenticación con JWT, conexión segura a bases de datos con PDO, subida de archivos, manejo de sesiones y todas las mejores prácticas de seguridad web.',
        'imagen_portada'          => 'assets/images/cursos/php-mysql.jpg',
        'video_presentacion'      => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'tipo_video'              => 'youtube',
        'total_horas'             => 140,
        'total_clases_estimado'   => 56,
        'duracion_meses'          => 6,
        'precio'                  => 390000.00,
        'precio_con_descuento'    => 320000.00,
        'nivel_dificultad'        => 'Intermedio',
        'lenguaje_programacion'   => 'PHP',
        'requisitos_previos'      => 'HTML y CSS básico, lógica de programación.',
        'certificado_disponible'  => 1,
    ],
    // --- CURSO 6: Estructuras de Datos ---
    [
        'id_categoria_fk'         => 2,
        'id_profesor_fk'          => 2,
        'titulo_curso'            => 'Estructuras de Datos y Algoritmos',
        'resumen_corto'           => 'Domina las estructuras de datos fundamentales y los algoritmos más importantes de la informática. Prepárate para entrevistas técnicas y desarrollo de software eficiente.',
        'descripcion_detallada'   => 'Las estructuras de datos y los algoritmos son la columna vertebral de la programación profesional. Este curso te dará el conocimiento sólido que todo ingeniero informático debe tener.

Estudiaremos arrays, listas enlazadas, pilas, colas, árboles, grafos y tablas hash. Implementaremos algoritmos de búsqueda y ordenamiento, y analizaremos su complejidad temporal y espacial (Big-O). Implementaciones en Python.',
        'imagen_portada'          => 'assets/images/cursos/estructuras-datos.jpg',
        'video_presentacion'      => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'tipo_video'              => 'youtube',
        'total_horas'             => 120,
        'total_clases_estimado'   => 48,
        'duracion_meses'          => 6,
        'precio'                  => 360000.00,
        'precio_con_descuento'    => null,
        'nivel_dificultad'        => 'Intermedio',
        'lenguaje_programacion'   => 'Python',
        'requisitos_previos'      => 'Programación básica en cualquier lenguaje.',
        'certificado_disponible'  => 1,
    ],
    // --- CURSO 7: Bases de Datos Avanzadas ---
    [
        'id_categoria_fk'         => 3,
        'id_profesor_fk'          => 2,
        'titulo_curso'            => 'Bases de Datos Avanzadas con MySQL',
        'resumen_corto'           => 'Lleva tus habilidades de bases de datos al siguiente nivel. Diseño avanzado, optimización de consultas, procedimientos almacenados, triggers, transacciones y administración MySQL.',
        'descripcion_detallada'   => 'Conviértete en un experto en bases de datos relacionales con MySQL. Este curso va más allá del SQL básico y te enseña técnicas avanzadas que usan los ingenieros de bases de datos profesionales.

Aprenderás diseño avanzado de esquemas, optimización de índices, procedimientos almacenados, funciones, triggers, transacciones ACID, replicación, backup y recuperación, y administración de MySQL en producción.',
        'imagen_portada'          => 'assets/images/cursos/bases-datos-avanzadas.jpg',
        'video_presentacion'      => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'tipo_video'              => 'youtube',
        'total_horas'             => 90,
        'total_clases_estimado'   => 36,
        'duracion_meses'          => 6,
        'precio'                  => 290000.00,
        'precio_con_descuento'    => null,
        'nivel_dificultad'        => 'Intermedio',
        'lenguaje_programacion'   => 'SQL',
        'requisitos_previos'      => 'SQL básico (SELECT, INSERT, UPDATE, DELETE), conocimiento de bases de datos relacionales.',
        'certificado_disponible'  => 1,
    ],
    // --- CURSO 8: Seguridad Informática ---
    [
        'id_categoria_fk'         => 4,
        'id_profesor_fk'          => 2,
        'titulo_curso'            => 'Seguridad Informática y Ciberseguridad',
        'resumen_corto'           => 'Aprende a proteger sistemas, redes y aplicaciones. Ethical hacking, análisis de vulnerabilidades, criptografía, OWASP Top 10 y herramientas profesionales de pentesting.',
        'descripcion_detallada'   => 'La ciberseguridad es uno de los campos más demandados y críticos de la tecnología actual. Este curso te forma como profesional en seguridad informática con enfoque práctico y ético.

Aprenderás los fundamentos de la seguridad, criptografía, análisis de vulnerabilidades web (OWASP Top 10), seguridad en redes, ethical hacking controlado, análisis forense digital y cómo implementar políticas de seguridad en organizaciones.',
        'imagen_portada'          => 'assets/images/cursos/ciberseguridad.jpg',
        'video_presentacion'      => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
        'tipo_video'              => 'youtube',
        'total_horas'             => 110,
        'total_clases_estimado'   => 44,
        'duracion_meses'          => 6,
        'precio'                  => 420000.00,
        'precio_con_descuento'    => 350000.00,
        'nivel_dificultad'        => 'Intermedio',
        'lenguaje_programacion'   => 'Python, Bash, Kali Linux',
        'requisitos_previos'      => 'Redes básicas, sistemas operativos, conocimientos de programación.',
        'certificado_disponible'  => 1,
    ],
];

$stmt_curso = $pdo->prepare("
    INSERT INTO cursos
        (id_categoria_fk, id_profesor_fk, titulo_curso, resumen_corto, descripcion_detallada,
         imagen_portada, video_presentacion, tipo_video, total_horas, total_clases_estimado,
         duracion_meses, precio, precio_con_descuento, nivel_dificultad, lenguaje_programacion,
         requisitos_previos, certificado_disponible, estado_activo)
    VALUES
        (:id_categoria_fk, :id_profesor_fk, :titulo_curso, :resumen_corto, :descripcion_detallada,
         :imagen_portada, :video_presentacion, :tipo_video, :total_horas, :total_clases_estimado,
         :duracion_meses, :precio, :precio_con_descuento, :nivel_dificultad, :lenguaje_programacion,
         :requisitos_previos, :certificado_disponible, 1)
");

foreach ($cursos as $curso) {
    $stmt_curso->execute($curso);
}
log_mensaje('ok', count($cursos) . " cursos insertados en el catálogo.");

// ============================================================
// PASO 10: COMPETENCIAS DEL CURSO 1 (Algoritmos Genéticos)
// ============================================================
log_mensaje('info', "Insertando competencias del curso Algoritmos Genéticos...");

$competencias_ag = [
    [1, 'Comprender los fundamentos teóricos y matemáticos de los Algoritmos Genéticos y la Computación Evolutiva.',                'fas fa-graduation-cap', 1],
    [1, 'Implementar Algoritmos Genéticos desde cero utilizando Python con código limpio y documentado.',                           'fas fa-code',           2],
    [1, 'Diseñar y aplicar operadores genéticos (selección, cruzamiento y mutación) adecuados para diferentes tipos de problemas.', 'fas fa-dna',            3],
    [1, 'Utilizar bibliotecas especializadas como DEAP y PyGAD para acelerar el desarrollo de soluciones evolutivas.',              'fas fa-cubes',          4],
    [1, 'Resolver problemas clásicos de optimización combinatoria como el TSP usando algoritmos evolutivos.',                       'fas fa-route',          5],
    [1, 'Ajustar y optimizar parámetros de los AG (tamaño de población, tasa de mutación, criterios de parada).',                  'fas fa-sliders-h',      6],
    [1, 'Integrar Algoritmos Genéticos con técnicas de Machine Learning para optimización de modelos.',                             'fas fa-brain',          7],
    [1, 'Analizar y visualizar la convergencia y el rendimiento de los algoritmos evolutivos con Matplotlib.',                      'fas fa-chart-line',     8],
];

$stmt_comp = $pdo->prepare("
    INSERT INTO competencias_curso (id_curso_fk, descripcion_competencia, icono_competencia, orden_visualizacion)
    VALUES (?, ?, ?, ?)
");
foreach ($competencias_ag as $comp) {
    $stmt_comp->execute($comp);
}

// --- Competencias para los demás cursos (resumidas) ---
$competencias_otros = [
    // Machine Learning (curso 2)
    [2, 'Implementar y evaluar algoritmos de aprendizaje supervisado y no supervisado.',  'fas fa-robot',   1],
    [2, 'Preprocesar y analizar conjuntos de datos reales con Pandas y NumPy.',           'fas fa-table',   2],
    [2, 'Seleccionar el modelo correcto según el tipo de problema a resolver.',           'fas fa-tasks',   3],
    [2, 'Evaluar modelos con métricas adecuadas y prevenir el sobreajuste.',             'fas fa-chart-bar',4],
    // Deep Learning (curso 3)
    [3, 'Diseñar y entrenar redes neuronales profundas con TensorFlow y Keras.',         'fas fa-network-wired',1],
    [3, 'Construir redes convolucionales (CNN) para visión por computadora.',            'fas fa-eye',          2],
    [3, 'Aplicar redes recurrentes (LSTM) para análisis de series de tiempo.',           'fas fa-wave-square',  3],
    // Python (curso 4)
    [4, 'Programar en Python usando paradigmas procedural y orientado a objetos.',       'fas fa-code',         1],
    [4, 'Automatizar tareas repetitivas y procesar archivos con Python.',               'fas fa-cogs',         2],
    [4, 'Consumir APIs REST y analizar datos con Pandas.',                              'fas fa-plug',         3],
    // PHP (curso 5)
    [5, 'Desarrollar aplicaciones web backend con PHP 8 y el patrón MVC.',             'fas fa-server',       1],
    [5, 'Crear APIs REST seguras con autenticación JWT.',                               'fas fa-lock',         2],
    [5, 'Conectar aplicaciones web a MySQL de forma segura con PDO.',                  'fas fa-database',     3],
    // Estructuras (curso 6)
    [6, 'Implementar las estructuras de datos fundamentales desde cero.',               'fas fa-sitemap',      1],
    [6, 'Analizar la complejidad temporal y espacial (Big-O) de los algoritmos.',      'fas fa-tachometer-alt',2],
    [6, 'Diseñar soluciones eficientes para problemas de programación competitiva.',   'fas fa-trophy',       3],
    // BD Avanzadas (curso 7)
    [7, 'Diseñar esquemas de base de datos optimizados y normalizados.',               'fas fa-project-diagram',1],
    [7, 'Crear procedimientos almacenados, funciones y triggers en MySQL.',             'fas fa-database',       2],
    [7, 'Optimizar consultas SQL y gestionar índices para alto rendimiento.',           'fas fa-bolt',           3],
    // Seguridad (curso 8)
    [8, 'Identificar y explotar vulnerabilidades web del OWASP Top 10.',               'fas fa-bug',            1],
    [8, 'Implementar controles de seguridad en aplicaciones web y APIs.',              'fas fa-shield-alt',     2],
    [8, 'Aplicar técnicas de criptografía para proteger información sensible.',        'fas fa-key',            3],
];

foreach ($competencias_otros as $comp) {
    $stmt_comp->execute($comp);
}
log_mensaje('ok', "Competencias de todos los cursos insertadas.");

// ============================================================
// PASO 11: EJEMPLOS DE CÓDIGO (Cursos de Programación)
// ============================================================
log_mensaje('info', "Insertando ejemplos de código de programación...");

// --- Ejemplo 1: Algoritmo Genético básico en Python ---
$codigo_ag_basico = <<<'PYTHON'
# Algoritmo Genético básico para maximizar f(x) = x^2
# en el intervalo [0, 31] con representación binaria
import random

# --- Parámetros del Algoritmo Genético ---
TAMANO_POBLACION  = 10
NUM_BITS          = 5      # Representa números de 0 a 31
GENERACIONES      = 20
TASA_CRUZAMIENTO  = 0.8
TASA_MUTACION     = 0.01

def binario_a_entero(cromosoma: list) -> int:
    """Convierte un cromosoma binario (lista de 0s y 1s) a entero."""
    return int(''.join(map(str, cromosoma)), 2)

def aptitud(cromosoma: list) -> int:
    """Función de aptitud: f(x) = x^2"""
    x = binario_a_entero(cromosoma)
    return x ** 2

def seleccion_ruleta(poblacion: list, aptitudes: list) -> list:
    """Selección por ruleta: probabilidad proporcional a la aptitud."""
    total_aptitud = sum(aptitudes)
    punto = random.uniform(0, total_aptitud)
    acumulado = 0
    for individuo, ap in zip(poblacion, aptitudes):
        acumulado += ap
        if acumulado >= punto:
            return individuo
    return poblacion[-1]

def cruzamiento_un_punto(padre1: list, padre2: list) -> tuple:
    """Cruzamiento de un punto: intercambia genes después del punto."""
    if random.random() < TASA_CRUZAMIENTO:
        punto = random.randint(1, NUM_BITS - 1)
        hijo1 = padre1[:punto] + padre2[punto:]
        hijo2 = padre2[:punto] + padre1[punto:]
        return hijo1, hijo2
    return padre1[:], padre2[:]

def mutacion(cromosoma: list) -> list:
    """Mutación bit-flip: invierte cada bit con la tasa de mutación."""
    return [1 - bit if random.random() < TASA_MUTACION else bit
            for bit in cromosoma]

# --- Inicializar población aleatoria ---
poblacion = [
    [random.randint(0, 1) for _ in range(NUM_BITS)]
    for _ in range(TAMANO_POBLACION)
]

# --- Ciclo Evolutivo ---
for generacion in range(GENERACIONES):
    aptitudes  = [aptitud(ind) for ind in poblacion]
    mejor      = max(aptitudes)
    mejor_ind  = poblacion[aptitudes.index(mejor)]

    print(f"Generación {generacion + 1:2d} | Mejor: x={binario_a_entero(mejor_ind):2d} | f(x)={mejor}")

    nueva_poblacion = []
    while len(nueva_poblacion) < TAMANO_POBLACION:
        padre1 = seleccion_ruleta(poblacion, aptitudes)
        padre2 = seleccion_ruleta(poblacion, aptitudes)
        hijo1, hijo2 = cruzamiento_un_punto(padre1, padre2)
        nueva_poblacion.append(mutacion(hijo1))
        if len(nueva_poblacion) < TAMANO_POBLACION:
            nueva_poblacion.append(mutacion(hijo2))

    poblacion = nueva_poblacion

print("\n✅ Resultado óptimo encontrado: x=31, f(x)=961")
PYTHON;

// --- Ejemplo 2: TSP con DEAP ---
$codigo_ag_deap = <<<'PYTHON'
# Algoritmo Genético para el Problema del Viajante (TSP)
# usando la biblioteca DEAP (Distributed Evolutionary Algorithms in Python)
# Instalación: pip install deap numpy matplotlib

import random
import numpy as np
from deap import base, creator, tools, algorithms

# --- Ciudades: coordenadas (x, y) ---
CIUDADES = [
    (0, 0), (100, 0), (100, 100), (0, 100),
    (50, 50), (25, 75), (75, 25), (10, 50),
    (90, 70), (45, 15)
]
NUM_CIUDADES = len(CIUDADES)

def distancia_total(ruta: list) -> tuple:
    """Calcula la distancia total de la ruta (a minimizar)."""
    distancia = 0
    for i in range(len(ruta)):
        ciudad_actual  = CIUDADES[ruta[i]]
        ciudad_siguiente = CIUDADES[ruta[(i + 1) % len(ruta)]]
        distancia += np.sqrt(
            (ciudad_actual[0] - ciudad_siguiente[0]) ** 2 +
            (ciudad_actual[1] - ciudad_siguiente[1]) ** 2
        )
    return (distancia,)  # DEAP requiere una tupla

# --- Configuración de DEAP ---
creator.create("FitnessMin", base.Fitness, weights=(-1.0,))  # Minimizar
creator.create("Individual", list, fitness=creator.FitnessMin)

caja_herramientas = base.Toolbox()
caja_herramientas.register("indices",    random.sample, range(NUM_CIUDADES), NUM_CIUDADES)
caja_herramientas.register("individual", tools.initIterate, creator.Individual, caja_herramientas.indices)
caja_herramientas.register("population", tools.initRepeat,  list, caja_herramientas.individual)
caja_herramientas.register("evaluate",   distancia_total)
caja_herramientas.register("mate",       tools.cxOrdered)               # Cruzamiento ordenado (para permutaciones)
caja_herramientas.register("mutate",     tools.mutShuffleIndexes, indpb=0.05)  # Mutación por intercambio
caja_herramientas.register("select",     tools.selTournament, tournsize=3)

# --- Ejecutar el Algoritmo Genético ---
poblacion     = caja_herramientas.population(n=300)
estadisticas  = tools.Statistics(lambda ind: ind.fitness.values)
estadisticas.register("min",  np.min)
estadisticas.register("avg",  np.mean)

resultado, log = algorithms.eaSimple(
    poblacion,
    caja_herramientas,
    cxpb=0.7,    # Probabilidad de cruzamiento
    mutpb=0.2,   # Probabilidad de mutación
    ngen=200,    # Número de generaciones
    stats=estadisticas,
    verbose=False
)

mejor_individuo = tools.selBest(resultado, k=1)[0]
print(f"✅ Mejor ruta encontrada: {mejor_individuo}")
print(f"📏 Distancia mínima: {distancia_total(mejor_individuo)[0]:.2f} unidades")
PYTHON;

// --- Ejemplo para Machine Learning ---
$codigo_ml = <<<'PYTHON'
# Ejemplo: Clasificación con scikit-learn
# Comparación de clasificadores en el dataset Iris
from sklearn.datasets      import load_iris
from sklearn.model_selection import train_test_split
from sklearn.preprocessing import StandardScaler
from sklearn.ensemble      import RandomForestClassifier
from sklearn.svm           import SVC
from sklearn.metrics       import classification_report, accuracy_score
import pandas as pd

# --- Cargar datos ---
iris = load_iris()
X, y = iris.data, iris.target
nombres_clases = iris.target_names

# --- Dividir en entrenamiento y prueba ---
X_entrenamiento, X_prueba, y_entrenamiento, y_prueba = train_test_split(
    X, y, test_size=0.2, random_state=42, stratify=y
)

# --- Normalizar características ---
normalizador = StandardScaler()
X_entrenamiento = normalizador.fit_transform(X_entrenamiento)
X_prueba        = normalizador.transform(X_prueba)

# --- Entrenar y evaluar modelos ---
modelos = {
    'Random Forest': RandomForestClassifier(n_estimators=100, random_state=42),
    'SVM':           SVC(kernel='rbf', C=1.0, random_state=42),
}

for nombre, modelo in modelos.items():
    modelo.fit(X_entrenamiento, y_entrenamiento)
    predicciones = modelo.predict(X_prueba)
    exactitud    = accuracy_score(y_prueba, predicciones)
    print(f"\n🤖 Modelo: {nombre}")
    print(f"   Exactitud: {exactitud:.2%}")
    print(classification_report(y_prueba, predicciones, target_names=nombres_clases))
PYTHON;

// --- Ejemplo para PHP ---
$codigo_php = <<<'PHP'
<?php
// Ejemplo: API REST segura con PDO y prepared statements
// Endpoint: GET /api/cursos.php?categoria=1

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once '../config/database.php';
require_once '../includes/session.php';

// --- Validar que la petición sea GET ---
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Método no permitido.']);
    exit();
}

// --- Obtener y validar parámetro de categoría ---
$id_categoria = isset($_GET['categoria']) ? (int)$_GET['categoria'] : null;

try {
    $pdo = obtenerConexion();

    if ($id_categoria !== null && $id_categoria > 0) {
        // Consulta con filtro — usando prepared statement (previene SQL Injection)
        $stmt = $pdo->prepare("
            SELECT
                id_curso_pk,
                titulo_curso,
                resumen_corto,
                precio,
                precio_con_descuento,
                nivel_dificultad,
                total_horas,
                imagen_portada
            FROM cursos
            WHERE id_categoria_fk = :id_categoria
              AND estado_activo = 1
            ORDER BY titulo_curso ASC
        ");
        $stmt->bindParam(':id_categoria', $id_categoria, PDO::PARAM_INT);
    } else {
        // Consulta sin filtro
        $stmt = $pdo->prepare("
            SELECT
                id_curso_pk,
                titulo_curso,
                resumen_corto,
                precio,
                precio_con_descuento,
                nivel_dificultad,
                total_horas,
                imagen_portada
            FROM cursos
            WHERE estado_activo = 1
            ORDER BY titulo_curso ASC
        ");
    }

    $stmt->execute();
    $cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Sanitizar salida para prevenir XSS ---
    $cursos_seguros = array_map(function($curso) {
        return array(
            'id_curso_pk'          => (int)$curso['id_curso_pk'],
            'titulo_curso'         => htmlspecialchars($curso['titulo_curso'],         ENT_QUOTES, 'UTF-8'),
            'resumen_corto'        => htmlspecialchars($curso['resumen_corto'],        ENT_QUOTES, 'UTF-8'),
            'precio'               => number_format((float)$curso['precio'],           2, '.', ''),
            'precio_con_descuento' => $curso['precio_con_descuento']
                                      ? number_format((float)$curso['precio_con_descuento'], 2, '.', '')
                                      : null,
            'nivel_dificultad'     => htmlspecialchars($curso['nivel_dificultad'],     ENT_QUOTES, 'UTF-8'),
            'total_horas'          => (int)$curso['total_horas'],
            'imagen_portada'       => htmlspecialchars(isset($curso['imagen_portada']) ? $curso['imagen_portada'] : '', ENT_QUOTES, 'UTF-8'),
        );
    }, $cursos);

    http_response_code(200);
    echo json_encode([
        'estado'  => 'ok',
        'total'   => count($cursos_seguros),
        'cursos'  => $cursos_seguros,
    ]);

} catch (PDOException $e) {
    error_log("[API ERROR] " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['estado' => 'error', 'mensaje' => 'Error al obtener los cursos.']);
}
?>
PHP;

$ejemplos_codigo = [
    [1, 'Algoritmo Genético Básico en Python', 'Implementación paso a paso de un AG básico para maximizar f(x)=x². Incluye codificación binaria, selección por ruleta, cruzamiento de un punto y mutación.', $codigo_ag_basico, 'python', 1],
    [1, 'Algoritmo Genético para TSP con DEAP', 'Solución al clásico Problema del Viajante (TSP) usando la biblioteca DEAP. Incluye cruzamiento ordenado y mutación por intercambio.', $codigo_ag_deap, 'python', 2],
    [2, 'Clasificación con scikit-learn', 'Ejemplo de clasificación comparando Random Forest y SVM en el dataset Iris. Incluye normalización, evaluación y métricas.', $codigo_ml, 'python', 1],
    [5, 'API REST Segura con PHP y PDO', 'Endpoint de ejemplo mostrando buenas prácticas: prepared statements, sanitización de salida con htmlspecialchars() y manejo de errores.', $codigo_php, 'php', 1],
];

$stmt_ej = $pdo->prepare("
    INSERT INTO ejemplos_codigo_curso
        (id_curso_fk, titulo_ejemplo, descripcion_ejemplo, codigo_fuente, lenguaje_programacion, orden_visualizacion)
    VALUES (?, ?, ?, ?, ?, ?)
");
foreach ($ejemplos_codigo as $ej) {
    $stmt_ej->execute($ej);
}
log_mensaje('ok', count($ejemplos_codigo) . " ejemplos de código insertados.");

// ============================================================
// PASO 12: MÓDULOS DEL CURSO 1 (Algoritmos Genéticos)
// ============================================================
log_mensaje('info', "Insertando módulos del curso Algoritmos Genéticos...");

$modulos_ag = [
    [1, 'Módulo 1: Introducción a la Computación Evolutiva',       'Fundamentos históricos y conceptuales de los algoritmos genéticos y la computación evolutiva.',        15, 1],
    [1, 'Módulo 2: Fundamentos Matemáticos y Representación',      'Bases matemáticas: probabilidad, espacios de búsqueda, representación de cromosomas y función de aptitud.', 20, 2],
    [1, 'Módulo 3: Operadores Genéticos',                          'Diseño e implementación de los operadores de selección, cruzamiento y mutación.',                        25, 3],
    [1, 'Módulo 4: Implementación en Python',                      'Desarrollo práctico de AG desde cero y con bibliotecas especializadas (DEAP, PyGAD).',                   30, 4],
    [1, 'Módulo 5: Aplicaciones Reales',                           'Resolución de problemas reales: TSP, optimización de hiperparámetros, diseño de redes neuronales.',       20, 5],
    [1, 'Módulo 6: Temas Avanzados de Computación Evolutiva',      'Algoritmos meméticos, programación genética y algoritmos multiobjetivo (NSGA-II).',                      10, 6],
];

$stmt_mod = $pdo->prepare("
    INSERT INTO modulos_curso (id_curso_fk, titulo_modulo, descripcion_modulo, total_horas_modulo, orden_modulo)
    VALUES (?, ?, ?, ?, ?)
");
foreach ($modulos_ag as $mod) {
    $stmt_mod->execute($mod);
}
log_mensaje('ok', "6 módulos del curso Algoritmos Genéticos insertados.");

// ============================================================
// PASO 13: CLASES DEL MÓDULO 1 (Introducción)
// ============================================================
log_mensaje('info', "Insertando clases del Módulo 1 (Introducción)...");

// id_modulo_fk = 1 (Módulo 1: Introducción)
$clases_modulo1 = [
    [1, 'Historia y fundamentos de los Algoritmos Genéticos',     'Recorrido histórico desde John Holland hasta las aplicaciones modernas.',                    'https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', 120, 1, 1],
    [1, 'Inspiración biológica: selección natural y genética',    'Cómo Darwin inspira los algoritmos de optimización modernos.',                              'https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', 120, 2, 0],
    [1, '¿Qué son los Algoritmos Genéticos? Conceptos clave',    'Cromosomas, genes, población, aptitud, generaciones. El ciclo evolutivo completo.',         'https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', 120, 3, 0],
    [1, 'Aplicaciones modernas de AG en IA y Optimización',       'Casos de uso reales: diseño de antenas NASA, trading algorítmico, optimización industrial.', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', 120, 4, 0],
];

$stmt_clase = $pdo->prepare("
    INSERT INTO clases_curso
        (id_modulo_fk, titulo_clase, descripcion_clase, url_video, tipo_video, duracion_minutos, orden_clase, es_clase_gratuita)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
foreach ($clases_modulo1 as $clase) {
    $stmt_clase->execute($clase);
}
log_mensaje('ok', "4 clases del Módulo 1 insertadas.");

// ============================================================
// PASO 14: EVALUACIONES DEL CURSO 1
// ============================================================
log_mensaje('info', "Insertando evaluaciones del curso Algoritmos Genéticos...");

$evaluaciones_ag = [
    [1, 'Evaluación 1: Fundamentos de AG',       'Evalúa conocimientos de los módulos 1 y 2 (Introducción y Fundamentos Matemáticos).', 8,  100.00, 70.00, 60, 2, 1],
    [1, 'Evaluación 2: Operadores Genéticos',    'Evalúa el dominio de los operadores de selección, cruzamiento y mutación.',            16, 100.00, 70.00, 60, 2, 2],
    [1, 'Evaluación 3: Implementación Python',   'Evaluación práctica: implementar un AG para un problema dado.',                        24, 100.00, 70.00, 90, 1, 3],
    [1, 'Evaluación Final: Proyecto Integrador', 'Proyecto final: resolver un problema real usando AG con Python.',                      40, 100.00, 70.00, null,1, 4],
];

$stmt_eval = $pdo->prepare("
    INSERT INTO evaluaciones
        (id_curso_fk, titulo_evaluacion, descripcion_evaluacion, numero_clases_requeridas,
         puntaje_maximo, puntaje_minimo_aprobacion, tiempo_limite_minutos, intentos_permitidos, orden_evaluacion)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
");
foreach ($evaluaciones_ag as $eval) {
    $stmt_eval->execute($eval);
}
log_mensaje('ok', "4 evaluaciones del curso Algoritmos Genéticos insertadas.");

// ============================================================
// PASO 15: PREGUNTAS DE LA EVALUACIÓN 1
// ============================================================
log_mensaje('info', "Insertando preguntas de la Evaluación 1...");

$preguntas_eval1 = [
    [1, '¿Quién es considerado el padre de los Algoritmos Genéticos?',                                                               'opcion_multiple', 1.00, 1],
    [1, 'En un Algoritmo Genético, ¿cómo se llama la medida que indica qué tan buena es una solución?',                              'opcion_multiple', 1.00, 2],
    [1, '¿Cuál de las siguientes NO es una operación estándar de un Algoritmo Genético?',                                             'opcion_multiple', 1.00, 3],
    [1, 'La representación binaria de un individuo en un AG se denomina:',                                                             'opcion_multiple', 1.00, 4],
    [1, 'Los Algoritmos Genéticos están inspirados en el proceso de evolución biológica descrito por Darwin.',                         'verdadero_falso', 1.00, 5],
    [1, '¿Qué representa cada individuo de la población en un Algoritmo Genético?',                                                    'opcion_multiple', 2.00, 6],
];

$stmt_preg = $pdo->prepare("
    INSERT INTO preguntas_evaluacion
        (id_evaluacion_fk, enunciado_pregunta, tipo_pregunta, puntaje_pregunta, orden_pregunta)
    VALUES (?, ?, ?, ?, ?)
");
foreach ($preguntas_eval1 as $preg) {
    $stmt_preg->execute($preg);
}

// --- Opciones de la Pregunta 1 ---
$opciones = [
    // Pregunta 1: ¿Quién es el padre de los AG?
    [1, 'John Holland',           1, 'Correcto. John Holland introdujo los AG en su libro "Adaptation in Natural and Artificial Systems" (1975).', 1],
    [1, 'Charles Darwin',         0, 'Darwin propuso la teoría de la evolución, pero no los algoritmos genéticos computacionales.',                2],
    [1, 'Alan Turing',            0, 'Turing es el padre de la computación teórica, no de los AG.',                                              3],
    [1, 'John von Neumann',       0, 'Von Neumann contribuyó a la arquitectura de computadoras, no a los AG.',                                   4],
    // Pregunta 2: ¿Cómo se llama la medida?
    [2, 'Función de aptitud (Fitness Function)', 1, 'Correcto. La función de aptitud evalúa qué tan buena es cada solución candidata.',            1],
    [2, 'Función de costo',       0, 'La función de costo es el concepto opuesto (a minimizar). La aptitud es para maximizar.',                   2],
    [2, 'Función de mutación',    0, 'La mutación es un operador, no una medida de calidad.',                                                     3],
    [2, 'Función de distribución',0, 'No es el término correcto en el contexto de los AG.',                                                       4],
    // Pregunta 3: ¿Cuál NO es una operación estándar?
    [3, 'Derivación',             1, 'Correcto. La derivación es de cálculo diferencial, no es parte de los AG.',                                 1],
    [3, 'Selección',              0, 'La selección sí es una operación estándar de los AG.',                                                      2],
    [3, 'Cruzamiento',            0, 'El cruzamiento sí es una operación estándar de los AG.',                                                    3],
    [3, 'Mutación',               0, 'La mutación sí es una operación estándar de los AG.',                                                       4],
    // Pregunta 4: Representación binaria
    [4, 'Cromosoma',              1, 'Correcto. Un cromosoma es la representación de una solución candidata.',                                    1],
    [4, 'Gen',                    0, 'El gen es cada bit individual dentro del cromosoma.',                                                       2],
    [4, 'Alelo',                  0, 'El alelo es el valor específico de un gen (0 o 1), no el cromosoma completo.',                              3],
    [4, 'Genotipo',               0, 'Genotipo se refiere a la representación completa del organismo, no solo a la binaria.',                     4],
    // Pregunta 5: Verdadero/Falso
    [5, 'Verdadero',              1, 'Correcto. Los AG están inspirados en la selección natural y la genética de la evolución biológica.',         1],
    [5, 'Falso',                  0, 'Incorrecto. Los AG sí están inspirados en la evolución natural.',                                           2],
    // Pregunta 6: ¿Qué representa cada individuo?
    [6, 'Una solución candidata al problema',           1, 'Correcto. Cada individuo representa una posible solución al problema de optimización.',1],
    [6, 'Una generación completa',                      0, 'La generación es el conjunto de todos los individuos, no uno solo.',                  2],
    [6, 'Un operador genético',                         0, 'Los operadores son las funciones de selección, cruzamiento y mutación.',              3],
    [6, 'El criterio de parada del algoritmo',          0, 'El criterio de parada determina cuándo terminar el algoritmo, no es un individuo.',  4],
];

$stmt_opc = $pdo->prepare("
    INSERT INTO opciones_pregunta
        (id_pregunta_fk, texto_opcion, es_respuesta_correcta, explicacion_opcion, orden_opcion)
    VALUES (?, ?, ?, ?, ?)
");
foreach ($opciones as $opc) {
    $stmt_opc->execute($opc);
}
log_mensaje('ok', "6 preguntas y sus opciones de la Evaluación 1 insertadas.");

// ============================================================
// PASO 16: ACTIVIDADES DE CALIFICACIÓN (Curso 1)
// ============================================================
log_mensaje('info', "Insertando actividades de calificación...");

$actividades = [
    [1, 'Taller Práctico 1: Codificación de Cromosomas',       'Implementar funciones de codificación y decodificación binaria.',                   100.00, 10.00, 'taller'],
    [1, 'Quiz Módulo 2: Fundamentos Matemáticos',              'Evaluación corta sobre probabilidad y espacios de búsqueda.',                       100.00, 10.00, 'quiz'],
    [1, 'Taller Práctico 2: Implementar AG desde cero',        'Implementar un AG completo en Python para un problema de optimización dado.',       100.00, 20.00, 'taller'],
    [1, 'Proyecto Módulo 4: Uso de DEAP o PyGAD',              'Resolver el Problema del Viajante con DEAP, documentando el proceso.',              100.00, 20.00, 'proyecto'],
    [1, 'Participación y Foros de Discusión',                  'Participación activa en los foros del curso.',                                      100.00, 10.00, 'participacion'],
    [1, 'Proyecto Final: Aplicación Real de AG',               'Proyecto integrador: aplicar AG a un problema real definido por el estudiante.',    100.00, 30.00, 'proyecto'],
];

$stmt_act = $pdo->prepare("
    INSERT INTO actividades_calificacion
        (id_curso_fk, nombre_actividad, descripcion_actividad, puntaje_maximo, porcentaje_nota_final, tipo_actividad)
    VALUES (?, ?, ?, ?, ?, ?)
");
foreach ($actividades as $act) {
    $stmt_act->execute($act);
}
log_mensaje('ok', "6 actividades de calificación del Curso 1 insertadas.");

// ============================================================
// PASO 17: TESTIMONIOS PARA LA LANDING PAGE
// ============================================================
log_mensaje('info', "Insertando testimonios...");

$testimonios = [
    [null, 'Laura Mendoza',       'Ingeniera de Software en Bancolombia',
     'EduTech Academy transformó mi carrera. El curso de Machine Learning me abrió puertas que no imaginaba. Los profesores son expertos reales.',
     5, null, 2, 1],
    [null, 'Andrés Felipe Rueda', 'Data Scientist en Rappi Colombia',
     'El curso de Algoritmos Genéticos es el mejor que he tomado. Desde la teoría hasta proyectos reales. 100% recomendado.',
     5, null, 1, 2],
    [null, 'Valentina Torres',    'Estudiante de Ingeniería Informática - Universidad Nacional',
     'Empecé con el curso de Python y ahora estoy terminando Deep Learning. La plataforma es intuitiva y el contenido es de nivel internacional.',
     5, null, 2, 3],
    [null, 'Carlos Humberto Ríos','CTO de Startup Tecnológica',
     'Capacité a todo mi equipo con EduTech Academy. Los cursos de seguridad informática y bases de datos avanzadas fueron exactamente lo que necesitábamos.',
     5, null, 8, 4],
];

$stmt_test = $pdo->prepare("
    INSERT INTO testimonios
        (id_usuario_fk, nombre_para_mostrar, cargo_o_profesion, texto_testimonio,
         calificacion_estrellas, foto_testimonio, id_curso_fk, orden_visualizacion)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
foreach ($testimonios as $test) {
    $stmt_test->execute($test);
}
log_mensaje('ok', "4 testimonios insertados para la landing page.");

// ============================================================
// RESUMEN FINAL
// ============================================================
log_mensaje('ok', "✅ ¡SEED COMPLETADO EXITOSAMENTE!");
log_mensaje('info', "Resumen: Roles(3) | Usuarios(3) | Categorías(4) | Cursos(8) | Módulos(6) | Clases(4) | Evaluaciones(4) | Preguntas(6) | Actividades(6) | Testimonios(4)");
log_mensaje('info', "IMPORTANTE: Elimina o bloquea este archivo (seed.php) después de usarlo por seguridad.");

mostrar_resultado_y_salir();

// ============================================================
// FUNCIÓN: Mostrar resultados en HTML y salir
// ============================================================
function mostrar_resultado_y_salir() {
    global $log_mensajes;
    $estilos = [
        'ok'        => 'color:#16a34a; font-weight:bold;',
        'error'     => 'color:#dc2626; font-weight:bold;',
        'advertencia'=>'color:#d97706; font-weight:bold;',
        'info'      => 'color:#2563eb;',
    ];
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>EduTech Academy — Setup</title>
        <style>
            body { font-family: monospace; background: #0f172a; color: #e2e8f0; padding: 2rem; }
            h1   { color: #60a5fa; }
            .log { background: #1e293b; border-radius: 8px; padding: 1.5rem; line-height: 2; }
            .hora{ color: #64748b; }
        </style>
    </head>
    <body>
        <h1>🚀 EduTech Academy — Inicialización de Base de Datos</h1>
        <div class="log">
        <?php foreach ($log_mensajes as $msg): ?>
            <div>
                <span class="hora">[<?= $msg['hora'] ?>]</span>
                <span style="<?= $estilos[$msg['tipo']] ?? '' ?>"><?= htmlspecialchars($msg['texto']) ?></span>
            </div>
        <?php endforeach; ?>
        </div>
    </body>
    </html>
    <?php
    exit();
}

/*
 * ============================================================
 * RESUMEN DEL ARCHIVO: /cursoonline/database/seed.php
 * ============================================================
 * Script de inicialización de datos de la base de datos EduTech Academy.
 *
 * Proceso que ejecuta:
 *   1. Crea la base de datos 'db_edutechacademy' si no existe
 *   2. Establece conexión PDO directa al servidor MySQL
 *   3. Ejecuta el esquema SQL (schema.sql) para crear las 32 tablas
 *   4. Verifica si ya existen datos (evita duplicados - idempotente)
 *   5. Inserta: 3 roles, 3 usuarios de prueba (contraseña: abc12345)
 *   6. Inserta: 4 categorías, 4 medios de pago, 12 configuraciones
 *   7. Inserta: 8 cursos con datos completos
 *   8. Inserta: 8 competencias del curso Algoritmos Genéticos + otras
 *   9. Inserta: 4 ejemplos de código (Python, PHP)
 *  10. Inserta: 6 módulos del curso Algoritmos Genéticos
 *  11. Inserta: 4 clases del Módulo 1
 *  12. Inserta: 4 evaluaciones + 6 preguntas + 20 opciones de Evaluación 1
 *  13. Inserta: 6 actividades de calificación
 *  14. Inserta: 4 testimonios para la landing page
 *
 * SEGURIDAD: Este archivo debe eliminarse o bloquearse via .htaccess
 *            después de la ejecución inicial. La carpeta /database/
 *            ya tiene su propio .htaccess de protección.
 *
 * URL de ejecución: http://localhost/cursoonline/database/seed.php
 * (Solo accesible localmente durante el setup)
 *
 * Última actualización: Fase 1 — Fundamentos y Base de Datos
 * ============================================================
 */
?>
