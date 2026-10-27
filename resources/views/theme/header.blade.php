{{-- Encabezado del sistema: identidad del municipio (config/municipio.php), cuenta del usuario y menú horizontal. --}}
@php
    $mpNombres = (string) Session::get('username');
    $mpApellidos = (string) Session::get('userlastname');
    $mpNombreCompleto = mb_convert_case(mb_strtolower(trim($mpNombres . ' ' . $mpApellidos)), MB_CASE_TITLE);
    $mpIniciales = mb_strtoupper(mb_substr($mpNombres, 0, 1) . mb_substr($mpApellidos, 0, 1));
    $mpPrimerNombre = mb_convert_case(mb_strtolower($mpNombres), MB_CASE_TITLE);
@endphp
<header class="cabecera">
    <div class="contenedor cabecera-fila">
        <a class="marca" href="/profile" aria-label="{{ config('municipio.alcaldia') }}, inicio">
            <img class="marca-logo" src="{{ asset(config('municipio.logo')) }}" alt="{{ config('municipio.alcaldia') }}">
            <span class="marca-texto">
                <strong>{{ config('municipio.secretaria') }}</strong>
                <span>{{ config('municipio.sistema') }}</span>
            </span>
        </a>
        <div class="cabecera-derecha">
            <div class="cuenta paipa-desplegable">
                <button type="button" class="cuenta-chip" data-paipa-toggle aria-haspopup="true" aria-expanded="false" title="Mi cuenta">
                    <span class="cuenta-avatar" aria-hidden="true">{{ $mpIniciales ?: '?' }}</span>
                    <span class="cuenta-datos">
                        <span class="cuenta-nombre">{{ $mpPrimerNombre }}</span>
                        <span class="cuenta-doc">{{ Session::get('desc_role') }}</span>
                    </span>
                    <svg class="cuenta-flecha" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </button>
                <div class="cuenta-menu paipa-desplegable-lista" role="menu">
                    <div class="cuenta-menu-cabeza">
                        <strong>{{ $mpNombreCompleto }}</strong>
                        <span>{{ Session::get('useremail') }}</span>
                    </div>
                    <a role="menuitem" href="{{ route('auth.index_change_pass') }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
                        Cambiar contrase&ntilde;a
                    </a>
                    <a role="menuitem" href="{{ route('auth.logout') }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
                        Cerrar sesi&oacute;n
                    </a>
                </div>
            </div>
            <a class="boton-salir" href="{{ route('auth.logout') }}" title="Cerrar sesi&oacute;n">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
                <span>Salir</span>
            </a>
        </div>
    </div>
    @include('theme.menu')
</header>
