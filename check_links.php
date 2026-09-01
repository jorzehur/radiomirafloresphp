<?php
// ============================================================
//  check_links.php - Verificador de enlaces rotos
//  Uso: php check_links.php [--fix]
// ============================================================

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$fix_mode = in_array('--fix', $argv);

echo "🔍 Escaneando enlaces rotos...\n\n";

$enlaces = [];

// 1. Videos
$videos = db()->query('SELECT id, titulo, url_video, plataforma FROM videos')->fetchAll();
foreach ($videos as $v) {
    $enlaces[] = [
        'tipo' => 'video',
        'id' => $v['id'],
        'titulo' => $v['titulo'],
        'url' => $v['url_video'],
        'plataforma' => $v['plataforma'],
    ];
}

// 2. Noticias (enlaces Facebook)
$noticias = db()->query('SELECT id, titulo, url_facebook FROM noticias WHERE url_facebook != ""')->fetchAll();
foreach ($noticias as $n) {
    $enlaces[] = [
        'tipo' => 'noticia_facebook',
        'id' => $n['id'],
        'titulo' => $n['titulo'],
        'url' => $n['url_facebook'],
    ];
}

// 3. Configuración (redes sociales, mapa)
$configs = db()->query("SELECT clave, valor FROM configuracion WHERE clave IN ('facebook','instagram','youtube','tiktok','whatsapp','mapa_embed')")->fetchAll();
foreach ($configs as $c) {
    $enlaces[] = [
        'tipo' => 'config',
        'clave' => $c['clave'],
        'url' => $c['valor'],
    ];
}

$rotos = 0;
$total = count($enlaces);

function verificar_url(string $url): array {
    $url = trim($url);
    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
        return ['valido' => false, 'codigo' => 0, 'mensaje' => 'URL malformada'];
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
        CURLOPT_USERAGENT      => 'RadioMiraflores-Bot/1.0 (+https://radiomiraflores.com)',
    ]);

    $ok = curl_exec($ch);
    $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($ok === false) {
        return ['valido' => false, 'codigo' => 0, 'mensaje' => "Error cURL: $error"];
    }

    $valido = ($codigo >= 200 && $codigo < 400);
    return ['valido' => $valido, 'codigo' => $codigo, 'mensaje' => $valido ? 'OK' : "HTTP $codigo"];
}

// Escanear cada enlace
echo "Verificando $total enlaces...\n";
foreach ($enlaces as $i => $e) {
    $progress = sprintf("[%d/%d]", $i + 1, $total);
    $tipo = $e['tipo'] ?? 'config';

    if ($tipo === 'config') {
        echo "$progress Config: {$e['clave']} - ";
    } else {
        echo "$progress {$e['tipo']} #{$e['id']}: ";
    }

    $resultado = verificar_url($e['url']);

    if ($resultado['valido']) {
        echo "✅ {$resultado['mensaje']}\n";
    } else {
        echo "❌ {$resultado['mensaje']}\n";
        echo "   URL: {$e['url']}\n";
        $rotos++;
    }

    // Pausa para no sobrecargar servidores
    usleep(200000); // 200ms
}

echo "\n";
echo "========================================\n";
echo "Resumen: $total verificados, $rotos rotos\n";
echo "========================================\n";

if ($rotos > 0 && $fix_mode) {
    echo "\n🔧 Modo reparación activado...\n";
    // Aquí se podría agregar lógica para desactivar entradas con URLs rotas
    // Por seguridad, solo mostramos qué hacer
    echo "Para reparar manualmente:\n";
    echo "1. Edita el registro en el panel admin\n";
    echo "2. Actualiza o elimina la URL rota\n";
} elseif ($rotos > 0) {
    echo "\n💡 Ejecuta: php check_links.php --fix para ver opciones de reparación\n";
}