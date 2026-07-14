<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe Recaudo</title>
    <style>
        body { font-family: sans-serif; font-size: 8px; margin: 0; padding: 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #000; padding: 3px; text-align: center; }
        .right { text-align: right; }
        .left { text-align: left; }
        .bold { font-weight: bold; }
        .header-table { margin-bottom: 20px; border: 1px solid #000; }
        .header-table td { border: none; padding: 10px; }
        .header-table .center-border { border-left: 1px solid #000; border-right: 1px solid #000; }
        .group-title { font-size: 10px; font-weight: bold; margin-top: 15px; margin-bottom: 5px; }
        @page { margin: 30px; }
    </style>
</head>
<body>

    <script type="text/php">
        if ( isset($pdf) ) {
            $pdf->page_script('
                $font = $fontMetrics->get_font("sans-serif", "normal");
                $pdf->text(760, 20, "Página " . $PAGE_NUM . " de " . $PAGE_COUNT, $font, 8);
            ');
        }
    </script>

    <table class="header-table">
        <tr>
            <td style="width: 15%; text-align: center;">
                <img src="{{ public_path('/theme/plugins/images/'.$logo) }}" width="80" alt="logo" />
            </td>
            <td class="center-border" style="width: 25%; text-align: center;">
                <div style="font-size: 11px; font-weight: bold;">{{ $alcaldia }}</div>
                <div>{{ $nit }}</div>
                <div>{{ $direccion }}</div>
                <div>{{ $ubicacion }}</div>
            </td>
            <td class="center-border" style="width: 45%; text-align: center; vertical-align: middle; font-size: 11px; font-weight: bold;">
                RELACIÓN DE INGRESOS PREDIAL POR RECIBO Y AÑO<br><br>
                ENTRE FECHAS {{ $fecha_inicial }} Y {{ $fecha_final }}
            </td>
            <td style="width: 15%; text-align: left;">
                Código: <br><br>
                Versión: <br><br>
                Registros: {{ count($registros) }}
            </td>
        </tr>
    </table>

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
            <div class="group-title">
                Banco: &nbsp;&nbsp;&nbsp;{{ $banco }}<br>
                Fecha: &nbsp;&nbsp;&nbsp;{{ $fecha }}
            </div>
            
            <table>
                <thead>
                    <tr style="background-color: #ededed;">
                        <th>Año</th>
                        <th>Factura</th>
                        <th>Código Predio</th>
                        <th>Propietario</th>
                        <th>Cédula/NIT</th>
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
                <tbody>
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
                        <tr>
                            <td>{{ $registro->ultimo_anio }}</td>
                            <td>{{ $registro->factura_pago }}</td>
                            <td style="font-size: 7px;">{{ $registro->codigo_predio }}</td>
                            <td class="left" style="width: 100px; font-size: 7px;">{{ $registro->nombre_propietario }}</td>
                            <td>{{ $registro->identificacion_propietario }}</td>
                            <td class="right">${{ number_format((float)$registro->predialanoactual, 2) }}</td>
                            <td class="right">${{ number_format((float)$registro->descuentopredial, 2) }}</td>
                            <td class="right">${{ number_format((float)$registro->predialanosanteriores, 2) }}</td>
                            <td class="right">${{ number_format((float)$registro->interesespredial, 2) }}</td>
                            <td class="right">${{ number_format((float)$registro->interesespredialanosanteriores, 2) }}</td>
                            <td class="right">${{ number_format((float)$registro->total_predial, 2) }}</td>
                            <td class="right">${{ number_format((float)$registro->caranoactual, 2) }}</td>
                            <td class="right">${{ number_format((float)$registro->descuentocar, 2) }}</td>
                            <td class="right">${{ number_format((float)$registro->caranoanteriores, 2) }}</td>
                            <td class="right">${{ number_format((float)$registro->interesescaractual, 2) }}</td>
                            <td class="right">${{ number_format((float)$registro->interesescaranteriores, 2) }}</td>
                            <td class="right">${{ number_format((float)$registro->totalcar, 2) }}</td>
                            <td class="right">${{ number_format((float)$registro->valor_facturado, 2) }}</td>
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
                    <tr class="bold" style="background-color: #ededed;">
                        <td colspan="5" class="right">SUB TOTAL POR FECHA</td>
                        <td class="right">${{ number_format($sub_predialanoactual, 2) }}</td>
                        <td class="right">${{ number_format($sub_descuentopredial, 2) }}</td>
                        <td class="right">${{ number_format($sub_predialanosanteriores, 2) }}</td>
                        <td class="right">${{ number_format($sub_interesespredial, 2) }}</td>
                        <td class="right">${{ number_format($sub_interesespredialanosanteriores, 2) }}</td>
                        <td class="right">${{ number_format($sub_total_predial, 2) }}</td>
                        <td class="right">${{ number_format($sub_caranoactual, 2) }}</td>
                        <td class="right">${{ number_format($sub_descuentocar, 2) }}</td>
                        <td class="right">${{ number_format($sub_caranoanteriores, 2) }}</td>
                        <td class="right">${{ number_format($sub_interesescaractual, 2) }}</td>
                        <td class="right">${{ number_format($sub_interesescaranteriores, 2) }}</td>
                        <td class="right">${{ number_format($sub_totalcar, 2) }}</td>
                        <td class="right">${{ number_format($sub_valor_facturado, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        @endforeach
    @endforeach

</body>
</html>
