{{-- Marca del encabezado: escudo y nombre del municipio (App\Support\Municipio). La usan el layout, el login y la
     validación pública del QR. Variables opcionales: $marcaEnlace (si falta, va dentro de un <span>) y
     $marcaSubtitulo (reemplaza la segunda línea de texto). --}}
@php
    $mpNombre = \App\Support\Municipio::nombre();
    $mpPerfil = \App\Support\Municipio::perfil();
    $mpCompleto = \App\Support\Municipio::logoCompletoUrl(); // imagen que ya trae el nombre escrito (Paipa)
    $mpImagen = $mpCompleto ?: \App\Support\Municipio::logoUrl();
    $mpSecretaria = trim((string) ($mpPerfil['secretaria'] ?? ''));
    if ($mpCompleto) {
        $mpLinea1 = $mpSecretaria !== '' ? $mpSecretaria : $mpPerfil['sistema'];
        $mpLinea2 = $mpSecretaria !== '' ? $mpPerfil['sistema'] : '';
    } else {
        $mpLinea1 = $mpNombre;
        $mpLinea2 = $mpSecretaria !== '' ? $mpSecretaria : $mpPerfil['sistema'];
    }
    if (isset($marcaSubtitulo)) {
        $mpLinea2 = $marcaSubtitulo;
    }
    $mpEtiqueta = isset($marcaEnlace) ? 'a' : 'span';
@endphp
<{{ $mpEtiqueta }} class="marca {{ $mpCompleto ? '' : 'marca-escudo' }}"@if (isset($marcaEnlace)) href="{{ $marcaEnlace }}" aria-label="{{ $mpNombre }}, inicio"@endif>
    @if ($mpImagen)
        <img class="marca-logo" src="{{ $mpImagen }}" alt="{{ $mpCompleto ? $mpNombre : 'Escudo de ' . \App\Support\Municipio::nombreCorto() }}">
    @endif
    <span class="marca-texto">
        <strong>{{ $mpLinea1 }}</strong>
        @if ($mpLinea2 !== '')<span>{{ $mpLinea2 }}</span>@endif
    </span>
</{{ $mpEtiqueta }}>
