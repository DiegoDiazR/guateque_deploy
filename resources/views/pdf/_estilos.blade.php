{{--
    Estilos comunes de los PDF compartidos (estilo del portal de pago de predial: verde del municipio,
    acento dorado, tipografía sans clara). Solo CSS 2.1 que entiende dompdf 0.9/1.0: tablas, bordes y
    colores; nada de flex ni grid. Los colores salen de $id['c'] (App\Support\PdfIdentidad).

    Uso:  @include('pdf._estilos', ['tam' => 'A4 portrait'])
    Parámetros opcionales: $tam (papel y orientación), $mx (margen lateral, pt),
    $arriba (margen superior donde cabe el encabezado, pt), $abajo (margen inferior para el pie, pt).
--}}
@php
    $tam = isset($tam) ? $tam : 'A4 portrait';
    $mx = isset($mx) ? $mx : 36;
    $arriba = isset($arriba) ? $arriba : 112;
    $abajo = isset($abajo) ? $abajo : 56;
    $c = $id['c'];
@endphp
<style>
    @page { size: {{ $tam }}; margin: {{ $arriba }}pt {{ $mx }}pt {{ $abajo }}pt {{ $mx }}pt; }
    /* No poner margin en `html`: anula los márgenes de @page en dompdf. */
    body { margin: 0; padding: 0; font-family: Helvetica, Arial, sans-serif; font-size: 9pt; line-height: 1.3; color: {{ $c['tinta'] }}; }
    table { border-collapse: collapse; width: 100%; }
    td, th { vertical-align: top; padding: 0; }
    p { margin: 0; }
    b, strong { font-weight: bold; }

    /* Encabezado y pie: se repiten en cada hoja (position: fixed) */
    #banda { position: fixed; top: -{{ $arriba }}pt; left: -{{ $mx }}pt; right: -{{ $mx }}pt; height: 8pt; background-color: {{ $c['oscuro'] }}; border-bottom: 2.5pt solid {{ $c['oro'] }}; }
    #cabecera { position: fixed; top: -{{ $arriba - 24 }}pt; left: 0; right: 0; }
    #cabecera td { vertical-align: middle; }
    #cabecera .nombre { font-size: 12.5pt; font-weight: bold; color: {{ $c['tinta'] }}; line-height: 1.15; }
    #cabecera .dependencia { font-size: 9pt; color: {{ $c['oscuro'] }}; font-weight: bold; padding-top: 2pt; }
    #cabecera .nit { font-size: 8pt; color: {{ $c['tenue'] }}; padding-top: 1pt; }
    #cabecera .caja { background-color: {{ $c['oscuro'] }}; color: #ffffff; text-align: center; padding: 6pt 8pt 6pt 8pt; border-radius: 5pt; }
    #cabecera .caja .rotulo { font-size: 6.8pt; letter-spacing: .6pt; color: #d6efec; }
    #cabecera .caja .valor { font-size: 15pt; font-weight: bold; padding-top: 1pt; padding-bottom: 1pt; }
    #cabecera .caja .linea { font-size: 7.4pt; color: #e7f6f4; }

    #pie { position: fixed; bottom: -{{ $abajo - 20 }}pt; left: 0; right: 0; border-top: .8pt solid {{ $c['linea2'] }}; padding-top: 4pt; font-size: 7pt; color: {{ $c['tenue'] }}; }
    #pie td { vertical-align: top; }
    #pie .centro { text-align: center; }
    #pie .der { text-align: right; }
    #pie .pagina:before { content: "Página " counter(page); }

    /* Título del documento */
    .titulo { text-align: center; font-size: 14.5pt; font-weight: bold; color: {{ $c['oscuro'] }}; letter-spacing: .4pt; }
    .subtitulo { text-align: center; font-size: 9pt; font-weight: bold; color: {{ $c['oro'] }}; letter-spacing: 1.2pt; padding-top: 2pt; }
    .regla { height: 2pt; width: 48pt; background-color: {{ $c['oro'] }}; margin: 7pt auto 0 auto; }

    /* Ficha de datos: rótulo sobre fondo suave y valor al lado */
    table.ficha td { border: .6pt solid {{ $c['linea2'] }}; padding: 4pt 6pt; font-size: 8.6pt; }
    table.ficha td.rot { background-color: {{ $c['tinte'] }}; color: {{ $c['oscuro'] }}; font-weight: bold; font-size: 6.8pt; letter-spacing: .3pt; }
    table.ficha td.cent { text-align: center; }

    /* Tabla de datos con encabezado verde, filas alternas y totales.
       Bordes "separate" y solo línea inferior: con border-collapse dompdf gasta mucha más memoria
       en las listas largas (prescripciones con cientos de filas llegaban a 600 MB, así 370 MB). */
    table.datos { border-collapse: separate; border-spacing: 0; }
    table.datos th { white-space: nowrap; background-color: {{ $c['oscuro'] }}; color: #ffffff; font-weight: bold; font-size: 7pt; text-align: center; vertical-align: middle; padding: 4.5pt 3pt; border-left: .6pt solid {{ $c['oscuro'] }}; }
    table.datos td { border-bottom: .6pt solid {{ $c['linea'] }}; padding: 3.2pt 4pt; font-size: 8pt; }
    table.datos tr.par td { background-color: {{ $c['zebra'] }}; }
    table.datos tr.totales td, table.datos tr.totales th { background-color: {{ $c['tinte'] }}; color: {{ $c['oscuro'] }}; font-weight: bold; font-size: 8pt; text-align: right; padding: 4.5pt 4pt; border-top: 1pt solid {{ $c['oscuro'] }}; border-bottom: 1pt solid {{ $c['oscuro'] }}; border-left: 0; }
    table.datos thead { display: table-header-group; }
    table.datos td.vacio { text-align: center; padding: 14pt; font-size: 9.5pt; color: {{ $c['tenue'] }}; }

    .nw { white-space: nowrap; }
    .izq { text-align: left; }
    .cen { text-align: center; }
    .der { text-align: right; }
    .tenue { color: {{ $c['tenue'] }}; }
    .nota { font-size: 7.4pt; color: {{ $c['tenue'] }}; }
</style>
