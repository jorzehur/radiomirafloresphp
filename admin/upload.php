<?php
/**
 * Upload de imágenes - Admin
 */

require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    securityLog('CSRF validation failed on upload');
    echo json_encode(['error' => 'Token de seguridad inválido']);
    exit;
}

if (!isset($_FILES['file'])) {
    echo json_encode(['error' => 'No se recibió archivo']);
    exit;
}

$result = processUpload($_FILES['file']);

if (isset($result['success']) && $result['success']) {
    securityLog('File uploaded: ' . ($result['filename'] ?? 'unknown'));
}

echo json_encode($result);
