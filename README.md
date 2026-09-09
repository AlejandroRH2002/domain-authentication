# Moodle Local Domain Authentication

Plugin local para Moodle que controla el uso de dominios de correo electrónico en la creación y edición de usuarios.

El plugin permite registrar usuarios con dominios institucionales autorizados sin requisitos adicionales. Cuando se utiliza un dominio externo, Moodle exige una **justificación** y una **fecha de expiración futura** antes de permitir guardar al usuario.

## Características

* Validación del dominio del correo electrónico.
* Catálogo de dominios institucionales autorizados.
* Permite automáticamente los dominios institucionales.
* Detecta dominios de correo externos.
* Exige una justificación para dominios externos.
* Exige una fecha de expiración válida y futura.
* Bloquea el guardado cuando los requisitos no se cumplen.
* Compatible con Moodle 4.x y 5.x.
* Soporte para inglés y español.

## Dominios autorizados

La configuración inicial incluye los siguientes dominios:

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
$authorizeddomains = array(
    'uady.mx',
    'fmat.uady.mx',
    'alumnos.uady.mx',
    'correo.uady.mx',
);
```

Puedes modificar esta lista de acuerdo con los dominios institucionales que necesite tu instalación de Moodle.

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

### Fecha de expiración

Debe existir un campo de perfil personalizado con:

```text
Short name: expiration_date
```

La clave utilizada por el formulario es:

```text
profile_field_expiration_date
```

> Es importante utilizar exactamente estos *shortnames*. El nombre visible del campo puede ser diferente.

## Reglas de validación

### Dominio institucional

Por ejemplo:

```text
usuario@uady.mx
usuario@fmat.uady.mx
usuario@alumnos.uady.mx
```

El usuario puede guardarse sin proporcionar información adicional.

### Dominio externo

Por ejemplo:

```text
usuario@gmail.com
usuario@hotmail.com
usuario@empresa.com
```

El plugin exige:

1. Una justificación no vacía.
2. Una fecha de expiración válida.
3. La fecha de expiración debe ser posterior a la fecha y hora actuales.

Si alguno de estos requisitos no se cumple, Moodle mostrará un error y bloqueará el guardado.

## Estructura del plugin

```text
domainauthentication/
├── version.php
├── lib.php
└── lang/
    ├── en/
    │   └── local_domainauthentication.php
    └── es/
        └── local_domainauthentication.php
```

## Información del plugin

| Propiedad       | Valor                        |
| --------------- | ---------------------------- |
| Nombre          | Domain Authentication        |
| Componente      | `local_domainauthentication` |
| Versión         | `2026090802`                 |
| Requiere Moodle | `2022112800`                 |
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
        └── lang/
            ├── en/
            │   └── local_domainauthentication.php
            └── es/
                └── local_domainauthentication.php
```

Posteriormente acceder a Moodle como administrador para ejecutar la actualización del sitio.

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

Y otro para la fecha:

```text
Short name: expiration_date
```

El tipo de campo recomendado para `expiration_date` es un campo de fecha.

## Funcionamiento

El flujo de validación es:

```text
Usuario introduce correo
        │
        ▼
¿El correo tiene un dominio válido?
        │
        ▼
Extraer dominio
        │
        ▼
¿Es un dominio institucional?
       / \
     Sí   No
     │     │
     │     ▼
     │  ¿Justificación?
     │     │
     │     ▼
     │  ¿Fecha válida?
     │     │
     │     ▼
     │  ¿Fecha futura?
     │    / \
     │  Sí   No
     │  │     │
     ▼  ▼     ▼
   Permitir  Bloquear
```

## Mensajes de error

Para dominios externos, el plugin puede mostrar mensajes como:

```text
El dominio del correo electrónico es externo.
Es obligatorio indicar una justificación.
```

o:

```text
El dominio del correo electrónico es externo.
Es obligatorio indicar una fecha de expiración válida y futura.
```

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
| `usuario@uady.mx`      | No requerida  | No requerida | Permitido          |
| `usuario@fmat.uady.mx` | No requerida  | No requerida | Permitido          |
| `usuario@gmail.com`    | Vacía         | Vacía        | Bloqueado          |
| `usuario@gmail.com`    | Completa      | Vacía        | Bloqueado          |
| `usuario@gmail.com`    | Vacía         | Futura       | Bloqueado          |
| `usuario@gmail.com`    | Completa      | Pasada       | Bloqueado          |
| `usuario@gmail.com`    | Completa      | Futura       | Permitido          |

## Seguridad

El plugin realiza la validación del lado del servidor mediante la API de validación de formularios de Moodle.

No se debe confiar únicamente en validaciones realizadas mediante JavaScript o en el navegador.

Los dominios autorizados deben mantenerse actualizados de acuerdo con las políticas de la institución.

## Licencia

Este plugin se distribuye bajo la licencia:

**GNU General Public License v3 or later (GPL-3.0-or-later)**.
