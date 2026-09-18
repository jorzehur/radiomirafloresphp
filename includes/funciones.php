<?php
// ============================================================
//  funciones.php - Funciones auxiliares del sitio
// ============================================================

require_once __DIR__ . '/db.php';

/**
 * Escapa texto para mostrarlo de forma segura en HTML.
 */
function e(?string $texto): string {
    return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}

/**
 * Devuelve un valor de configuracion del sitio (o un valor por defecto).
 */
function config_cache(bool $recargar = false): array {
    static $cache = null;

    if ($cache === null || $recargar) {
        $cache = [];
        try {
            $stmt = db()->query('SELECT clave, valor FROM configuracion');
            foreach ($stmt->fetchAll() as $fila) {
                $cache[$fila['clave']] = $fila['valor'];
            }
        } catch (PDOException $e) {
            // Si la tabla no existe aun, devolver vacio sin romper.
        }
    }

    return $cache;
}

/**
 * Devuelve un valor de configuracion del sitio (o un valor por defecto).
 */
function config(string $clave, string $defecto = ''): string {
    $cache = config_cache();
    return $cache[$clave] ?? $defecto;
}

/**
 * Guarda un valor de configuracion y refresca la cache en memoria.
 */
function config_guardar(string $clave, string $valor): void {
    db()->prepare('INSERT INTO configuracion (clave, valor) VALUES (?, ?)
                   ON DUPLICATE KEY UPDATE valor = VALUES(valor)')
        ->execute([$clave, $valor]);
    config_cache(true);
}

/**
 * Convierte un texto en "slug" (url-amigable).
 * Ej: "Hola Mundo!" -> "hola-mundo"
 */
function slugify(string $texto): string {
    // Reemplazar acentos y caracteres especiales del español para slugs legibles
    $texto = strtr($texto, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u',
        'ñ' => 'n', 'Ñ' => 'n', 'ü' => 'u', 'Ü' => 'u',
    ]);
    $texto = strtolower(trim($texto));
    $texto = preg_replace('/[^a-z0-9\s-]/', '', $texto);
    $texto = preg_replace('/[\s-]+/', '-', $texto);
    $texto = trim($texto, '-');
    // Si el resultado quedó vacío (p. ej. título sin caracteres ASCII), usar uno por defecto
    if ($texto === '') {
        $texto = 'publicacion';
    }
    return $texto;
}

/**
 * Extrae el ID de un video de YouTube desde distintas URL.
 * Acepta: watch?v=, youtu.be/, live/, shorts/, embed/
 */
function youtube_id(string $url): ?string {
    $patrones = [
        '/youtu\.be\/([A-Za-z0-9_-]{11})/',
        '/[?&]v=([A-Za-z0-9_-]{11})/',
        '/embed\/([A-Za-z0-9_-]{11})/',
        '/live\/([A-Za-z0-9_-]{11})/',
        '/shorts\/([A-Za-z0-9_-]{11})/',
    ];
    foreach ($patrones as $p) {
        if (preg_match($p, $url, $m)) {
            return $m[1];
        }
    }
    return null;
}

/**
 * Devuelve la URL de embed de YouTube lista para iframe.
 * El parametro $autoPlay se usa para la portada (en vivo).
 */
function youtube_embed(string $url): string {
    $id = youtube_id($url);
    if ($id === null) {
        return '';
    }
    return 'https://www.youtube-nocookie.com/embed/' . $id;
}

/**
 * Devuelve la URL de embed de Facebook lista para iframe.
 * Acepta tanto la URL del reproductor de Facebook como la URL
 * normal del video/directo (la envuelve en el reproductor).
 */
function facebook_embed(string $url): string {
    $url = trim($url);

    // Si ya es el reproductor embebible de Facebook, usarla tal cual
    if (preg_match('#^https?://(www\.|web\.)?facebook\.com/plugins/video\.php#i', $url)) {
        return $url;
    }

    // Si es una URL normal de Facebook (video o directo), envolverla
    if (preg_match('#^https?://(www\.|web\.)?facebook\.com/#i', $url)) {
        return 'https://www.facebook.com/plugins/video.php?href='
            . rawurlencode($url) . '&show_text=false';
    }

    return '';
}

/**
 * Devuelve la URL de embed de una publicación de Facebook lista para iframe.
 * Acepta la URL normal de la publicación y la envuelve en el "post plugin".
 */
function facebook_post_embed(string $url): string {
    $url = trim($url);

    if (preg_match('#^https?://(www\.|web\.)?facebook\.com/#i', $url)) {
        return 'https://www.facebook.com/plugins/post.php?href='
            . rawurlencode($url) . '&show_text=true&width=350';
    }

    return '';
}

/**
 * Resuelve un enlace de Facebook siguiendo las redirecciones de los enlaces
 * "compartir" (facebook.com/share/...) hasta su URL directa (pfbid/reel/watch).
 * No cambia la URL de los enlaces directos (pfbid, reel, watch) porque esos
 * son los que Facebook permite incrustar.
 * Si no se puede resolver, devuelve la URL original.
 */
function facebook_resolver(string $url): string {
    $url = trim($url);

    // No tocar los enlaces de reproductor (ya son embebibles)
    if (preg_match('#^https?://(www\.|web\.)?facebook\.com/plugins/#i', $url)) {
        return $url;
    }

    // Si no es un enlace "compartir", devolverlo tal cual (pfbid/reel/watch)
    if (!preg_match('#facebook\.com/share/#i', $url)) {
        return $url;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_NOBODY         => true,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
    ]);
    $ok    = curl_exec($ch);
    $final = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);

    if ($ok !== false && $final && preg_match('#facebook\.com/#i', $final)) {
        // Quitar parámetros extra para dejar solo la URL limpia del contenido
        $final = preg_replace('/\?.*$/', '', $final);
        return $final;
    }

    return $url;
}

/**
 * Genera un token CSRF y lo guarda en sesion.
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica que el token CSRF recibido sea valido.
 */
function csrf_verificar(?string $token): bool {
    return !empty($_SESSION['csrf_token'])
        && is_string($token)
        && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Campo oculto con el token CSRF para formularios.
 */
function csrf_campo(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Redirige a una URL relativa y detiene el script.
 */
function redirigir(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Formatea una fecha al estilo espanol: "12 de marzo de 2026".
 */
function fecha_larga(?string $fecha): string {
    if (empty($fecha)) {
        return '';
    }
    $ts = strtotime($fecha);
    if ($ts === false) {
        return e($fecha);
    }
    $meses = ['enero','febrero','marzo','abril','mayo','junio',
              'julio','agosto','septiembre','octubre','noviembre','diciembre'];
    return date('j', $ts) . ' de ' . $meses[(int)date('n', $ts) - 1] . ' de ' . date('Y', $ts);
}

/**
 * Comprueba, preguntando al propio Facebook, que una publicacion se puede
 * incrustar de verdad (que no se haya borrado ni sea privada).
 * Si no hay red o Facebook no responde, devuelve true para no bloquear el guardado.
 */
function facebook_post_disponible(string $url): bool {
    if (!function_exists('curl_init')) {
        return true;
    }

    $plugin = facebook_post_embed($url);
    if ($plugin === '') {
        return false;
    }

    $ch = curl_init($plugin);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_CONNECTTIMEOUT => 6,
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; RadioMiraflores/1.0)',
    ]);
    $html = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($html === false || $code !== 200) {
        return true;   // no se pudo comprobar: no bloqueamos nada
    }

    // Respuestas con las que Facebook avisa de que el post no se puede mostrar
    $avisos = [
        "ya no est\u{00E1} disponible",
        "isn't available",
        "no se puede mostrar",
        "Este contenido no est\u{00E1} disponible",
    ];
    foreach ($avisos as $texto) {
        if (stripos((string) $html, $texto) !== false) {
            return false;
        }
    }

    return true;
}
/**
 * Saca la URL de la publicacion de cualquier cosa que pegue el usuario:
 * un enlace normal, un enlace corto de "Compartir" o el codigo completo
 * que da Facebook en "Insertar" (el iframe del plugin).
 */
function facebook_url_desde_texto(string $texto): string {
    $texto = trim($texto);

    // Codigo de "Insertar": <iframe src="https://www.facebook.com/plugins/post.php?href=...">
    if (preg_match('~plugins/(?:post|video)\.php\?href=([^"&\s]+)~i', $texto, $m)) {
        return urldecode(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
    }

    // Formato XFBML: <div class="fb-post" data-href="...">
    if (preg_match('~data-href="([^"]+)"~i', $texto, $m)) {
        return html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
    }

    // Un enlace suelto (normal o de "Compartir")
    if (preg_match('~https?://[^\s"\'<>]+~i', $texto, $m)) {
        return html_entity_decode($m[0], ENT_QUOTES, 'UTF-8');
    }

    return $texto;
}