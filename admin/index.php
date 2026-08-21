<?php
/**
 * Panel admin principal
 */

require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

$secciones = db()->fetchAll("SELECT * FROM secciones ORDER BY id");
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    
    if (isset($_POST['update_section'])) {
        $sectionId = intval($_POST['section_id']);
        $titulo = sanitize($_POST['titulo']);
        $contenidoRaw = $_POST['contenido'] ?? [];
        
        $contenidoSanitizado = [];
        foreach ($contenidoRaw as $key => $value) {
            $contenidoSanitizado[sanitize($key)] = sanitize($value);
        }
        $contenido = json_encode($contenidoSanitizado);
        
        db()->update('secciones', [
            'titulo' => $titulo,
            'contenido' => $contenido
        ], 'id = ?', [$sectionId]);
        
        $seccion = db()->fetchOne("SELECT clave FROM secciones WHERE id = ?", [$sectionId]);
        if ($seccion) {
            clearSectionCache($seccion['clave']);
        }
        
        securityLog('Section updated: ' . $sectionId);
        $message = 'Sección actualizada correctamente';
        $secciones = db()->fetchAll("SELECT * FROM secciones ORDER BY id");
    }
    
    if (isset($_POST['delete_item'])) {
        $itemId = intval($_POST['item_id']);
        $item = db()->fetchOne("SELECT seccion_id FROM items WHERE id = ?", [$itemId]);
        
        db()->delete('items', 'id = ?', [$itemId]);
        cache_clear();
        
        if ($item) {
            $sec = db()->fetchOne("SELECT clave FROM secciones WHERE id = ?", [$item['seccion_id']]);
            if ($sec) clearSectionCache($sec['clave']);
        }
        
        securityLog('Item deleted: ' . $itemId);
        $message = 'Item eliminado correctamente';
        $secciones = db()->fetchAll("SELECT * FROM secciones ORDER BY id");
    }
    
    if (isset($_POST['toggle_item'])) {
        $itemId = intval($_POST['item_id']);
        $item = db()->fetchOne("SELECT activo, seccion_id FROM items WHERE id = ?", [$itemId]);
        if ($item) {
            db()->update('items', ['activo' => $item['activo'] ? 0 : 1], 'id = ?', [$itemId]);
            cache_clear();
            
            $sec = db()->fetchOne("SELECT clave FROM secciones WHERE id = ?", [$item['seccion_id']]);
            if ($sec) clearSectionCache($sec['clave']);
            
            securityLog('Item toggled: ' . $itemId);
            $message = 'Estado del item actualizado';
            $secciones = db()->fetchAll("SELECT * FROM secciones ORDER BY id");
        }
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Radio Miraflores</title>
    <meta name="robots" content="noindex, nofollow">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f5f5f5; }
        .header { background: #667eea; color: #fff; padding: 20px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 1.5rem; }
        .header a { color: #fff; text-decoration: none; padding: 8px 16px; background: rgba(255,255,255,.2); border-radius: 5px; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .message { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .section { background: #fff; border-radius: 10px; padding: 25px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,.1); }
        .section h2 { margin-bottom: 20px; color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #555; }
        input[type="text"], textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 1rem;
        }
        textarea { min-height: 100px; resize: vertical; }
        button {
            padding: 10px 20px;
            background: #667eea;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover { background: #5568d3; }
        .items-list { margin-top: 20px; }
        .item { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        .item-info { flex: 1; }
        .item-actions { display: flex; gap: 10px; }
        .btn-delete { background: #dc3545; padding: 5px 10px; font-size: .9rem; }
        .btn-delete:hover { background: #c82333; }
        .btn-edit { background: #28a745; padding: 5px 10px; font-size: .9rem; text-decoration: none; color: #fff; border-radius: 5px; display: inline-block; }
        .btn-edit:hover { background: #218838; }
        .btn-toggle { background: #ffc107; padding: 5px 10px; font-size: .9rem; color: #333; }
        .btn-toggle:hover { background: #e0a800; }
        .btn-add { background: #28a745; padding: 8px 16px; font-size: .9rem; text-decoration: none; color: #fff; border-radius: 5px; display: inline-block; }
        .btn-add:hover { background: #218838; }
        .json-editor { font-family: monospace; font-size: .9rem; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Panel Admin</h1>
        <div>
            <a href="../index.php" target="_blank">Ver Sitio</a>
            <a href="logout.php">Cerrar Sesión</a>
        </div>
    </div>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if (isset($_GET['created'])): ?>
            <div class="message">Item creado correctamente</div>
        <?php endif; ?>
        
        <?php foreach ($secciones as $seccion): 
            $contenido = json_decode($seccion['contenido'], true);
            if (!is_array($contenido)) $contenido = [];
            $items = db()->fetchAll("SELECT * FROM items WHERE seccion_id = ? ORDER BY orden", [$seccion['id']]);
        ?>
        <div class="section">
            <h2><?= htmlspecialchars($seccion['titulo']) ?> <small style="color:#999">(<?= htmlspecialchars($seccion['clave']) ?>)</small></h2>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="section_id" value="<?= intval($seccion['id']) ?>">
                
                <div class="form-group">
                    <label>Título</label>
                    <input type="text" name="titulo" value="<?= htmlspecialchars($seccion['titulo']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Contenido (JSON)</label>
                    <textarea name="contenido[title]" class="json-editor"><?= htmlspecialchars($contenido['title'] ?? '') ?></textarea>
                </div>
                
                <?php if (isset($contenido['subtitle'])): ?>
                <div class="form-group">
                    <label>Subtítulo</label>
                    <input type="text" name="contenido[subtitle]" value="<?= htmlspecialchars($contenido['subtitle']) ?>">
                </div>
                <?php endif; ?>
                
                <?php if (isset($contenido['description'])): ?>
                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="contenido[description]"><?= htmlspecialchars($contenido['description']) ?></textarea>
                </div>
                <?php endif; ?>
                
                <button type="submit" name="update_section">Guardar Cambios</button>
            </form>
            
            <div class="items-list">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:30px;margin-bottom:15px;">
                    <h3>Items (<?= count($items) ?>)</h3>
                    <a href="item-create.php?seccion_id=<?= intval($seccion['id']) ?>" class="btn-add">+ Agregar Item</a>
                </div>
                <?php if ($items): ?>
                <?php foreach ($items as $item): ?>
                <div class="item" style="<?= !$item['activo'] ? 'opacity:.6' : '' ?>">
                    <div class="item-info">
                        <strong><?= htmlspecialchars($item['titulo']) ?></strong>
                        <br><small>Orden: <?= intval($item['orden']) ?> | <?= $item['activo'] ? 'Activo' : 'Inactivo' ?></small>
                    </div>
                    <div class="item-actions">
                        <a href="item-edit.php?id=<?= intval($item['id']) ?>" class="btn-edit">Editar</a>
                        <form method="POST" style="display:inline">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="item_id" value="<?= intval($item['id']) ?>">
                            <button type="submit" name="toggle_item" class="btn-toggle"><?= $item['activo'] ? 'Desactivar' : 'Activar' ?></button>
                        </form>
                        <form method="POST" style="display:inline" onsubmit="return confirm('¿Eliminar este item?')">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="item_id" value="<?= intval($item['id']) ?>">
                            <button type="submit" name="delete_item" class="btn-delete">Eliminar</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <p style="color:#999;text-align:center;padding:20px">No hay items en esta sección</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
