# StarLeague - SGDM (Sistema de Gestión Deportiva Modular)

Plataforma web modular para la organización, administración y seguimiento de torneos deportivos, mentales y electrónicos.

Proyecto de tecnólogo de la información - Bachillerato Tecnológico Instituto Tecnológico Superior "Arias - Balparda" (UTU) - 2026.

`¡Entra a nuestra pagina!` _**--> [StarLeague](https://esmogg.github.io/StarLeague/) <--**_

<p align="left"
  <a href="https://esmogg.github.io/StarLeague/">
    <img src="images/logo.png" alt="StarLeague" width="300">
  </a>
</p>

## Equipo - StarLeague

| Integrante | Rol |
|---|---|
| Renzo Márquez  | Scrum Master / Desarollador Backend  |
| Mateo Osorio   | Desarollador Frontend |
| Lucas Lacassie | Encargado de Documentación |
| Fabián Taboada | Asistente de Documentación |

## Stack tecnológico

- __Frontend:__ HTML5, CSS3 (Mobile First / Flexbox), JavaScript, JSON
- __Backend:__ PHP (PDO, Arquitectura MVC)
- __Base de datos:__ MySQL
- __Servidor:__ Apache
- __Control de versiones:__ Git / GitHub
- __Despliegue:__ Docker

## Estructura del proyecto

```
StarLeague-sgdm/
│
├── css/
│   ├── equipos/
│   ├── torneos/
│   └── usuario/
│
├── js/
│   ├── equipos/
│   ├── torneos/
│   └── usuario/
│
├── html/
│   ├── login/
│   │   ├── codigo a reutilizar/
│   │   ├── configuracion/
│   │   ├── equipos/
│   │   ├── torneos/
│   │   └── usuario/
│   └── nologin/
│       ├── configuracion/
│       ├── torneos/
│       └── usuario/
│
├── images/
│   ├── bg/
│   ├── mascota/
│   └── user/
│
├── scripts/
│   └── scripts_monitoreo/
│
├── database/
│
├── php/
│   ├── auth/
│   ├── equipos/
│   └── torneos/
│
├── .gitattributes
├── index.html
└── README.md

```

## Entregas

* __Primera entrega:__ 27 de Julio.
* __Segunda entrega:__ 14 de Septiembre.
* __Entrega final:__ 23 de Octubre.
* __Defensa:__ 2-6 de Noviembre.

## Puesta en marcha

### Con Docker (recomendado)

```bash
cp .env.example .env      # y cambiar las contraseñas
docker compose up --build
```

Abre <http://localhost>. La primera vez se crean el esquema (`starleague_BD.sql`), los datos de prueba (`starleague_seed.sql`, todos con contraseña `Test1234`; el administrador es `admin`) y el DCL (`03_dcl_docker.sh`). Si cambias algo de la base, borra el volumen: `docker compose down -v`.

La aplicación se conecta con `DB_USER` (no con root) y ese usuario solo tiene `SELECT`/`INSERT` en cada tabla, más `DELETE` en `Torneo`, con límites de consultas y conexiones. La base no se expone fuera de la red de Docker.

### Sin Docker (XAMPP / Apache)

1. Como administrador de MySQL: `mysql -u root -p < database/starleague_BD.sql`, editar las dos contraseñas `CAMBIAR_CLAVE_*` de `database/dcl_usuarios.sql` y ejecutarlo. Crea `app_starleague` (aplicación) y `auditoria_starleague` (solo lectura, sin acceso al hash de contraseñas).
2. Copiar `php/config/config.local.example.php` a `php/config/config.local.php` con la contraseña de `app_starleague` (o usar las variables `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`). Ese archivo no se sube a git.
3. Copiar `apache/starleague.conf` a `/etc/httpd/conf.d/`, ajustar `ServerName`/`DocumentRoot`, habilitar `mod_rewrite` y `mod_headers`, y recargar Apache. El `.htaccess` de la raíz bloquea archivos internos y define las URLs limpias `/api/...`.

## Arquitectura PHP

```
php/
├── config/        Database.php (PDO Singleton), bootstrap.php, config.local.example.php
├── models/        Usuario, Equipo, Torneo, Partido
├── repositories/  UsuarioRepository, EquipoRepository, TorneoRepository, InscripcionRepository, PartidoRepository
├── auth/          crear_usuario.php, login.php, perfil.php        (controladores finos)
├── equipos/       crear_equipos.php
└── torneos/       crear_torneos.php, listar_torneos.php, eliminar_torneo.php, listar_resultados.php,
                   obtener_torneos.php, obtener_candidatos.php, procesar_inscripcion.php
```
