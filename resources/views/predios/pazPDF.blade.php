{{--
    Certificado de paz y salvo - plantilla COMPARTIDA (municipios sin formato propio: parametros.formato-paz = predios.pazPDF).
    Estilo del portal de pago de predial. Los municipios con formato oficial propio (pazPDF_pai, _lacap, _srosa, _villa)
    no pasan por aquí. La identidad (nombre, escudo, NIT, dirección, ubicación, colores) sale de App\Support\PdfIdentidad.
    Texto legal y datos impresos: los mismos de la plantilla anterior. Documentación: docs/PDF.md
--}}
@php($id = \App\Support\PdfIdentidad::datos($logo ?? null, $alcaldia ?? null, $nit ?? null))
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Paz y Salvo {{ $alcaldia }}</title>
    @include('pdf._estilos', ['tam' => 'A4 portrait', 'arriba' => 112, 'abajo' => 58])
    <style>
        .pais { text-align: center; font-size: 7.6pt; letter-spacing: 1.6pt; color: {{ $id['c']['tenue'] }}; padding-bottom: 5pt; }
        .quien { text-align: center; font-size: 10pt; font-weight: bold; letter-spacing: .3pt; padding-top: 22pt; }
        .certifica { text-align: center; font-size: 13pt; font-weight: bold; color: {{ $id['c']['oscuro'] }}; letter-spacing: 3pt; padding-top: 5pt; }
        .texto { font-size: 10pt; line-height: 1.6; text-align: justify; }
        .ultimo { font-size: 9.6pt; padding-top: 7pt; }
        .intro { font-size: 9.6pt; padding-top: 18pt; padding-bottom: 6pt; }
        .pazysalvo { background-color: {{ $id['c']['tinte'] }}; border-left: 3.5pt solid {{ $id['c']['oro'] }}; padding: 10pt 12pt; font-size: 10.8pt; font-weight: bold; color: {{ $id['c']['oscuro'] }}; }
        .destino { font-size: 10pt; padding-top: 12pt; }
        table.cierre td { vertical-align: bottom; }
        .rotulo-s { font-size: 6.8pt; font-weight: bold; letter-spacing: .3pt; color: {{ $id['c']['oscuro'] }}; }
        .valor-s { font-size: 10pt; font-weight: bold; padding-bottom: 7pt; }
        .linea-firma { border-top: 1pt solid {{ $id['c']['tinta'] }}; width: 82%; margin: 0 auto; padding-top: 4pt; font-size: 8.2pt; font-weight: bold; text-align: center; }
    </style>
</head>
<body>
    @include('pdf._marco', [
        'caja' => ['rotulo' => 'CERTIFICADO No.', 'valor' => $numero_certificado, 'l1' => 'Expedido el', 'l2' => $fecha_expedicion],
        'pie_izq' => 'Elaboró: ' . $usuario,
        'pagina_fija' => count($propietarios) <= 10 ? 'Página 1 de 1' : null,
    ])

    <div class="pais">REPÚBLICA DE COLOMBIA</div>
    <div class="titulo">CERTIFICADO DE PAZ Y SALVO</div>
    <div class="subtitulo">IMPUESTO PREDIAL</div>
    <div class="regla"></div>

    <div class="quien">EL SUSCRITO SECRETARIO DE HACIENDA MUNICIPAL</div>
    <div class="certifica">CERTIFICA</div>

    <table style="margin-top: 18pt;">
        <tr>
            <td class="texto">
                Que en los archivos de la tesorería municipal aparece inscrito el predio con código catastral número: <b>{{ $predio->codigo_predio }}</b> el cual figura a nombre de <b>{{ $propietario_ppal->nombre }}</b>, documento de identidad número <b>{{ $propietario_ppal->identificacion }}</b> con las siguientes especificaciones.
            </td>
        </tr>
        <tr>
            <td class="ultimo">
                <b>Último pago con factura No.:</b> {{ $ultimo_anio_pagado->factura_pago }} &nbsp;&nbsp;&nbsp;<b>Fecha:</b> {{ substr($ultimo_anio_pagado->fecha_pago, 0, 10) }}
            </td>
        </tr>
    </table>

    <table class="ficha" style="margin-top: 12pt; table-layout: fixed;">
        <tr>
            <td class="rot" style="width: 17%;">CÓDIGO CATASTRAL</td>
            <td colspan="3" style="width: 48%;">{{ $predio->codigo_predio }}</td>
            <td class="rot" style="width: 19%;">UBICACIÓN</td>
            <td style="width: 16%;">{{ $predio->zonas_descripcion }}</td>
        </tr>
        <tr>
            <td class="rot">DIRECCIÓN DEL PREDIO</td>
            <td colspan="5">{{ $predio->direccion }}</td>
        </tr>
        @if(!empty($codigo_postal))
        <tr>
            <td class="rot">CÓD. POSTAL</td>
            <td colspan="5">{{ $codigo_postal }}</td>
        </tr>
        @endif
        <tr>
            <td class="rot">ÁREA (HA)</td>
            <td class="cent" style="width: 15%;">{{ $predio->area_hectareas == '.00' ? 0 : str_replace('.00', '', $predio->area_hectareas) }}</td>
            <td class="rot" style="width: 17%;">ÁREA (M2)</td>
            <td class="cent" style="width: 16%;">{{ $predio->area_metros == '.00' ? 0 : str_replace('.00', '', $predio->area_metros) }}</td>
            <td class="rot">ÁREA CONSTRUIDA (M2)</td>
            <td class="cent">{{ $predio->area_construida == '.00' ? 0 : str_replace('.00', '', $predio->area_construida) }}</td>
        </tr>
        <tr>
            <td class="rot">VALOR AVALÚO</td>
            <td colspan="3">@money($ultimo_anio_pagado->avaluo)</td>
            <td class="rot">AÑO AVALÚO</td>
            <td class="cent">{{ $ultimo_anio_pagado->ultimo_anio }}</td>
        </tr>
    </table>

    <div class="intro">El cual está registrado con los siguientes propietarios:</div>
    <table class="datos">
        <thead>
            <tr>
                <th class="izq" style="width: 8%;">No.</th>
                <th class="izq" style="width: 22%;">Cédula / NIT</th>
                <th class="izq">Nombre</th>
            </tr>
        </thead>
        @foreach($propietarios as $propietario)
        <tr class="{{ $loop->iteration % 2 == 0 ? 'par' : '' }}">
            <td class="izq">{{ strlen($propietario->jerarquia) < 3 ? str_pad(trim($propietario->jerarquia), 3, "0", STR_PAD_LEFT) : $propietario->jerarquia }}</td>
            <td class="izq">{{ $propietario->identificacion }}</td>
            <td class="izq">{{ $propietario->nombre }}</td>
        </tr>
        @endforeach
    </table>

    <div style="padding-top: 18pt;">
        <div class="pazysalvo">El cual se encuentra a PAZ Y SALVO por concepto de impuesto predial.</div>
    </div>
    <div class="destino">Se expide con destino a: <b>{{ $destino }}</b></div>

    <table class="cierre" style="margin-top: 34pt; page-break-inside: avoid;">
        <tr>
            <td style="width: 24%;">
                @if(!empty($qr_token) && !empty($qr_code))
                <div style="width: 86pt; text-align: center; border: .8pt solid {{ $id['c']['linea2'] }}; padding: 5pt 3pt 4pt 3pt;">
                    <img src="data:image/png;base64,{{ $qr_code }}" style="width: 74pt; height: 74pt;" alt="QR" />
                    <div style="font-size: 6.6pt; color: {{ $id['c']['tenue'] }}; padding-top: 2pt;">Verificar autenticidad</div>
                </div>
                @endif
            </td>
            <td style="width: 31%;">
                <div class="rotulo-s">VÁLIDO HASTA</div>
                <div class="valor-s">{{ $fecha_validez }}</div>
                <div class="rotulo-s">VALOR</div>
                <div class="valor-s" style="padding-bottom: 0;">{{ $valor }} <span style="font-size: 8pt; font-weight: normal;">Pesos m/cte.</span></div>
            </td>
            <td style="width: 45%; text-align: center;">
                <div style="height: 64pt;">&nbsp;</div>
                <div class="linea-firma">SECRETARIO DE HACIENDA</div>
            </td>
        </tr>
    </table>

    <div style="padding-top: 22pt; font-size: 8.6pt; font-weight: bold; color: {{ $id['c']['oscuro'] }};">NO SE COBRA IMPUESTO POR VALORIZACIÓN.</div>
</body>
</html>
