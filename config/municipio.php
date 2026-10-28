<?php

/*
|--------------------------------------------------------------------------
| Identidad del municipio (encabezado, login, pie de página y colores)
|--------------------------------------------------------------------------
|
| La misma base de código se despliega en varios municipios. Cada uno ve SU
| nombre y SU escudo sin editar el .env del servidor, porque la identidad viva
| (alcaldía, logo, dirección, ubicación, NIT) está en la tabla `parametros` de
| su propia base de datos. Esa lectura la hace App\Support\Municipio.
|
| Este archivo guarda solo lo que NO está en la base de datos:
|
|   'slug'           municipio forzado (variable MUNICIPIO del .env). Si es null
|                    se deduce del parámetro `logo` y del parámetro `alcaldia`.
|   'predeterminado' valores comunes a todos los municipios (colores, sistema).
|   'perfiles'       datos propios de cada municipio, por slug: contacto, enlaces,
|                    colores, imágenes. Una clave que falte no se imprime.
|   'sobrescribir'   variables MUNICIPIO_* del .env. Mandan sobre todo lo demás.
|
| Orden de decisión de cada dato: .env  >  perfil  >  parametros  >  valor por omisión.
|
| Las rutas de imágenes son relativas a public/. Después de cambiar el .env en
| producción:   php artisan config:clear   (o config:cache)
|
| Más detalle y cómo agregar un perfil: docs/MULTIMUNICIPIO.md
|
*/

return [

    // Municipio forzado: paipa, guateque, sutatenza, lacapilla, santarosa, villadeleyva, sesquile.
    // Casi nunca hace falta: sin él, el sistema lo deduce de la base de datos.
    'slug' => env('MUNICIPIO'),

    // Comunes a todos los municipios.
    'predeterminado' => [
        'sistema'       => 'Impuesto Predial Unificado',
        'desarrollador' => 'ERPSOFT S.A.S.',

        // Colores (hexadecimal de 6 dígitos). El resto de tonos se calcula a partir de estos.
        'color'        => '#1fa49d',
        'color_oscuro' => '#17756f',
        'color_acento' => '#c99a2e',
    ],

    // Perfiles por municipio. Solo van datos confirmados: no se inventan teléfonos,
    // correos ni enlaces. Lo que no esté aquí ni en `parametros` simplemente no se imprime.
    //
    // Claves posibles: ciudad, departamento, alcaldia (fuerza el nombre completo),
    // secretaria, dependencia, sistema, desarrollador, color, color_oscuro, color_acento,
    // telefono, correo, direccion, horario, nit, portal, portal_texto, enlaces ('texto' => 'url'),
    // logo_completo (imagen del encabezado que ya trae el nombre escrito), escudo (pie y
    // favicon), fondo (foto del login), validacion_titulo y validacion_correo (pie de la
    // validación pública del QR).
    'perfiles' => [

        'paipa' => [
            'ciudad'       => 'Paipa',
            'departamento' => 'Boyacá',
            'secretaria'   => 'Secretaría de Hacienda',
            'dependencia'  => 'Dirección de Impuestos, Rentas y Jurisdicción Coactiva',
            'telefono'     => '318 530 9285',
            'correo'       => 'impuestos@paipa-boyaca.gov.co',
            'direccion'    => 'Carrera 22 # 25-14, primer piso',
            'logo_completo' => 'img/paipa/logo-paipa-full.png',
            'escudo'       => 'img/paipa/escudo-paipa.png',
            'fondo'        => 'img/paipa/fondo-paipa.jpg',
            'portal'       => 'https://psepaipa.erpsoftsas.com',
            'portal_texto' => 'Pago en línea del Impuesto Predial',
            'enlaces'      => [
                'Estatuto de Rentas (Acuerdo 019 de 2022)' => 'https://www.paipa-boyaca.gov.co/Transparencia/Normatividad/Acuerdo%20019%20de%202022.pdf',
                'Régimen sancionatorio (Acuerdo 018 de 2024)' => 'https://www.paipa-boyaca.gov.co/Transparencia/Normatividad/Acuerdo%20018%20de%202024.pdf',
            ],
            'validacion_titulo' => 'Tesorería Municipal de Paipa',
            'validacion_correo' => 'tesoreria@paipa-boyaca.gov.co',
        ],

        // Los demás municipios solo tienen por ahora el nombre de la ciudad y el departamento
        // (para escribirlos bien con tildes). Su nombre completo, escudo, dirección, ubicación
        // y NIT salen de `parametros`. Cuando haya contacto o colores propios, se agregan aquí.
        'guateque'     => ['ciudad' => 'Guateque',     'departamento' => 'Boyacá'],
        'sutatenza'    => ['ciudad' => 'Sutatenza',    'departamento' => 'Boyacá'],
        'lacapilla'    => ['ciudad' => 'La Capilla',   'departamento' => 'Boyacá'],
        'santarosa'    => ['ciudad' => 'Santa Rosa de Viterbo', 'departamento' => 'Boyacá'],
        'villadeleyva' => ['ciudad' => 'Villa de Leyva', 'departamento' => 'Boyacá'],
        'sesquile'     => ['ciudad' => 'Sesquilé',     'departamento' => 'Cundinamarca'],
    ],

    // Variables del .env que sobrescriben el perfil. Si la variable no existe valen null y no
    // cambian nada; una variable definida pero vacía (MUNICIPIO_HORARIO=) borra ese dato.
    // MUNICIPIO_LOGO es la imagen del encabezado (la que ya trae el nombre), no el escudo.
    'sobrescribir' => [
        'ciudad'        => env('MUNICIPIO_CIUDAD'),
        'departamento'  => env('MUNICIPIO_DEPARTAMENTO'),
        'alcaldia'      => env('MUNICIPIO_ALCALDIA'),
        'secretaria'    => env('MUNICIPIO_SECRETARIA'),
        'dependencia'   => env('MUNICIPIO_DEPENDENCIA'),
        'sistema'       => env('MUNICIPIO_SISTEMA'),
        'desarrollador' => env('MUNICIPIO_DESARROLLADOR'),
        'color'         => env('MUNICIPIO_COLOR'),
        'color_oscuro'  => env('MUNICIPIO_COLOR_OSCURO'),
        'color_acento'  => env('MUNICIPIO_COLOR_ACENTO'),
        'logo_completo' => env('MUNICIPIO_LOGO'),
        'escudo'        => env('MUNICIPIO_ESCUDO'),
        'fondo'         => env('MUNICIPIO_FONDO'),
        'telefono'      => env('MUNICIPIO_TELEFONO'),
        'correo'        => env('MUNICIPIO_CORREO'),
        'direccion'     => env('MUNICIPIO_DIRECCION'),
        'horario'       => env('MUNICIPIO_HORARIO'),
        'nit'           => env('MUNICIPIO_NIT'),
        'portal'        => env('MUNICIPIO_PORTAL'),
        'portal_texto'  => env('MUNICIPIO_PORTAL_TEXTO'),
        // JSON: MUNICIPIO_ENLACES={"Texto":"https://..."}
        'enlaces'       => json_decode((string) env('MUNICIPIO_ENLACES', ''), true) ?: null,
    ],

];
