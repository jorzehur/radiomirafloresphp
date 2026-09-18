<?php
// ============================================================
//  noticias.php - Indice de noticias con paginacion y filtros
// ============================================================
require_once __DIR__ . '/includes/funciones.php';

$porPagina = 9;
$pagina    = max(1, (int) ($_GET['pagina'] ?? 1));
$catSlug   = trim((string) ($_GET['categoria'] ?? ''));

$categoria = null;
if ($catSlug !== '') {
    $stmt = db()->prepare('SELECT id, nombre, slug FROM categorias WHERE slug = ? LIMIT 1');
    $stmt->execute([$catSlug]);
    $categoria = $stmt->fetch() ?: null;
}

if ($categoria) {
    $stmt = db()->prepare('SELECT COUNT(*) FROM noticias WHERE categoria_id = ?');
    $stmt->execute([(int) $categoria['id']]);
} else {
    $stmt = db()->query('SELECT COUNT(*) FROM noticias');
}
$total = (int) $stmt->fetchColumn();

$paginas = max(1, (int) ceil($total / $porPagina));
if ($pagina > $paginas) {
    $pagina = $paginas;
}
$offset = ($pagina - 1) * $porPagina;

$sql = 'SELECT n.id, n.titulo, n.slug, n.resumen, n.imagen, n.url_facebook, n.fecha_publicacion, c.nombre AS categoria
        FROM noticias n
        LEFT JOIN categorias c ON c.id = n.categoria_id';
if ($categoria) {
    $sql .= ' WHERE n.categoria_id = ?';
}
$sql .= ' ORDER BY n.destacada DESC, n.fecha_publicacion DESC, n.id DESC LIMIT ' . (int) $porPagina . ' OFFSET ' . (int) $offset;

$stmt = db()->prepare($sql);
$stmt->execute($categoria ? [(int) $categoria['id']] : []);
$noticias = $stmt->fetchAll();

$totalTodas = (int) db()->query('SELECT COUNT(*) FROM noticias')->fetchColumn();
$categorias = db()->query('SELECT c.id, c.nombre, c.slug, (SELECT COUNT(*) FROM noticias n WHERE n.categoria_id = c.id) AS total
                           FROM categorias c ORDER BY c.nombre')->fetchAll();

// Si alguna noticia es un post de Facebook, hay que cargar el SDK
foreach ($noticias as $fila) {
    if (!empty($fila['url_facebook'])) {
        $GLOBALS['cargar_fb_sdk'] = true;
        break;
    }
}

function enlace_pagina(int $p, string $catSlug): string {
    $q = ['pagina' => $p];
    if ($catSlug !== '') {
        $q['categoria'] = $catSlug;
    }
    return 'noticias.php?' . http_build_query($q);
}

$titulo_pagina      = 'Noticias';
$descripcion_pagina = 'Todas las noticias y publicaciones de ' . config('nombre_sitio', 'Radio Miraflores') . '.';
$url_canonica_ruta  = $categoria
    ? 'noticias.php?categoria=' . rawurlencode((string) $categoria['slug'])
    : 'noticias.php';
if ($pagina > 1) {
    $titulo_pagina .= ' - pagina ' . $pagina;
}

require __DIR__ . '/includes/header.php';
?>

<main class="pagina-detalle">
    <div class="contenedor">
        <h1 class="seccion__titulo">Noticias</h1>

        <p class="listado__info">
            <?php if ($categoria): ?>
                Mostrando: <?= e($categoria['nombre']) ?> (<?= (int) $total ?>)
            <?php else: ?>
                Todas las noticias (<?= (int) $total ?>)
            <?php endif; ?>
        </p>

        <nav class="filtros" aria-label="Paginacion">
            <a class="filtro<?= $categoria ? '' : ' filtro--activo' ?>" href="noticias.php">Todas (<?= (int) $totalTodas ?>)</a>
            <?php foreach ($categorias as $cat): ?>
                <a class="filtro<?= ($categoria && (int) $categoria['id'] === (int) $cat['id']) ? ' filtro--activo' : '' ?>"
                   href="noticias.php?categoria=<?= e($cat['slug']) ?>"><?= e($cat['nombre']) ?> (<?= (int) $cat['total'] ?>)</a>
            <?php endforeach; ?>
        </nav>

        <?php if ($noticias): ?>
            <div class="noticias">
                <?php foreach ($noticias as $noticia): ?>
                    <?php if (!empty($noticia['url_facebook'])): ?>
                        <div class="noticia noticia--facebook">
                            <div class="noticia__fb">
                                <div class="fb-post" data-href="<?= e($noticia['url_facebook']) ?>" data-show-text="true" data-width="350"></div>
                                <div class="noticia__fb-vacio">
                                    <span>Esta publicación se lee en Facebook<br>
                                    <a href="<?= e($noticia['url_facebook']) ?>" target="_blank" rel="noopener">Abrir en Facebook &nearr;</a></span>
                                </div>
                            </div>
                            <div class="noticia__cuerpo">
                                <?php if (!empty($noticia['categoria'])): ?>
                                    <span class="etiqueta"><?= e($noticia['categoria']) ?></span>
                                <?php endif; ?>
                                <h3 class="noticia__titulo"><a href="noticia.php?slug=<?= e($noticia['slug']) ?>"><?= e($noticia['titulo']) ?></a></h3>
                                <?php if (!empty($noticia['resumen'])): ?>
                                    <p class="noticia__resumen"><?= e($noticia['resumen']) ?></p>
                                <?php endif; ?>
                                <div class="noticia__acciones">
                                    <?php if (trim((string) $noticia['contenido']) !== '' || !empty($noticia['imagen'])): ?>
                                        <a class="boton-fb" href="noticia.php?slug=<?= e($noticia['slug']) ?>">Leer la noticia completa aqu&iacute;</a>
                                    <?php endif; ?>
                                    <a class="boton-fb boton-fb--secundario" href="<?= e($noticia['url_facebook']) ?>" target="_blank" rel="noopener">Ver en Facebook &nearr;</a>
                                </div>
                                <time class="noticia__fecha"><?= fecha_larga($noticia['fecha_publicacion']) ?></time>
                            </div>
                        </div>
                    <?php else: ?>
                        <a class="noticia" href="noticia.php?slug=<?= e($noticia['slug']) ?>">
                            <?php if (!empty($noticia['imagen'])): ?>
                                <div class="noticia__imagen">
                                    <img src="<?= e(URL_UPLOADS . '/' . $noticia['imagen']) ?>" alt="<?= e($noticia['titulo']) ?>" loading="lazy">
                                </div>
                            <?php endif; ?>
                            <div class="noticia__cuerpo">
                                <?php if ($noticia['categoria']): ?>
                                    <span class="etiqueta"><?= e($noticia['categoria']) ?></span>
                                <?php endif; ?>
                                <h3 class="noticia__titulo"><?= e($noticia['titulo']) ?></h3>
                                <p class="noticia__resumen"><?= e($noticia['resumen']) ?></p>
                                <time class="noticia__fecha"><?= fecha_larga($noticia['fecha_publicacion']) ?></time>
                            </div>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <?php if ($paginas > 1): ?>
                <nav class="paginacion" aria-label="Paginacion">
                    <?php if ($pagina > 1): ?>
                        <a class="paginacion__enlace" rel="prev" href="<?= e(enlace_pagina($pagina - 1, $catSlug)) ?>">&larr; Anterior</a>
                    <?php endif; ?>
                    <span class="paginacion__actual"><?= (int) $pagina ?> / <?= (int) $paginas ?></span>
                    <?php if ($pagina < $paginas): ?>
                        <a class="paginacion__enlace" rel="next" href="<?= e(enlace_pagina($pagina + 1, $catSlug)) ?>">Siguiente &rarr;</a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <p class="seccion__vacio"><?= "No hay noticias publicadas en esta secci\u{00F3}n." ?></p>
        <?php endif; ?>

        <p class="paginacion"><a class="paginacion__enlace" href="index.php">&larr; Volver a la portada</a></p>
    </div>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>