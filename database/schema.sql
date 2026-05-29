-- /cursoonline/database/schema.sql
-- ============================================================
-- Esquema completo de la base de datos — EduTech Academy
-- Motor: MySQL 8.x | Charset: utf8mb4
-- Convención: PKs terminan en _pk | FKs terminan en _fk
-- Campos obligatorios en toda tabla de negocio:
--   estado_activo, fecha_creacion, fecha_modificacion, modificado_por
-- ============================================================

-- --- Crear y seleccionar la base de datos ---
CREATE DATABASE IF NOT EXISTS `db_edutechacademy`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `db_edutechacademy`;

-- ============================================================
-- DESACTIVAR RESTRICCIONES FK DURANTE LA CREACIÓN
-- ============================================================
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- TABLA: roles
-- Catálogo de roles del sistema (admin_total, profesor, estudiante)
-- Sin dependencias externas
-- ============================================================
CREATE TABLE IF NOT EXISTS `roles` (
    `id_rol_pk`           INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `nombre_rol`          VARCHAR(50)      NOT NULL COMMENT 'Nombre único del rol: admin_total, profesor, estudiante',
    `descripcion_rol`     TEXT             NULL     COMMENT 'Descripción detallada del rol y sus permisos',
    `estado_activo`       TINYINT(1)       NOT NULL DEFAULT 1,
    `fecha_creacion`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_modificacion`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modificado_por`      INT UNSIGNED     NULL,
    PRIMARY KEY (`id_rol_pk`),
    UNIQUE KEY `uk_nombre_rol` (`nombre_rol`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catálogo de roles del sistema';

-- ============================================================
-- TABLA: categorias_curso
-- Categorías de los cursos (IA, Informática, Sistemas)
-- Sin dependencias externas
-- ============================================================
CREATE TABLE IF NOT EXISTS `categorias_curso` (
    `id_categoria_pk`         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `nombre_categoria`        VARCHAR(100)     NOT NULL COMMENT 'Ejemplo: Inteligencia Artificial, Ingeniería de Sistemas',
    `descripcion_categoria`   TEXT             NULL,
    `icono_categoria`         VARCHAR(100)     NULL     COMMENT 'Clase de ícono FontAwesome o nombre de imagen',
    `color_categoria`         VARCHAR(20)      NULL     COMMENT 'Color HEX para identificar visualmente la categoría',
    `estado_activo`           TINYINT(1)       NOT NULL DEFAULT 1,
    `fecha_creacion`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_modificacion`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modificado_por`          INT UNSIGNED     NULL,
    PRIMARY KEY (`id_categoria_pk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Categorías de clasificación de los cursos';

-- ============================================================
-- TABLA: medios_pago
-- Configuración de métodos de pago del sistema
-- Sin dependencias externas
-- ============================================================
CREATE TABLE IF NOT EXISTS `medios_pago` (
    `id_medio_pago_pk`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `nombre_medio_pago`           VARCHAR(100)     NOT NULL COMMENT 'Ejemplo: PayU, ePayco, Transferencia Bancaria',
    `descripcion_medio_pago`      TEXT             NULL,
    `tipo_integracion`            ENUM('pasarela_online','transferencia_bancaria','pse','efectivo','otro')
                                                   NOT NULL DEFAULT 'pasarela_online',
    `credenciales_configuracion`  TEXT             NULL     COMMENT 'JSON cifrado con credenciales API de la pasarela',
    `instrucciones_pago`          TEXT             NULL     COMMENT 'Instrucciones para el usuario (para transferencias)',
    `logo_medio_pago`             VARCHAR(255)     NULL,
    `es_medio_activo`             TINYINT(1)       NOT NULL DEFAULT 1,
    `estado_activo`               TINYINT(1)       NOT NULL DEFAULT 1,
    `fecha_creacion`              DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_modificacion`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modificado_por`              INT UNSIGNED     NULL,
    PRIMARY KEY (`id_medio_pago_pk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Configuración de métodos de pago disponibles';

-- ============================================================
-- TABLA: configuracion_sistema
-- Parámetros globales de configuración del sistema
-- Sin dependencias externas
-- ============================================================
CREATE TABLE IF NOT EXISTS `configuracion_sistema` (
    `id_configuracion_pk`     INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `clave_configuracion`     VARCHAR(100)     NOT NULL COMMENT 'Clave única del parámetro',
    `valor_configuracion`     TEXT             NOT NULL COMMENT 'Valor del parámetro',
    `descripcion_configuracion` TEXT           NULL     COMMENT 'Descripción de para qué sirve este parámetro',
    `tipo_dato`               ENUM('texto','numero','boolean','json','color')
                                               NOT NULL DEFAULT 'texto',
    `es_editable_por_admin`   TINYINT(1)       NOT NULL DEFAULT 1,
    `estado_activo`           TINYINT(1)       NOT NULL DEFAULT 1,
    `fecha_creacion`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_modificacion`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modificado_por`          INT UNSIGNED     NULL,
    PRIMARY KEY (`id_configuracion_pk`),
    UNIQUE KEY `uk_clave_configuracion` (`clave_configuracion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Parámetros de configuración global del sistema';

-- ============================================================
-- TABLA: usuarios
-- Todos los usuarios del sistema (todos los roles)
-- Dependencias: roles
-- ============================================================
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id_usuario_pk`                   INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `primer_nombre`                   VARCHAR(100)     NOT NULL,
    `segundo_nombre`                  VARCHAR(100)     NULL,
    `primer_apellido`                 VARCHAR(100)     NOT NULL,
    `segundo_apellido`                VARCHAR(100)     NULL,
    `correo_electronico`              VARCHAR(255)     NOT NULL,
    `contrasena_hash`                 VARCHAR(255)     NOT NULL COMMENT 'Contraseña cifrada con password_hash() de PHP',
    `id_rol_fk`                       INT UNSIGNED     NOT NULL,
    `numero_telefono`                 VARCHAR(20)      NULL,
    `foto_perfil`                     VARCHAR(255)     NULL     COMMENT 'Ruta relativa de la imagen de perfil',
    `tipo_documento_identidad`        ENUM('CC','CE','TI','Pasaporte','NIT','Otro')
                                                       NULL,
    `numero_documento_identidad`      VARCHAR(30)      NULL,
    `fecha_nacimiento`                DATE             NULL,
    `ciudad_residencia`               VARCHAR(100)     NULL,
    `departamento_residencia`         VARCHAR(100)     NULL,
    `pais_residencia`                 VARCHAR(100)     NULL     DEFAULT 'Colombia',
    `token_recuperacion_contrasena`   VARCHAR(255)     NULL     COMMENT 'Token para recuperación de contraseña',
    `fecha_expiracion_token`          DATETIME         NULL     COMMENT 'Fecha límite del token de recuperación',
    `ultimo_acceso`                   DATETIME         NULL,
    `estado_activo`                   TINYINT(1)       NOT NULL DEFAULT 1
                                                       COMMENT '0=inactivo (soft delete), 1=activo',
    `fecha_creacion`                  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_modificacion`              DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modificado_por`                  INT UNSIGNED     NULL,
    PRIMARY KEY (`id_usuario_pk`),
    UNIQUE KEY `uk_correo_electronico` (`correo_electronico`),
    INDEX `idx_id_rol_fk` (`id_rol_fk`),
    INDEX `idx_estado_activo` (`estado_activo`),
    INDEX `idx_numero_documento_identidad` (`numero_documento_identidad`),
    CONSTRAINT `fk_usuarios_rol`
        FOREIGN KEY (`id_rol_fk`) REFERENCES `roles` (`id_rol_pk`)
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Todos los usuarios del sistema con sus datos personales';

-- ============================================================
-- TABLA: cursos
-- Catálogo de cursos disponibles en la plataforma
-- Dependencias: categorias_curso, usuarios (profesor)
-- ============================================================
CREATE TABLE IF NOT EXISTS `cursos` (
    `id_curso_pk`             INT UNSIGNED         NOT NULL AUTO_INCREMENT,
    `id_categoria_fk`         INT UNSIGNED         NOT NULL,
    `id_profesor_fk`          INT UNSIGNED         NULL     COMMENT 'Profesor principal del curso',
    `titulo_curso`            VARCHAR(255)         NOT NULL,
    `resumen_corto`           TEXT                 NOT NULL COMMENT 'Resumen breve para listado de cursos (máx. 300 chars)',
    `descripcion_detallada`   LONGTEXT             NOT NULL COMMENT 'Descripción completa para página de detalle',
    `imagen_portada`          VARCHAR(255)         NULL     COMMENT 'Ruta de imagen principal del curso',
    `video_presentacion`      VARCHAR(500)         NULL     COMMENT 'URL del video de presentación (YouTube/Vimeo)',
    `tipo_video`              ENUM('youtube','vimeo','servidor_propio')
                                                   NULL     DEFAULT 'youtube',
    `total_horas`             INT UNSIGNED         NOT NULL DEFAULT 0,
    `total_clases_estimado`   INT UNSIGNED         NOT NULL DEFAULT 0,
    `duracion_meses`          TINYINT UNSIGNED     NOT NULL DEFAULT 6,
    `precio`                  DECIMAL(12,2)        NOT NULL DEFAULT 0.00 COMMENT 'Precio en COP',
    `precio_con_descuento`    DECIMAL(12,2)        NULL     COMMENT 'Precio con descuento si aplica',
    `nivel_dificultad`        ENUM('Principiante','Intermedio','Avanzado')
                                                   NOT NULL DEFAULT 'Principiante',
    `lenguaje_programacion`   VARCHAR(100)         NULL     COMMENT 'Ej: Python, PHP, C#, JavaScript',
    `requisitos_previos`      TEXT                 NULL,
    `certificado_disponible`  TINYINT(1)           NOT NULL DEFAULT 1,
    `numero_estudiantes`      INT UNSIGNED         NOT NULL DEFAULT 0 COMMENT 'Contador desnormalizado para rendimiento',
    `calificacion_promedio`   DECIMAL(3,2)         NOT NULL DEFAULT 0.00,
    `estado_activo`           TINYINT(1)           NOT NULL DEFAULT 1,
    `fecha_creacion`          DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_modificacion`      DATETIME             NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modificado_por`          INT UNSIGNED         NULL,
    PRIMARY KEY (`id_curso_pk`),
    INDEX `idx_id_categoria_fk` (`id_categoria_fk`),
    INDEX `idx_id_profesor_fk` (`id_profesor_fk`),
    INDEX `idx_nivel_dificultad` (`nivel_dificultad`),
    INDEX `idx_estado_activo` (`estado_activo`),
    CONSTRAINT `fk_cursos_categoria`
        FOREIGN KEY (`id_categoria_fk`) REFERENCES `categorias_curso` (`id_categoria_pk`)
        ON UPDATE CASCADE,
    CONSTRAINT `fk_cursos_profesor`
        FOREIGN KEY (`id_profesor_fk`) REFERENCES `usuarios` (`id_usuario_pk`)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catálogo de cursos disponibles en la plataforma';

-- ============================================================
-- TABLA: competencias_curso
-- Habilidades que adquiere el estudiante en cada curso
-- Dependencias: cursos
-- ============================================================
CREATE TABLE IF NOT EXISTS `competencias_curso` (
    `id_competencia_pk`       INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `id_curso_fk`             INT UNSIGNED     NOT NULL,
    `descripcion_competencia` TEXT             NOT NULL COMMENT 'Descripción de la competencia a adquirir',
    `icono_competencia`       VARCHAR(100)     NULL     COMMENT 'Ícono FontAwesome para la competencia',
    `orden_visualizacion`     TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `estado_activo`           TINYINT(1)       NOT NULL DEFAULT 1,
    `fecha_creacion`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_modificacion`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modificado_por`          INT UNSIGNED     NULL,
    PRIMARY KEY (`id_competencia_pk`),
    INDEX `idx_id_curso_fk` (`id_curso_fk`),
    CONSTRAINT `fk_competencias_curso`
        FOREIGN KEY (`id_curso_fk`) REFERENCES `cursos` (`id_curso_pk`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Competencias y habilidades que adquiere el estudiante en cada curso';

-- ============================================================
-- TABLA: ejemplos_codigo_curso
-- Fragmentos de código de programación mostrados en el detalle del curso
-- Dependencias: cursos
-- ============================================================
CREATE TABLE IF NOT EXISTS `ejemplos_codigo_curso` (
    `id_ejemplo_pk`           INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `id_curso_fk`             INT UNSIGNED     NOT NULL,
    `titulo_ejemplo`          VARCHAR(255)     NOT NULL,
    `descripcion_ejemplo`     TEXT             NULL     COMMENT 'Explicación de qué hace el código',
    `codigo_fuente`           LONGTEXT         NOT NULL COMMENT 'Código fuente a mostrar con highlight.js',
    `lenguaje_programacion`   VARCHAR(50)      NOT NULL DEFAULT 'python'
                                               COMMENT 'Lenguaje para highlight.js: python, php, javascript, cpp, csharp',
    `orden_visualizacion`     TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `estado_activo`           TINYINT(1)       NOT NULL DEFAULT 1,
    `fecha_creacion`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_modificacion`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modificado_por`          INT UNSIGNED     NULL,
    PRIMARY KEY (`id_ejemplo_pk`),
    INDEX `idx_id_curso_fk` (`id_curso_fk`),
    CONSTRAINT `fk_ejemplos_codigo_curso`
        FOREIGN KEY (`id_curso_fk`) REFERENCES `cursos` (`id_curso_pk`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Ejemplos de código de programación para mostrar en el detalle del curso';

-- ============================================================
-- TABLA: imagenes_curso
-- Galería de imágenes del curso (página de detalle)
-- Dependencias: cursos
-- ============================================================
CREATE TABLE IF NOT EXISTS `imagenes_curso` (
    `id_imagen_pk`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `id_curso_fk`             INT UNSIGNED     NOT NULL,
    `url_imagen`              VARCHAR(255)     NOT NULL COMMENT 'Ruta relativa de la imagen',
    `texto_alternativo`       VARCHAR(255)     NULL     COMMENT 'Texto ALT para accesibilidad y SEO',
    `titulo_imagen`           VARCHAR(255)     NULL,
    `orden_galeria`           TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `estado_activo`           TINYINT(1)       NOT NULL DEFAULT 1,
    `fecha_creacion`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_modificacion`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modificado_por`          INT UNSIGNED     NULL,
    PRIMARY KEY (`id_imagen_pk`),
    INDEX `idx_id_curso_fk` (`id_curso_fk`),
    CONSTRAINT `fk_imagenes_curso`
        FOREIGN KEY (`id_curso_fk`) REFERENCES `cursos` (`id_curso_pk`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Galería de imágenes alusivas para la página de detalle del curso';

-- ============================================================
-- TABLA: modulos_curso
-- Módulos o unidades temáticas dentro de cada curso
-- Dependencias: cursos
-- ============================================================
CREATE TABLE IF NOT EXISTS `modulos_curso` (
    `id_modulo_pk`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `id_curso_fk`             INT UNSIGNED     NOT NULL,
    `titulo_modulo`           VARCHAR(255)     NOT NULL,
    `descripcion_modulo`      TEXT             NULL,
    `total_horas_modulo`      INT UNSIGNED     NOT NULL DEFAULT 0,
    `orden_modulo`            TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `estado_activo`           TINYINT(1)       NOT NULL DEFAULT 1,
    `fecha_creacion`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_modificacion`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modificado_por`          INT UNSIGNED     NULL,
    PRIMARY KEY (`id_modulo_pk`),
    INDEX `idx_id_curso_fk` (`id_curso_fk`),
    CONSTRAINT `fk_modulos_curso`
        FOREIGN KEY (`id_curso_fk`) REFERENCES `cursos` (`id_curso_pk`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Módulos o unidades temáticas dentro de cada curso';

-- ============================================================
-- TABLA: clases_curso
-- Clases individuales dentro de cada módulo
-- Dependencias: modulos_curso
-- ============================================================
CREATE TABLE IF NOT EXISTS `clases_curso` (
    `id_clase_pk`             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `id_modulo_fk`            INT UNSIGNED     NOT NULL,
    `titulo_clase`            VARCHAR(255)     NOT NULL,
    `descripcion_clase`       TEXT             NULL,
    `url_video`               VARCHAR(500)     NULL     COMMENT 'URL del video de la clase',
    `tipo_video`              ENUM('youtube','vimeo','servidor_propio')
                                               NULL     DEFAULT 'youtube',
    `duracion_minutos`        INT UNSIGNED     NOT NULL DEFAULT 0,
    `orden_clase`             TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `es_clase_gratuita`       TINYINT(1)       NOT NULL DEFAULT 0
                                               COMMENT 'Si 1, la clase es visible sin comprar el curso (preview)',
    `estado_activo`           TINYINT(1)       NOT NULL DEFAULT 1,
    `fecha_creacion`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_modificacion`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modificado_por`          INT UNSIGNED     NULL,
    PRIMARY KEY (`id_clase_pk`),
    INDEX `idx_id_modulo_fk` (`id_modulo_fk`),
    CONSTRAINT `fk_clases_modulo`
        FOREIGN KEY (`id_modulo_fk`) REFERENCES `modulos_curso` (`id_modulo_pk`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Clases individuales dentro de cada módulo del curso';

-- ============================================================
-- TABLA: materiales_curso
-- Archivos y recursos de apoyo adjuntos a cada clase
-- Dependencias: clases_curso
-- ============================================================
CREATE TABLE IF NOT EXISTS `materiales_curso` (
    `id_material_pk`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `id_clase_fk`             INT UNSIGNED     NOT NULL,
    `nombre_material`         VARCHAR(255)     NOT NULL,
    `descripcion_material`    TEXT             NULL,
    `url_archivo`             VARCHAR(500)     NOT NULL COMMENT 'Ruta del archivo en el servidor',
    `tipo_archivo`            ENUM('pdf','docx','xlsx','pptx','zip','py','ipynb','otro')
                                               NOT NULL DEFAULT 'pdf',
    `tamano_archivo_kb`       INT UNSIGNED     NULL,
    `estado_activo`           TINYINT(1)       NOT NULL DEFAULT 1,
    `fecha_creacion`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_modificacion`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modificado_por`          INT UNSIGNED     NULL,
    PRIMARY KEY (`id_material_pk`),
    INDEX `idx_id_clase_fk` (`id_clase_fk`),
    CONSTRAINT `fk_materiales_clase`
        FOREIGN KEY (`id_clase_fk`) REFERENCES `clases_curso` (`id_clase_pk`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Materiales y recursos de apoyo de cada clase';

-- ============================================================
-- TABLA: actividades_calificacion
-- Tipos de actividades evaluables por el profesor en un curso
-- Dependencias: cursos
-- ============================================================
CREATE TABLE IF NOT EXISTS `actividades_calificacion` (
    `id_actividad_pk`             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `id_curso_fk`                 INT UNSIGNED     NOT NULL,
    `nombre_actividad`            VARCHAR(255)     NOT NULL COMMENT 'Ej: Taller Práctico 1, Proyecto Final, Quiz Módulo 2',
    `descripcion_actividad`       TEXT             NULL,
    `puntaje_maximo`              DECIMAL(5,2)     NOT NULL DEFAULT 100.00,
    `porcentaje_nota_final`       DECIMAL(5,2)     NOT NULL DEFAULT 0.00
                                                   COMMENT 'Porcentaje que representa en la nota final (0-100)',
    `tipo_actividad`              ENUM('taller','proyecto','quiz','examen','participacion','otro')
                                                   NOT NULL DEFAULT 'taller',
    `estado_activo`               TINYINT(1)       NOT NULL DEFAULT 1,
    `fecha_creacion`              DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_modificacion`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modificado_por`              INT UNSIGNED     NULL,
    PRIMARY KEY (`id_actividad_pk`),
    INDEX `idx_id_curso_fk` (`id_curso_fk`),
    CONSTRAINT `fk_actividades_curso`
        FOREIGN KEY (`id_curso_fk`) REFERENCES `cursos` (`id_curso_pk`)
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tipos de actividades evaluables por el profesor en cada curso';

-- ============================================================
-- TABLA: inscripciones
-- Relación estudiante <-> curso (registro de compras/matrículas)
-- Dependencias: usuarios, cursos
-- ============================================================
CREATE TABLE IF NOT EXISTS `inscripciones` (
    `id_inscripcion_pk`       INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `id_usuario_fk`           INT UNSIGNED     NOT NULL,
    `id_curso_fk`             INT UNSIGNED     NOT NULL,
    `fecha_inscripcion`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `monto_pagado`            DECIMAL(12,2)    NOT NULL DEFAULT 0.00,
    `estado_inscripcion`      ENUM('activa','completada','cancelada','suspendida')
                                               NOT NULL DEFAULT 'activa',
    `porcentaje_progreso`     DECIMAL(5,2)     NOT NULL DEFAULT 0.00
                                               COMMENT 'Porcentaje calculado de avance en el curso',
    `fecha_finalizacion`      DATETIME         NULL,
    `certificado_generado`    TINYINT(1)       NOT NULL DEFAULT 0,
    `url_certificado`         VARCHAR(255)     NULL,
    `estado_activo`           TINYINT(1)       NOT NULL DEFAULT 1,
    `fecha_creacion`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_modificacion`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modificado_por`          INT UNSIGNED     NULL,
    PRIMARY KEY (`id_inscripcion_pk`),
    UNIQUE KEY `uk_usuario_curso` (`id_usuario_fk`, `id_curso_fk`),
    INDEX `idx_id_usuario_fk` (`id_usuario_fk`),
    INDEX `idx_id_curso_fk` (`id_curso_fk`),
    INDEX `idx_estado_inscripcion` (`estado_inscripcion`),
    CONSTRAINT `fk_inscripciones_usuario`
        FOREIGN KEY (`id_usuario_fk`) REFERENCES `usuarios` (`id_usuario_pk`)
        ON UPDATE CASCADE,
    CONSTRAINT `fk_inscripciones_curso`
        FOREIGN KEY (`id_curso_fk`) REFERENCES `cursos` (`id_curso_pk`)
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Matrículas e inscripciones de estudiantes en cursos';

-- ============================================================
-- TABLA: progreso_clases
-- Seguimiento de clases vistas por cada estudiante inscrito
-- Dependencias: inscripciones, clases_curso
-- ============================================================
CREATE TABLE IF NOT EXISTS `progreso_clases` (
    `id_progreso_pk`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `id_inscripcion_fk`       INT UNSIGNED     NOT NULL,
    `id_clase_fk`             INT UNSIGNED     NOT NULL,
    `fecha_primera_vista`     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_ultima_vista`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `porcentaje_completado`   TINYINT UNSIGNED NOT NULL DEFAULT 0
                                               COMMENT 'Porcentaje del video visto (0-100)',
    `estado_completada`       TINYINT(1)       NOT NULL DEFAULT 0
                                               COMMENT '1 = clase completada (>=80% visto)',
    `estado_activo`           TINYINT(1)       NOT NULL DEFAULT 1,
    `fecha_creacion`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_modificacion`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modificado_por`          INT UNSIGNED     NULL,
    PRIMARY KEY (`id_progreso_pk`),
    UNIQUE KEY `uk_inscripcion_clase` (`id_inscripcion_fk`, `id_clase_fk`),
    INDEX `idx_id_inscripcion_fk` (`id_inscripcion_fk`),
    INDEX `idx_id_clase_fk` (`id_clase_fk`),
    CONSTRAINT `fk_progreso_inscripcion`
        FOREIGN KEY (`id_inscripcion_fk`) REFERENCES `inscripciones` (`id_inscripcion_pk`)
        ON UPDATE CASCADE,
    CONSTRAINT `fk_progreso_clase`
        FOREIGN KEY (`id_clase_fk`) REFERENCES `clases_curso` (`id_clase_pk`)
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Registro de progreso de clases vistas por estudiante';

-- ============================================================
-- TABLA: evaluaciones
-- Evaluaciones habilitadas cuando se cumple cierto número de clases
-- Dependencias: cursos
-- ============================================================
CREATE TABLE IF NOT EXISTS `evaluaciones` (
    `id_evaluacion_pk`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `id_curso_fk`                 INT UNSIGNED     NOT NULL,
    `titulo_evaluacion`           VARCHAR(255)     NOT NULL,
    `descripcion_evaluacion`      TEXT             NULL,
    `numero_clases_requeridas`    INT UNSIGNED     NOT NULL DEFAULT 1
                                                   COMMENT 'Número de clases completadas para habilitar esta evaluación',
    `puntaje_maximo`              DECIMAL(5,2)     NOT NULL DEFAULT 100.00,
    `puntaje_minimo_aprobacion`   DECIMAL(5,2)     NOT NULL DEFAULT 60.00,
    `tiempo_limite_minutos`       INT UNSIGNED     NULL     COMMENT 'NULL = sin límite de tiempo',
    `intentos_permitidos`         TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `orden_evaluacion`            TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `estado_activo`               TINYINT(1)       NOT NULL DEFAULT 1,
    `fecha_creacion`              DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_modificacion`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modificado_por`              INT UNSIGNED     NULL,
    PRIMARY KEY (`id_evaluacion_pk`),
    INDEX `idx_id_curso_fk` (`id_curso_fk`),
    CONSTRAINT `fk_evaluaciones_curso`
        FOREIGN KEY (`id_curso_fk`) REFERENCES `cursos` (`id_curso_pk`)
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Evaluaciones disponibles en cada curso, habilitadas por progreso';

-- ============================================================
-- TABLA: preguntas_evaluacion
-- Banco de preguntas de cada evaluación
-- Dependencias: evaluaciones
-- ============================================================
CREATE TABLE IF NOT EXISTS `preguntas_evaluacion` (
    `id_pregunta_pk`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `id_evaluacion_fk`        INT UNSIGNED     NOT NULL,
    `enunciado_pregunta`      TEXT             NOT NULL,
    `tipo_pregunta`           ENUM('opcion_multiple','verdadero_falso','respuesta_corta')
                                               NOT NULL DEFAULT 'opcion_multiple',
    `puntaje_pregunta`        DECIMAL(5,2)     NOT NULL DEFAULT 1.00,
    `imagen_pregunta`         VARCHAR(255)     NULL     COMMENT 'Imagen opcional para la pregunta',
    `orden_pregunta`          TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `estado_activo`           TINYINT(1)       NOT NULL DEFAULT 1,
    `fecha_creacion`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_modificacion`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modificado_por`          INT UNSIGNED     NULL,
    PRIMARY KEY (`id_pregunta_pk`),
    INDEX `idx_id_evaluacion_fk` (`id_evaluacion_fk`),
    CONSTRAINT `fk_preguntas_evaluacion`
        FOREIGN KEY (`id_evaluacion_fk`) REFERENCES `evaluaciones` (`id_evaluacion_pk`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Banco de preguntas de cada evaluación';

-- ============================================================
-- TABLA: opciones_pregunta
-- Opciones de respuesta para preguntas de opción múltiple
-- Dependencias: preguntas_evaluacion
-- ============================================================
CREATE TABLE IF NOT EXISTS `opciones_pregunta` (
    `id_opcion_pk`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `id_pregunta_fk`          INT UNSIGNED     NOT NULL,
    `texto_opcion`            TEXT             NOT NULL,
    `es_respuesta_correcta`   TINYINT(1)       NOT NULL DEFAULT 0,
    `explicacion_opcion`      TEXT             NULL     COMMENT 'Retroalimentación al estudiante al ver resultados',
    `orden_opcion`            TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `estado_activo`           TINYINT(1)       NOT NULL DEFAULT 1,
    `fecha_creacion`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_modificacion`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modificado_por`          INT UNSIGNED     NULL,
    PRIMARY KEY (`id_opcion_pk`),
    INDEX `idx_id_pregunta_fk` (`id_pregunta_fk`),
    CONSTRAINT `fk_opciones_pregunta`
        FOREIGN KEY (`id_pregunta_fk`) REFERENCES `preguntas_evaluacion` (`id_pregunta_pk`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Opciones de respuesta para preguntas de opción múltiple';

-- ============================================================
-- TABLA: intentos_evaluacion
-- Registro de intentos de evaluación por estudiante
-- Dependencias: inscripciones, evaluaciones
-- ============================================================
CREATE TABLE IF NOT EXISTS `intentos_evaluacion` (
    `id_intento_pk`           INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `id_inscripcion_fk`       INT UNSIGNED     NOT NULL,
    `id_evaluacion_fk`        INT UNSIGNED     NOT NULL,
    `numero_intento`          TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `fecha_inicio`            DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_fin`               DATETIME         NULL,
    `puntaje_obtenido`        DECIMAL(5,2)     NULL,
    `estado_intento`          ENUM('en_progreso','completado','abandonado','expirado')
                                               NOT NULL DEFAULT 'en_progreso',
    `estado_aprobado`         TINYINT(1)       NULL     COMMENT 'NULL=sin resultado, 1=aprobado, 0=reprobado',
    `estado_activo`           TINYINT(1)       NOT NULL DEFAULT 1,
    `fecha_creacion`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_modificacion`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modificado_por`          INT UNSIGNED     NULL,
    PRIMARY KEY (`id_intento_pk`),
    INDEX `idx_id_inscripcion_fk` (`id_inscripcion_fk`),
    INDEX `idx_id_evaluacion_fk` (`id_evaluacion_fk`),
    CONSTRAINT `fk_intentos_inscripcion`
        FOREIGN KEY (`id_inscripcion_fk`) REFERENCES `inscripciones` (`id_inscripcion_pk`)
        ON UPDATE CASCADE,
    CONSTRAINT `fk_intentos_evaluacion`
        FOREIGN KEY (`id_evaluacion_fk`) REFERENCES `evaluaciones` (`id_evaluacion_pk`)
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Intentos de evaluación realizados por cada estudiante';

-- ============================================================
-- TABLA: respuestas_evaluacion
-- Respuestas dadas por el estudiante en cada intento
-- Dependencias: intentos_evaluacion, preguntas_evaluacion, opciones_pregunta
-- ============================================================
CREATE TABLE IF NOT EXISTS `respuestas_evaluacion` (
    `id_respuesta_pk`             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `id_intento_fk`               INT UNSIGNED     NOT NULL,
    `id_pregunta_fk`              INT UNSIGNED     NOT NULL,
    `id_opcion_seleccionada_fk`   INT UNSIGNED     NULL     COMMENT 'Para opción múltiple',
    `respuesta_texto`             TEXT             NULL     COMMENT 'Para respuesta corta',
    `es_correcta`                 TINYINT(1)       NULL,
    `puntaje_obtenido`            DECIMAL(5,2)     NOT NULL DEFAULT 0.00,
    `estado_activo`               TINYINT(1)       NOT NULL DEFAULT 1,
    `fecha_creacion`              DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_modificacion`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modificado_por`              INT UNSIGNED     NULL,
    PRIMARY KEY (`id_respuesta_pk`),
    INDEX `idx_id_intento_fk` (`id_intento_fk`),
    INDEX `idx_id_pregunta_fk` (`id_pregunta_fk`),
    CONSTRAINT `fk_respuestas_intento`
        FOREIGN KEY (`id_intento_fk`) REFERENCES `intentos_evaluacion` (`id_intento_pk`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_respuestas_pregunta`
        FOREIGN KEY (`id_pregunta_fk`) REFERENCES `preguntas_evaluacion` (`id_pregunta_pk`)
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Respuestas del estudiante en cada intento de evaluación';

-- ============================================================
-- TABLA: calificaciones
-- Notas cargadas por el profesor por estudiante y actividad
-- Dependencias: inscripciones, actividades_calificacion, usuarios
-- ============================================================
CREATE TABLE IF NOT EXISTS `calificaciones` (
    `id_calificacion_pk`      INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `id_inscripcion_fk`       INT UNSIGNED     NOT NULL,
    `id_actividad_fk`         INT UNSIGNED     NOT NULL,
    `id_profesor_fk`          INT UNSIGNED     NOT NULL,
    `nota_obtenida`           DECIMAL(5,2)     NOT NULL DEFAULT 0.00,
    `observaciones_profesor`  TEXT             NULL,
    `fecha_calificacion`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `estado_activo`           TINYINT(1)       NOT NULL DEFAULT 1,
    `fecha_creacion`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_modificacion`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modificado_por`          INT UNSIGNED     NULL,
    PRIMARY KEY (`id_calificacion_pk`),
    UNIQUE KEY `uk_inscripcion_actividad` (`id_inscripcion_fk`, `id_actividad_fk`),
    INDEX `idx_id_inscripcion_fk` (`id_inscripcion_fk`),
    INDEX `idx_id_actividad_fk` (`id_actividad_fk`),
    INDEX `idx_id_profesor_fk` (`id_profesor_fk`),
    CONSTRAINT `fk_calificaciones_inscripcion`
        FOREIGN KEY (`id_inscripcion_fk`) REFERENCES `inscripciones` (`id_inscripcion_pk`)
        ON UPDATE CASCADE,
    CONSTRAINT `fk_calificaciones_actividad`
        FOREIGN KEY (`id_actividad_fk`) REFERENCES `actividades_calificacion` (`id_actividad_pk`)
        ON UPDATE CASCADE,
    CONSTRAINT `fk_calificaciones_profesor`
        FOREIGN KEY (`id_profesor_fk`) REFERENCES `usuarios` (`id_usuario_pk`)
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Calificaciones cargadas por el profesor para cada estudiante y actividad';

-- ============================================================
-- TABLA: transacciones_pago
-- Historial completo de pagos realizados
-- Dependencias: usuarios, cursos, medios_pago
-- ============================================================
CREATE TABLE IF NOT EXISTS `transacciones_pago` (
    `id_transaccion_pk`               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `id_usuario_fk`                   INT UNSIGNED     NOT NULL,
    `id_curso_fk`                     INT UNSIGNED     NOT NULL,
    `id_medio_pago_fk`                INT UNSIGNED     NOT NULL,
    `numero_referencia`               VARCHAR(100)     NOT NULL COMMENT 'Referencia única del pago',
    `monto_total`                     DECIMAL(12,2)    NOT NULL,
    `estado_transaccion`              ENUM('pendiente','aprobada','rechazada','cancelada','reembolsada')
                                                       NOT NULL DEFAULT 'pendiente',
    `fecha_transaccion`               DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `datos_respuesta_pasarela`        TEXT             NULL     COMMENT 'JSON con respuesta de la pasarela de pago',
    `ip_origen_transaccion`           VARCHAR(45)      NULL,
    `observaciones`                   TEXT             NULL,
    `estado_activo`                   TINYINT(1)       NOT NULL DEFAULT 1,
    `fecha_creacion`                  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_modificacion`              DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modificado_por`                  INT UNSIGNED     NULL,
    PRIMARY KEY (`id_transaccion_pk`),
    UNIQUE KEY `uk_numero_referencia` (`numero_referencia`),
    INDEX `idx_id_usuario_fk` (`id_usuario_fk`),
    INDEX `idx_id_curso_fk` (`id_curso_fk`),
    INDEX `idx_id_medio_pago_fk` (`id_medio_pago_fk`),
    INDEX `idx_estado_transaccion` (`estado_transaccion`),
    CONSTRAINT `fk_transacciones_usuario`
        FOREIGN KEY (`id_usuario_fk`) REFERENCES `usuarios` (`id_usuario_pk`)
        ON UPDATE CASCADE,
    CONSTRAINT `fk_transacciones_curso`
        FOREIGN KEY (`id_curso_fk`) REFERENCES `cursos` (`id_curso_pk`)
        ON UPDATE CASCADE,
    CONSTRAINT `fk_transacciones_medio_pago`
        FOREIGN KEY (`id_medio_pago_fk`) REFERENCES `medios_pago` (`id_medio_pago_pk`)
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Historial completo de transacciones de pago del sistema';

-- ============================================================
-- TABLA: notificaciones
-- Notificaciones del sistema para usuarios
-- ============================================================
CREATE TABLE IF NOT EXISTS `notificaciones` (
    `id_notificacion_pk`      INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `titulo_notificacion`     VARCHAR(255)     NOT NULL,
    `mensaje_notificacion`    TEXT             NOT NULL,
    `tipo_notificacion`       ENUM('info','alerta','exito','error','pago','evaluacion','calificacion','sistema')
                                               NOT NULL DEFAULT 'info',
    `id_rol_destinatario_fk`  INT UNSIGNED     NULL     COMMENT 'NULL = todos los roles',
    `id_usuario_emisor_fk`    INT UNSIGNED     NULL     COMMENT 'Admin que generó la notificación',
    `url_accion`              VARCHAR(500)     NULL     COMMENT 'URL a donde llevar al usuario al hacer clic',
    `estado_activo`           TINYINT(1)       NOT NULL DEFAULT 1,
    `fecha_creacion`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_modificacion`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modificado_por`          INT UNSIGNED     NULL,
    PRIMARY KEY (`id_notificacion_pk`),
    INDEX `idx_id_rol_destinatario_fk` (`id_rol_destinatario_fk`),
    INDEX `idx_tipo_notificacion` (`tipo_notificacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Notificaciones generadas por el sistema o el administrador';

-- ============================================================
-- TABLA: notificaciones_usuario
-- Relación entre notificaciones y usuarios destinatarios
-- Dependencias: notificaciones, usuarios
-- ============================================================
CREATE TABLE IF NOT EXISTS `notificaciones_usuario` (
    `id_notificacion_usuario_pk`  INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `id_notificacion_fk`          INT UNSIGNED     NOT NULL,
    `id_usuario_fk`               INT UNSIGNED     NOT NULL,
    `fecha_lectura`               DATETIME         NULL,
    `estado_leida`                TINYINT(1)       NOT NULL DEFAULT 0,
    `estado_activo`               TINYINT(1)       NOT NULL DEFAULT 1,
    `fecha_creacion`              DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_modificacion`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modificado_por`              INT UNSIGNED     NULL,
    PRIMARY KEY (`id_notificacion_usuario_pk`),
    INDEX `idx_id_notificacion_fk` (`id_notificacion_fk`),
    INDEX `idx_id_usuario_fk` (`id_usuario_fk`),
    INDEX `idx_estado_leida` (`estado_leida`),
    CONSTRAINT `fk_notif_usuario_notificacion`
        FOREIGN KEY (`id_notificacion_fk`) REFERENCES `notificaciones` (`id_notificacion_pk`)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_notif_usuario_usuario`
        FOREIGN KEY (`id_usuario_fk`) REFERENCES `usuarios` (`id_usuario_pk`)
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Notificaciones enviadas a usuarios específicos con estado de lectura';

-- ============================================================
-- TABLA: contacto_formulario
-- Mensajes del formulario de contacto público
-- Sin dependencias externas
-- ============================================================
CREATE TABLE IF NOT EXISTS `contacto_formulario` (
    `id_contacto_pk`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `nombre_completo`         VARCHAR(255)     NOT NULL,
    `correo_electronico`      VARCHAR(255)     NOT NULL,
    `numero_telefono`         VARCHAR(20)      NULL,
    `asunto_mensaje`          VARCHAR(255)     NOT NULL,
    `mensaje`                 TEXT             NOT NULL,
    `ip_origen`               VARCHAR(45)      NULL,
    `estado_respuesta`        ENUM('pendiente','en_proceso','respondido','archivado')
                                               NOT NULL DEFAULT 'pendiente',
    `respuesta_admin`         TEXT             NULL,
    `fecha_respuesta`         DATETIME         NULL,
    `estado_activo`           TINYINT(1)       NOT NULL DEFAULT 1,
    `fecha_creacion`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_modificacion`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modificado_por`          INT UNSIGNED     NULL,
    PRIMARY KEY (`id_contacto_pk`),
    INDEX `idx_estado_respuesta` (`estado_respuesta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Mensajes enviados desde el formulario de contacto público';

-- ============================================================
-- TABLA: testimonios
-- Testimonios de estudiantes para la landing page
-- Dependencias: usuarios
-- ============================================================
CREATE TABLE IF NOT EXISTS `testimonios` (
    `id_testimonio_pk`        INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `id_usuario_fk`           INT UNSIGNED     NULL     COMMENT 'NULL si el testimonio es manual/ficticio',
    `nombre_para_mostrar`     VARCHAR(100)     NOT NULL,
    `cargo_o_profesion`       VARCHAR(150)     NULL,
    `texto_testimonio`        TEXT             NOT NULL,
    `calificacion_estrellas`  TINYINT UNSIGNED NOT NULL DEFAULT 5
                                               COMMENT 'Calificación de 1 a 5 estrellas',
    `foto_testimonio`         VARCHAR(255)     NULL,
    `id_curso_fk`             INT UNSIGNED     NULL     COMMENT 'Curso sobre el que da el testimonio',
    `orden_visualizacion`     TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `estado_activo`           TINYINT(1)       NOT NULL DEFAULT 1,
    `fecha_creacion`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_modificacion`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modificado_por`          INT UNSIGNED     NULL,
    PRIMARY KEY (`id_testimonio_pk`),
    INDEX `idx_id_usuario_fk` (`id_usuario_fk`),
    INDEX `idx_id_curso_fk` (`id_curso_fk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Testimonios de estudiantes para mostrar en la landing page';

-- ============================================================
-- TABLAS DE SEGURIDAD Y AUDITORÍA
-- ============================================================

-- ============================================================
-- TABLA: log_accesos
-- Registro de todos los accesos (login/logout) al sistema
-- ============================================================
CREATE TABLE IF NOT EXISTS `log_accesos` (
    `id_log_acceso_pk`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `id_usuario_fk`               INT UNSIGNED     NULL     COMMENT 'NULL si el acceso falló antes de identificar al usuario',
    `correo_electronico_intento`  VARCHAR(255)     NULL     COMMENT 'Email ingresado en el formulario',
    `tipo_accion`                 ENUM('login_exitoso','login_fallido','logout','sesion_expirada','acceso_no_autorizado')
                                                   NOT NULL,
    `direccion_ip`                VARCHAR(45)      NOT NULL,
    `agente_navegador`            TEXT             NULL     COMMENT 'User-Agent del navegador',
    `panel_acceso`                ENUM('publico','admin') NOT NULL DEFAULT 'publico',
    `detalles_adicionales`        TEXT             NULL,
    `fecha_acceso`                DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_log_acceso_pk`),
    INDEX `idx_id_usuario_fk` (`id_usuario_fk`),
    INDEX `idx_tipo_accion` (`tipo_accion`),
    INDEX `idx_direccion_ip` (`direccion_ip`),
    INDEX `idx_fecha_acceso` (`fecha_acceso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Registro de todos los accesos al sistema (login, logout, fallos)';

-- ============================================================
-- TABLA: log_errores_sistema
-- Errores del sistema capturados automáticamente por PHP
-- ============================================================
CREATE TABLE IF NOT EXISTS `log_errores_sistema` (
    `id_log_error_pk`     INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `tipo_error`          VARCHAR(100)     NOT NULL COMMENT 'Ej: PDOException, RuntimeException, Error 404',
    `mensaje_error`       TEXT             NOT NULL,
    `archivo_error`       VARCHAR(500)     NULL,
    `linea_error`         INT UNSIGNED     NULL,
    `traza_error`         TEXT             NULL     COMMENT 'Stack trace completo',
    `id_usuario_fk`       INT UNSIGNED     NULL,
    `direccion_ip`        VARCHAR(45)      NULL,
    `url_afectada`        VARCHAR(500)     NULL,
    `datos_post`          TEXT             NULL     COMMENT 'Datos POST sanitizados (sin contraseñas)',
    `fecha_error`         DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_log_error_pk`),
    INDEX `idx_tipo_error` (`tipo_error`),
    INDEX `idx_fecha_error` (`fecha_error`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Errores del sistema capturados automáticamente por el manejador de errores PHP';

-- ============================================================
-- TABLA: log_intentos_fallidos
-- Control de intentos fallidos de login (anti fuerza bruta)
-- ============================================================
CREATE TABLE IF NOT EXISTS `log_intentos_fallidos` (
    `id_intento_fallido_pk`       INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `correo_electronico_intento`  VARCHAR(255)     NOT NULL,
    `direccion_ip`                VARCHAR(45)      NOT NULL,
    `numero_intentos`             INT UNSIGNED     NOT NULL DEFAULT 1,
    `fecha_primer_intento`        DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_ultimo_intento`        DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `fecha_bloqueo_hasta`         DATETIME         NULL,
    `estado_bloqueado`            TINYINT(1)       NOT NULL DEFAULT 0,
    PRIMARY KEY (`id_intento_fallido_pk`),
    INDEX `idx_correo_ip` (`correo_electronico_intento`(100), `direccion_ip`),
    INDEX `idx_direccion_ip` (`direccion_ip`),
    INDEX `idx_estado_bloqueado` (`estado_bloqueado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Control de intentos fallidos de login para prevención de fuerza bruta';

-- ============================================================
-- TABLA: log_actividad_usuario
-- Registro de acciones críticas realizadas por usuarios autenticados
-- Dependencias: usuarios
-- ============================================================
CREATE TABLE IF NOT EXISTS `log_actividad_usuario` (
    `id_log_actividad_pk`     INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `id_usuario_fk`           INT UNSIGNED     NOT NULL,
    `tipo_accion`             VARCHAR(100)     NOT NULL COMMENT 'Ej: CREAR_CURSO, ELIMINAR_USUARIO, CAMBIAR_CONTRASENA',
    `descripcion_accion`      TEXT             NOT NULL,
    `tabla_afectada`          VARCHAR(100)     NULL,
    `id_registro_afectado`    INT UNSIGNED     NULL,
    `datos_anteriores`        TEXT             NULL     COMMENT 'JSON con estado anterior del registro',
    `datos_nuevos`            TEXT             NULL     COMMENT 'JSON con estado nuevo del registro',
    `direccion_ip`            VARCHAR(45)      NULL,
    `fecha_actividad`         DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_log_actividad_pk`),
    INDEX `idx_id_usuario_fk` (`id_usuario_fk`),
    INDEX `idx_tipo_accion` (`tipo_accion`),
    INDEX `idx_tabla_afectada` (`tabla_afectada`),
    INDEX `idx_fecha_actividad` (`fecha_actividad`),
    CONSTRAINT `fk_log_actividad_usuario`
        FOREIGN KEY (`id_usuario_fk`) REFERENCES `usuarios` (`id_usuario_pk`)
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Auditoría de acciones críticas realizadas por usuarios autenticados';

-- ============================================================
-- TABLA: ips_bloqueadas
-- IPs bloqueadas por comportamiento malicioso
-- ============================================================
CREATE TABLE IF NOT EXISTS `ips_bloqueadas` (
    `id_ip_bloqueada_pk`      INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `direccion_ip`            VARCHAR(45)      NOT NULL,
    `motivo_bloqueo`          TEXT             NOT NULL,
    `tipo_bloqueo`            ENUM('temporal','permanente') NOT NULL DEFAULT 'temporal',
    `fecha_bloqueo`           DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_desbloqueo`        DATETIME         NULL     COMMENT 'NULL si es permanente',
    `id_usuario_bloqueo_fk`   INT UNSIGNED     NULL     COMMENT 'Admin que realizó el bloqueo manual',
    `estado_activo`           TINYINT(1)       NOT NULL DEFAULT 1,
    `fecha_creacion`          DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_modificacion`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `modificado_por`          INT UNSIGNED     NULL,
    PRIMARY KEY (`id_ip_bloqueada_pk`),
    UNIQUE KEY `uk_direccion_ip` (`direccion_ip`),
    INDEX `idx_estado_activo` (`estado_activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='IPs bloqueadas por comportamiento sospechoso o malicioso';

-- ============================================================
-- TABLA: tokens_csrf
-- Tokens CSRF activos para validación de formularios
-- ============================================================
CREATE TABLE IF NOT EXISTS `tokens_csrf` (
    `id_token_pk`         INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `token_valor`         VARCHAR(255)     NOT NULL,
    `id_sesion_php`       VARCHAR(255)     NOT NULL COMMENT 'ID de sesión PHP asociado al token',
    `nombre_formulario`   VARCHAR(100)     NULL     COMMENT 'Identificador del formulario protegido',
    `fecha_creacion`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `fecha_expiracion`    DATETIME         NOT NULL,
    `estado_usado`        TINYINT(1)       NOT NULL DEFAULT 0 COMMENT '1 = token ya usado (inválido)',
    PRIMARY KEY (`id_token_pk`),
    UNIQUE KEY `uk_token_valor` (`token_valor`),
    INDEX `idx_id_sesion_php` (`id_sesion_php`),
    INDEX `idx_fecha_expiracion` (`fecha_expiracion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tokens CSRF para validación de envíos de formularios';

-- ============================================================
-- REACTIVAR RESTRICCIONES FK
-- ============================================================
SET FOREIGN_KEY_CHECKS = 1;

/*
 * ============================================================
 * RESUMEN DEL ARCHIVO: /cursoonline/database/schema.sql
 * ============================================================
 * Esquema completo de la base de datos db_edutechacademy.
 *
 * Total de tablas: 32
 *
 * TABLAS DE CATÁLOGO (sin FK):
 *   - roles, categorias_curso, medios_pago, configuracion_sistema
 *
 * TABLAS DE USUARIOS:
 *   - usuarios
 *
 * TABLAS DE CURSOS:
 *   - cursos, competencias_curso, ejemplos_codigo_curso,
 *     imagenes_curso, modulos_curso, clases_curso, materiales_curso
 *     actividades_calificacion
 *
 * TABLAS ACADÉMICAS:
 *   - inscripciones, progreso_clases, evaluaciones,
 *     preguntas_evaluacion, opciones_pregunta, intentos_evaluacion,
 *     respuestas_evaluacion, calificaciones
 *
 * TABLAS DE NEGOCIO:
 *   - transacciones_pago, notificaciones, notificaciones_usuario,
 *     contacto_formulario, testimonios
 *
 * TABLAS DE SEGURIDAD Y AUDITORÍA:
 *   - log_accesos, log_errores_sistema, log_intentos_fallidos,
 *     log_actividad_usuario, ips_bloqueadas, tokens_csrf
 *
 * Convenciones:
 *   - PKs: id_[tabla]_pk | FKs: id_[tabla]_fk
 *   - Soft delete: campo estado_activo en tablas de negocio
 *   - Auditoría: fecha_creacion, fecha_modificacion, modificado_por
 *   - charset: utf8mb4 | collation: utf8mb4_unicode_ci
 *
 * Última actualización: Fase 1 — Fundamentos y Base de Datos
 * ============================================================
 */
