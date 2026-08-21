<?php
/**
 * Configuración principal - Radio Miraflores PHP
 * Optimizado para hosting compartido
 */

// Cargar variables de entorno desde .env (fuera del webroot)
$envFile = dirname(__DIR__) . '/.env.radio-miraflores';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
        }
    }
}

// Configuración de base de datos
define('DB_HOST', $_ENV['DB_HOST'] ?? 'sqlite');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'radio_miraflores');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');

// Configuración del sitio
define('SITE_NAME', $_ENV['SITE_NAME'] ?? 'Radio Miraflores Televisión');
define('SITE_URL', $_ENV['SITE_URL'] ?? 'http://localhost/radio-miraflores-php');
define('ADMIN_EMAIL', $_ENV['ADMIN_EMAIL'] ?? 'admin@radiomiraflores.com');
define('ADMIN_PASSWORD', $_ENV['ADMIN_PASSWORD'] ?? 'ChangeThisPassword123!');

// Configuración de caché
define('CACHE_ENABLED', function_exists('apcu_store'));
define('CACHE_TTL', intval($_ENV['CACHE_TTL'] ?? 3600));

// Configuración de uploads
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');
define('MAX_FILE_SIZE', intval($_ENV['MAX_FILE_SIZE'] ?? 524288));
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Configuración de optimización
define('ENABLE_WEBP', true);
define('IMAGE_MAX_WIDTH', intval($_ENV['IMAGE_MAX_WIDTH'] ?? 800));
define('IMAGE_QUALITY', intval($_ENV['IMAGE_QUALITY'] ?? 80));

// Zona horaria
date_default_timezone_set('America/Lima');

// Reporting de errores (desactivar en producción)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Configuración de sesión segura
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0);
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', 3600);
}

// Rate limiting para login (basado en archivo)
define('RATE_LIMIT_FILE', __DIR__ . '/.rate_limit_login');
define('RATE_LIMIT_MAX_ATTEMPTS', 5);
define('RATE_LIMIT_WINDOW', 900);

// Log de seguridad
define('SECURITY_LOG', __DIR__ . '/.security.log');

function securityLog($message) {
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $line = "[$timestamp] [$ip] $message" . PHP_EOL;
    @file_put_contents(SECURITY_LOG, $line, FILE_APPEND | LOCK_EX);
}

function checkRateLimit() {
    if (!file_exists(RATE_LIMIT_FILE)) return true;
    
    $data = @json_decode(file_get_contents(RATE_LIMIT_FILE), true);
    if (!$data) return true;
    
    $now = time();
    $window = RATE_LIMIT_WINDOW;
    $maxAttempts = RATE_LIMIT_MAX_ATTEMPTS;
    
    $recentAttempts = array_filter($data['attempts'] ?? [], function($t) use ($now, $window) {
        return ($now - $t) < $window;
    });
    
    return count($recentAttempts) < $maxAttempts;
}

function recordFailedAttempt() {
    $data = ['attempts' => []];
    if (file_exists(RATE_LIMIT_FILE)) {
        $data = @json_decode(file_get_contents(RATE_LIMIT_FILE), true) ?: $data;
    }
    
    $data['attempts'][] = time();
    $data['attempts'] = array_filter($data['attempts'], function($t) {
        return (time() - $t) < (RATE_LIMIT_WINDOW * 2);
    });
    
    @file_put_contents(RATE_LIMIT_FILE, json_encode($data), LOCK_EX);
}

function clearRateLimit() {
    if (file_exists(RATE_LIMIT_FILE)) {
        @unlink(RATE_LIMIT_FILE);
    }
}
