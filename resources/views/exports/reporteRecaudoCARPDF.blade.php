{{--
    Relación de ingresos de predial por recibo y año (informe de recaudo) - plantilla COMPARTIDA por todos los municipios
    (estilo del portal de pago). Papel A3 horizontal (lo fija el controlador). Identidad desde App\Support\PdfIdentidad.
    Mismos datos y mismas sumas que la plantilla anterior. Documentación: docs/PDF.md
--}}
@php
    $id =\App\Support\PdfIdentidad::datos($logo ?? null, $alcaldia ?? null, $nit ?? null, $direccion ?? null, $ubicacion ?? null);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe Recaudo</title>
    @include('pdf._estilos', ['tam' => 'A3 landscape', 'arriba' => 112, 'abajo' => 56])
    <style>
        body { font-size: 8.6pt; }
        table.datos td { font-size: 7.6pt; padding-left: 2.5pt; padding-right: 2.5pt; }
        table.datos th { font-size: 6.8pt; }
        table.datos td.num { text-align: right; }
        table.datos td.cen { text-align: center; }
        table.datos td.num, table.datos td.cen, table.datos tr.totales td { white-space: nowrap; }
        table.datos td.nom { text-align: left; font-size: 7.2pt; }
        .grupo { background-color: {{ $id['c']['tinte'] }}; border-left: 3pt solid {{ $id['c']['oro'] }}; padding: 5pt 9pt; margin-top: 16pt; font-size: 9pt; color: {{ $id['c']['oscuro'] }}; }
        .grupo b { color: {{ $id['c']['tinta'] }}; }
    </style>
</head>
<body>
    @include('pdf._marco', [
        'caja' => ['rotulo' => 'REGISTROS', 'valor' => count($registros), 'l1' => 'Código:', 'l2' => 'Versión:'],
    ])

    <div class="titulo">RELACIÓN DE INGRESOS PREDIAL POR RECIBO Y AÑO</div>
    <div class="subtitulo">ENTRE FECHAS {{ $fecha_inicial }} Y {{ $fecha_final }}</div>
    <div class="regla"></div>

    @php
        $agrupadoPorBanco = collect($registros)->groupBy('nombre_banco');
    @endphp
    @foreach($agrupadoPorBanco as $banco => $registrosBanco)
        @php
            $agrupadoPorFecha = collect($registrosBanco)->groupBy(function($item) {
                return \Carbon\Carbon::parse($item->fechapago)->format('Y-m-d');
            });
        @endphp
        @foreach($agrupadoPorFecha as $fecha => $registrosFecha)
            <div class="grupo">
                Banco:&nbsp;&nbsp;<b>{{ $banco }}</b>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Fecha:&nbsp;&nbsp;<b>{{ $fecha }}</b>
            </div>
            <table class="datos" style="margin-top: 5pt;">
                <thead>
                    <tr>
                        <th style="width: 3%;">Año</th>
                        <th style="width: 5.2%;">Factura</th>
                        <th style="width: 10%;">Código Predio</th>
                        <th style="width: 14%;">Propietario</th>
                        <th style="width: 6.5%;">Cédula/NIT</th>
                        <th>Predial<br>Presente Año</th>
                        <th>Descuento<br>Predial</th>
                        <th>Predial Años<br>Anteriores</th>
                        <th>Intereses<br>Predial</th>
                        <th>Intereses Años<br>Anteriores</th>
                        <th>Total Predial</th>
                        <th>Car Año<br>Actual</th>
                        <th>Desc. Car</th>
                        <th>Car Años<br>Anteriores</th>
                        <th>Intereses Car<br>Año Actual</th>
                        <th>Intereses Car<br>Años Anteriores</th>
                        <th>Total Car</th>
                        <th>Valor Facturado</th>
                    </tr>
                </thead>
                @php
                    $sub_predialanoactual = 0;
                    $sub_descuentopredial = 0;
                    $sub_predialanosanteriores = 0;
                    $sub_interesespredial = 0;
                    $sub_interesespredialanosanteriores = 0;
                    $sub_total_predial = 0;
                    $sub_caranoactual = 0;
                    $sub_descuentocar = 0;
                    $sub_caranoanteriores = 0;
                    $sub_interesescaractual = 0;
                    $sub_interesescaranteriores = 0;
                    $sub_totalcar = 0;
                    $sub_valor_facturado = 0;
                @endphp
                @foreach($registrosFecha as $registro)
                    <tr class="{{ $loop->iteration % 2 == 0 ? 'par' : '' }}">
                        <td class="cen">{{ $registro->ultimo_anio }}</td>
                        <td class="cen nw">{{ $registro->factura_pago }}</td>
                        <td class="cen nw" style="font-size: 7pt;">{{ $registro->codigo_predio }}</td>
                        <td class="nom">{{ $registro->nombre_propietario }}</td>
                        <td class="cen nw">{{ $registro->identificacion_propietario }}</td>
                        <td class="num">${{ number_format((float)$registro->predialanoactual, 2) }}</td>
                        <td class="num">${{ number_format((float)$registro->descuentopredial, 2) }}</td>
                        <td class="num">${{ number_format((float)$registro->predialanosanteriores, 2) }}</td>
                        <td class="num">${{ number_format((float)$registro->interesespredial, 2) }}</td>
                        <td class="num">${{ number_format((float)$registro->interesespredialanosanteriores, 2) }}</td>
                        <td class="num">${{ number_format((float)$registro->total_predial, 2) }}</td>
                        <td class="num">${{ number_format((float)$registro->caranoactual, 2) }}</td>
                        <td class="num">${{ number_format((float)$registro->descuentocar, 2) }}</td>
                        <td class="num">${{ number_format((float)$registro->caranoanteriores, 2) }}</td>
                        <td class="num">${{ number_format((float)$registro->interesescaractual, 2) }}</td>
                        <td class="num">${{ number_format((float)$registro->interesescaranteriores, 2) }}</td>
                        <td class="num">${{ number_format((float)$registro->totalcar, 2) }}</td>
                        <td class="num"><b>${{ number_format((float)$registro->valor_facturado, 2) }}</b></td>
                    </tr>
                    @php
                        $sub_predialanoactual += (float)$registro->predialanoactual;
                        $sub_descuentopredial += (float)$registro->descuentopredial;
                        $sub_predialanosanteriores += (float)$registro->predialanosanteriores;
                        $sub_interesespredial += (float)$registro->interesespredial;
                        $sub_interesespredialanosanteriores += (float)$registro->interesespredialanosanteriores;
                        $sub_total_predial += (float)$registro->total_predial;
                        $sub_caranoactual += (float)$registro->caranoactual;
                        $sub_descuentocar += (float)$registro->descuentocar;
                        $sub_caranoanteriores += (float)$registro->caranoanteriores;
                        $sub_interesescaractual += (float)$registro->interesescaractual;
                        $sub_interesescaranteriores += (float)$registro->interesescaranteriores;
                        $sub_totalcar += (float)$registro->totalcar;
                        $sub_valor_facturado += (float)$registro->valor_facturado;
                    @endphp
                @endforeach
                <tr class="totales">
                    <td colspan="5">SUB TOTAL POR FECHA</td>
                    <td>${{ number_format($sub_predialanoactual, 2) }}</td>
                    <td>${{ number_format($sub_descuentopredial, 2) }}</td>
                    <td>${{ number_format($sub_predialanosanteriores, 2) }}</td>
                    <td>${{ number_format($sub_interesespredial, 2) }}</td>
                    <td>${{ number_format($sub_interesespredialanosanteriores, 2) }}</td>
                    <td>${{ number_format($sub_total_predial, 2) }}</td>
                    <td>${{ number_format($sub_caranoactual, 2) }}</td>
                    <td>${{ number_format($sub_descuentocar, 2) }}</td>
                    <td>${{ number_format($sub_caranoanteriores, 2) }}</td>
                    <td>${{ number_format($sub_interesescaractual, 2) }}</td>
                    <td>${{ number_format($sub_interesescaranteriores, 2) }}</td>
                    <td>${{ number_format($sub_totalcar, 2) }}</td>
                    <td>${{ number_format($sub_valor_facturado, 2) }}</td>
                </tr>
            </table>
        @endforeach
    @endforeach
</body>
</html>
