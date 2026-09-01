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
            // No revelar datos internos al visitante
            die('Error de conexion con la base de datos. '
                . 'Verifica que MySQL este activo y que la base "'
                . DB_NAME . '" exista (importa database.sql).');
        }
    }

    return $pdo;
}
