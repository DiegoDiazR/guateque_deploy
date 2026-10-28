<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Http\Requests\ResolucionesIgacCreateFormRequest;
use App\Http\Requests\ResolucionesIgacUpdateFormRequest;
use App\Models\ResolucionIgac;
use App\Models\Opcion;

class ResolucionesIgacController extends Controller
{
    /** Columnas que la pantalla de resoluciones IGAC lista y guarda. */
    const COLUMNAS_PANTALLA = ['id', 'ano', 'resolucion', 'fecha', 'consecutivo', 'codigo', 'codigoanterior',
                               'tipo', 'tiporegistro', 'numeroorden', 'avaluoigac', 'area', 'nombre'];

    /** Filas por pagina en la lista. */
    const POR_PAGINA = 25;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, $id) {
        if (!$request->session()->exists('userid')) {
            return redirect('/');
        }
        $opcion = Opcion::where('id','=', base64_decode($id))->first();

        // La pantalla solo sirve si la tabla del municipio tiene las columnas que esta pantalla guarda y muestra
        // (hay bases donde resoluciones_igac es el archivo plano del IGAC, con otras columnas). Si no es asi se
        // muestra un aviso en vez de dejar caer la pagina.
        $tabla_compatible = Schema::hasTable('resoluciones_igac')
            && Schema::hasColumns('resoluciones_igac', self::COLUMNAS_PANTALLA);

        // La tabla puede tener decenas de miles de filas: se muestra por paginas y se busca en el servidor.
        $busqueda = trim((string) $request->query('q', ''));
        $resoluciones_igac = null;
        if ($tabla_compatible) {
            $consulta = DB::table('resoluciones_igac');
            if ($busqueda !== '') {
                $consulta->where(function ($donde) use ($busqueda) {
                    foreach (['ano', 'resolucion', 'codigo', 'codigoanterior', 'tipo', 'nombre'] as $columna) {
                        $donde->orWhere($columna, 'like', '%' . $busqueda . '%');
                    }
                });
            }
            $resoluciones_igac = $consulta->orderBy('id', 'desc')
                                          ->paginate(self::POR_PAGINA)
                                          ->appends($busqueda !== '' ? ['q' => $busqueda] : []);
        }

        $tab_current = 'li-section-bar-1';
        if ($request->has('page') || $busqueda !== '') {
            $tab_current = 'li-section-bar-2';
        }

        //$request->session()->put('search', '1');

        return view('ResolucionesIgac.create', ['opcion' => $opcion,
                                             'resoluciones_igac' => $resoluciones_igac,
                                             'tabla_compatible' => $tabla_compatible,
                                             'busqueda' => $busqueda,
                                             'tab_current' => $tab_current]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  ResolucionesIgacCreateFormRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ResolucionesIgacCreateFormRequest $request) {
        if (!$request->session()->exists('userid')) {
            return redirect('/');
        }

        $resolucion_igac = new ResolucionIgac();
        $resolucion_igac->ano = $request->ano;
        $resolucion_igac->resolucion = $request->resolucion;
        $resolucion_igac->fecha = $request->fecha;
        $resolucion_igac->consecutivo = $request->consecutivo;
        $resolucion_igac->codigo = $request->codigo;
        $resolucion_igac->codigoanterior = $request->codigoanterior;
        $resolucion_igac->tipo = $request->tipo;
        $resolucion_igac->tiporegistro = $request->tiporegistro;
        $resolucion_igac->numeroorden = $request->numeroorden;
        $resolucion_igac->avaluoigac = $request->avaluoigac;
        $resolucion_igac->area = $request->area;
        $resolucion_igac->nombre = $request->nombre;
        $query = $resolucion_igac->save();
        $tab_current = 'li-section-bar-1';

        if($query) {
            return back()->with(['success' => 'La informaci&oacute;n se guard&oacute; satisfactoriamente.', 'tab_current' => $tab_current]);
        }
        else {
            return back()->with(['fail' => 'No se pudo guardar la informaci&oacute;n. Intente nuevamente.', 'tab_current' => $tab_current]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  ResolucionesIgacUpdateFormRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function update(ResolucionesIgacUpdateFormRequest $request) {
        if (!$request->session()->exists('userid')) {
            return redirect('/');
        }

        $resolucion_igac = new ResolucionIgac();
        $resolucion_igac = ResolucionIgac::find($request->id_edit);

        $resolucion_igac->resolucion = $request->resolucion_edit;
        $query = $resolucion_igac->save();
        $tab_current = 'li-section-bar-2';

        if($query) {
            return back()->with(['success' => 'La informaci&oacute;n se actualiz&oacute; satisfactoriamente.', 'tab_current' => $tab_current]);
        }
        else {
            return back()->with(['fail' => 'No se pudo actualizar la informaci&oacute;n. Intente nuevamente.', 'tab_current' => $tab_current]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request)
    {
        if (!$request->session()->exists('userid')) {
            return redirect('/');
        }

        $countResolucionesIgac = DB::table('resoluciones_igac')
                            ->select(DB::raw('count(*) as cm_count')) // verificar si ya esta relacionada con otras tablas
                            ->where('id', $request->input_delete)->first();

        $tab_current = 'li-section-bar-2';
        $resolucion_igac = new ResolucionIgac();
        $resolucion_igac = ResolucionIgac::find($request->input_delete);

        //if($countResolucionesIgac->cm_count == 0) {
            $query = $resolucion_igac->delete();
            if($query) {
                return back()->with(['success' => 'El registro se elimin&oacute; satisfactoriamente.', 'tab_current' => $tab_current]);
            }
            else {
                return back()->with(['fail' => 'No se pudo eliminar la informaci&oacute;n. Intente nuevamente.', 'tab_current' => $tab_current]);
            }
        // }
        // else {
        //     return back()->with(['fail' => 'No se pudo eliminar la informaci&oacute;n. La clase de mutaci&oacute;n <b>' . $tipo_predio->nombre . ' (' . $tipo_predio->codigo . ')</b> ya posee informaci&oacute;n asociada.', 'tab_current' => $tab_current]);
        // }
    }


}
