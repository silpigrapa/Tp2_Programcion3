CREATE DATABASE IF NOT EXISTS tp2_programacion3
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE tp2_programacion3;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS examen (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idUsuario INT UNSIGNED NOT NULL,
    nombreExamen VARCHAR(100) NOT NULL,
    UNIQUE KEY uq_examen_id_usuario (id, idUsuario),
    CONSTRAINT fk_examen_usuario
        FOREIGN KEY (idUsuario) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS preguntas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idExamen INT UNSIGNED NOT NULL,
    idUsuario INT UNSIGNED NOT NULL,
    textoPregunta TEXT NOT NULL,
    KEY idx_preguntas_examen_usuario (idExamen, idUsuario),
    CONSTRAINT fk_preguntas_examen_usuario
        FOREIGN KEY (idExamen, idUsuario)
        REFERENCES examen(id, idUsuario)
        ON DELETE CASCADE
) ENGINE=InnoDB;
