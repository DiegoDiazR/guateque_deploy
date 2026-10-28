# Un solo sistema para varios municipios

La misma base de código se despliega en Paipa, Guateque, Sutatenza, La Capilla, Santa Rosa de Viterbo, Villa de Leyva y Sesquilé.
**Cada municipio ve su nombre y su escudo sin editar el `.env` del servidor**, porque la identidad viva está en la tabla `parametros`
de su propia base de datos. Todo lo que el rediseño muestra del municipio (encabezado, login, pie, título de la pestaña, favicon,
franja del inicio, cambiar contraseña, validación pública del QR y colores) sale de una sola clase: `App\Support\Municipio`.

## 1. Cómo se decide el municipio

`Municipio::slug()` devuelve el municipio activo en minúsculas y sin tildes: `paipa`, `guateque`, `sutatenza`, `lacapilla`,
`santarosa`, `villadeleyva`, `sesquile`. Orden de decisión:

1. Variable `MUNICIPIO` del `.env` (si existe). Casi nunca hace falta.
2. El nombre del archivo del parámetro `logo`: `logo-guateque.png` → `guateque`; `logo-sesquile-2.png` → `sesquile`;
   `logo-villadeleyva.png` → `villadeleyva`. Solo cuenta si el nombre corresponde a un municipio conocido; un archivo llamado
   `logo-escudo.png` no sirve para deducirlo y se pasa al siguiente punto.
3. El nombre en el parámetro `alcaldia` (`ALCALDÍA DE VILLA DE LEYVA`, `ALCALDÍA MUNICIPAL DE LA CAPILLA`...).
4. `paipa`. Solo se llega aquí si no hay `.env`, ni parámetros, ni caché (base caída desde el primer minuto).

Un municipio nuevo (cuyo nombre no está en la lista) recibe un slug propio y **no hereda nada de Paipa**: usa el perfil
`predeterminado` y lo que haya en `parametros`.

## 2. De dónde sale cada dato

Orden de decisión de cada dato: **`.env` > perfil de `config/municipio.php` > tabla `parametros` > valor por omisión**.

| Dato | Método | Fuente normal |
|---|---|---|
| Nombre completo ("Alcaldía de Guateque") | `Municipio::nombre()` | `parametros.alcaldia`, pasado a mayúsculas y minúsculas de título; si falta la tilde se corrige con el nombre del perfil (`SESQUILE` → `Sesquilé`) |
| Solo el municipio ("Guateque") | `nombreCorto()` | perfil `ciudad`; si no, deducido de `alcaldia` o de `ubicacion` |
| Departamento | `departamento()` | perfil `departamento`; si no, la segunda parte de `parametros.ubicacion` |
| "Guateque - Boyacá" | `ubicacion()` | las dos anteriores |
| Escudo | `logoUrl()` | `public/theme/plugins/images/<parametros.logo>`; si el archivo no existe, el escudo del perfil (solo Paipa) |
| Dirección | `direccion()`, `direccionCompleta()` | perfil `direccion` (solo Paipa); si no, `parametros.direccion` |
| NIT | `nit()` | `parametros.nit` |
| Colores, sistema, contacto, enlaces | `perfil()` | `config/municipio.php` |
| Cualquier fila de `parametros` tal cual | `param('nombre', $porDefecto)` | `parametros` |

Parámetros que lee el sistema para la identidad: `alcaldia`, `logo`, `direccion`, `ubicacion`, `nit`. Los demás (`admin-text`,
`admin-text-dark`, `ean`, `formato-paz`, `formato-factura`, `formato-acuerdo`, `codigo_postal`...) siguen siendo de los PDF y se leen con
`Municipio::param()` si hacen falta. `param()` devuelve el texto tal cual está guardado; `nombre()` y `ubicacion()` ya vienen "bonitos".

### Cómo se ve cada municipio

* **Con imagen que ya trae el nombre escrito (hoy solo Paipa, `perfiles.paipa.logo_completo`)**: el encabezado muestra esa imagen y,
  al lado, "Secretaría de Hacienda / Impuesto Predial Unificado".
* **Con solo el escudo (todos los demás)**: escudo + nombre ("Alcaldía de Villa de Leyva") + "Impuesto Predial Unificado". El nombre
  se ve también en celular. El escudo tiene alto máximo y `object-fit: contain`, así que cualquier proporción cabe sin deformarse.
* **Login**: con la foto de fondo si el perfil la tiene (Paipa); si no, color liso con un resplandor suave.
* **Pie**: nombre y escudo; "Contáctenos" solo si hay algún dato (teléfono, correo, dirección, horario); "Enlaces" con el portal de
  pago y los enlaces del perfil, si existen, y siempre GOV.CO. **Lo que no existe no se imprime**; no se inventan teléfonos, correos
  ni enlaces.

## 3. Caché y base de datos caída

* `Municipio` lee **toda** la tabla `parametros` con una sola consulta y la guarda en caché 10 minutos (`Municipio::TTL`).
  Si se cambia un parámetro en un municipio, se ve hasta 10 minutos después, o de inmediato con `php artisan cache:clear`.
* La clave de la caché incluye el servidor y el nombre de la base de datos, así que dos municipios que compartan caché no se mezclan.
* Si la base de datos no responde, nada lanza error: el login y las demás pantallas se dibujan igual.
  * Se usa la **última lectura buena** (se guarda 30 días), de modo que el municipio sigue viéndose con su nombre y su escudo.
  * Si nunca hubo una lectura buena, se usan los valores por omisión (Paipa).
  * La lectura usa una conexión con `login_timeout` de 2 segundos (el controlador ODBC reintenta, así que una base caída tarda ~8 s
    en vez de 60 s) y después de un fallo no se vuelve a intentar durante 30 segundos.
* Para que el login se vea bien aun con la base caída y la caché vacía (por ejemplo, justo después de un `cache:clear`), se puede
  fijar `MUNICIPIO=<slug>` en el `.env` del municipio. Es opcional.

## 4. Añadir o ajustar un perfil (`config/municipio.php`)

El archivo tiene cuatro bloques: `slug`, `predeterminado`, `perfiles` y `sobrescribir`. Los datos propios de un municipio van en
`perfiles['<slug>']`. Ejemplo para completar Guateque cuando se confirmen sus datos de contacto:

```php
'guateque' => [
    'ciudad'       => 'Guateque',
    'departamento' => 'Boyacá',
    'secretaria'   => 'Secretaría de Hacienda',      // aparece en el encabezado, login y pie
    'telefono'     => '(teléfono confirmado por el municipio)',
    'correo'       => '(correo confirmado por el municipio)',
    'portal'       => 'https://...',                  // pago en línea
    'portal_texto' => 'Pago en línea del Impuesto Predial',
    'enlaces'      => ['Estatuto de Rentas' => 'https://...'],
    'color'        => '#rrggbb',                      // cada color es #rrggbb; el resto de tonos se calcula
    'color_oscuro' => '#rrggbb',
],
```

Claves posibles: `ciudad`, `departamento`, `alcaldia` (fuerza el nombre completo), `secretaria`, `dependencia`, `sistema`, `desarrollador`,
`color`, `color_oscuro`, `color_acento`, `telefono`, `correo`, `direccion`, `horario`, `nit`, `portal`, `portal_texto`,
`enlaces` (`'texto' => 'url'`), `logo_completo`, `escudo`, `fondo` (rutas relativas a `public/`), `validacion_titulo` y
`validacion_correo` (pie de la validación pública del QR; sin ellos se usa el nombre de la alcaldía y la dirección).

Un municipio nuevo (octavo): no hace falta tocar nada para que se vea con su nombre y su escudo; basta con que su base tenga
`alcaldia` y `logo` y que el archivo del logo exista. Si se quiere contacto o colores, se agrega su bloque en `perfiles`.

Después de editar el archivo en producción: `php artisan config:clear` (o `config:cache`). Los cambios de `config/municipio.php`
no necesitan tocar vistas, CSS ni JavaScript.

### Variables `MUNICIPIO_*` del `.env`

Sobrescriben el perfil; están listadas y comentadas en `.env.example`. Una variable que no existe no cambia nada; una definida pero
vacía (`MUNICIPIO_HORARIO=`) borra ese dato. `MUNICIPIO_LOGO` es la imagen del encabezado que ya trae el nombre, no el escudo.
Con `config:cache` el `.env` ya no se lee en cada petición, por eso `config/municipio.php` es el único lugar que usa `env()`.

## 5. Qué revisar al desplegar en cada municipio

1. **`parametros.logo` apunta a un archivo que existe** en `public/theme/plugins/images/` (por ejemplo `logo-guateque.png`).
   Si el archivo falta, el encabezado y el pie salen sin escudo (solo el nombre) y no hay error.
2. **`parametros.alcaldia` está escrito con tilde** ("ALCALDÍA DE …"). Se corrige la tilde del nombre del municipio con el perfil, pero
   la palabra "Alcaldía" solo se corrige si empieza el texto.
3. `parametros.direccion` y `parametros.ubicacion` tienen valores reales ("GUATEQUE - BOYACÁ"). Aparecen en el pie y en la validación del QR.
4. `parametros.nit` correcto (lo usan los PDF).
5. Sin `.env` especial: no hace falta definir ninguna variable `MUNICIPIO_*`. Si existe una de una instalación anterior, revisar que
   no fuerce Paipa en otro municipio.
6. Probar el login y el inicio en celular (375 px) y la validación `/validate-qr/<token>` con un token cualquiera.
7. Limpiar cachés tras el despliegue: `php artisan cache:clear` y `php artisan view:clear`.
8. El archivo nuevo `app/Support/Municipio.php` y `public/theme/css/municipio.css` deben subirse junto con las vistas. La clase se carga por
   PSR-4 sin necesidad de `composer dump-autoload`.

## 6. Para quien trabaje en los PDF u otras pantallas

```php
use App\Support\Municipio;

Municipio::nombre();            // "Alcaldía de Guateque"
Municipio::nombreCorto();       // "Guateque"
Municipio::slug();              // "guateque"
Municipio::logoUrl();           // URL del escudo (null si no hay archivo)
Municipio::param('logo');       // "logo-guateque.png", tal cual está en parametros
Municipio::param('formato-paz', 'predios.pazPDF');
Municipio::direccion();         // null si no hay
Municipio::ubicacion();         // "Guateque - Boyacá"
Municipio::nit();
Municipio::perfil()['color'];   // colores y contacto del perfil
```

Para pruebas automáticas: `Municipio::fijar([...parametros...])` fija el contenido sin tocar la base (ver `tests/Unit/MunicipioTest.php`);
`Municipio::olvidar()` borra la memoria de la petición y la caché.

## 7. Archivos

| Archivo | Para qué |
|---|---|
| `app/Support/Municipio.php` | lectura de `parametros`, caché, slug, nombre, escudo, perfil |
| `config/municipio.php` | perfiles por municipio, predeterminados y variables `MUNICIPIO_*` |
| `resources/views/theme/marca.blade.php` | escudo + nombre del encabezado (layout, login, validación QR) |
| `resources/views/theme/header.blade.php`, `footer.blade.php`, `identidad.blade.php` | encabezado, pie y variables CSS de color |
| `resources/views/auth/login.blade.php`, `qr/validation-result.blade.php`, `theme/default.blade.php` | login, validación pública, layout (`<title>` y favicon) |
| `public/theme/css/municipio.css` | escudos de cualquier forma, nombre visible en celular, login sin foto |
| `tests/Unit/MunicipioTest.php` | pruebas de la clase |

Los nombres de clase `paipa` en `paipa.css` / `paipa.js` (`body.paipa`, `.pagina-paipa`, `data-paipa-toggle`) son solo nombres internos
del rediseño; no se muestran y no dependen del municipio.
