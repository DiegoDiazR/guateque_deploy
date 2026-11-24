<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Apoyo de la factura de Paipa (resources/views/predios/facturaPDF_pai.blade.php).
 *
 * La hoja oficial lleva un solo total y un solo "pague hasta": los del descuento que aplica hoy o, si ya
 * no queda ninguno, los del día en que se imprime. Aquí viven esa selección, el valor en letras y la
 * tasa de mora que se imprime. Ningún método lanza excepciones: un PDF no debe fallar por un dato opcional.
 */
class FacturaPaipa
{
    /**
     * Deja un solo cupón: el primero cuya fecha límite no ha pasado. Si todas pasaron, el primero con la
     * fecha de hoy (el valor ya viene calculado a hoy por el procedimiento de cálculo).
     *
     * @param array|Collection $valores     valor de cada cupón
     * @param array|Collection $fechas      fecha límite de cada cupón (Y-m-d)
     * @param array|Collection $porcentajes porcentaje de descuento de cada cupón
     * @return array [valores, fechas, porcentajes], cada uno con un solo elemento
     */
    public static function cuponVigente($valores, $fechas, $porcentajes, $hoy = null)
    {
        $valores = self::lista($valores);
        $fechas = self::lista($fechas);
        $porcentajes = self::lista($porcentajes);
        $hoy = $hoy ?: Carbon::now()->toDateString();

        $claves = array_keys($valores);
        $elegido = $claves ? $claves[0] : 0;
        $vigente = false;
        foreach ($claves as $i) {
            $f = isset($fechas[$i]) ? substr((string) $fechas[$i], 0, 10) : '';
            if ($f !== '' && $f >= $hoy) {
                $elegido = $i;
                $vigente = true;
                break;
            }
        }

        return [
            [0 => isset($valores[$elegido]) ? $valores[$elegido] : 0],
            [0 => $vigente ? substr((string) $fechas[$elegido], 0, 10) : $hoy],
            [0 => isset($porcentajes[$elegido]) ? $porcentajes[$elegido] : 0],
        ];
    }

    private static function lista($v)
    {
        if ($v instanceof Collection) {
            return $v->all();
        }
        return is_array($v) ? $v : [];
    }

    /**
     * Tasa de mora anual (en %) que está en la tabla tasas_interes para el mes de la fecha o, si ese mes
     * aún no está cargado, para el último mes anterior. La tabla guarda la tasa anual dividida entre 12
     * (tasa_mensual) y entre 360 (tasa_diaria). Null si no hay dato.
     */
    public static function tasaMoraAnual($fecha = null)
    {
        try {
            $f = $fecha ?: Carbon::now();
            $fila = DB::table('tasas_interes')
                ->where(function ($q) use ($f) {
                    $q->where('anio', '<', $f->year)
                      ->orWhere(function ($q2) use ($f) {
                          $q2->where('anio', $f->year)->where('mes', '<=', $f->month);
                      });
                })
                ->orderByDesc('anio')
                ->orderByDesc('mes')
                ->first();
            if (!$fila || (float) $fila->tasa_mensual <= 0) {
                return null;
            }
            return round((float) $fila->tasa_mensual * 12 * 100, 2);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Fecha d/m/Y, o '' si el dato no es una fecha. El controlador ya entrega la fecha del último pago como
     * d/m/Y (o "N/D" cuando no hay); también se acepta Y-m-d.
     */
    public static function fecha($valor)
    {
        $v = trim((string) $valor);
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $v)) {
            return $v;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}/', $v)) {
            return '';
        }
        try {
            return Carbon::parse($v)->format('d/m/Y');
        } catch (\Throwable $e) {
            return '';
        }
    }

    /** "24,76" para la tasa en %. */
    public static function porcentaje($valor)
    {
        return number_format((float) $valor, 2, ',', '.');
    }

    /** Zona del predio para la línea de dirección: "Zona urbana". */
    public static function zona($descripcion)
    {
        $d = trim((string) $descripcion);
        if ($d === '') {
            return '';
        }
        $mapa = ['urbano' => 'urbana', 'rural' => 'rural', 'sub urbano' => 'suburbana', 'expansion' => 'de expansión'];
        $k = strtolower($d);
        return 'Zona ' . (isset($mapa[$k]) ? $mapa[$k] : $k);
    }

    // ---------------------------------------------------------------------------------------------
    // Valor en letras: "DOSCIENTOS VEINTIDÓS MIL TRESCIENTOS PESOS"
    // ---------------------------------------------------------------------------------------------

    private static $unidades = [
        '', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE', 'DIEZ', 'ONCE', 'DOCE',
        'TRECE', 'CATORCE', 'QUINCE', 'DIECISÉIS', 'DIECISIETE', 'DIECIOCHO', 'DIECINUEVE', 'VEINTE', 'VEINTIUNO',
        'VEINTIDÓS', 'VEINTITRÉS', 'VEINTICUATRO', 'VEINTICINCO', 'VEINTISÉIS', 'VEINTISIETE', 'VEINTIOCHO', 'VEINTINUEVE',
    ];
    private static $decenas = [3 => 'TREINTA', 4 => 'CUARENTA', 5 => 'CINCUENTA', 6 => 'SESENTA', 7 => 'SETENTA', 8 => 'OCHENTA', 9 => 'NOVENTA'];
    private static $centenas = [1 => 'CIENTO', 2 => 'DOSCIENTOS', 3 => 'TRESCIENTOS', 4 => 'CUATROCIENTOS', 5 => 'QUINIENTOS', 6 => 'SEISCIENTOS', 7 => 'SETECIENTOS', 8 => 'OCHOCIENTOS', 9 => 'NOVECIENTOS'];

    public static function totalEnLetras($valor)
    {
        $n = (int) round((float) $valor);
        if ($n <= 0) {
            return 'CERO PESOS';
        }
        if ($n === 1) {
            return 'UN PESO';
        }

        $millones = intdiv($n, 1000000);
        $resto = $n % 1000000;
        $texto = '';
        if ($millones === 1) {
            $texto = 'UN MILLÓN';
        } elseif ($millones > 1) {
            $texto = self::hastaMillon($millones) . ' MILLONES';
        }
        if ($resto > 0) {
            $texto .= ($texto !== '' ? ' ' : '') . self::hastaMillon($resto);
            return $texto . ' PESOS';
        }
        return $texto . ' DE PESOS';
    }

    /** 1 a 999.999 con "UN" (y no "UNO") delante de una unidad, como se lee antes de MIL, MILLONES y PESOS. */
    private static function hastaMillon($n)
    {
        $miles = intdiv($n, 1000);
        $resto = $n % 1000;
        $t = '';
        if ($miles > 0) {
            $t = $miles === 1 ? 'MIL' : self::apocope(self::hastaMil($miles)) . ' MIL';
        }
        if ($resto > 0) {
            $t .= ($t !== '' ? ' ' : '') . self::apocope(self::hastaMil($resto));
        }
        return $t;
    }

    private static function hastaMil($n)
    {
        if ($n === 100) {
            return 'CIEN';
        }
        $c = intdiv($n, 100);
        $r = $n % 100;
        $t = $c > 0 ? self::$centenas[$c] : '';
        if ($r > 0) {
            $t .= $t !== '' ? ' ' : '';
            if ($r < 30) {
                $t .= self::$unidades[$r];
            } else {
                $u = $r % 10;
                $t .= self::$decenas[intdiv($r, 10)] . ($u > 0 ? ' Y ' . self::$unidades[$u] : '');
            }
        }
        return $t;
    }

    private static function apocope($t)
    {
        if (substr($t, -9) === 'VEINTIUNO') {
            return substr($t, 0, -9) . 'VEINTIÚN';
        }
        if (substr($t, -3) === 'UNO') {
            return substr($t, 0, -3) . 'UN';
        }
        return $t;
    }
}
