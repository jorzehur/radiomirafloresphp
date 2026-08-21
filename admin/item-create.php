<?php
/**
 * Crear nuevo item
 */

require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

$seccionId = intval($_GET['seccion_id'] ?? 0);
if (!$seccionId) {
    header('Location: index.php');
    exit;
}

$seccion = db()->fetchOne("SELECT * FROM secciones WHERE id = ?", [$seccionId]);
if (!$seccion) {
    header('Location: index.php');
    exit;
}

$seccionClave = $seccion['clave'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    
    $titulo = sanitize($_POST['titulo'] ?? '');
    $orden = intval($_POST['orden'] ?? 0);
    $activo = isset($_POST['activo']) ? 1 : 0;
    $imagenUrl = '';
    
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
    
    if (!$error && $titulo) {
        db()->insert('items', [
            'seccion_id' => $seccionId,
            'tipo' => $seccionClave,
            'titulo' => $titulo,
            'contenido' => json_encode($newContent),
            'imagen_url' => $imagenUrl,
            'orden' => $orden,
            'activo' => $activo
        ]);
        
        clearSectionCache($seccionClave);
        securityLog('Item created in section: ' . $seccionClave);
        header('Location: index.php?created=1');
        exit;
    } else {
        $error = $error ?: 'El título es obligatorio';
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Item - Admin</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f5f5f5; }
        .header { background: #667eea; color: #fff; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.5rem; }
        .header a { color: #fff; text-decoration: none; padding: 8px 16px; background: rgba(255,255,255,.2); border-radius: 5px; margin-left: 10px; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .card { background: #fff; border-radius: 10px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,.1); }
        .card h2 { margin-bottom: 20px; color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; }
        input[type="text"], input[type="number"], input[type="url"], textarea, select {
            width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 5px; font-size: 1rem;
        }
        textarea { min-height: 100px; resize: vertical; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; }
        .checkbox-group input { width: auto; }
        button { padding: 12px 24px; background: #28a745; color: #fff; border: none; border-radius: 5px; font-size: 1rem; cursor: pointer; margin-top: 10px; }
        button:hover { background: #218838; }
        .btn-secondary { background: #6c757d; margin-left: 10px; }
        .btn-secondary:hover { background: #5a6268; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Nuevo Item</h1>
        <div>
            <a href="index.php">← Volver</a>
            <a href="../index.php" target="_blank">Ver Sitio</a>
            <a href="logout.php">Cerrar Sesión</a>
        </div>
    </div>
    
    <div class="container">
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Nuevo item en: <?= htmlspecialchars($seccion['titulo']) ?></h2>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="form-group">
                    <label>Título *</label>
                    <input type="text" name="titulo" required>
                </div>
                
                <div class="form-group">
                    <label>Orden</label>
                    <input type="number" name="orden" value="0">
                </div>
                
                <div class="form-group checkbox-group">
                    <input type="checkbox" name="activo" id="activo" checked>
                    <label for="activo" style="margin:0">Activo (visible en el sitio)</label>
                </div>
                
                <hr style="margin: 20px 0; border: none; border-top: 1px solid #e0e0e0;">
                
                <?php if ($seccionClave === 'ranking'): ?>
                    <div class="form-group"><label>Posición</label><input type="number" name="position" value="0"></div>
                    <div class="form-group"><label>Canción</label><input type="text" name="song"></div>
                    <div class="form-group"><label>Artista</label><input type="text" name="artist"></div>
                    <div class="form-group"><label>Álbum</label><input type="text" name="album"></div>
                    <div class="form-group"><label>Semanas en ranking</label><input type="number" name="weeks" value="0"></div>
                    <div class="form-group">
                        <label>Tendencia</label>
                        <select name="trend">
                            <option value="up">↑ Subiendo</option>
                            <option value="same" selected>→ Igual</option>
                            <option value="down">↓ Bajando</option>
                        </select>
                    </div>
                <?php elseif ($seccionClave === 'testimonios'): ?>
                    <div class="form-group"><label>Nombre</label><input type="text" name="name"></div>
                    <div class="form-group"><label>Rol / Desde cuándo es oyente</label><input type="text" name="role"></div>
                    <div class="form-group"><label>Testimonio</label><textarea name="quote"></textarea></div>
                    <div class="form-group"><label>Calificación (1-5)</label><input type="number" name="rating" min="1" max="5" value="5"></div>
                <?php elseif ($seccionClave === 'noticias'): ?>
                    <div class="form-group"><label>Título</label><input type="text" name="title"></div>
                    <div class="form-group"><label>Extracto</label><textarea name="excerpt"></textarea></div>
                    <div class="form-group"><label>Contenido completo</label><textarea name="content" style="min-height:150px"></textarea></div>
                    <div class="form-group"><label>Autor</label><input type="text" name="author" value="Radio Miraflores"></div>
                    <div class="form-group"><label>Facebook Embed URL (opcional)</label><input type="url" name="facebook_embed_url"></div>
                <?php elseif ($seccionClave === 'video_ranking'): ?>
                    <div class="form-group"><label>Título</label><input type="text" name="title"></div>
                    <div class="form-group"><label>Artista</label><input type="text" name="artist"></div>
                    <div class="form-group"><label>YouTube URL</label><input type="url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=..."></div>
                    <div class="form-group"><label>Video ID</label><input type="text" name="video_id" placeholder="Ej: dQw4w9WgXcQ"></div>
                <?php elseif ($seccionClave === 'nosotros'): ?>
                    <div class="form-group"><label>Año</label><input type="text" name="year"></div>
                    <div class="form-group"><label>Título</label><input type="text" name="title"></div>
                    <div class="form-group"><label>Descripción</label><textarea name="description"></textarea></div>
                    <div class="form-group">
                        <label>Icono</label>
                        <select name="icon">
                            <option value="radio">📻 Radio</option>
                            <option value="mic">🎤 Micrófono</option>
                            <option value="headphones">🎧 Audífonos</option>
                            <option value="heart">❤️ Corazón</option>
                        </select>
                    </div>
                <?php elseif ($seccionClave === 'redes_sociales'): ?>
                    <div class="form-group">
                        <label>Plataforma</label>
                        <select name="platform">
                            <option value="youtube">YouTube</option>
                            <option value="instagram">Instagram</option>
                            <option value="twitter">X (Twitter)</option>
                            <option value="facebook">Facebook</option>
                            <option value="tiktok">TikTok</option>
                        </select>
                    </div>
                    <div class="form-group"><label>URL del perfil</label><input type="url" name="url"></div>
                    <div class="form-group"><label>Username</label><input type="text" name="username"></div>
                    <div class="form-group"><label>Seguidores</label><input type="text" name="followers" placeholder="Ej: 10K seguidores"></div>
                    <div class="form-group"><label>Embed URL (opcional)</label><textarea name="embed_url"></textarea></div>
                <?php endif; ?>
                
                <hr style="margin: 20px 0; border: none; border-top: 1px solid #e0e0e0;">
                
                <div class="form-group">
                    <label>Imagen (opcional)</label>
                    <input type="file" name="imagen" accept="image/*">
                </div>
                
                <button type="submit">Crear Item</button>
                <a href="index.php" class="btn-secondary" style="text-decoration:none;display:inline-block">Cancelar</a>
            </form>
        </div>
    </div>
</body>
</html>
