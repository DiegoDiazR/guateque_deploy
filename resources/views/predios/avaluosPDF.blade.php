{{--
    Histórico de pagos del predio - plantilla COMPARTIDA por todos los municipios (estilo del portal de pago).
    Identidad (nombre, escudo, NIT, colores) desde App\Support\PdfIdentidad. Mismos datos que la plantilla anterior.
    Documentación: docs/PDF.md
--}}
@php($id = \App\Support\PdfIdentidad::datos($logo ?? null, $alcaldia ?? null, $nit ?? null))
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Histórico de pagos</title>
    @include('pdf._estilos', ['tam' => 'A4 portrait'])
    <style>
        table.datos td.num { text-align: right; }
        table.datos td.cen { text-align: center; }
    </style>
</head>
<body>
    @include('pdf._marco', [
        'caja' => ['rotulo' => 'HISTÓRICO DE PAGOS', 'valor' => count($avaluos), 'l1' => 'pagos registrados', 'l2' => 'IMPUESTO PREDIAL UNIFICADO'],
    ])

    <table class="ficha" style="table-layout: fixed;">
        <tr>
            <td class="rot" style="width: 17%;">CÉDULA CATASTRAL</td>
            <td style="width: 83%;">{{ $predio->codigo_predio }}</td>
        </tr>
        <tr>
            <td class="rot">PROPIETARIO</td>
            <td>{{ optional($propietario_ppal)->nombre }}</td>
        </tr>
        <tr>
            <td class="rot">DIRECCIÓN</td>
            <td>{{ $predio->direccion }}</td>
        </tr>
    </table>

    <table class="datos" style="margin-top: 14pt;">
        <thead>
            <tr>
                <th style="width: 11%;">RECIBO</th>
                <th style="width: 11%;">FECHA</th>
                <th style="width: 25%;">BANCO</th>
                <th style="width: 12%;">VALOR</th>
                <th style="width: 6%;">AÑO</th>
                <th style="width: 12%;">AVALUO</th>
                <th style="width: 9%;">TIPO RECIBO</th>
                <th style="width: 8%;">PRESCRITO</th>
                <th style="width: 6%;">EXENTO</th>
            </tr>
        </thead>
        @php($suma_impuesto = 0)
        @foreach($avaluos as $avaluo)
        <tr class="{{ $loop->iteration % 2 == 0 ? 'par' : '' }}">
            <td class="cen nw">{{ $avaluo->factura_pago }}</td>
            <td class="cen nw">{{ $avaluo->fecha_pago }}</td>
            <td class="cen">{{ $avaluo->banco }}</td>
            <td class="num">@money($avaluo->valor_pago)</td>
            <td class="cen"><b>{{ $avaluo->anio }}</b></td>
            <td class="num">@money($avaluo->avaluo)</td>
            <td class="cen">Vigencia</td>
            <td class="cen">{{ $avaluo->prescrito }}</td>
            <td class="cen">{{ $avaluo->exencion }}</td>
        </tr>
        @php($suma_impuesto += $avaluo->valor_pago)
        @endforeach
        <tr class="totales">
            <th colspan="3">TOTAL PAGO</th>
            <th>@money($suma_impuesto)</th>
            <th colspan="5" style="background-color: #ffffff; border: 0;"></th>
        </tr>
    </table>
</body>
</html>
