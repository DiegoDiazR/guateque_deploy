{{-- Accesos directos de la pantalla de inicio: las mismas opciones (y permisos) del menú, agrupadas por su padre. --}}
@php
    $acOpciones = collect(Session::get('opciones') ?: [])->values();
    $acPadres = [];
    foreach ($acOpciones as $o) {
        if ($o->opcion_padre === null) {
            $acPadres[(string) $o->id] = $o;
        }
    }
    $acHijos = [];
    foreach ($acOpciones as $o) {
        if ($o->opcion_padre !== null && isset($acPadres[(string) $o->opcion_padre])) {
            $acHijos[(string) $o->opcion_padre][] = $o;
        }
    }
@endphp
@foreach ($acPadres as $acId => $acPadre)
    @if (!empty($acHijos[$acId]))
    <section class="accesos-grupo">
        <h3 class="accesos-titulo"><i class="{{ $acPadre->icono }}" aria-hidden="true"></i> {!! $acPadre->titulo !!}</h3>
        <div class="accesos-rejilla">
            @foreach ($acHijos[$acId] as $acHijo)
            <a class="acceso" href="/{{ $acHijo->url }}/{{ base64_encode($acHijo->id) }}">
                <span class="acceso-icono"><i class="{{ $acHijo->icono }}" aria-hidden="true"></i></span>
                <span class="acceso-texto"><strong>{!! $acHijo->titulo !!}</strong>@if (mb_strtolower(trim(html_entity_decode((string) $acHijo->descripcion))) !== mb_strtolower(trim(html_entity_decode((string) $acHijo->titulo))))<small>{!! $acHijo->descripcion !!}</small>@endif</span>
            </a>
            @endforeach
        </div>
    </section>
    @endif
@endforeach
<section class="accesos-grupo">
    <h3 class="accesos-titulo"><i class="ti-pie-chart" aria-hidden="true"></i> Informes</h3>
    <div class="accesos-rejilla">
        <a class="acceso" href="/informes">
            <span class="acceso-icono"><i class="fa fa-file-excel-o" aria-hidden="true"></i></span>
            <span class="acceso-texto"><strong>Recaudo Predial y CAR</strong><small>Informes de recaudo en Excel y PDF</small></span>
        </a>
    </div>
</section>
