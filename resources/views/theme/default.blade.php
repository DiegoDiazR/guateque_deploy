<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    {{-- La salida por inactividad la maneja site.js (avisa antes y cuenta desde la última operación), no un meta refresh. --}}
    @if ($mpEscudo = \App\Support\Municipio::escudoUrl())<link rel="icon" type="image/png" href="{{ $mpEscudo }}">@endif
    <title>{{ \App\Support\Municipio::perfil()['sistema'] }} | {{ \App\Support\Municipio::nombre() }}</title>
    {{-- CSS comunes en un solo archivo (lista en config/theme_assets.php; se arma con php scripts/build_assets.php).
         Los plugins de una sola pantalla se agregan con @push('styles') en esa vista. --}}
    @include('theme.assets_css')

    {{-- Hoja de estilos del sistema (va al final para ganar sobre Bootstrap/Pixel Admin) y colores del municipio (App\Support\Municipio) --}}
    <link href="{!! asset('theme/css/paipa.css') !!}?v={{ @filemtime(public_path('theme/css/paipa.css')) ?: 1 }}" rel="stylesheet">
    @include('theme.identidad')
</head>

<body class="paipa" data-sesion-minutos="{{ intval(config('session.lifetime')) }}">
    <!-- ============================================================== -->
    <!-- Preloader -->
    <!-- ============================================================== -->
    <div class="preloader">
        <svg class="circular" viewBox="25 25 50 50">
            <circle class="path" cx="50" cy="50" r="20" fill="none" stroke-width="2" stroke-miterlimit="10" />
        </svg>
    </div>
    <!-- ============================================================== -->
    <!-- Wrapper -->
    <!-- ============================================================== -->
    <a class="saltar" href="#contenido">Saltar al contenido</a>
    <div id="wrapper">
        {{-- Encabezado (identidad del municipio, cuenta del usuario) y menú horizontal --}}
        @include('theme.header')
        {{-- Botones de tarea de cada pantalla (@section('buttons')): paipa.js los lleva a la franja del título --}}
        @php $mpBotonesTarea = trim($__env->yieldContent('buttons')); @endphp
        <main id="contenido" class="pagina-paipa" tabindex="-1">
            @if ($mpBotonesTarea !== '')
            <ul id="buttonsTareas" class="barra-tareas">
                @yield('buttons')
            </ul>
            @endif
            @yield('content')
            @yield('modales')
            @yield('resolucion')
        </main>
        @include('theme.footer', ['compacto' => true])
    </div>
    <!-- End Wrapper -->
    <!-- ============================================================== -->
    <!-- ============================================================== -->
    <!-- All Jquery -->
    <!-- ============================================================== -->
    {{-- JS comunes en un solo archivo (lista en config/theme_assets.php; se arma con php scripts/build_assets.php).
         Los plugins de una sola pantalla se agregan con @push('scripts') en esa vista. --}}
    @include('theme.assets_js')
    <script src="{!! asset('theme/js/customjs/site.js') !!}?v={{ @filemtime(public_path('theme/js/customjs/site.js')) ?: 1 }}"></script>
    <!-- Menú horizontal, cuenta y franja del título -->
    <script src="{!! asset('theme/js/customjs/paipa.js') !!}?v={{ @filemtime(public_path('theme/js/customjs/paipa.js')) ?: 1 }}"></script>
    @stack('scripts')
</body>

</html>
