// Inicializa las pestañas (.sttabs) de cada pantalla. Antes era un <script> en linea en
// resources/views/theme/default.blade.php; se movio aqui para poder empaquetarlo con el resto.
// Debe cargarse despues de theme/js/cbpFWTabs.js.
(function () {
    [].slice.call(document.querySelectorAll('.sttabs')).forEach(function (el) {
        new CBPFWTabs(el);
    });
})();
