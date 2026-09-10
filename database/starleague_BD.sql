-- StarLeague - Script SQL
-- Motor: MySQL / MariaDB (InnoDB)

CREATE DATABASE IF NOT EXISTS starleague
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE starleague;

-- Entidades base
CREATE TABLE usuario (
    id_usuario     INT AUTO_INCREMENT PRIMARY KEY,
    nombre         VARCHAR(100)  NOT NULL,
    email          VARCHAR(150)  NOT NULL UNIQUE,
    contrasena     VARCHAR(255)  NOT NULL,
    rol            ENUM('admin', 'organizador', 'jugador') NOT NULL DEFAULT 'jugador'
) ENGINE=InnoDB;

CREATE TABLE categoria (
    id_categoria INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- La categoria es un dato propio de cada deporte (catalogos.js: deporte.categoria),
-- no una eleccion independiente del torneo
CREATE TABLE deporte (
    id_deporte        INT AUTO_INCREMENT PRIMARY KEY,
    nombre            VARCHAR(100) NOT NULL UNIQUE,
    id_categoria      INT NOT NULL,
    tipo_participante ENUM('equipos', 'individual') NOT NULL,
    titulares         TINYINT NOT NULL,
    suplentes         TINYINT NOT NULL DEFAULT 0,
    CONSTRAINT fk_deporte_categoria
        FOREIGN KEY (id_categoria) REFERENCES categoria(id_categoria)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Roles de plantel por deporte (catalogos.js: deporte.configuracionEquipo.roles)
CREATE TABLE rol_deporte (
    id_rol     INT AUTO_INCREMENT PRIMARY KEY,
    id_deporte INT NOT NULL,
    nombre_rol VARCHAR(50) NOT NULL,
    CONSTRAINT fk_rol_deporte_deporte
        FOREIGN KEY (id_deporte) REFERENCES deporte(id_deporte)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT uq_rol_deporte UNIQUE (id_deporte, nombre_rol)
) ENGINE=InnoDB;

CREATE TABLE formato (
    id_formato INT AUTO_INCREMENT PRIMARY KEY,
    nombre     ENUM('liga', 'eliminacion_directa', 'sistema_suizo') NOT NULL UNIQUE
) ENGINE=InnoDB;

-- TORNEO ya no guarda id_categoria: la categoria se obtiene via deporte
CREATE TABLE torneo (
    id_torneo     INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(150) NOT NULL,
    fecha_inicio  DATE NOT NULL,
    fecha_fin     DATE NOT NULL,
    logo_path     VARCHAR(255),
    banner_path   VARCHAR(255),
    id_creador    INT NOT NULL,
    id_deporte    INT NOT NULL,
    id_formato    INT NOT NULL,
    CONSTRAINT fk_torneo_creador
        FOREIGN KEY (id_creador) REFERENCES usuario(id_usuario)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_torneo_deporte
        FOREIGN KEY (id_deporte) REFERENCES deporte(id_deporte)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_torneo_formato
        FOREIGN KEY (id_formato) REFERENCES formato(id_formato)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_torneo_fechas CHECK (fecha_fin >= fecha_inicio)
) ENGINE=InnoDB;

-- Especializacion "es": TORNEO -> CONFIG_LOL / CONFIG_VALORANT / CONFIG_AJEDREZ / CONFIG_TENIS
-- Columnas segun el formulario real de cada deporte en catalogos.js.configuracionesDeporte

CREATE TABLE config_lol (
    id_torneo      INT PRIMARY KEY,
    mejor_de       TINYINT,
    seleccion_lado ENUM('aleatorio', 'azul', 'rojo') NOT NULL DEFAULT 'aleatorio',
    CONSTRAINT fk_config_lol_torneo
        FOREIGN KEY (id_torneo) REFERENCES torneo(id_torneo)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE config_valorant (
    id_torneo       INT PRIMARY KEY,
    mejor_de        TINYINT,
    seleccion_mapas ENUM('aleatorio', 'organizador') NOT NULL DEFAULT 'aleatorio',
    CONSTRAINT fk_config_valorant_torneo
        FOREIGN KEY (id_torneo) REFERENCES torneo(id_torneo)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE config_ajedrez (
    id_torneo      INT PRIMARY KEY,
    ritmo_juego    ENUM('clasico', 'rapido', 'blitz') NOT NULL DEFAULT 'clasico',
    control_tiempo VARCHAR(20),
    CONSTRAINT fk_config_ajedrez_torneo
        FOREIGN KEY (id_torneo) REFERENCES torneo(id_torneo)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE config_tenis (
    id_torneo       INT PRIMARY KEY,
    sets_para_ganar TINYINT NOT NULL DEFAULT 2,
    usar_tiebreak   BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT fk_config_tenis_torneo
        FOREIGN KEY (id_torneo) REFERENCES torneo(id_torneo)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- EQUIPO + relacion M:N "integra" (EQUIPO-USUARIO)
CREATE TABLE equipo (
    id_equipo INT AUTO_INCREMENT PRIMARY KEY,
    nombre    VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE equipo_miembro (
    id_equipo  INT NOT NULL,
    id_usuario INT NOT NULL,
    PRIMARY KEY (id_equipo, id_usuario),
    CONSTRAINT fk_equipo_miembro_equipo
        FOREIGN KEY (id_equipo) REFERENCES equipo(id_equipo)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_equipo_miembro_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- "participa (equipo)" y "participa (individual)": dos relaciones M:N independientes
CREATE TABLE torneo_equipo (
    id_torneo INT NOT NULL,
    id_equipo INT NOT NULL,
    PRIMARY KEY (id_torneo, id_equipo),
    CONSTRAINT fk_torneo_equipo_torneo
        FOREIGN KEY (id_torneo) REFERENCES torneo(id_torneo)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_torneo_equipo_equipo
        FOREIGN KEY (id_equipo) REFERENCES equipo(id_equipo)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE torneo_usuario (
    id_torneo  INT NOT NULL,
    id_usuario INT NOT NULL,
    PRIMARY KEY (id_torneo, id_usuario),
    CONSTRAINT fk_torneo_usuario_torneo
        FOREIGN KEY (id_torneo) REFERENCES torneo(id_torneo)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_torneo_usuario_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Relacion 1:N "genera": TORNEO (1) -> PARTIDO (N)
CREATE TABLE partido (
    id_partido INT AUTO_INCREMENT PRIMARY KEY,
    id_torneo  INT NOT NULL,
    fecha      DATETIME NOT NULL,
    resultado  VARCHAR(50),
    CONSTRAINT fk_partido_torneo
        FOREIGN KEY (id_torneo) REFERENCES torneo(id_torneo)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Indices adicionales
CREATE INDEX idx_torneo_deporte         ON torneo(id_deporte);
CREATE INDEX idx_torneo_formato         ON torneo(id_formato);
CREATE INDEX idx_torneo_creador         ON torneo(id_creador);
CREATE INDEX idx_partido_torneo         ON partido(id_torneo);
CREATE INDEX idx_equipo_miembro_usuario ON equipo_miembro(id_usuario);
CREATE INDEX idx_torneo_equipo_equipo   ON torneo_equipo(id_equipo);
CREATE INDEX idx_torneo_usuario_usuario ON torneo_usuario(id_usuario);
CREATE INDEX idx_deporte_categoria      ON deporte(id_categoria);
CREATE INDEX idx_rol_deporte_deporte    ON rol_deporte(id_deporte);
