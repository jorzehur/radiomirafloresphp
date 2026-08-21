<?php
/**
 * Editar item individual
 */

require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

$itemId = intval($_GET['id'] ?? 0);
if (!$itemId) {
    header('Location: index.php');
    exit;
}

$item = db()->fetchOne("SELECT i.*, s.clave FROM items i JOIN secciones s ON i.seccion_id = s.id WHERE i.id = ?", [$itemId]);
if (!$item) {
    header('Location: index.php');
    exit;
}

$content = json_decode($item['contenido'], true) ?: [];
$seccionClave = $item['clave'];

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    
    $titulo = sanitize($_POST['titulo'] ?? '');
    $orden = intval($_POST['orden'] ?? 0);
    $activo = isset($_POST['activo']) ? 1 : 0;
    $imagenUrl = sanitize($_POST['imagen_url'] ?? '');
    
    if ($imagenUrl && !isValidImageUrl($imagenUrl)) {
        $imagenUrl = '';
    }
    
    $newContent = [];
    
    switch ($seccionClave) {
        case 'ranking':
            $newContent = [
                'position' => intval($_POST['position'] ?? 0),
                'song' => sanitize($_POST['song'] ?? ''),
                'artist' => sanitize($_POST['artist'] ?? ''),
                'album' => sanitize($_POST['album'] ?? ''),
                'weeks' => intval($_POST['weeks'] ?? 0),
                'trend' => sanitize($_POST['trend'] ?? 'same')
            ];
            break;
        case 'testimonios':
            $newContent = [
                'name' => sanitize($_POST['name'] ?? ''),
                'role' => sanitize($_POST['role'] ?? ''),
                'quote' => sanitize($_POST['quote'] ?? ''),
                'rating' => intval($_POST['rating'] ?? 5)
            ];
            break;
        case 'noticias':
            $embedUrl = sanitize($_POST['facebook_embed_url'] ?? '');
            if ($embedUrl && !isAllowedEmbedOrigin($embedUrl)) $embedUrl = '';
            $newContent = [
                'title' => sanitize($_POST['title'] ?? ''),
                'excerpt' => sanitize($_POST['excerpt'] ?? ''),
                'content' => sanitize($_POST['content'] ?? ''),
                'author' => sanitize($_POST['author'] ?? 'Radio Miraflores'),
                'facebook_embed_url' => $embedUrl
            ];
            break;
        case 'video_ranking':
            $newContent = [
                'title' => sanitize($_POST['title'] ?? ''),
                'artist' => sanitize($_POST['artist'] ?? ''),
                'youtube_url' => sanitize($_POST['youtube_url'] ?? ''),
                'video_id' => preg_replace('/[^a-zA-Z0-9_-]/', '', sanitize($_POST['video_id'] ?? ''))
            ];
            break;
        case 'nosotros':
            $newContent = [
                'year' => sanitize($_POST['year'] ?? ''),
                'title' => sanitize($_POST['title'] ?? ''),
                'description' => sanitize($_POST['description'] ?? ''),
                'icon' => sanitize($_POST['icon'] ?? 'radio')
            ];
            break;
        case 'redes_sociales':
            $embedUrl = sanitize($_POST['embed_url'] ?? '');
            if ($embedUrl && !isAllowedEmbedOrigin($embedUrl)) $embedUrl = '';
            $newContent = [
                'platform' => sanitize($_POST['platform'] ?? ''),
                'url' => sanitize($_POST['url'] ?? ''),
                'embed_url' => $embedUrl,
                'username' => sanitize($_POST['username'] ?? ''),
                'followers' => sanitize($_POST['followers'] ?? '')
            ];
            break;
    }
    
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = processUpload($_FILES['imagen']);
        if ($uploadResult['success']) {
            $imagenUrl = $uploadResult['url'];
        } else {
            $error = $uploadResult['error'];
        }
    }
    
    if (!$error) {
        db()->update('items', [
            'titulo' => $titulo,
            'contenido' => json_encode($newContent),
            'imagen_url' => $imagenUrl,
            'orden' => $orden,
            'activo' => $activo
        ], 'id = ?', [$itemId]);
        
        clearSectionCache($seccionClave);
        securityLog('Item updated: ' . $itemId);
        $message = 'Item actualizado correctamente';
        
        $item = db()->fetchOne("SELECT i.*, s.clave FROM items i JOIN secciones s ON i.seccion_id = s.id WHERE i.id = ?", [$itemId]);
        $content = json_decode($item['contenido'], true) ?: [];
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Item - Admin</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f5f5f5; }
        .header { background: #667eea; color: #fff; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.5rem; }
        .header a { color: #fff; text-decoration: none; padding: 8px 16px; background: rgba(255,255,255,.2); border-radius: 5px; margin-left: 10px; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .message { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .card { background: #fff; border-radius: 10px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,.1); }
        .card h2 { margin-bottom: 20px; color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; }
        input[type="text"], input[type="number"], input[type="url"], textarea, select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 1rem;
        }
        textarea { min-height: 100px; resize: vertical; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; }
        .checkbox-group input { width: auto; }
        button {
            padding: 12px 24px;
            background: #667eea;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover { background: #5568d3; }
        .btn-secondary { background: #6c757d; margin-left: 10px; }
        .btn-secondary:hover { background: #5a6268; }
        .image-preview { margin-top: 10px; max-width: 200px; }
        .image-preview img { width: 100%; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Editar Item</h1>
        <div>
            <a href="index.php">← Volver</a>
            <a href="../index.php" target="_blank">Ver Sitio</a>
            <a href="logout.php">Cerrar Sesión</a>
        </div>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Editar: <?= htmlspecialchars($item['titulo']) ?></h2>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="form-group">
                    <label>Título</label>
                    <input type="text" name="titulo" value="<?= htmlspecialchars($item['titulo']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Orden</label>
                    <input type="number" name="orden" value="<?= $item['orden'] ?>">
                </div>
                
                <div class="form-group checkbox-group">
                    <input type="checkbox" name="activo" id="activo" <?= $item['activo'] ? 'checked' : '' ?>>
                    <label for="activo" style="margin:0">Activo (visible en el sitio)</label>
                </div>
                
                <hr style="margin: 20px 0; border: none; border-top: 1px solid #e0e0e0;">
                
                <?php if ($seccionClave === 'ranking'): ?>
                    <div class="form-group">
                        <label>Posición</label>
                        <input type="number" name="position" value="<?= htmlspecialchars($content['position'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Canción</label>
                        <input type="text" name="song" value="<?= htmlspecialchars($content['song'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Artista</label>
                        <input type="text" name="artist" value="<?= htmlspecialchars($content['artist'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Álbum</label>
                        <input type="text" name="album" value="<?= htmlspecialchars($content['album'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Semanas en ranking</label>
                        <input type="number" name="weeks" value="<?= htmlspecialchars($content['weeks'] ?? 0) ?>">
                    </div>
                    <div class="form-group">
                        <label>Tendencia</label>
                        <select name="trend">
                            <option value="up" <?= ($content['trend'] ?? '') === 'up' ? 'selected' : '' ?>>↑ Subiendo</option>
                            <option value="same" <?= ($content['trend'] ?? '') === 'same' ? 'selected' : '' ?>>→ Igual</option>
                            <option value="down" <?= ($content['trend'] ?? '') === 'down' ? 'selected' : '' ?>>↓ Bajando</option>
                        </select>
                    </div>
                
                <?php elseif ($seccionClave === 'testimonios'): ?>
                    <div class="form-group">
                        <label>Nombre</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($content['name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Rol / Desde cuándo es oyente</label>
                        <input type="text" name="role" value="<?= htmlspecialchars($content['role'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Testimonio</label>
                        <textarea name="quote"><?= htmlspecialchars($content['quote'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Calificación (1-5)</label>
                        <input type="number" name="rating" min="1" max="5" value="<?= htmlspecialchars($content['rating'] ?? 5) ?>">
                    </div>
                
                <?php elseif ($seccionClave === 'noticias'): ?>
                    <div class="form-group">
                        <label>Título</label>
                        <input type="text" name="title" value="<?= htmlspecialchars($content['title'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Extracto</label>
                        <textarea name="excerpt"><?= htmlspecialchars($content['excerpt'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Contenido completo</label>
                        <textarea name="content" style="min-height:150px"><?= htmlspecialchars($content['content'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Autor</label>
                        <input type="text" name="author" value="<?= htmlspecialchars($content['author'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Facebook Embed URL (opcional)</label>
                        <input type="url" name="facebook_embed_url" value="<?= htmlspecialchars($content['facebook_embed_url'] ?? '') ?>" placeholder="https://www.facebook.com/plugins/post.php?...">
                    </div>
                
                <?php elseif ($seccionClave === 'video_ranking'): ?>
                    <div class="form-group">
                        <label>Título</label>
                        <input type="text" name="title" value="<?= htmlspecialchars($content['title'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Artista</label>
                        <input type="text" name="artist" value="<?= htmlspecialchars($content['artist'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>YouTube URL</label>
                        <input type="url" name="youtube_url" value="<?= htmlspecialchars($content['youtube_url'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=...">
                    </div>
                    <div class="form-group">
                        <label>Video ID (se extrae de la URL)</label>
                        <input type="text" name="video_id" value="<?= htmlspecialchars($content['video_id'] ?? '') ?>" placeholder="Ej: dQw4w9WgXcQ">
                    </div>
                
                <?php elseif ($seccionClave === 'nosotros'): ?>
                    <div class="form-group">
                        <label>Año</label>
                        <input type="text" name="year" value="<?= htmlspecialchars($content['year'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Título</label>
                        <input type="text" name="title" value="<?= htmlspecialchars($content['title'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Descripción</label>
                        <textarea name="description"><?= htmlspecialchars($content['description'] ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Icono</label>
                        <select name="icon">
                            <option value="radio" <?= ($content['icon'] ?? '') === 'radio' ? 'selected' : '' ?>>📻 Radio</option>
                            <option value="mic" <?= ($content['icon'] ?? '') === 'mic' ? 'selected' : '' ?>>🎤 Micrófono</option>
                            <option value="headphones" <?= ($content['icon'] ?? '') === 'headphones' ? 'selected' : '' ?>>🎧 Audífonos</option>
                            <option value="heart" <?= ($content['icon'] ?? '') === 'heart' ? 'selected' : '' ?>>❤️ Corazón</option>
                        </select>
                    </div>
                
                <?php elseif ($seccionClave === 'redes_sociales'): ?>
                    <div class="form-group">
                        <label>Plataforma</label>
                        <select name="platform">
                            <option value="youtube" <?= ($content['platform'] ?? '') === 'youtube' ? 'selected' : '' ?>>YouTube</option>
                            <option value="instagram" <?= ($content['platform'] ?? '') === 'instagram' ? 'selected' : '' ?>>Instagram</option>
                            <option value="twitter" <?= ($content['platform'] ?? '') === 'twitter' ? 'selected' : '' ?>>X (Twitter)</option>
                            <option value="facebook" <?= ($content['platform'] ?? '') === 'facebook' ? 'selected' : '' ?>>Facebook</option>
                            <option value="tiktok" <?= ($content['platform'] ?? '') === 'tiktok' ? 'selected' : '' ?>>TikTok</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>URL del perfil</label>
                        <input type="url" name="url" value="<?= htmlspecialchars($content['url'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($content['username'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Seguidores</label>
                        <input type="text" name="followers" value="<?= htmlspecialchars($content['followers'] ?? '') ?>" placeholder="Ej: 10K seguidores">
                    </div>
                    <div class="form-group">
                        <label>Embed URL (opcional)</label>
                        <textarea name="embed_url" placeholder="URL del embed o HTML completo"><?= htmlspecialchars($content['embed_url'] ?? '') ?></textarea>
                    </div>
                <?php endif; ?>
                
                <hr style="margin: 20px 0; border: none; border-top: 1px solid #e0e0e0;">
                
                <div class="form-group">
                    <label>Imagen actual</label>
                    <?php if ($item['imagen_url']): ?>
                    <div class="image-preview">
                        <img src="<?= htmlspecialchars($item['imagen_url']) ?>" alt="Imagen actual">
                    </div>
                    <?php else: ?>
                    <p style="color:#999">Sin imagen</p>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label>Cambiar imagen (opcional)</label>
                    <input type="file" name="imagen" accept="image/*">
                </div>
                
                <div class="form-group">
                    <label>O URL de imagen externa</label>
                    <input type="url" name="imagen_url" value="<?= htmlspecialchars($item['imagen_url'] ?? '') ?>" placeholder="https://...">
                </div>
                
                <button type="submit">Guardar Cambios</button>
                <a href="index.php" class="btn-secondary" style="text-decoration:none;display:inline-block">Cancelar</a>
            </form>
        </div>
    </div>
</body>
</html>
