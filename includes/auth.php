<?php
// ============================================================
//  auth.php - Control de acceso al panel de administracion
// ============================================================

require_once __DIR__ . '/funciones.php';

// El panel de administracion si necesita sesion
iniciar_sesion();

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

    // Bloqueado temporalmente por demasiados intentos fallidos (sesion o base de datos)
    if (($intentos['hasta'] ?? 0) > time() || login_espera($usuario) > 0) {
        return false;
    }

    $stmt = db()->prepare('SELECT id, usuario, password FROM usuarios WHERE usuario = ? LIMIT 1');
    $stmt->execute([$usuario]);
    $fila = $stmt->fetch();

    if ($fila && password_verify($password, $fila['password'])) {
        unset($_SESSION['login_intentos']);
        login_limpiar($usuario);
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

    // Registro en el servidor por IP: el contador de sesion se evita
    // borrando la cookie, este no.
    login_registrar_fallo($usuario);

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

/**
 * IP del visitante (recortada al tamano del campo).
 */
function login_ip(): string {
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

/**
 * Segundos de bloqueo que le quedan a esta IP/usuario (0 = sin bloqueo).
 * Usa la tabla intentos_login; si no existe, cae al contador de sesion.
 */
function login_espera(string $usuario): int {
    $espera = 0;
    try {
        $stmt = db()->prepare('SELECT bloqueado_hasta FROM intentos_login
                               WHERE ip = ? AND usuario = ? AND bloqueado_hasta > NOW() LIMIT 1');
        $stmt->execute([login_ip(), mb_substr($usuario, 0, 50)]);
        $hasta = $stmt->fetchColumn();
        if ($hasta) {
            $espera = max(0, strtotime($hasta) - time());
        }

        // Si el bloqueo ya caduco, se da otra tanda de intentos. Sin esto, cada
        // fallo posterior volvia a bloquear 5 minutos y el bloqueo no acababa nunca.
        $stmt = db()->prepare('UPDATE intentos_login SET intentos = 0, bloqueado_hasta = NULL
                               WHERE ip = ? AND usuario = ? AND bloqueado_hasta IS NOT NULL AND bloqueado_hasta <= NOW()');
        $stmt->execute([login_ip(), mb_substr($usuario, 0, 50)]);
        // Ataque con muchos usuarios distintos desde la misma IP
        $stmt = db()->prepare('SELECT COUNT(*) FROM intentos_login
                               WHERE ip = ? AND actualizado_en > DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
        $stmt->execute([login_ip()]);
        if ((int) $stmt->fetchColumn() > 25) {
            $espera = max($espera, 300);
        }
    } catch (PDOException $e) {
        $hasta = $_SESSION['login_intentos']['hasta'] ?? 0;
        if ($hasta > time()) { $espera = max($espera, $hasta - time()); }
    }
    return $espera;
}

/**
 * Registra un intento fallido (sesion + base de datos) y bloquea a los 5.
 */
function login_registrar_fallo(string $usuario): void {
    $n = (int) ($_SESSION['login_intentos']['n'] ?? 0) + 1;
    $_SESSION['login_intentos'] = ($n >= 5)
        ? ['n' => 0, 'hasta' => time() + 300]
        : ['n' => $n, 'hasta' => 0];

    try {
        $stmt = db()->prepare('INSERT INTO intentos_login (ip, usuario, intentos, bloqueado_hasta)
                               VALUES (?, ?, 1, NULL)
                               ON DUPLICATE KEY UPDATE
                                   bloqueado_hasta = IF(intentos + 1 >= 5, DATE_ADD(NOW(), INTERVAL 5 MINUTE), bloqueado_hasta),
                                   intentos = intentos + 1');
        $stmt->execute([login_ip(), mb_substr($usuario, 0, 50)]);
        if (random_int(1, 20) === 1) {
            db()->exec('DELETE FROM intentos_login WHERE actualizado_en < DATE_SUB(NOW(), INTERVAL 2 DAY)');
        }
    } catch (PDOException $e) {
        error_log('No se pudo registrar el intento fallido: ' . $e->getMessage());
    }
}

/**
 * Limpia los intentos al iniciar sesion correctamente.
 */
function login_limpiar(string $usuario): void {
    unset($_SESSION['login_intentos']);
    try {
        db()->prepare('DELETE FROM intentos_login WHERE ip = ? AND usuario = ?')
            ->execute([login_ip(), mb_substr($usuario, 0, 50)]);
    } catch (PDOException $e) {
        // Si la tabla no existe, no hay nada que limpiar
    }
}
