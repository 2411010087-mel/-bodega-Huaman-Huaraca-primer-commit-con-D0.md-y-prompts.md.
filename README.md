# Bodega Doña Rosa

Aplicación PHP 8 y MySQL para registrar ventas y consultar el inventario.

## Despliegue en InfinityFree

### 1. Crear la cuenta y el sitio

1. Regístrate en [InfinityFree](https://www.infinityfree.com/) y confirma la cuenta.
2. Desde el Client Area, crea una cuenta de hosting y elige un dominio gratuito o asocia uno propio.
3. Abre el Control Panel de la cuenta de hosting y confirma que PHP 8 está seleccionado.

### 2. Crear la base de datos MySQL

1. En el Control Panel, abre **MySQL Databases**.
2. Crea una base de datos y guarda los valores que muestra el panel: nombre de la base, usuario, contraseña y hostname MySQL. El hostname puede ser distinto de `localhost`.
3. Abre **phpMyAdmin** desde el mismo panel y selecciona la base recién creada.
4. En la pestaña **Import**, elige `sql/bodega_produccion.sql`, conserva el formato SQL y pulsa **Import**. El script crea las tablas y carga los seis productos de ejemplo; no crea ni selecciona una base de datos.

### 3. Preparar la configuración

1. En el proyecto, copia `config/env.ejemplo.php` y nombra la copia `config/env.php`.
2. Edita `config/env.php` y reemplaza los valores de ejemplo por el hostname, usuario, contraseña y nombre exactos mostrados en **MySQL Databases**.
3. No subas `config/env.php` a un repositorio: está excluido por `.gitignore`. No publiques ni compartas sus credenciales.

La aplicación también acepta las variables de entorno `DB_HOST`, `DB_NAME`, `DB_USER` y `DB_PASSWORD`. Si no están definidas, `config/env.php` usa la configuración local de XAMPP (`localhost`, base `bodega`, usuario `root` y clave vacía).

### 4. Subir los archivos

1. En el Control Panel, abre **Online File Manager** y entra en `htdocs`.
2. Sube el contenido de la aplicación a `htdocs`, conservando las carpetas `assets`, `config` y `src` y los archivos PHP de la raíz. `index.php` debe quedar directamente dentro de `htdocs`.
3. Sube también `config/env.php` a `htdocs/config/` y verifica en el administrador de archivos que contiene los datos MySQL de InfinityFree. No reemplaces este archivo con `env.ejemplo.php` sin completar sus valores.
4. No es necesario subir la carpeta `tests`, PHPUnit ni los archivos de documentación o SQL una vez importado el esquema.

### 5. Comprobar el despliegue

1. Abre el dominio del sitio. Debe cargar la lista de productos.
2. Revisa que aparezcan los seis productos y sus existencias, incluido el atún con stock cero.
3. Abre **Registrar venta** y confirma que se carga el formulario. Puedes cancelar sin guardar para comprobar la conexión.
4. Si aparece un error de conexión, vuelve a **MySQL Databases** y verifica el hostname, nombre de base, usuario y contraseña de `config/env.php`.
