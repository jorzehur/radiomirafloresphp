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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($nombre_sitio) ?><?= $eslogan ? ' - ' . e($eslogan) : '' ?></title>
    <meta name="description" content="<?= e($eslogan) ?>">

    <!-- Headers de seguridad -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="SAMEORIGIN">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' 'unsafe-inline' https://connect.facebook.net; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; frame-src 'self' https://www.facebook.com https://www.youtube.com; connect-src 'self';">

    <link rel="stylesheet" href="assets/css/estilos.css?v=<?= @filemtime(__DIR__ . '/../assets/css/estilos.css') ?>">
    <style>
        :root {
            --color-hero: <?= e($color_hero) ?>;
            --color-acento: <?= e($color_acento) ?>;
        }
    </style>
</head>
<body>
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
