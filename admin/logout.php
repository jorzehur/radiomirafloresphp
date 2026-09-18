<?php
// ============================================================
//  logout.php - Cerrar sesion (solo por POST y con token CSRF)
// ============================================================
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verificar($_POST['csrf_token'] ?? null)) {
    cerrar_sesion();
    redirigir('login.php');
}

// Si llega por GET (enlace antiguo o marcador) no se cierra la sesion:
// se devuelve al panel si sigue logueado, y al login si no.
if (usuario_actual() !== null) {
    redirigir('index.php');
}
redirigir('login.php');