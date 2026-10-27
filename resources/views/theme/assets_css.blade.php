{{--
    CSS comunes de todas las pantallas.
    Usa el paquete public/theme/dist/app.<hash>.css (manifest.json) y, si no existe o
    ASSETS_BUNDLE=false, carga uno por uno los archivos de config/theme_assets.php.
    Despues van los estilos de cada pantalla (@push('styles')). Ver docs/RENDIMIENTO.md.
--}}
@php
    $__ta = config('theme_assets', []);
    $__tm = null;
    if (!empty($__ta['bundle']) && is_file(public_path('theme/dist/manifest.json'))) {
        $__tm = json_decode(file_get_contents(public_path('theme/dist/manifest.json')), true);
        if (empty($__tm['css']) || !is_file(public_path('theme/dist/' . $__tm['css']))) {
            $__tm = null;
        }
    }
@endphp
@if ($__tm)
    <link href="{{ asset('theme/dist/' . $__tm['css']) }}" rel="stylesheet">
@else
    @foreach (($__ta['css'] ?? []) as $__f)
    <link href="{{ asset($__f) }}" rel="stylesheet">
    @endforeach
@endif
    @stack('styles')
