<?php

/*
|--------------------------------------------------------------------------
| Empaquetador de CSS/JS comunes (sin npm, sin composer)
|--------------------------------------------------------------------------
|
| Uso (desde la raiz del proyecto, con cualquier PHP >= 7.2):
|
|     php scripts/build_assets.php            construye public/theme/dist/
|     php scripts/build_assets.php --check    solo verifica que el paquete este al dia
|
| Lee la lista ordenada de config/theme_assets.php y genera:
|
|     public/theme/dist/app.<hash>.css    todos los CSS en uno (url() reescritas, @import
|                                         locales incluidos, comentarios y espacios fuera)
|     public/theme/dist/app.<hash>.js     todos los JS en uno, en el mismo orden (sin tocar el codigo)
|     public/theme/dist/manifest.json     nombres vigentes; lo lee la vista theme.assets_*
|
| El <hash> es del contenido: si el contenido no cambia, el nombre no cambia (cache del
| navegador), y si cambia, el nombre cambia solo (no hay que limpiar nada). Los archivos
| originales NO se modifican. El resultado se versiona en git: en el servidor no se compila nada.
|
| Windows: si no hay PHP en el PC, se puede correr dentro del contenedor local de pruebas:
|     docker run --rm -v "<ruta del proyecto>:/app" -w /app predial-php74 php scripts/build_assets.php
*/

error_reporting(E_ALL);

// config/theme_assets.php llama a env(); fuera de Laravel basta un sustituto.
if (!function_exists('env')) {
    function env($key, $default = null)
    {
        return $default;
    }
}

$root = dirname(__DIR__);
$public = $root . '/public';
$distRel = 'theme/dist';
$dist = $public . '/' . $distRel;

$config = require $root . '/config/theme_assets.php';

function fail($msg)
{
    fwrite(STDERR, "ERROR: $msg\n");
    exit(1);
}

function readFileStrict($path)
{
    if (!is_file($path)) {
        fail("No existe el archivo: $path");
    }
    $s = file_get_contents($path);
    // Quita BOM UTF-8
    if (substr($s, 0, 3) === "\xEF\xBB\xBF") {
        $s = substr($s, 3);
    }
    return $s;
}

// Resuelve "a/b/../c" -> "a/c"
function normalizePath($path)
{
    $out = [];
    foreach (explode('/', str_replace('\\', '/', $path)) as $seg) {
        if ($seg === '' || $seg === '.') {
            continue;
        }
        if ($seg === '..') {
            array_pop($out);
            continue;
        }
        $out[] = $seg;
    }
    return implode('/', $out);
}

// Ruta relativa de $to (relativa a public/) vista desde el directorio $fromDir (relativo a public/)
function relativeFrom($fromDir, $to)
{
    $a = $fromDir === '' ? [] : explode('/', $fromDir);
    $b = explode('/', $to);
    while (count($a) && count($b) && $a[0] === $b[0]) {
        array_shift($a);
        array_shift($b);
    }
    return str_repeat('../', count($a)) . implode('/', $b);
}

$warnings = [];

/**
 * Procesa un CSS: reescribe url() relativas para que funcionen desde theme/dist/,
 * inlinea @import locales, quita comentarios y colapsa espacios.
 * Devuelve [css, importsExternos].
 */
function processCss($publicRel, $distRel, $public, &$warnings, &$visited = [])
{
    if (isset($visited[$publicRel])) {
        return ['', []];
    }
    $visited[$publicRel] = true;

    $src = readFileStrict($public . '/' . $publicRel);
    $src = preg_replace('/@charset\s+["\'][^"\']*["\']\s*;/i', '', $src);
    $dirRel = trim(dirname($publicRel), '/.');
    $dirRel = $dirRel === '' ? '' : $dirRel;

    $len = strlen($src);
    $i = 0;
    $out = '';
    $external = [];
    $pendingSpace = false;

    $emit = function ($chunk) use (&$out, &$pendingSpace) {
        if ($chunk === '') {
            return;
        }
        if ($pendingSpace) {
            $last = $out === '' ? '' : substr($out, -1);
            $first = $chunk[0];
            if ($last !== '' && strpos('{};,', $last) === false && strpos('{};,}', $first) === false) {
                $out .= ' ';
            }
            $pendingSpace = false;
        }
        $out .= $chunk;
    };

    $rewriteUrl = function ($raw) use ($dirRel, $distRel, $public, $publicRel, &$warnings) {
        $u = trim($raw);
        if ($u === '' || preg_match('#^(data:|https?:|//|/|\#)#i', $u)) {
            return $u;
        }
        // separa ?query y #fragmento
        $suffix = '';
        if (preg_match('/^([^?#]*)([?#].*)$/', $u, $m)) {
            $u = $m[1];
            $suffix = $m[2];
        }
        $target = normalizePath(($dirRel === '' ? '' : $dirRel . '/') . $u);
        if (!is_file($public . '/' . $target)) {
            $warnings[] = "CSS $publicRel: url() apunta a un archivo que no existe: $u";
        }
        return relativeFrom($distRel, $target) . $suffix;
    };

    while ($i < $len) {
        $c = $src[$i];

        // Comentario
        if ($c === '/' && $i + 1 < $len && $src[$i + 1] === '*') {
            $end = strpos($src, '*/', $i + 2);
            $i = ($end === false) ? $len : $end + 2;
            $pendingSpace = true;
            continue;
        }

        // Cadena entre comillas (copiar tal cual)
        if ($c === '"' || $c === "'") {
            $j = $i + 1;
            while ($j < $len) {
                if ($src[$j] === '\\') {
                    $j += 2;
                    continue;
                }
                if ($src[$j] === $c) {
                    break;
                }
                $j++;
            }
            $emit(substr($src, $i, $j - $i + 1));
            $i = $j + 1;
            continue;
        }

        // url(...)
        if (($c === 'u' || $c === 'U') && strncasecmp(substr($src, $i, 4), 'url(', 4) === 0 && ($i === 0 || !preg_match('/[\w-]/', $src[$i - 1]))) {
            $j = $i + 4;
            while ($j < $len && ctype_space($src[$j])) {
                $j++;
            }
            if ($j < $len && ($src[$j] === '"' || $src[$j] === "'")) {
                $q = $src[$j];
                $k = $j + 1;
                while ($k < $len && $src[$k] !== $q) {
                    if ($src[$k] === '\\') {
                        $k++;
                    }
                    $k++;
                }
                $inner = substr($src, $j + 1, $k - $j - 1);
                $k++;
                while ($k < $len && $src[$k] !== ')') {
                    $k++;
                }
                $emit('url(' . $q . $rewriteUrl($inner) . $q . ')');
                $i = $k + 1;
            } else {
                $k = strpos($src, ')', $j);
                if ($k === false) {
                    $k = $len;
                }
                $inner = substr($src, $j, $k - $j);
                $emit('url(' . $rewriteUrl($inner) . ')');
                $i = $k + 1;
            }
            continue;
        }

        // @import
        if ($c === '@' && strncasecmp(substr($src, $i, 7), '@import', 7) === 0) {
            $end = strpos($src, ';', $i);
            if ($end === false) {
                fail("CSS $publicRel: @import sin ';'");
            }
            $stmt = substr($src, $i + 7, $end - $i - 7);
            $i = $end + 1;
            if (!preg_match('/^\s*(?:url\(\s*)?["\']?([^"\')\s]+)["\']?\s*\)?\s*(.*)$/s', $stmt, $m)) {
                fail("CSS $publicRel: no entiendo el @import: $stmt");
            }
            $target = $m[1];
            $media = trim($m[2]);
            if (preg_match('#^(https?:)?//#i', $target)) {
                $external[] = '@import url(' . $target . ')' . ($media !== '' ? ' ' . $media : '') . ';';
                continue;
            }
            $targetRel = normalizePath(($dirRel === '' ? '' : $dirRel . '/') . $target);
            list($inner, $ext2) = processCss($targetRel, $distRel, $public, $warnings, $visited);
            $external = array_merge($external, $ext2);
            if ($media !== '') {
                $inner = '@media ' . $media . '{' . $inner . '}';
            }
            $emit($inner);
            continue;
        }

        // Espacios
        if (ctype_space($c)) {
            $pendingSpace = true;
            $i++;
            continue;
        }

        $emit($c);
        $i++;
    }

    return [$out, $external];
}

function buildCss(array $files, $distRel, $public, &$warnings)
{
    $parts = [];
    $external = [];
    $visited = [];
    foreach ($files as $f) {
        list($css, $ext) = processCss($f, $distRel, $public, $warnings, $visited);
        $parts[] = $css;
        $external = array_merge($external, $ext);
    }
    return '@charset "UTF-8";' . "\n" . implode("\n", array_unique($external)) . ($external ? "\n" : '') . implode("\n", $parts) . "\n";
}

function buildJs(array $files, $public)
{
    $out = '';
    foreach ($files as $f) {
        $s = readFileStrict($public . '/' . $f);
        // fines de linea LF siempre: el resultado (y su hash) no depende de si el checkout es Windows o Linux
        $s = str_replace(array("\r\n", "\r"), "\n", $s);
        // quita referencias a sourcemaps (no se publican; evitan 404 en las herramientas del navegador)
        $s = preg_replace('~^\s*//[#@]\s*sourceMappingURL=.*$~m', '', $s);
        $s = preg_replace('~/\*[#@]\s*sourceMappingURL=.*?\*/~s', '', $s);
        $out .= '/* ==== ' . $f . " ==== */\n" . rtrim($s) . "\n;\n";
    }
    return $out;
}

$check = in_array('--check', $argv, true);

$css = buildCss($config['css'], $distRel, $public, $warnings);
$js = buildJs($config['js'], $public);

$cssName = 'app.' . substr(sha1($css), 0, 10) . '.css';
$jsName = 'app.' . substr(sha1($js), 0, 10) . '.js';
$manifest = json_encode(['css' => $cssName, 'js' => $jsName], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

if ($check) {
    $current = is_file($dist . '/manifest.json') ? file_get_contents($dist . '/manifest.json') : '';
    $ok = trim($current) === trim($manifest) && is_file($dist . '/' . $cssName) && is_file($dist . '/' . $jsName);
    echo $ok ? "OK: public/theme/dist esta al dia.\n" : "DESACTUALIZADO: corre php scripts/build_assets.php\n";
    exit($ok ? 0 : 1);
}

if (!is_dir($dist) && !mkdir($dist, 0775, true)) {
    fail("No se pudo crear $dist");
}

// Borra paquetes anteriores
foreach (glob($dist . '/app.*') as $old) {
    @unlink($old);
}
file_put_contents($dist . '/' . $cssName, $css);
file_put_contents($dist . '/' . $jsName, $js);
file_put_contents($dist . '/manifest.json', $manifest);

echo "CSS: " . count($config['css']) . " archivos -> $distRel/$cssName (" . round(strlen($css) / 1024) . " KB)\n";
echo "JS : " . count($config['js']) . " archivos -> $distRel/$jsName (" . round(strlen($js) / 1024) . " KB)\n";
foreach (array_unique($warnings) as $w) {
    echo "AVISO: $w\n";
}
