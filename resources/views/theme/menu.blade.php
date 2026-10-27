{{-- Menú horizontal. Sale de Session('opciones') (permisos del tipo de usuario) respetando padre -> hijos. --}}
@php
    $mpOpciones = collect(Session::get('opciones') ?: [])->values();

    // Hijos agrupados por el id de su padre (se conserva el orden de jerarquía).
    $mpIdsPadres = [];
    foreach ($mpOpciones as $o) {
        if ($o->opcion_padre === null) {
            $mpIdsPadres[(string) $o->id] = true;
        }
    }
    $mpHijos = [];
    foreach ($mpOpciones as $o) {
        if ($o->opcion_padre !== null && isset($mpIdsPadres[(string) $o->opcion_padre])) {
            $mpHijos[(string) $o->opcion_padre][] = $o;
        }
    }

    $mpSegmento = request()->segment(1);
    $mpEsActiva = function ($o) use ($mpSegmento) {
        return !empty($o->url) && $o->url === $mpSegmento;
    };
    $mpHref = function ($o) {
        return '/' . $o->url . '/' . base64_encode($o->id);
    };
    $mpIconoActivo = '';
    foreach ($mpOpciones as $o) {
        if ($o->opcion_padre !== null && $mpEsActiva($o)) {
            $mpIconoActivo = $o->icono;
        }
    }
    if (request()->is('informes*')) {
        $mpIconoActivo = 'fa fa-file-excel-o';
    } elseif (request()->is('cambiar_password*')) {
        $mpIconoActivo = 'fa fa-lock';
    }
    $mpInformesActivo = request()->is('informes*');
    $mpInicioActivo = request()->is('profile');

    $mpFlecha = '<svg class="menu-flecha" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>';
@endphp
<nav class="menu" id="menu-principal" aria-label="Men&uacute; principal" data-icono="{{ $mpIconoActivo }}">
    <div class="contenedor menu-barra">
        <button type="button" class="menu-hamburguesa" aria-controls="menu-lista" aria-expanded="false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
            <span>Men&uacute;</span>
        </button>
        <ul class="menu-fila" id="menu-lista">
            <li class="menu-item">
                <a class="menu-enlace {{ $mpInicioActivo ? 'activo' : '' }}" href="/profile" {!! $mpInicioActivo ? 'aria-current="page"' : '' !!}><i class="fa fa-home" aria-hidden="true"></i><span>Inicio</span></a>
            </li>
            @foreach ($mpOpciones as $padre)
                @if ($padre->opcion_padre === null)
                    @php $hijos = $mpHijos[(string) $padre->id] ?? []; @endphp
                    @if (count($hijos) > 0)
                        @php $padreActivo = collect($hijos)->contains(function ($h) use ($mpEsActiva) { return $mpEsActiva($h); }); @endphp
                        <li class="menu-item menu-padre paipa-desplegable {{ $padreActivo ? 'activo' : '' }}" id="menu-padre-{{ $padre->id }}">
                            <button type="button" class="menu-enlace {{ $padreActivo ? 'activo' : '' }}" data-paipa-toggle aria-haspopup="true" aria-expanded="false">
                                <i class="{{ $padre->icono }}" aria-hidden="true"></i><span>{!! $padre->titulo !!}</span>{!! $mpFlecha !!}
                            </button>
                            <ul class="submenu paipa-desplegable-lista">
                                @foreach ($hijos as $hijo)
                                    <li>
                                        <a class="submenu-enlace {{ $mpEsActiva($hijo) ? 'activo' : '' }}" href="{{ $mpHref($hijo) }}" {!! $mpEsActiva($hijo) ? 'aria-current="page"' : '' !!}><i class="{{ $hijo->icono }}" aria-hidden="true"></i><span>{!! $hijo->titulo !!}</span></a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @elseif (!empty($padre->url))
                        {{-- Padre sin hijos pero con enlace propio. --}}
                        <li class="menu-item">
                            <a class="menu-enlace {{ $mpEsActiva($padre) ? 'activo' : '' }}" href="{{ $mpHref($padre) }}"><i class="{{ $padre->icono }}" aria-hidden="true"></i><span>{!! $padre->titulo !!}</span></a>
                        </li>
                    @endif
                @elseif (!isset($mpIdsPadres[(string) $padre->opcion_padre]))
                    {{-- Opción cuyo padre no está entre los permisos: queda como enlace suelto. --}}
                    <li class="menu-item">
                        <a class="menu-enlace {{ $mpEsActiva($padre) ? 'activo' : '' }}" href="{{ $mpHref($padre) }}"><i class="{{ $padre->icono }}" aria-hidden="true"></i><span>{!! $padre->titulo !!}</span></a>
                    </li>
                @endif
            @endforeach
            <li class="menu-item menu-padre paipa-desplegable {{ $mpInformesActivo ? 'activo' : '' }}" id="menu-padre-informes">
                <button type="button" class="menu-enlace {{ $mpInformesActivo ? 'activo' : '' }}" data-paipa-toggle aria-haspopup="true" aria-expanded="false">
                    <i class="ti-pie-chart" aria-hidden="true"></i><span>Informes</span>{!! $mpFlecha !!}
                </button>
                <ul class="submenu paipa-desplegable-lista">
                    <li>
                        <a class="submenu-enlace {{ $mpInformesActivo ? 'activo' : '' }}" href="/informes" {!! $mpInformesActivo ? 'aria-current="page"' : '' !!}><i class="fa fa-file-excel-o" aria-hidden="true"></i><span>Recaudo Predial y CAR</span></a>
                    </li>
                </ul>
            </li>
        </ul>
    </div>
</nav>
