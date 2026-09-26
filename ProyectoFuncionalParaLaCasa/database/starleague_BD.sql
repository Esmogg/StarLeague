-- StarLeague - Script SQL Corregido y Ajustado al Modelo
-- Motor: MySQL / MariaDB (InnoDB)

CREATE DATABASE IF NOT EXISTS starleague
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE starleague;


CREATE TABLE IF NOT EXISTS usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(150) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'organizador', 'jugador') NOT NULL DEFAULT 'jugador'
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS Torneo (
    id_torneo INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL, 
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(500) NOT NULL,
    categoria ENUM('E-Sports', 'Tradicional') NOT NULL,
    disciplina ENUM('Ajedrez', 'Valorant', 'Tenis', 'League of Legends') NOT NULL,
    formato ENUM('Liga', 'Eliminacion Directa', 'Sistema Suizo') NOT NULL,
    cantParticipantes INT NOT NULL,
    fecha_inicio DATE not null,
    fecha_fin DATE not null, 
    CONSTRAINT fk_torneo_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS Equipo (
    id_equipo INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    disciplina ENUM('Ajedrez', 'Valorant', 'Tenis', 'League of Legends') NOT NULL
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS UnirseEquipo (
    id_equipo INT NOT NULL,
    id_usuario INT NOT NULL,
    PRIMARY KEY (id_equipo, id_usuario),
    CONSTRAINT fk_unirse_equipo_equipo
        FOREIGN KEY (id_equipo) REFERENCES Equipo(id_equipo)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_unirse_equipo_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS CrearEquipo (
    id_equipo INT NOT NULL,
    id_usuario INT NOT NULL,
    PRIMARY KEY (id_equipo, id_usuario),
    CONSTRAINT fk_crear_equipo_equipo
        FOREIGN KEY (id_equipo) REFERENCES Equipo(id_equipo)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_crear_equipo_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS ParticipaIndv (
    id_usuario INT NOT NULL,
    id_torneo INT NOT NULL,
    PRIMARY KEY (id_usuario, id_torneo),
    CONSTRAINT fk_participa_indv_usuario
        FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_participa_indv_torneo
        FOREIGN KEY (id_torneo) REFERENCES Torneo(id_torneo)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS ParticipaEquip (
    id_equipo INT NOT NULL,
    id_torneo INT NOT NULL,
    PRIMARY KEY (id_equipo, id_torneo),
    CONSTRAINT fk_participa_equip_equipo
        FOREIGN KEY (id_equipo) REFERENCES Equipo(id_equipo)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_participa_equip_torneo
        FOREIGN KEY (id_torneo) REFERENCES Torneo(id_torneo)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS Partido (
    id_partido INT AUTO_INCREMENT PRIMARY KEY,
    id_torneo INT NOT NULL,
    fecha DATE NOT NULL,
    resultado VARCHAR(100) NOT NULL,
    CONSTRAINT fk_partido_torneo
        FOREIGN KEY (id_torneo) REFERENCES Torneo(id_torneo)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;