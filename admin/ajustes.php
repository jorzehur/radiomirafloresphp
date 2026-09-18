<?php
// ============================================================
//  ajustes.php - Configuración general del sitio
// ============================================================
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/includes/admin-funciones.php';
requerir_login();

$paginaActiva = 'ajustes';
$tituloAdmin = 'Ajustes';
$mensaje = '';
$error = '';

// Lista de claves de configuración editables (clave => etiqueta)
$camposTexto = [
    'nombre_sitio'  => 'Nombre del sitio',
    'eslogan'       => 'Eslogan',
    'texto_hero'    => 'Texto de la sección Hero',
    'texto_nosotros'=> 'Texto de "Sobre Nosotros"',
    'dias_conservar' => 'Días que se conservan las noticias (0 = no borrar ninguna)',
    'direccion'     => 'Dirección',
    'telefono'      => 'Teléfono',
    'email'         => 'Correo electrónico',
];

$camposRedes = [
    'facebook'  => 'Facebook (URL)',
    'instagram' => 'Instagram (URL)',
    'youtube'   => 'YouTube (URL)',
    'tiktok'    => 'TikTok (URL)',
    'whatsapp'  => 'WhatsApp (URL)',
];

$camposColores = [
    'color_hero'   => 'Color del Hero',
    'color_acento' => 'Color de acento (botones, enlaces)',
];

// --- Limpiar imagenes que ya no usa nadie ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['limpiar_huerfanas'])) {
    if (!csrf_verificar($_POST['csrf_token'] ?? null)) {
        $error = 'Token inválido. Inténtalo de nuevo.';
    } else {
        $borradas = imagenes_huerfanas(true);
        $mensaje  = $borradas
            ? 'Se han borrado ' . count($borradas) . ' imágenes que no usaba nadie.'
            : 'No había ninguna imagen suelta: todas están en uso.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['limpiar_huerfanas'])) {
    if (!csrf_verificar($_POST['csrf_token'] ?? null)) {
        $error = 'Token inválido. Los cambios no se guardaron.';
    } else {
        // Guardar textos y redes
        $claves = array_merge(
            array_keys($camposTexto),
            array_keys($camposRedes),
            array_keys($camposColores)
        );
        foreach ($claves as $clave) {
            $valor = trim($_POST[$clave] ?? '');
            $stmt = db()->prepare('INSERT INTO configuracion (clave, valor) VALUES (?, ?)
                                   ON DUPLICATE KEY UPDATE valor = VALUES(valor)');
            $stmt->execute([$clave, $valor]);
        }

        // Guardar URL del mapa
        $mapa = trim($_POST['mapa_embed'] ?? '');
        db()->prepare('INSERT INTO configuracion (clave, valor) VALUES (?, ?)
                       ON DUPLICATE KEY UPDATE valor = VALUES(valor)')
            ->execute(['mapa_embed', $mapa]);

        // Subir logo (opcional)
        $logoNuevo = subir_imagen($_FILES['logo'] ?? [], $errImg);
        if ($errImg) {
            $error = $errImg;
        } elseif ($logoNuevo) {
            $viejo = config('logo', '');
            borrar_imagen($viejo ?: null);
            db()->prepare('INSERT INTO configuracion (clave, valor) VALUES (?, ?)
                           ON DUPLICATE KEY UPDATE valor = VALUES(valor)')
                ->execute(['logo', $logoNuevo]);
        }

        // Subir imagen de fondo del Hero (opcional)
        $heroNuevo = subir_imagen($_FILES['imagen_hero'] ?? [], $errImg2);
        if ($errImg2) {
            $error = $error ?: $errImg2;
        } elseif ($heroNuevo) {
            $viejoHero = config('imagen_hero', '');
            borrar_imagen($viejoHero ?: null);
            db()->prepare('INSERT INTO configuracion (clave, valor) VALUES (?, ?)
                           ON DUPLICATE KEY UPDATE valor = VALUES(valor)')
                ->execute(['imagen_hero', $heroNuevo]);
        }

        // Subir imagen de "Sobre Nosotros" (opcional)
        $nosotrosNuevo = subir_imagen($_FILES['imagen_nosotros'] ?? [], $errImg3);
        if ($errImg3) {
            $error = $error ?: $errImg3;
        } elseif ($nosotrosNuevo) {
            $viejoNosotros = config('imagen_nosotros', '');
            borrar_imagen($viejoNosotros ?: null);
            db()->prepare('INSERT INTO configuracion (clave, valor) VALUES (?, ?)
                           ON DUPLICATE KEY UPDATE valor = VALUES(valor)')
                ->execute(['imagen_nosotros', $nosotrosNuevo]);
        }

        if (!$error) {
            $mensaje = 'Ajustes guardados correctamente.';
        }
    }
}

config_cache(true); // refrescar los valores recien guardados antes de pintar el formulario

require __DIR__ . '/includes/encabezado.php';
?>

<div class="titulo-pagina">
    <h1>Ajustes del sitio</h1>
</div>

<?php if ($mensaje): ?><div class="alerta alerta--ok"><?= e($mensaje) ?></div><?php endif; ?>
<?php if ($error): ?><div class="alerta alerta--error"><?= e($error) ?></div><?php endif; ?>

<form class="formulario" method="post" action="ajustes.php" enctype="multipart/form-data">
    <?= csrf_campo() ?>

    <h2 style="margin-top:0;">Información general</h2>
    <?php foreach ($camposTexto as $clave => $etiqueta): ?>
        <div class="campo">
            <label for="<?= e($clave) ?>"><?= e($etiqueta) ?></label>
            <?php if ($clave === 'texto_nosotros'): ?>
                <textarea id="<?= e($clave) ?>" name="<?= e($clave) ?>"><?= e(config($clave)) ?></textarea>
            <?php else: ?>
                <input type="text" id="<?= e($clave) ?>" name="<?= e($clave) ?>" value="<?= e(config($clave)) ?>">
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="campo">
        <label for="logo">Logo</label>
        <?php if (config('logo', '')): ?>
            <p class="ayuda">Logo actual:</p>
            <img src="../uploads/<?= e(config('logo')) ?>" alt="" style="max-height:70px;margin-bottom:8px;">
        <?php endif; ?>
        <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/gif,image/webp">
        <p class="ayuda">Se muestra en el menú (izquierda) y en la sección Hero. Deja vacío para conservar el actual.</p>
    </div>

    <div class="campo">
        <label for="imagen_hero">Imagen de fondo del Hero</label>
        <?php if (config('imagen_hero', '')): ?>
            <p class="ayuda">Imagen actual:</p>
            <img src="../uploads/<?= e(config('imagen_hero')) ?>" alt="" style="max-height:80px;margin-bottom:8px;">
        <?php endif; ?>
        <input type="file" id="imagen_hero" name="imagen_hero" accept="image/jpeg,image/png,image/gif,image/webp">
        <p class="ayuda">Opcional. Se muestra de fondo en la sección roja (Hero), con el texto encima. Deja vacío para usar solo el color rojo.</p>
    </div>

    <div class="campo">
        <label for="imagen_nosotros">Imagen de "Sobre Nosotros"</label>
        <?php if (config('imagen_nosotros', '')): ?>
            <p class="ayuda">Imagen actual:</p>
            <img src="../uploads/<?= e(config('imagen_nosotros')) ?>" alt="" style="max-height:80px;margin-bottom:8px;">
        <?php endif; ?>
        <input type="file" id="imagen_nosotros" name="imagen_nosotros" accept="image/jpeg,image/png,image/gif,image/webp">
        <p class="ayuda">Opcional. Se muestra junto al texto de "Sobre Nosotros" (se convertirá a WebP).</p>
    </div>

    <h2>Colores</h2>
    <?php foreach ($camposColores as $clave => $etiqueta): ?>
        <div class="campo">
            <label for="<?= e($clave) ?>"><?= e($etiqueta) ?></label>
            <input type="color" id="<?= e($clave) ?>" name="<?= e($clave) ?>" value="<?= e(config($clave, '#d32f2f')) ?>">
        </div>
    <?php endforeach; ?>

    <h2>Redes sociales</h2>
    <?php foreach ($camposRedes as $clave => $etiqueta): ?>
        <div class="campo">
            <label for="<?= e($clave) ?>"><?= e($etiqueta) ?></label>
            <input type="url" id="<?= e($clave) ?>" name="<?= e($clave) ?>" value="<?= e(config($clave)) ?>" placeholder="https://...">
        </div>
    <?php endforeach; ?>

    <h2>Mapa de Google Maps</h2>
    <div class="campo">
        <label for="mapa_embed">URL para incrustar el mapa</label>
        <input type="text" id="mapa_embed" name="mapa_embed" value="<?= e(config('mapa_embed')) ?>" placeholder="https://www.google.com/maps/embed?...">
        <p class="ayuda">
            En Google Maps: busca tu ubicación → botón "Compartir" → pestaña "Insertar un mapa" → copia la URL del <code>src</code>.
            Déjalo vacío para ocultar el mapa.
        </p>
    </div>

    <p>
        <button class="boton" type="submit">Guardar ajustes</button>
    </p>
</form>

<?php $huerfanas = imagenes_huerfanas(false); ?>
<div class="formulario">
    <h2 style="margin-top:0;">Imágenes que ya no usa nadie</h2>

    <?php if (!$huerfanas): ?>
        <p class="ayuda">Todo en orden: no hay ninguna imagen suelta en la carpeta <code>uploads</code>.</p>
    <?php else: ?>
        <p class="ayuda">
            Hay <strong><?= count($huerfanas) ?></strong> imagen(es) en <code>uploads</code> que no usa ninguna noticia,
            ningún testimonio, ni el logo, ni el hero, ni "Sobre Nosotros". Puedes borrarlas para liberar espacio.
        </p>
        <ul style="color:#999;font-size:0.85rem;margin:0 0 16px 18px;">
            <?php foreach (array_slice($huerfanas, 0, 20) as $h): ?>
                <li>
                    <?= e($h) ?>
                    <span style="color:#666;">(<?= (int) round(filesize(DIR_UPLOADS . '/' . $h) / 1024) ?> KB)</span>
                </li>
            <?php endforeach; ?>
            <?php if (count($huerfanas) > 20): ?>
                <li>... y <?= count($huerfanas) - 20 ?> más</li>
            <?php endif; ?>
        </ul>
        <form method="post" action="ajustes.php" onsubmit="return confirm('¿Borrar estas <?= count($huerfanas) ?> imágenes? Esta acción no se puede deshacer.');">
            <?= csrf_campo() ?>
            <input type="hidden" name="limpiar_huerfanas" value="1">
            <button class="boton boton--peligro" type="submit">Borrar las <?= count($huerfanas) ?> imágenes sueltas</button>
        </form>
        <p class="ayuda" style="margin-top:12px;">
            Ojo: si alguna de esas imágenes la subiste tú y quieres conservarla, no pulses el botón.
        </p>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/pie.php'; ?>
