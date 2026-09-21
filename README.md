# Moodle Local Domain Authentication

Plugin local para Moodle que controla el uso de dominios de correo electrónico en la creación y edición de usuarios, aplicando reglas de validación dinámicas y visibilidad condicional de campos personalizados.

El plugin permite registrar usuarios con dominios institucionales autorizados sin requisitos adicionales. Cuando se utiliza un dominio externo, Moodle exige una **justificación** y una **fecha de expiración futura** antes de permitir guardar al usuario. Los campos de justificación y fecha se ocultan automáticamente para dominios internos, y se limpian para evitar datos residuales.

## Características

* Validación del dominio del correo electrónico en el servidor.
* Catálogo de dominios institucionales autorizados.
* Visibilidad condicional de campos personalizados mediante JavaScript (AMD).
* Limpieza de valores en cliente para dominios internos.
* Aviso visual destacado para dominios externos.
* Exige una justificación válida (no vacía y no "Case 1") para dominios externos.
* Exige una fecha de expiración válida y futura para dominios externos.
* Bloquea el guardado cuando los requisitos no se cumplen, mostrando errores asociados a cada campo.
* Compatible con Moodle 4.2 y superiores.
* Soporte para inglés y español.

## Dominios autorizados

La configuración inicial incluye los siguientes dominios considerados institucionales (sin restricciones):

```text
uady.mx
fmat.uady.mx
alumnos.uady.mx
correo.uady.mx
```

Estos dominios se encuentran definidos en:

```text
lib.php
```

Dentro de:

```php
$authorizeddomains = [
    'uady.mx',
    'fmat.uady.mx',
    'alumnos.uady.mx',
    'correo.uady.mx',
];
```

Y también en el archivo JavaScript:

```text
amd/src/form.js
```

Para mantener el comportamiento coherente, es necesario actualizar la lista en ambos archivos.

## Campos de perfil requeridos

Para los dominios externos, el plugin utiliza dos campos personalizados de usuario de Moodle.

### Justificación

Debe existir un campo de perfil personalizado con:

```text
Short name: justification
```

La clave utilizada por el formulario es:

```text
profile_field_justification
```

El campo debería ser de tipo **menú desplegable (select)**. El plugin añade automáticamente una opción vacía al inicio y establece el valor por defecto a vacío.

### Fecha de expiración

Debe existir un campo de perfil personalizado con:

```text
Short name: expiration_date
```

La clave utilizada por el formulario es:

```text
profile_field_expiration_date
```

El campo debería ser de tipo **fecha**.

> Es importante utilizar exactamente estos *shortnames*. El nombre visible del campo puede ser diferente.

## Reglas de validación

### Dominio institucional

Por ejemplo:

```text
usuario@uady.mx
usuario@fmat.uady.mx
usuario@alumnos.uady.mx
```

El usuario puede guardarse sin proporcionar información adicional. Los campos personalizados se ocultan y se limpian en el formulario cuando el dominio es institucional.

### Dominio externo

Por ejemplo:

```text
usuario@gmail.com
usuario@hotmail.com
usuario@empresa.com
```

El plugin exige:

1. Una justificación no vacía y que no sea "Case 1" ni "1".
2. Una fecha de expiración válida.
3. La fecha de expiración debe ser posterior a la fecha y hora actuales.

Si alguno de estos requisitos no se cumple, Moodle mostrará un error junto al campo correspondiente y bloqueará el guardado.

## Estructura del plugin

```text
domainauthentication/
├── version.php
├── lib.php
├── lang/
│   ├── en/
│   │   └── local_domainauthentication.php
│   └── es/
│       └── local_domainauthentication.php
└── amd/
    └── src/
        └── form.js
```

## Información del plugin

| Propiedad       | Valor                        |
| --------------- | ---------------------------- |
| Nombre          | Domain Authentication        |
| Componente      | `local_domainauthentication` |
| Versión         | `2026090902`                 |
| Requiere Moodle | `2022112800` (Moodle 4.2)    |
| Tipo            | Local plugin                 |
| Licencia        | GPL v3 or later              |

## Instalación

### Opción 1: Desde Moodle

1. Descargar el archivo ZIP del plugin.
2. Entrar como administrador en Moodle.
3. Ir a:

```text
Administración del sitio
→ Plugins
→ Instalar plugins
```

4. Subir:

```text
domainauthentication.zip
```

5. Seguir el proceso de instalación.
6. Completar las actualizaciones de la base de datos si Moodle las solicita.

### Opción 2: Instalación manual

Extraer el plugin dentro de:

```text
moodle/local/
```

La estructura final debe ser:

```text
moodle/
└── local/
    └── domainauthentication/
        ├── version.php
        ├── lib.php
        ├── lang/
        │   ├── en/
        │   │   └── local_domainauthentication.php
        │   └── es/
        │       └── local_domainauthentication.php
        └── amd/
            └── src/
                └── form.js
```

Posteriormente acceder a Moodle como administrador para ejecutar la actualización del sitio (o purgar cachés).

## Configuración de los campos personalizados

Después de instalar el plugin, crear los campos desde:

```text
Administración del sitio
→ Usuarios
→ Cuentas
→ Campos de perfil de usuario
```

Crear un campo para la justificación con:

```text
Short name: justification
```

Tipo recomendado: menú desplegable (select).

Crear otro campo para la fecha con:

```text
Short name: expiration_date
```

Tipo recomendado: fecha.

## Funcionamiento

El flujo de validación combina lógica de cliente (JavaScript) y servidor (PHP):

1. Al cargar el formulario, JavaScript comprueba el dominio del email, muestra un aviso para dominios externos y muestra/oculta los campos dependientes.
2. Cuando el usuario cambia el email, los campos se actualizan en tiempo real y se limpian si son ocultados.
3. Al enviar el formulario:
    - Si el dominio es interno, no se aplican requisitos adicionales.
   - Si el dominio es externo, el servidor valida justificación y fecha. Si falla, se añaden errores al formulario y se detiene el guardado.

El siguiente diagrama resume el flujo:

```text
Usuario introduce correo
        │
        ▼
JavaScript detecta dominio
        │
        ▼
¿Es dominio institucional?
       / \
     Sí   No
     │     │
     │     ▼
     │  Muestra campos
     │  (justificación y fecha)
     │     │
     ▼     ▼
Oculta y limpia  Envía formulario
campos            │
                  ▼
              Validación servidor
                  │
          ¿Dominio externo?
            /         \
          Sí           No
          │             │
          ▼             ▼
   Valida campos  Fuerza campos a vacío
   (justif. y     y permite guardado
   fecha)
          │
    ¿Cumple?
     /    \
   Sí      No
   │        │
   ▼        ▼
Permite   Muestra errores
guardado  y bloquea
```

## Mensajes de error

El plugin utiliza mensajes de error específicos para cada campo, definidos en los archivos de idioma:

- `errorjustification`: "La justificación es obligatoria y no puede ser 'Case 1' para correos externos."
- `errorexpiration`: "La fecha de expiración debe ser una fecha futura."
- `errorexpirationempty`: "Debe configurar una fecha de expiración para correos externos."

También se proporcionan textos de ayuda en los campos:

- `justificationhelp`: "Obligatorio solo para dominios externos."
- `expirationhelp`: "Debe ser una fecha futura, obligatoria para dominios externos."

## Desarrollo

Este proyecto está pensado para ser desarrollado y probado mediante Git.

Clonar el repositorio:

```bash
git clone https://github.com/USUARIO/domainauthentication.git
```

Entrar al directorio:

```bash
cd domainauthentication
```

Para realizar cambios:

```bash
git checkout -b feature/nombre-del-cambio
```

Después:

```bash
git add .
git commit -m "Descripción del cambio"
git push origin feature/nombre-del-cambio
```

## Pruebas recomendadas

Se recomienda probar al menos los siguientes escenarios:

| Correo                 | Justificación | Expiración   | Resultado esperado |
| ---------------------- | ------------- | ------------ | ------------------ |
| `usuario@uady.mx`      | No requerida  | No requerida | Permitido, campos ocultos |
| `usuario@fmat.uady.mx` | No requerida  | No requerida | Permitido, campos ocultos |
| `usuario@gmail.com`    | Vacía         | Vacía        | Bloqueado, error en ambos campos |
| `usuario@gmail.com`    | Completa      | Vacía        | Bloqueado, error en fecha |
| `usuario@gmail.com`    | Vacía         | Futura       | Bloqueado, error en justificación |
| `usuario@gmail.com`    | "Case 1"      | Futura       | Bloqueado, error en justificación |
| `usuario@gmail.com`    | Completa      | Pasada       | Bloqueado, error en fecha |
| `usuario@gmail.com`    | Completa      | Futura       | Permitido |

## Seguridad

El plugin contiene la validación server-side en `local_domainauthentication_validation`.
Moodle estándar no descubre automáticamente callbacks arbitrarios con ese nombre. Para que bloquee
el submit en una instalación sin una integración adicional, esta función debe invocarse desde el
método `validation()` de `user_editadvanced_form` (o desde una extensión equivalente del formulario).

No se debe confiar únicamente en validaciones realizadas mediante JavaScript o en el navegador, ya que pueden ser eludidas. La lógica de servidor es la que garantiza la integridad de los datos.

La validación del servidor no depende de JavaScript y bloquea el guardado cuando un dominio externo no tiene una justificación válida o una fecha de expiración futura.

## Licencia

Este plugin se distribuye bajo la licencia:

**GNU General Public License v3 or later (GPL-3.0-or-later)**.