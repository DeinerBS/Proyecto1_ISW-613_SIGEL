
--  --- Datos de prueba ---

-- Ejecutar DESPUÉS de schema.sql
--
-- Credenciales:
--   admin@example.com         / Admin123+
--   operador@example.com       / Opera123+
--   alli.rodriguez@example.com  / Profe123+
--   kay.garcia@example.com    / Profe123+
-- -------------------------------------------------------
USE sigel;

-- Roles
INSERT INTO roles (id, nombre) VALUES
    (1, 'admin'),
    (2, 'operador'),
    (3, 'profesor');
-- -------------------------------------------------------

-- Usuarios: un hash generado con password_hash() por contraseña
INSERT INTO usuarios (id, rol_id, nombre, correo, password_hash) VALUES
    (1, 1, 'Administrador SIGEL',  'admin@example.com',         '$2y$10$GgPUhvaE6PCxgdQ0RjeSy.wk1sbDgcYVTZeTggGWPVpxa2U/jUyNy'),
    (2, 2, 'Operador SIGEL', 'operador@example.com',      '$2y$10$cxuB0meqkPs2zu/T8clL3eKfDiGPsEG1WRiCo5yKnTYZbksSmLZVO'),
    (3, 3, 'Allison Rodríguez',        'alli.rodriguez@example.com', '$2y$10$8PSZV.TEBgYSvf398e4Muu/F05pR8GcSzlSFWZAHdGYee2q6QC6m6'),
    (4, 3, 'Kay García',           'kay.garcia@example.com',    '$2y$10$2sdYIrPfQXR59XqDxfxvH.CR4aNaqpjTtyAppV9Bi9seDpK71h/yW');
-- -------------------------------------------------------

-- Categorías con su máximo de días de préstamo
INSERT INTO categorias (id, nombre, dias_max_prestamo) VALUES
    (1, 'Proyectores', 3),
    (2, 'Laptops',     7),
    (3, 'Regletas',     5);
-- -------------------------------------------------------

-- Equipos
INSERT INTO equipos (id, categoria_id, codigo, nombre, estado) VALUES
    (1, 1, 'Proy-01', 'Proyector Epson X41',        'operativo'),
    (2, 1, 'PROY-02', 'Proyector BenQ MS560',       'operativo'),
    (3, 2, 'LAP-01',  'Laptop Dell Latitude 5420',  'operativo'),
    (4, 2, 'LAP-02',  'Laptop HP ProBook 440',      'mantenimiento'),
    (5, 3, 'Regl-01',  'Regleta Argom Blanca',       'operativo');
-- -------------------------------------------------------

-- Préstamos: uno ya devuelto (pasado) y uno aprobado (futuro)
-- Las fechas se calculan desde NOW() para que el seed siga funcionando en cualquier día
INSERT INTO prestamos (id, usuario_id, operador_id, estado, fecha_inicio, fecha_fin) VALUES
    (1, 3, 2, 'devuelto', DATE_SUB(NOW(), INTERVAL 10 DAY), DATE_SUB(NOW(), INTERVAL 8 DAY)),
    (2, 4, 2, 'aprobado', DATE_ADD(NOW(), INTERVAL 1 DAY),  DATE_ADD(NOW(), INTERVAL 3 DAY));
-- -------------------------------------------------------

-- Detalle de los préstamos
INSERT INTO prestamo_detalle (prestamo_id, equipo_id, fecha_devolucion) VALUES
    (1, 1, DATE_SUB(NOW(), INTERVAL 8 DAY)),
    (1, 3, DATE_SUB(NOW(), INTERVAL 8 DAY)),
    (2, 2, NULL),
    (2, 5, NULL);