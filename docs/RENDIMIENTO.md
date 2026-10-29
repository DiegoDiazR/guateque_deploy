# Rendimiento del sistema de predial

Resumen de lo que se midió, lo que se cambió, lo que queda propuesto y cómo desplegarlo y revertirlo.
Todas las cifras salen de la copia local de Guateque (predios 8.982, pagos 156.462, predios_pagos 208.659) con PHP 7.4 + Apache + SQL Server
en Docker. **No se pudo probar IIS/Plesk**: lo que depende de IIS (compresión, caché) está indicado como tal y trae su forma de validarlo.

## 1. Qué hacía lento al sistema

| Causa | Dónde | Efecto |
|---|---|---|
| Cada pantalla pedía ~30 CSS y ~40 JS (80 a 95 peticiones, 1,0 a 1,2 MB comprimidos, 2 a 2,8 MB de JS sin comprimir) | `resources/views/theme/default.blade.php` | En una primera visita con una línea de 10 Mbps y 70 ms de latencia, la pantalla tardaba ~6,5 a 7,3 s en estar lista |
| 13 recursos desde CDN externos (cdn.datatables.net, cdnjs, `cdn.rawgit.com` que hoy redirige a jsdelivr y trae 254 KB comprimidos de fuentes de pdfmake) y Google Fonts importado dos veces desde `style.css` / `green.css` | layout y CSS | Cada carga dependía de 5 servidores ajenos; si uno estaba lento, la página esperaba (los scripts bloquean el cierre del *preloader*) |
| Plugins que ninguna pantalla usa (morris, raphael, chartist, clockpicker, fullcalendar, waypoints, counterup, horizontal-timeline, styleswitcher x2, 2.ª copia de moment, botones/select/jszip/pdfmake de DataTables) | layout | ~1,6 MB de JS sin comprimir cargados y ejecutados en cada página |
| `?{{ $current_time }}` en `controlsite.js`, `predios_forms.js`, `pagos.js`, `acuerdos.js` | vistas de Predios, Pagos y Acuerdos | El navegador **nunca** podía guardarlos: 150 KB + 93 KB + ... descargados completos en cada visita |
| Sin compresión ni caché de estáticos configuradas | `public/web.config` | Todo viajaba sin comprimir y se revalidaba en cada página |
| Informes en Excel con `FromView`: PhpSpreadsheet interpreta los `style/width/data-format` de **cada celda** (~4 ms por celda) | `app/Exports/ExportRecaudoCAR.php`, `ExportPrediosPagos.php`, `ExportCartera.php` | Recaudo de un año (8.325 filas): **39,5 s**; cartera (22.982 filas): **41,5 s**. En IIS con `max_execution_time` de 30 s esto termina en error |
| Consultas por `id_predio`, `factura_pago`, `numero_recibo`, `fecha_pago` sin índice | tablas `predios_pagos` y `pagos` | Cada consulta recorre la tabla completa (691 a 2.876 páginas); `get_predio` hace 11 a 14 de ellas |

## 2. Resultados medidos

### 2.1 Carga de pantallas (navegador, 5 cargas por pantalla, mediana; caché del navegador caliente, servidor local)

| Pantalla | Peticiones antes → después | Externas | JS sin comprimir | Total comprimido* |
|---|---|---|---|---|
| Inicio `/profile` | 80 → 15 | 13 → 0 | 2.033 → 449 KB | 1.012 → 494 KB |
| Predios | 93 → 28 | 13 → 0 | 2.813 → 1.229 KB | 1.190 → 672 KB |
| Pagos | 92 → 29 | 13 → 0 | 2.807 → 1.247 KB | 1.192 → 680 KB |
| Acuerdos | 95 → 30 | 13 → 0 | 2.836 → 1.253 KB | 1.202 → 684 KB |
| Informes | 80 → 15 | 13 → 0 | 2.033 → 449 KB | 1.013 → 495 KB |
| Notas | 93 → 28 | 13 → 0 | 2.791 → 1.207 KB | 1.188 → 670 KB |
| Tarifas predial | 85 → 20 | 13 → 0 | 2.672 → 1.088 KB | 1.196 → 678 KB |
| Usuarios | 84 → 18 | 13 → 0 | 2.470 → 886 KB | 1.128 → 592 KB |

\* Incluye en ambos casos las fuentes e imágenes del rediseño (que no cambiaron); la diferencia (~0,5 MB por pantalla) es la de este trabajo.

Tiempo hasta que el sistema queda usable, **primera visita sin caché** con una línea simulada de 10 Mbps y 70 ms de latencia
(los CDN del "antes" también pasaron por esa línea):

| Pantalla | DOM listo (antes → después) | Carga completa (antes → después) |
|---|---|---|
| Inicio | 6,5 s → 0,81 s | 7,0 s → 1,16 s |
| Pagos | 7,3 s → 0,88 s | 7,7 s → 1,26 s |

En el servidor local (sin latencia) el DOM listo con caché caliente queda igual (150 a 290 ms), porque en una red local las 80 peticiones casi no cuestan; el
beneficio aparece con red real. El *preloader* se cierra al estar listo el DOM (`custom.min.js`), por eso mejora en la misma proporción.

### 2.2 Servidor (PHP + SQL, en proceso, base local; mínimo de 3 ejecuciones)

| Operación | Antes | Después | Qué cambió |
|---|---|---|---|
| Informe de recaudo en Excel, año 2025 (8.325 filas) | 39,5 s | **3,0 s** | formato por columna (código) |
| Informe de recaudo, 1.er trimestre 2025 (6.066 filas) | 26,8 s | 2,6 s | ídem |
| Relación de ingresos por recibo (Facturado), 1.er trim. 2025 (6.024 filas) | 24,8 s | 2,5 s | ídem |
| Cartera (22.982 filas) | 41,5 s | 4,9 s | ídem |
| `get_predio` (al elegir un predio), 3 predios | 67 / 90 / 102 ms, 11 a 14 consultas | 32 / 38 / 43 ms, 10 a 11 consultas | índices + una consulta menos por factura |
| `avaluos_predio` / `estado_cuenta_predio` | 23 / 13 ms | 5 / 4 ms | índices |
| `get_info_pago` | 30 ms | 5 ms | índices |
| Listado de pagos por número de recibo | 16 ms | 4 ms | índices |
| `autocomplete` (buscador de predios, 16 búsquedas) | 2.224 ms en total | 1.824 ms en total | consulta sin tabla derivada ni producto con `predios_pagos` |

Las páginas HTML en sí cuestan poco en el servidor (5 a 70 ms por pantalla); las más pesadas son por tamaño:
`registro_tarifaspredial` (2,6 MB de HTML, 54 KB comprimido) y `registro_conceptospredio` (2,0 MB).

## 3. Cambios aplicados en el repositorio

### Frontend
* **Un solo CSS y un solo JS comunes** (`public/theme/dist/app.<hash>.css` y `.js`, 13 CSS + 18 JS). Lista única y ordenada en
  `config/theme_assets.php`; la arma `php scripts/build_assets.php` (PHP puro, sin npm); el resultado está versionado en git, el servidor no compila nada.
  Los archivos originales quedan intactos. Las vistas `theme/assets_css.blade.php` y `assets_js.blade.php` usan el paquete y, si no existe
  o `ASSETS_BUNDLE=false` en `.env`, cargan los archivos originales de la lista uno por uno.
  * Verificación: las **8.682 reglas CSS** que ve el navegador son idénticas con y sin paquete, y el conjunto de variables globales de JS también.
* **Plugins por pantalla**: lo que usa una sola vista va con `@push('styles')` / `@push('scripts')` (el layout ahora tiene `@stack('styles')`). Hoy: Switchery solo en Pagos.
* **Sin CDN**: quitados los 13 recursos externos. Los botones de exportación de DataTables (Buttons, Select, JSZip, pdfmake, vfs_fonts) **ningún DataTable del
  sistema los usa** (ninguno define `buttons`/`dom: 'B'`), así que ya no se cargan; quedaron copias locales en
  `public/theme/plugins/bower_components/datatables-export/` y el parcial opcional `theme.datatables_export` para quien los necesite.
  Rubik (Google Fonts) quedó autoalojada en `public/theme/css/fonts/`.
* **Quitado tras comprobar con grep (vistas, `customjs`, `custom.min.js`) que nada los usa**: morris, raphael, chartist y su tooltip, clockpicker, fullcalendar y su idioma,
  waypoints, counterup, horizontal-timeline, styleswitcher (cargado dos veces; su función `get()` está vacía), la copia 2.17.1 de moment (la 2.ª pisaba a la 1.ª),
  y el *shim* de IE8 (`html5shiv`).
  * Diferencia conocida: `moment` ya no queda en idioma `es` (lo ponía el archivo de idioma de fullcalendar). El sistema solo usa `moment` para
    interpretar/comparar fechas (`moment(x)`, `isValid`, `toDate`), que no dependen del idioma.
* **Versión por fecha del archivo** (`?v=`) en todos los JS propios, en lugar de la hora actual.
* **No se tocó**: el `<meta http-equiv="refresh">` (cierra la sesión a las 2 h aunque la página siga abierta; quitarlo cambia el comportamiento, no es redundante),
  ni `defer` (los scripts ya van al final del `<body>`; no ganaría nada).
* `public/web.config` y `public/theme/dist/web.config`: ver sección 5.

### Backend
* **Informes en Excel** (`ExportRecaudoCAR`, `ExportPrediosPagos`, `ExportCartera` y sus vistas `exports/reporte*EXCEL.blade.php`): las celdas de datos van sin
  `style/width/data-format` y el formato (alineación, `$#,##0_-`, anchos) se aplica una vez por columna en `registerEvents()`.
  **Verificado celda por celda con openpyxl** contra el archivo que generaba el código anterior (valores, tipo, formato numérico, alineación, fuente, relleno, bordes, anchos,
  alturas, celdas combinadas e imagen del logo): 0 diferencias en 121.320, 102.527 y 206.901 celdas (la única diferencia en Cartera es la hora de generación impresa en el encabezado).
* `PrediosController::get_predio`: las "listas de años por factura" se traen con **una** consulta en vez de una por factura, y la consulta idéntica de prescripción/exención ya no se
  ejecuta dos veces. Respuesta idéntica (md5) en 16 predios de prueba (con varias facturas, con facturas de 34 años, sin deuda).
* `PrediosController::autocomplete`: se quitó la tabla derivada de propietarios (join directo) y el producto con `predios_pagos` (se usa un conjunto `DISTINCT id_predio`).
  Respuesta idéntica en 16 términos de búsqueda y en la comparación SQL `EXCEPT` en ambos sentidos; funciona igual con o sin los índices propuestos.
* `ResolucionesIgacController` (y su vista `ResolucionesIgac/create.blade.php`):
  * El nombre de la vista no coincidía en mayúsculas con la carpeta (`resolucionesigac` vs `ResolucionesIgac`); fallaba en Linux, en IIS/Windows no se notaba.
  * La lista ya no vuelca toda la tabla en el HTML: se pagina en el servidor (25 por página, la más reciente primero) y trae un buscador (año, resolución, código, código anterior, tipo y nombre). Antes eran 87.810 filas en la copia de Guateque. Ya no se usa DataTables en esa pantalla.
  * Si la tabla `resoluciones_igac` del municipio no tiene las columnas que la pantalla lista y guarda (`Schema::hasColumns`), se muestra un aviso en vez del error `Undefined property: stdClass::$ano`. La copia local de Guateque tiene la tabla con el diseño del archivo plano del IGAC, por eso allí se ve el aviso.
  * Crear y editar siguen igual (probado con una tabla de prueba): vuelven a la misma página y búsqueda.

## 4. Propuestas NO aplicadas (decide Diego)

### 4.1 Índices (script en `docs/sql/propuesta_indices_rendimiento.sql`)
Cinco índices sobre `predios_pagos` y `pagos`. Lecturas lógicas antes → después en la base local: por `id_predio` 691 → 3, por `factura_pago` 2.096 → 3,
por `numero_recibo` 2.876 → 6, por fecha y banco 2.876 → 25, pagos de un predio 2.876 → 3. Ocupan ~36 MB. No cambian ningún resultado (comprobado con md5 de las respuestas, con y sin índices).
Probar primero en una copia, crear fuera de horario y con respaldo; el script trae cómo revertir.

### 4.2 Cosas que cambiarían el comportamiento (solo documentadas)
* **Seguridad (pendiente, no es de rendimiento)**: la revisión del control de acceso y de las consultas de algunas rutas de consulta y exportación se entrega
  por separado a quien administra el sistema; no se detalla aquí porque este repositorio es público.
* `registro_resolucion_igac` cargaba **toda** la tabla `resoluciones_igac` en el HTML (87.810 filas en Guateque, 2,4 s solo de SQL): **resuelto** con paginación en el servidor y buscador (sección 3).
  Hallazgos de esa pantalla que no se tocaron: el formulario de **actualizar no lleva `@csrf`** (el botón Actualizar responde 419 «La sesión caducó»; el de Crear y el de borrar sí lo llevan) y `update()` solo guarda la columna `resolucion`, no los demás campos que muestra el formulario.
* `list/pagos_fecha` envía todos los pagos del rango como JSON y el navegador los pagina (un año de pagos: 2,5 MB). Paginación en servidor cambiaría la interfaz.
* `autocomplete_check` junta con `STRING_AGG` (sin `ORDER BY`) el propietario y una fila vacía " - ": el orden de esas dos cadenas depende del plan de ejecución. Se
  dejó la consulta como estaba para no alterarlo (reescribirla la hacía ~2,5 veces más rápida pero cambiaba ese orden en 236 filas).
* `get_info_recibo` devuelve la primera de varias filas agrupadas sin `ORDER BY` (puede variar entre ejecuciones, incluso sin ningún cambio).
* Los catálogos (bancos, años, tarifas, tasas) **no se cachearon**: se editan en pantallas del mismo sistema y un `Cache::remember` mostraría datos viejos tras editar.
* Otros informes en Excel (acuerdos, notas, exenciones, prescripciones, avalúos, estado de cuenta) tienen el mismo patrón pero pocas filas; no valía la pena tocarlos.
* Los PDF (DomPDF) del recaudo y las facturas no se midieron: `SP_CALCULO_PREDIAL` y la generación por lotes escriben datos y no se ejecutaron en la base de prueba.
* Sesiones en archivos: Laravel limpia las sesiones viejas en el 2 % de las peticiones (`lottery [2, 100]`) recorriendo `storage/framework/sessions`. Si esa carpeta tiene
  decenas de miles de archivos, **esas peticiones al azar tardan segundos** (puede ser parte del "a veces muy lento"). Revisar cuántos archivos hay en producción y borrar los
  de más de 2 días con una tarea programada.
* Imágenes del rediseño: `escudo-paipa.png` (128 KB), `logo-paipa-full.png` (182 KB) y `fondo-paipa.jpg` (259 KB) se descargan en la primera visita; reducirlas/convertirlas a WebP ahorra ~0,4 MB.

## 5. IIS / Plesk: `public/web.config`

Cambios mínimos y comentados dentro de `<system.webServer>`:
1. `<urlCompression doStaticCompression="true" doDynamicCompression="true" />`: comprime CSS/JS/JSON/SVG y también el HTML y el JSON de Laravel.
   Necesita las funciones de IIS *Compresión de contenido estático* y *dinámico*; si no están instaladas, IIS lo ignora sin error. Los tipos MIME comprimidos (por ejemplo `application/json`)
   se definen en `applicationHost.config` (`httpCompression`), no en `web.config`; si el JSON no sale comprimido, pedirle al administrador:
   `appcmd set config -section:system.webServer/httpCompression /+"dynamicTypes.[mimeType='application/json',enabled='True']" /commit:apphost`.
2. `<clientCache cacheControlMode="UseMaxAge" cacheControlMaxAge="7.00:00:00" />`: 7 días para estáticos (imágenes, fuentes, librerías). `public/theme/dist/web.config`
   lo sube a 365 días solo para el paquete, cuyo nombre lleva el hash del contenido. Los JS propios y `paipa.css/js` van con `?v=<fecha>`, por lo que un despliegue nuevo se ve de inmediato.
3. Tipo MIME `font/woff2` (con `<remove>` antes del `<mimeMap>` para que no falle si ya existe). IIS 8.5 o anterior no lo trae y respondería 404 a las fuentes woff2
   (Font Awesome, Rubik, y las Inter/Source Serif del rediseño, que **solo** existen en woff2).

Cómo validarlo (después de copiar los archivos al sitio):
* Sin errores 500.19: abrir `https://paipa.erpsoftsas.com/` (si falla, restaurar el `web.config` anterior; el XML se validó con un analizador, pero IIS no se pudo probar aquí).
* Compresión: `curl.exe -I -H "Accept-Encoding: gzip" https://paipa.erpsoftsas.com/theme/dist/<archivo del manifest.json>` debe mostrar `Content-Encoding: gzip`; y lo mismo con `https://paipa.erpsoftsas.com/`
  (HTML dinámico). En las herramientas del navegador (pestaña Red, columnas *Tamaño* y *Contenido*) el JS debe pesar ~136 KB y no ~450 KB.
* Caché: en esa misma respuesta, `Cache-Control: max-age=31536000` para `theme/dist/*` y `max-age=604800` para otros estáticos (por ejemplo `/theme/css/animate.css`).
  Segunda visita a cualquier pantalla: las peticiones de estáticos deben aparecer como *(desde caché de disco)* sin red.
* Fuentes: `/theme/less/icons/font-awesome/fonts/fontawesome-webfont.woff2` debe responder 200 con `Content-Type: font/woff2`.
* En Plesk (Windows) la compresión también puede activarse por dominio en *Sitios web y dominios → paipa.erpsoftsas.com → Configuración de IIS*, según la versión.

## 6. Despliegue

Orden sugerido (el sitio sigue funcionando en cada paso; no hay migraciones ni dependencias nuevas de composer):
1. Respaldar `public/web.config` actual, `.env` y la base de datos.
2. Copiar los archivos del repositorio. Incluye `public/theme/dist/` (el paquete ya viene construido).
3. En la carpeta del proyecto (PHP 7.4 de producción):
   ```
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
   (`php artisan optimize` equivale a los dos primeros.) Comprobado en el contenedor local: los tres funcionan, incluida la ruta con *closure* `test-db`. El código no usa `env()` fuera de `config/`.
   **Después de esto, un cambio en `.env` no tiene efecto hasta volver a correr `php artisan config:cache`**, y tras cada despliegue conviene repetir los tres comandos.
   `view:cache` necesita permiso de escritura en `storage/framework/views`.
4. OPcache (PHP en IIS/FastCGI) en `php.ini`; revisar con `php -i | findstr opcache` o con `phpinfo()` que la extensión esté cargada:
   ```
   zend_extension=php_opcache.dll
   opcache.enable=1
   opcache.memory_consumption=192
   opcache.interned_strings_buffer=16
   opcache.max_accelerated_files=20000
   opcache.validate_timestamps=1
   opcache.revalidate_freq=60
   realpath_cache_size=4096K
   realpath_cache_ttl=600
   ```
   (`realpath_cache_*` pesa mucho en Windows. Con `validate_timestamps=1` los cambios de archivos se ven en menos de un minuto; con `0` hay que reiniciar el grupo de aplicaciones
   tras cada despliegue.) En producción, `.env` con `APP_DEBUG=false` y `LOG_LEVEL=warning` o `error`.
   En la copia local de pruebas ya hay OPcache y la diferencia de las tres cachés de Laravel fue menor a 2 ms; en Windows/IIS, con carga de clases más lenta, debe ser mayor (no medido).
5. Aplicar los índices (sección 4.1) cuando se decida.
6. Si cambia `config/theme_assets.php` o cualquier archivo de la lista, **reconstruir el paquete antes de subir**: `php scripts/build_assets.php` (o `php scripts/build_assets.php --check` para
   ver si está al día). Sin PHP en el PC: `docker run --rm -v "<ruta del proyecto>:/app" -w /app <imagen php 7.4> php scripts/build_assets.php`.

### Cómo revertir
* Todo el frontend: poner `ASSETS_BUNDLE=false` en `.env` y `php artisan config:cache` (vuelve a cargar los archivos originales uno por uno; las CDN ya no se usan). O borrar `public/theme/dist/`.
* Cachés de Laravel: `php artisan optimize:clear` (limpia config, rutas y vistas compiladas).
* `web.config`: restaurar el respaldo.
* Excel / `get_predio` / `autocomplete`: son cambios de código; volver al commit anterior de esos archivos.
* Índices: bloque `DROP INDEX` al final del script.

### Cómo comprobar cada mejora en producción
| Mejora | Cómo verla |
|---|---|
| Menos peticiones y sin CDN | Navegador → Herramientas de desarrollo → Red, recargar: debe haber ~15 a 30 peticiones y ninguna a `cdn.*` ni `fonts.googleapis.com` |
| Compresión | Cabecera `Content-Encoding: gzip` y tamaños en la pestaña Red (ver sección 5) |
| Caché de estáticos | 2.ª visita: estáticos "desde caché"; `Cache-Control: max-age` en las cabeceras |
| Excel | Informes → Recaudo Predial y CAR con un año completo: debe bajar en segundos y no en ~40 s; abrir el archivo y comparar con uno generado antes (mismas cifras y formato) |
| Cachés de Laravel / OPcache | `phpinfo()` → opcache habilitado; `bootstrap/cache/config.php` y `routes-v7.php` existen; el tiempo de respuesta de una pantalla simple (pestaña Red, *Esperando* / TTFB) |
| Índices | Antes/después de crear: tiempo de elegir un predio en Registrar pagos (petición `get_predio`), y `SET STATISTICS IO ON` con las consultas del script |

## 7. Cómo se midió (para repetirlo)
* Navegador: API de *Navigation/Resource Timing*, 5 cargas por pantalla en un `iframe` (mediana), sesión de administrador; "antes" = la lista original de recursos con los CDN reales.
  Primera visita: proxy local que limita a 10 Mbps compartidos y suma 70 ms por petición; "después" con `Cache-Control` de 7 días en estáticos para imitar el `web.config` nuevo.
* Servidor: se cargó el *kernel* de Laravel con la sesión real y se contaron las consultas con `DB::listen`; se compararon las respuestas por md5 antes y después de cada cambio.
* Excel: comparación celda por celda con `openpyxl` del archivo anterior contra el nuevo.
