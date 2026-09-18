// ============================================================
//  main.js - Interactividad minima del menu movil
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    var boton = document.getElementById('menuBoton');
    var menu = document.getElementById('menu');

    if (!boton || !menu) return;

    if (!boton.hasAttribute('aria-controls')) {
        boton.setAttribute('aria-controls', 'menu');
    }

    function abrir() {
        menu.classList.add('abierto');
        boton.setAttribute('aria-expanded', 'true');
        boton.setAttribute('aria-label', 'Cerrar men\u00fa');
    }

    function cerrar() {
        menu.classList.remove('abierto');
        boton.setAttribute('aria-expanded', 'false');
        boton.setAttribute('aria-label', 'Abrir men\u00fa');
    }

    boton.addEventListener('click', function (evento) {
        evento.stopPropagation();
        if (menu.classList.contains('abierto')) { cerrar(); } else { abrir(); }
    });

    // Al elegir un enlace, cerrar el menu
    var enlaces = menu.querySelectorAll('a');
    for (var i = 0; i < enlaces.length; i++) {
        enlaces[i].addEventListener('click', cerrar);
    }

    // Cerrar con Escape y devolver el foco al boton
    document.addEventListener('keydown', function (evento) {
        if ((evento.key === 'Escape' || evento.key === 'Esc') && menu.classList.contains('abierto')) {
            cerrar();
            boton.focus();
        }
    });

    // Cerrar al hacer clic fuera del menu
    document.addEventListener('click', function (evento) {
        if (!menu.classList.contains('abierto')) return;
        if (menu.contains(evento.target) || boton.contains(evento.target)) return;
        cerrar();
    });
});
// ============================================================
//  Videos: cargar el reproductor de YouTube solo al pulsar play
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    var fachadas = document.querySelectorAll('.video-fachada');

    for (var i = 0; i < fachadas.length; i++) {
        fachadas[i].addEventListener('click', function () {
            var caja = this.parentNode;
            var iframe = document.createElement('iframe');

            iframe.setAttribute('src', this.getAttribute('data-embed') + '?autoplay=1&rel=0');
            iframe.setAttribute('title', this.getAttribute('data-titulo') || 'Video');
            iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
            iframe.setAttribute('allowfullscreen', '');

            caja.innerHTML = '';
            caja.appendChild(iframe);
        }, { once: true });
    }
});