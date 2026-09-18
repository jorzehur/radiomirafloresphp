<?php
// ============================================================
//  categorias.php - Gestión de categorías de noticias
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
requerir_login();

$paginaActiva = 'categorias';
$tituloAdmin = 'Categorías';
$mensaje = '';
$error = '';

// --- BORRAR ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['borrar'])) {
    if (!csrf_verificar($_POST['csrf_token'] ?? null)) {
        $error = 'Token inválido. No se pudo borrar.';
    } else {
        db()->prepare('DELETE FROM categorias WHERE id = ?')->execute([(int) $_POST['borrar']]);
        $mensaje = 'Categoría eliminada correctamente.';
    }
}

// --- GUARDAR ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['borrar'])) {
    if (!csrf_verificar($_POST['csrf_token'] ?? null)) {
        $error = 'Token inválido. Los cambios no se guardaron.';
    } else {
        $id     = (int) ($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');

        if ($nombre === '') {
            $error = 'El nombre es obligatorio.';
        } elseif (mb_strlen($nombre) > 100) {
            $error = 'El nombre no puede superar los 100 caracteres.';
        } else {
            $slug = slugify($nombre);
            $base = $slug;
            $n = 2;
            while (true) {
                $stmt = db()->prepare('SELECT id FROM categorias WHERE slug = ? AND id != ?');
                $stmt->execute([$slug, $id]);
                if (!$stmt->fetch()) break;
                $slug = $base . '-' . $n++;
            }

            if ($id > 0) {
                db()->prepare('UPDATE categorias SET nombre=?, slug=? WHERE id=?')->execute([$nombre, $slug, $id]);
                $mensaje = 'Categoría actualizada correctamente.';
            } else {
                db()->prepare('INSERT INTO categorias (nombre, slug) VALUES (?,?)')->execute([$nombre, $slug]);
                $mensaje = 'Categoría creada correctamente.';
            }
        }
    }
}

// --- Datos del formulario ---
$editando = null;
if (isset($_GET['id'])) {
    $stmt = db()->prepare('SELECT * FROM categorias WHERE id = ?');
    $stmt->execute([(int) $_GET['id']]);
    $editando = $stmt->fetch() ?: null;
} elseif (isset($_GET['nueva'])) {
    $editando = ['id' => 0, 'nombre' => ''];
}
$mostrarForm = $editando !== null;

$lista = db()->query('SELECT c.id, c.nombre, (SELECT COUNT(*) FROM noticias n WHERE n.categoria_id = c.id) AS total FROM categorias c ORDER BY nombre')->fetchAll();

require __DIR__ . '/includes/encabezado.php';
?>

<div class="titulo-pagina">
    <h1>Categorías</h1>
    <a class="boton" href="categorias.php?nueva=1">+ Nueva categoría</a>
</div>

<?php if ($mensaje): ?><div class="alerta alerta--ok"><?= e($mensaje) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alerta alerta--error"><?= e($error) ?></div><?php endif; ?>

<?php if ($mostrarForm): ?>
<form class="formulario" method="post" action="categorias.php">
    <?= csrf_campo() ?>
    <input type="hidden" name="id" value="<?= (int) $editando['id'] ?>">

    <h2 style="margin-top:0;"><?= $editando['id'] ? 'Editar categoría' : 'Nueva categoría' ?></h2>

    <div class="campo">
        <label for="nombre">Nombre *</label>
        <input type="text" id="nombre" name="nombre" required maxlength="100" value="<?= e($editando['nombre']) ?>">
    </div>

    <p>
        <button class="boton" type="submit">Guardar</button>
        <a class="boton boton--secundario" href="categorias.php">Cancelar</a>
    </p>
</form>
<?php endif; ?>

<?php if (!$mostrarForm): ?>
<table class="tabla">
    <thead>
        <tr>
            <th>Nombre</th>
            <th>Noticias</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!$lista): ?>
            <tr><td colspan="3" style="text-align:center;color:#999;">No hay categorías.</td></tr>
        <?php endif; ?>
        <?php foreach ($lista as $c): ?>
            <tr>
                <td><?= e($c['nombre']) ?></td>
                <td><?= (int) $c['total'] ?></td>
                <td>
                    <a class="boton boton--pequeno" href="categorias.php?id=<?= $c['id'] ?>">Editar</a>
                    <form class="form-borrar" method="post" action="categorias.php" onsubmit="return confirm('¿Eliminar esta categoría?');">
    <?= csrf_campo() ?>
    <input type="hidden" name="borrar" value="<?= (int) $c['id'] ?>">
    <button class="boton boton--pequeno boton--peligro" type="submit">Borrar</button>
</form>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php require __DIR__ . '/includes/pie.php'; ?>
