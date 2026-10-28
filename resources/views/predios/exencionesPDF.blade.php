{{--
    Lista de exenciones - plantilla COMPARTIDA por todos los municipios (estilo del portal de pago). Papel A4 horizontal.
    Identidad (nombre, escudo, NIT, colores) desde App\Support\PdfIdentidad. Mismos datos que la plantilla anterior.
    Documentación: docs/PDF.md
--}}
@php($id = \App\Support\PdfIdentidad::datos($logo ?? null, $alcaldia ?? null, $nit ?? null))
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Lista de exenciones</title>
    @include('pdf._estilos', ['tam' => 'A4 landscape'])
    <style>
        table.datos td.num { text-align: right; }
        table.datos td.cen { text-align: center; }
    </style>
</head>
<body>
    @include('pdf._marco', [
        'caja' => ['rotulo' => 'LISTA DE EXENCIONES', 'valor' => count($exenciones), 'l1' => 'registros'],
    ])

    <table class="datos">
        <thead>
            <tr>
                <th>FECHA<br />EXENCION</th>
                <th>CODIGO<br />PREDIO</th>
                <th>VIGENCIA</th>
                <th>PORCENTAJE</th>
                <th>PREDIAL</th>
                <th>INTERES<br />PREDIAL</th>
                <th>CAR</th>
                <th>INTERES<br />CAR</th>
                <th>DESCUENTO<br />PREDIAL</th>
                <th>DESCUENTO<br />CAR</th>
                <th>TASA<br />BOMBERIL</th>
                <th>ALUMBRADO</th>
            </tr>
        </thead>
        @if (count($exenciones) > 0)
        @php($suma_valor_concepto1 = 0)
        @php($suma_valor_concepto2 = 0)
        @php($suma_valor_concepto3 = 0)
        @php($suma_valor_concepto4 = 0)
        @php($suma_valor_concepto13 = 0)
        @php($suma_valor_concepto15 = 0)
        @php($suma_valor_concepto16 = 0)
        @php($suma_valor_concepto18 = 0)
        @foreach($exenciones as $exencion)
        <tr class="{{ $loop->iteration % 2 == 0 ? 'par' : '' }}">
            <td class="cen nw">{{ substr($exencion->created_at, 0, 10) }}</td>
            <td class="cen nw">{{ $exencion->codigo_predio }}</td>
            <td class="cen"><b>{{ $exencion->exencion_anio }}</b></td>
            <td class="cen">{{ number_format($exencion->porcentaje, 2) }}%</td>
            <td class="num">@money($exencion->valor_concepto1)</td>
            <td class="num">@money($exencion->valor_concepto2)</td>
            <td class="num">@money($exencion->valor_concepto3)</td>
            <td class="num">@money($exencion->valor_concepto4)</td>
            <td class="num">@money($exencion->valor_concepto13)</td>
            <td class="num">@money($exencion->valor_concepto15)</td>
            <td class="num">@money($exencion->valor_concepto16)</td>
            <td class="num">@money($exencion->valor_concepto18)</td>
        </tr>
        @php($suma_valor_concepto1 += $exencion->valor_concepto1)
        @php($suma_valor_concepto2 += $exencion->valor_concepto2)
        @php($suma_valor_concepto3 += $exencion->valor_concepto3)
        @php($suma_valor_concepto4 += $exencion->valor_concepto4)
        @php($suma_valor_concepto13 += $exencion->valor_concepto13)
        @php($suma_valor_concepto15 += $exencion->valor_concepto15)
        @php($suma_valor_concepto16 += $exencion->valor_concepto16)
        @php($suma_valor_concepto18 += $exencion->valor_concepto18)
        @endforeach
        <tr class="totales">
            <th colspan="4">TOTAL PAGO</th>
            <th>@money($suma_valor_concepto1)</th>
            <th>@money($suma_valor_concepto2)</th>
            <th>@money($suma_valor_concepto3)</th>
            <th>@money($suma_valor_concepto4)</th>
            <th>@money($suma_valor_concepto13)</th>
            <th>@money($suma_valor_concepto15)</th>
            <th>@money($suma_valor_concepto16)</th>
            <th>@money($suma_valor_concepto18)</th>
        </tr>
        @else
        <tr>
            <td colspan="12" class="vacio">NO HAY INFORMACIÓN DISPONIBLE</td>
        </tr>
        @endif
    </table>
</body>
</html>
