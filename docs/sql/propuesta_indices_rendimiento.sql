/*
    PROPUESTA DE INDICES (SQL Server) - NO es una migracion de Laravel y NO se aplica sola.
    Decide Diego/administrador de la base de datos. Ver docs/RENDIMIENTO.md (seccion "Indices").

    Medido en la copia local de Guateque (predios_pagos 208.659 filas, pagos 156.462 filas):
    cada consulta por id_predio / numero_recibo / factura_pago / fecha_pago pasaba de recorrer
    toda la tabla (691 a 2.876 paginas leidas) a leer entre 3 y 25 paginas. Con los datos de Paipa
    (mas grandes) la diferencia sera mayor. No cambia ningun resultado: los indices solo aceleran la busqueda.

    Costo: espacio (~36 MB en total con los datos locales) y un poco mas de trabajo al insertar un pago
    o al recalcular (predios_pagos). Crear un indice bloquea la tabla unos segundos: hacerlo fuera de
    horario y con respaldo previo. En ediciones Enterprise se puede agregar WITH (ONLINE = ON).

    Antes de crear, revisar si ya existen equivalentes en produccion:
        SELECT t.name AS tabla, i.name AS indice,
               STRING_AGG(c.name, ',') WITHIN GROUP (ORDER BY ic.key_ordinal) AS columnas
        FROM sys.indexes i
        JOIN sys.tables t ON t.object_id = i.object_id
        JOIN sys.index_columns ic ON ic.object_id = i.object_id AND ic.index_id = i.index_id AND ic.is_included_column = 0
        JOIN sys.columns c ON c.object_id = ic.object_id AND c.column_id = ic.column_id
        WHERE t.name IN ('pagos', 'predios_pagos') AND i.type > 0
        GROUP BY t.name, i.name ORDER BY t.name, i.name;
*/

-- 1) Todas las consultas de un predio: get_predio, avaluos_predio, estado_cuenta_predio, show_predios_datos,
--    informes por predio. Hoy recorren predios_pagos completa porque id_predio no es la primera columna de ningun indice.
CREATE NONCLUSTERED INDEX IX_predios_pagos_predio_anio
    ON predios_pagos (id_predio, ultimo_anio)
    INCLUDE (pagado, anulada, factura_pago);

-- 2) Busqueda por numero de factura: get_info_pago, get_info_recibo (anios anteriores), al registrar/anular un pago
--    (UPDATE predios_pagos ... WHERE factura_pago = ...).
CREATE NONCLUSTERED INDEX IX_predios_pagos_factura
    ON predios_pagos (factura_pago)
    INCLUDE (id_predio, ultimo_anio, pagado);

-- 3) pagos por numero de recibo (get_info_recibo del anio en curso, listado por numero de recibo, validar duplicados).
CREATE NONCLUSTERED INDEX IX_pagos_numero_recibo
    ON pagos (numero_recibo);

-- 4) Listado de pagos por rango de fechas y banco (pantalla Registrar pagos).
CREATE NONCLUSTERED INDEX IX_pagos_fecha_banco
    ON pagos (fecha_pago, id_banco_factura);

-- 5) Pagos de un predio.
CREATE NONCLUSTERED INDEX IX_pagos_id_predio
    ON pagos (id_predio);

/* ---- Para revertir ----
DROP INDEX IF EXISTS IX_predios_pagos_predio_anio ON predios_pagos;
DROP INDEX IF EXISTS IX_predios_pagos_factura     ON predios_pagos;
DROP INDEX IF EXISTS IX_pagos_numero_recibo       ON pagos;
DROP INDEX IF EXISTS IX_pagos_fecha_banco         ON pagos;
DROP INDEX IF EXISTS IX_pagos_id_predio           ON pagos;
*/
