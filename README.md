# Radio Miraflores — Sitio web en PHP puro + MySQL

Sitio de noticias e información para una radio online, con panel de administración.
Sin frameworks: solo PHP, MySQL, CSS y un poco de JavaScript. Pensado para **hosting compartido** (económico y ligero).

## Requisitos

- XAMPP (Apache + MySQL + PHP)
- Navegador web

## Estructura del proyecto

```
pagina web php/
├─ index.php              → página principal (una sola página con secciones)
├─ noticia.php            → detalle de una noticia
├─ database.sql           → base de datos (importar en phpMyAdmin)
├─ .htaccess              → protección de archivos
├─ assets/
│  ├─ css/estilos.css     → diseño del sitio
│  └─ js/main.js          → menú móvil
├─ includes/              → configuración y funciones (no tocar)
├─ uploads/               → imágenes subidas desde el panel
└─ admin/                 → panel de administración
```

## 1. Poner el sitio en XAMPP

1. Copia la carpeta completa `pagina web php` dentro de `C:\xampp\htdocs\`.
   - Quedará en: `C:\xampp\htdocs\pagina web php\`
2. Abre el **Panel de Control de XAMPP** y enciende **Apache** y **MySQL** (botón "Start" en ambos).

## 2. Crear la base de datos

1. Abre tu navegador y entra a: `http://localhost/phpmyadmin`
2. Arriba, haz clic en la pestaña **"Importar"**.
3. Pulsa **"Seleccionar archivo"** y elige `database.sql` (está en la carpeta del proyecto).
4. Pulsa **"Continuar"** / **"Importar"** (abajo).

Esto crea la base `radio_miraflores` con sus tablas y datos de ejemplo.

## 3. Ver el sitio

Entra en tu navegador a:

```
http://localhost/pagina web php/
```

(Nota: el espacio en la URL se escribe tal cual, el navegador lo maneja solo.)

## 4. Entrar al panel de administración

```
http://localhost/pagina web php/admin/
```

- **Usuario:** `admin`
- **Contraseña:** `admin123`

> **IMPORTANTE:** cambia la contraseña al entrar (menú "Cambiar contraseña").

## 5. Qué puedes gestionar desde el panel

| Sección      | Qué hace |
|--------------|----------|
| Dashboard    | Resumen y accesos rápidos |
| Noticias     | Crear, editar y borrar noticias + subir imágenes |
| Videos       | Agregar videos o transmisiones en vivo de YouTube |
| Testimonios  | Gestionar opiniones de oyentes |
| Categorías   | Organizar las noticias |
| Ajustes      | Nombre, logo, colores, textos, redes sociales, dirección y mapa |

## Cómo poner una transmisión en vivo

1. Panel → **Videos** → "+ Nuevo video".
2. Pega la URL del directo de YouTube.
3. En **Tipo** elige **"Transmisión en vivo"**.
4. Guarda. Aparecerá en la sección "Videos" de la portada.

## Cómo poner el mapa de Google Maps

1. Ve a Google Maps y busca tu dirección.
2. Haz clic en **"Compartir"** → pestaña **"Insertar un mapa"** → copia el enlace que empieza con `https://www.google.com/maps/embed?...`.
3. Panel → **Ajustes** → pégalo en **"URL para incrustar el mapa"** → Guardar.

## Solución de problemas

- **"Error de conexion con la base de datos"** → MySQL no está encendido o no importaste `database.sql`.
- **No carga CSS** → revisa que la carpeta esté dentro de `C:\xampp\htdocs\`.
- **No sube imágenes** → revisa que la carpeta `uploads` exista y tenga permisos de escritura.

## Seguridad (ya incluida)

- Contraseñas guardadas con hash seguro (`password_hash`).
- Consultas con sentencias preparadas (protege contra inyección SQL).
- Protección CSRF en todos los formularios.
- Subida de imágenes validada (solo JPG, PNG, GIF, WEBP; máx. 5 MB).
- Archivos sensibles protegidos con `.htaccess`.
