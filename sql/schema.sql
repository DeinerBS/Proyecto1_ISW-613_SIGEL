
-- Esquema de base de datos --

-- Borra y recrea la BD completa
DROP DATABASE IF EXISTS sigel;
CREATE DATABASE sigel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sigel;

-- ---------------------------------------------------------

-- Roles: admin, operador, profesor
CREATE TABLE roles (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(25) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ---------------------------------------------------------

-- Usuarios
CREATE TABLE usuarios (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rol_id            INT UNSIGNED NOT NULL,
    nombre            VARCHAR(100) NOT NULL,
    correo            VARCHAR(150) NOT NULL UNIQUE,
    password_hash     VARCHAR(255) NOT NULL,
    activo            TINYINT(1)   NOT NULL DEFAULT 1,
    intentos_fallidos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bloqueado_hasta   DATETIME NULL,
    creado_en         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    eliminado_en      DATETIME NULL,
    CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES roles(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------

-- Tokens de Recordarme 
CREATE TABLE auth_tokens (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id     INT UNSIGNED NOT NULL,
    selector       CHAR(18) NOT NULL UNIQUE,
    validator_hash CHAR(64) NOT NULL,
    expira_en      DATETIME NOT NULL,
    CONSTRAINT fk_tokens_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------

-- Categorías de equipos
CREATE TABLE categorias (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre            VARCHAR(80) NOT NULL UNIQUE,
    dias_max_prestamo SMALLINT UNSIGNED NOT NULL,
    creado_en         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    eliminado_en      DATETIME NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------

-- Equipos
CREATE TABLE equipos (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT UNSIGNED NOT NULL,
    codigo       VARCHAR(30)  NOT NULL UNIQUE,
    nombre       VARCHAR(120) NOT NULL,
    estado       ENUM('operativo', 'mantenimiento', 'dado_de_baja') NOT NULL DEFAULT 'operativo',
    version      INT UNSIGNED NOT NULL DEFAULT 1,
    creado_en    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    eliminado_en DATETIME NULL,
    CONSTRAINT fk_equipos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------

-- Imágenes de equipos: un equipo puede tener varias
CREATE TABLE equipo_imagenes (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipo_id      INT UNSIGNED NOT NULL,
    nombre_archivo VARCHAR(100) NOT NULL,
    creado_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_imagenes_equipo FOREIGN KEY (equipo_id) REFERENCES equipos(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------

-- Préstamos: usuario_id  = profesor que recibe el equipo.  operador_id = operador que registra el préstamo
CREATE TABLE prestamos (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id   INT UNSIGNED NOT NULL,
    operador_id  INT UNSIGNED NOT NULL,
    estado       ENUM('solicitado', 'aprobado', 'entregado', 'devuelto', 'cancelado') NOT NULL DEFAULT 'solicitado',
    fecha_inicio DATETIME NOT NULL,
    fecha_fin    DATETIME NOT NULL,
    creado_en    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_prestamos_usuario  FOREIGN KEY (usuario_id)  REFERENCES usuarios(id),
    CONSTRAINT fk_prestamos_operador FOREIGN KEY (operador_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------

-- Detalle: qué equipos hay en cada préstamo
CREATE TABLE prestamo_detalle (
    prestamo_id      INT UNSIGNED NOT NULL,
    equipo_id        INT UNSIGNED NOT NULL,
    fecha_devolucion DATETIME NULL,
    PRIMARY KEY (prestamo_id, equipo_id),
    CONSTRAINT fk_detalle_prestamo FOREIGN KEY (prestamo_id) REFERENCES prestamos(id),
    CONSTRAINT fk_detalle_equipo   FOREIGN KEY (equipo_id)   REFERENCES equipos(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------

-- Bitácora: crear, editar y eliminar
CREATE TABLE bitacora (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id      INT UNSIGNED NULL,
    accion          VARCHAR(20) NOT NULL,
    entidad         VARCHAR(30) NOT NULL,
    entidad_id      INT UNSIGNED NULL,
    valores_antes   JSON NULL,
    valores_despues JSON NULL,
    ip              VARCHAR(45) NULL,
    creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_bitacora_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB;