<?php
// ============================================================
//  index.php - Dashboard del panel de administración
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
requerir_login();

$paginaActiva = 'dashboard';
$tituloAdmin = 'Dashboard';

$contadores = [
    'Noticias'    => (int) db()->query('SELECT COUNT(*) FROM noticias')->fetchColumn(),
    'Videos'      => (int) db()->query('SELECT COUNT(*) FROM videos')->fetchColumn(),
    'Testimonios' => (int) db()->query('SELECT COUNT(*) FROM testimonios')->fetchColumn(),
    'Categorías'  => (int) db()->query('SELECT COUNT(*) FROM categorias')->fetchColumn(),
];

require __DIR__ . '/includes/encabezado.php';
?>

<div class="titulo-pagina">
    <h1>Bienvenido, <?= e($usuarioAdmin['nombre'] ?? $usuarioAdmin['usuario']) ?></h1>
</div>

<div class="tarjetas">
    <?php foreach ($contadores as $etiqueta => $numero): ?>
        <div class="tarjeta">
            <div class="tarjeta__numero"><?= $numero ?></div>
            <div class="tarjeta__etiqueta"><?= e($etiqueta) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="formulario">
    <h2 style="margin-top:0;">Acciones rápidas</h2>
    <p style="color:#999;">Usa el menú de la izquierda para gestionar el contenido del sitio.</p>
    <p>
        <a class="boton" href="noticias.php?nueva=1">+ Nueva noticia</a>
        <a class="boton boton--secundario" href="videos.php?nueva=1">+ Nuevo video</a>
    </p>
</div>

<?php require __DIR__ . '/includes/pie.php'; ?>
