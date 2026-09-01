<?php
// ============================================================
//  testimonios.php - Gestión de testimonios
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
requerir_login();

$paginaActiva = 'testimonios';
$tituloAdmin = 'Testimonios';
$mensaje = '';
$error = '';

// --- BORRAR ---
if (isset($_GET['borrar'])) {
    if (!csrf_verificar($_GET['csrf_token'] ?? null)) {
        $error = 'Token inválido. No se pudo borrar.';
    } else {
        $id = (int) $_GET['borrar'];
        $stmt = db()->prepare('SELECT imagen FROM testimonios WHERE id = ?');
        $stmt->execute([$id]);
        $img = $stmt->fetchColumn();
        db()->prepare('DELETE FROM testimonios WHERE id = ?')->execute([$id]);
        borrar_imagen($img ?: null);
        $mensaje = 'Testimonio eliminado correctamente.';
    }
}

// --- GUARDAR ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verificar($_POST['csrf_token'] ?? null)) {
        $error = 'Token inválido. Los cambios no se guardaron.';
    } else {
        $id     = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $cargo  = trim($_POST['cargo'] ?? '');
        $texto  = trim($_POST['texto'] ?? '');
        $activo = isset($_POST['activo']) ? 1 : 0;

        if ($nombre === '' || $texto === '') {
            $error = 'El nombre y el texto son obligatorios.';
        } else {
            $imagenNueva = subir_imagen($_FILES['imagen'] ?? [], $errImg);
            if ($errImg) {
                $error = $errImg;
            } elseif ($id > 0) {
                if ($imagenNueva) {
                    $stmt = db()->prepare('SELECT imagen FROM testimonios WHERE id = ?');
                    $stmt->execute([$id]);
                    $vieja = $stmt->fetchColumn();
                    borrar_imagen($vieja ?: null);
                    db()->prepare('UPDATE testimonios SET nombre=?, cargo=?, texto=?, activo=?, imagen=? WHERE id=?')
                        ->execute([$nombre, $cargo, $texto, $activo, $imagenNueva, $id]);
                } else {
                    db()->prepare('UPDATE testimonios SET nombre=?, cargo=?, texto=?, activo=? WHERE id=?')
                        ->execute([$nombre, $cargo, $texto, $activo, $id]);
                }
                $mensaje = 'Testimonio actualizado correctamente.';
            } else {
                db()->prepare('INSERT INTO testimonios (nombre, cargo, texto, activo, imagen) VALUES (?,?,?,?,?)')
                    ->execute([$nombre, $cargo, $texto, $activo, $imagenNueva]);
                $mensaje = 'Testimonio creado correctamente.';
            }
        }
    }
}

// --- Datos del formulario ---
$editando = null;
if (isset($_GET['id'])) {
    $stmt = db()->prepare('SELECT * FROM testimonios WHERE id = ?');
    $stmt->execute([(int) $_GET['id']]);
    $editando = $stmt->fetch() ?: null;
} elseif (isset($_GET['nueva'])) {
    $editando = ['id' => 0, 'nombre' => '', 'cargo' => '', 'texto' => '', 'activo' => 1, 'imagen' => ''];
}
$mostrarForm = $editando !== null;

$lista = db()->query('SELECT id, nombre, cargo, texto, activo, imagen FROM testimonios ORDER BY id DESC')->fetchAll();

require __DIR__ . '/includes/encabezado.php';
?>

<div class="titulo-pagina">
    <h1>Testimonios</h1>
    <a class="boton" href="testimonios.php?nueva=1">+ Nuevo testimonio</a>
</div>

<?php if ($mensaje): ?><div class="alerta alerta--ok"><?= e($mensaje) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alerta alerta--error"><?= e($error) ?></div><?php endif; ?>

<?php if ($mostrarForm): ?>
<form class="formulario" method="post" action="testimonios.php" enctype="multipart/form-data">
    <?= csrf_campo() ?>
    <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">

    <h2 style="margin-top:0;"><?= $editando['id'] ? 'Editar testimonio' : 'Nuevo testimonio' ?></h2>

    <div class="campo">
        <label for="nombre">Nombre *</label>
        <input type="text" id="nombre" name="nombre" required value="<?= e($editando['nombre']) ?>">
    </div>

    <div class="campo">
        <label for="cargo">Cargo / descripción (opcional)</label>
        <input type="text" id="cargo" name="cargo" value="<?= e($editando['cargo']) ?>">
    </div>

    <div class="campo">
        <label for="texto">Texto del testimonio *</label>
        <textarea id="texto" name="texto" required><?= e($editando['texto']) ?></textarea>
    </div>

    <div class="campo">
        <label for="imagen">Imagen (opcional)</label>
        <?php if (!empty($editando['imagen'])): ?>
            <p class="ayuda">Imagen actual:</p>
            <img src="../uploads/<?= e($editando['imagen']) ?>" alt="" style="width:120px;border-radius:6px;margin-bottom:8px;">
        <?php endif; ?>
        <input type="file" id="imagen" name="imagen" accept="image/jpeg,image/png,image/gif,image/webp">
        <p class="ayuda">Foto de la persona o una imagen ilustrativa (se convertirá a WebP). Deja vacío para conservar la actual.</p>
    </div>

    <div class="campo">
        <label class="checkbox">
            <input type="checkbox" name="activo" <?= $editando['activo'] ? 'checked' : '' ?>>
            Visible en la página
        </label>
    </div>

    <p>
        <button class="boton" type="submit">Guardar</button>
        <a class="boton boton--secundario" href="testimonios.php">Cancelar</a>
    </p>
</form>
<?php endif; ?>

<?php if (!$mostrarForm): ?>
<table class="tabla">
    <thead>
        <tr>
            <th>Foto</th>
            <th>Nombre</th>
            <th>Cargo</th>
            <th>Texto</th>
            <th>Visible</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!$lista): ?>
            <tr><td colspan="6" style="text-align:center;color:#999;">No hay testimonios todavía.</td></tr>
        <?php endif; ?>
        <?php foreach ($lista as $t): ?>
            <tr>
                <td>
                    <?php if (!empty($t['imagen'])): ?>
                        <img class="mini" src="../uploads/<?= e($t['imagen']) ?>" alt="">
                    <?php else: ?>
                        <span style="color:#666;">—</span>
                    <?php endif; ?>
                </td>
                <td><?= e($t['nombre']) ?></td>
                <td><?= e($t['cargo']) ?></td>
                <td style="max-width:340px;"><?= e(mb_substr($t['texto'], 0, 80)) ?><?= mb_strlen($t['texto']) > 80 ? '…' : '' ?></td>
                <td><?= $t['activo'] ? 'Sí' : 'No' ?></td>
                <td>
                    <a class="boton boton--pequeno" href="testimonios.php?id=<?= $t['id'] ?>">Editar</a>
                    <a class="boton boton--pequeno boton--peligro"
                       href="testimonios.php?borrar=<?= $t['id'] ?>&csrf_token=<?= e(csrf_token()) ?>"
                       onclick="return confirm('¿Eliminar este testimonio?');">Borrar</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php require __DIR__ . '/includes/pie.php'; ?>
