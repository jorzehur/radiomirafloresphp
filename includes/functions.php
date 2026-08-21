<?php
/**
 * Funciones helper - Radio Miraflores PHP
 */

require_once __DIR__ . '/../config.php';

function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return trim($input);
}

function escape($output) {
    if (is_array($output)) {
        return array_map('escape', $output);
    }
    return htmlspecialchars((string)$output, ENT_QUOTES, 'UTF-8');
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . escape($token) . '">';
}

function validateCSRFToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

function requireCSRF() {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        securityLog('CSRF validation failed');
        http_response_code(403);
        die('Token de seguridad inválido. Recarga la página e intenta de nuevo.');
    }
}

function isLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        return false;
    }
    
    if (isset($_SESSION['admin_expires']) && $_SESSION['admin_expires'] < time()) {
        session_destroy();
        return false;
    }
    
    return true;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/admin/login.php');
        exit;
    }
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['admin_expires'] = time() + 3600;
}

function formatDate($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

function timeAgo($datetime) {
    $now = time();
    $time = strtotime($datetime);
    $diff = $now - $time;
    
    if ($diff < 60) return 'Hace un momento';
    if ($diff < 3600) return 'Hace ' . floor($diff / 60) . ' min';
    if ($diff < 86400) return 'Hace ' . floor($diff / 3600) . ' horas';
    if ($diff < 604800) return 'Hace ' . floor($diff / 86400) . ' días';
    
    return formatDate($datetime, 'd/m/Y');
}

function getAllSections() {
    return cache_remember("all_sections", function() {
        $sections = db()->fetchAll("SELECT id, clave, contenido FROM secciones");
        $result = [];
        foreach ($sections as $sec) {
            $data = json_decode($sec['contenido'], true);
            if (!is_array($data)) $data = [];
            $data['id'] = $sec['id'];
            $result[$sec['clave']] = $data;
        }
        return $result;
    });
}

function getAllSectionItems() {
    return cache_remember("all_items", function() {
        $items = db()->fetchAll(
            "SELECT seccion_id, id, tipo, titulo, contenido, imagen_url, orden, activo, created_at 
             FROM items 
             WHERE activo = 1 
             ORDER BY seccion_id, orden ASC"
        );
        $result = [];
        foreach ($items as $item) {
            $result[$item['seccion_id']][] = $item;
        }
        return $result;
    });
}

function getSection($clave) {
    $sections = getAllSections();
    return $sections[$clave] ?? null;
}

function getSectionItems($seccion_id) {
    $allItems = getAllSectionItems();
    return $allItems[$seccion_id] ?? [];
}

function clearSectionCache($clave = null) {
    if ($clave) {
        cache_delete("section_{$clave}");
        cache_delete("items_{$clave}");
    }
    cache_delete("all_sections");
    cache_delete("all_items");
    cache_clear();
}

function isValidImageUrl($url) {
    if (empty($url)) return false;
    $parsed = parse_url($url);
    if (!$parsed || !isset($parsed['scheme'])) return false;
    if (!in_array(strtolower($parsed['scheme']), ['http', 'https'])) return false;
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function isAllowedEmbedOrigin($url) {
    if (empty($url)) return false;
    
    $allowedOrigins = [
        'youtube.com',
        'www.youtube.com',
        'youtube-nocookie.com',
        'www.youtube-nocookie.com',
        'facebook.com',
        'www.facebook.com',
        'instagram.com',
        'www.instagram.com',
        'twitter.com',
        'x.com',
        'platform.twitter.com',
        'open.spotify.com',
        'tiktok.com',
        'www.tiktok.com',
    ];
    
    $parsed = parse_url(trim($url));
    if (!$parsed || !isset($parsed['host'])) return false;
    
    $host = strtolower($parsed['host']);
    foreach ($allowedOrigins as $origin) {
        if ($host === $origin || str_ends_with($host, '.' . $origin)) {
            return true;
        }
    }
    
    return false;
}

function sanitizeEmbedUrl($url) {
    if (empty($url)) return '';
    $url = trim($url);
    
    if (str_starts_with($url, '<')) {
        return '';
    }
    
    if (!isAllowedEmbedOrigin($url)) {
        return '';
    }
    
    return $url;
}

function getMagicBytesForType($type) {
    $map = [
        'image/jpeg' => ["\xFF\xD8\xFF"],
        'image/png' => ["\x89\x50\x4E\x47\x0D\x0A\x1A\x0A"],
        'image/gif' => ["GIF87a", "GIF89a"],
        'image/webp' => ["RIFF"],
    ];
    return $map[$type] ?? [];
}

function verifyImageMagicBytes($filepath, $expectedType) {
    $magicBytes = getMagicBytesForType($expectedType);
    if (empty($magicBytes)) return false;
    
    $handle = @fopen($filepath, 'rb');
    if (!$handle) return false;
    
    $header = fread($handle, 16);
    fclose($handle);
    
    if ($header === false) return false;
    
    foreach ($magicBytes as $magic) {
        if (str_starts_with($header, $magic)) {
            if ($expectedType === 'image/webp') {
                return strlen($header) >= 12 && substr($header, 8, 4) === 'WEBP';
            }
            return true;
        }
    }
    
    return false;
}

function processUpload($file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Error en el upload'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['error' => 'Archivo demasiado grande (máx ' . round(MAX_FILE_SIZE / 1024) . 'KB)'];
    }
    
    if ($file['size'] === 0) {
        return ['error' => 'Archivo vacío'];
    }
    
    if (!in_array($file['type'], ALLOWED_TYPES)) {
        return ['error' => 'Tipo de archivo no permitido'];
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        return ['error' => 'Extensión de archivo no permitida'];
    }
    
    $tmpPath = $file['tmp_name'];
    if (!verifyImageMagicBytes($tmpPath, $file['type'])) {
        return ['error' => 'El archivo no es una imagen válida'];
    }
    
    $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $filepath = UPLOAD_DIR . $filename;
    
    if (!is_dir(UPLOAD_DIR)) {
        @mkdir(UPLOAD_DIR, 0755, true);
    }
    
    if (!move_uploaded_file($tmpPath, $filepath)) {
        return ['error' => 'No se pudo guardar el archivo'];
    }
    
    if (ENABLE_WEBP && extension_loaded('gd')) {
        optimizeImage($filepath);
    }
    
    return [
        'success' => true,
        'url' => UPLOAD_URL . $filename,
        'filename' => $filename
    ];
}

function optimizeImage($filepath) {
    if (!extension_loaded('gd')) {
        return false;
    }
    
    $info = @getimagesize($filepath);
    if (!$info) return false;
    
    list($width, $height, $type) = $info;
    
    if ($width > IMAGE_MAX_WIDTH) {
        $ratio = $width / $height;
        $newWidth = IMAGE_MAX_WIDTH;
        $newHeight = intval($newWidth / $ratio);
        
        $src = @imagecreatefromstring(file_get_contents($filepath));
        if (!$src) return false;
        
        $dst = imagecreatetruecolor($newWidth, $newHeight);
        
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }
        
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        $webpPath = preg_replace('/\.[^.]+$/', '.webp', $filepath);
        imagewebp($dst, $webpPath, IMAGE_QUALITY);
        
        imagedestroy($src);
        imagedestroy($dst);
        
        if (file_exists($webpPath) && filesize($webpPath) > 0) {
            unlink($filepath);
            return str_replace(UPLOAD_DIR, UPLOAD_URL, $webpPath);
        }
    }
    
    return false;
}

function getImageUrl($filename) {
    $webpFile = preg_replace('/\.[^.]+$/', '.webp', $filename);
    $webpPath = UPLOAD_DIR . basename($webpFile);
    
    if (file_exists($webpPath)) {
        return UPLOAD_URL . basename($webpFile);
    }
    
    return UPLOAD_URL . basename($filename);
}

function renderImage($src, $alt = '', $class = '') {
    $webpSrc = preg_replace('/\.[^.]+$/', '.webp', $src);
    $originalSrc = $src;
    
    return '<picture>' .
           '<source srcset="' . htmlspecialchars($webpSrc, ENT_QUOTES, 'UTF-8') . '" type="image/webp">' .
           '<img src="' . htmlspecialchars($originalSrc, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($alt, ENT_QUOTES, 'UTF-8') . '" class="' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '" loading="lazy">' .
           '</picture>';
}
