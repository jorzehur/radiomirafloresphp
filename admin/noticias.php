<?php
// ============================================================
//  noticias.php - Gestión de noticias (crear/editar/borrar)
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/admin-funciones.php';
requerir_login();

$paginaActiva = 'noticias';
$tituloAdmin = 'Noticias';
$mensaje = '';
$error = '';

// --- Categorías para el selector ---
$categorias = db()->query('SELECT id, nombre FROM categorias ORDER BY nombre')->fetchAll();

// --- BORRAR ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrar'])) {
    if (!csrf_verificar($_POST['csrf_token'] ?? null)) {
        $error = 'Token inválido. No se pudo borrar.';
    } else {
        $id = (int) $_POST['borrar'];
        // Borrar la imagen asociada antes de borrar la noticia
        $stmt = db()->prepare('SELECT imagen FROM noticias WHERE id = ?');
        $stmt->execute([$id]);
        $img = $stmt->fetchColumn();
        db()->prepare('DELETE FROM noticias WHERE id = ?')->execute([$id]);
        borrar_imagen($img ?: null);
        $mensaje = 'Noticia eliminada correctamente.';
    }
}

// --- GUARDAR (crear o editar) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['borrar'])) {
    if (!csrf_verificar($_POST['csrf_token'] ?? null)) {
        $error = 'Token inválido. Los cambios no se guardaron.';
    } else {
        $id         = (int) ($_POST['id'] ?? 0);
        $titulo     = trim($_POST['titulo'] ?? '');
        $categoria  = ($_POST['categoria_id'] ?? '') !== '' ? (int) $_POST['categoria_id'] : null;
        $resumen    = trim($_POST['resumen'] ?? '');
        $contenido  = trim($_POST['contenido'] ?? '');
        $destacada    = isset($_POST['destacada']) ? 1 : 0;
        $fecha        = $_POST['fecha_publicacion'] ?? date('Y-m-d');
        $url_facebook = trim($_POST['url_facebook'] ?? '');

        // Resolver y validar el enlace de Facebook (si se proporcionó)
        if ($url_facebook !== '') {
            $url_facebook = facebook_resolver($url_facebook);
            if (facebook_post_embed($url_facebook) === '') {
                $error = 'La URL de Facebook no es válida. Pega el enlace de la publicación.';
            } elseif (mb_strlen($url_facebook) > 255) {
                $error = 'La URL de Facebook es demasiado larga (máx. 255 caracteres).';
            }
        }

        if ($titulo === '') {
            $error = 'El título es obligatorio.';
        } elseif (mb_strlen($titulo) > 200) {
            $error = 'El título no puede superar los 200 caracteres.';
        }

        if ($fecha !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $fecha);
            if (!$d || $d->format('Y-m-d') !== $fecha) {
                $error = 'La fecha de publicación no es válida.';
            }
        }

        if (!$error) {
            // Slug único
            $slug = slugify($titulo);
            $base = $slug;
            $n = 2;
            while (true) {
                $stmt = db()->prepare('SELECT id FROM noticias WHERE slug = ? AND id != ?');
                $stmt->execute([$slug, $id]);
                if (!$stmt->fetch()) break;
                $slug = $base . '-' . $n++;
            }

            // Imagen (solo si se subió una nueva)
            $imagenNueva = subir_imagen($_FILES['imagen'] ?? [], $errImg);
            if ($errImg) {
                $error = $errImg;
            }

            if (!$error) {
                if ($id > 0) {
                    // Editar
                    if ($imagenNueva) {
                        // borrar la anterior
                        $stmt = db()->prepare('SELECT imagen FROM noticias WHERE id = ?');
                        $stmt->execute([$id]);
                        $vieja = $stmt->fetchColumn();
                        borrar_imagen($vieja ?: null);

                        db()->prepare(
                            'UPDATE noticias SET titulo=?, slug=?, resumen=?, contenido=?, imagen=?, url_facebook=?, categoria_id=?, destacada=?, fecha_publicacion=? WHERE id=?'
                        )->execute([$titulo, $slug, $resumen, $contenido, $imagenNueva, $url_facebook, $categoria, $destacada, $fecha, $id]);
                    } else {
                        db()->prepare(
                            'UPDATE noticias SET titulo=?, slug=?, resumen=?, contenido=?, url_facebook=?, categoria_id=?, destacada=?, fecha_publicacion=? WHERE id=?'
                        )->execute([$titulo, $slug, $resumen, $contenido, $url_facebook, $categoria, $destacada, $fecha, $id]);
                    }
                    $mensaje = 'Noticia actualizada correctamente.';
                } else {
                    // Crear
                    db()->prepare(
                        'INSERT INTO noticias (titulo, slug, resumen, contenido, imagen, url_facebook, categoria_id, destacada, fecha_publicacion) VALUES (?,?,?,?,?,?,?,?,?)'
                    )->execute([$titulo, $slug, $resumen, $contenido, $imagenNueva, $url_facebook, $categoria, $destacada, $fecha]);
                    $mensaje = 'Noticia creada correctamente.';
                }
            }
        }
    }
}

// --- Datos para el formulario (edición o nueva) ---
$editando = null;
if (isset($_GET['id'])) {
    $stmt = db()->prepare('SELECT * FROM noticias WHERE id = ?');
    $stmt->execute([(int) $_GET['id']]);
    $editando = $stmt->fetch() ?: null;
} elseif (isset($_GET['nueva'])) {
    $editando = [
        'id' => 0, 'titulo' => '', 'resumen' => '', 'contenido' => '',
        'imagen' => '', 'url_facebook' => '', 'categoria_id' => null, 'destacada' => 0,
        'fecha_publicacion' => date('Y-m-d'),
    ];
}

$mostrarForm = $editando !== null;

// --- Listado ---
$lista = db()->query(
    'SELECT n.id, n.titulo, n.imagen, n.fecha_publicacion, n.destacada, c.nombre AS categoria
     FROM noticias n LEFT JOIN categorias c ON c.id = n.categoria_id
     ORDER BY n.fecha_publicacion DESC, n.id DESC'
)->fetchAll();

require __DIR__ . '/includes/encabezado.php';
?>

<div class="titulo-pagina">
    <h1>Noticias</h1>
    <a class="boton" href="noticias.php?nueva=1">+ Nueva noticia</a>
</div>

<?php if ($mensaje): ?><div class="alerta alerta--ok"><?= e($mensaje) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alerta alerta--error"><?= e($error) ?></div><?php endif; ?>

<?php if ($mostrarForm): ?>
<form class="formulario" method="post" action="noticias.php" enctype="multipart/form-data">
    <?= csrf_campo() ?>
    <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">

    <h2 style="margin-top:0;"><?= $editando['id'] ? 'Editar noticia' : 'Nueva noticia' ?></h2>

    <div class="campo">
        <label for="titulo">Título *</label>
        <input type="text" id="titulo" name="titulo" required maxlength="200" value="<?= e($editando['titulo']) ?>">
    </div>

    <div class="campo">
        <label for="categoria_id">Categoría</label>
        <select id="categoria_id" name="categoria_id">
            <option value="">— Sin categoría —</option>
            <?php foreach ($categorias as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= (int) $editando['categoria_id'] === (int) $cat['id'] ? 'selected' : '' ?>>
                    <?= e($cat['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="campo">
        <label for="fecha_publicacion">Fecha de publicación</label>
        <input type="date" id="fecha_publicacion" name="fecha_publicacion" value="<?= e($editando['fecha_publicacion']) ?>">
    </div>

    <div class="campo">
        <label for="resumen">Resumen (aparece en la portada)</label>
        <textarea id="resumen" name="resumen"><?= e($editando['resumen']) ?></textarea>
    </div>

    <div class="campo">
        <label for="contenido">Contenido completo</label>
        <textarea id="contenido" name="contenido" style="min-height:220px;"><?= e($editando['contenido']) ?></textarea>
    </div>

    <div class="campo">
        <label for="url_facebook">URL de Facebook (opcional)</label>
        <input type="url" id="url_facebook" name="url_facebook" maxlength="255" value="<?= e($editando['url_facebook']) ?>" placeholder="https://www.facebook.com/...">
        <p class="ayuda">Si pegas el enlace de una publicación de Facebook, la noticia se mostrará incrustada desde Facebook (con su imagen y texto), sin necesidad de subir imagen ni escribir contenido. Acepta enlaces "compartir".</p>
    </div>

    <div class="campo">
        <label for="imagen">Imagen (JPG, PNG, GIF o WEBP, máx. 5 MB)</label>
        <?php if (!empty($editando['imagen'])): ?>
            <p class="ayuda">Imagen actual:</p>
            <img src="../uploads/<?= e($editando['imagen']) ?>" alt="" style="width:180px;border-radius:6px;margin-bottom:8px;">
        <?php endif; ?>
        <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/gif,image/webp">
        <p class="ayuda">Deja este campo vacío para conservar la imagen actual.</p>
    </div>

    <div class="campo">
        <label class="checkbox">
            <input type="checkbox" name="destacada" <?= $editando['destacada'] ? 'checked' : '' ?>>
            Destacada (aparece primero en la portada)
        </label>
    </div>

    <p>
        <button class="boton" type="submit">Guardar</button>
        <a class="boton boton--secundario" href="noticias.php">Cancelar</a>
    </p>
</form>
<?php endif; ?>

<?php if (!$mostrarForm): ?>
<table class="tabla">
    <thead>
        <tr>
            <th>Imagen</th>
            <th>Título</th>
            <th>Categoría</th>
            <th>Fecha</th>
            <th>Destacada</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!$lista): ?>
            <tr><td colspan="6" style="text-align:center;color:#999;">No hay noticias todavía.</td></tr>
        <?php endif; ?>
        <?php foreach ($lista as $n): ?>
            <tr>
                <td>
                    <?php if (!empty($n['imagen'])): ?>
                        <img class="mini" src="../uploads/<?= e($n['imagen']) ?>" alt="">
                    <?php else: ?>
                        <span style="color:#666;">—</span>
                    <?php endif; ?>
                </td>
                <td><?= e($n['titulo']) ?></td>
                <td><?= e($n['categoria'] ?? '—') ?></td>
                <td><?= e($n['fecha_publicacion']) ?></td>
                <td><?= $n['destacada'] ? 'Sí' : 'No' ?></td>
                <td>
                    <a class="boton boton--pequeno" href="noticias.php?id=<?= $n['id'] ?>">Editar</a>
                    <form class="form-borrar" method="post" action="noticias.php" onsubmit="return confirm('¿Eliminar esta noticia?');">
    <?= csrf_campo() ?>
    <input type="hidden" name="borrar" value="<?= (int) $n['id'] ?>">
    <button class="boton boton--pequeno boton--peligro" type="submit">Borrar</button>
</form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php require __DIR__ . '/includes/pie.php'; ?>
