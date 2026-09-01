<?php
// ============================================================
//  footer.php - Pie de pagina comun de la parte publica
// ============================================================
$nombre_sitio = config('nombre_sitio', 'Radio Miraflores');
?>
<footer class="pie">
    <div class="contenedor">
        <p>&copy; <?= date('Y') ?> <?= e($nombre_sitio) ?>. Todos los derechos reservados.</p>
    </div>
</footer>

<script src="assets/js/main.js?v=<?= @filemtime(__DIR__ . '/../assets/js/main.js') ?>"></script>
<?php if (!empty($GLOBALS['cargar_fb_sdk'])): ?>
<script async defer crossorigin="anonymous" src="https://connect.facebook.net/es_LA/sdk.js#xfbml=1&version=v18.0"></script>
<?php endif; ?>
</body>
</html>
