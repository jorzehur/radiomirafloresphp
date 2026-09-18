<?php
// ============================================================
//  cambiar-password.php - Cambiar la contraseña del admin
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
requerir_login();

$paginaActiva = 'password';
$tituloAdmin = 'Mi cuenta';
$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verificar($_POST['csrf_token'] ?? null)) {
        $error = 'Token inválido. No se pudieron guardar los cambios.';
    } else {
        $accion = $_POST['accion'] ?? '';

        if ($accion === 'cambiar_usuario') {
            $nuevoUsuario = trim($_POST['usuario_nuevo'] ?? '');
            $actual       = $_POST['password_actual'] ?? '';

            $stmt = db()->prepare('SELECT password FROM usuarios WHERE id = ?');
            $stmt->execute([$_SESSION['usuario_id']]);
            $hashActual = $stmt->fetchColumn();

            if (!password_verify($actual, $hashActual)) {
                $error = 'La contraseña actual es incorrecta.';
            } elseif ($nuevoUsuario === '') {
                $error = 'El nombre de usuario no puede estar vacío.';
            } elseif (mb_strlen($nuevoUsuario) < 3 || mb_strlen($nuevoUsuario) > 50) {
                $error = 'El nombre de usuario debe tener entre 3 y 50 caracteres.';
            } elseif (!preg_match('/^[A-Za-z0-9._-]+$/', $nuevoUsuario)) {
                $error = 'Solo se permiten letras, números, punto, guion y guion bajo.';
            } else {
                $stmt = db()->prepare('SELECT id FROM usuarios WHERE usuario = ? AND id != ?');
                $stmt->execute([$nuevoUsuario, $_SESSION['usuario_id']]);
                if ($stmt->fetch()) {
                    $error = 'Ese nombre de usuario ya está en uso.';
                } else {
                    db()->prepare('UPDATE usuarios SET usuario = ? WHERE id = ?')
                        ->execute([$nuevoUsuario, $_SESSION['usuario_id']]);
                    $mensaje = 'Nombre de usuario cambiado correctamente. Usa el nuevo nombre para iniciar sesión.';
                }
            }
        } elseif ($accion === 'cambiar_password') {
            $actual    = $_POST['password_actual'] ?? '';
            $nueva     = $_POST['password_nueva'] ?? '';
            $confirmar = $_POST['password_confirmar'] ?? '';

            $stmt = db()->prepare('SELECT password FROM usuarios WHERE id = ?');
            $stmt->execute([$_SESSION['usuario_id']]);
            $hashActual = $stmt->fetchColumn();

            if (!password_verify($actual, $hashActual)) {
                $error = 'La contraseña actual es incorrecta.';
            } elseif (mb_strlen($nueva) < 8) {
                $error = 'La nueva contraseña debe tener al menos 8 caracteres.';
            } elseif (strlen($nueva) > 72) {
                $error = 'La nueva contraseña no puede superar los 72 caracteres.';
            } elseif ($nueva !== $confirmar) {
                $error = 'Las contraseñas no coinciden.';
            } else {
                $nuevoHash = password_hash($nueva, PASSWORD_DEFAULT);
                db()->prepare('UPDATE usuarios SET password = ? WHERE id = ?')
                    ->execute([$nuevoHash, $_SESSION['usuario_id']]);
                $mensaje = 'Contraseña cambiada correctamente.';
            }
        }
    }
}

require __DIR__ . '/includes/encabezado.php';
?>

<div class="titulo-pagina">
    <h1>Mi cuenta</h1>
</div>

<?php if ($mensaje): ?><div class="alerta alerta--ok"><?= e($mensaje) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alerta alerta--error"><?= e($error) ?></div><?php endif; ?>

<form class="formulario" method="post" action="cambiar-password.php" style="max-width:480px;margin-bottom:32px;">
    <?= csrf_campo() ?>
    <input type="hidden" name="accion" value="cambiar_usuario">

    <h2 style="margin-top:0;">Nombre de usuario</h2>
    <p class="ayuda">Usuario actual: <strong><?= e($usuarioAdmin['usuario'] ?? '') ?></strong></p>

    <div class="campo">
        <label for="usuario_nuevo">Nuevo nombre de usuario</label>
        <input type="text" id="usuario_nuevo" name="usuario_nuevo" required maxlength="50" value="<?= e($usuarioAdmin['usuario'] ?? '') ?>">
        <p class="ayuda">Elige un nombre difícil de adivinar (evita "admin"). Solo letras, números, punto, guion o guion bajo.</p>
    </div>

    <div class="campo">
        <label for="usuario_password">Tu contraseña actual (para confirmar)</label>
        <input type="password" id="usuario_password" name="password_actual" required maxlength="72">
    </div>

    <button class="boton" type="submit">Cambiar usuario</button>
</form>

<form class="formulario" method="post" action="cambiar-password.php" style="max-width:480px;">
    <?= csrf_campo() ?>
    <input type="hidden" name="accion" value="cambiar_password">

    <h2 style="margin-top:0;">Contraseña</h2>

    <div class="campo">
        <label for="password_actual">Contraseña actual</label>
        <input type="password" id="password_actual" name="password_actual" required maxlength="72">
    </div>

    <div class="campo">
        <label for="password_nueva">Nueva contraseña (mínimo 8 caracteres)</label>
        <input type="password" id="password_nueva" name="password_nueva" required maxlength="72">
    </div>

    <div class="campo">
        <label for="password_confirmar">Confirmar nueva contraseña</label>
        <input type="password" id="password_confirmar" name="password_confirmar" required maxlength="72">
    </div>

    <button class="boton" type="submit">Cambiar contraseña</button>
</form>

<?php require __DIR__ . '/includes/pie.php'; ?>
