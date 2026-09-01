<?php
// ============================================================
//  admin-funciones.php - Funciones exclusivas del admin
// ============================================================

/**
 * Sube una imagen validada a /uploads y devuelve el nombre del archivo.
 * Devuelve null si no se subió nada o hubo error (guarda el error en $mensajeError).
 */
function subir_imagen(array $archivo, ?string &$mensajeError = null): ?string {
    // ¿No se eligió ningún archivo?
    if (empty($archivo['name']) || $archivo['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    // ¿Error al subir?
    if ($archivo['error'] !== UPLOAD_ERR_OK) {
        $mensajeError = 'Error al subir el archivo (código ' . $archivo['error'] . ').';
        return null;
    }

    // Limite de tamaño: 5 MB
    if ($archivo['size'] > 5 * 1024 * 1024) {
        $mensajeError = 'La imagen supera los 5 MB permitidos.';
        return null;
    }

    // Validar tipo real con getimagesize (más seguro que la extensión)
    $info = @getimagesize($archivo['tmp_name']);
    if ($info === false) {
        $mensajeError = 'El archivo no es una imagen válida.';
        return null;
    }

    $permitidos = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($info['mime'], $permitidos, true)) {
        $mensajeError = 'Solo se permiten imágenes JPG, PNG, GIF o WEBP.';
        return null;
    }

    // Generar nombre único y seguro
    $nombre = bin2hex(random_bytes(8)) . '.webp';

    if (!is_dir(DIR_UPLOADS)) {
        mkdir(DIR_UPLOADS, 0755, true);
    }

    // Re-codificar la imagen con GD: SIEMPRE a WebP (más ligero) y sin
    // metadatos ni contenido embebido (defensa en profundidad).
    $img = false;
    switch ($info['mime']) {
        case 'image/jpeg': $img = @imagecreatefromjpeg($archivo['tmp_name']); break;
        case 'image/png':  $img = @imagecreatefrompng($archivo['tmp_name']);  break;
        case 'image/gif':  $img = @imagecreatefromgif($archivo['tmp_name']);  break;
        case 'image/webp': $img = @imagecreatefromwebp($archivo['tmp_name']); break;
    }

    if ($img === false) {
        $mensajeError = 'No se pudo procesar la imagen. Prueba con otro archivo.';
        return null;
    }

    imagepalettetotruecolor($img);
    imagealphablending($img, false);
    imagesavealpha($img, true);

    $destino = DIR_UPLOADS . '/' . $nombre;
    $ok = imagewebp($img, $destino, 82);
    imagedestroy($img);

    if (!$ok) {
        $mensajeError = 'No se pudo guardar la imagen en el servidor.';
        return null;
    }

    return $nombre;
}

/**
 * Elimina una imagen del servidor (si existe).
 */
function borrar_imagen(?string $nombre): void {
    if (!$nombre) {
        return;
    }
    $ruta = DIR_UPLOADS . '/' . $nombre;
    if (is_file($ruta) && strpos($nombre, '..') === false) {
        @unlink($ruta);
    }
}
