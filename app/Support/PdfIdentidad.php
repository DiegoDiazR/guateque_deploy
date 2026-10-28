<?php

namespace App\Support;

/**
 * Identidad del municipio para los PDF (dompdf): nombre, dependencia, NIT, dirección, ubicación,
 * escudo ya listo para incrustar y paleta de colores.
 *
 * Las plantillas compartidas (resources/views/pdf/*.blade.php y los documentos que las usan) la
 * llaman una sola vez al comienzo:
 *
 *     @php($id = \App\Support\PdfIdentidad::datos($logo ?? null, $alcaldia ?? null, $nit ?? null))
 *
 * Los textos salen de App\Support\Municipio (perfil + tabla `parametros` del municipio) y, si esa
 * clase no responde o el dato no existe, de lo que ya pasa el controlador ($alcaldia, $nit, $logo).
 * Nunca se escribe el nombre de un municipio a mano. Ningún método lanza excepciones: un PDF no
 * debe fallar porque falte un escudo o un dato opcional.
 *
 * El escudo se reduce con GD a un máximo de MAX_PX píxeles por lado (y se guarda como JPEG sobre
 * fondo blanco) antes de incrustarse: los archivos de public/theme/plugins/images/logo-*.png pesan
 * hasta 900 KB y los PDF de varias hojas lo repiten en cada hoja. Si GD no está disponible se
 * incrusta el original.
 *
 * Documentación: docs/PDF.md
 */
class PdfIdentidad
{
    /** Carpeta (dentro de public/) con los escudos del parámetro `logo`. */
    const CARPETA_LOGOS = 'theme/plugins/images/';

    /** Lado máximo, en píxeles, del escudo incrustado. */
    const MAX_PX = 200;

    private static $logos = [];

    /**
     * @param string|null $logo      valor del parámetro `logo` (logo-guateque.png)
     * @param string|null $alcaldia  valor del parámetro `alcaldia` (respaldo si Municipio no responde)
     * @param string|null $nit       NIT que pasa el controlador (respaldo)
     * @param string|null $direccion respaldo
     * @param string|null $ubicacion respaldo
     * @return array
     */
    public static function datos($logo = null, $alcaldia = null, $nit = null, $direccion = null, $ubicacion = null)
    {
        $perfil = self::llamar('perfil');
        $perfil = is_array($perfil) ? $perfil : [];

        $nombre = self::texto(self::llamar('nombre'));
        if ($nombre === '') {
            $nombre = self::texto($alcaldia);
        }
        $nit = self::texto(self::llamar('nit')) !== '' ? self::texto(self::llamar('nit')) : self::texto($nit);
        $direccion = self::texto(self::llamar('direccion')) !== '' ? self::texto(self::llamar('direccion')) : self::texto($direccion);
        $ubicacion = self::texto(self::llamar('ubicacion')) !== '' ? self::texto(self::llamar('ubicacion')) : self::texto($ubicacion);
        $secretaria = self::texto(isset($perfil['secretaria']) ? $perfil['secretaria'] : '');
        if ($secretaria === '') {
            $secretaria = 'Secretaría de Hacienda';
        }

        $primario = self::color(isset($perfil['color']) ? $perfil['color'] : '', '#1fa49d');
        $oscuro = self::color(isset($perfil['color_oscuro']) ? $perfil['color_oscuro'] : '', '#17756f');
        $oro = self::color(isset($perfil['color_acento']) ? $perfil['color_acento'] : '', '#c99a2e');

        $escudo = self::escudo($logo);
        if ($escudo !== null) {
            $caja = self::ajustar($escudo['w'], $escudo['h'], 58, 58);
            $escudo['pw'] = $caja[0];
            $escudo['ph'] = $caja[1];
        }

        $pie = [];
        foreach ([$secretaria, $nit !== '' ? 'NIT ' . $nit : '', $direccion, $ubicacion] as $parte) {
            if ($parte !== '') {
                $pie[] = $parte;
            }
        }

        return [
            'nombre'     => self::mayusculas($nombre),
            'nombre_t'   => $nombre,
            'secretaria' => $secretaria,
            'nit'        => $nit,
            'direccion'  => $direccion,
            'ubicacion'  => $ubicacion,
            'logo'       => $escudo,
            'pie'        => implode('  ·  ', $pie),
            'c' => [
                'primario' => $primario,
                'oscuro'   => $oscuro,
                'oro'      => $oro,
                'tinte'    => self::mezclar($primario, 0.90),   // fondo de rótulos
                'zebra'    => self::mezclar($primario, 0.95),   // filas alternas
                'linea'    => '#dbe5e2',
                'linea2'   => '#c3d2ce',
                'tinta'    => '#10201c',
                'tenue'    => '#4b5f5a',
            ],
        ];
    }

    /** Escudo listo para <img src>: ['uri' => 'data:image/png;base64,...', 'w' => px, 'h' => px] o null. */
    public static function escudo($archivo)
    {
        $archivo = basename(trim((string) $archivo));
        if ($archivo === '') {
            return null;
        }
        if (array_key_exists($archivo, self::$logos)) {
            return self::$logos[$archivo];
        }

        $resultado = null;
        try {
            $ruta = public_path(self::CARPETA_LOGOS . $archivo);
            $info = is_file($ruta) ? @getimagesize($ruta) : false;
            if ($info && $info[0] > 0 && $info[1] > 0) {
                $w = (int) $info[0];
                $h = (int) $info[1];
                $mime = $info['mime'];
                $binario = null;

                if (($w > self::MAX_PX || $h > self::MAX_PX) && function_exists('imagecreatefromstring')) {
                    $origen = @imagecreatefromstring((string) file_get_contents($ruta));
                    if ($origen) {
                        $escala = min(self::MAX_PX / $w, self::MAX_PX / $h);
                        $nw = max(1, (int) round($w * $escala));
                        $nh = max(1, (int) round($h * $escala));
                        // 1) reducir conservando la transparencia; 2) pegar sobre blanco y guardar como JPEG:
                        // el encabezado es blanco y un JPEG pesa ~8 KB (el PNG con alfa, ~50 KB) en cada hoja.
                        $reducido = imagecreatetruecolor($nw, $nh);
                        imagealphablending($reducido, false);
                        imagesavealpha($reducido, true);
                        imagefill($reducido, 0, 0, imagecolorallocatealpha($reducido, 255, 255, 255, 127));
                        imagecopyresampled($reducido, $origen, 0, 0, 0, 0, $nw, $nh, $w, $h);
                        $destino = imagecreatetruecolor($nw, $nh);
                        imagefill($destino, 0, 0, imagecolorallocate($destino, 255, 255, 255));
                        imagealphablending($destino, true);
                        imagecopy($destino, $reducido, 0, 0, 0, 0, $nw, $nh);
                        imagedestroy($reducido);
                        ob_start();
                        imagejpeg($destino, null, 90);
                        $binario = ob_get_clean();
                        imagedestroy($origen);
                        imagedestroy($destino);
                        if ($binario) {
                            $w = $nw;
                            $h = $nh;
                            $mime = 'image/jpeg';
                        }
                    }
                }
                if (!$binario) {
                    $binario = (string) file_get_contents($ruta);
                }
                if ($binario !== '') {
                    $resultado = ['uri' => 'data:' . $mime . ';base64,' . base64_encode($binario), 'w' => $w, 'h' => $h];
                }
            }
        } catch (\Throwable $e) {
            $resultado = null;
        }

        return self::$logos[$archivo] = $resultado;
    }

    /** Ajusta (w, h) a una caja máxima conservando la proporción. Devuelve [ancho, alto] en puntos. */
    public static function ajustar($w, $h, $maxW, $maxH)
    {
        if ($w <= 0 || $h <= 0) {
            return [$maxW, $maxH];
        }
        $escala = min($maxW / $w, $maxH / $h);
        return [round($w * $escala, 1), round($h * $escala, 1)];
    }

    /** Mezcla un #rrggbb con blanco: $mezcla = 0.9 deja un 10 % del color. */
    public static function mezclar($hex, $mezcla)
    {
        $hex = ltrim((string) $hex, '#');
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return '#f4f8f7';
        }
        $salida = '#';
        foreach ([0, 2, 4] as $i) {
            $c = hexdec(substr($hex, $i, 2));
            $salida .= str_pad(dechex((int) round($c + (255 - $c) * $mezcla)), 2, '0', STR_PAD_LEFT);
        }
        return $salida;
    }

    /** Mayúsculas con tildes (strtoupper de PHP 7.4 no toca las letras acentuadas). */
    public static function mayusculas($texto)
    {
        $texto = (string) $texto;
        return function_exists('mb_strtoupper') ? mb_strtoupper($texto, 'UTF-8') : strtoupper($texto);
    }

    /** Llama a un método estático de Municipio sin romper el PDF si la clase o el método no existen. */
    private static function llamar($metodo)
    {
        try {
            if (class_exists('\App\Support\Municipio') && method_exists('\App\Support\Municipio', $metodo)) {
                return call_user_func(['\App\Support\Municipio', $metodo]);
            }
        } catch (\Throwable $e) {
            // se usa el respaldo del controlador
        }
        return null;
    }

    private static function texto($valor)
    {
        return is_scalar($valor) ? trim((string) $valor) : '';
    }

    private static function color($valor, $omision)
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', (string) $valor) ? strtolower($valor) : $omision;
    }
}
