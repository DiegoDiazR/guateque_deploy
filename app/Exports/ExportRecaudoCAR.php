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
        $ano = substr($this->fechaInicial, 0, 4);

        $registros = DB::table('predios_pagos')
            ->join('predios', 'predios_pagos.id_predio', '=', 'predios.id')
            ->join('bancos', 'predios_pagos.id_banco', '=', 'bancos.id')
            ->leftJoin('predios_propietarios', function($join) {
                $join->on('predios.id', '=', 'predios_propietarios.id_predio')
                     ->where('predios_propietarios.jerarquia', '=', '001');
            })
            ->leftJoin('propietarios', 'predios_propietarios.id_propietario', '=', 'propietarios.id')
            ->where('predios_pagos.pagado', '!=', 0)
            ->where('predios_pagos.anulada', 0)
            ->where('predios_pagos.acuerdo', 0)
            ->whereBetween('predios_pagos.fecha_pago', [$this->fechaInicial, $this->fechaFinal])
            ->whereBetween('predios_pagos.id_banco', [$this->bancoInicial, $this->bancoFinal])
            ->selectRaw("
                predios_pagos.ultimo_anio,
                predios_pagos.factura_pago,
                predios.codigo_predio,
                propietarios.nombre as nombre_propietario,
                propietarios.identificacion as identificacion_propietario,
                bancos.nombre_banco as nombre_banco,
                predios_pagos.fecha_pago as fechapago,
                
                CASE WHEN predios_pagos.ultimo_anio = {$ano} THEN predios_pagos.valor_concepto1 ELSE 0 END as predialanoactual,
                CASE WHEN predios_pagos.ultimo_anio < {$ano} THEN predios_pagos.valor_concepto1 ELSE 0 END as predialanosanteriores,
                
                predios_pagos.valor_concepto13 as descuentopredial,
                
                CASE WHEN predios_pagos.ultimo_anio = {$ano} THEN predios_pagos.valor_concepto2 ELSE 0 END as interesespredial,
                CASE WHEN predios_pagos.ultimo_anio < {$ano} THEN predios_pagos.valor_concepto2 ELSE 0 END as interesespredialanosanteriores,
                
                (predios_pagos.valor_concepto1 + predios_pagos.valor_concepto2 + predios_pagos.valor_concepto13) as total_predial,
                
                CASE WHEN predios_pagos.ultimo_anio = {$ano} THEN (predios_pagos.valor_concepto3 + predios_pagos.valor_concepto21) ELSE 0 END as caranoactual,
                CASE WHEN predios_pagos.ultimo_anio < {$ano} THEN (predios_pagos.valor_concepto3 + predios_pagos.valor_concepto21) ELSE 0 END as caranoanteriores,
                
                CASE WHEN predios_pagos.ultimo_anio = {$ano} THEN predios_pagos.valor_concepto4 ELSE 0 END as interesescaractual,
                CASE WHEN predios_pagos.ultimo_anio < {$ano} THEN predios_pagos.valor_concepto4 ELSE 0 END as interesescaranteriores,
                
                predios_pagos.valor_concepto15 as porcentaje_car,
                
                (predios_pagos.valor_concepto3 + predios_pagos.valor_concepto21 + predios_pagos.valor_concepto4 + predios_pagos.valor_concepto15) as totalcar,
                
                predios_pagos.valor_pago as valor_facturado
            ")
            ->orderBy('bancos.nombre_banco', 'asc')
            ->orderBy('predios_pagos.fecha_pago', 'asc')
            ->orderBy('predios.codigo_predio', 'asc')
            ->orderBy('predios_pagos.ultimo_anio', 'asc')
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
