<?php
// ============================================================
//  config.php - Configuracion general del sitio
//  Edita estos datos si tu MySQL usa otra cuenta o puerto.
// ============================================================

// --- Conexion a MySQL (XAMPP por defecto) ---
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'radio_miraflores');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
// En produccion define DB_HOST, DB_NAME, DB_USER y DB_PASS como variables de
// entorno, o cambia aqui los valores por defecto.

// --- Rutas base (dejar como estan) ---
define('BASE_URL', '');                 // '' si el sitio esta en la raiz
define('DIR_UPLOADS', __DIR__ . '/../uploads');   // carpeta fisica de subidas
define('URL_UPLOADS', 'uploads');                 // ruta web de subidas

// --- Sesion ---
// Las paginas publicas NO inician sesion: solo la necesita el panel. Asi cada
// visitante anonimo no genera un archivo de sesion ni recibe cookie.
function iniciar_sesion(): void {
    if (session_status() === PHP_SESSION_NONE) {
        // Cookies seguras: HttpOnly (evita acceso desde JS) y SameSite (evita CSRF).
        // "secure" se activa solo cuando el sitio corre por HTTPS (produccion).
        $cookieSegura = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $cookieSegura,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_name('RADIOMIRAF');
        session_start();
    }
}

// --- Zona horaria ---
date_default_timezone_set('America/Lima');