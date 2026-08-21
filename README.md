# Radio Miraflores PHP - Versión Optimizada

Versión PHP pura optimizada para hosting compartido de bajo costo.

## Características

✅ **Ultra ligero** - Sin frameworks pesados
✅ **Caché APCu** - Respuestas en milisegundos
✅ **MySQL optimizado** - Consultas con índices
✅ **Imágenes WebP** - Conversión automática
✅ **Lazy loading** - Carga diferida de imágenes
✅ **CSS inline crítico** - Renderizado rápido
✅ **Minificado** - Assets optimizados
✅ **Seguro** - Protección contra XSS, CSRF, SQL injection

## Requisitos

- PHP 7.4+ (recomendado 8.0+)
- MySQL 5.7+ o MariaDB 10.3+
- APCu (opcional pero recomendado)
- GD Library (para optimización de imágenes)

## Instalación

### 1. Subir archivos al hosting

Sube todos los archivos a tu hosting compartido vía FTP o cPanel File Manager.

### 2. Configurar base de datos

Edita `config.php` con tus credenciales de MySQL:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'tu_base_de_datos');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
```

### 3. Ejecutar instalación

Visita en tu navegador:
```
http://tudominio.com/install.php
```

Esto creará las tablas y migrará los datos desde el proyecto Laravel.

### 4. Eliminar install.php

Por seguridad, elimina el archivo `install.php` después de la instalación.

### 5. Configurar admin

Edita las credenciales en `config.php`:

```php
define('ADMIN_EMAIL', 'tu@email.com');
define('ADMIN_PASSWORD', 'tu_contraseña_segura');
```

## Uso

### Frontend
```
http://tudominio.com/
```

### Panel Admin
```
http://tudominio.com/admin/login.php
```

## Optimizaciones incluidas

### 1. Caché APCu
- Contenido de secciones cacheado por 1 hora
- Reducción drástica de consultas a BD
- Fallback automático si APCu no está disponible

### 2. Optimización de imágenes
- Conversión automática a WebP
- Redimensionamiento a 800px máximo
- Compresión con calidad 80%
- Lazy loading nativo del navegador

### 3. Caché del navegador
- Imágenes: 1 mes
- CSS/JS: 1 semana
- HTML: 5 minutos

### 4. Compresión GZIP
- HTML, CSS, JS comprimidos
- Reducción de 60-80% en tamaño

### 5. CSS crítico inline
- Estilos esenciales en el `<head>`
- Renderizado sin bloqueos
- Sin archivos CSS externos

## Consumo de recursos

| Recurso | Uso aproximado |
|---------|----------------|
| CPU | < 1% |
| RAM | < 10MB |
| MySQL | < 5 conexiones |
| Ancho de banda | ~50KB por página |

## Estructura de archivos

```
/
├── index.php              # Frontend principal
├── install.php            # Script de instalación
├── config.php             # Configuración
├── .htaccess              # Optimizaciones Apache
├── includes/
│   ├── db.php            # Conexión PDO
│   ├── cache.php         # Sistema APCu
│   └── functions.php     # Funciones helper
├── admin/
│   ├── index.php         # Panel admin
│   ├── login.php         # Login
│   ├── logout.php        # Logout
│   └── upload.php        # Upload imágenes
├── uploads/              # Imágenes subidas
└── assets/               # CSS/JS (futuro)
```

## Migración desde Laravel

El script `install.php` migra automáticamente:
- Hero section
- Testimonios
- Ranking musical
- Noticias

Los datos se leen desde la base de datos SQLite de Laravel.

## Solución de problemas

### APCu no disponible
El sistema funciona sin APCu, pero será más lento. Para activarlo:
- cPanel: PHP Extensions → habilitar APCu
- Contacta a tu hosting

### Error de conexión MySQL
Verifica las credenciales en `config.php` y que la base de datos exista.

### Imágenes no se optimizan
Asegúrate que GD Library esté instalada:
```php
php -m | grep gd
```

## Seguridad

✅ Contraseñas hasheadas con bcrypt
✅ Protección CSRF en formularios
✅ Sanitización de todas las entradas
✅ PDO con prepared statements
✅ Headers de seguridad HTTP
✅ Archivos sensibles protegidos

## Soporte

Para problemas o consultas, contacta al administrador del sistema.

---

**Desarrollado para Radio Miraflores Televisión**
Optimizado para hosting compartido de bajo costo
