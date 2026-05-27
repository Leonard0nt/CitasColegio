# Backend de usuarios con PHP puro

Proyecto listo para cPanel básico o entorno local con PHP + MySQL/MariaDB.

## Incluye

- Login, registro y logout.
- Sesiones PHP.
- CRUD de usuarios desde panel admin.
- Roles: `admin`, `profesor`, `alumno`.
- Alumnos con apoderado titular y apoderado suplente.
- PHP puro + PDO.
- HTML, CSS y JS separados.

## Credenciales demo

- Email: `admin@demo.cl`
- Password: `Admin12345`

## Configuración local en Linux

1. Crear base de datos:

```sql
CREATE DATABASE php_users_backend CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'phpuser'@'localhost' IDENTIFIED BY '123456';
GRANT ALL PRIVILEGES ON php_users_backend.* TO 'phpuser'@'localhost';
FLUSH PRIVILEGES;
```

2. Importar base de datos nueva:

```bash
mariadb -u phpuser -p php_users_backend < database/database.sql
```

3. Editar `config/database.php`:

```php
$host = 'localhost';
$dbname = 'php_users_backend';
$username = 'phpuser';
$password = '123456';
```

4. Levantar app desde la raíz del proyecto:

```bash
php -S localhost:8000 -t public_html
```

5. Abrir:

```text
http://localhost:8000/login.php
```

## Si ya tenías instalada la versión anterior

Aplica la migración:

```bash
mariadb -u phpuser -p php_users_backend < database/migrate_roles_guardians.sql
```

Luego levanta la app nuevamente:

```bash
php -S localhost:8000 -t public_html
```

## Estructura

```text
php_users_backend/
├── config/
│   └── database.php
├── database/
│   ├── database.sql
│   └── migrate_roles_guardians.sql
└── public_html/
    ├── assets/
    │   ├── css/styles.css
    │   └── js/users.js
    ├── backend/
    │   ├── auth/
    │   └── users/
    ├── dashboard.php
    ├── login.php
    ├── register.php
    └── users.php
```

## Notas para cPanel

Sube el contenido de `public_html/` dentro del `public_html` real del hosting.

Deja la carpeta `config/` fuera del acceso público cuando sea posible, por ejemplo:

```text
/home/usuario_cpanel/config/database.php
/home/usuario_cpanel/public_html/
```

Luego ajusta `public_html/includes/config-path.php` si tu ruta cambia.


## Módulo de reuniones / consultas

Esta versión agrega el módulo `Reuniones`, disponible para usuarios con rol `admin` y `profesor`.

Campos de cada reunión:

- Profesor
- Alumno
- Apoderado titular o suplente
- Fecha
- Hora
- Estado: `por_atender` o `atendido`
- Observación opcional

Si ya tenías la base anterior instalada, ejecuta:

```bash
mariadb -u phpuser -p php_users_backend < database/migrate_meetings.sql
```

Luego levanta la app:

```bash
php -S localhost:8000 -t public_html
```

Entra a:

```text
http://localhost:8000/meetings.php
```

Para crear una reunión primero debes tener al menos:

1. Un usuario con rol `profesor`.
2. Un usuario con rol `alumno` con apoderado registrado.
