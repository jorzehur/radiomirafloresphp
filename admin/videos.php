<?php
// ============================================================
//  videos.php - Gestión de videos / transmisiones en vivo
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
requerir_login();

$paginaActiva = 'videos';
$tituloAdmin = 'Videos';
$mensaje = '';
$error = '';

// --- BORRAR ---
if (isset($_GET['borrar'])) {
    if (!csrf_verificar($_GET['csrf_token'] ?? null)) {
        $error = 'Token inválido. No se pudo borrar.';
    } else {
        db()->prepare('DELETE FROM videos WHERE id = ?')->execute([(int) $_GET['borrar']]);
        $mensaje = 'Video eliminado correctamente.';
    }
}

// --- GUARDAR ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verificar($_POST['csrf_token'] ?? null)) {
        $error = 'Token inválido. Los cambios no se guardaron.';
    } else {
        $id         = (int) ($_POST['id'] ?? 0);
        $titulo     = trim($_POST['titulo'] ?? '');
        $url        = trim($_POST['url_video'] ?? '');
        $plataforma = ($_POST['plataforma'] ?? 'youtube') === 'facebook' ? 'facebook' : 'youtube';
        $tipo       = $_POST['tipo'] ?? 'video';
        $activo     = isset($_POST['activo']) ? 1 : 0;
        $orden      = (int) ($_POST['orden'] ?? 0);

        // Validación de URL con dominios específicos
        $url_valida = false;
        if ($plataforma === 'facebook') {
            $url = facebook_resolver($url);
            $url_valida = validar_url($url, ['facebook.com']);
        } elseif ($plataforma === 'youtube') {
            $url_valida = validar_url($url, ['youtube.com', 'youtu.be']);
        }

        if ($titulo === '' || $url === '') {
            $error = 'El título y la URL del video son obligatorios.';
        } elseif (!$url_valida) {
            $error = 'La URL no es válida. Por favor usa un formato correcto:
            - YouTube: https://youtu.be/ID o https://www.youtube.com/watch?v=ID
            - Facebook: Enlace de video o directo';
        } elseif ($plataforma === 'facebook' && facebook_embed($url) === '') {
            $error = 'La URL de Facebook no es válida. Pega el enlace del video o del directo.';
        } elseif ($plataforma === 'youtube' && youtube_id($url) === null) {
            $error = 'La URL de YouTube no es válida. Usa un enlace de YouTube (watch, youtu.be, live o embed).';
        } else {
            if ($id > 0) {
                db()->prepare('UPDATE videos SET titulo=?, url_video=?, plataforma=?, tipo=?, activo=?, orden=? WHERE id=?')
                    ->execute([$titulo, $url, $plataforma, $tipo, $activo, $orden, $id]);
                $mensaje = 'Video actualizado correctamente.';
            } else {
                db()->prepare('INSERT INTO videos (titulo, url_video, plataforma, tipo, activo, orden) VALUES (?,?,?,?,?,?)')
                    ->execute([$titulo, $url, $plataforma, $tipo, $activo, $orden]);
                $mensaje = 'Video creado correctamente.';
            }
        }
    }
}

// --- Datos del formulario ---
$editando = null;
if (isset($_GET['id'])) {
    $stmt = db()->prepare('SELECT * FROM videos WHERE id = ?');
    $stmt->execute([(int) $_GET['id']]);
    $editando = $stmt->fetch() ?: null;
} elseif (isset($_GET['nueva'])) {
    $editando = ['id' => 0, 'titulo' => '', 'url_video' => '', 'plataforma' => 'youtube', 'tipo' => 'video', 'activo' => 1, 'orden' => 0];
}
$mostrarForm = $editando !== null;

$lista = db()->query('SELECT id, titulo, plataforma, tipo, activo, orden FROM videos ORDER BY orden ASC, id DESC')->fetchAll();

require __DIR__ . '/includes/encabezado.php';
?>

<div class="titulo-pagina">
    <h1>Videos</h1>
    <a class="boton" href="videos.php?nueva=1">+ Nuevo video</a>
</div>

<?php if ($mensaje): ?><div class="alerta alerta--ok"><?= e($mensaje) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alerta alerta--error"><?= e($error) ?></div><?php endif; ?>

<?php if ($mostrarForm): ?>
<form class="formulario" method="post" action="videos.php">
    <?= csrf_campo() ?>
    <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">

    <h2 style="margin-top:0;"><?= $editando['id'] ? 'Editar video' : 'Nuevo video' ?></h2>

    <div class="campo">
        <label for="titulo">Título *</label>
        <input type="text" id="titulo" name="titulo" required value="<?= e($editando['titulo']) ?>">
    </div>

    <div class="campo">
        <label for="plataforma">Plataforma *</label>
        <select id="plataforma" name="plataforma">
            <option value="youtube" <?= $editando['plataforma'] === 'youtube' ? 'selected' : '' ?>>YouTube</option>
            <option value="facebook" <?= $editando['plataforma'] === 'facebook' ? 'selected' : '' ?>>Facebook</option>
        </select>
    </div>

    <div class="campo">
        <label for="url_video">URL del video *</label>
        <input type="url" id="url_video" name="url_video" required value="<?= e($editando['url_video']) ?>" placeholder="https://www.youtube.com/watch?v=...">
        <p class="ayuda">Para una transmisión en vivo, pega la URL del directo. En Facebook puedes pegar el enlace del video, del directo o un enlace "compartir" (se convertirá automáticamente).</p>
    </div>

    <div class="campo">
        <label for="tipo">Tipo</label>
        <select id="tipo" name="tipo">
            <option value="video" <?= $editando['tipo'] === 'video' ? 'selected' : '' ?>>Video normal</option>
            <option value="en_vivo" <?= $editando['tipo'] === 'en_vivo' ? 'selected' : '' ?>>Transmisión en vivo</option>
        </select>
    </div>

    <div class="campo">
        <label for="orden">Orden (menor = primero)</label>
        <input type="number" id="orden" name="orden" value="<?= (int) $editando['orden'] ?>">
    </div>

    <div class="campo">
        <label class="checkbox">
            <input type="checkbox" name="activo" <?= $editando['activo'] ? 'checked' : '' ?>>
            Visible en la página
        </label>
    </div>

    <p>
        <button class="boton" type="submit">Guardar</button>
        <a class="boton boton--secundario" href="videos.php">Cancelar</a>
    </p>
</form>
<?php endif; ?>

<?php if (!$mostrarForm): ?>
<table class="tabla">
    <thead>
        <tr>
            <th>Título</th>
            <th>Plataforma</th>
            <th>Tipo</th>
            <th>Orden</th>
            <th>Visible</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!$lista): ?>
            <tr><td colspan="6" style="text-align:center;color:#999;">No hay videos todavía.</td></tr>
        <?php endif; ?>
        <?php foreach ($lista as $v): ?>
            <tr>
                <td><?= e($v['titulo']) ?></td>
                <td><?= $v['plataforma'] === 'facebook' ? 'Facebook' : 'YouTube' ?></td>
                <td><?= $v['tipo'] === 'en_vivo' ? '🔴 En vivo' : 'Video' ?></td>
                <td><?= (int) $v['orden'] ?></td>
                <td><?= $v['activo'] ? 'Sí' : 'No' ?></td>
                <td>
                    <a class="boton boton--pequeno" href="videos.php?id=<?= $v['id'] ?>">Editar</a>
                    <a class="boton boton--pequeno boton--peligro"
                       href="videos.php?borrar=<?= $v['id'] ?>&csrf_token=<?= e(csrf_token()) ?>"
                       onclick="return confirm('¿Eliminar este video?');">Borrar</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php require __DIR__ . '/includes/pie.php'; ?>
