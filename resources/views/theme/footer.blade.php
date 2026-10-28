{{-- Pie de página con los datos del municipio (App\Support\Municipio; contacto y enlaces de config/municipio.php).
     Solo se imprime lo que existe. Lo usan el layout y el login. --}}
@php
    $mpPerfil = \App\Support\Municipio::perfil();
    $mpNombre = \App\Support\Municipio::nombre();
    $mpEscudo = \App\Support\Municipio::escudoUrl();
    $mpContacto = \App\Support\Municipio::contacto();
    $mpSecretaria = trim((string) ($mpPerfil['secretaria'] ?? ''));
    $mpDependencia = trim((string) ($mpPerfil['dependencia'] ?? ''));
    $mpIconos = [
        'telefono'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/></svg>',
        'correo'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="4.5" width="19" height="15" rx="2"/><path d="m3 6 9 7 9-7"/></svg>',
        'direccion' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>',
        'horario'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>',
    ];
@endphp
<footer class="pie" id="contacto">
    <div class="contenedor pie-rejilla{{ count($mpContacto) ? '' : ' pie-sin-contacto' }}">
        <div class="pie-marca">
            @if ($mpEscudo)<img src="{{ $mpEscudo }}" alt="" height="56">@endif
            <div>
                <strong>{{ $mpNombre }}</strong>
                @if ($mpSecretaria !== '')<span>{{ $mpSecretaria }}</span>@endif
                @if ($mpDependencia !== '')<span>{{ $mpDependencia }}</span>@endif
            </div>
        </div>
        @if (count($mpContacto))
        <div>
            <h2 class="pie-titulo">Cont&aacute;ctenos</h2>
            <ul class="pie-contacto">
                @foreach ($mpContacto as $mpFila)
                <li>{!! $mpIconos[$mpFila['tipo']] !!}@if ($mpFila['enlace'])<a href="{{ $mpFila['enlace'] }}">{{ $mpFila['texto'] }}</a>@else<span>{{ $mpFila['texto'] }}</span>@endif</li>
                @endforeach
            </ul>
        </div>
        @endif
        <div>
            <h2 class="pie-titulo">Enlaces</h2>
            <ul class="pie-enlaces">
                @if (!empty($mpPerfil['portal']))
                <li><a href="{{ $mpPerfil['portal'] }}" target="_blank" rel="noopener">{{ !empty($mpPerfil['portal_texto']) ? $mpPerfil['portal_texto'] : 'Portal tributario' }}</a></li>
                @endif
                @foreach ($mpPerfil['enlaces'] as $mpTexto => $mpUrl)
                <li><a href="{{ $mpUrl }}" target="_blank" rel="noopener">{{ $mpTexto }}</a></li>
                @endforeach
                <li><a href="https://www.gov.co/" target="_blank" rel="noopener">Portal del Estado colombiano GOV.CO</a></li>
            </ul>
        </div>
    </div>
    <div class="pie-legal">
        <div class="contenedor pie-legal-fila">
            <span>&copy; {{ date('Y') }} {{ $mpNombre }} &middot; {{ $mpPerfil['sistema'] }}</span>
            <span>Desarrollado por {{ $mpPerfil['desarrollador'] }}</span>
        </div>
    </div>
</footer>
