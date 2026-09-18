<?php
// ============================================================
//  noticia.php - Detalle de una noticia
// ============================================================
require_once __DIR__ . '/includes/funciones.php';

$slug = $_GET['slug'] ?? '';

$stmt = db()->prepare(
    'SELECT n.*, c.nombre AS categoria
     FROM noticias n
     LEFT JOIN categorias c ON c.id = n.categoria_id
     WHERE n.slug = ? LIMIT 1'
);
$stmt->execute([$slug]);
$noticia = $stmt->fetch();

if (!$noticia) {
    http_response_code(404);
}

require __DIR__ . '/includes/header.php';
?>

<main class="pagina-detalle">
    <div class="contenedor contenedor--estrecho">
        <?php if (!$noticia): ?>
            <h1>Noticia no encontrada</h1>
            <p>La noticia que buscas no existe o fue eliminada.</p>
            <p><a href="index.php#noticias">&larr; Volver a noticias</a></p>
        <?php else: ?>
            <article class="articulo">
                <a class="articulo__volver" href="index.php#noticias">&larr; Volver a noticias</a>
                <h1 class="articulo__titulo"><?= e($noticia['titulo']) ?></h1>
                <?php if (!empty($noticia['url_facebook'])): ?>
                    <?php $GLOBALS['cargar_fb_sdk'] = true; ?>
                    <div class="articulo__facebook">
                        <div class="fb-post" data-href="<?= e($noticia['url_facebook']) ?>" data-show-text="true" data-width="500"></div>
                    </div>
                <?php else: ?>
                    <?php if ($noticia['categoria']): ?>
                        <span class="etiqueta"><?= e($noticia['categoria']) ?></span>
                    <?php endif; ?>
                    <time class="articulo__fecha"><?= fecha_larga($noticia['fecha_publicacion']) ?></time>

                    <?php if (!empty($noticia['imagen'])): ?>
                        <img class="articulo__imagen" src="<?= e(URL_UPLOADS . '/' . $noticia['imagen']) ?>" alt="<?= e($noticia['titulo']) ?>">
                    <?php endif; ?>

                    <?php if ($noticia['resumen']): ?>
                        <p class="articulo__resumen"><?= e($noticia['resumen']) ?></p>
                    <?php endif; ?>

                    <div class="contenido">
                        <?= nl2br(e($noticia['contenido'])) ?>
                    </div>
                <?php endif; ?>
            </article>
        <?php endif; ?>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
