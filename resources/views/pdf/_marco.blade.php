{{--
    Banda superior, encabezado (escudo + nombre + dependencia + caja del documento) y pie. Se repiten en
    cada hoja. Requiere $id (App\Support\PdfIdentidad::datos) y los estilos de pdf._estilos.

    Parámetros:
      $caja   ['rotulo' => 'CERTIFICADO No.', 'valor' => '202600383', 'l1' => '...', 'l2' => '...']  (l1 y l2 opcionales)
      $pie_izq texto opcional a la izquierda del pie (por ejemplo "Elaboró: Nombre")
      $pagina_fija texto fijo para la derecha del pie (por ejemplo "Página 1 de 1"); si no se pasa, "Página N" con
                   el contador de dompdf (el PHP incrustado en el HTML está apagado, por eso no hay "de N")
      $anchoCaja ancho de la caja en pt (178 por omisión)
--}}
@php
    $caja = isset($caja) && is_array($caja) ? $caja : [];
    $anchoCaja = isset($anchoCaja) ? $anchoCaja : 178;
    $logo = $id['logo'];
@endphp
<div id="banda"></div>

<div id="cabecera">
    <table>
        <tr>
            @if($logo)
            <td style="width: {{ $logo['pw'] + 12 }}pt;">
                <img src="{{ $logo['uri'] }}" style="width: {{ $logo['pw'] }}pt; height: {{ $logo['ph'] }}pt;" alt="" />
            </td>
            @endif
            <td>
                <div class="nombre">{{ $id['nombre'] }}</div>
                <div class="dependencia">{{ $id['secretaria'] }}</div>
                @if($id['nit'] !== '')<div class="nit">NIT {{ $id['nit'] }}</div>@endif
            </td>
            @if(count($caja))
            <td style="width: {{ $anchoCaja }}pt;">
                <div class="caja">
                    <div class="rotulo">{{ $caja['rotulo'] ?? '' }}</div>
                    <div class="valor">{{ $caja['valor'] ?? '' }}</div>
                    @if(!empty($caja['l1']))<div class="linea">{{ $caja['l1'] }}</div>@endif
                    @if(!empty($caja['l2']))<div class="linea">{{ $caja['l2'] }}</div>@endif
                </div>
            </td>
            @endif
        </tr>
    </table>
</div>

<div id="pie">
    <table>
        <tr>
            <td style="width: 24%;">{{ $pie_izq ?? '' }}</td>
            <td class="centro">{{ $id['pie'] }}</td>
            <td class="der" style="width: 24%;">
                @if(isset($pagina_fija))
                    {{ $pagina_fija }}
                @else
                    <span class="pagina"></span>
                @endif
            </td>
        </tr>
    </table>
</div>
