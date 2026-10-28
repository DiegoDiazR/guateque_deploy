<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="">
<meta name="author" content="">
@if ($mpEscudo = \App\Support\Municipio::escudoUrl())<link rel="icon" type="image/png" href="{{ $mpEscudo }}">@endif
<title>Iniciar sesi&oacute;n | {{ \App\Support\Municipio::nombre() }}</title>
<!-- Bootstrap Core CSS -->
<link href="{!! asset('theme/bootstrap/dist/css/bootstrap.min.css') !!}" rel="stylesheet">
{{-- Estilos del sistema (login, encabezado y pie) y colores del municipio (App\Support\Municipio) --}}
<link href="{!! asset('theme/css/paipa.css') !!}?v={{ @filemtime(public_path('theme/css/paipa.css')) ?: 1 }}" rel="stylesheet">
@include('theme.identidad')
<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
<!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
<![endif]-->
</head>
<body class="pagina-entrada">
<a class="saltar" href="#contenido">Saltar al contenido</a>
<header class="cabecera">
    <div class="contenedor cabecera-fila">
        @include('theme.marca', ['marcaEnlace' => '/'])
    </div>
</header>
<main id="contenido" tabindex="-1">
<section class="entrada">
    @php $mpFondo = \App\Support\Municipio::fondoUrl(); $mpMarcaAgua = $mpFondo ? null : \App\Support\Municipio::logoUrl(); @endphp
    @if ($mpFondo)
    <div class="entrada-foto" aria-hidden="true" style="background-image: url('{{ $mpFondo }}');"></div>
    @elseif ($mpMarcaAgua)
    <div class="entrada-foto entrada-marca-agua" aria-hidden="true" style="background-image: url('{{ $mpMarcaAgua }}');"></div>
    @endif
    <div class="contenedor entrada-fila">
        <div class="entrada-texto">
            <p class="ceja">{{ \App\Support\Municipio::ceja() }}</p>
            <h1>Impuesto Predial <em>Unificado</em></h1>
            <p class="bajada">Sistema de gesti&oacute;n para los funcionarios de la Alcald&iacute;a: predios, pagos, acuerdos de pago, tarifas e informes de recaudo.</p>
            <ul class="beneficios">
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18"/><path d="M5 21V10l7-6 7 6v11"/><path d="M10 21v-5h4v5"/></svg><span><strong>Predios y vigencias.</strong> Registro, tarifas, exenciones y prescripciones.</span></li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2.5" y="5" width="19" height="14" rx="2"/><path d="M2.5 10h19"/><path d="M6.5 15h4"/></svg><span><strong>Pagos y acuerdos.</strong> Recaudo, notas a factura y acuerdos de pago.</span></li>
                <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h4"/></svg><span><strong>Informes.</strong> Recaudo predial y CAR en Excel y PDF.</span></li>
            </ul>
        </div>

        <div class="tarjeta-acceso">
            <form class="formulario" id="loginform" method="post" action="{{ route('auth.checkLogin') }}" novalidate>
                @csrf
                <h2>Inicio de sesi&oacute;n</h2>
                <p class="formulario-ayuda">Ingrese con el correo y la contrase&ntilde;a de su usuario.</p>
                <div class="result">
                    @if(Session::get('fail'))
                        <div class="aviso aviso-error" role="alert">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3 2 20h20L12 3z"/><path d="M12 10v4"/><path d="M12 17h.01"/></svg>
                            <span>{!! Session::get('fail') !!}</span>
                        </div>
                    @endif
                </div>
                <div class="form-group campo">
                    <label for="email" class="control-label"><span>Correo electr&oacute;nico</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="usuario@correo.com" autocomplete="username" autofocus>
                    <span class="text-danger campo-error">@error('email') {{ $message }} @enderror</span>
                </div>
                <div class="form-group campo">
                    <label for="password" class="control-label"><span>Contrase&ntilde;a</span></label>
                    <span class="campo-clave">
                        <input type="password" id="password" name="password" placeholder="Su contrase&ntilde;a" autocomplete="current-password">
                        <button type="button" class="ver-clave" aria-label="Mostrar contrase&ntilde;a"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg></button>
                    </span>
                    <span class="text-danger campo-error">@error('password') {{ $message }} @enderror</span>
                </div>
                <button name="login" class="boton boton-primario boton-ancho" type="submit">Ingresar <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg></button>
            </form>
        </div>
    </div>
</section>
</main>
@include('theme.footer')
<!-- jQuery -->
<script src="{!! asset('theme/plugins/bower_components/jquery/dist/jquery.min.js') !!}"></script>
<!-- Bootstrap Core JavaScript -->
<script src="{!! asset('theme/bootstrap/dist/js/bootstrap.min.js') !!}"></script>
<!-- Laravel Javascript Validation -->
<script type="text/javascript" src="{{ asset('vendor/jsvalidation/js/jsvalidation.js')}}"></script>
{!! JsValidator::formRequest('App\Http\Requests\LoginFormRequest', '#loginform'); !!}
<script>
    // Mostrar u ocultar la contraseña
    $(function () {
        $('.ver-clave').on('click', function () {
            var input = $(this).closest('.campo-clave').find('input');
            var ver = input.attr('type') === 'password';
            input.attr('type', ver ? 'text' : 'password');
            $(this).attr('aria-label', ver ? 'Ocultar contraseña' : 'Mostrar contraseña');
        });
    });
</script>
</body>
</html>
