-- ============================================================
-- SEED COMPLETO: Curso "Algoritmos Genéticos" (id_curso = 1)
-- EduTech Academy
-- ============================================================
-- Este script completa TODOS los módulos con sus clases,
-- evaluaciones por módulo, preguntas y opciones de respuesta.
-- Ejecutar DESPUÉS de seed.php para reemplazar los datos
-- de prueba con contenido completo.
-- ============================================================

SET @id_curso = 1;

-- Desactivar restricciones para limpieza segura
SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET SQL_SAFE_UPDATES = 0;

-- ============================================================
-- 1. LIMPIEZA: eliminar datos anteriores del curso 1
-- (orden inverso de dependencias)
-- ============================================================
DELETE FROM calificaciones WHERE id_actividad_fk IN (
    SELECT id_actividad_pk FROM actividades_calificacion WHERE id_curso_fk = @id_curso
);
DELETE FROM actividades_calificacion WHERE id_curso_fk = @id_curso;

DELETE FROM respuestas_evaluacion WHERE id_intento_fk IN (
    SELECT id_intento_pk FROM intentos_evaluacion WHERE id_evaluacion_fk IN (
        SELECT id_evaluacion_pk FROM evaluaciones WHERE id_curso_fk = @id_curso
    )
);
DELETE FROM intentos_evaluacion WHERE id_evaluacion_fk IN (
    SELECT id_evaluacion_pk FROM evaluaciones WHERE id_curso_fk = @id_curso
);

-- evaluaciones CASCADE DELETE -> preguntas_evaluacion -> opciones_pregunta
DELETE FROM evaluaciones WHERE id_curso_fk = @id_curso;

DELETE FROM progreso_clases WHERE id_inscripcion_fk IN (
    SELECT id_inscripcion_pk FROM inscripciones WHERE id_curso_fk = @id_curso
);

DELETE FROM clases_curso WHERE id_modulo_fk IN (
    SELECT id_modulo_pk FROM modulos_curso WHERE id_curso_fk = @id_curso
);

DELETE FROM ejemplos_codigo_curso WHERE id_curso_fk = @id_curso;
DELETE FROM competencias_curso WHERE id_curso_fk = @id_curso;

ALTER TABLE evaluaciones           AUTO_INCREMENT = 1;
ALTER TABLE preguntas_evaluacion   AUTO_INCREMENT = 1;
ALTER TABLE opciones_pregunta      AUTO_INCREMENT = 1;
ALTER TABLE actividades_calificacion AUTO_INCREMENT = 1;
ALTER TABLE calificaciones         AUTO_INCREMENT = 1;

-- ============================================================
-- 2. CLASES (24 clases: 4 por módulo × 6 módulos)
-- ============================================================
INSERT INTO clases_curso (id_modulo_fk, titulo_clase, descripcion_clase, url_video, tipo_video, duracion_minutos, orden_clase, es_clase_gratuita) VALUES
-- Módulo 1: Introducción (orden_modulo = 1)
(1, 'Historia y fundamentos de los Algoritmos Genéticos',
 'Recorrido histórico desde John Holland hasta las aplicaciones modernas.',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', 120, 1, 1),
(1, 'Inspiración biológica: selección natural y genética',
 'Cómo Darwin inspira los algoritmos de optimización modernos.',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', 120, 2, 0),
(1, '¿Qué son los Algoritmos Genéticos? Conceptos clave',
 'Cromosomas, genes, población, aptitud, generaciones. El ciclo evolutivo completo.',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', 120, 3, 0),
(1, 'Aplicaciones modernas de AG en IA y Optimización',
 'Casos de uso reales: diseño de antenas NASA, trading algorítmico, optimización industrial.',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', 120, 4, 0),
-- Módulo 2: Fundamentos Matemáticos (orden_modulo = 2)
(2, 'Espacios de búsqueda y representación de soluciones',
 'Definición del espacio de búsqueda, representación binaria, entera y permutacional.',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', 120, 1, 0),
(2, 'Función de aptitud (Fitness)',
 'Cómo diseñar una función de aptitud. Maximización vs minimización. Restricciones.',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', 120, 2, 0),
(2, 'Probabilidad y estocasticidad en AG',
 'Fundamentos de probabilidad aplicados: selección estocástica, muestreo y convergencia.',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', 120, 3, 0),
(2, 'Teorema de los esquemas (Schema Theorem)',
 'Fundamento teórico que explica por qué funcionan los AG. Holland y el building block.',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', 120, 4, 0),
-- Módulo 3: Operadores Genéticos (orden_modulo = 3)
(3, 'Selección: ruleta, torneo y rango',
 'Métodos de selección proporcional, torneo determinístico y selección por rango.',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', 120, 1, 0),
(3, 'Cruzamiento (Crossover)',
 'Cruzamiento de un punto, multi-punto, uniforme. Cruzamiento ordenado para permutaciones.',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', 120, 2, 0),
(3, 'Mutación: bit-flip, swap e inversión',
 'Operadores de mutación para diferentes representaciones. Probabilidad de mutación.',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', 120, 3, 0),
(3, 'Elitismo y estrategias de reemplazo',
 'Preservación de los mejores individuos. Reemplazo generacional vs estado estable.',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', 120, 4, 0),
-- Módulo 4: Implementación en Python (orden_modulo = 4)
(4, 'AG desde cero con NumPy',
 'Implementación paso a paso: población binaria, fitness, selección, cruce, mutación.',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', 120, 1, 0),
(4, 'Introducción a DEAP',
 'Framework DEAP: Toolbox, tipos, algoritmos predefinidos (eaSimple, eaMuPlusLambda).',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', 120, 2, 0),
(4, 'PyGAD: Algoritmos Genéticos simplificados',
 'PyGAD: definición de fitness, crossover, mutación. Ejecución y obtención de resultados.',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', 120, 3, 0),
(4, 'Laboratorio: optimizar función con DEAP',
 'Hands-on: optimización de una función multimodal. Análisis de convergencia y parámetros.',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', 120, 4, 0),
-- Módulo 5: Aplicaciones Reales (orden_modulo = 5)
(5, 'Problema del Viajante (TSP) con AG',
 'Representación permutacional, cruzamiento PMX y mutación por intercambio para TSP.',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', 120, 1, 0),
(5, 'Optimización de hiperparámetros con AG',
 'Uso de AG para encontrar los mejores hiperparámetros de modelos de Machine Learning.',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', 120, 2, 0),
(5, 'Neuroevolución (NEAT, CNN-GA)',
 'Evolución de redes neuronales. NEAT: topologías evolutivas. Optimización de CNNs.',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', 120, 3, 0),
(5, 'Casos de estudio: industria y academia',
 'Diseño de alas, planificación de rutas, trading algorítmico, scheduling de producción.',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', 120, 4, 0),
-- Módulo 6: Temas Avanzados (orden_modulo = 6)
(6, 'Algoritmos Meméticos',
 'Combinación de AG con búsqueda local. Evolución cultural. Aplicaciones.',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', 120, 1, 0),
(6, 'Programación Genética (GP)',
 'Evolución de programas y expresiones. GP estándar, gramática y cartesiana.',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', 120, 2, 0),
(6, 'Optimización Multiobjetivo: NSGA-II',
 'Pareto optimalidad. NSGA-II: ranking no-dominado y crowding distance.',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', 120, 3, 0),
(6, 'Coevolución y Computación Evolutiva Distribuida',
 'Coevolución competitiva y cooperativa. AG paralelos y distribuidos (islas).',
 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', 120, 4, 0);

-- ============================================================
-- 3. EVALUACIONES (una por módulo, orden = orden_modulo)
-- ============================================================
INSERT INTO evaluaciones (id_evaluacion_pk, id_curso_fk, titulo_evaluacion, descripcion_evaluacion,
    numero_clases_requeridas, puntaje_maximo, puntaje_minimo_aprobacion,
    tiempo_limite_minutos, intentos_permitidos, orden_evaluacion) VALUES
(1, @id_curso, 'Evaluación 1: Introducción a los AG',
 'Evalúa fundamentos históricos, inspiración biológica y conceptos clave.', 4, 20, 14, 30, 2, 1),
(2, @id_curso, 'Evaluación 2: Fundamentos Matemáticos',
 'Evalúa espacios de búsqueda, función de aptitud, probabilidad y teorema de esquemas.', 8, 20, 14, 30, 2, 2),
(3, @id_curso, 'Evaluación 3: Operadores Genéticos',
 'Evalúa selección, cruzamiento, mutación y elitismo.', 12, 20, 14, 30, 2, 3),
(4, @id_curso, 'Evaluación 4: Implementación en Python',
 'Evalúa AG con NumPy, DEAP, PyGAD y laboratorios prácticos.', 16, 20, 14, 45, 2, 4),
(5, @id_curso, 'Evaluación 5: Aplicaciones Reales',
 'Evalúa TSP, optimización hiperparámetros, neuroevolución y casos de estudio.', 20, 20, 14, 45, 1, 5),
(6, @id_curso, 'Evaluación Final: Temas Avanzados',
 'Evalúa algoritmos meméticos, programación genética, NSGA-II y computación evolutiva distribuida.', 24, 20, 14, 60, 1, 6);

-- ============================================================
-- 4. PREGUNTAS (6 por evaluación × 6 evaluaciones = 36)
-- ============================================================
INSERT INTO preguntas_evaluacion (id_pregunta_pk, id_evaluacion_fk, enunciado_pregunta, tipo_pregunta, puntaje_pregunta, orden_pregunta) VALUES
-- Eval 1
(1,  1, '¿Quién es considerado el padre de los Algoritmos Genéticos?',                                                'opcion_multiple', 3, 1),
(2,  1, '¿En qué proceso biológico se inspiran los Algoritmos Genéticos?',                                             'opcion_multiple', 3, 2),
(3,  1, '¿Cuál de las siguientes NO es una operación estándar en un AG?',                                              'opcion_multiple', 3, 3),
(4,  1, '¿Cómo se denomina la representación de una solución candidata?',                                              'opcion_multiple', 3, 4),
(5,  1, 'Los Algoritmos Genéticos fueron desarrollados por John Holland en la década de 1960.',                        'verdadero_falso', 4, 5),
(6,  1, '¿Qué representa la población en un Algoritmo Genético?',                                                      'opcion_multiple', 4, 6),
-- Eval 2
(7,  2, '¿Qué es el espacio de búsqueda en un problema de optimización?',                                              'opcion_multiple', 3, 1),
(8,  2, '¿Qué tipo de representación es más adecuada para problemas de optimización combinatoria?',                    'opcion_multiple', 4, 2),
(9,  2, 'La función de aptitud (fitness) en un AG debe ser:',                                                          'opcion_multiple', 3, 3),
(10, 2, 'El teorema de los esquemas (Schema Theorem) explica:',                                                        'opcion_multiple', 4, 4),
(11, 2, 'Una población más grande siempre garantiza mejores resultados en un AG.',                                     'verdadero_falso', 3, 5),
(12, 2, '¿Qué papel juega la probabilidad en la selección por ruleta?',                                                'opcion_multiple', 3, 6),
-- Eval 3
(13, 3, '¿Qué método de selección elige individuos comparándolos en grupos pequeños?',                                 'opcion_multiple', 3, 1),
(14, 3, 'En el cruzamiento de un punto (single-point crossover), ¿qué ocurre?',                                        'opcion_multiple', 3, 2),
(15, 3, 'La mutación bit-flip consiste en:',                                                                           'opcion_multiple', 3, 3),
(16, 3, '¿Qué es el elitismo en un Algoritmo Genético?',                                                               'opcion_multiple', 4, 4),
(17, 3, 'Una tasa de mutación alta favorece la explotación de soluciones conocidas.',                                  'verdadero_falso', 4, 5),
(18, 3, '¿Qué operador de cruzamiento es más adecuado para representaciones permutacionales?',                         'opcion_multiple', 3, 6),
-- Eval 4
(19, 4, '¿Qué función de la biblioteca DEAP se usa para ejecutar un AG simple?',                                      'opcion_multiple', 4, 1),
(20, 4, 'En PyGAD, ¿cómo se define la función de aptitud?',                                                           'opcion_multiple', 3, 2),
(21, 4, '¿Cuál de estas NO es una característica de DEAP?',                                                           'opcion_multiple', 3, 3),
(22, 4, 'Para instalar DEAP se utiliza el comando:',                                                                   'opcion_multiple', 3, 4),
(23, 4, 'NumPy es necesario para implementar un AG desde cero en Python.',                                              'verdadero_falso', 4, 5),
(24, 4, '¿Qué es el HallOfFame en DEAP?',                                                                              'opcion_multiple', 3, 6),
-- Eval 5
(25, 5, '¿Qué representación es la más adecuada para resolver el TSP con AG?',                                         'opcion_multiple', 4, 1),
(26, 5, '¿Cómo se evalúa la aptitud en optimización de hiperparámetros con AG?',                                      'opcion_multiple', 3, 2),
(27, 5, 'NEAT (NeuroEvolution of Augmenting Topologies) evoluciona:',                                                  'opcion_multiple', 3, 3),
(28, 5, 'En el TSP, el cruzamiento PMX (Partially Mapped Crossover) sirve para:',                                      'opcion_multiple', 3, 4),
(29, 5, 'Los AG pueden aplicarse a problemas con múltiples objetivos en competencia.',                                 'verdadero_falso', 4, 5),
(30, 5, '¿Qué técnica permite manejar restricciones en un AG?',                                                        'opcion_multiple', 3, 6),
-- Eval 6
(31, 6, '¿Qué caracteriza a un Algoritmo Memético respecto a un AG clásico?',                                          'opcion_multiple', 4, 1),
(32, 6, 'En Programación Genética (GP), ¿qué estructura representa una solución?',                                     'opcion_multiple', 3, 2),
(33, 6, 'NSGA-II es un algoritmo utilizado para:',                                                                     'opcion_multiple', 4, 3),
(34, 6, 'En optimización multiobjetivo, el Frente de Pareto representa:',                                              'opcion_multiple', 3, 4),
(35, 6, 'En coevolución competitiva, dos poblaciones evolucionan con objetivos opuestos.',                             'verdadero_falso', 3, 5),
(36, 6, '¿Qué ventaja tiene un AG distribuido (modelo de islas)?',                                                     'opcion_multiple', 3, 6);

-- ============================================================
-- 5. OPCIONES DE RESPUESTA (~140 registros)
-- ============================================================
INSERT INTO opciones_pregunta (id_pregunta_fk, texto_opcion, es_respuesta_correcta, explicacion_opcion, orden_opcion) VALUES
-- Preguntas 1-6 (Eval 1)
(1, 'John Holland',    1, 'Correcto. Holland introdujo los AG en 1975.', 1),
(1, 'Charles Darwin',  0, 'Darwin propuso la evolución natural, no los AG computacionales.', 2),
(1, 'Alan Turing',     0, 'Turing es padre de la computación teórica, no de los AG.', 3),
(1, 'John von Neumann',0, 'Von Neumann trabajó en arquitectura de computadoras y autómatas celulares.', 4),
(2, 'Selección natural y genética', 1, 'Correcto. Los AG imitan la selección natural, cruzamiento y mutación.', 1),
(2, 'Fotosíntesis',                 0, 'No está relacionado con los mecanismos evolutivos de los AG.', 2),
(2, 'Digestión celular',            0, 'No tiene relación con los AG.', 3),
(2, 'Síntesis de proteínas',        0, 'Proceso celular, no evolutivo.', 4),
(3, 'Derivación',    1, 'Correcto. La derivación es del cálculo diferencial, no un operador de AG.', 1),
(3, 'Selección',    0, 'La selección es un operador estándar en los AG.', 2),
(3, 'Cruzamiento',  0, 'El cruzamiento es un operador fundamental.', 3),
(3, 'Mutación',     0, 'La mutación introduce diversidad genética.', 4),
(4, 'Cromosoma',  1, 'Correcto. Un cromosoma es una solución candidata compuesta por genes.', 1),
(4, 'Gen',       0, 'Un gen es una unidad dentro del cromosoma.', 2),
(4, 'Alelo',     0, 'Valor específico de un gen (0 o 1 en binario).', 3),
(4, 'Fenotipo',  0, 'Manifestación física del genotipo.', 4),
(5, 'Verdadero', 1, 'Correcto. Holland desarrolló los AG en los años 60.', 1),
(5, 'Falso',     0, 'Incorrecto. Holland sí desarrolló los AG en los 60.', 2),
(6, 'Un conjunto de soluciones candidatas',                    1, 'Correcto. La población evoluciona generación tras generación.', 1),
(6, 'La mejor solución encontrada hasta el momento',          0, 'Esa es la descripción del mejor individuo (élite).', 2),
(6, 'El operador de selección',                               0, 'La selección es un operador, no la población.', 3),
(6, 'El criterio de parada del algoritmo',                    0, 'El criterio de parada determina cuándo terminar.', 4),
-- Preguntas 7-12 (Eval 2)
(7, 'El conjunto de todas las soluciones posibles', 1, 'Correcto. El espacio de búsqueda contiene todas las soluciones candidatas.', 1),
(7, 'Un subconjunto de soluciones evaluadas',       0, 'Eso describe la población, no el espacio de búsqueda.', 2),
(7, 'La función objetivo del problema',             0, 'La función objetivo evalúa soluciones.', 3),
(7, 'El conjunto de operadores genéticos',          0, 'Los operadores son mecanismos de búsqueda.', 4),
(8, 'Representación permutacional', 1, 'Correcto. Para TSP la permutación es la representación natural.', 1),
(8, 'Representación binaria',      0, 'Útil para continuos, no para combinatoria pura.', 2),
(8, 'Representación real',         0, 'Para variables continuas.', 3),
(8, 'Representación por árbol',    0, 'Se usa en Programación Genética.', 4),
(9, 'Una función que maximiza calidad de soluciones', 1, 'Correcto. Asigna valor según qué tan buena es cada solución.', 1),
(9, 'Una función que minimiza el error',              0, 'Minimizar error es de redes neuronales.', 2),
(9, 'Una función que cuenta la población',            0, 'No es función de aptitud.', 3),
(9, 'Una función que mide la diversidad genética',    0, 'La diversidad se mide con otras métricas.', 4),
(10, 'Por qué los building blocks se propagan en la población', 1, 'Correcto. Los esquemas cortos de alta aptitud crecen exponencialmente.', 1),
(10, 'Cómo se implementa la mutación en AG',                     0, 'No trata sobre implementación.', 2),
(10, 'Cómo se selecciona la población inicial',                  0, 'Aplica durante la evolución.', 3),
(10, 'Cómo se calcula la función de aptitud',                    0, 'Independiente del teorema.', 4),
(11, 'Verdadero', 0, 'Una población grande no garantiza mejores resultados; hay un equilibrio.', 1),
(11, 'Falso',     1, 'Correcto. Más población no garantiza mejores resultados.', 2),
(12, 'Probabilidad de selección proporcional a la aptitud', 1, 'Correcto. Cada individuo tiene probabilidad proporcional a su fitness.', 1),
(12, 'Selecciona al mejor individuo siempre',               0, 'Eso es elitismo, no ruleta.', 2),
(12, 'Elimina los peores individuos',                       0, 'Eso es reemplazo.', 3),
(12, 'Selecciona al azar sin criterio',                     0, 'Usa aptitud como criterio.', 4),
-- Preguntas 13-18 (Eval 3)
(13, 'Selección por torneo',        1, 'Correcto. Se toman k individuos al azar y se selecciona el mejor.', 1),
(13, 'Selección por ruleta',        0, 'Asigna probabilidad proporcional a la aptitud.', 2),
(13, 'Selección por rango',         0, 'Asigna probabilidades basadas en la posición.', 3),
(13, 'Selección aleatoria',         0, 'No tiene presión selectiva.', 4),
(14, 'Se intercambia material a partir de un punto de corte', 1, 'Correcto. Se intercambian las porciones finales de los padres.', 1),
(14, 'Se mezclan todos los genes de ambos padres',             0, 'Eso es cruzamiento uniforme.', 2),
(14, 'Se intercambia un solo gen',                              0, 'No es crossover estándar.', 3),
(14, 'Se copia el mejor padre',                                 0, 'Eso es clonación elitista.', 4),
(15, 'Cambiar un bit (0→1 o 1→0) en el cromosoma', 1, 'Correcto. Bit-flip invierte el valor de un bit.', 1),
(15, 'Intercambiar dos bits de posición',             0, 'Eso es mutación swap.', 2),
(15, 'Eliminar un bit del cromosoma',                 0, 'Cambiaría la longitud del cromosoma.', 3),
(15, 'Duplicar un segmento del cromosoma',            0, 'Es un tipo diferente de mutación.', 4),
(16, 'Preservar los mejores individuos para la siguiente generación', 1, 'Correcto. El elitismo asegura que los mejores no se pierdan.', 1),
(16, 'Eliminar los peores individuos',                               0, 'Eso es reemplazo, no elitismo.', 2),
(16, 'Aumentar la tasa de mutación',                                0, 'Eso es adaptación de parámetros.', 3),
(16, 'Seleccionar solo padres más aptos',                           0, 'Selección de padres es diferente.', 4),
(17, 'Verdadero', 0, 'Mutación alta favorece exploración, no explotación.', 1),
(17, 'Falso',     1, 'Correcto. Mutación alta = exploración; baja = explotación.', 2),
(18, 'Cruzamiento PMX (Partially Mapped Crossover)', 1, 'Correcto. PMX intercambia subsecuencias y resuelve conflictos.', 1),
(18, 'Cruzamiento de un punto',                      0, 'No preserva permutaciones sin corrección.', 2),
(18, 'Cruzamiento uniforme',                         0, 'Tampoco garantiza permutaciones válidas.', 3),
(18, 'Cruzamiento aritmético',                       0, 'Se usa para representación real.', 4),
-- Preguntas 19-24 (Eval 4)
(19, 'eaSimple',             1, 'Correcto. Ejecuta un AG con selección, cruce y mutación básicos.', 1),
(19, 'eaMuPlusLambda',       0, 'Estrategia evolutiva (μ+λ), no el AG simple.', 2),
(19, 'eaGenerateUpdate',     0, 'Algoritmo de evolución diferencial.', 3),
(19, 'runGA',                0, 'No existe en DEAP; PyGAD usa run().', 4),
(20, 'Función que recibe la población y devuelve aptitudes', 1, 'Correcto. fitness_function(instance, solution, solution_idx).', 1),
(20, 'Clase que hereda de GA',                                0, 'PyGAD usa callbacks.', 2),
(20, 'Archivo de configuración JSON',                          0, 'PyGAD usa código Python.', 3),
(20, 'Automática según el problema',                           0, 'Debe programarse por el usuario.', 4),
(21, 'Soporte nativo para programación genética', 1, 'DEAP sí soporta GP, no es una carencia.', 1),
(21, 'Paralelización automática',                  0, 'Requiere configurar map manualmente.', 2),
(21, 'Visualización integrada de convergencia',    0, 'Requiere matplotlib externamente.', 3),
(21, 'Interfaz gráfica de usuario',                0, 'DEAP no tiene GUI.', 4),
(22, 'pip install deap',            1, 'Correcto. DEAP se instala con pip.', 1),
(22, 'pip install pygad',           0, 'Eso instala PyGAD.', 2),
(22, 'conda install deap',          0, 'Conda también funciona pero pip es el estándar.', 3),
(22, 'npm install deap',            0, 'npm es de Node.js.', 4),
(23, 'Verdadero', 1, 'Correcto. NumPy proporciona arrays eficientes esenciales para implementar AG.', 1),
(23, 'Falso',     0, 'Aunque se puede usar Python puro, NumPy es la práctica estándar.', 2),
(24, 'Contenedor que almacena los mejores individuos', 1, 'Correcto. HallOfFame guarda los N mejores de toda la evolución.', 1),
(24, 'El criterio de parada del algoritmo',             0, 'No es el HoF.', 2),
(24, 'El operador de mutación por defecto',             0, 'Los operadores van en Toolbox.', 3),
(24, 'La función de aptitud predefinida',               0, 'La define el usuario.', 4),
-- Preguntas 25-30 (Eval 5)
(25, 'Representación permutacional',       1, 'Correcto. TSP requiere permutaciones sin repetir ciudades.', 1),
(25, 'Representación binaria',             0, 'No puede representar permutaciones sin violar restricciones.', 2),
(25, 'Representación real',                0, 'Para variables continuas.', 3),
(25, 'Representación por árbol',           0, 'Se usa en Programación Genética.', 4),
(26, 'Entrenando un modelo ML y usando su precisión como fitness', 1, 'Correcto. Se entrena con los parámetros y se evalúa.', 1),
(26, 'Sumando los valores de los hiperparámetros',                  0, 'No es medida de calidad.', 2),
(26, 'Usando el tiempo de entrenamiento como fitness',              0, 'No refleja calidad del modelo.', 3),
(26, 'Contando el número de parámetros',                             0, 'No indica buen rendimiento.', 4),
(27, 'Topología y pesos de redes neuronales', 1, 'Correcto. NEAT evoluciona estructura y pesos.', 1),
(27, 'Solo los pesos de la red',              0, 'NEAT también cambia topología.', 2),
(27, 'Solo la función de activación',         0, 'NEAT evoluciona la arquitectura completa.', 3),
(27, 'El conjunto de datos de entrenamiento', 0, 'Los datos son fijos.', 4),
(28, 'Intercambiar subrutas y reparar duplicados mediante mapeo', 1, 'Correcto. PMX intercambia segmentos y usa mapeo.', 1),
(28, 'Mezclar aleatoriamente todas las ciudades',                 0, 'No preserva rutas prometedoras.', 2),
(28, 'Intercambiar dos ciudades de posición',                     0, 'Eso es mutación por intercambio.', 3),
(28, 'Copiar el mejor padre',                                     0, 'Eso es clonación.', 4),
(29, 'Verdadero', 1, 'Correcto. Los AG multiobjetivo manejan objetivos en conflicto simultáneamente.', 1),
(29, 'Falso',     0, 'Sí se pueden aplicar; existen NSGA-II, SPEA2, MOEA/D.', 2),
(30, 'Funciones de penalización en la aptitud', 1, 'Correcto. Se penalizan soluciones que violan restricciones.', 1),
(30, 'Eliminar soluciones inviables',           0, 'Eliminar reduce diversidad.', 2),
(30, 'Aumentar la población',                   0, 'No resuelve restricciones.', 3),
(30, 'Reducir la tasa de mutación',             0, 'No relacionado con restricciones.', 4),
-- Preguntas 31-36 (Eval 6)
(31, 'Incorpora búsqueda local para refinar soluciones', 1, 'Correcto. Combina exploración global con explotación local.', 1),
(31, 'Usa solo mutación sin cruzamiento',                 0, 'Usa ambos más búsqueda local.', 2),
(31, 'Trabaja con poblaciones más pequeñas',              0, 'No es la característica definitoria.', 3),
(31, 'No utiliza función de aptitud',                     0, 'Todo evolutivo necesita función de aptitud.', 4),
(32, 'Un árbol de sintaxis abstracta (AST)', 1, 'Correcto. En GP las soluciones son programas como árboles.', 1),
(32, 'Un cromosoma binario',                 0, 'Del AG clásico.', 2),
(32, 'Una permutación de enteros',           0, 'Se usa en problemas de ordenamiento.', 3),
(32, 'Una matriz de números reales',         0, 'Se usa en AG con representación real.', 4),
(33, 'Optimización multiobjetivo',                   1, 'Correcto. NSGA-II es el algoritmo multiobjetivo más popular.', 1),
(33, 'Programación Genética',                         0, 'GP tiene sus propios algoritmos.', 2),
(33, 'Optimización de un solo objetivo',              0, 'Diseñado para múltiples objetivos.', 3),
(33, 'Clasificación de datos',                        0, 'Es de optimización.', 4),
(34, 'El conjunto de soluciones no dominadas', 1, 'Correcto. Soluciones donde ningún objetivo mejora sin empeorar otro.', 1),
(34, 'La mejor solución encontrada',            0, 'En multiobjetivo no hay una sola mejor solución.', 2),
(34, 'El promedio de todas las soluciones',     0, 'No tiene significado en Pareto.', 3),
(34, 'Soluciones que violan restricciones',     0, 'Las inviables no forman parte del Frente.', 4),
(35, 'Verdadero', 1, 'Correcto. En coevolución competitiva las poblaciones tienen objetivos opuestos.', 1),
(35, 'Falso',     0, 'Sí existe coevolución competitiva, ej. depredador-presa.', 2),
(36, 'Preserva diversidad evolucionando subpoblaciones aisladas', 1, 'Correcto. Las islas mantienen diversidad y migran individuos.', 1),
(36, 'Elimina la necesidad de función de aptitud',                 0, 'Toda población necesita aptitud.', 2),
(36, 'Garantiza encontrar el óptimo global',                       0, 'Ningún AG lo garantiza.', 3),
(36, 'Reduce el tiempo a la mitad',                                0, 'No hay garantía de reducción.', 4);

-- ============================================================
-- 6. ACTIVIDADES DE CALIFICACIÓN
-- ============================================================
INSERT INTO actividades_calificacion (id_actividad_pk, id_curso_fk, nombre_actividad, descripcion_actividad, puntaje_maximo, porcentaje_nota_final, tipo_actividad) VALUES
(1, @id_curso, 'Taller Práctico 1: Codificación de Cromosomas',       'Implementar funciones de codificación y decodificación binaria.',                    100, 10, 'taller'),
(2, @id_curso, 'Quiz Módulo 2: Fundamentos Matemáticos',              'Evaluación corta sobre probabilidad y espacios de búsqueda.',                        100, 10, 'quiz'),
(3, @id_curso, 'Taller Práctico 2: Implementar AG desde cero',        'Implementar un AG completo en Python para un problema de optimización.',             100, 20, 'taller'),
(4, @id_curso, 'Proyecto Módulo 4: Uso de DEAP o PyGAD',              'Resolver el Problema del Viajante con DEAP.',                                       100, 20, 'proyecto'),
(5, @id_curso, 'Participación y Foros de Discusión',                  'Participación activa en los foros del curso.',                                       100, 10, 'participacion'),
(6, @id_curso, 'Proyecto Final: Aplicación Real de AG',               'Proyecto integrador: aplicar AG a un problema real.',                               100, 30, 'proyecto');

-- ============================================================
-- 7. RESTAURAR CONFIGURACIÓN
-- ============================================================
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
SET SQL_SAFE_UPDATES = 1;

-- ============================================================
-- RESUMEN
-- ============================================================
SELECT '✅ Curso 1 (Algoritmos Genéticos) completado:' AS mensaje
UNION ALL
SELECT CONCAT('  • Módulos: 6')
UNION ALL
SELECT CONCAT('  • Clases: 24 (4 por módulo)')
UNION ALL
SELECT CONCAT('  • Evaluaciones: 6 (una por módulo)')
UNION ALL
SELECT CONCAT('  • Preguntas: 36 (6 por evaluación)')
UNION ALL
SELECT CONCAT('  • Opciones: ~140')
UNION ALL
SELECT CONCAT('  • Actividades: 6');
