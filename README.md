# Gestor de evaluaciones — Programación III

Proyecto del Trabajo Práctico N.º 2 para integrar PHP, JavaScript, JSON, AJAX y jQuery.

## Datos académicos

- **Institución:** Instituto Superior Gaspar L Benavento.
- **Carrera:** Tecnicatura Superior en Análisis y Desarrollo de Software.
- **Asignatura:** Programación III.
- **Docente:** Delfor Hernán Castro.
- **Autor:** Silvia Graciela Acosta

## Funcionalidades

- Registro de usuarios sin iniciar sesión.
- Inicio y cierre de sesión.
- Alta, edición y eliminación de exámenes propios.
- Alta, edición y eliminación de preguntas asociadas a los exámenes propios.
- Sorteo de una cantidad elegida de preguntas de un examen, sin repeticiones y sin mezclar datos de otros usuarios.
- Comunicación entre JavaScript y PHP mediante AJAX de jQuery y respuestas JSON.

## Requisitos

- Apache con PHP 8.0 o superior.
- Extensiones PHP `PDO`, `pdo_mysql` y `mbstring`.
- MySQL o MariaDB.
- Conexión a Internet para cargar jQuery desde su CDN.

## Instalación y ejecución con Apache

1. Copiar el proyecto en el directorio publicado por Apache. En la instalación local usada para este proyecto, la ruta es `/var/www/html/tp2_prog3`.
2. Iniciar Apache y MySQL/MariaDB si todavía no están activos:

   ```bash
   sudo systemctl start apache2
   sudo systemctl start mariadb
   ```

3. Crear la base y las tablas importando `evaluaciones_db.sql`. Por ejemplo, desde el directorio del proyecto en una instalación que permite administrar MySQL mediante el socket local:

   ```bash
   sudo mysql < evaluaciones_db.sql
   ```

   También se puede importar el archivo desde una herramienta de administración como Adminer o phpMyAdmin. El script crea la base `tp2_programacion3` y sus tablas.
4. Configurar la conexión PDO del servidor web con las variables de entorno `DB_HOST`, `DB_NAME`, `DB_USER` y `DB_PASSWORD`. Apache debe recibir esas variables en su propia configuración; no alcanza con definirlas solamente en una terminal. La aplicación incluye valores de desarrollo de respaldo en `controlador_evaluaciones.php`; para otras instalaciones, configurar las variables con las credenciales correspondientes. Se recomienda crear un usuario MySQL dedicado para la aplicación y no usar `root`.
5. Abrir **http://localhost/tp2_prog3/**. No hace falta iniciar `php -S` cuando se usa Apache.
6. Crear una cuenta desde **Crear una cuenta** e iniciar sesión desde **Iniciar sesión**. No hay cuentas de aplicación predefinidas. Una vez dentro, crear un examen y cargar sus preguntas para realizar un sorteo.

