<?php
// ============================================================
//  index.php - Pagina principal (una sola pagina con secciones)
// ============================================================
require_once __DIR__ . '/includes/funciones.php';

// --- Consultar datos de las secciones ---
$videos = db()->query('SELECT id, titulo, url_video, plataforma, tipo, orden FROM videos WHERE activo = 1 ORDER BY orden ASC, id DESC')->fetchAll();

$noticias = db()->query(
    'SELECT n.id, n.titulo, n.slug, n.resumen, n.imagen, n.url_facebook, n.fecha_publicacion, c.nombre AS categoria
     FROM noticias n
     LEFT JOIN categorias c ON c.id = n.categoria_id
     ORDER BY n.destacada DESC, n.fecha_publicacion DESC, n.id DESC
     LIMIT 3'
)->fetchAll();

$testimonios = db()->query('SELECT id, nombre, cargo, texto, imagen FROM testimonios WHERE activo = 1 ORDER BY id DESC')->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<!-- ============================ HERO ============================ -->
<?php $imagenHero = config('imagen_hero', ''); ?>
<section class="hero" id="inicio">
    <?php if ($imagenHero): ?>
        <div class="hero__foto" style="background-image: url('<?= e(URL_UPLOADS . '/' . $imagenHero) ?>');" aria-hidden="true"></div>
    <?php endif; ?>
    <div class="contenedor hero__contenido">
        <?php if (config('logo', '')): ?>
            <img class="hero__logo" src="<?= e(URL_UPLOADS . '/' . config('logo')) ?>" alt="<?= e(config('nombre_sitio')) ?>">
        <?php endif; ?>
        <h1 class="hero__titulo"><?= e(config('nombre_sitio', 'Radio Miraflores')) ?></h1>
        <p class="hero__eslogan"><?= e(config('eslogan', '')) ?></p>
        <?php if (config('texto_hero', '')): ?>
            <p class="hero__texto"><?= e(config('texto_hero', '')) ?></p>
        <?php endif; ?>
        <a class="hero__boton" href="#videos">Escúchanos en vivo</a>
    </div>
</section>

<!-- ============================ VIDEOS ============================ -->
<section class="seccion" id="videos">
    <div class="contenedor">
        <h2 class="seccion__titulo">Videos y transmisiones en vivo</h2>
        <?php if ($videos): ?>
            <div class="videos">
                <?php foreach ($videos as $video): ?>
                    <?php
                        $esYouTube  = ($video['plataforma'] !== 'facebook');
                        $embed      = $esYouTube ? youtube_embed($video['url_video']) : facebook_embed($video['url_video']);
                        $videoId    = $esYouTube ? youtube_id($video['url_video']) : null;
                        $esVertical = ($video['plataforma'] === 'facebook' && preg_match('#/reel/#', $video['url_video']));
                        // Fachada: los videos de YouTube muestran su miniatura y solo
                        // cargan el reproductor (cerca de 1 MB) cuando se pulsa play.
                        $conFachada = ($esYouTube && $videoId !== null);
                        $miniatura  = $conFachada ? 'https://i.ytimg.com/vi/' . rawurlencode($videoId) . '/hqdefault.jpg' : '';
                    ?>
                    <?php if ($embed === ''): continue; endif; ?>
                    <div class="video<?= $video['tipo'] === 'en_vivo' ? ' video--envivo' : '' ?><?= $esVertical ? ' video--vertical' : '' ?>">
                        <div class="video__caja">
                            <?php if ($conFachada): ?>
                                <button class="video-fachada" type="button"
                                        data-embed="<?= e($embed) ?>"
                                        data-titulo="<?= e($video['titulo']) ?>"
                                        aria-label="Reproducir video: <?= e($video['titulo']) ?>">
                                    <img class="video-fachada__imagen" src="<?= e($miniatura) ?>" alt="" loading="lazy" width="480" height="270">
                                    <span class="video-fachada__play" aria-hidden="true"><svg viewBox="0 0 24 24" width="72" height="72"><circle cx="12" cy="12" r="12" fill="rgba(0,0,0,.6)"/><path d="M9.5 7.5l7 4.5-7 4.5z" fill="#fff"/></svg></span>
                                </button>
                            <?php else: ?>
                                <iframe
                                    src="<?= e($embed) ?>"
                                    title="<?= e($video['titulo']) ?>"
                                    loading="lazy"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen>
                                </iframe>
                            <?php endif; ?>
                        </div>
                        <h3 class="video__titulo"><?= e($video['titulo']) ?></h3>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="seccion__vacio">Pronto publicaremos videos y transmisiones en vivo.</p>
        <?php endif; ?>
    </div>
</section>

<!-- ============================ NOTICIAS ============================ -->
<section class="seccion seccion--gris" id="noticias">
    <div class="contenedor">
        <h2 class="seccion__titulo">Noticias</h2>
        <p class="seccion__mas"><a href="noticias.php">Ver todas las noticias &rarr;</a></p>
        <?php if ($noticias): ?>
            <div class="noticias">
                <?php foreach ($noticias as $noticia): ?>
                    <?php if (!empty($noticia['url_facebook'])): ?>
                        <?php $GLOBALS['cargar_fb_sdk'] = true; ?>
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
                                <h3 class="noticia__titulo"><?= e($noticia['titulo']) ?></h3>
                                <div class="noticia__pie">
                                    <time class="noticia__fecha"><?= fecha_larga($noticia['fecha_publicacion']) ?></time>
                                    <a class="noticia__enlace-fb" href="<?= e($noticia['url_facebook']) ?>" target="_blank" rel="noopener">Ver en Facebook &nearr;</a>
                                </div>
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
        <?php else: ?>
            <p class="seccion__vacio">Aún no hay noticias publicadas.</p>
        <?php endif; ?>
    </div>
</section>

<!-- ============================ NOSOTROS ============================ -->
<section class="seccion" id="nosotros">
    <div class="contenedor">
        <h2 class="seccion__titulo">Sobre Nosotros</h2>
        <?php $imagenNosotros = config('imagen_nosotros', ''); ?>
        <div class="nosotros<?= $imagenNosotros ? ' nosotros--con-imagen' : '' ?>">
            <?php if ($imagenNosotros): ?>
                <img class="nosotros__imagen" src="<?= e(URL_UPLOADS . '/' . $imagenNosotros) ?>" alt="Sobre Nosotros" loading="lazy">
            <?php endif; ?>
            <div class="contenido">
                <p><?= nl2br(e(config('texto_nosotros', 'Somos una radio comprometida con informar a nuestra comunidad.'))) ?></p>
            </div>
        </div>
    </div>
</section>

<!-- ============================ TESTIMONIOS ============================ -->
<section class="seccion seccion--gris" id="testimonios">
    <div class="contenedor">
        <h2 class="seccion__titulo">Testimonios</h2>
        <?php if ($testimonios): ?>
            <div class="testimonios">
                <?php foreach ($testimonios as $testimonio): ?>
                    <figure class="testimonio<?= !empty($testimonio['imagen']) ? ' testimonio--con-imagen' : '' ?>">
                        <?php if (!empty($testimonio['imagen'])): ?>
                            <img class="testimonio__imagen" src="<?= e(URL_UPLOADS . '/' . $testimonio['imagen']) ?>" alt="<?= e($testimonio['nombre']) ?>" loading="lazy">
                        <?php endif; ?>
                        <div class="testimonio__cuerpo">
                            <blockquote class="testimonio__texto"><?= e($testimonio['texto']) ?></blockquote>
                            <figcaption class="testimonio__autor">
                                <strong><?= e($testimonio['nombre']) ?></strong>
                                <?php if ($testimonio['cargo']): ?>
                                    <span><?= e($testimonio['cargo']) ?></span>
                                <?php endif; ?>
                            </figcaption>
                        </div>
                    </figure>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="seccion__vacio">Pronto compartiremos testimonios de nuestros oyentes.</p>
        <?php endif; ?>
    </div>
</section>

<!-- ============================ REDES SOCIALES ============================ -->
<section class="seccion" id="redes">
    <div class="contenedor">
        <h2 class="seccion__titulo">Síguenos en redes sociales</h2>
        <?php
        $redes = [
            ['facebook',  config('facebook'),  'Facebook'],
            ['instagram', config('instagram'), 'Instagram'],
            ['youtube',   config('youtube'),    'YouTube'],
            ['tiktok',    config('tiktok'),     'TikTok'],
            ['whatsapp',  config('whatsapp'),   'WhatsApp'],
        ];
        $redes_con_enlace = array_filter($redes, fn($r) => !empty($r[1]));
        $iconosRedes = [
            'facebook'  => 'M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z',
            'instagram' => 'M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z',
            'youtube'   => 'M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z',
            'tiktok'    => 'M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.1 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z',
            'whatsapp'  => 'M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z',
        ];
        ?>
        <?php if ($redes_con_enlace): ?>
            <div class="redes">
                <?php foreach ($redes_con_enlace as $red): ?>
                    <a class="red" href="<?= e($red[1]) ?>" target="_blank" rel="noopener" aria-label="<?= e($red[2]) ?>">
                        <span class="red__icono">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="<?= $iconosRedes[$red[0]] ?>"/></svg>
                        </span>
                        <span class="red__nombre"><?= e($red[2]) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="seccion__vacio">Pronto estaremos en redes sociales.</p>
        <?php endif; ?>
    </div>
</section>

<!-- ============================ CONTACTO ============================ -->
<section class="seccion seccion--gris" id="contacto">
    <div class="contenedor">
        <h2 class="seccion__titulo">Contáctanos</h2>
        <div class="contacto">
            <div class="contacto__datos">
                <?php if (config('direccion', '')): ?>
                    <p><strong>Dirección:</strong><br><?= e(config('direccion')) ?></p>
                <?php endif; ?>
                <?php if (config('telefono', '')): ?>
                    <p><strong>Teléfono:</strong><br><?= e(config('telefono')) ?></p>
                <?php endif; ?>
                <?php if (config('email', '')): ?>
                    <p><strong>Correo:</strong><br><?= e(config('email')) ?></p>
                <?php endif; ?>
            </div>
            <?php if (config('mapa_embed', '')): ?>
                <div class="contacto__mapa">
                    <iframe
                        src="<?= e(config('mapa_embed')) ?>"
                        title="Mapa de ubicación"
                        loading="lazy"
                        allowfullscreen>
                    </iframe>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
