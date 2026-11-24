{{--
    Factura (liquidación oficial) del Impuesto Predial Unificado de Paipa.
    Formato de la hoja de la Alcaldía (A4, una página): encabezado, predio, vigencias, competencia,
    total y fecha límite, notificación, firma y dos cupones (banco y municipio) con código GS1-128.

    Un solo total y un solo "pague hasta": los del descuento que aplica hoy (los elige el controlador con
    App\Support\FacturaPaipa::cuponVigente). Las normas y la notificación son el texto validado por el
    abogado de la Alcaldía: no se cambian sin su visto bueno.
--}}
@php
    $n = function ($v) { return number_format((float) $v, 0, ',', '.'); };
    $meses = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SEP', 'OCT', 'NOV', 'DIC'];
    $fechaImpresion = $fecha === 'INDEFINIDA' ? \Carbon\Carbon::now()->format('d/m/Y') : $fecha;
    $fechaHasta = \Carbon\Carbon::parse($fechas_pago_hasta[0]);
    $hastaTexto = $fechaHasta->format('d/m/Y');
    $total = (float) $valores_factura[0];
    $hayFilas = count($lista_pagos) > 0;

    // Un solo propietario (el principal): el controlador ya trae el de jerarquía 001.
    $propietario = trim((string) preg_replace('/<br\s*\/?>.*$/is', '', (string) $predio->propietarios));
    $identificacion = trim((string) preg_replace('/<br\s*\/?>.*$/is', '', (string) $predio->identificaciones));

    $fechaUltimoPago = \App\Support\FacturaPaipa::fecha(isset($ultimo_anio_pagado->fecha_pago) ? $ultimo_anio_pagado->fecha_pago : '');
    $valorUltimoPago = isset($ultimo_anio_pagado->valor_pago) ? $ultimo_anio_pagado->valor_pago : null;

    // Vigencias liquidadas: las de las filas de la tabla. Si hay una fila acumulada ("Antes de ..."),
    // el inicio no se ve en las filas y se toma de los años a pagar.
    $vigencias = str_replace(' A ', ' a ', trim((string) $predio->anios_a_pagar));
    $aniosFilas = [];
    $hayAcumulada = false;
    foreach ($lista_pagos as $p) {
        if (ctype_digit((string) $p->anio)) { $aniosFilas[] = (int) $p->anio; } else { $hayAcumulada = true; }
    }
    if (!$hayAcumulada && count($aniosFilas) > 0) {
        $vigencias = min($aniosFilas) == max($aniosFilas) ? (string) min($aniosFilas) : min($aniosFilas) . ' a ' . max($aniosFilas);
    }
    $variasVigencias = strpos($vigencias, ' a ') !== false;

    // Filas: cada una debe sumar su total. Lo que no cabe en las columnas de la hoja (saldos, notas,
    // otros conceptos) va en "Otros", que solo aparece si algún predio lo necesita.
    $filas = [];
    foreach ($lista_pagos as $p) {
        $descuento = (float) $p->trece + (float) $p->quince;
        $filas[] = [
            'anio'      => (string) $p->anio,
            'avaluo'    => (float) $p->avaluo,
            'tarifa'    => (float) $p->m_tar,
            'impuesto'  => (float) $p->impuesto,
            'int_imp'   => (float) $p->impuesto_interes,
            'car'       => (float) $p->car,
            'int_car'   => (float) $p->car_interes,
            'alumbrado' => (float) $p->dieciocho,
            'descuento' => $descuento,
            'total'     => (float) $p->total,
        ];
    }
    // El valor a pagar manda: si el descuento vigente es otro (segunda o tercera fecha), la última
    // vigencia lo absorbe en "Descuentos" para que la tabla y el total a pagar coincidan.
    if ($hayFilas) {
        $ult = count($filas) - 1;
        $suma = 0;
        foreach ($filas as $f) { $suma += $f['total']; }
        $ajuste = $total - $suma;
        if (abs($ajuste) >= 1) {
            $filas[$ult]['descuento'] += $ajuste;
            $filas[$ult]['total'] += $ajuste;
        }
    }
    $hayOtros = false;
    foreach ($filas as $k => $f) {
        $otros = $f['total'] - ($f['impuesto'] + $f['int_imp'] + $f['car'] + $f['int_car'] + $f['alumbrado'] + $f['descuento']);
        $filas[$k]['otros'] = abs($otros) >= 1 ? $otros : 0;
        if ($filas[$k]['otros'] != 0) { $hayOtros = true; }
    }
    $tot = ['impuesto' => 0, 'int_imp' => 0, 'car' => 0, 'int_car' => 0, 'alumbrado' => 0, 'otros' => 0, 'descuento' => 0, 'total' => 0];
    foreach ($filas as $f) { foreach ($tot as $c => $v) { $tot[$c] += $f[$c]; } }

    $tasa = \App\Support\FacturaPaipa::tasaMoraAnual();
    $letras = \App\Support\FacturaPaipa::totalEnLetras($total);
    $zona = \App\Support\FacturaPaipa::zona(isset($predio->descripcion) ? $predio->descripcion : '');
    $direccion = trim((string) $predio->direccion) . ($zona !== '' ? ' · ' . $zona : '');

    $escudo = '';
    foreach (['img/paipa/escudo-paipa.png', 'theme/plugins/images/logo-paipa.png'] as $ruta) {
        if (is_file(public_path($ruta))) { $escudo = 'data:image/png;base64,' . base64_encode(file_get_contents(public_path($ruta))); break; }
    }
    $firma = '';
    if (is_file(public_path('theme/plugins/images/firma-paz-y-salvo-paipa-3.png'))) {
        $firma = 'data:image/png;base64,' . base64_encode(file_get_contents(public_path('theme/plugins/images/firma-paz-y-salvo-paipa-3.png')));
    }

    $puntos = 'AGRARIO 1511-007245-4 · BANCOLOMBIA 038-851044-27 · BBVA 001380660100000027 · BOGOTÁ 676001340 · CONFIAR COOP 450006242 · DAVIVIENDA 41600000803-5 · POPULAR 261-72007-2';
    $mostrarCupones = $hayFilas && !$facturaYaPagada;
    $conBarras = intval($temporal) == 0;
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Factura predial</title>
    <style>
        @page { margin: 22px 30px 18px 30px; }
        body { margin: 0; padding: 0; font-family: Helvetica, Arial, sans-serif; font-size: 9px; color: #1f2d2b; }
        table { border-collapse: collapse; }
        .w { width: 100%; }
        .b { font-weight: bold; }
        .c { text-align: center; }
        .r { text-align: right; }
        .verde { color: #17756f; }
        .caja { background: #17756f; color: #ffffff; border-radius: 9px; padding: 9px 12px; text-align: center; }
        .caja .t { font-size: 8.5px; letter-spacing: 0.4px; }
        .caja .n { font-size: 22px; font-weight: bold; margin: 3px 0; }
        .caja .p { font-size: 8.5px; }
        .info td { border: 1px solid #cfdcd9; padding: 5px 7px; font-size: 10px; }
        .info td.l { background: #e8f0ee; font-weight: bold; width: 17%; }
        .vig { margin-top: 9px; width: 100%; }
        .vig th { background: #17756f; color: #ffffff; padding: 6px 4px; font-size: 9px; text-align: center; }
        .vig td { padding: 5px 5px; font-size: 10.5px; border-bottom: 1px solid #e3ecea; }
        .vig tr.tot td { border-top: 2px solid #17756f; background: #f3f8f7; }
        .comp { font-size: 8.4px; line-height: 1.38; text-align: justify; padding-right: 10px; }
        .tbox { border: 1.5px solid #17756f; border-radius: 9px; padding: 10px 6px; text-align: center; }
        .tbox .a { color: #17756f; font-size: 8px; font-weight: bold; letter-spacing: 0.5px; }
        .tbox .m { font-size: 19px; font-weight: bold; color: #17756f; margin-top: 6px; }
        .fbox { border: 1.5px solid #17756f; border-radius: 9px; text-align: center; margin-top: 7px; }
        .fbox .h { background: #17756f; color: #ffffff; font-size: 8px; font-weight: bold; padding: 3px; border-radius: 7px 7px 0 0; }
        .fbox .d { font-size: 30px; font-weight: bold; color: #17756f; padding-top: 4px; }
        .fbox .s { font-size: 9px; font-weight: bold; padding-bottom: 6px; }
        .nota { font-size: 8.4px; color: #4a5a57; margin: 5px 0; }
        .not { border-left: 3px solid #17756f; background: #f3f8f7; padding: 7px 10px; font-size: 8.2px; line-height: 1.38; text-align: justify; margin-top: 5px; }
        .corte { border-top: 1px dashed #7d8f8c; text-align: center; font-size: 8px; color: #6a7a77; margin-top: 8px; padding-top: 1px; }
        .tijera { font-family: "DejaVu Sans", sans-serif; }
        .cupon { border: 1.5px solid #cfdcd9; border-radius: 9px; padding: 6px 10px 3px 10px; margin-top: 2px; }
        .cupon .v { font-size: 10px; }
        .cupon .g { font-size: 12.5px; padding-top: 5px; }
        .barras { text-align: center; padding-top: 4px; }
        .bt { text-align: center; font-size: 9px; padding-top: 2px; }
        .aviso { border: 1.5px solid #d9534f; color: #b52b27; border-radius: 9px; padding: 10px; text-align: center; font-size: 10px; font-weight: bold; margin-top: 10px; }
        .mar { position: absolute; left: 80px; top: 330px; font-size: 62px; font-weight: bold; color: #17756f; opacity: 0.06; transform: rotate(-24deg); letter-spacing: 9px; white-space: nowrap; }
    </style>
</head>
<body>
<div class="mar">MUNICIPIO DE PAIPA</div>
<table class="w">
    <tr>
        <td style="width: 60px;">@if($escudo !== '')<img src="{{ $escudo }}" width="52" alt="Escudo" />@endif</td>
        <td style="vertical-align: middle;">
            <div style="font-size: 19px; font-weight: bold;">ALCALDÍA MUNICIPAL DE PAIPA</div>
            <div style="font-size: 10px;">NIT 891.801.240-1</div>
            <div style="font-size: 10px; font-weight: bold;">Secretaría de Hacienda</div>
            <div style="font-size: 10px;">Impuesto Predial Unificado</div>
        </td>
        <td style="width: 230px;">
            <div class="caja">
                <div class="t">LIQUIDACIÓN OFICIAL – FACTURA No.</div>
                <div class="n">{{ $numero_factura }}</div>
                <div class="p">Expedida: {{ $fechaImpresion }} · Publicada: {{ $fechaImpresion }}</div>
            </div>
        </td>
    </tr>
</table>

<table class="w info" style="margin-top: 9px;">
    <tr>
        <td class="l">Propietario</td>
        <td colspan="3">{{ $propietario }}</td>
    </tr>
    <tr>
        <td class="l">Código catastral</td>
        <td>{{ $predio->codigo_predio }}</td>
        <td class="l" style="width: 11%;">NIT / C.C.</td>
        <td>{{ $identificacion }}</td>
    </tr>
    <tr>
        <td class="l">Dirección del predio</td>
        <td>{{ $direccion }}</td>
        <td class="l">Área</td>
        <td>Ha {{ number_format($predio->area_hectareas, 0) }} &nbsp; Área M² {{ number_format($predio->area_metros, 0) }} &nbsp; Construida {{ number_format($predio->area_construida, 0) }}</td>
    </tr>
    <tr>
        <td class="l">Últ. año pagado</td>
        <td>
            {{ $predio->ultimo_anio_pago }}
            @if($fechaUltimoPago !== '') &nbsp; Fecha pago {{ $fechaUltimoPago }} @endif
            @if(is_numeric($valorUltimoPago) && $valorUltimoPago > 0) &nbsp; Valor pagado $ {{ $n($valorUltimoPago) }} @endif
        </td>
        <td class="l">Vigencias liquidadas</td>
        <td class="b">{{ $vigencias }}</td>
    </tr>
</table>

@if($hayFilas)
<table class="vig">
    <tr>
        <th>Vigencia</th>
        <th>Avalúo<br />catastral</th>
        <th>Tarifa<br />‰</th>
        <th>Impuesto<br />predial</th>
        <th>Intereses<br />predial</th>
        <th>Sobretasa<br />ambiental</th>
        <th>Intereses<br />sobretasa</th>
        <th>Alumbrado<br />público</th>
        @if($hayOtros)<th>Otros</th>@endif
        <th>Descuentos</th>
        <th>Total</th>
    </tr>
    @foreach($filas as $f)
    @php($acumulada = strpos($f['anio'], '<') === 0)
    <tr>
        <td class="c b" style="white-space: nowrap;">{{ $acumulada ? 'Antes de ' . trim(substr($f['anio'], 1)) : $f['anio'] }}</td>
        <td class="r">{{ $acumulada ? '' : $n($f['avaluo']) }}</td>
        <td class="c">{{ $acumulada ? '' : rtrim(rtrim(number_format($f['tarifa'], 2, ',', '.'), '0'), ',') }}</td>
        <td class="r">{{ $n($f['impuesto']) }}</td>
        <td class="r">{{ $n($f['int_imp']) }}</td>
        <td class="r">{{ $n($f['car']) }}</td>
        <td class="r">{{ $n($f['int_car']) }}</td>
        <td class="r">{{ $n($f['alumbrado']) }}</td>
        @if($hayOtros)<td class="r">{{ $n($f['otros']) }}</td>@endif
        <td class="r">{{ $n($f['descuento']) }}</td>
        <td class="r b">{{ $n($f['total']) }}</td>
    </tr>
    @endforeach
    <tr class="tot">
        <td class="c b">Total</td>
        <td></td>
        <td></td>
        <td class="r b">{{ $n($tot['impuesto']) }}</td>
        <td class="r b">{{ $n($tot['int_imp']) }}</td>
        <td class="r b">{{ $n($tot['car']) }}</td>
        <td class="r b">{{ $n($tot['int_car']) }}</td>
        <td class="r b">{{ $n($tot['alumbrado']) }}</td>
        @if($hayOtros)<td class="r b">{{ $n($tot['otros']) }}</td>@endif
        <td class="r b">{{ $n($tot['descuento']) }}</td>
        <td class="r b">{{ $n($tot['total']) }}</td>
    </tr>
</table>

<table class="w" style="margin-top: 7px;">
    <tr>
        <td style="vertical-align: top;" class="comp">
            <span class="verde b">Competencia y determinación.</span> El Secretario de Hacienda del Municipio de Paipa, en ejercicio de la competencia de liquidación y recaudo asignada por la Resolución 031 de 2023 (Manual de Funciones), el Acuerdo 019 de 2022 y el Decreto 009 de 2017, determina oficialmente a cargo del sujeto pasivo señalado (art. 24 Acuerdo 019 de 2022 y art. 54 Ley 1430 de 2010) el Impuesto Predial Unificado sobre el avalúo catastral vigente (art. 3 Ley 44 de 1990) a la tarifa del art. 25 del Acuerdo 019 de 2022, la sobretasa ambiental (art. 44 Ley 99 de 1993, Corpoboyacá), el alumbrado público (art. 9 Acuerdo 006 de 2026) y los intereses de mora (arts. 634 y 635 E.T., art. 59 Ley 788 de 2002) de {{ $variasVigencias ? 'las vigencias' : 'la vigencia' }} {{ $vigencias }}, por <span class="verde b">{{ $letras }} (${{ $n($total) }}) M/CTE.</span>
        </td>
        <td style="width: 150px; vertical-align: top;">
            <div class="tbox"><div class="a">TOTAL A PAGAR</div><div class="m">$ {{ $n($total) }}</div></div>
        </td>
        <td style="width: 112px; vertical-align: top;">
            <div class="fbox"><div class="h">PAGUE HASTA</div><div class="d">{{ $fechaHasta->format('d') }}</div><div class="s">{{ $meses[$fechaHasta->month - 1] }} · {{ $fechaHasta->year }}</div></div>
        </td>
    </tr>
</table>

<div class="nota">Intereses de mora liquidados al {{ $fechaImpresion }} a la tasa {{ $tasa !== null ? 'de ' . \App\Support\FacturaPaipa::porcentaje($tasa) . ' % E.A.' : 'vigente' }}, por cada día de retardo (arts. 634 y 635 E.T.). Si paga después de la fecha límite, solicite una nueva liquidación.</div>

<div class="not"><b>Notificación.</b> Se notifica por inserción en la página web del Municipio y publicación simultánea en la cartelera de la Secretaría de Hacienda (art. 69 Ley 1111 de 2006, mod. art. 354 Ley 1819 de 2016; art. 11 Decreto 009 de 2017). El envío al predio o al correo del contribuyente es divulgación adicional y su omisión no invalida la notificación. <b>Recurso.</b> Procede el de reconsideración, por escrito ante la Secretaría de Hacienda, dentro de los dos (2) meses siguientes a la notificación (arts. 720 y 722 E.T.), en la Carrera 22 # 25-14 de Paipa o al correo impuestos&#64;paipa-boyaca.gov.co. <b>Mérito ejecutivo.</b> En firme, este acto presta mérito ejecutivo y su cobro corresponde a la Dirección de Impuestos, Rentas y Jurisdicción Coactiva mediante el procedimiento administrativo de cobro coactivo (art. 69 Ley 1111 de 2006, arts. 823 y 828 E.T.). Los intereses se siguen causando hasta el pago efectivo.</div>

<table class="w" style="margin-top: 6px;">
    <tr>
        <td style="vertical-align: bottom; font-size: 9px; color: #4a5a57;">
            <b>Atención al contribuyente:</b><br />Carrera 22 # 25-14, Paipa · Tel. 318 530 9285<br />impuestos&#64;paipa-boyaca.gov.co
        </td>
        <td style="width: 240px; text-align: center;">
            @if($firma !== '')<div style="height: 42px; overflow: hidden;"><img src="{{ $firma }}" width="120" alt="Firma" /></div>@else<div style="height: 42px;"></div>@endif
            <div style="border-top: 1px solid #1f2d2b; font-size: 9.5px; font-weight: bold; padding-top: 2px;">WILLIAM NIÑO ECHEVERRÍA</div>
            <div style="font-size: 9.5px;">Secretario de Hacienda Municipal</div>
            <div style="font-size: 7.5px; color: #6a7a77;">Firma mecánica - art. 12 Decreto Ley 2150 de 1995</div>
        </td>
    </tr>
</table>

@if($mostrarCupones)
    @foreach(['BANCO', 'MUNICIPIO'] as $rotulo)
    <div class="corte"><span class="tijera">✂</span> Cupón para {{ $rotulo == 'BANCO' ? 'el banco' : 'el municipio' }}</div>
    <div class="cupon">
        <table class="w">
            <tr>
                <td class="v"><span class="verde b">{{ $rotulo }}</span> · Factura No. <b>{{ $numero_factura }}</b></td>
                <td class="v c">Ref. catastral {{ $predio->codigo_predio }}</td>
                <td class="v r">Vigencias {{ $vigencias }}</td>
            </tr>
            <tr>
                <td class="b g" colspan="2">Pague hasta {{ $hastaTexto }}</td>
                <td class="r b g">$ {{ $n($total) }}</td>
            </tr>
        </table>
        @if($conBarras)
        <div class="barras"><img src="data:image/png;base64,{{ DNS1D::getBarcodePNG($barras[0], 'C128') }}" height="62" width="520" alt="Código de barras" /></div>
        <div class="bt">{{ $barras_texto[0] }}</div>
        @endif
    </div>
    @endforeach
    <div style="font-size: 8.5px; color: #4a5a57; margin-top: 7px;"><b>Puntos de pago:</b> {{ $puntos }}</div>
@else
    <div class="aviso">
        @if($facturaYaPagada && $informativa != '1')
            PAGO DE FACTURA YA REGISTRADO.<br />El predio se encuentra a paz y salvo.
        @endif
    </div>
@endif
@else
<div class="aviso">NO HAY INFORMACIÓN DISPONIBLE PARA REALIZAR PAGO</div>
@endif

@if($temporal > 0)
<div class="aviso">VISTA PREVIA FACTURA DE COBRO<br />Documento no válido para la ejecución del pago de impuesto predial.</div>
@endif
<div style="font-size: 7px; color: #8a9a97; text-align: center; margin-top: 6px;">Diseñado e impreso por SISTEMAS ERPSOFT SAS</div>
</body>
</html>
