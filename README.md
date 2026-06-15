# Citas Colegio

Aplicación web en PHP para registrar y administrar citas o reuniones entre profesores y apoderados de un colegio. El sistema incluye autenticación para administradores y profesores, gestión de docentes, gestión de alumnos con apoderado titular/suplente, agenda de reuniones y una vista pública de las reuniones del día.

## Tabla de contenidos

- [Características](#características)
- [Tecnologías](#tecnologías)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Requisitos](#requisitos)
- [Configuración](#configuración)
- [Base de datos](#base-de-datos)
- [Ejecución local](#ejecución-local)
- [Uso de la aplicación](#uso-de-la-aplicación)
- [Importación por CSV](#importación-por-csv)
- [Variables de entorno](#variables-de-entorno)
- [Seguridad y despliegue](#seguridad-y-despliegue)
- [Endpoints principales](#endpoints-principales)
- [Mantenimiento](#mantenimiento)

## Características

- Inicio de sesión para usuarios con rol `admin` o `profesor` mediante correo o RUT de profesor.
- Panel privado para usuarios autenticados.
- Administración de profesores: creación, edición, activación/desactivación, datos de perfil docente e importación CSV.
- Administración de alumnos: creación, edición, activación/desactivación, curso, RUT y datos de apoderado titular/suplente.
- Agenda de reuniones profesor-apoderado con validación de conflictos por profesor, fecha y hora.
- Vista pública de reuniones del día sin inicio de sesión.
- Perfil de usuario para actualizar datos personales y contraseña.
- Endpoints JSON consumidos por JavaScript sin dependencias externas de frontend.

## Tecnologías

- PHP 8.x recomendado.
- MySQL o MariaDB.
- PDO para acceso a base de datos.
- HTML, CSS y JavaScript vanilla.
- Servidor web compatible con PHP, por ejemplo Apache, Nginx con PHP-FPM o el servidor embebido de PHP para desarrollo.

## Estructura del proyecto

```text
.
├── assets/
│   ├── css/styles.css              # Estilos de la aplicación
│   └── js/                         # Lógica frontend por módulo
├── backend/
│   ├── auth/                       # Login, logout, sesión y registro deshabilitado
│   ├── meetings/                   # API de reuniones
│   └── users/                      # API de profesores, alumnos, perfiles e importación CSV
├── config/
│   ├── database.php                # Conexión PDO principal
│   └── test.php                    # Prueba básica de conexión/configuración
├── includes/
│   ├── config-path.php             # Carga la configuración de base de datos
│   └── session.php                 # Helpers de sesión, roles y escape HTML
├── dashboard.php                   # Inicio privado
├── login.php                       # Pantalla de login
├── meetings.php                    # Gestión de reuniones
├── profile.php                     # Perfil del usuario autenticado
├── students.php                    # Gestión de alumnos
├── today-meetings.php              # Vista pública de reuniones del día
└── users.php                       # Gestión de profesores
```

## Requisitos

Antes de instalar, asegúrate de contar con:

1. PHP 8.0 o superior con extensión `pdo_mysql` habilitada.
2. MySQL 5.7+/MariaDB 10.3+.
3. Un servidor web apuntando a la raíz del proyecto.
4. Una base de datos creada para la aplicación.
5. Un usuario administrador inicial en la tabla `users`.

## Configuración

La conexión a base de datos se define en `config/database.php`. El archivo lee variables de entorno y, si no existen, usa valores por defecto.

Variables soportadas:

```bash
DB_HOST=localhost
DB_NAME=nombre_base_datos
DB_USER=usuario_base_datos
DB_PASS=contraseña_base_datos
```

> Recomendación: en producción no guardes credenciales reales dentro del repositorio. Configura estas variables desde el panel de hosting, Apache/Nginx, `.env` gestionado por el servidor o variables del sistema.

## Base de datos

El proyecto crea o ajusta automáticamente algunas tablas auxiliares cuando se usan sus módulos:

- `teacher_profiles`
- `students`
- `student_guardians`
- Clave foránea de `meetings.student_id` hacia `students.id`

Sin embargo, las tablas base `users` y `meetings` deben existir. Un esquema mínimo compatible es:

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'profesor', 'alumno') NOT NULL DEFAULT 'profesor',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_email (email),
    INDEX idx_users_role (role),
    INDEX idx_users_active (active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE meetings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    student_id INT NOT NULL,
    guardian_type ENUM('titular', 'suplente') NOT NULL DEFAULT 'titular',
    guardian_name VARCHAR(120) NOT NULL,
    guardian_email VARCHAR(150),
    guardian_phone VARCHAR(30),
    meeting_date DATE NOT NULL,
    meeting_time TIME NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_meetings_teacher
        FOREIGN KEY (teacher_id) REFERENCES users(id)
        ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_datetime (teacher_id, meeting_date, meeting_time),
    INDEX idx_meetings_date_time (meeting_date, meeting_time),
    INDEX idx_meetings_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Crear un administrador inicial

Genera un hash de contraseña con PHP:

```bash
php -r "echo password_hash('CambiaEstaClave123', PASSWORD_DEFAULT), PHP_EOL;"
```

Luego inserta el usuario administrador reemplazando el hash:

```sql
INSERT INTO users (name, email, password, role, active)
VALUES ('Administrador', 'admin@colegio.cl', '$2y$10$REEMPLAZA_ESTE_HASH', 'admin', 1);
```

Después de iniciar sesión, crea profesores y alumnos desde la interfaz.

## Ejecución local

1. Clona el repositorio:

   ```bash
   git clone <url-del-repositorio>
   cd CitasColegio
   ```

2. Crea la base de datos y las tablas base indicadas en [Base de datos](#base-de-datos).

3. Exporta las variables de entorno:

   ```bash
   export DB_HOST=127.0.0.1
   export DB_NAME=citas_colegio
   export DB_USER=root
   export DB_PASS=secret
   ```

4. Levanta el servidor embebido de PHP:

   ```bash
   php -S 127.0.0.1:8000
   ```

5. Abre la aplicación en:

   ```text
   http://127.0.0.1:8000
   ```

## Uso de la aplicación

### Roles

- `admin`: administra profesores, alumnos, reuniones y su perfil.
- `profesor`: gestiona o consulta sus reuniones y su perfil.
- `alumno`: se usa como referencia de datos en reuniones; no tiene inicio de sesión habilitado.

### Flujo recomendado

1. Iniciar sesión con un usuario administrador.
2. Crear o importar profesores desde `users.php`.
3. Crear o importar alumnos desde `students.php`.
4. Registrar reuniones desde `meetings.php`.
5. Consultar la vista pública del día en `today-meetings.php` si se necesita mostrar una pantalla sin autenticación.

## Importación por CSV

La aplicación incluye carga masiva para profesores y alumnos desde las pantallas de administración.

### Profesores

El importador acepta encabezados relacionados con:

- Nombre.
- Correo.
- RUT.
- Centro de costo.
- Teléfono o móvil.
- Contraseña, cuando el archivo la incluya.

### Alumnos

El importador acepta encabezados flexibles para:

- Nombre del alumno.
- RUT/RUN del alumno.
- Curso.
- Datos de apoderado titular.
- Datos de apoderado suplente.

Los archivos pueden usar coma, punto y coma o tabulación como separador. Se recomienda guardar los CSV en UTF-8.

## Variables de entorno

| Variable | Descripción | Valor por defecto |
| --- | --- | --- |
| `DB_HOST` | Host de MySQL/MariaDB | `localhost` |
| `DB_NAME` | Nombre de la base de datos | Definido en `config/database.php` |
| `DB_USER` | Usuario de la base de datos | Definido en `config/database.php` |
| `DB_PASS` | Contraseña de la base de datos | Definido en `config/database.php` |

## Seguridad y despliegue

- Mueve la carpeta `config/` fuera de `public_html` cuando el hosting lo permita.
- Configura credenciales con variables de entorno y evita publicar contraseñas reales.
- Usa HTTPS en producción.
- Restringe el acceso al panel de administración solo a usuarios autorizados.
- Mantén PHP, MySQL/MariaDB y el servidor web actualizados.
- Realiza respaldos periódicos de la base de datos.
- Cambia la contraseña del administrador inicial después del primer acceso.

## Endpoints principales

### Autenticación

| Método | Ruta | Descripción |
| --- | --- | --- |
| `POST` | `backend/auth/login.php` | Inicia sesión con correo/RUT y contraseña. |
| `GET` | `backend/auth/logout.php` | Cierra la sesión. |
| `GET` | `backend/auth/check-session.php` | Verifica la sesión actual. |
| `POST` | `backend/auth/register.php` | Registro deshabilitado; devuelve error 403. |

### Usuarios, profesores y alumnos

| Método | Ruta | Descripción |
| --- | --- | --- |
| `GET` | `backend/users/list.php` | Lista profesores/admins o alumnos según filtro. |
| `POST` | `backend/users/create.php` | Crea profesores, administradores o alumnos de referencia. |
| `POST` | `backend/users/update.php` | Actualiza profesores, administradores o alumnos. |
| `POST` | `backend/users/delete.php` | Elimina o desactiva registros según reglas del backend. |
| `POST` | `backend/users/profile-update.php` | Actualiza el perfil del usuario autenticado. |
| `POST` | `backend/users/upload-teachers.php` | Importa profesores desde CSV. |
| `POST` | `backend/users/upload-students.php` | Importa alumnos desde CSV. |

### Reuniones

| Método | Ruta | Descripción |
| --- | --- | --- |
| `GET` | `backend/meetings/options.php` | Devuelve profesores y alumnos disponibles para agendar. |
| `GET` | `backend/meetings/list.php` | Lista reuniones según el rol del usuario. |
| `POST` | `backend/meetings/create.php` | Crea una reunión. |
| `POST` | `backend/meetings/delete.php` | Elimina una reunión. |
| `GET` | `backend/meetings/today-public.php` | Lista reuniones del día para la vista pública. |

## Mantenimiento

- Ejecuta validaciones de sintaxis PHP después de cambios importantes:

  ```bash
  find . -path './.git' -prune -o -name '*.php' -print -exec php -l {} \;
  ```

- Revisa periódicamente que los índices únicos de correo, RUT y horario de reunión estén activos.
- Si cambias estructura de tablas, actualiza esta documentación y los helpers de migración automática correspondientes.

## Licencia

Este repositorio no declara una licencia explícita. Agrega un archivo `LICENSE` antes de distribuirlo públicamente si necesitas definir permisos de uso, copia o modificación.
