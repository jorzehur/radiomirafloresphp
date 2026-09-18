<?php
// ============================================================
//  login.php - Inicio de sesión del panel de administración
// ============================================================
require_once __DIR__ . '/../includes/auth.php';

$error = '';

// Si ya está logueado, ir al panel
if (usuario_actual() !== null) {
    redirigir('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verificar($_POST['csrf_token'] ?? null)) {
        $error = 'Sesión expirada. Inténtalo de nuevo.';
    } else {
        $usuario  = trim((string)($_POST['usuario'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($usuario === '' || $password === '') {
            $error = 'Ingresa usuario y contraseña.';
        } elseif (!empty($_SESSION['login_intentos']['hasta']) && $_SESSION['login_intentos']['hasta'] > time()) {
            $error = 'Demasiados intentos fallidos. Espera unos minutos e inténtalo de nuevo.';
        } elseif (intentar_login($usuario, $password)) {
            redirigir('index.php');
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - Administración</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
<div class="login">
    <form class="login__caja" method="post" action="login.php">
        <h1>Panel de administración</h1>

        <?php if ($error): ?>
            <div class="alerta alerta--error"><?= e($error) ?></div>
        <?php endif; ?>

        <?= csrf_campo() ?>
        <div class="campo">
            <label for="usuario">Usuario</label>
            <input type="text" id="usuario" name="usuario" required autofocus>
        </div>
        <div class="campo">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button class="boton" type="submit" style="width:100%;">Entrar</button>
    </form>
</div>
</body>
</html>
