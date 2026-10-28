<table style="width: 100%;">
    <thead>
        <tr style="border: 1px solid #000;">
            <td style="border: 1px solid #000; width: 88%; padding: 10px;" colspan="2" rowspan="3">
                <img src="{{ public_path('/theme/plugins/images/'.$logo) }}" width="auto" height="120px" alt="logo" class="dark-logo" style="margin: 10px;" />
            </td>
            <td style="border: 1px solid #000; width: 88%; text-align: center; vertical-align: middle;" rowspan="3" colspan="3">
                <h4><b>{{ $alcaldia }}</b></h4>
                <h5>{{ $nit }}</h5>
                <h5>{{ $direccion }}</h5>
                <h5>{{ $ubicacion }}</h5>
            </td>
            <td style="border: 1px solid #000; width: 88%; text-align: center; vertical-align: middle;" colspan="13" rowspan="3">
                <h3><b>INFORME DE RECAUDO PREDIAL Y CAR POR BANCO Y FECHA</b></h3>
            </td>
            <td colspan="2" style="border: 1px solid #000; vertical-align: middle;" height="35">C&oacute;digo:</td>
        </tr>
        <tr style="border: 1px solid #000;">
            <td colspan="2" style="border: 1px solid #000; vertical-align: middle;" height="35">Versi&oacute;n:</td>
        </tr>
        <tr style="border: 1px solid #000;">
            <td colspan="2" style="border: 1px solid #000; vertical-align: middle;" height="35">Registros: {{ count($registros) }}</td>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align: center; vertical-align: middle;" colspan="20" height="25"><h3><b>INFORME DE RECAUDO PREDIAL Y CAR</b></h3></td>
        </tr>
        <tr>
            <td style="text-align: center; vertical-align: middle;" colspan="20" height="25"><h3><b>ENTRE FECHAS {{ $fecha_inicial }} Y {{ $fecha_final }}</b></h3></td>
        </tr>
        <tr>
            <th style="background-color: #ededed; text-align: center; vertical-align: middle; border: 1px solid #000;" height="25"><b>Banco</b></th>
            <th style="background-color: #ededed; text-align: center; vertical-align: middle; border: 1px solid #000;" height="25"><b>Fecha Pago</b></th>
            <th style="background-color: #ededed; text-align: center; vertical-align: middle; border: 1px solid #000;" height="25"><b>A&ntilde;o</b></th>
            <th style="background-color: #ededed; text-align: center; vertical-align: middle; border: 1px solid #000;" height="25"><b>Factura</b></th>
            <th style="background-color: #ededed; text-align: center; vertical-align: middle; border: 1px solid #000;" height="25"><b>C&oacute;digo predio</b></th>
            <th style="background-color: #ededed; text-align: center; vertical-align: middle; border: 1px solid #000;" height="25"><b>Propietario</b></th>
            <th style="background-color: #ededed; text-align: center; vertical-align: middle; border: 1px solid #000;" height="25"><b>C&eacute;dula/NIT</b></th>
            <th style="background-color: #ededed; text-align: center; vertical-align: middle; border: 1px solid #000;" height="25"><b>Predial a&ntilde;o act.</b></th>
            <th style="background-color: #ededed; text-align: center; vertical-align: middle; border: 1px solid #000;" height="25"><b>Desc. predial</b></th>
            <th style="background-color: #ededed; text-align: center; vertical-align: middle; border: 1px solid #000;" height="25"><b>Predial a&ntilde;os ant.</b></th>
            <th style="background-color: #ededed; text-align: center; vertical-align: middle; border: 1px solid #000;" height="25"><b>Int. predial</b></th>
            <th style="background-color: #ededed; text-align: center; vertical-align: middle; border: 1px solid #000;" height="25"><b>Int. a&ntilde;os ant.</b></th>
            <th style="background-color: #ededed; text-align: center; vertical-align: middle; border: 1px solid #000;" height="25"><b>Total predial</b></th>
            <th style="background-color: #ededed; text-align: center; vertical-align: middle; border: 1px solid #000;" height="25"><b>Car a&ntilde;o act.</b></th>
            <th style="background-color: #ededed; text-align: center; vertical-align: middle; border: 1px solid #000;" height="25"><b>Desc. Car</b></th>
            <th style="background-color: #ededed; text-align: center; vertical-align: middle; border: 1px solid #000;" height="25"><b>Car a&ntilde;os ant.</b></th>
            <th style="background-color: #ededed; text-align: center; vertical-align: middle; border: 1px solid #000;" height="25"><b>Int. Car a&ntilde;o act.</b></th>
            <th style="background-color: #ededed; text-align: center; vertical-align: middle; border: 1px solid #000;" height="25"><b>Int. Car a&ntilde;os ant.</b></th>
            <th style="background-color: #ededed; text-align: center; vertical-align: middle; border: 1px solid #000;" height="25"><b>Total Car</b></th>
            <th style="background-color: #ededed; text-align: center; vertical-align: middle; border: 1px solid #000;" height="25"><b>Valor facturado</b></th>
        </tr>
        @php($predialanoactual = 0)
        @php($descuentopredial = 0)
        @php($predialanosanteriores = 0)
        @php($interesespredial = 0)
        @php($interesespredialanosanteriores = 0)
        @php($total_predial = 0)
        @php($caranoactual = 0)
        @php($descuentocar = 0)
        @php($caranoanteriores = 0)
        @php($interesescaractual = 0)
        @php($interesescaranteriores = 0)
        @php($totalcar = 0)
        @php($valor_facturado = 0)
        {{-- RENDIMIENTO: las celdas de datos van SIN style/width/data-format porque PhpSpreadsheet tardaba ~4 ms por celda
             interpretandolos (34 s para 8.000 filas). Alineacion, formato $#,##0_- y anchos se aplican de una vez por
             columna en ExportRecaudoCAR::registerEvents(). El resultado en Excel es el mismo. --}}
        @foreach($registros as $registro)
            <tr>
                <td>{{ $registro->nombre_banco }}</td>
                <td>{{ \Carbon\Carbon::parse($registro->fechapago)->format('Y-m-d') }}</td>
                <td>{{ $registro->ultimo_anio }}</td>
                <td>{{ $registro->factura_pago }}</td>
                <td>{{ $registro->codigo_predio }}</td>
                <td>{{ $registro->nombre_propietario }}</td>
                <td>{{ $registro->identificacion_propietario }}</td>
                <td>{{ $registro->predialanoactual }}</td>
                <td>{{ $registro->descuentopredial }}</td>
                <td>{{ $registro->predialanosanteriores }}</td>
                <td>{{ $registro->interesespredial }}</td>
                <td>{{ $registro->interesespredialanosanteriores }}</td>
                <td>{{ $registro->total_predial }}</td>
                <td>{{ $registro->caranoactual }}</td>
                <td>{{ $registro->descuentocar }}</td>
                <td>{{ $registro->caranoanteriores }}</td>
                <td>{{ $registro->interesescaractual }}</td>
                <td>{{ $registro->interesescaranteriores }}</td>
                <td>{{ $registro->totalcar }}</td>
                <td>{{ $registro->valor_facturado }}</td>
            </tr>
            @php($predialanoactual += $registro->predialanoactual)
            @php($descuentopredial += $registro->descuentopredial)
            @php($predialanosanteriores += $registro->predialanosanteriores)
            @php($interesespredial += $registro->interesespredial)
            @php($interesespredialanosanteriores += $registro->interesespredialanosanteriores)
            @php($total_predial += $registro->total_predial)
            @php($caranoactual += $registro->caranoactual)
            @php($descuentocar += $registro->descuentocar)
            @php($caranoanteriores += $registro->caranoanteriores)
            @php($interesescaractual += $registro->interesescaractual)
            @php($interesescaranteriores += $registro->interesescaranteriores)
            @php($totalcar += $registro->totalcar)
            @php($valor_facturado += $registro->valor_facturado)
        @endforeach
        <tr>
            <td style="background-color: #ededed; text-align: right; vertical-align: middle; border: 1px solid #000;" colspan="7"><b>TOTAL:</b></td>
            <td style="background-color: #ededed; text-align: right; vertical-align: middle; border: 1px solid #000;" data-format="$#,##0_-">{{ $predialanoactual }}</td>
            <td style="background-color: #ededed; text-align: right; vertical-align: middle; border: 1px solid #000;" data-format="$#,##0_-">{{ $descuentopredial }}</td>
            <td style="background-color: #ededed; text-align: right; vertical-align: middle; border: 1px solid #000;" data-format="$#,##0_-">{{ $predialanosanteriores }}</td>
            <td style="background-color: #ededed; text-align: right; vertical-align: middle; border: 1px solid #000;" data-format="$#,##0_-">{{ $interesespredial }}</td>
            <td style="background-color: #ededed; text-align: right; vertical-align: middle; border: 1px solid #000;" data-format="$#,##0_-">{{ $interesespredialanosanteriores }}</td>
            <td style="background-color: #ededed; text-align: right; vertical-align: middle; border: 1px solid #000;" data-format="$#,##0_-">{{ $total_predial }}</td>
            <td style="background-color: #ededed; text-align: right; vertical-align: middle; border: 1px solid #000;" data-format="$#,##0_-">{{ $caranoactual }}</td>
            <td style="background-color: #ededed; text-align: right; vertical-align: middle; border: 1px solid #000;" data-format="$#,##0_-">{{ $descuentocar }}</td>
            <td style="background-color: #ededed; text-align: right; vertical-align: middle; border: 1px solid #000;" data-format="$#,##0_-">{{ $caranoanteriores }}</td>
            <td style="background-color: #ededed; text-align: right; vertical-align: middle; border: 1px solid #000;" data-format="$#,##0_-">{{ $interesescaractual }}</td>
            <td style="background-color: #ededed; text-align: right; vertical-align: middle; border: 1px solid #000;" data-format="$#,##0_-">{{ $interesescaranteriores }}</td>
            <td style="background-color: #ededed; text-align: right; vertical-align: middle; border: 1px solid #000;" data-format="$#,##0_-">{{ $totalcar }}</td>
            <td style="background-color: #ededed; text-align: right; vertical-align: middle; border: 1px solid #000;" data-format="$#,##0_-">{{ $valor_facturado }}</td>
        </tr>
    </tbody>
</table>
