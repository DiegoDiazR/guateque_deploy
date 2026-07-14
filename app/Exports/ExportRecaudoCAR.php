<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

class ExportRecaudoCAR implements FromView, WithTitle
{
    protected $fechaInicial;
    protected $fechaFinal;
    protected $bancoInicial;
    protected $bancoFinal;

    public function __construct(string $fechaInicial, string $fechaFinal, string $bancoInicial, string $bancoFinal) {
        $this->fechaInicial = $fechaInicial;
        $this->fechaFinal = $fechaFinal;
        $this->bancoInicial = intval($bancoInicial);
        $this->bancoFinal = intval($bancoFinal);
    }

    public function title(): string {
        return 'Recaudo Predial y CAR';
    }

    public function view(): View {
        $parametro_logo = DB::table('parametros')->select('valor')->where('nombre', 'logo')->first();
        $parametro_nit = DB::table('parametros')->select('valor')->where('nombre', 'nit')->first();
        $parametro_alcaldia = DB::table('parametros')->select('valor')->where('nombre', 'alcaldia')->first();
        $parametro_direccion = DB::table('parametros')->select('valor')->where('nombre', 'direccion')->first();
        $parametro_ubicacion = DB::table('parametros')->select('valor')->where('nombre', 'ubicacion')->first();

        // Join to get identificacion
        $registros = DB::table('View_predial_facturado')
            ->leftJoin('predios', 'View_predial_facturado.id_predio', '=', 'predios.id')
            ->leftJoin('predios_propietarios', function($join) {
                $join->on('predios.id', '=', 'predios_propietarios.id_predio')
                     ->where('predios_propietarios.jerarquia', '=', '001');
            })
            ->leftJoin('propietarios', 'predios_propietarios.id_propietario', '=', 'propietarios.id')
            ->leftJoin('bancos', 'View_predial_facturado.id_banco', '=', 'bancos.id')
            ->select(
                'View_predial_facturado.ultimo_anio',
                'View_predial_facturado.factura_pago',
                'View_predial_facturado.codigo_predio',
                'View_predial_facturado.nombre_propietario',
                'propietarios.identificacion as identificacion_propietario',
                'bancos.nombre_banco as nombre_banco',
                'View_predial_facturado.fechapago',
                'View_predial_facturado.predialanoactual',
                'View_predial_facturado.descuentopredial',
                'View_predial_facturado.predialanosanteriores',
                'View_predial_facturado.interesespredial',
                'View_predial_facturado.interesespredialanosanteriores',
                'View_predial_facturado.total_predial',
                'View_predial_facturado.caranoactual',
                'View_predial_facturado.descuentocar',
                'View_predial_facturado.caranoanteriores',
                'View_predial_facturado.interesescaractual',
                'View_predial_facturado.interesescaranteriores',
                'View_predial_facturado.totalcar',
                'View_predial_facturado.valor_facturado'
            )
            ->whereBetween('View_predial_facturado.fechapago', [$this->fechaInicial, $this->fechaFinal])
            ->whereBetween('View_predial_facturado.id_banco', [$this->bancoInicial, $this->bancoFinal])
            ->orderBy('bancos.nombre_banco', 'asc')
            ->orderBy('View_predial_facturado.fechapago', 'asc')
            ->orderBy('View_predial_facturado.codigo_predio', 'asc')
            ->orderBy('View_predial_facturado.ultimo_anio', 'asc')
            ->get();

        return view('exports.reporteRecaudoCAREXCEL', [
            'registros' => $registros,
            'logo' => $parametro_logo->valor ?? '',
            'nit' => $parametro_nit->valor ?? '',
            'alcaldia' => $parametro_alcaldia->valor ?? '',
            'direccion' => $parametro_direccion->valor ?? '',
            'ubicacion' => $parametro_ubicacion->valor ?? '',
            'fecha_inicial' => $this->fechaInicial,
            'fecha_final' => $this->fechaFinal
        ]);
    }
}
