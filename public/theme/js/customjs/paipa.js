/* Interfaz del sistema: menú horizontal, menú de cuenta y franja del título.
   Sin dependencias (JavaScript puro); no toca la lógica de las pantallas. */
(function () {
    'use strict';

    var $ = function (sel, raiz) { return (raiz || document).querySelector(sel); };
    var $$ = function (sel, raiz) { return Array.prototype.slice.call((raiz || document).querySelectorAll(sel)); };

    // Pantalla ancha con puntero que puede pasar por encima (escritorio): los menús se abren al pasar el ratón.
    var consulta = window.matchMedia ? window.matchMedia('(min-width: 992px) and (hover: hover)') : null;
    function conHover() { return !!(consulta && consulta.matches); }

    // ---------- Menús desplegables (menú principal y cuenta) ----------
    function abrir(caja) {
        cerrarTodos(caja);
        caja.classList.add('abierto');
        var boton = $('[data-paipa-toggle]', caja);
        if (boton) { boton.setAttribute('aria-expanded', 'true'); }
    }
    function cerrar(caja) {
        caja.classList.remove('abierto');
        var boton = $('[data-paipa-toggle]', caja);
        if (boton) { boton.setAttribute('aria-expanded', 'false'); }
    }
    function cerrarTodos(excepto) {
        $$('.paipa-desplegable.abierto').forEach(function (c) { if (c !== excepto) { cerrar(c); } });
    }

    $$('.paipa-desplegable').forEach(function (caja) {
        var boton = $('[data-paipa-toggle]', caja);
        if (!boton) { return; }
        boton.addEventListener('click', function (ev) {
            ev.stopPropagation();
            // Con ratón en pantalla ancha ya se abrió al pasar por encima: el clic lo deja abierto.
            if (caja.classList.contains('abierto') && !(conHover() && caja.classList.contains('menu-padre'))) {
                cerrar(caja);
            } else {
                abrir(caja);
            }
        });
        if (caja.classList.contains('menu-padre')) {
            var espera = null;
            caja.addEventListener('mouseenter', function () {
                if (!conHover()) { return; }
                clearTimeout(espera);
                abrir(caja);
            });
            caja.addEventListener('mouseleave', function () {
                if (!conHover()) { return; }
                clearTimeout(espera);
                espera = setTimeout(function () { cerrar(caja); }, 160);
            });
        }
    });
    document.addEventListener('click', function (ev) {
        if (!ev.target.closest || !ev.target.closest('.paipa-desplegable')) { cerrarTodos(null); }
    });
    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' || ev.keyCode === 27) {
            var abierta = $('.paipa-desplegable.abierto');
            cerrarTodos(null);
            if (abierta) { var b = $('[data-paipa-toggle]', abierta); if (b) { b.focus(); } }
            cerrarMenuMovil();
        }
    });

    // ---------- Menú en celular (hamburguesa) ----------
    var menu = $('#menu-principal');
    var hamburguesa = menu ? $('.menu-hamburguesa', menu) : null;
    function cerrarMenuMovil() {
        if (menu && menu.classList.contains('menu-abierto')) {
            menu.classList.remove('menu-abierto');
            if (hamburguesa) { hamburguesa.setAttribute('aria-expanded', 'false'); }
        }
    }
    if (hamburguesa) {
        hamburguesa.addEventListener('click', function (ev) {
            ev.stopPropagation();
            var abierto = menu.classList.toggle('menu-abierto');
            hamburguesa.setAttribute('aria-expanded', abierto ? 'true' : 'false');
        });
    }
    window.addEventListener('resize', function () {
        if (window.innerWidth >= 992) { cerrarMenuMovil(); }
    });

    // ---------- Franja del título ----------
    // Cada pantalla trae su ".bg-title" (título y rol). Aquí se completa: ícono de la opción activa del menú
    // y botones de tarea (@section('buttons')) a la derecha de la franja.
    function armarFranja() {
        var franja = $('#contenido .bg-title');
        if (!franja) { return; }
        franja.classList.add('franja-titulo-pagina');

        var titulo = $('.page-title', franja);
        var icono = menu ? menu.getAttribute('data-icono') : '';
        if (titulo && icono && !$('.franja-icono', titulo)) {
            var span = document.createElement('span');
            span.className = 'franja-icono';
            span.setAttribute('aria-hidden', 'true');
            var i = document.createElement('i');
            i.className = icono;
            span.appendChild(i);
            titulo.insertBefore(span, titulo.firstChild);
        }

        var tareas = $('#buttonsTareas');
        if (tareas && !franja.contains(tareas)) {
            tareas.classList.add('en-franja');
            franja.appendChild(tareas);
        }
    }
    armarFranja();
})();
