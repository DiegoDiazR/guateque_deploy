<?php

/*
|--------------------------------------------------------------------------
| CSS y JS comunes de todas las pantallas (rendimiento)
|--------------------------------------------------------------------------
|
| Esta es la UNICA lista (y el orden) de los archivos que cargan todas las
| pantallas del sistema. Rutas relativas a public/.
|
|  - scripts/build_assets.php concatena estas listas en public/theme/dist/
|    (app.<hash>.css, app.<hash>.js y manifest.json). Esos archivos SI van al
|    repositorio: el servidor no necesita Node ni compilar nada.
|  - resources/views/theme/assets_css.blade.php y assets_js.blade.php usan el
|    paquete si existe; si no existe (o ASSETS_BUNDLE=false en .env) cargan
|    los archivos originales de esta lista, uno por uno.
|
| Si cambias esta lista o alguno de los archivos, vuelve a correr:
|     php scripts/build_assets.php
|
| Los plugins que usa UNA sola pantalla (p. ej. switchery en Pagos) NO van
| aqui: se cargan con @push('styles') / @push('scripts') en esa vista.
| Los CSS/JS que cambian seguido (paipa.css, paipa.js, site.js, controlsite.js,
| pagos.js, ...) tampoco: no se empaquetan para no tener que reconstruir.
*/

return [

    // false = no usar el paquete, cargar cada archivo por separado (depuracion).
    'bundle' => env('ASSETS_BUNDLE', true),

    'css' => [
        'theme/bootstrap/dist/css/bootstrap.min.css',
        'theme/plugins/bower_components/datatables/jquery.dataTables.min.css',
        'theme/plugins/bower_components/toast-master/css/jquery.toast.css',
        'theme/bootstrap/jqdatepicker/datepicker.min.css',
        'theme/plugins/bower_components/bootstrap-select/bootstrap-select.min.css',
        'theme/plugins/bower_components/select2/css/select2.min.css',
        'theme/plugins/bower_components/select2/css/select2-options.css',
        'theme/plugins/bower_components/sweetalert/sweetalert.css',
        'theme/css/animate.css',
        'theme/css/style.css',
        'theme/css/colors/green.css',
        'theme/css/customcss/style.css',
        'theme/css/jquery.powertip.min.css',
    ],

    'js' => [
        'theme/plugins/bower_components/jquery/dist/jquery.min.js',
        'theme/bootstrap/dist/js/bootstrap.min.js',
        'theme/plugins/bower_components/sidebar-nav/dist/sidebar-nav.min.js',
        'theme/js/jquery.slimscroll.js',
        'theme/js/waves.js',
        // moment: antes se cargaban dos copias (2.17.1 y la de fullcalendar); la segunda
        // pisaba a la primera, asi que esta es la que realmente usaba el sistema.
        'theme/plugins/bower_components/fullcalendar3.9/lib/moment.min.js',
        'theme/bootstrap/jqdatepicker/datepicker.min.js',
        'theme/bootstrap/jqdatepicker/datepicker.es-ES.js',
        'theme/plugins/bower_components/bootstrap-select/bootstrap-select.min.js',
        'theme/plugins/bower_components/datatables/jquery.dataTables.min.js',
        'theme/plugins/bower_components/select2/js/select2.full.min.js',
        'theme/plugins/bower_components/select2/js/i18n/es.js',
        'theme/js/custom.min.js',
        'theme/js/cbpFWTabs.js',
        'theme/js/customjs/tabs-init.js',
        'theme/plugins/bower_components/toast-master/js/jquery.toast.js',
        'theme/plugins/bower_components/sweetalert/sweetalert.min.js',
        'theme/js/jquery.powertip.min.js',
    ],

];
