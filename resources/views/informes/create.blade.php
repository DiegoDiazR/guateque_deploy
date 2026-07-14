@extends('theme.default')

@section('content')

@push('scripts')
    <script>
        $(document).ready(function() {
            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                todayHighlight: true
            });

            $('#generar_informe_recaudo').off('click').on('click', function() {
                var fechaInicial = $('#fecha_inicial').val();
                var fechaFinal = $('#fecha_final').val();
                var bancoInicial = $('#banco_inicial').val();
                var bancoFinal = $('#banco_final').val();

                if (!fechaInicial || !fechaFinal) {
                    swal("Error", "Debe seleccionar la fecha inicial y final", "error");
                    return;
                }

                if (!bancoInicial || !bancoFinal) {
                    swal("Error", "Debe seleccionar los bancos", "error");
                    return;
                }

                var url = '/export-excel-recaudo-car/' + fechaInicial + '/' + fechaFinal + '/' + bancoInicial + '/' + bancoFinal;
                window.open(url, '_blank');
            });
        });
    </script>
@endpush

<div class="container-fluid">
    <div class="row bg-title">
        <div class="col-lg-5 col-md-8 col-sm-12 col-xs-12">
            <h4 class="page-title">Informes de Recaudo</h4>
        </div>
        <div class="col-lg-7 col-md-4 col-sm-12 col-xs-12">
            <ol class="breadcrumb">
                <li class="active">{{ Session::get('desc_role') }}</li>
            </ol>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div class="white-box">
                <section>
                    <div class="sttabs tabs-style-bar">
                        <nav>
                            <ul>
                                <li class="tab-current"><a href="#section-bar-1" class="sticon ti-pie-chart"><span>Generar Informe de Recaudo Predial y CAR</span></a></li>
                            </ul>
                        </nav>
                        <div class="content-wrap">
                            <section id="section-bar-1" class="content-current">
                                <div class="panel panel-inverse">
                                    <div class="panel-heading"><i class="fa fa-file-excel-o"></i>&nbsp;&nbsp;Par&aacute;metros del Informe</div>
                                    <div class="panel-wrapper collapse in" aria-expanded="true">
                                        <div class="panel-body">
                                            <div class="form-body">
                                                <div class="row">
                                                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                                                        <div class="form-group">
                                                            <label class="control-label">Fecha Inicial:</label>
                                                            <input type="text" id="fecha_inicial" class="form-control datepicker" autocomplete="off" placeholder="yyyy-mm-dd">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                                                        <div class="form-group">
                                                            <label class="control-label">Fecha Final:</label>
                                                            <input type="text" id="fecha_final" class="form-control datepicker" autocomplete="off" placeholder="yyyy-mm-dd">
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                                                        <div class="form-group">
                                                            <label class="control-label">Banco Inicial:</label>
                                                            <select id="banco_inicial" class="form-control">
                                                                <option value="1">Seleccione o deje por defecto para todos</option>
                                                                @foreach($bancos as $banco)
                                                                    <option value="{{ $banco->id }}" {{ $banco->id == 1 ? 'selected' : '' }}>{{ $banco->nombre }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
                                                        <div class="form-group">
                                                            <label class="control-label">Banco Final:</label>
                                                            <select id="banco_final" class="form-control">
                                                                <option value="999">Seleccione o deje por defecto para todos</option>
                                                                @foreach($bancos as $banco)
                                                                    <option value="{{ $banco->id }}">{{ $banco->nombre }}</option>
                                                                @endforeach
                                                                <option value="999" selected>Último banco (Todos)</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-actions text-right" style="margin-top: 20px;">
                                                <button id="generar_informe_recaudo" type="button" class="btn btn-success"><i class="fa fa-file-excel-o"></i> Generar Excel</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>
@endsection
