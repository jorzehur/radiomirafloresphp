<?php
// ============================================================
//  encabezado.php - Cabecera y menú lateral del panel admin
// ============================================================
if (!defined('ADMIN_PANEL')) {
    // Solo se usa desde el admin
    require_once __DIR__ . '/../../includes/funciones.php';
}

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/admin-funciones.php';

$usuarioAdmin = usuario_actual();
$paginaActiva = $paginaActiva ?? '';
$menuItems = [
    'dashboard'    => ['index.php',        'Dashboard'],
    'noticias'     => ['noticias.php',     'Noticias'],
    'videos'       => ['videos.php',       'Videos'],
    'testimonios'  => ['testimonios.php',  'Testimonios'],
    'categorias'   => ['categorias.php',   'Categorías'],
    'ajustes'      => ['ajustes.php',      'Ajustes'],
    'password'     => ['cambiar-password.php', 'Mi cuenta'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tituloAdmin ?? 'Panel') ?> - Administración</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
<div class="layout">
    <aside class="lateral">
        <div class="lateral__marca"><?= e(config('nombre_sitio', 'Radio Miraflores')) ?></div>
        <nav>
            <?php foreach ($menuItems as $clave => $item): ?>
                <a href="<?= e($item[0]) ?>" class="<?= $paginaActiva === $clave ? 'activo' : '' ?>"><?= e($item[1]) ?></a>
            <?php endforeach; ?>
            <a class="salir" href="logout.php">Cerrar sesión</a>
        </nav>
    </aside>
    <main class="contenido">
