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

    // ---------- Cabecera fija ----------
    // La cabecera es "sticky" (CSS). Con la página desplazada se compacta (body.desplazado) y el alto que pierde
    // se pasa a su margen inferior, así el contenido no salta. Dos umbrales (80 y 20 px) separados más que lo que
    // se achica la cabecera, para que el cambio de alto no dispare el cambio contrario.
    var cabecera = $('.cabecera');
    var compacta = false;
    var pendiente = false;
    function ajustarCabecera() {
        pendiente = false;
        if (!cabecera) { return; }
        var y = window.pageYOffset || document.documentElement.scrollTop || 0;
        if (!compacta && y > 80) {
            var antes = cabecera.offsetHeight;
            compacta = true;
            document.body.classList.add('desplazado');
            cabecera.style.marginBottom = Math.max(0, antes - cabecera.offsetHeight) + 'px';
        } else if (compacta && y < 20) {
            compacta = false;
            document.body.classList.remove('desplazado');
            cabecera.style.marginBottom = '';
        }
    }
    window.addEventListener('scroll', function () {
        if (!pendiente) { pendiente = true; window.requestAnimationFrame(ajustarCabecera); }
    }, { passive: true });
    ajustarCabecera();

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

        // Botones de tarea: barra propia justo debajo de la franja, con el nombre completo en vez de las siglas
        // (DBP, DP, DA...). Se quita el tooltip: el nombre ya se ve y el tooltip quedaba cortado por la franja.
        var tareas = $('#buttonsTareas');
        if (tareas) {
            Array.prototype.forEach.call(tareas.querySelectorAll('a'), function (a) {
                var nombre = a.getAttribute('data-original-title') || a.getAttribute('title') || '';
                var sigla = a.querySelector('.hidden-xs');
                if (window.jQuery) { try { window.jQuery(a).tooltip('destroy'); } catch (e) { /* sin tooltip */ } }
                a.removeAttribute('data-tooltip');
                a.removeAttribute('data-toggle-tooltip');
                if (sigla && nombre) { sigla.textContent = nombre; }
                if (nombre) { a.setAttribute('aria-label', nombre); a.removeAttribute('title'); }
            });
            if (franja.nextElementSibling !== tareas) { franja.insertAdjacentElement('afterend', tareas); }
        }
    }
    armarFranja();

    // ---------- Estados SÍ / NO junto a las casillas ----------
    // Los textos "NO" que cambian a "SI" al marcar una casilla (¿Aplica ley 1995?, ¿Exento impuesto?, ...)
    // se muestran como etiqueta y con tilde ("SÍ"). Solo cambia la presentación: nada del sistema lee ese texto.
    function marcarEstado(el) {
        var t = (el.textContent || '').trim().toUpperCase();
        if (t === 'SI') { el.textContent = 'SÍ'; return; } // el cambio vuelve a pasar por aquí
        if (t === 'SÍ' || t === 'NO') { el.setAttribute('data-valor', t); }
    }
    $$('span.text-muted[id^="span_"]').forEach(function (el) {
        var t = (el.textContent || '').trim().toUpperCase();
        if (t !== 'SI' && t !== 'NO' && t !== 'SÍ') { return; }
        el.classList.add('estado-sn');
        marcarEstado(el);
        if (window.MutationObserver) {
            new MutationObserver(function () { marcarEstado(el); }).observe(el, { childList: true, characterData: true, subtree: true });
        }
    });
})();
