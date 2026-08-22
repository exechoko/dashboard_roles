# Gestor de contraseñas

Guarda credenciales de sistemas, VPN, cámaras y equipos, y permite compartirlas con otros usuarios. Está protegido por una segunda clave, la contraseña maestra, distinta de la contraseña de acceso al sistema.

## Configurar la contraseña maestra

1. Abrí las opciones de perfil en la barra superior.
2. Entrá a **Contraseña Maestra del Gestor**.
3. Ingresá la nueva clave, de al menos cuatro caracteres, y repetila.
4. Guardá.

Desde esa misma pantalla se puede cambiar o eliminar. Si se elimina, el gestor deja de pedir una segunda clave.

## Entrar al gestor

1. Abrí [Gestor de contraseñas](/password-vault).
2. Ingresá la contraseña maestra cuando el sistema la pida.
3. El gestor queda desbloqueado.

El desbloqueo dura 30 minutos contados desde la última acción dentro del gestor, así que mientras se lo usa no vuelve a pedir la clave. Pasado ese tiempo sin actividad se bloquea otra vez. Si la pantalla quedó abierta y el desbloqueo venció, aparece un aviso al intentar una acción: hay que ingresar la clave maestra nuevamente.

## Buscar una contraseña

Disponible con permiso `ver-clave`.

1. Escribí en el buscador: busca por nombre del sistema, usuario o URL.
2. Filtrá por tipo de sistema o marcá **Solo Favoritos**.
3. Pulsá **Buscar**.
4. En cada tarjeta, usá el botón de ojo para mostrar u ocultar la clave, el de copiar para llevarla al portapapeles y la estrella para marcarla como favorita.

## Guardar una contraseña nueva

Disponible con permiso `crear-clave`.

1. Pulsá **Nueva Contraseña**.
2. Completá nombre del sistema, tipo y usuario: son obligatorios.
3. Ingresá la contraseña, o usá el generador, que crea una de 16 caracteres con mayúsculas, minúsculas, números y símbolos.
4. Si es una VPN, cargá además host, tipo de VPN y clave precompartida.
5. Agregá URL, notas y marcá favorito cuando corresponda.
6. Guardá.

## Editar o eliminar

Editar requiere `editar-clave` y puede hacerlo el dueño o quien la haya recibido con permiso de edición. Si el campo de contraseña se deja vacío al editar, se conserva la contraseña anterior.

Eliminar requiere `borrar-clave` y solo puede hacerlo el dueño.

## Compartir con otros usuarios

Disponible con permiso `compartir-clave`. Solo el dueño puede compartir.

1. En la tarjeta, pulsá el botón de compartir.
2. Seleccioná uno o varios usuarios; se los puede buscar por nombre o correo.
3. Tildá **Permitir edición** únicamente si además deben poder modificarla.
4. Confirmá.

Para compartir varias a la vez, tildá las contraseñas propias en el listado y usá **Compartir seleccionadas**.

En la misma ventana figura quién tiene acceso: se puede pasar a un usuario de solo lectura a edición, o revocarle el acceso.

Revocar el acceso es inmediato, pero quien lo tuvo pudo haber copiado la contraseña. Si la revocación es por un motivo de seguridad, hay que cambiar además la contraseña en el sistema de origen.
