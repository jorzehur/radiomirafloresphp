<?php
// ============================================================
//  db.php - Conexion a la base de datos con PDO
// ============================================================

require_once __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        } catch (PDOException $e) {
            // El detalle va al log del servidor, al visitante no se le revela nada
            error_log('Radio Miraflores - error de conexion a la base de datos: ' . $e->getMessage());
            http_response_code(503);
            die("El sitio no est\u{00E1} disponible en este momento. Int\u{00E9}ntalo de nuevo m\u{00E1}s tarde.");
        }
    }

    return $pdo;
}
