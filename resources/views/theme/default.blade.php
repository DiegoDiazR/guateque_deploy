<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <meta http-equiv="refresh" content="{{ intval(config('session.lifetime')) * 60 }};url=/">
    <link rel="icon" type="image/png" sizes="16x16" href="{!! asset('theme/plugins/images/favicon.png') !!}">
    <title>Plataforma - ERPSoft Predial</title>
    {{-- CSS comunes en un solo archivo (lista en config/theme_assets.php; se arma con php scripts/build_assets.php).
         Los plugins de una sola pantalla se agregan con @push('styles') en esa vista. --}}
    @include('theme.assets_css')
    {{-- sidebar-nav: lo usa el menu lateral de este commit; el rediseno (menu horizontal) lo deja de cargar --}}
    <link href="{!! asset('theme/plugins/bower_components/sidebar-nav/dist/sidebar-nav.min.css') !!}" rel="stylesheet">

</head>

<body class="fix-header">
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
    <div id="wrapper">
        <!-- ============================================================== -->
        <!-- Topbar header - style you can find in pages.scss -->
        <!-- ============================================================== -->
        <nav class="navbar navbar-default navbar-static-top m-b-0">
            @include('theme.header')
            {{-- @include('theme.header', ['template' => $template]) --}}
        </nav>
        <!-- End Top Navigation -->
        <!-- ============================================================== -->
        <!-- Left Sidebar - style you can find in sidebar.scss  -->
        <!-- ============================================================== -->
        <div class="navbar-default sidebar" role="navigation">
            @include('theme.sidebar')
        </div>
        <!-- ============================================================== -->
        <!-- End Left Sidebar -->
        <!-- ============================================================== -->
        <!-- ============================================================== -->
        <!-- Page Content -->
        <!-- ============================================================== -->
        <div id="page-wrapper">
            @yield('content')
            @yield('modales')
            @yield('resolucion')
            <footer class="footer text-center">
                <script>document.write(new Date().getFullYear())</script>
                &copy; ERPSoft Predial - Software Development Team
            </footer>
        </div>
        <!-- ============================================================== -->
        <!-- End Page Content -->
        <!-- ============================================================== -->
    </div>
    <!-- ============================================================== -->
    <!-- End Wrapper -->
    <!-- ============================================================== -->
    <!-- ============================================================== -->
    <!-- All Jquery -->
    <!-- ============================================================== -->
    {{-- JS comunes en un solo archivo (lista en config/theme_assets.php; se arma con php scripts/build_assets.php).
         Los plugins de una sola pantalla se agregan con @push('scripts') en esa vista. --}}
    @include('theme.assets_js')
    <script src="{!! asset('theme/js/customjs/site.js') !!}?v={{ @filemtime(public_path('theme/js/customjs/site.js')) ?: 1 }}"></script>
    @stack('scripts')
</body>

</html>
