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

CREATE TABLE deporte (
    id_deporte INT AUTO_INCREMENT PRIMARY KEY,
    nombre     VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE formato (
    id_formato INT AUTO_INCREMENT PRIMARY KEY,
    nombre     ENUM('liga', 'eliminacion_directa', 'sistema_suizo') NOT NULL UNIQUE
) ENGINE=InnoDB;


-- TORNEO (entidad fuerte) + relaciones 1:N "crea", "clasifica",
-- "define", "usa" -> se resuelven como FKs en torneo

CREATE TABLE torneo (
    id_torneo     INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(150) NOT NULL,
    fecha_inicio  DATE NOT NULL,
    fecha_fin     DATE NOT NULL,
    logo_path     VARCHAR(255),
    banner_path   VARCHAR(255),
    id_creador    INT NOT NULL,
    id_categoria  INT NOT NULL,
    id_deporte    INT NOT NULL,
    id_formato    INT NOT NULL,
    CONSTRAINT fk_torneo_creador
        FOREIGN KEY (id_creador) REFERENCES usuario(id_usuario)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_torneo_categoria
        FOREIGN KEY (id_categoria) REFERENCES categoria(id_categoria)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_torneo_deporte
        FOREIGN KEY (id_deporte) REFERENCES deporte(id_deporte)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_torneo_formato
        FOREIGN KEY (id_formato) REFERENCES formato(id_formato)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_torneo_fechas CHECK (fecha_fin >= fecha_inicio)
) ENGINE=InnoDB;


-- Especialización "es": TORNEO -> CONFIG_LOL / CONFIG_VALORANT /
-- CONFIG_AJEDREZ / CONFIG_TENIS (1 a 1 identificador)

CREATE TABLE config_lol (
    id_torneo    INT PRIMARY KEY,
    modo_juego   VARCHAR(50),
    parche       VARCHAR(20),
    CONSTRAINT fk_config_lol_torneo
        FOREIGN KEY (id_torneo) REFERENCES torneo(id_torneo)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE config_valorant (
    id_torneo    INT PRIMARY KEY,
    modo_juego   VARCHAR(50),
    mapa_pool    VARCHAR(255),
    CONSTRAINT fk_config_valorant_torneo
        FOREIGN KEY (id_torneo) REFERENCES torneo(id_torneo)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE config_ajedrez (
    id_torneo        INT PRIMARY KEY,
    tiempo_control   VARCHAR(50),
    sistema_puntaje  VARCHAR(50),
    CONSTRAINT fk_config_ajedrez_torneo
        FOREIGN KEY (id_torneo) REFERENCES torneo(id_torneo)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE config_tenis (
    id_torneo         INT PRIMARY KEY,
    superficie        VARCHAR(50),
    sets_por_partido  TINYINT,
    CONSTRAINT fk_config_tenis_torneo
        FOREIGN KEY (id_torneo) REFERENCES torneo(id_torneo)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


-- EQUIPO + relación "integra" (M:N EQUIPO-USUARIO)

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


-- Relaciones "participa (equipo)" y "participa (individual)":
-- son dos relaciones M:N INDEPENDIENTES en el DER (dos rombos
-- distintos, sin atributos propios), no una sola tabla con XOR.
-- Se modelan como dos tablas puente separadas.

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



-- Relación "genera": TORNEO (1) -> PARTIDO (N)

CREATE TABLE partido (
    id_partido INT AUTO_INCREMENT PRIMARY KEY,
    id_torneo  INT NOT NULL,
    fecha      DATETIME NOT NULL,
    resultado  VARCHAR(50),
    CONSTRAINT fk_partido_torneo
        FOREIGN KEY (id_torneo) REFERENCES torneo(id_torneo)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


-- Índices adicionales
CREATE INDEX idx_torneo_categoria      ON torneo(id_categoria);
CREATE INDEX idx_torneo_deporte        ON torneo(id_deporte);
CREATE INDEX idx_torneo_formato        ON torneo(id_formato);
CREATE INDEX idx_torneo_creador        ON torneo(id_creador);
CREATE INDEX idx_partido_torneo        ON partido(id_torneo);
CREATE INDEX idx_equipo_miembro_usuario ON equipo_miembro(id_usuario);
CREATE INDEX idx_torneo_equipo_equipo   ON torneo_equipo(id_equipo);
CREATE INDEX idx_torneo_usuario_usuario ON torneo_usuario(id_usuario);