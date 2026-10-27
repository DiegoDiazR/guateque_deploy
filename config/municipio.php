<?php

/*
|--------------------------------------------------------------------------
| Identidad del municipio (encabezado, login, pie de página y colores)
|--------------------------------------------------------------------------
|
| Todo lo que el sistema muestra del municipio sale de aquí: nombre, logos,
| colores y datos de contacto. Los valores por omisión son los de Paipa; otro
| municipio solo necesita sobrescribirlos en su .env (MUNICIPIO_*), sin tocar
| las vistas. Después de cambiar el .env en producción:
|     php artisan config:clear   (o config:cache)
|
| Los logos se buscan dentro de public/ (ruta relativa a esa carpeta).
|
*/

$ciudad = env('MUNICIPIO_CIUDAD', 'Paipa');

return [

    // Nombres
    'ciudad'       => $ciudad,
    'departamento' => env('MUNICIPIO_DEPARTAMENTO', 'Boyacá'),
    'alcaldia'     => env('MUNICIPIO_ALCALDIA', 'Alcaldía de ' . $ciudad),
    'secretaria'   => env('MUNICIPIO_SECRETARIA', 'Secretaría de Hacienda'),
    'dependencia'  => env('MUNICIPIO_DEPENDENCIA', 'Dirección de Impuestos, Rentas y Jurisdicción Coactiva'),
    'sistema'      => env('MUNICIPIO_SISTEMA', 'Impuesto Predial Unificado'),
    'desarrollador' => env('MUNICIPIO_DESARROLLADOR', 'ERPSOFT S.A.S.'),

    // Colores (hexadecimal de 6 dígitos). El resto de tonos se calcula a partir de estos.
    'color'        => env('MUNICIPIO_COLOR', '#1fa49d'),
    'color_oscuro' => env('MUNICIPIO_COLOR_OSCURO', '#17756f'),
    'color_acento' => env('MUNICIPIO_COLOR_ACENTO', '#c99a2e'),

    // Imágenes (relativas a public/)
    'logo'   => env('MUNICIPIO_LOGO', 'img/paipa/logo-paipa-full.png'),
    'escudo' => env('MUNICIPIO_ESCUDO', 'img/paipa/escudo-paipa.png'),
    'fondo'  => env('MUNICIPIO_FONDO', 'img/paipa/fondo-paipa.jpg'),

    // Contacto (pie de página). Un campo vacío no se muestra.
    'telefono'  => env('MUNICIPIO_TELEFONO', '318 530 9285'),
    'correo'    => env('MUNICIPIO_CORREO', 'impuestos@paipa-boyaca.gov.co'),
    'direccion' => env('MUNICIPIO_DIRECCION', 'Carrera 22 # 25-14, primer piso'),
    'horario'   => env('MUNICIPIO_HORARIO', ''),

    // Enlaces del pie de página: 'texto' => 'url'. En el .env se pueden dar
    // como JSON: MUNICIPIO_ENLACES={"Texto":"https://..."}
    'portal' => env('MUNICIPIO_PORTAL', 'https://psepaipa.erpsoftsas.com'),
    'portal_texto' => env('MUNICIPIO_PORTAL_TEXTO', 'Pago en línea del Impuesto Predial'),
    'enlaces' => (json_decode((string) env('MUNICIPIO_ENLACES', ''), true) ?: [
        'Estatuto de Rentas (Acuerdo 019 de 2022)' => 'https://www.paipa-boyaca.gov.co/Transparencia/Normatividad/Acuerdo%20019%20de%202022.pdf',
        'Régimen sancionatorio (Acuerdo 018 de 2024)' => 'https://www.paipa-boyaca.gov.co/Transparencia/Normatividad/Acuerdo%20018%20de%202024.pdf',
    ]),

];
