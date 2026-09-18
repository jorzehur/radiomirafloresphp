<?php
// ============================================================
//  reset-password.php - Cambiar la contrasena del panel desde la consola
//  Solo funciona desde la consola, nunca desde el navegador.
//
//  Uso:  php reset-password.php NUEVA_CONTRASENA [usuario]
//  Ej.:  C:\xampp\php\php.exe reset-password.php MiClaveSegura123 admin
// ============================================================

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este archivo solo se puede ejecutar desde la consola.');
}

require_once __DIR__ . '/includes/funciones.php';

$nueva   = $argv[1] ?? '';
$usuario = $argv[2] ?? 'admin';

if ($nueva === '') {
    echo "Uso: php reset-password.php NUEVA_CONTRASENA [usuario]\n";
    echo "Ejemplo: C:\\xampp\\php\\php.exe reset-password.php MiClaveSegura123 admin\n\n";
    echo "Usuarios del panel ahora mismo:\n";
    foreach (db()->query('SELECT usuario, nombre FROM usuarios ORDER BY id') as $u) {
        echo "  - {$u['usuario']}  ({$u['nombre']})\n";
    }
    exit(1);
}

if (strlen($nueva) < 8) {
    exit("La contrasena debe tener al menos 8 caracteres.\n");
}

$stmt = db()->prepare('SELECT id, usuario FROM usuarios WHERE usuario = ? LIMIT 1');
$stmt->execute([$usuario]);
$u = $stmt->fetch();

if (!$u) {
    exit("No existe el usuario \"" . $usuario . "\". Ejecuta el script sin argumentos para ver la lista.\n");
}

db()->prepare('UPDATE usuarios SET password = ? WHERE id = ?')
    ->execute([password_hash($nueva, PASSWORD_DEFAULT), $u['id']]);

// Se quita tambien el bloqueo por intentos fallidos, por si estaba bloqueado
db()->prepare('DELETE FROM intentos_login WHERE usuario = ?')->execute([$u['usuario']]);

echo "Contrasena cambiada para el usuario \"" . $u['usuario'] . "\".\n";
echo "Tambien se ha quitado el bloqueo por intentos fallidos.\n";
echo "Entra al panel y, si quieres, cambiala otra vez desde \"Mi cuenta\".\n";