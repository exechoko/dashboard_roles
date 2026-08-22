# Control de armas y personal

## Crear una retención

Disponible con permiso `crear-arma-retencion`.

1. Abrí [Retenciones de armas](/armas/retenciones).
2. Pulsá **Nueva retención**.
3. Seleccioná un funcionario con arma asignada y sin otra retención activa.
4. Elegí el motivo.
5. Completá fecha y datos complementarios.
6. Revisá la información del arma y chaleco tomada por el sistema.
7. Guardá y generá el acta cuando corresponda.

El estado inicial es **En armería** y el sistema calcula los días restantes según el motivo.

## Elevar una retención

1. Abrí el detalle de la retención.
2. Revisá funcionario, arma, motivo, fecha e historial.
3. Pulsá **Elevar** si el procedimiento corresponde.
4. Confirmá la acción.
5. Verificá el nuevo estado en el detalle.

## Registrar la devolución

1. Abrí la retención activa.
2. Pulsá **Devolver**.
3. Completá la información solicitada y observaciones.
4. Confirmá.
5. Verificá que el estado figure como **Devuelta**.

## Buscar retenciones

1. Abrí Retenciones.
2. Usá búsqueda y filtros de estado, tipo o motivo.
3. Revisá alertas de vencimiento y devoluciones recientes.
4. Abrí el detalle para consultar acta, comentarios e historial.

## Personal e inventario

1. Abrí [Personal de armas](/armas/personal).
2. Buscá por identidad funcional, situación, función, observaciones o licencia.
3. Filtrá por licencias, estado o tipo.
4. Abrí el detalle para consultar arma, chaleco, licencias, asignaciones anteriores y retenciones.

## Cambiar arma o chaleco

Disponible con permiso `editar-arma-personal`.

1. Abrí el personal correspondiente y pulsá **Editar**.
2. Verificá que sea la persona correcta.
3. Seleccioná la opción de cambio de inventario.
4. Completá numeración, tipo y motivo.
5. Guardá.

Una misma arma o chaleco no puede estar asignado activamente a más de una persona. Las correcciones manuales pueden quedar protegidas frente a sincronizaciones posteriores.

## Motivos de retención

1. Abrí [Motivos](/armas/motivos).
2. Consultá o cargá los motivos disponibles.

Cada motivo define el plazo con el que el sistema calcula los días restantes de una retención. Modificarlo afecta el cálculo de las retenciones que se creen después.

## Tipos de arma

1. Abrí [Tipos de arma](/armas/tipos).
2. Consultá o cargá los tipos disponibles.

Es el catálogo que alimenta las retenciones y el inventario de armería.

## Armería

Disponible con permiso `ver-armeria`.

1. Abrí [Armas secundarias](/armas/armeria/armas) o [Chalecos](/armas/armeria/chalecos).
2. Consultá numeración, tipo y estado de cada elemento.

Es el inventario del que salen los elementos al asignar o cambiar el equipamiento de una persona.
