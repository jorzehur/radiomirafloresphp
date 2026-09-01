<?php
// ============================================================
//  auth.php - Control de acceso al panel de administracion
// ============================================================

require_once __DIR__ . '/funciones.php';

/**
 * Devuelve el usuario logueado (o null si no hay sesion).
 */
function usuario_actual(): ?array {
    if (empty($_SESSION['usuario_id'])) {
        return null;
    }
    $stmt = db()->prepare('SELECT id, usuario, nombre FROM usuarios WHERE id = ?');
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch();
    return $usuario ?: null;
}

/**
 * Requiere estar logueado; si no, redirige al login.
 */
function requerir_login(): void {
    if (usuario_actual() === null) {
        redirigir('login.php');
    }
}

/**
 * Intenta iniciar sesion con usuario y contrasena.
 * Incluye proteccion contra fuerza bruta: tras 5 intentos fallidos
 * bloquea durante 5 minutos, y aplica un retardo de 1 segundo por fallo.
 */
function intentar_login(string $usuario, string $password): bool {
    $intentos = $_SESSION['login_intentos'] ?? ['n' => 0, 'hasta' => 0];

    // Bloqueado temporalmente por demasiados intentos fallidos
    if (($intentos['hasta'] ?? 0) > time()) {
        return false;
    }

    $stmt = db()->prepare('SELECT id, usuario, password FROM usuarios WHERE usuario = ? LIMIT 1');
    $stmt->execute([$usuario]);
    $fila = $stmt->fetch();

    if ($fila && password_verify($password, $fila['password'])) {
        unset($_SESSION['login_intentos']);
        // Regenerar id de sesion para evitar fijacion de sesion
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = (int)$fila['id'];
        return true;
    }

    // Falló el intento: registrar y aplicar retardo
    $n = (int)($intentos['n'] ?? 0) + 1;
    if ($n >= 5) {
        $intentos = ['n' => 0, 'hasta' => time() + 300]; // bloquear 5 minutos
    } else {
        $intentos['n'] = $n;
    }
    $_SESSION['login_intentos'] = $intentos;

    sleep(1); // ralentiza los intentos automáticos

    return false;
}

/**
 * Cierra la sesion del usuario.
 */
function cerrar_sesion(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
