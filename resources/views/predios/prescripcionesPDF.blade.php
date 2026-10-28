{{--
    Lista de prescripciones - plantilla COMPARTIDA por todos los municipios (estilo del portal de pago). Papel A4 horizontal.
    Identidad (nombre, escudo, NIT, colores) desde App\Support\PdfIdentidad. Mismos datos que la plantilla anterior.
    Documentación: docs/PDF.md
--}}
@php($id = \App\Support\PdfIdentidad::datos($logo ?? null, $alcaldia ?? null, $nit ?? null))
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Lista de prescripciones</title>
    @include('pdf._estilos', ['tam' => 'A4 landscape'])
    <style>
        table.datos td.num { text-align: right; }
        table.datos td.cen { text-align: center; }
    </style>
</head>
<body>
    @include('pdf._marco', [
        'caja' => ['rotulo' => 'LISTA DE PRESCRIPCIONES', 'valor' => count($prescripciones), 'l1' => 'registros'],
    ])

    <table class="datos">
        <thead>
            <tr>
                <th>FECHA<br />PRESCRIPCION</th>
                <th>CODIGO<br />PREDIO</th>
                <th>VIGENCIA</th>
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
        @if (count($prescripciones) > 0)
        @php($suma_valor_concepto1 = 0)
        @php($suma_valor_concepto2 = 0)
        @php($suma_valor_concepto3 = 0)
        @php($suma_valor_concepto4 = 0)
        @php($suma_valor_concepto13 = 0)
        @php($suma_valor_concepto15 = 0)
        @php($suma_valor_concepto16 = 0)
        @php($suma_valor_concepto18 = 0)
        @foreach($prescripciones as $prescribe)
        <tr class="{{ $loop->iteration % 2 == 0 ? 'par' : '' }}">
            <td class="cen nw">{{ substr($prescribe->created_at, 0, 10) }}</td>
            <td class="cen nw">{{ $prescribe->codigo_predio }}</td>
            <td class="cen"><b>{{ $prescribe->prescribe_anio }}</b></td>
            <td class="num">@money($prescribe->valor_concepto1)</td>
            <td class="num">@money($prescribe->valor_concepto2)</td>
            <td class="num">@money($prescribe->valor_concepto3)</td>
            <td class="num">@money($prescribe->valor_concepto4)</td>
            <td class="num">@money($prescribe->valor_concepto13)</td>
            <td class="num">@money($prescribe->valor_concepto15)</td>
            <td class="num">@money($prescribe->valor_concepto16)</td>
            <td class="num">@money($prescribe->valor_concepto18)</td>
        </tr>
        @php($suma_valor_concepto1 += $prescribe->valor_concepto1)
        @php($suma_valor_concepto2 += $prescribe->valor_concepto2)
        @php($suma_valor_concepto3 += $prescribe->valor_concepto3)
        @php($suma_valor_concepto4 += $prescribe->valor_concepto4)
        @php($suma_valor_concepto13 += $prescribe->valor_concepto13)
        @php($suma_valor_concepto15 += $prescribe->valor_concepto15)
        @php($suma_valor_concepto16 += $prescribe->valor_concepto16)
        @php($suma_valor_concepto18 += $prescribe->valor_concepto18)
        @endforeach
        <tr class="totales">
            <th colspan="3">TOTAL PAGO</th>
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
            <td colspan="11" class="vacio">NO HAY INFORMACIÓN DISPONIBLE</td>
        </tr>
        @endif
    </table>
</body>
</html>
