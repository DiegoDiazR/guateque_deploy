@extends('theme.default')
@section('content')
<div class="container-fluid">
    <div class="row bg-title">
        <div class="col-lg-5 col-md-8 col-sm-12 col-xs-12">
            <h4 class="page-title">Inicio</h4>
        </div>
        <div class="col-lg-7 col-md-4 col-sm-12 col-xs-12">
            <ol class="breadcrumb">
                <li class="active">{{ Session::get('desc_role') }}</li>
            </ol>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="white-box bienvenida">
                <p class="ceja ceja-oscura">{{ config('municipio.secretaria') }} &middot; {{ config('municipio.ciudad') }}</p>
                <h2>Bienvenido(a), {{ mb_convert_case(mb_strtolower((string) Session::get('username')), MB_CASE_TITLE) }}</h2>
                <p class="texto-tenue">Elija una opci&oacute;n del men&uacute; o use los accesos directos de esta p&aacute;gina.</p>
            </div>
            @include('theme.accesos')
        </div>
    </div>
</div>
@endsection
