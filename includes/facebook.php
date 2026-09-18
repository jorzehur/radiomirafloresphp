<?php
// ============================================================
//  facebook.php - Leer las publicaciones de la pagina con la API
//  Necesita includes/facebook-secreto.php con FB_PAGE_ID y FB_TOKEN.
//  Si no esta configurado, todas las funciones devuelven vacio.
// ============================================================

function facebook_configurado(): bool {
    return defined('FB_PAGE_ID') && defined('FB_TOKEN')
        && trim((string) FB_PAGE_ID) !== '' && trim((string) FB_TOKEN) !== '';
}

function facebook_error(): string {
    return (string) ($GLOBALS['facebook_error'] ?? '');
}

function facebook_api(string $consulta): array {
    if (!facebook_configurado() || !function_exists('curl_init')) {
        return array();
    }

    $url = 'https://graph.facebook.com/v21.0/' . ltrim($consulta, '/')
         . (strpos($consulta, '?') === false ? '?' : '&')
         . 'access_token=' . urlencode(trim((string) FB_TOKEN));

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $respuesta = curl_exec($ch);
    $code      = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $errorCurl = curl_error($ch);
    curl_close($ch);

    $datos = json_decode((string) $respuesta, true);

    if ($code !== 200 || !is_array($datos) || isset($datos['error'])) {
        $GLOBALS['facebook_error'] = $datos['error']['message'] ?? ($errorCurl !== '' ? $errorCurl : 'HTTP ' . $code);
        return array();
    }

    return $datos;
}

/**
 * Ultimas publicaciones de la pagina, con el TEXTO COMPLETO.
 */
function facebook_publicaciones(int $limite = 5): array {
    if (!facebook_configurado()) {
        return array();
    }

    $limite = max(1, min(100, $limite));
    $datos  = facebook_api(trim((string) FB_PAGE_ID)
        . '/posts?fields=id,message,created_time,permalink_url,full_picture&limit=' . $limite);

    $salida = array();
    foreach (($datos['data'] ?? array()) as $p) {
        $texto = trim(preg_replace('/\s+/', ' ', (string) ($p['message'] ?? '')));
        $salida[] = array(
            'id'     => (string) ($p['id'] ?? ''),
            'texto'  => $texto,
            'url'    => (string) ($p['permalink_url'] ?? ''),
            'imagen' => (string) ($p['full_picture'] ?? ''),
            'fecha'  => substr((string) ($p['created_time'] ?? ''), 0, 10),
            'titulo' => facebook_titulo_desde_texto($texto),
        );
    }

    return $salida;
}

/**
 * Saca un titular razonable del propio texto del post (sin inventar nada).
 */
function facebook_titulo_desde_texto(string $texto): string {
    $t = trim($texto);
    if ($t === '') {
        return "Publicaci\u{00F3}n de Facebook";
    }
    if (mb_strlen($t) <= 110) {
        return $t;
    }
    $corte   = mb_substr($t, 0, 110);
    $espacio = mb_strrpos($corte, ' ');
    if ($espacio !== false && $espacio > 60) {
        $corte = mb_substr($corte, 0, $espacio);
    }
    return rtrim($corte, " ,;:.-") . '...';
}