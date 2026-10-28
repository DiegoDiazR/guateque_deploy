{{-- Páginas de error (401, 403, 404, 419, 429, 500, 503) con el estilo del sistema: encabezado con la marca del municipio, tarjeta
     central y pie. No depende de la base de datos ni de recursos externos: si la identidad no se puede leer, se usa un encabezado simple. --}}
@php
    try {
        $erMarca = view('theme.marca', ['marcaEnlace' => url('/')])->render();
        $erPie = view('theme.footer')->render();
        $erIdentidad = view('theme.identidad')->render();
        $erTitulo = \App\Support\Municipio::nombre();
    } catch (\Throwable $e) {
        $erMarca = '<span class="marca"><span class="marca-texto"><strong>Impuesto Predial Unificado</strong></span></span>';
        $erPie = '';
        $erIdentidad = '';
        $erTitulo = 'Impuesto Predial Unificado';
    }
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo') | {{ $erTitulo }}</title>
    <link href="{!! asset('theme/bootstrap/dist/css/bootstrap.min.css') !!}" rel="stylesheet">
    <link href="{!! asset('theme/css/paipa.css') !!}?v={{ @filemtime(public_path('theme/css/paipa.css')) ?: 1 }}" rel="stylesheet">
    {!! $erIdentidad !!}
</head>
<body class="pagina-entrada pagina-error">
<header class="cabecera">
    <div class="contenedor cabecera-fila">
        {!! $erMarca !!}
    </div>
</header>
<main id="contenido" tabindex="-1" class="error-pagina">
    <section class="error-tarjeta" role="alert">
        <p class="error-codigo" aria-hidden="true">@yield('codigo')</p>
        <h1>@yield('titulo')</h1>
        <p>@yield('mensaje')</p>
        <div class="error-acciones">
            <a class="boton boton-primario" href="{{ url('/') }}">Ir al inicio</a>
            <button class="boton boton-secundario" type="button" onclick="if (history.length > 1) { history.back(); } else { location.href = '{{ url('/') }}'; }">Volver atr&aacute;s</button>
        </div>
    </section>
</main>
{!! $erPie !!}
</body>
</html>
