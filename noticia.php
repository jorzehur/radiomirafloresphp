<?php
// ============================================================
//  noticia.php - Detalle de una noticia
// ============================================================
require_once __DIR__ . '/includes/funciones.php';

$slug = (string) ($_GET['slug'] ?? '');

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
    $titulo_pagina  = 'Noticia no encontrada';
    $robots_noindex = true;
} else {
    $titulo_pagina      = $noticia['titulo'];
    $descripcion_pagina = trim((string) ($noticia['resumen'] ?? ''));
    if ($descripcion_pagina === '') {
        $descripcion_pagina = mb_substr(trim(strip_tags((string) $noticia['contenido'])), 0, 160);
    }
    $og_tipo           = 'article';
    $url_canonica_ruta = 'noticia.php?slug=' . rawurlencode((string) $noticia['slug']);
    if (!empty($noticia['imagen'])) {
        $og_imagen_archivo = $noticia['imagen'];
    }
    if (!empty($noticia['url_facebook'])) {
        $GLOBALS['cargar_fb_sdk'] = true;
    }
}

require __DIR__ . '/includes/header.php';
?>

<main class="pagina-detalle">
    <div class="contenedor contenedor--estrecho">
        <?php if (!$noticia): ?>
            <h1>Noticia no encontrada</h1>
            <p>La noticia que buscas no existe o fue eliminada.</p>
            <p><a href="noticias.php">&larr; Ver todas las noticias</a></p>
        <?php else: ?>
            <article class="articulo">
                <a class="articulo__volver" href="noticias.php">&larr; Volver a las noticias</a>
                <h1 class="articulo__titulo"><?= e($noticia['titulo']) ?></h1>

                <?php if ($noticia['categoria']): ?>
                    <span class="etiqueta"><?= e($noticia['categoria']) ?></span>
                <?php endif; ?>
                <time class="articulo__fecha"><?= fecha_larga($noticia['fecha_publicacion']) ?></time>

                <?php if (!empty($noticia['imagen'])): ?>
                    <img class="articulo__imagen" src="<?= e(URL_UPLOADS . '/' . $noticia['imagen']) ?>" alt="<?= e($noticia['titulo']) ?>">
                <?php endif; ?>

                <?php if (!empty($noticia['resumen'])): ?>
                    <p class="articulo__resumen"><?= e($noticia['resumen']) ?></p>
                <?php endif; ?>

                <?php if (trim((string) $noticia['contenido']) !== ''): ?>
                    <div class="contenido">
                        <?= nl2br(e($noticia['contenido'])) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($noticia['url_facebook'])): ?>
                    <p class="articulo__boton">
                        <a class="boton-fb" href="<?= e($noticia['url_facebook']) ?>" target="_blank" rel="noopener">Leer la noticia completa en Facebook &nearr;</a>
                    </p>

                    <div class="articulo__facebook">
                        <p class="articulo__facebook-titulo">Publicación original en Facebook</p>
                        <div class="fb-post" data-href="<?= e($noticia['url_facebook']) ?>" data-show-text="true" data-width="500"></div>
                    </div>
                <?php endif; ?>
            </article>
        <?php endif; ?>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>