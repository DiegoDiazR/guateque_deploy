<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Identidad del municipio activo: nombre, escudo, dirección, ubicación, NIT, colores y contacto.
 *
 * El mismo código se despliega en varios municipios y cada uno guarda su identidad en la tabla
 * `parametros` de su propia base de datos (alcaldia, logo, direccion, ubicacion, nit...). Esta
 * clase la lee (con caché y tolerando que la base falle) y la combina con los perfiles de
 * config/municipio.php. Orden de decisión de cada dato:
 *
 *     .env (MUNICIPIO_*)  >  perfil de config/municipio.php  >  tabla parametros  >  valor por omisión
 *
 * Todo son métodos estáticos y ninguno lanza excepciones: en el login con la base caída se
 * devuelven los valores por omisión (o la última lectura buena guardada en caché).
 * Documentación: docs/MULTIMUNICIPIO.md
 */
class Municipio
{
    /** Municipios con perfil. El orden importa solo para deducir el slug a partir de un texto. */
    const CONOCIDOS = ['paipa', 'guateque', 'sutatenza', 'lacapilla', 'santarosa', 'villadeleyva', 'sesquile'];

    /** Segundos que dura la lectura de `parametros` en caché. */
    const TTL = 600;

    /** Segundos sin volver a intentar la base de datos después de un fallo (el login sigue rápido). */
    const ESPERA_FALLO = 30;

    /** Carpeta (dentro de public/) donde viven los logos del parámetro `logo`. */
    const CARPETA_LOGOS = 'theme/plugins/images/';

    private static $parametros = null;
    private static $slug = null;
    private static $perfil = null;
    private static $archivos = [];

    // ------------------------------------------------------------------ parametros

    /**
     * Valor de la fila `nombre` de la tabla parametros, o $default si no existe o la base no responde.
     * Devuelve el texto tal cual está guardado (por ejemplo "ALCALDÍA DE GUATEQUE").
     */
    public static function param($nombre, $default = null)
    {
        $todos = self::parametros();
        if (array_key_exists($nombre, $todos) && $todos[$nombre] !== null) {
            return $todos[$nombre];
        }
        return $default;
    }

    /** Todas las filas de `parametros` como [nombre => valor]. Una sola consulta por 10 minutos. */
    private static function parametros()
    {
        if (self::$parametros !== null) {
            return self::$parametros;
        }

        $clave = self::claveCache();
        $guardado = self::cacheGet($clave);
        if (is_array($guardado)) {
            return self::$parametros = $guardado;
        }

        // Si la base falló hace unos segundos no se insiste: se usa la última lectura buena.
        if (self::cacheGet($clave . '.fallo')) {
            return self::$parametros = self::ultimaLectura($clave);
        }

        try {
            $mapa = [];
            $filas = DB::table('parametros')->select('nombre', 'valor')->orderBy('id')->get();
            foreach ($filas as $fila) {
                $nombre = (string) $fila->nombre;
                if (!array_key_exists($nombre, $mapa)) {
                    $mapa[$nombre] = $fila->valor === null ? null : trim((string) $fila->valor);
                }
            }
            self::cachePut($clave, $mapa, self::TTL);
            self::cachePut($clave . '.ultimo', $mapa, 60 * 60 * 24 * 30);
            return self::$parametros = $mapa;
        } catch (\Throwable $e) {
            self::cachePut($clave . '.fallo', 1, self::ESPERA_FALLO);
            return self::$parametros = self::ultimaLectura($clave);
        }
    }

    private static function ultimaLectura($clave)
    {
        $ultimo = self::cacheGet($clave . '.ultimo');
        return is_array($ultimo) ? $ultimo : [];
    }

    /** La clave incluye el nombre de la base para no mezclar municipios si se comparte la caché. */
    private static function claveCache()
    {
        try {
            $conexion = config('database.default');
            $base = (string) config('database.connections.' . $conexion . '.database');
            $host = (string) config('database.connections.' . $conexion . '.host');
        } catch (\Throwable $e) {
            $base = $host = '';
        }
        return 'municipio.parametros.' . md5($host . '|' . $base);
    }

    private static function cacheGet($clave)
    {
        try {
            return Cache::get($clave);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function cachePut($clave, $valor, $segundos)
    {
        try {
            Cache::put($clave, $valor, $segundos);
        } catch (\Throwable $e) {
            // sin caché el sistema sigue funcionando, solo consulta más veces
        }
    }

    /** Borra la memoria de esta petición y la caché (para pruebas o después de cambiar `parametros`). */
    public static function olvidar()
    {
        $clave = self::claveCache();
        foreach ([$clave, $clave . '.fallo', $clave . '.ultimo'] as $k) {
            try {
                Cache::forget($k);
            } catch (\Throwable $e) {
            }
        }
        self::$parametros = self::$slug = self::$perfil = null;
        self::$archivos = [];
    }

    // ------------------------------------------------------------------ identidad

    /**
     * Municipio activo en minúsculas y sin tildes: paipa, guateque, sutatenza, lacapilla,
     * santarosa, villadeleyva, sesquile (u otro si el sistema se instala en uno nuevo).
     * Prioridad: variable MUNICIPIO del .env; nombre del archivo del parámetro `logo`
     * (logo-guateque.png); nombre en el parámetro `alcaldia`; 'paipa'.
     */
    public static function slug()
    {
        if (self::$slug !== null) {
            return self::$slug;
        }

        $forzado = self::normalizarSlug((string) config('municipio.slug'));
        if ($forzado !== '') {
            return self::$slug = $forzado;
        }

        $logo = basename((string) self::param('logo', ''));
        if ($logo !== '' && preg_match('/^logo[-_ ]+(.+?)(?:[-_ ]*\d+)?\.[a-z0-9]+$/iu', $logo, $m)) {
            $deLogo = self::normalizarSlug($m[1]);
            if ($deLogo !== '') {
                return self::$slug = $deLogo;
            }
        }

        $deAlcaldia = self::normalizarSlug(self::ciudadDesdeAlcaldia((string) self::param('alcaldia', '')));
        if ($deAlcaldia !== '') {
            return self::$slug = $deAlcaldia;
        }

        return self::$slug = 'paipa';
    }

    /** "Villa de Leyva", "logo-santa-rosa" -> villadeleyva, santarosa. Reconoce los municipios conocidos. */
    private static function normalizarSlug($texto)
    {
        $limpio = preg_replace('/[^a-z]/', '', self::sinTildes($texto));
        if ($limpio === '') {
            return '';
        }
        foreach (self::CONOCIDOS as $conocido) {
            if (strpos($limpio, $conocido) === 0) {
                return $conocido;
            }
        }
        return $limpio;
    }

    /** Nombre completo de la alcaldía en mayúsculas y minúsculas de título: "Alcaldía de Guateque". */
    public static function nombre()
    {
        $propio = self::dato('alcaldia');
        if ($propio !== null) {
            return $propio;
        }

        $param = trim((string) self::param('alcaldia', ''));
        if ($param !== '') {
            $texto = self::titulo($param);
            // Con el nombre curado del perfil se corrigen las tildes que falten ("Sesquile" -> "Sesquilé").
            $ciudad = self::dato('ciudad');
            if ($ciudad !== null) {
                $n = mb_strlen($ciudad);
                if (mb_strlen($texto) >= $n && self::sinTildes(mb_substr($texto, -$n)) === self::sinTildes($ciudad)) {
                    $texto = mb_substr($texto, 0, mb_strlen($texto) - $n) . $ciudad;
                }
            }
            return $texto;
        }

        return 'Alcaldía de ' . self::nombreCorto();
    }

    /** Solo el municipio: "Guateque", "Villa de Leyva". */
    public static function nombreCorto()
    {
        $propio = self::dato('ciudad');
        if ($propio !== null) {
            return $propio;
        }

        $deAlcaldia = self::titulo(self::ciudadDesdeAlcaldia((string) self::param('alcaldia', '')));
        if ($deAlcaldia !== '') {
            return $deAlcaldia;
        }

        $partes = self::partesUbicacion();
        if ($partes[0] !== '') {
            return $partes[0];
        }

        return ucfirst(self::slug());
    }

    /** Departamento: "Boyacá". null si no se sabe. */
    public static function departamento()
    {
        $propio = self::dato('departamento');
        if ($propio !== null) {
            return $propio;
        }
        $partes = self::partesUbicacion();
        return $partes[1] !== '' ? $partes[1] : null;
    }

    /** Parámetro `ubicacion` en título ("Guateque - Boyacá"); si falta, "Ciudad - Departamento". */
    public static function ubicacion()
    {
        $param = trim((string) self::param('ubicacion', ''));
        if ($param !== '') {
            return self::titulo($param);
        }
        $departamento = self::departamento();
        return self::nombreCorto() . ($departamento ? ' - ' . $departamento : '');
    }

    /** Dirección de atención. null si no hay. */
    public static function direccion()
    {
        $propio = self::dato('direccion');
        if ($propio !== null) {
            return $propio;
        }
        $param = trim((string) self::param('direccion', ''));
        return $param !== '' ? $param : null;
    }

    /** "Carrera 6 # 9-18, Guateque, Boyacá" (solo las partes que existen). */
    public static function direccionCompleta()
    {
        $partes = array_filter([self::direccion(), self::nombreCorto(), self::departamento()], function ($p) {
            return $p !== null && trim((string) $p) !== '';
        });
        return implode(', ', $partes);
    }

    /** NIT del municipio tal cual está guardado. null si no hay. */
    public static function nit()
    {
        $propio = self::dato('nit');
        if ($propio !== null) {
            return $propio;
        }
        $param = trim((string) self::param('nit', ''));
        return $param !== '' ? $param : null;
    }

    /** Texto corto sobre los títulos del login e inicio: "Secretaría de Hacienda · Paipa" o "Guateque · Boyacá". */
    public static function ceja()
    {
        $perfil = self::perfil();
        $ciudad = self::nombreCorto();
        if (!empty($perfil['secretaria'])) {
            return $perfil['secretaria'] . ' · ' . $ciudad;
        }
        $departamento = self::departamento();
        return $ciudad . ($departamento ? ' · ' . $departamento : '');
    }

    // ------------------------------------------------------------------ imágenes

    /** Escudo del parámetro `logo` (public/theme/plugins/images/<logo>). Si falta, el escudo del perfil (Paipa). null si no hay. */
    public static function logoUrl()
    {
        $archivo = basename(trim((string) self::param('logo', '')));
        if ($archivo !== '' && self::existe(self::CARPETA_LOGOS . $archivo)) {
            return asset(self::CARPETA_LOGOS . $archivo);
        }
        return self::imagenPerfil('escudo');
    }

    /** Imagen del encabezado que ya trae el nombre escrito (hoy solo Paipa). null si el municipio no tiene. */
    public static function logoCompletoUrl()
    {
        return self::imagenPerfil('logo_completo');
    }

    /** Escudo para el pie y el favicon: el del perfil si existe y, si no, el del parámetro `logo`. */
    public static function escudoUrl()
    {
        return self::imagenPerfil('escudo') ?: self::logoUrl();
    }

    /** Foto de fondo del login. null si el municipio no tiene. */
    public static function fondoUrl()
    {
        return self::imagenPerfil('fondo');
    }

    private static function imagenPerfil($clave)
    {
        $ruta = ltrim((string) (self::perfil()[$clave] ?? ''), '/');
        return ($ruta !== '' && self::existe($ruta)) ? asset($ruta) : null;
    }

    private static function existe($rutaRelativa)
    {
        if (!array_key_exists($rutaRelativa, self::$archivos)) {
            try {
                self::$archivos[$rutaRelativa] = is_file(public_path($rutaRelativa));
            } catch (\Throwable $e) {
                self::$archivos[$rutaRelativa] = false;
            }
        }
        return self::$archivos[$rutaRelativa];
    }

    // ------------------------------------------------------------------ perfil

    /**
     * Perfil del municipio: predeterminado + perfil por slug + .env. Siempre trae las claves
     * color, color_oscuro, color_acento, sistema y desarrollador; el resto (secretaria, dependencia,
     * telefono, correo, direccion, horario, portal, portal_texto, enlaces, validacion_titulo,
     * validacion_correo...) solo si el municipio las tiene.
     */
    public static function perfil()
    {
        if (self::$perfil !== null) {
            return self::$perfil;
        }

        $config = (array) config('municipio', []);
        $perfil = (array) ($config['predeterminado'] ?? []);
        $propios = (array) (($config['perfiles'] ?? [])[self::slug()] ?? []);
        $perfil = array_merge($perfil, $propios);
        foreach ((array) ($config['sobrescribir'] ?? []) as $clave => $valor) {
            if ($valor !== null) {
                $perfil[$clave] = $valor;
            }
        }

        // Colores: si no son #rrggbb se usan los predeterminados.
        foreach (['color' => '#1fa49d', 'color_oscuro' => '#17756f', 'color_acento' => '#c99a2e'] as $clave => $omision) {
            $valor = $perfil[$clave] ?? '';
            $perfil[$clave] = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $valor) ? strtolower($valor) : $omision;
        }
        $perfil['sistema'] = trim((string) ($perfil['sistema'] ?? '')) ?: 'Impuesto Predial Unificado';
        $perfil['desarrollador'] = trim((string) ($perfil['desarrollador'] ?? '')) ?: 'ERPSOFT S.A.S.';
        $perfil['enlaces'] = is_array($perfil['enlaces'] ?? null) ? $perfil['enlaces'] : [];

        return self::$perfil = $perfil;
    }

    /** Un dato del perfil (ya con .env aplicado) o null si no existe o está vacío. */
    private static function dato($clave)
    {
        $valor = self::perfil()[$clave] ?? null;
        if (!is_string($valor)) {
            return null;
        }
        $valor = trim($valor);
        return $valor !== '' ? $valor : null;
    }

    /** Líneas de contacto del pie: solo las que existen. Cada una: ['tipo' => ..., 'texto' => ..., 'enlace' => ...]. */
    public static function contacto()
    {
        $filas = [];
        $perfil = self::perfil();
        if (!empty($perfil['telefono'])) {
            $filas[] = ['tipo' => 'telefono', 'texto' => $perfil['telefono'], 'enlace' => 'tel:+57' . preg_replace('/\D/', '', $perfil['telefono'])];
        }
        if (!empty($perfil['correo'])) {
            $filas[] = ['tipo' => 'correo', 'texto' => $perfil['correo'], 'enlace' => 'mailto:' . $perfil['correo']];
        }
        $direccion = self::direccionCompleta();
        if (self::direccion() !== null && $direccion !== '') {
            $filas[] = ['tipo' => 'direccion', 'texto' => $direccion, 'enlace' => null];
        }
        if (!empty($perfil['horario'])) {
            $filas[] = ['tipo' => 'horario', 'texto' => $perfil['horario'], 'enlace' => null];
        }
        return $filas;
    }

    // ------------------------------------------------------------------ utilidades de texto

    /** "ALCALDÍA DE VILLA DE LEYVA" -> "Alcaldía de Villa de Leyva". Un texto que ya trae minúsculas no se toca. */
    public static function titulo($texto)
    {
        $texto = trim((string) preg_replace('/\s+/u', ' ', (string) $texto));
        if ($texto === '' || $texto !== mb_strtoupper($texto)) {
            return $texto;
        }
        $palabras = explode(' ', mb_strtolower($texto));
        foreach ($palabras as $i => $palabra) {
            if ($i > 0 && in_array($palabra, ['de', 'del', 'y', 'e'], true)) {
                continue;
            }
            $palabras[$i] = mb_convert_case($palabra, MB_CASE_TITLE);
        }
        return implode(' ', $palabras);
    }

    /** Minúsculas y sin tildes, sin cambiar la cantidad de letras. */
    private static function sinTildes($texto)
    {
        return strtr(mb_strtolower((string) $texto), [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);
    }

    /** "ALCALDÍA MUNICIPAL DE LA CAPILLA" -> "LA CAPILLA" (quita las palabras de cabecera). */
    private static function ciudadDesdeAlcaldia($texto)
    {
        $palabras = preg_split('/\s+/u', trim((string) $texto), -1, PREG_SPLIT_NO_EMPTY);
        $cabecera = ['alcaldia', 'municipal', 'municipio', 'de', 'del'];
        while ($palabras && in_array(self::sinTildes($palabras[0]), $cabecera, true)) {
            array_shift($palabras);
        }
        return implode(' ', $palabras);
    }

    /** Parámetro `ubicacion` partido en [ciudad, departamento], ya en título. */
    private static function partesUbicacion()
    {
        $partes = preg_split('/\s*[-,\/]\s*/u', trim((string) self::param('ubicacion', '')), 2);
        return [
            self::titulo($partes[0] ?? ''),
            self::titulo($partes[1] ?? ''),
        ];
    }
}
