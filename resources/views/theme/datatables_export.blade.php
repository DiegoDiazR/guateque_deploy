{{--
    OPCIONAL. Botones de exportacion de DataTables (Excel, CSV, PDF, imprimir) y seleccion de filas.

    Hoy NINGUNA pantalla los usa (ningun DataTable del sistema define "buttons" ni "dom: B"), asi que
    ya no se cargan en todas las paginas. Antes venian de cdn.datatables.net / cdnjs / cdn.rawgit.com
    en CADA carga; ahora estan como archivos locales en
    public/theme/plugins/bower_components/datatables-export/.

    Para usarlos en una pantalla, dentro de su @section('content'):

        @include('theme.datatables_export')

    y en el JS de esa pantalla, por ejemplo:

        $('#miTabla').DataTable({ dom: 'Bfrtip', buttons: ['excelHtml5', 'csvHtml5', 'pdfHtml5', 'print'] });
--}}
@push('styles')
    <link href="{!! asset('theme/plugins/bower_components/datatables-export/css/buttons.dataTables.min.css') !!}" rel="stylesheet" type="text/css" />
    <link href="{!! asset('theme/plugins/bower_components/datatables-export/css/select.bootstrap.min.css') !!}" rel="stylesheet" type="text/css" />
    <link href="{!! asset('theme/plugins/bower_components/datatables-export/css/select.dataTables.min.css') !!}" rel="stylesheet" type="text/css" />
@endpush
@push('scripts')
    <script src="{!! asset('theme/plugins/bower_components/datatables-export/js/dataTables.buttons.min.js') !!}"></script>
    <script src="{!! asset('theme/plugins/bower_components/datatables-export/js/buttons.flash.min.js') !!}"></script>
    <script src="{!! asset('theme/plugins/bower_components/datatables-export/js/jszip.min.js') !!}"></script>
    <script src="{!! asset('theme/plugins/bower_components/datatables-export/js/pdfmake.min.js') !!}"></script>
    <script src="{!! asset('theme/plugins/bower_components/datatables-export/js/vfs_fonts.js') !!}"></script>
    <script src="{!! asset('theme/plugins/bower_components/datatables-export/js/buttons.html5.min.js') !!}"></script>
    <script src="{!! asset('theme/plugins/bower_components/datatables-export/js/buttons.print.min.js') !!}"></script>
    <script src="{!! asset('theme/plugins/bower_components/datatables-export/js/dataTables.select.min.js') !!}"></script>
    <script src="{!! asset('theme/plugins/bower_components/datatables-export/js/select.bootstrap.min.js') !!}"></script>
    <script src="{!! asset('theme/plugins/bower_components/datatables-export/js/select.dataTables.min.js') !!}"></script>
@endpush
