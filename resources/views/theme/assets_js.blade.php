{{--
    JS comunes de todas las pantallas (jQuery, Bootstrap, DataTables, select2, ... en este orden).
    Usa el paquete public/theme/dist/app.<hash>.js (manifest.json) y, si no existe o
    ASSETS_BUNDLE=false, carga uno por uno los archivos de config/theme_assets.php.
    Los scripts de cada pantalla (@push('scripts')) se cargan despues, al final del <body>.
    Ver docs/RENDIMIENTO.md.
--}}
@php
    $__ta = config('theme_assets', []);
    $__tm = null;
    if (!empty($__ta['bundle']) && is_file(public_path('theme/dist/manifest.json'))) {
        $__tm = json_decode(file_get_contents(public_path('theme/dist/manifest.json')), true);
        if (empty($__tm['js']) || !is_file(public_path('theme/dist/' . $__tm['js']))) {
            $__tm = null;
        }
    }
@endphp
@if ($__tm)
    <script src="{{ asset('theme/dist/' . $__tm['js']) }}"></script>
@else
    @foreach (($__ta['js'] ?? []) as $__f)
    <script src="{{ asset($__f) }}"></script>
    @endforeach
@endif
