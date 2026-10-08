#!/bin/bash
# DCL para Docker. Se ejecuta SOLO la primera vez, después del esquema y del seed
# (docker-entrypoint-initdb.d). Quita a DB_USER el "ALL PRIVILEGES" que la imagen de
# MySQL le da por defecto y le deja solo lo que usa la aplicación (menor privilegio).
# Equivale a la parte de app_starleague de database/dcl_usuarios.sql.
set -e

mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" <<SQL
REVOKE ALL PRIVILEGES, GRANT OPTION FROM '${MYSQL_USER}'@'%';

ALTER USER '${MYSQL_USER}'@'%'
    WITH MAX_QUERIES_PER_HOUR 5000
         MAX_UPDATES_PER_HOUR 1000
         MAX_CONNECTIONS_PER_HOUR 200
         MAX_USER_CONNECTIONS 20;

GRANT SELECT, INSERT         ON \`${MYSQL_DATABASE}\`.usuario        TO '${MYSQL_USER}'@'%';
GRANT SELECT, INSERT, DELETE ON \`${MYSQL_DATABASE}\`.Torneo         TO '${MYSQL_USER}'@'%';
GRANT SELECT, INSERT, DELETE ON \`${MYSQL_DATABASE}\`.Equipo         TO '${MYSQL_USER}'@'%';
GRANT SELECT, INSERT         ON \`${MYSQL_DATABASE}\`.CrearEquipo    TO '${MYSQL_USER}'@'%';
GRANT SELECT, INSERT         ON \`${MYSQL_DATABASE}\`.UnirseEquipo   TO '${MYSQL_USER}'@'%';
GRANT SELECT, INSERT         ON \`${MYSQL_DATABASE}\`.ParticipaIndv  TO '${MYSQL_USER}'@'%';
GRANT SELECT, INSERT         ON \`${MYSQL_DATABASE}\`.ParticipaEquip TO '${MYSQL_USER}'@'%';
GRANT SELECT                 ON \`${MYSQL_DATABASE}\`.Partido        TO '${MYSQL_USER}'@'%';

FLUSH PRIVILEGES;
SQL
