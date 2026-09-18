<?php
// ============================================================
//  index.php - Dashboard del panel de administracion
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/admin-funciones.php';
requerir_login();

$paginaActiva = 'dashboard';
$tituloAdmin  = 'Dashboard';
$mensaje      = '';
$error        = '';
$nuevaId      = 0;

// --- Alta rapida: pegar solo la URL de una publicacion de Facebook ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url_facebook_rapida'])) {
    if (!csrf_verificar($_POST['csrf_token'] ?? null)) {
        $error = 'Token invalido. Intentalo de nuevo.';
    } else {
        $url = trim((string) $_POST['url_facebook_rapida']);

        if ($url === '') {
            $error = 'Pega la URL de la publicacion de Facebook.';
        } else {
            // Resuelve los enlaces "compartir" a la URL directa del post
            $url = facebook_resolver($url);

            if (facebook_post_embed($url) === '') {
                $error = "Esa URL no es una publicaci\u{00F3}n de Facebook. Copiala desde el propio post con Compartir > Copiar enlace.";
            } elseif (mb_strlen($url) > 255) {
                $error = 'La URL es demasiado larga.';
            } else {
                // Si esa publicacion ya esta en la web, no se duplica
                $stmt = db()->prepare('SELECT id FROM noticias WHERE url_facebook = ? LIMIT 1');
                $stmt->execute([$url]);
                $yaEsta = (int) $stmt->fetchColumn();
                if ($yaEsta > 0) {
                    redirigir('index.php?repetida=' . $yaEsta);
                }

                $hoy    = date('Y-m-d');
                $titulo = "Publicaci\u{00F3}n del " . fecha_larga($hoy);

                // Slug unico
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
                               VALUES (?, ?, ?, ?, NULL, ?, NULL, 0, ?)')
                    ->execute([$titulo, $slug, '', '', $url, $hoy]);

                redirigir('index.php?anadida=' . (int) db()->lastInsertId());
            }
        }
    }
}

if (isset($_GET['anadida'])) {
    $nuevaId = (int) $_GET['anadida'];
    $mensaje = "Publicaci\u{00F3}n a\u{00F1}adida correctamente.";
}

if (isset($_GET['repetida'])) {
    $nuevaId = (int) $_GET['repetida'];
    $mensaje = "Esa publicaci\u{00F3}n ya estaba en la web, no la he duplicado.";
}

$contadores = [
    'Noticias'                 => (int) db()->query('SELECT COUNT(*) FROM noticias')->fetchColumn(),
    'Videos'                   => (int) db()->query('SELECT COUNT(*) FROM videos')->fetchColumn(),
    'Testimonios'              => (int) db()->query('SELECT COUNT(*) FROM testimonios')->fetchColumn(),
    "Categor\u{00ED}as"        => (int) db()->query('SELECT COUNT(*) FROM categorias')->fetchColumn(),
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
                <a href="noticias.php?id=<?= $nuevaId ?>">ponerle t&iacute;tulo y categor&iacute;a</a>
                <a href="../index.php" target="_blank" rel="noopener">ver en la portada</a>
            </span>
        <?php endif; ?>
    </div>
<?php endif; ?>
<?php if ($error): ?><div class="alerta alerta--error"><?= e($error) ?></div><?php endif; ?>

<div class="formulario formulario--rapido">
    <h2>A&ntilde;adir publicaci&oacute;n de Facebook</h2>
    <p class="ayuda">
        Pega el enlace del post (sirve tambi&eacute;n el de "Compartir") y pulsa Guardar.
        Se a&ntilde;ade con la fecha de hoy y aparece la primera en la portada.
        Si quieres, luego le pones t&iacute;tulo y categor&iacute;a.
    </p>
    <form method="post" action="index.php">
        <?= csrf_campo() ?>
        <div class="campo">
            <label for="url_facebook_rapida">URL de la publicaci&oacute;n</label>
            <input type="url" id="url_facebook_rapida" name="url_facebook_rapida" required autofocus
                   placeholder="https://www.facebook.com/radiomiraflorestelevision/posts/...">
        </div>
        <button class="boton" type="submit">Guardar y publicar</button>
    </form>
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
    <h2>Otras acciones</h2>
    <p style="color:#999;">Usa el men&uacute; de la izquierda para gestionar el contenido del sitio.</p>
    <p>
        <a class="boton" href="noticias.php?nueva=1">+ Nueva noticia completa</a>
        <a class="boton boton--secundario" href="videos.php?nueva=1">+ Nuevo video</a>
    </p>
</div>

<?php require __DIR__ . '/includes/pie.php'; ?>