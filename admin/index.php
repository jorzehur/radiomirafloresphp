<?php
// ============================================================
//  index.php - Dashboard del panel de administracion
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/admin-funciones.php';
require_once __DIR__ . '/../includes/facebook.php';
requerir_login();

$paginaActiva = 'dashboard';
$tituloAdmin  = 'Dashboard';
$mensaje      = '';
$error        = '';
$nuevaId      = 0;

// --- Alta rapida: enlace (obligatorio) + titulo y texto (opcionales) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url_facebook_rapida'])) {
    if (!csrf_verificar($_POST['csrf_token'] ?? null)) {
        $error = 'Token invalido. Intentalo de nuevo.';
    } else {
        $url          = facebook_url_desde_texto((string) $_POST['url_facebook_rapida']);
        $tituloManual = trim((string) ($_POST['titulo_rapido'] ?? ''));
        $texto        = trim((string) ($_POST['texto_rapido'] ?? ''));

        if ($url === '') {
            $error = 'Pega el enlace de la publicacion de Facebook.';
        } else {
            $url = facebook_resolver($url);

            if (facebook_post_embed($url) === '') {
                $error = "Eso no es una publicaci\u{00F3}n de Facebook. Copia el enlace desde el propio post o pega el c\u{00F3}digo de Insertar entero.";
            } elseif (!facebook_post_disponible($url)) {
                $error = "Facebook dice que esa publicaci\u{00F3}n ya no est\u{00E1} disponible (se ha eliminado o es privada). Abre el post en tu p\u{00E1}gina, comprueba que sea p\u{00FA}blico y copia el enlace desde la fecha del post.";
            } elseif (mb_strlen($url) > 255) {
                $error = 'El enlace es demasiado largo.';
            } else {
                // ¿Ya estaba esa publicacion?
                $stmt = db()->prepare('SELECT id FROM noticias WHERE url_facebook = ? LIMIT 1');
                $stmt->execute([$url]);
                $yaEsta = (int) $stmt->fetchColumn();
                if ($yaEsta > 0) {
                    redirigir('index.php?repetida=' . $yaEsta);
                }

                $hoy    = date('Y-m-d');
                $titulo = ($tituloManual !== '') ? $tituloManual : "Publicaci\u{00F3}n del " . fecha_larga($hoy);
                if (mb_strlen($titulo) > 200) { $titulo = mb_substr($titulo, 0, 200); }

                $resumen = ($texto !== '') ? mb_substr(preg_replace('/\s+/', ' ', $texto), 0, 180) : '';
                $imagen  = null;
                $traido  = false;

                // Se trae del post lo que falte (Facebook lo publica para los robots:
                // no hace falta token ni aplicacion)
                $datos = facebook_datos_post($url);
                if ($texto === '' && !empty($datos['texto'])) {
                    $texto   = $datos['texto'];
                    $resumen = mb_substr($texto, 0, 180);
                    $traido  = true;
                }
                if (!empty($datos['imagen'])) {
                    $bajada = guardar_imagen_desde_url($datos['imagen']);
                    if ($bajada !== null) {
                        $imagen = $bajada;
                        $traido = true;
                    }
                }

                $slug = slugify($titulo);
                if ($slug === '') { $slug = 'publicacion'; }
                $base = $slug;
                $n    = 2;
                while (true) {
                    $stmt = db()->prepare('SELECT id FROM noticias WHERE slug = ?');
                    $stmt->execute([$slug]);
                    if (!$stmt->fetch()) { break; }
                    $slug = $base . '-' . $n++;
                }

                db()->prepare('INSERT INTO noticias (titulo, slug, resumen, contenido, imagen, url_facebook, categoria_id, destacada, fecha_publicacion)
                               VALUES (?, ?, ?, ?, ?, ?, NULL, 0, ?)')
                    ->execute([$titulo, $slug, $resumen, $texto, $imagen, $url, $hoy]);

                // Limpieza automática: borra las noticias más antiguas que el límite
                // puesto en Ajustes (y sus imágenes), para no llenar el hosting.
                noticias_limpiar_antiguas();

                redirigir('index.php?anadida=' . (int) db()->lastInsertId() . ($traido ? '&traido=1' : ''));
            }
        }
    }
}

if (isset($_GET['anadida'])) {
    $nuevaId = (int) $_GET['anadida'];
    $mensaje = "Publicaci\u{00F3}n a\u{00F1}adida correctamente.";
    if (!empty($_GET['traido'])) {
        $mensaje .= " Se han tra\u{00ED}do del post el texto y la imagen.";
    }
}

if (isset($_GET['repetida'])) {
    $nuevaId = (int) $_GET['repetida'];
    $mensaje = "Esa publicaci\u{00F3}n ya estaba en la web, no la he duplicado.";
}

$contadores = [
    'Noticias'          => (int) db()->query('SELECT COUNT(*) FROM noticias')->fetchColumn(),
    'Videos'            => (int) db()->query('SELECT COUNT(*) FROM videos')->fetchColumn(),
    'Testimonios'       => (int) db()->query('SELECT COUNT(*) FROM testimonios')->fetchColumn(),
    "Categor\u{00ED}as" => (int) db()->query('SELECT COUNT(*) FROM categorias')->fetchColumn(),
];

require __DIR__ . '/includes/encabezado.php';
?>

<div class="titulo-pagina">
    <h1>Bienvenido, <?= e($usuarioAdmin['nombre'] ?? $usuarioAdmin['usuario']) ?></h1>
</div>

<?php if ($mensaje): ?>
    <div class="alerta alerta--ok">
        <?= e($mensaje) ?>
        <?php if ($nuevaId > 0): ?>
            <span class="alerta__acciones">
                <a href="noticias.php?id=<?= $nuevaId ?>">editarla</a>
                <a href="../index.php" target="_blank" rel="noopener">ver en la portada</a>
            </span>
        <?php endif; ?>
    </div>
<?php endif; ?>
<?php if ($error): ?><div class="alerta alerta--error"><?= e($error) ?></div><?php endif; ?>

<div class="formulario formulario--rapido">
    <h2>A&ntilde;adir noticia de Facebook</h2>
    <p class="ayuda">
        <strong>El enlace:</strong> abre la publicaci&oacute;n en Facebook y pulsa la <strong>fecha del post</strong>
        (o los tres puntos &rarr; Copiar enlace). Tambi&eacute;n vale pegar el c&oacute;digo de <strong>Insertar</strong> entero.
    </p>
    <p class="ayuda">
        El texto y la imagen se traen solos del post. Si escribes un <strong>texto</strong> propio, se usa el tuyo.
        leer completa en tu web; si los dejas vac&iacute;os, se muestra el post de Facebook y ya.
    </p>
    <form method="post" action="index.php">
        <?= csrf_campo() ?>
        <div class="campo">
            <label for="url_facebook_rapida">Enlace de la publicaci&oacute;n *</label>
            <input type="text" inputmode="url" id="url_facebook_rapida" name="url_facebook_rapida" maxlength="1500" required autofocus
                   placeholder="https://www.facebook.com/radiomiraflorestelevision/posts/...">
        </div>
        <div class="campo">
            <label for="titulo_rapido">T&iacute;tulo</label>
            <input type="text" id="titulo_rapido" name="titulo_rapido" maxlength="200"
                   placeholder="Si lo dejas vac&iacute;o se pone la fecha">
        </div>
        <div class="campo">
            <label for="texto_rapido">Texto de la noticia</label>
            <textarea id="texto_rapido" name="texto_rapido" rows="6"
                      placeholder="Opcional. Si lo pegas, se podr&aacute; leer completo en tu web (en Facebook sigue estando el original)."></textarea>
        </div>
        <button class="boton" type="submit">Guardar y publicar</button>
    </form>
</div>

<?php if (facebook_configurado()): ?>
    <?php $postsFb = isset($_GET['traer']) ? facebook_publicaciones(5) : array(); ?>
    <div class="formulario">
        <h2>Traer mis &uacute;ltimas publicaciones de Facebook</h2>
        <p class="ayuda">
            Aqu&iacute; el texto llega <strong>completo</strong>, tal como lo escribiste en el post
            (Facebook solo da un extracto cuando se lee el enlace suelto).
        </p>

        <?php if (!isset($_GET['traer'])): ?>
            <p><a class="boton" href="index.php?traer=1">Traer mis &uacute;ltimas publicaciones</a></p>
        <?php elseif (!$postsFb): ?>
            <div class="alerta alerta--error">No he podido leerlas. <?= e(facebook_error()) ?></div>
            <p><a class="boton boton--secundario" href="index.php">Volver</a></p>
        <?php else: ?>
            <?php foreach ($postsFb as $p): ?>
                <div class="tarjeta" style="margin-bottom:14px;padding:14px;">
                    <div class="tarjeta__etiqueta"><?= e($p['fecha']) ?> &middot; <?= (int) mb_strlen($p['texto']) ?> caracteres</div>
                    <p style="margin:8px 0;font-size:0.9rem;color:#ccc;"><?= e(mb_substr($p['texto'], 0, 280)) ?><?= mb_strlen($p['texto']) > 280 ? '...' : '' ?></p>
                    <form class="form-borrar" method="post" action="index.php">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="url_facebook_rapida" value="<?= e($p['url']) ?>">
                        <input type="hidden" name="titulo_rapido" value="<?= e($p['titulo']) ?>">
                        <input type="hidden" name="texto_rapido" value="<?= e($p['texto']) ?>">
                        <button class="boton boton--pequeno" type="submit">A&ntilde;adir esta</button>
                    </form>
                </div>
            <?php endforeach; ?>
            <p><a class="boton boton--secundario" href="index.php">Volver</a></p>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="tarjetas">
    <?php foreach ($contadores as $etiqueta => $numero): ?>
        <div class="tarjeta">
            <div class="tarjeta__numero"><?= $numero ?></div>
            <div class="tarjeta__etiqueta"><?= e($etiqueta) ?></div>
        </div>
    <?php endforeach; ?>
</div>

<div class="formulario">
    <h2>Otras acciones</h2>
    <p style="color:#999;">Usa el men&uacute; de la izquierda para gestionar el contenido del sitio.</p>
    <p>
        <a class="boton" href="noticias.php?nueva=1">+ Nueva noticia completa</a>
        <a class="boton boton--secundario" href="videos.php?nueva=1">+ Nuevo video</a>
    </p>
</div>

<?php require __DIR__ . '/includes/pie.php'; ?>