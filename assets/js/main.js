// ============================================================
//  main.js - Interactividad mínima (menú móvil)
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    var boton = document.getElementById('menuBoton');
    var menu = document.getElementById('menu');

    if (!boton || !menu) return;

    boton.addEventListener('click', function () {
        var abierto = menu.classList.toggle('abierto');
        boton.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    });

    // Cerrar el menú al hacer clic en un enlace
    menu.querySelectorAll('a').forEach(function (enlace) {
        enlace.addEventListener('click', function () {
            menu.classList.remove('abierto');
            boton.setAttribute('aria-expanded', 'false');
        });
    });
});
