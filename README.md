# local_domainauthentication

Plugin local de Moodle para validar dominios de correo institucionales y conservar metadatos de cuentas externas.

## Estado de la integración

Moodle 4.2, 4.3, 4.4 y 4.5 implementa la creación de usuarios mediante la clase core `user_editadvanced_form`. Su método `validation()` es invocado por el formulario antes de `get_data()` y de `user_create_user()`.

En esas versiones no existe un callback de plugins locales que permita:

- insertar campos propios en `user_editadvanced_form`;
- añadir validación a su método `validation()`;
- interceptar de forma portable la creación antes de insertar el usuario.

Una función arbitraria como `local_domainauthentication_validation()` no es descubierta automáticamente por Moodle. Tampoco `local_domainauthentication_user_editadvanced_form_definition()` es un callback del core. Los hooks disponibles para usuarios no proporcionan un punto equivalente y portable para bloquear la creación en las cuatro versiones solicitadas.

Por este motivo, este repositorio **no afirma que el formulario estándar esté bloqueado**. La función de validación y el repositorio están preparados para una integración soportada que añada esos campos al formulario, pero dicha integración requiere una de estas opciones:

1. una extensión oficial del formulario en una versión futura de Moodle;
2. un plugin de formulario/extensión mantenido por la organización;
3. un cambio controlado en `user_editadvanced_form` y su método `validation()` dentro del core mantenido localmente.

Modificar el core no forma parte de este plugin. La validación JavaScript o un evento posterior a la creación tampoco se consideran controles de seguridad.

## Flujo alternativo recomendado

El plugin ofrece una página propia en `/local/domainauthentication/index.php`.
Usa `moodleform`, exige la capacidad `local/domainauthentication:createuser` y crea
usuarios mediante `user_create_user()`. La justificación y la fecha son elementos
del formulario del plugin, no campos de perfil, y se validan en el servidor antes
de insertar el usuario.

La creación y el registro de metadata se ejecutan dentro de una transacción
delegada. Si falla la creación o la escritura en `mdl_domainauthentication`, la
transacción se revierte y no queda una cuenta parcial ni un registro huérfano.

Para que este sea el único flujo operativo, los roles correspondientes deben
perder `moodle/user:create` y recibir `local/domainauthentication:createuser`.
Los site administrators pueden seguir accediendo al formulario core porque
Moodle los autoriza globalmente; esa excepción requiere el parche de core o una
política operativa explícita.

## Parche controlado del formulario core

Si se necesita conservar exactamente `Site administration > Users > Accounts > Add a new user`, el archivo que debe modificarse es:

```text
user/editadvanced_form.php
```

El parche debe añadir los elementos adicionales en `definition()` y llamar a
`local_domainauthentication_validation()` desde `validation($usernew, $files)`
antes de devolver los errores, conservando las validaciones originales de Moodle.
La persistencia debe coordinarse en `user/editadvanced.php`, alrededor de
`user_create_user()`, dentro de una transacción.

Ese parche es mantenimiento local del core, no parte de este plugin, y debe
reaplicarse y probarse después de cada actualización de Moodle.

## Dominios

Se consideran institucionales únicamente estos dominios y sus subdominios con límite de etiqueta:

```text
uady.mx
fmat.uady.mx
alumnos.uady.mx
correo.uady.mx
```

Ejemplos:

```text
usuario@uady.mx              institucional
usuario@dept.uady.mx         institucional
usuario@uady.mx.ejemplo.com  externo
usuario@ejemplo-uady.mx     externo
```

La comparación normaliza el correo con `trim()` y minúsculas, valida su formato y evita aceptar dominios que solo contienen el texto institucional.

## Validación propia

[classes/local/domain_validator.php](classes/local/domain_validator.php) contiene las reglas reutilizables. Para una integración de formulario, los datos adicionales deben tener nombres propios del plugin:

```text
domainauthentication_justification
domainauthentication_expirationdate
```

Las cuentas externas requieren:

- una justificación no vacía después de aplicar `trim()`;
- una fecha representada como entero Unix;
- una fecha estrictamente mayor que `time()`.

`lib.php` expone la firma solicitada:

```php
local_domainauthentication_validation($data, $files, $form)
```

Esta función devuelve errores indexados por campo, pero Moodle estándar no la invoca por nombre automáticamente.

## Almacenamiento

El plugin crea la tabla `mdl_domainauthentication` mediante XMLDB:

- `userid`: usuario de Moodle, único y con clave foránea lógica a `user.id`;
- `justification`: texto de la justificación;
- `expirationdate`: timestamp Unix futuro;
- `timecreated` y `timemodified`.

La persistencia está encapsulada en [classes/local/external_account_repository.php](classes/local/external_account_repository.php). La página alternativa consume este repositorio y guarda metadata solo para dominios externos; las cuentas institucionales no generan registros.

## Instalación y actualización

Instalar el directorio como:

```text
moodle/local/domainauthentication/
```

Después, ejecutar la actualización de Moodle. `db/install.xml` crea la tabla para instalaciones nuevas y `db/upgrade.php` la crea para instalaciones existentes. La versión actual del plugin es `2026092101` y requiere Moodle `2022112800` o posterior.

El código fue diseñado para las ramas 4.2, 4.3, 4.4 y 4.5, pero no se dispone de una instalación de cada versión en este workspace; por tanto, no se declara una verificación funcional runtime de esas versiones.

## Pruebas

Las pruebas PHPUnit están en `tests/local/`:

- `domain_validator_test.php`: pruebas unitarias de dominios, correo inválido, datos vacíos/manipulados y fechas límite;
- `external_account_repository_test.php`: pruebas de integración de base de datos para insertar, actualizar y eliminar metadatos.
- `form_integration_test.php`: casos explícitamente omitidos porque Moodle 4.2-4.5 no ofrece el punto de extensión requerido para el formulario core.

Las pruebas de permisos y bloqueo real del formulario estándar requieren una instalación Moodle con el formulario integrado. En particular, todavía no existe en estas ramas un punto soportado que permita probar y garantizar:

- usuario sin permisos suficientes;
- rechazo antes de `user_create_user()`;
- rollback coordinado entre usuario y tabla propia;
- rutas alternativas de creación, web services o importaciones.

No se han ejecutado PHPUnit contra una instalación Moodle en este workspace y no se afirma que esas pruebas hayan pasado.

## Desinstalación

Antes de desinstalar, exportar `mdl_domainauthentication` si se necesitan conservar las justificaciones y fechas. La desinstalación de un plugin puede eliminar su tabla mediante `db/uninstall.php` si se añade posteriormente; este plugin no elimina datos automáticamente en una ruta posterior a la creación. Verificar la política de retención antes de eliminar la tabla.

## Archivos principales

```text
db/install.xml
db/upgrade.php
db/access.php
settings.php
index.php
classes/form/external_user_form.php
classes/local/domain_validator.php
classes/local/external_account_repository.php
lib.php
lang/en/local_domainauthentication.php
lang/es/local_domainauthentication.php
tests/local/domain_validator_test.php
tests/local/external_account_repository_test.php
```
