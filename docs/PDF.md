# PDF del sistema de predial

Todos los PDF se generan con `barryvdh/laravel-dompdf` 0.9 (dompdf 1.0.2, solo CSS 2.1: tablas, bordes y
colores; sin flex ni grid) en PHP 7.4. Cada documento carga su vista con `PDF::loadView($vista, $data)`.
La misma base de código se despliega en varios municipios; qué plantilla usa cada uno sale de la tabla
`parametros` de **su** base de datos (ver `docs/MULTIMUNICIPIO.md`).

## Qué plantilla usa cada documento

| Documento | Ruta | Vista | Papel | Quién la elige |
|---|---|---|---|---|
| Certificado de paz y salvo | `generate_paz_pdf/{id}/{destino}/{fecha}/{valor}` | `parametros.formato-paz` | A4 vertical | **por municipio** |
| Factura / liquidación oficial | `generate_factura_pdf/...` | `parametros.formato-factura` | A4 vertical | **por municipio** (oficial) |
| Factura de acuerdo de pago | `generate_factura_acuerdo_pdf/...`, `regenerate_factura_acuerdo_pdf/...` | `parametros.formato-acuerdo` | A4 vertical | **por municipio** (oficial) |
| Estado de cuenta del predio | `generate_estado_cuenta_predio_pdf/{id}` | `predios.estadoCuentaPDF` | A4 vertical | compartida |
| Histórico de pagos | `generate_avaluos_predio_pdf/{id}` | `predios.avaluosPDF` | A4 vertical | compartida |
| Lista de exenciones | `generate_exenciones_pdf/{desde}/{hasta}` | `predios.exencionesPDF` | A4 horizontal | compartida |
| Lista de prescripciones | `generate_prescripciones_pdf/{desde}/{hasta}` | `predios.prescripcionesPDF` | A4 horizontal | compartida |
| Informe de recaudo (relación de ingresos) | `export-pdf-recaudo-car/{ini}/{fin}/{bancoI}/{bancoF}` | `exports.reporteRecaudoCARPDF` | A3 horizontal (lo fija el controlador) | compartida |
| Validación pública del QR | `validate-qr/{token}` | `qr.validation-result` | página web, no PDF | compartida (la rediseñó el trabajo de identidad: `paipa.css`) |

La "nota a factura" no tiene PDF (la ruta `generate_nota_factura_pdf` está comentada); lo único que hay en
Notas es la lista de exenciones reutilizada.

### Plantillas por municipio

Valores que se deducen de las plantillas que existen en el repositorio. En la base local solo está Guateque;
**confirmar en cada servidor el valor real de `formato-paz`, `formato-factura` y `formato-acuerdo`**.

| Municipio | `formato-paz` | `formato-factura` | `formato-acuerdo` |
|---|---|---|---|
| Guateque | `predios.pazPDF` (compartida, rediseñada) | `predios.facturaPDF_gua` | `acuerdos.facturaAcuerdoPDF_gua` |
| Sutatenza | `predios.pazPDF` (compartida, rediseñada) | `predios.facturaPDF_sut` | `acuerdos.facturaAcuerdoPDF_sut` |
| Sesquilé | `predios.pazPDF` (compartida, rediseñada) | `predios.facturaPDF` (genérica) | sin plantilla propia (se usó `_gua` en las pruebas) |
| Paipa | `predios.pazPDF_pai` (oficial) | `predios.facturaPDF_pai` | `acuerdos.facturaAcuerdoPDF_pai` |
| La Capilla | `predios.pazPDF_lacap` (oficial) | `predios.facturaPDF_lacap` | `acuerdos.facturaAcuerdoPDF_lacap` |
| Santa Rosa de Viterbo | `predios.pazPDF_srosa` (oficial) | `predios.facturaPDF_srosa` | `acuerdos.facturaAcuerdoPDF_srosa` |
| Villa de Leyva | `predios.pazPDF_villa` (oficial) | `predios.facturaPDF_villa` | sin plantilla propia |

## Qué se rediseñó y qué se dejó oficial

**Rediseñado con el estilo del portal de pago (psepaipa)**, una sola plantilla para todos los municipios:
`predios.pazPDF`, `predios.estadoCuentaPDF`, `predios.avaluosPDF`, `predios.exencionesPDF`,
`predios.prescripcionesPDF` y `exports.reporteRecaudoCARPDF`. Mismos datos, mismas consultas y mismas sumas que
antes; solo cambia la presentación (franja verde con acento dorado, escudo y nombre del municipio a la
izquierda, caja verde con el dato principal del documento a la derecha, tablas con encabezado verde y filas
alternas, pie con la dependencia, el NIT, la dirección y la ubicación, y número de página). El encabezado y el
pie se repiten en cada hoja.

El certificado de paz y salvo compartido conserva el texto legal tal cual ("EL SUSCRITO SECRETARIO DE HACIENDA
MUNICIPAL / CERTIFICA...", "El cual se encuentra a PAZ Y SALVO...", "NO SE COBRA IMPUESTO POR VALORIZACIÓN.")
y los mismos datos. Cambios de presentación a tener en cuenta:

- Ahora imprime el **código QR de verificación** que ya se generaba y se guardaba en
  `predios_validaciones_qr` pero esta plantilla no mostraba (las de Paipa y las demás oficiales sí lo muestran).
  Si un municipio no lo quiere, basta quitar el bloque `@if(!empty($qr_token) ...)` de `pazPDF.blade.php`.
- "Cod. postal" solo se imprime cuando el municipio tiene ese parámetro (antes salía el rótulo vacío).
- "Página 1 de 1" se conserva cuando el certificado cabe en una hoja (hasta 10 propietarios); con más
  propietarios el certificado sigue en una segunda hoja y el pie dice "Página N".
- Se agregó "Código catastral" a la tabla del predio (ya salía en el párrafo).

**Oficiales por municipio, sin tocar** (los entregó o aprobó cada tesorería): `pazPDF_pai`, `pazPDF_lacap`,
`pazPDF_srosa`, `pazPDF_villa`, todas las `facturaPDF*` (código de barras GS1-128 y datos de pago) y todas las
`facturaAcuerdoPDF*`. Se revisó que renderizan bien para los siete municipios (capturas en
`predial_paipa_capturas/pdf/`). Sus textos y nombres de firmantes están escritos en cada plantilla.

## Cómo está construido

```
app/Support/PdfIdentidad.php           identidad para PDF: nombre, dependencia, NIT, dirección, ubicación, escudo y colores
resources/views/pdf/_estilos.blade.php CSS común (papel, márgenes, encabezado, pie, fichas, tablas)
resources/views/pdf/_marco.blade.php   banda superior + encabezado + pie (se repiten en cada hoja)
```

Cada vista compartida empieza con `@php($id = \App\Support\PdfIdentidad::datos($logo ?? null, $alcaldia ?? null, $nit ?? null))`,
incluye `pdf._estilos` (con el papel: `'tam' => 'A4 portrait'`) y `pdf._marco` (con la caja de la derecha:
`'caja' => ['rotulo' => ..., 'valor' => ..., 'l1' => ..., 'l2' => ...]`).

- `PdfIdentidad` toma nombre, NIT, dirección, ubicación y la dependencia ("Secretaría de Hacienda" salvo que el
  perfil del municipio traiga otra) de `App\Support\Municipio` y, si falta algún dato, de lo que ya pasa el
  controlador. **Nunca se escribe el nombre de un municipio en una plantilla compartida.**
- Los colores salen del perfil (`color`, `color_oscuro`, `color_acento` de `config/municipio.php`); por omisión
  el verde `#1fa49d` / `#17756f` y el dorado `#c99a2e`.
- El escudo es el parámetro `logo` (`public/theme/plugins/images/<logo>`). Se reduce a 200 px y se incrusta como JPEG
  sobre blanco: los archivos originales pesan hasta 900 KB y los PDF de varias hojas lo repetían en cada hoja
  (un estado de cuenta pasó de 425 KB a 29 KB). Se ajusta a una caja de 58 pt conservando la proporción, así que
  escudos altos, anchos o cuadrados quedan bien. Si el archivo no existe, el encabezado sale sin escudo en vez de
  fallar.
- Si un municipio necesita otra dependencia ("Tesorería Municipal") en los documentos compartidos, se le pone
  `secretaria` en su perfil de `config/municipio.php` (o `MUNICIPIO_SECRETARIA` en el `.env`).

### Trampas de dompdf 1.0.2 que ya se resolvieron (no volver a caer)

- **No poner `margin` en `html`** (`html, body { margin: 0 }`): anula los márgenes de `@page`. Usar solo `body`.
- El `@php(...)` de una línea y los bloques `@php ... @endphp` no se pueden mezclar en la misma vista: Blade se
  traga el HTML entre ellos. En una vista con bloques, el `$id = ...` también va en bloque.
- El PHP incrustado en el HTML (`<script type="text/php">`, `page_script`) está **apagado** (`enable_php` es
  false). Por eso el número de página usa `counter(page)` y no puede decir "de N". La plantilla del informe de
  recaudo de antes tenía un script de numeración que nunca se ejecutaba.
- Encabezado y pie con `position: fixed` y márgenes de `@page`: así se repiten en cada hoja.
- Las listas largas usan `border-collapse: separate` y solo línea inferior: con `collapse` dompdf gastaba mucha
  más memoria (prescripciones con 875 filas: 626 MB frente a 366 MB). `thead { display: table-header-group }`
  repite el encabezado de la tabla en cada hoja.
- Tipografía Helvetica (fuente base del PDF, no se incrusta). Las plantillas anteriores incrustaban LATAM Sans
  completa y cada PDF pesaba unos 430 KB.

## Cómo añadir un municipio

1. **Base de datos del municipio**: en su tabla `parametros`, las filas `alcaldia`, `logo`, `nit`, `direccion`,
   `ubicacion`, `ean`, `formato-paz`, `formato-factura` y `formato-acuerdo`. Si el municipio no tiene formato
   oficial propio de paz y salvo, usar `predios.pazPDF` (queda con su nombre, NIT, escudo y colores sin hacer nada más).
2. **Escudo**: copiar `logo-<municipio>.png` a `public/theme/plugins/images/` y ponerlo en `parametros.logo`.
   Cualquier proporción sirve; conviene un PNG de al menos 200 px.
3. **Perfil** (opcional): en `config/municipio.php`, `perfiles['<slug>']` con `ciudad`, `departamento`, `secretaria`,
   y colores propios si los tiene (ver `docs/MULTIMUNICIPIO.md`).
4. **Formato oficial propio** (solo si la tesorería entrega uno): copiar `predios/pazPDF_<sufijo>.blade.php` (o
   `facturaPDF_<sufijo>`, `facturaAcuerdoPDF_<sufijo>`) de un municipio parecido, ajustar los textos y el firmante y
   poner `predios.pazPDF_<sufijo>` en el parámetro. Las facturas llevan el código de barras GS1-128: no cambiar los
   campos, el orden de los datos de pago ni las dimensiones del código.
5. **Probar**: generar cada documento (paz y salvo, factura vista previa, estado de cuenta, histórico, informe) y
   revisar el nombre, el NIT, el escudo y que un nombre largo no rompa el encabezado. Se puede simular otro
   municipio cambiando temporalmente esos parámetros en la base de pruebas (guardar los valores originales y
   restaurarlos, y `php artisan cache:clear` porque `Municipio` guarda `parametros` diez minutos).

## Factura de Paipa: formato de la Alcaldía (2026-11)

`predios.facturaPDF_pai` reproduce la hoja que entregó la Alcaldía (A4, una página): encabezado con escudo y número de
liquidación, datos del predio, tabla de vigencias, competencia y determinación con el valor en letras, cajas de total y
«pague hasta», notificación/recurso/mérito ejecutivo, firma del Secretario y dos cupones (banco y municipio) con GS1-128.
El texto legal lo validó el abogado de la Alcaldía: no se cambia sin su visto bueno.

**La misma hoja la dibuja el portal de pago en línea** (repositorio `psepaipa`, `src/FacturaPdf.php`, con TCPDF): las dos
deben verse y decir lo mismo. Si se cambia el texto, las columnas, la regla del cupón o la firma en una, se cambia en la otra.

- **Un solo total y un solo «pague hasta»**: los del descuento que aplica hoy, o los del día de impresión si ya no queda
  ninguno. Si pasa la fecha hay que imprimir otra factura. Lo elige `App\Support\FacturaPaipa::cuponVigente`, que
  `PrediosController` llama (en las dos funciones que generan la factura) solo cuando el formato contiene `facturaPDF_pai`,
  antes de armar el código de barras. Los otros formatos no cambian.
- **Columnas**: impuesto, intereses, sobretasa ambiental, intereses de la sobretasa, alumbrado y descuentos (conceptos 13 y
  15, en negativo). «Otros» (saldos, notas y cualquier concepto que no tenga columna) solo aparece si algún predio lo
  necesita, para que cada fila siga sumando su total. Si el valor a pagar es el de la segunda o tercera fecha de descuento,
  la última vigencia lo absorbe en «Descuentos» y la tabla coincide con el total a pagar.
- **Más de cinco vigencias**: el controlador acumula las más viejas en una fila «Antes de AAAA» (sin avalúo ni tarifa).
  Con este formato lo hace siempre, también en Guateque y Sutatenza (que con su formato propio listan todas las
  vigencias): sin la fila acumulada la hoja no cabe en una página.
- **Tasa de mora**: la de `tasas_interes` del mes de impresión (o la del último mes cargado): `tasa_mensual` × 12, en % E.A.
- **Propietario**: solo el principal (jerarquía 001), con nombre y documento completos, como lo pidió la Alcaldía.
- **Firma**: `public/theme/plugins/images/firma-paz-y-salvo-paipa-3.png` (la del paz y salvo) y el nombre del Secretario
  de Hacienda están en la vista; si cambia el Secretario hay que cambiar ambos.
- **Puntos de pago**: lista fija, igual a la factura anterior. El código de barras usa `parametros.ean` (en la hoja de
  muestra es 7709998086715: confirmar que es el de Paipa).
- Probado con datos de Guateque cambiando `formato-factura` en la base local (se restauró): vista previa y factura real,
  una y varias vigencias, y con datos inventados (réplica de la hoja, fila acumulada, «Otros», descuento de otra fecha, ya
  pagada, sin datos). Los dos códigos de barras decodifican a GS1-128 (`]C1`) con los cuatro campos. La pantalla de
  «ya pagada» y la vista previa conservan su aviso en rojo.

## Hallazgos que no se tocaron

- `predios.facturaPDF` (genérica, la que usaría Sesquilé) tiene los encabezados de columna provisionales
  "DSCTO / 14 / DSCTO / (vacío)" en el detalle de pago. Los conceptos 13 a 16 se llaman distinto en cada municipio
  (`conceptos_predio.nombre`), así que no se puede rotular sin confirmar con cada uno. Es una factura oficial: no se cambió.
- `exportPdfRecaudoCAR` fija `memory_limit` en 512 MB y dompdf necesita unos 0,5 MB por fila: el informe en PDF
  falla (sin mensaje) a partir de unas 900 filas (un trimestre de Guateque tiene 6 135). Pasaba igual con la plantilla
  anterior (que gastaba un 13 % más). Para períodos largos conviene el Excel, o partir el informe por banco/fecha.
- `QrCodeHelper::generateQrCode` pide el QR a `api.qrserver.com` (y luego a Google Charts) en cada paz y salvo,
  con espera de hasta 30 s por intento si no hay internet; solo si ambos fallan dibuja un QR de relleno.
- La lista "Lista de propietarios" del paz y salvo compartido se imprime en el orden en que la entrega la
  consulta (por ejemplo 004, 003, 002, 001, 019...), igual que antes.
