{{-- Colores del municipio (config/municipio.php) como variables CSS. Lo usan el layout y el login. --}}
@php
    $mpHex = function ($valor, $porDefecto) {
        return preg_match('/^#[0-9a-fA-F]{6}$/', (string) $valor) ? strtolower($valor) : $porDefecto;
    };
    $mpRgb = function ($hex) {
        return implode(', ', sscanf($hex, '#%02x%02x%02x'));
    };
    $mpMezcla = function ($a, $b, $t) {
        $x = sscanf($a, '#%02x%02x%02x');
        $y = sscanf($b, '#%02x%02x%02x');
        $c = [];
        for ($i = 0; $i < 3; $i++) {
            $c[] = (int) round($x[$i] * (1 - $t) + $y[$i] * $t);
        }
        return sprintf('#%02x%02x%02x', $c[0], $c[1], $c[2]);
    };
    $mpPrimario = $mpHex(config('municipio.color'), '#1fa49d');
    $mpOscuro = $mpHex(config('municipio.color_oscuro'), '#17756f');
    $mpOro = $mpHex(config('municipio.color_acento'), '#c99a2e');
@endphp
<meta name="theme-color" content="{{ $mpOscuro }}">
<style>
    :root {
        --primario: {{ $mpPrimario }};
        --primario-rgb: {{ $mpRgb($mpPrimario) }};
        --primario-oscuro: {{ $mpOscuro }};
        --profundo: {{ $mpOscuro }};
        --profundo-rgb: {{ $mpRgb($mpOscuro) }};
        --suave: {{ $mpMezcla($mpPrimario, '#ffffff', 0.9) }};
        --suave-fuerte: {{ $mpMezcla($mpPrimario, '#ffffff', 0.78) }};
        --oro: {{ $mpOro }};
        --oro-claro: {{ $mpMezcla($mpOro, '#ffffff', 0.38) }};
    }
</style>
