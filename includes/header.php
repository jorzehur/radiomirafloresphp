<?php
// ============================================================
//  header.php - Cabecera comun de la parte publica
// ============================================================
require_once __DIR__ . '/funciones.php';

$nombre_sitio = config('nombre_sitio', 'Radio Miraflores');
$eslogan      = config('eslogan', '');
$logo         = config('logo', '');
$color_hero   = config('color_hero', '#d32f2f');
$color_acento = config('color_acento', '#d32f2f');

// Los enlaces del menú apuntan a secciones de la portada. Desde otra página
// (p. ej. el detalle de una noticia) se antepone "index.php" para que funcionen.
$esInicio = basename($_SERVER['SCRIPT_NAME'] ?? '') === 'index.php';
$baseMenu = $esInicio ? '' : 'index.php';

// --- SEO y redes sociales: cualquier pagina puede sobrescribir estas variables ---
$titulo_pagina      = $titulo_pagina ?? '';
$descripcion_pagina = $descripcion_pagina ?? '';
$og_tipo            = $og_tipo ?? 'website';
$og_imagen          = $og_imagen ?? '';
$og_imagen_archivo  = $og_imagen_archivo ?? '';
$robots_noindex     = $robots_noindex ?? false;

$esquema    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$hostActual = $_SERVER['HTTP_HOST'] ?? 'localhost';
$dirBase    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$urlBase    = $esquema . '://' . $hostActual . $dirBase . '/';

$url_canonica      = $url_canonica ?? null;
$url_canonica_ruta = $url_canonica_ruta ?? '';
if ($url_canonica === null) {
    $url_canonica = ($url_canonica_ruta !== '')
        ? $urlBase . $url_canonica_ruta
        : $esquema . '://' . $hostActual . ($_SERVER['REQUEST_URI'] ?? '/');
}
if ($og_imagen === '' && $og_imagen_archivo !== '') {
    $og_imagen = $urlBase . URL_UPLOADS . '/' . $og_imagen_archivo;
}
if ($og_imagen === '') {
    $heroSocial = config('imagen_hero', '');
    if ($heroSocial !== '') { $og_imagen = $urlBase . URL_UPLOADS . '/' . $heroSocial; }
}
$tituloFinal = ($titulo_pagina !== '') ? $titulo_pagina . ' | ' . $nombre_sitio : $nombre_sitio . ($eslogan ? ' | ' . $eslogan : '');
$descFinal   = ($descripcion_pagina !== '') ? $descripcion_pagina : $eslogan;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tituloFinal) ?></title>
    <meta name="description" content="<?= e($descFinal) ?>">
    <link rel="canonical" href="<?= e($url_canonica) ?>">
<?php if ($robots_noindex): ?>
    <meta name="robots" content="noindex, follow">
<?php endif; ?>
    <meta property="og:site_name" content="<?= e($nombre_sitio) ?>">
    <meta property="og:type" content="<?= e($og_tipo) ?>">
    <meta property="og:title" content="<?= e($titulo_pagina !== '' ? $titulo_pagina : $nombre_sitio) ?>">
    <meta property="og:description" content="<?= e($descFinal) ?>">
    <meta property="og:url" content="<?= e($url_canonica) ?>">
<?php if ($og_imagen !== ''): ?>
    <meta property="og:image" content="<?= e($og_imagen) ?>">
<?php endif; ?>
    <meta name="twitter:card" content="<?= $og_imagen !== '' ? 'summary_large_image' : 'summary' ?>">
    <link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="assets/css/estilos.css?v=<?= @filemtime(__DIR__ . '/../assets/css/estilos.css') ?>">
    <style>
        :root {
            --color-hero: <?= e($color_hero) ?>;
            --color-acento: <?= e($color_acento) ?>;
        }
    </style>
<script>document.documentElement.className += ' con-js';</script>
</head>
<body>
<a class="saltar" href="#videos">Saltar al contenido</a>
<div id="fb-root"></div>

<header class="barra-navegacion">
    <div class="contenedor barra-navegacion__fila">
        <a href="index.php" class="logo">
            <?php if ($logo): ?>
                <img src="<?= e(URL_UPLOADS . '/' . $logo) ?>" alt="<?= e($nombre_sitio) ?>">
            <?php else: ?>
                <span class="logo__texto"><?= e($nombre_sitio) ?></span>
            <?php endif; ?>
        </a>

        <nav class="menu" id="menu">
            <a href="<?= e($baseMenu) ?>#inicio">Inicio</a>
            <a href="<?= e($baseMenu) ?>#videos">Videos</a>
            <a href="<?= e($baseMenu) ?>#noticias">Noticias</a>
            <a href="<?= e($baseMenu) ?>#nosotros">Nosotros</a>
            <a href="<?= e($baseMenu) ?>#testimonios">Testimonios</a>
            <a href="<?= e($baseMenu) ?>#redes">Redes</a>
            <a href="<?= e($baseMenu) ?>#contacto">Contáctanos</a>
        </nav>

        <button class="menu-boton" id="menuBoton" aria-label="Abrir menú" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>
