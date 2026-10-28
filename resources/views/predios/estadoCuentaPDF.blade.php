{{--
    Estado de cuenta del predio - plantilla COMPARTIDA por todos los municipios (estilo del portal de pago).
    Identidad (nombre, escudo, NIT, colores) desde App\Support\PdfIdentidad. Mismos datos que la plantilla anterior.
    Documentación: docs/PDF.md
--}}
@php($id = \App\Support\PdfIdentidad::datos($logo ?? null, $alcaldia ?? null, $nit ?? null))
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Estado de cuenta predial</title>
    @include('pdf._estilos', ['tam' => 'A4 portrait'])
    <style>
        table.datos td.num { text-align: right; }
        table.datos td.vig { text-align: center; font-weight: bold; }
        table.datos td.ap { text-align: center; }
    </style>
</head>
<body>
    @include('pdf._marco', [
        'caja' => ['rotulo' => 'ESTADO DE CUENTA A', 'valor' => $fecha, 'l1' => 'IMPUESTO PREDIAL UNIFICADO'],
    ])

    <table class="ficha" style="table-layout: fixed;">
        <tr>
            <td class="rot" style="width: 17%;">IDENTIFICACIÓN</td>
            <td style="width: 33%;">{{ optional($propietario_ppal)->identificacion }}</td>
            <td class="rot" style="width: 17%;">PROPIETARIO</td>
            <td style="width: 33%;">{{ optional($propietario_ppal)->nombre }}</td>
        </tr>
        <tr>
            <td class="rot">CÉDULA CATASTRAL</td>
            <td>{{ $predio->codigo_predio }}</td>
            <td class="rot">DIRECCIÓN</td>
            <td>{{ $predio->direccion }}</td>
        </tr>
        <tr>
            <td class="rot">AVALÚO ACTUAL</td>
            <td>@money(optional($predio_pago)->avaluo)</td>
            <td class="rot">TARIFA</td>
            <td>{{ number_format(optional($predio_pago)->tarifa * 1000, 2) }}</td>
        </tr>
        <tr>
            <td class="rot">ÁREA (M2)</td>
            <td>{{ number_format($predio->area_metros, 2) }}</td>
            <td class="rot">ÁREA CONST. (M2)</td>
            <td>{{ number_format($predio->area_construida, 2) }}</td>
        </tr>
        <tr>
            <td class="rot">ÚLTIMO PAGO</td>
            <td colspan="3">{{ optional($predio_pago)->ultimo_anio }}</td>
        </tr>
    </table>

    <table class="datos" style="margin-top: 14pt;">
        <thead>
            <tr>
                <th style="width: 7%;">VIG.</th>
                <th style="width: 13%;">AVALUO</th>
                <th>IMPUESTO</th>
                <th>INTERESES</th>
                <th style="width: 9%;">CAR</th>
                <th style="width: 11%;">INTERES CAR</th>
                <th>DESCUENTO</th>
                <th style="width: 8%;">OTROS</th>
                <th style="width: 12%;">TOTAL</th>
                <th style="width: 5%;">AP</th>
            </tr>
        </thead>
        @php($suma_impuesto = 0)
        @php($suma_interes_impuesto = 0)
        @php($suma_car = 0)
        @php($suma_interes_car = 0)
        @php($suma_descuento = 0)
        @php($suma_otros = 0)
        @php($suma_total = 0)
        @foreach($pendientes as $pendiente)
        <tr class="{{ $loop->iteration % 2 == 0 ? 'par' : '' }}">
            <td class="vig">{{ $pendiente->vigencia }}</td>
            <td class="num">@money($pendiente->avaluo)</td>
            <td class="num">@money($pendiente->impuesto)</td>
            <td class="num">@money($pendiente->interes_impuesto)</td>
            <td class="num">@money($pendiente->car)</td>
            <td class="num">@money($pendiente->interes_car)</td>
            <td class="num">@money($pendiente->descuento)</td>
            <td class="num">@money($pendiente->otros)</td>
            <td class="num"><b>@money($pendiente->total)</b></td>
            <td class="ap">{{ $pendiente->acuerdo }}</td>
        </tr>
        @php($suma_impuesto += $pendiente->impuesto)
        @php($suma_interes_impuesto += $pendiente->interes_impuesto)
        @php($suma_car += $pendiente->car)
        @php($suma_interes_car += $pendiente->interes_car)
        @php($suma_descuento += $pendiente->descuento)
        @php($suma_otros += $pendiente->otros)
        @php($suma_total += $pendiente->total)
        @endforeach
        <tr class="totales">
            <th colspan="2">TOTALES</th>
            <th>@money($suma_impuesto)</th>
            <th>@money($suma_interes_impuesto)</th>
            <th>@money($suma_car)</th>
            <th>@money($suma_interes_car)</th>
            <th>@money($suma_descuento)</th>
            <th>@money($suma_otros)</th>
            <th>@money($suma_total)</th>
            <th style="background-color: #ffffff; border: 0;"></th>
        </tr>
    </table>
</body>
</html>
