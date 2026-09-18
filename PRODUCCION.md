# Guía para subir a producción (Radio Miraflores)

Lista de tareas de seguridad y configuración que debes hacer **antes** de publicar el sitio en un hosting, y el procedimiento paso a paso.

---

## 1. Cambiar las credenciales de la base de datos

En local (XAMPP) el sitio usa `root` sin contraseña. En el hosting debes crear un usuario y contraseña dedicados.

### Paso a paso (cPanel, lo habitual en hosting compartido)

1. Entra al cPanel del hosting → sección **"Bases de datos MySQL"**.
2. Crea la **base de datos** (ej. `miraflores`). El hosting le agrega un prefijo automático: `tucpanel_miraflores`.
3. Crea el **usuario MySQL** con una contraseña fuerte. También llevará prefijo: `tucpanel_usuario`.
4. En **"Añadir usuario a la base de datos"**, asigna el usuario a la base y dale **"Todos los privilegios"**.
5. Entra a **phpMyAdmin** del hosting, selecciona la base recién creada y ve a la pestaña **Importar**.
6. Sube el archivo `database.sql` de este proyecto (crea las tablas y los datos iniciales).

### Editar `includes/config.php`

Justo antes de subir (o sube el archivo ya editado), cambia estos 4 datos por los del hosting:

```php
define('DB_HOST', 'localhost');               // en cPanel suele ser 'localhost'
define('DB_NAME', 'tucpanel_miraflores');     // nombre con prefijo del hosting
define('DB_USER', 'tucpanel_usuario');        // usuario con prefijo del hosting
define('DB_PASS', 'TuClaveSegura123!');       // la contraseña que creaste
```

> **Verificación:** sube todo por FTP y abre la página. Si ves el sitio normal, la conexión está bien. Si aparece "Error de conexión con la base de datos", revisa que los 4 datos coincidan exactamente (los prefijos del cPanel son fáciles de olvidar).

---

## 2. Cambiar el usuario y la contraseña del panel

El panel trae un usuario por defecto `admin` / `admin123`. Cámbialo al entrar:

1. Entra al panel: `tusitio.com/admin/`.
2. Ve al menú **"Mi cuenta"**.
3. Cambia el **nombre de usuario** (evita "admin") y la **contraseña** (usa mayúsculas, minúsculas, números y símbolos).

---

## 3. Activar HTTPS (certificado SSL)

1. En el cPanel, busca **"SSL/TLS Status"** o **"Certificados SSL"**.
2. Activa el certificado gratuito (Let's Encrypt) para tu dominio.
3. Verifica que el sitio cargue con `https://` y que el candado aparezca en el navegador.

---

## Checklist final

- [ ] Base de datos creada en el hosting e importado `database.sql`.
- [ ] `includes/config.php` con las credenciales del hosting (no `root`).
- [ ] Usuario del panel cambiado (no `admin`).
- [ ] Contraseña del panel cambiada y robusta.
- [ ] HTTPS activado.
- [ ] Página cargando correctamente con `https://`.

---

## 4. Comprobaciones finales antes de publicar

- [ ] PHP con las extensiones **gd**, **curl**, **pdo_mysql** y **mbstring** activas (sin ellas fallan las subidas, los enlaces de Facebook y algunos textos).
- [ ] Poner `expose_php = Off` en php.ini (el `.htaccess` ya intenta ocultar `X-Powered-By`).
- [ ] HTTPS activo: el `.htaccess` ya redirige a `https://` y activa HSTS cuando detecta SSL (en localhost no redirige, para no romper XAMPP).
- [ ] Importar `database.sql` en el hosting: incluye la tabla `intentos_login`, que es la que bloquea los ataques de fuerza bruta al panel.
- [ ] Copiar la carpeta `uploads` por FTP (las imagenes no se suben a git).
- [ ] Cambiar el usuario y la contrasena del panel (no dejar `admin` / `admin123`).
- [ ] Revisar que `https://tudominio/robots.txt` responde y que `/admin/` no aparece en Google.

---

## 5. Si no puedes entrar al panel (recuperar la contrasena)

No hay recuperacion por email: se hace desde la consola, en la carpeta del proyecto.

En local (XAMPP):

```
C:\xampp\php\php.exe reset-password.php MiClaveSegura123 admin
```

En el hosting, si tienes acceso SSH:

```
php reset-password.php MiClaveSegura123 admin
```

- El primer argumento es la contrasena nueva (minimo 8 caracteres) y el segundo el usuario (por defecto `admin`).
- Ejecutado sin argumentos, muestra los usuarios que existen.
- El archivo **solo funciona desde la consola**: si alguien lo abre en el navegador responde 403, y ademas el `.htaccess` lo bloquea.
- El usuario por defecto de `database.sql` es `admin` / `admin123`. **Cambialo** en cuanto entres (menu "Mi cuenta").
