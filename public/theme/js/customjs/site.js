$(document).ready(function() {

    applyOnlyNumbers();

    if ($('#btn_generar_password')) {
        var generatePassword = (
                length = 8,
                wishlist = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz~!@-#$"
            ) => Array(length)
            .fill('')
            .map(() => wishlist[Math.floor(crypto.getRandomValues(new Uint32Array(1))[0] / (0xffffffff + 1) * wishlist.length)])
            .join('');

        $('#btn_generar_password').off('click').on('click', function() {
            var pass = generatePassword();
            $('#password').val(pass);
            $('#password_confirmation').val(pass);
            $('#div_random_text').text(pass);
        });
    }

    // (El menú ahora es horizontal y lo maneja paipa.js: se retiró el armado del menú lateral que usaba .padre/.opcion.)

    if ($('#search_control').length > 0) {
        $('#search_control').off('keyup').on('keyup', function() {
            var action = $('#link_search').attr('url');
            $('#form_search').attr('action', action + encodeURIComponent($(this).val()));
        });
    }

    iniciarAvisoSesion();
});

/* ------------------------------------------------------------------
   Campos solo numéricos. Se puede cortar, copiar y pegar: lo pegado se
   limpia (se quita todo lo que no sea dígito) y se respeta el maxlength.
   ------------------------------------------------------------------ */
function applyOnlyNumbers() {
    $('.onlyNumbers').off('paste.soloNumeros').on('paste.soloNumeros', function() {
        var campo = $(this);
        setTimeout(function() {
            var limpio = String(campo.val()).replace(/\D/g, '');
            var max = parseInt(campo.attr('maxlength'), 10);
            if (max > 0) { limpio = limpio.substring(0, max); }
            campo.val(limpio).trigger('keyup');
        }, 0);
    });

    $('.onlyNumbers').off('keypress').on('keypress', function(evt) {
        if (evt.ctrlKey || evt.metaKey) { return; } // Ctrl+C, Ctrl+V, Ctrl+X, Ctrl+A
        if (evt.which !== 8 && evt.which !== 0 && (evt.which < 48 || evt.which > 57)) {
            evt.preventDefault();
            return false;
        }
    });

    $('.onlyNumbers').off('keyup').on('keyup', function(evt) {
        if ($.trim($(this).val()).length > 0) {
            $(this).trigger('change');
        }
    });

    $('.onlyNumbers').off('change').on('change', function(evt) {
        if ($(this).val().length > 0) {
            $(this).val($(this).val().replace(/\D/g, ''));
        }
    });
}

/* ------------------------------------------------------------------
   Texto del servidor que se va a insertar como HTML: se escapa siempre.
   ------------------------------------------------------------------ */
function escHtml(valor) {
    if (valor === null || valor === undefined) { return ''; }
    return String(valor)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

// Igual, pero conserva los saltos de línea <br /> que el servidor usa para separar propietarios.
function escHtmlConSaltos(valor) {
    return escHtml(valor).replace(/&lt;br\s*\/?&gt;/gi, '<br />');
}

/* ------------------------------------------------------------------
   Avisos de error comunes. Antes los fallos del servidor solo quedaban
   en la consola del navegador y el funcionario no se enteraba.
   ------------------------------------------------------------------ */
var avisoErrorUltimo = 0;

function quitarBloqueoPantalla() {
    try { if ($.unblockUI) { $.unblockUI(); } } catch (e) { /* sin blockUI en esta pantalla */ }
}

function avisoError(titulo, texto, alAceptar) {
    var ahora = new Date().getTime();
    if (ahora - avisoErrorUltimo < 1500) { return; } // varias peticiones fallando a la vez: un solo aviso
    avisoErrorUltimo = ahora;
    quitarBloqueoPantalla();
    if (typeof swal === 'function') {
        swal({
            title: titulo,
            text: texto,
            type: 'error',
            confirmButtonText: 'Aceptar',
            closeOnConfirm: true
        }, function() {
            if (typeof alAceptar === 'function') { alAceptar(); }
        });
    } else {
        window.alert(titulo + '\n\n' + texto);
        if (typeof alAceptar === 'function') { alAceptar(); }
    }
}

function avisoSesionVencida() {
    avisoError('Su sesión venció',
        'Por seguridad la sesión se cerró. Ingrese de nuevo para continuar. Lo que no se alcanzó a guardar debe volver a registrarse.',
        function() { window.location.href = '/'; });
}

// Traduce la respuesta fallida del servidor a un mensaje para el funcionario.
function mensajeErrorHttp(estado, respuesta) {
    if (estado === 0) {
        return ['Sin conexión', 'No se pudo comunicar con el servidor. Revise la conexión a internet e intente de nuevo.'];
    }
    if (estado === 422) {
        var lista = [];
        if (respuesta && respuesta.errors) {
            $.each(respuesta.errors, function(campo, mensajes) {
                $.each([].concat(mensajes), function(i, m) { lista.push('- ' + m); });
            });
        }
        return ['Revise los datos', lista.length > 0 ? lista.join('\n') : 'Hay datos incompletos o con un formato que no corresponde.'];
    }
    if (estado === 403) {
        return ['Sin permiso', 'Su usuario no tiene permiso para realizar esta operación.'];
    }
    if (estado === 404) {
        return ['No encontrado', 'No se encontró la información solicitada. Actualice la página e intente de nuevo.'];
    }
    if (estado === 413) {
        return ['Archivo muy grande', 'El archivo supera el tamaño permitido por el servidor.'];
    }
    if (estado === 429) {
        return ['Demasiados intentos', 'Espere un momento antes de intentar de nuevo.'];
    }
    return ['No se pudo completar la operación',
        'El servidor presentó un error (código ' + estado + '). Antes de repetir la operación verifique si quedó registrada. Si el problema continúa, informe a soporte.'];
}

$(document).ajaxError(function(evt, xhr, settings, motivo) {
    if (motivo === 'abort' || (settings && settings.sinAvisoGlobal)) { return; }
    var estado = xhr ? xhr.status : 0;
    var texto = xhr && xhr.responseText ? String(xhr.responseText) : '';

    if (estado === 401 || estado === 419) { avisoSesionVencida(); return; }
    // La sesión venció y el servidor contestó con la página de ingreso en vez de datos.
    if (estado === 200 && motivo === 'parsererror' && /<form[^>]*login|name="password"/i.test(texto)) {
        avisoSesionVencida();
        return;
    }
    if (motivo === 'timeout') {
        avisoError('El servidor tardó demasiado', 'No se recibió respuesta a tiempo. Antes de repetir la operación verifique si quedó registrada.');
        return;
    }
    if (estado === 200 && motivo === 'parsererror') {
        avisoError('Respuesta inesperada', 'El servidor contestó algo que no se pudo leer. Antes de repetir la operación verifique si quedó registrada.');
        return;
    }
    var m = mensajeErrorHttp(estado, xhr ? xhr.responseJSON : null);
    avisoError(m[0], m[1]);
});

/* ------------------------------------------------------------------
   Doble envío. Mientras una operación viaja al servidor, el botón que la
   lanzó queda deshabilitado: un doble clic no registra dos veces.
   ------------------------------------------------------------------ */
$(document).ajaxSend(function(evt, xhr, settings) {
    if (!settings || String(settings.type).toUpperCase() === 'GET') { return; }
    var activo = document.activeElement;
    if (activo && /^(BUTTON|INPUT)$/.test(activo.tagName) && !activo.disabled && $(activo).closest('.sweet-alert').length === 0 &&
        (activo.tagName === 'BUTTON' || /^(submit|button)$/i.test(activo.type))) {
        activo.disabled = true;
        settings.botonBloqueado = activo;
    }
});
$(document).ajaxComplete(function(evt, xhr, settings) {
    if (settings && settings.botonBloqueado) {
        settings.botonBloqueado.disabled = false;
        settings.botonBloqueado = null;
    }
    reiniciarAvisoSesion();
});

// Formularios normales (los que recargan la página). Este manejador corre después de la validación del
// formulario: si la validación lo detuvo (preventDefault), no se bloquea nada.
$(document).on('submit', 'form', function(evt) {
    var formulario = $(this);
    if (evt.isDefaultPrevented()) { return; }
    if (formulario.data('enviando')) {
        evt.preventDefault();
        return false;
    }
    formulario.data('enviando', true);
    var botones = formulario.find('button[type="submit"], input[type="submit"]').filter(':enabled');
    setTimeout(function() { botones.prop('disabled', true).addClass('enviando'); }, 0);
    // Si el envío no cambia de página (descarga de un archivo), el formulario se libera solo.
    setTimeout(function() {
        formulario.data('enviando', false);
        botones.prop('disabled', false).removeClass('enviando');
    }, 12000);
});
window.addEventListener('pageshow', function() {
    $('form').data('enviando', false);
    $('.enviando').prop('disabled', false).removeClass('enviando');
});

/* ------------------------------------------------------------------
   Descargas (Excel y PDF). Se revisa la respuesta antes de guardar el
   archivo: antes, si el servidor fallaba, se guardaba la página de error
   con nombre .xlsx y Excel la reportaba como archivo dañado.
   ------------------------------------------------------------------ */
function revisarDescarga(resp) {
    var tipo = (resp.headers.get('content-type') || '').toLowerCase();
    if (resp.status === 401 || resp.status === 419 || (resp.ok && resp.redirected && tipo.indexOf('text/html') === 0)) {
        avisoSesionVencida();
        throw new Error('sesion vencida');
    }
    if (!resp.ok) {
        var m = mensajeErrorHttp(resp.status, null);
        avisoError('No se pudo generar el archivo', m[1]);
        throw new Error('descarga ' + resp.status);
    }
    if (tipo.indexOf('text/html') === 0) {
        avisoError('No se pudo generar el archivo', 'El servidor no devolvió el archivo. Revise los datos de la consulta (fechas, predio) e intente de nuevo.');
        throw new Error('descarga sin archivo');
    }
    return resp.blob().then(function(blob) {
        if (!blob || blob.size === 0) {
            avisoError('No se pudo generar el archivo', 'El servidor devolvió un archivo vacío. Revise los datos e intente de nuevo; si continúa, informe a soporte.');
            throw new Error('descarga vacia');
        }
        return blob;
    });
}

// Nombre que el servidor le puso al archivo (cabecera Content-Disposition).
function nombreDescarga(resp, porDefecto) {
    var cab = resp.headers.get('content-disposition') || '';
    var m = /filename\*=(?:UTF-8'')?([^;]+)/i.exec(cab);
    if (m) { try { return decodeURIComponent(m[1].replace(/"/g, '')); } catch (e) { /* sigue */ } }
    m = /filename="?([^";]+)"?/i.exec(cab);
    return m ? m[1] : porDefecto;
}

function guardarBlob(blob, nombre) {
    var url = window.URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.style.display = 'none';
    a.href = url;
    a.download = nombre;
    document.body.appendChild(a);
    a.click();
    setTimeout(function() {
        window.URL.revokeObjectURL(url);
        if (a.parentNode) { a.parentNode.removeChild(a); }
    }, 1000);
}

// Descarga un archivo y avisa si falla. Devuelve una promesa: true si se guardó, false si no.
function descargarArchivo(direccion, nombrePorDefecto) {
    var nombre = nombrePorDefecto;
    return fetch(direccion, { credentials: 'same-origin' })
        .then(function(resp) {
            nombre = nombreDescarga(resp, nombrePorDefecto);
            return revisarDescarga(resp);
        })
        .then(function(blob) {
            guardarBlob(blob, nombre);
            return true;
        })
        .catch(function(error) {
            if (error && error instanceof TypeError) {
                avisoError('Sin conexión', 'No se pudo comunicar con el servidor. Revise la conexión a internet e intente de nuevo.');
            }
            return false;
        });
}

/* ------------------------------------------------------------------
   Sesión. Reemplaza la redirección automática que había en la página
   (salía a los N minutos de abrirla, aunque se estuviera trabajando).
   Ahora el tiempo cuenta desde la última operación con el servidor y,
   cinco minutos antes, se pregunta si se quiere seguir.
   ------------------------------------------------------------------ */
var sesionMinutos = 0;
var sesionTemporizadorAviso = null;
var sesionTemporizadorFin = null;

function iniciarAvisoSesion() {
    sesionMinutos = parseInt($('body').attr('data-sesion-minutos'), 10) || 0;
    reiniciarAvisoSesion();
}

function reiniciarAvisoSesion() {
    if (!(sesionMinutos > 0)) { return; }
    clearTimeout(sesionTemporizadorAviso);
    clearTimeout(sesionTemporizadorFin);
    var total = sesionMinutos * 60 * 1000;
    var antes = Math.min(5 * 60 * 1000, total / 2);
    sesionTemporizadorAviso = setTimeout(function() {
        if (typeof swal !== 'function') { return; }
        swal({
            title: 'Su sesión está por cerrarse',
            text: 'Lleva un buen rato sin operaciones con el servidor. ¿Desea seguir trabajando?',
            type: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Seguir trabajando',
            cancelButtonText: 'Cerrar sesión',
            closeOnConfirm: true,
            closeOnCancel: true
        }, function(seguir) {
            if (seguir) {
                // Cualquier petición con sesión la renueva; al completarse se reinicia el contador.
                $.ajax({ url: '/profile', type: 'GET', dataType: 'html', sinAvisoGlobal: true })
                    .fail(function() { avisoSesionVencida(); });
            } else {
                window.location.href = '/logout';
            }
        });
    }, total - antes);
    sesionTemporizadorFin = setTimeout(function() { window.location.href = '/'; }, total);
}

/* ------------------------------------------------------------------
   Pulido común de las pantallas (no cambia lo que hace ninguna):
   - título de la pestaña con el nombre de la pantalla;
   - etiquetas ligadas a su campo (clic en la etiqueta lleva al campo);
   - asterisco en los campos obligatorios, leído de las reglas de
     validación que ya tiene cada formulario;
   - nombre en los botones que solo tienen ícono;
   - X para cerrar en los modales que tienen botón de cerrar al pie;
   - avisos de la página anunciados a los lectores de pantalla.
   ------------------------------------------------------------------ */
var pulidoContador = 0;

function pulirPantalla() {
    // Título de la pestaña: "Predios | Impuesto Predial Unificado | Alcaldía de ..."
    var nombrePantalla = $.trim($('#contenido .bg-title .page-title').first().text());
    if (nombrePantalla.length > 0 && document.title.indexOf(nombrePantalla + ' | ') !== 0) {
        document.title = nombrePantalla + ' | ' + document.title;
    }
    $('#contenido .bg-title .page-title').first().attr({ role: 'heading', 'aria-level': '1' });

    // Etiquetas sin "for": se ligan al único campo de su grupo.
    $('label:not([for])').each(function() {
        var etiqueta = $(this);
        if (etiqueta.find('input, select, textarea').length > 0) { return; } // ya envuelve su campo
        var grupo = etiqueta.closest('.form-group');
        if (grupo.length === 0) { grupo = etiqueta.parent(); }
        if (grupo.find('label').length !== 1) { return; }
        var campos = grupo.find('input, select, textarea').not('[type="hidden"]').not('.bs-searchbox input');
        if (campos.length !== 1) { return; }
        var campo = campos.first();
        if (!campo.attr('id')) { campo.attr('id', 'campo_auto_' + (++pulidoContador)); }
        etiqueta.attr('for', campo.attr('id'));
    });

    // Campos obligatorios según las reglas de validación del formulario.
    $('form').each(function() {
        var formulario = $(this);
        var validador = $.data(this, 'validator');
        if (!validador || !validador.settings || !validador.settings.rules) { return; }
        var marcados = 0;
        $.each(validador.settings.rules, function(nombre, reglas) {
            var lista = reglas && reglas.laravelValidation ? reglas.laravelValidation : [];
            var obligatorio = reglas && reglas.required === true;
            $.each(lista, function(i, regla) { if (regla && regla[0] === 'Required') { obligatorio = true; } });
            if (!obligatorio) { return; }
            var campo = formulario.find('[name="' + nombre + '"], [name="' + nombre + '[]"]').not('[type="hidden"]').first();
            if (campo.length === 0) { return; }
            campo.attr('aria-required', 'true');
            var etiqueta = campo.attr('id') ? formulario.find('label[for="' + campo.attr('id') + '"]').first() : $();
            if (etiqueta.length === 0) { etiqueta = campo.closest('.form-group').find('label').first(); }
            if (etiqueta.length === 0 || etiqueta.find('.obligatorio-marca').length > 0) { return; }
            etiqueta.append('<span class="obligatorio-marca" title="Obligatorio" aria-hidden="true">*</span>');
            marcados++;
        });
        if (marcados > 0 && formulario.find('.obligatorio-leyenda').length === 0) {
            formulario.prepend('<p class="obligatorio-leyenda"><span class="obligatorio-marca" aria-hidden="true">*</span> Campo obligatorio</p>');
        }
    });

    nombrarBotonesDeIcono(document);

    // Modales sin X: se agrega una que hace lo mismo que el botón de cerrar del pie.
    $('.modal').each(function() {
        var modal = $(this);
        var cabecera = modal.find('.modal-header').first();
        var cerrarPie = modal.find('.modal-footer [data-dismiss="modal"]').first();
        if (cabecera.length === 0 || cerrarPie.length === 0 || cabecera.find('.close').length > 0) { return; }
        var equis = $('<button type="button" class="close" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>');
        equis.on('click', function() { if (!cerrarPie.prop('disabled')) { cerrarPie.trigger('click'); } });
        cabecera.prepend(equis);
    });
    $('.modal-header .close[aria-hidden="true"]').removeAttr('aria-hidden').attr('aria-label', 'Cerrar');

    $('#contenido .alert').attr('role', 'alert');
}

// Botones que solo muestran un ícono: reciben nombre (ayuda al pasar el ratón y para lectores de pantalla).
function nombrarBotonesDeIcono(raiz) {
    var nombres = [
        ['modify_row', 'Editar'], ['delete_row', 'Eliminar'],
        ['fa-pencil', 'Editar'], ['fa-trash', 'Eliminar'], ['fa-search', 'Buscar'], ['fa-upload', 'Cargar archivo'],
        ['fa-print', 'Imprimir'], ['fa-file-excel-o', 'Descargar Excel'], ['fa-file-pdf-o', 'Descargar PDF'],
        ['fa-download', 'Descargar'], ['fa-eye', 'Ver'], ['fa-plus', 'Agregar'], ['fa-times', 'Quitar'], ['fa-copy', 'Copiar']
    ];
    $(raiz).find('button, a.btn').each(function() {
        var boton = $(this);
        if ($.trim(boton.text()).length > 0) { return; }
        var actual = boton.attr('title') || boton.attr('data-original-title') || boton.attr('aria-label');
        if (!actual) {
            var marca = (boton.attr('class') || '') + ' ' + (boton.find('i').attr('class') || '');
            for (var i = 0; i < nombres.length; i++) {
                if (marca.indexOf(nombres[i][0]) !== -1) { actual = nombres[i][1]; break; }
            }
            if (!actual) { return; }
            boton.attr('title', actual);
        }
        if (!boton.attr('aria-label')) { boton.attr('aria-label', actual); }
    });
}

// Se corre al terminar de cargar (las reglas de validación de cada formulario ya están puestas).
$(window).on('load', function() { pulirPantalla(); });
// Las filas de las tablas paginadas se nombran cada vez que la tabla se dibuja.
$(document).on('draw.dt', function(evt) { nombrarBotonesDeIcono(evt.target); });
