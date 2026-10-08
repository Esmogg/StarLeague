-- StarLeague - DCL (Data Control Language) y usuarios de BD con restricciones
-- Ejecutar DESPUÉS de starleague_BD.sql y como administrador:
--     mysql -u root -p < database/dcl_usuarios.sql
-- Antes de usarlo cambiar las dos contraseñas de abajo; la de app_starleague
-- debe coincidir con la de php/config/config.local.php.
-- Principio aplicado: menor privilegio. La aplicación web NO usa root.

USE starleague;

-- =====================================================================
-- 1. Usuario de la aplicación web (PHP)
-- =====================================================================
CREATE USER IF NOT EXISTS 'app_starleague'@'localhost'
    IDENTIFIED BY 'StarLeague2026!Pass';

-- Límites de recursos
ALTER USER 'app_starleague'@'localhost'
    WITH MAX_QUERIES_PER_HOUR 5000
         MAX_UPDATES_PER_HOUR 1000
         MAX_CONNECTIONS_PER_HOUR 200
         MAX_USER_CONNECTIONS 20;

-- Partir siempre desde cero para que el script sea repetible
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'app_starleague'@'localhost';

-- Solo lo que el código realmente hace (ver php/repositories/):
GRANT SELECT, INSERT ON starleague.usuario        TO 'app_starleague'@'localhost';
GRANT SELECT, INSERT, DELETE ON starleague.Torneo TO 'app_starleague'@'localhost';  -- DELETE: eliminar_torneo.php (los hijos se borran por ON DELETE CASCADE)
GRANT SELECT, INSERT, DELETE ON starleague.Equipo TO 'app_starleague'@'localhost';  -- DELETE: eliminar_equipo.php (los hijos se borran por ON DELETE CASCADE)
GRANT SELECT, INSERT ON starleague.CrearEquipo    TO 'app_starleague'@'localhost';
GRANT SELECT, INSERT ON starleague.UnirseEquipo   TO 'app_starleague'@'localhost';
GRANT SELECT, INSERT ON starleague.ParticipaIndv  TO 'app_starleague'@'localhost';
GRANT SELECT, INSERT ON starleague.ParticipaEquip TO 'app_starleague'@'localhost';
GRANT SELECT         ON starleague.Partido        TO 'app_starleague'@'localhost';
-- Sin UPDATE, DROP, ALTER, CREATE, GRANT ni FILE; DELETE solo sobre Torneo.

-- =====================================================================
-- 2. Usuario de auditoría / monitoreo (solo lectura)
-- =====================================================================
CREATE USER IF NOT EXISTS 'auditoria_starleague'@'localhost'
    IDENTIFIED BY 'StarLeague2026!Auditoria';

ALTER USER 'auditoria_starleague'@'localhost'
    WITH MAX_QUERIES_PER_HOUR 500
         MAX_USER_CONNECTIONS 2;

REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'auditoria_starleague'@'localhost';

-- Lectura de todo, salvo el hash de contraseña (permiso a nivel de columna)
GRANT SELECT (id_usuario, nombre, email, rol, fecha_creacion)
    ON starleague.usuario        TO 'auditoria_starleague'@'localhost';
GRANT SELECT ON starleague.Torneo         TO 'auditoria_starleague'@'localhost';
GRANT SELECT ON starleague.Equipo         TO 'auditoria_starleague'@'localhost';
GRANT SELECT ON starleague.CrearEquipo    TO 'auditoria_starleague'@'localhost';
GRANT SELECT ON starleague.UnirseEquipo   TO 'auditoria_starleague'@'localhost';
GRANT SELECT ON starleague.ParticipaIndv  TO 'auditoria_starleague'@'localhost';
GRANT SELECT ON starleague.ParticipaEquip TO 'auditoria_starleague'@'localhost';
GRANT SELECT ON starleague.Partido        TO 'auditoria_starleague'@'localhost';

-- =====================================================================
-- 3. Ejemplo de REVOKE puntual (retirar un permiso ya otorgado)
--    Si más adelante se amplía un permiso y hay que quitarlo:
--    REVOKE INSERT ON starleague.Torneo FROM 'app_starleague'@'localhost';
-- =====================================================================

FLUSH PRIVILEGES;

-- Verificación:
-- SHOW GRANTS FOR 'app_starleague'@'localhost';
-- SHOW GRANTS FOR 'auditoria_starleague'@'localhost';
