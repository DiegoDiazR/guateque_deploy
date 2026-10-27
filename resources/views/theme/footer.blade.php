{{-- Pie de página con los datos de contacto del municipio (config/municipio.php). Lo usan el layout y el login. --}}
<footer class="pie" id="contacto">
    <div class="contenedor pie-rejilla">
        <div class="pie-marca">
            <img src="{{ asset(config('municipio.escudo')) }}" alt="" width="48" height="56">
            <div>
                <strong>{{ config('municipio.alcaldia') }}</strong>
                <span>{{ config('municipio.secretaria') }}</span>
                @if (config('municipio.dependencia'))<span>{{ config('municipio.dependencia') }}</span>@endif
            </div>
        </div>
        <div>
            <h2 class="pie-titulo">Cont&aacute;ctenos</h2>
            <ul class="pie-contacto">
                @if (config('municipio.telefono'))
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/></svg><a href="tel:+57{{ preg_replace('/\D/', '', (string) config('municipio.telefono')) }}">{{ config('municipio.telefono') }}</a></li>
                @endif
                @if (config('municipio.correo'))
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="4.5" width="19" height="15" rx="2"/><path d="m3 6 9 7 9-7"/></svg><a href="mailto:{{ config('municipio.correo') }}">{{ config('municipio.correo') }}</a></li>
                @endif
                @if (config('municipio.direccion'))
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg><span>{{ config('municipio.direccion') }}, {{ config('municipio.ciudad') }}, {{ config('municipio.departamento') }}</span></li>
                @endif
                @if (config('municipio.horario'))
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><span>{{ config('municipio.horario') }}</span></li>
                @endif
            </ul>
        </div>
        <div>
            <h2 class="pie-titulo">Enlaces</h2>
            <ul class="pie-enlaces">
                @if (config('municipio.portal'))
                <li><a href="{{ config('municipio.portal') }}" target="_blank" rel="noopener">{{ config('municipio.portal_texto') ?: 'Portal tributario' }}</a></li>
                @endif
                @foreach ((array) config('municipio.enlaces', []) as $texto => $url)
                <li><a href="{{ $url }}" target="_blank" rel="noopener">{{ $texto }}</a></li>
                @endforeach
                <li><a href="https://www.gov.co/" target="_blank" rel="noopener">Portal del Estado colombiano GOV.CO</a></li>
            </ul>
        </div>
    </div>
    <div class="pie-legal">
        <div class="contenedor pie-legal-fila">
            <span>&copy; {{ date('Y') }} {{ config('municipio.alcaldia') }} &middot; {{ config('municipio.sistema') }}</span>
            <span>Desarrollado por {{ config('municipio.desarrollador') }}</span>
        </div>
    </div>
</footer>
