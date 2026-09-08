# Bulk Quiz Question Values — Documentación técnica

**Bulk Quiz Question Values** (`local_quizbulkmarks`) es un plugin local para Moodle LMS que permite al docente asignar el mismo valor máximo a varias preguntas del mismo cuestionario en una sola operación.

Este documento describe el funcionamiento del plugin, su integración con el Cuestionario de Moodle, flujo de datos, validación, seguridad, recálculo de calificaciones, JavaScript, accesibilidad, internacionalización, privacidad, pruebas y mantenimiento.

> **Plugin:** `local_quizbulkmarks`  
> **Versión pública:** 1.0.0  
> **Moodle:** 4.5–5.2  
> **Licencia:** GNU GPL v3 o posterior

---

## 1. Propósito

En Moodle, el valor máximo de cada pregunta de un cuestionario normalmente se ajusta de forma individual. En cuestionarios extensos, este proceso puede resultar repetitivo.

El plugin permite:

- seleccionar varias preguntas del mismo cuestionario;
- seleccionar todas las preguntas calificables;
- seleccionar por intervalo de números;
- introducir un único nuevo valor máximo;
- previsualizar el nuevo total;
- aplicar el valor a todas las preguntas seleccionadas de una sola vez.

El objetivo principal es aumentar la **productividad del docente**, reducir el trabajo repetitivo y disminuir inconsistencias manuales.

---

## 2. Qué modifica el plugin

El plugin modifica el **valor máximo del slot de una pregunta dentro de un cuestionario específico**. En Moodle, este valor es `maxmark`.

```text
Pregunta del banco de preguntas
        │
        ├── Cuestionario A → maxmark = 0.25
        ├── Cuestionario B → maxmark = 1.00
        └── Cuestionario C → maxmark = 2.00
```

La pregunta original almacenada en el banco de preguntas no se modifica. Por lo tanto, una misma pregunta puede tener valores diferentes en distintos cuestionarios.

---

## 3. Qué no hace el plugin

Bulk Quiz Question Values no:

- modifica la calificación predeterminada de la pregunta en el banco de preguntas;
- crea o edita preguntas;
- modifica el contenido o el comportamiento de las preguntas;
- modifica los métodos de calificación del cuestionario;
- modifica directamente la calificación final del cuestionario;
- omite permisos de Moodle;
- actualiza `quiz_slots` mediante SQL personalizado;
- crea tablas propias;
- almacena datos personales;
- envía datos a servicios externos.

Moodle sigue siendo la fuente de verdad para estructura, intentos, calificación, permisos y libro de calificaciones.

---

## 4. Requisitos y compatibilidad

La versión 1.0.0 declara:

```php
$plugin->requires = 2024100700; // Moodle 4.5.
$plugin->supported = [405, 502];
$plugin->maturity = MATURITY_STABLE;
```

Compatibilidad declarada:

- Moodle 4.5;
- Moodle 5.0;
- Moodle 5.1;
- Moodle 5.2.

El plugin trabaja específicamente con:

```text
mod_quiz
```

y requiere la capacidad:

```text
mod/quiz:manage
```

---

## 5. Flujo de trabajo del docente

1. Abrir un Cuestionario.
2. Ir a **Preguntas**.
3. Hacer clic en **Definir valores de las preguntas en grupo**.
4. Seleccionar una o más preguntas calificables.
5. Introducir el nuevo valor máximo.
6. Revisar el resumen y el total proyectado.
7. Aplicar el valor.
8. Volver a la página estándar de Preguntas.

Solo se modifican los slots calificables seleccionados.

---

## 6. Interfaz de usuario

El editor en grupo se implementa en:

```text
index.php
```

La página utiliza las API de salida de Moodle y clases compatibles con Bootstrap.

### 6.1 Información introductoria

El primer bloque explica que la operación modifica el `maxmark` individual de las preguntas seleccionadas.

### 6.2 Identificación del cuestionario

Se muestran:

- nombre del cuestionario;
- cantidad de preguntas calificables;
- total actual de los valores de las preguntas.

La suma actual se expone a JavaScript mediante `data-raw-sum`.

### 6.3 Selección de preguntas

Hay tres formas de selección:

- selección individual mediante checkbox;
- **Seleccionar todas**;
- selección por intervalo.

Ejemplo:

```text
Desde: 1
Hasta: 20
```

Los intervalos invertidos, como `20 → 1`, se normalizan a `1 → 20`.

### 6.4 Elementos no calificables

Elementos como **Descripción** se muestran, pero no pueden seleccionarse.

### 6.5 Definición del valor

El campo numérico utiliza:

```text
min = 0
step = 0.01
```

### 6.6 Resumen dinámico

Antes del envío, el plugin muestra:

- cantidad seleccionada;
- total actual de las preguntas seleccionadas;
- nuevo total proyectado.

```text
total proyectado
    = suma actual
    - total actual de las preguntas seleccionadas
    + (cantidad seleccionada × nuevo valor)
```

El total proyectado se muestra con dos decimales. El cálculo definitivo se realiza en el servidor.

---

## 7. Integración con la página nativa de Preguntas

El plugin no sustituye la página de edición del Cuestionario. Añade un botón a la barra de acciones existente.

Archivos implicados:

```text
lib.php
classes/local/edit_page_integration.php
amd/src/editbutton.js
```

### 7.1 Callbacks

`lib.php` utiliza:

```php
local_quizbulkmarks_extend_navigation()
local_quizbulkmarks_extend_settings_navigation()
```

Ambos delegan en:

```php
\local_quizbulkmarks\local\edit_page_integration::register();
```

Una bandera estática `$registered` evita registros duplicados.

### 7.2 Detección de página

La integración verifica:

```text
mod-quiz-edit
```

y también:

```text
/mod/quiz/edit.php
```

### 7.3 Capacidad

Antes de insertar el botón:

```php
has_capability('mod/quiz:manage', $context)
```

### 7.4 Botón AMD

PHP registra:

```php
$PAGE->requires->js_call_amd(
    'local_quizbulkmarks/editbutton',
    'init',
    [...]
);
```

JavaScript busca:

```text
.mod_quiz-edit-action-buttons
```

y crea un enlace con:

```text
btn btn-secondary ms-1
```

Los textos visible y ARIA proceden de las cadenas de idioma de Moodle.

### 7.5 Fallback para temas

Un `MutationObserver` observa la página durante un máximo de cinco segundos cuando la barra se renderiza tarde.

ID usado para impedir duplicación:

```text
local-quizbulkmarks-edit-button
```

---

## 8. Ciclo de la solicitud en el servidor

Endpoint principal:

```text
/local/quizbulkmarks/index.php?cmid=<course-module-id>
```

### 8.1 Resolución del módulo

```php
$cmid = required_param('cmid', PARAM_INT);
```

El plugin resuelve módulo, curso, cuestionario y contexto.

### 8.2 Autenticación y autorización

```php
require_login($course, false, $cm);
require_capability('mod/quiz:manage', $context);
```

El acceso directo por URL no omite permisos.

### 8.3 Lectura

```php
$quizobj = quiz_settings::create($quiz->id);
```

El objeto se entrega a:

```php
\local_quizbulkmarks\local\question_value_manager
```

### 8.4 Escritura

Cuando se envía `action=apply`:

```php
require_sesskey();
```

Los IDs usan:

```php
PARAM_INT
```

y el valor usa:

```php
PARAM_LOCALISEDFLOAT
```

---

## 9. Servicio `question_value_manager`

Se implementa en:

```text
classes/local/question_value_manager.php
```

Clase:

```php
\local_quizbulkmarks\local\question_value_manager
```

Responsabilidades principales:

```text
get_question_rows()
apply_value()
```

---

## 10. Lectura de los slots

```php
$structure = $this->quizobj->get_structure();
$structure->get_slots();
```

Para cada slot:

```php
$question = $structure->get_question_in_slot($slot->slot);
```

Datos devueltos:

```text
slotid
slotnumber
page
questionid
name
qtype
qtypename
maxmark
gradable
```

### 10.1 Detección de calificabilidad

El plugin consulta Moodle:

```php
$structure->is_real_question($slot->slot)
```

y no deduce la calificabilidad a partir del nombre del tipo.

---

## 11. Tipos de pregunta localizados

Ejemplos de identificadores internos:

```text
multichoice
truefalse
shortanswer
numerical
```

El plugin intenta:

```php
$component = 'qtype_' . $qtype;
get_string('pluginname', $component);
```

Como fallback:

```php
\question_bank::get_qtype_name($qtype)
```

Si es necesario, devuelve el qtype interno. El nombre mostrado sigue el idioma activo de Moodle cuando existe traducción.

---

## 12. Aplicación del nuevo valor máximo

Operación principal:

```php
$manager->apply_value($slotids, $newmaxmark);
```

### 12.1 Validación del valor

Se rechazan valores negativos, infinitos o no finitos:

```php
if ($newmaxmark < 0 || !is_finite($newmaxmark)) {
    throw new \invalid_parameter_exception(...);
}
```

`0` es válido.

### 12.2 Normalización de IDs

```php
array_map('intval', $slotids)
array_unique(...)
array_values(...)
```

Esto evita procesar varias veces IDs duplicados.

### 12.3 Pertenencia al cuestionario

```php
$validslots = $structure->get_slots();
```

Solo se procesan slots pertenecientes al cuestionario actual.

### 12.4 Elementos no calificables

El servidor vuelve a verificar:

```php
if (!$structure->is_real_question($slot->slot)) {
    continue;
}
```

La seguridad no depende de una casilla deshabilitada en el navegador.

### 12.5 Actualización mediante la API de Moodle

```php
$structure->update_slot_maxmark($slot, $newmaxmark);
```

No hay SQL directo. El contador aumenta únicamente cuando se produce un cambio real.

---

## 13. Transacción de base de datos

La operación en grupo se ejecuta dentro de:

```php
$transaction = $DB->start_delegated_transaction();
```

y finaliza con:

```php
$transaction->allow_commit();
```

Las actualizaciones y el recálculo quedan agrupados en el mismo flujo transaccional.

---

## 14. Recálculo del cuestionario y las calificaciones

Si al menos un slot cambia, el plugin ejecuta:

### 14.1 Eliminar previsualizaciones

```php
quiz_delete_previews($quiz);
```

### 14.2 Recalcular la suma del cuestionario

```php
$gradecalculator->recompute_quiz_sumgrades();
```

### 14.3 Recalcular intentos

```php
$gradecalculator->recompute_all_attempt_sumgrades();
```

### 14.4 Recalcular calificaciones finales

```php
$gradecalculator->recompute_all_final_grades();
```

### 14.5 Actualizar el libro de calificaciones

```php
quiz_update_grades($quiz, 0, true);
```

### 14.6 Frecuencia

El recálculo se ejecuta **una sola vez después de procesar todos los slots seleccionados**, no una vez por pregunta.

---

## 15. Cuando no hay cambios

Si no es necesario modificar ningún valor, `apply_value()` devuelve `0` y se muestra una notificación informativa.

Si hay cambios, el mensaje de éxito indica cuántas preguntas fueron modificadas.

---

## 16. JavaScript del lado del cliente

Fuentes:

```text
amd/src/
```

Builds:

```text
amd/build/
```

Módulos:

```text
editbutton.js
selection.js
```

---

## 17. `editbutton.js`

Responsable de integrar el botón en la página nativa. El módulo:

- localiza `.mod_quiz-edit-action-buttons`;
- evita duplicados;
- crea el enlace;
- aplica clases Moodle/Bootstrap;
- recibe textos traducidos desde PHP;
- usa `MutationObserver` como fallback.

No modifica calificaciones.

---

## 18. `selection.js`

Responsable de:

- seleccionar/deseleccionar todas;
- seleccionar por intervalo;
- clic en la fila;
- selección mediante teclado;
- resaltado de selección;
- cantidad seleccionada;
- total actual seleccionado;
- total proyectado.

### 18.1 Checkboxes válidos

```javascript
document.querySelectorAll('.quizbulkmarks-slot:not(:disabled)')
```

### 18.2 Fila seleccionada

```text
table-active
```

### 18.3 Teclado

```text
Enter
Espacio
```

alternan la selección.

### 18.4 Entrada numérica localizada

La previsualización JavaScript acepta coma o punto. En el servidor, `PARAM_LOCALISEDFLOAT` es la referencia definitiva.

---

## 19. Modelo de seguridad en capas

La escritura está protegida por:

- sesión autenticada;
- resolución del módulo;
- `mod/quiz:manage`;
- `sesskey`;
- IDs enteros;
- valor localizado;
- valor finito y no negativo;
- comprobación de pertenencia al cuestionario;
- comprobación de calificabilidad.

Manipular el formulario en el navegador no concede nuevos permisos.

---

## 20. Protección CSRF

El formulario incluye:

```php
sesskey()
```

y la escritura exige:

```php
require_sesskey();
```

---

## 21. Seguridad de la salida

El contenido dinámico utiliza mecanismos de Moodle, incluidos:

```php
format_string(...)
s(...)
```

La interfaz se genera con `html_writer`, reduciendo la concatenación directa de HTML no confiable.

---

## 22. Accesibilidad

La interfaz incluye:

- encabezados y secciones semánticos;
- checkboxes HTML;
- labels explícitos;
- ARIA cuando corresponde;
- `aria-labelledby`;
- `aria-describedby`;
- `aria-live="polite"`;
- selección de filas mediante teclado;
- foco visible;
- selección no comunicada solo mediante color;
- tipografía nativa Moodle/Bootstrap.

Las filas clicables reciben:

```text
tabindex="0"
```

y existe estilo `:focus-visible`.

---

## 23. Diseño responsivo

Se utilizan clases como:

```text
col-12
col-6
col-lg-2
col-lg-4
row
g-3
g-4
table-responsive
```

Los controles se apilan en pantallas pequeñas y usan columnas en pantallas grandes.

---

## 24. Internacionalización

Idiomas incluidos:

```text
en     Inglés
pt_br  Portugués de Brasil
es     Español
```

Archivos:

```text
lang/en/local_quizbulkmarks.php
lang/pt_br/local_quizbulkmarks.php
lang/es/local_quizbulkmarks.php
```

La interfaz utiliza:

```php
get_string(..., 'local_quizbulkmarks')
```

Los tipos de pregunta también siguen el paquete de idioma activo de Moodle.

---

## 25. Privacidad

Provider:

```text
classes/privacy/provider.php
```

Implementación:

```php
\core_privacy\local\metadata\null_provider
```

El plugin declara que no almacena datos personales propios.

---

## 26. Almacenamiento de datos

El plugin no crea tablas propias ni mantiene almacenamiento persistente para:

- selecciones;
- historial de ediciones en grupo;
- datos personales;
- copias de los valores de las preguntas.

Los valores permanecen en la configuración nativa del Cuestionario.

---

## 27. Estructura de archivos

```text
local/quizbulkmarks/
├── amd/
│   ├── build/
│   │   ├── editbutton.min.js
│   │   └── selection.min.js
│   └── src/
│       ├── editbutton.js
│       └── selection.js
├── classes/
│   ├── local/
│   │   ├── edit_page_integration.php
│   │   └── question_value_manager.php
│   └── privacy/
│       └── provider.php
├── docs/
├── lang/
│   ├── en/
│   ├── es/
│   └── pt_br/
├── tests/
│   └── question_value_manager_test.php
├── index.php
├── lib.php
├── styles.css
├── version.php
├── README.md
├── CHANGELOG.md
├── CONTRIBUTING.md
├── SECURITY.md
└── LICENSE
```

### Archivos principales

| Archivo | Responsabilidad |
| --- | --- |
| `index.php` | Página, solicitud, validación e interfaz |
| `lib.php` | Callbacks de integración |
| `classes/local/edit_page_integration.php` | Detección de página, capacidad y registro AMD |
| `classes/local/question_value_manager.php` | Lectura, validación, actualización de `maxmark` y recálculo |
| `amd/src/editbutton.js` | Botón en la barra del cuestionario |
| `amd/src/selection.js` | Selección y totales dinámicos |
| `styles.css` | Estilos mínimos de fila y foco |
| `classes/privacy/provider.php` | Privacy API |
| `tests/question_value_manager_test.php` | Pruebas PHPUnit |
| `version.php` | Versión y compatibilidad |

---

## 28. Visión general del flujo de datos

```text
Docente abre Cuestionario → Preguntas
          ↓
Callbacks de local_quizbulkmarks
          ↓
edit_page_integration comprueba página + capacidad
          ↓
Se registra editbutton AMD
          ↓
Aparece el botón de edición en grupo
          ↓
/local/quizbulkmarks/index.php?cmid=...
          ↓
Login + capacidad
          ↓
quiz_settings::create()
          ↓
question_value_manager::get_question_rows()
          ↓
Editor en grupo
```

Flujo de escritura:

```text
Seleccionar preguntas
        ↓
Introducir nuevo valor
        ↓
Previsualización JavaScript
        ↓
POST action=apply + sesskey
        ↓
Validación en el servidor
        ↓
question_value_manager::apply_value()
        ↓
Transacción
        ├── validar pertenencia
        ├── validar calificabilidad
        └── update_slot_maxmark()
        ↓
Si hubo cambios
        ├── eliminar previsualizaciones
        ├── recalcular cuestionario
        ├── recalcular intentos
        ├── recalcular calificaciones finales
        └── actualizar libro de calificaciones
        ↓
Commit
        ↓
Notificación
```

---

## 29. Ejemplo con 40 preguntas

```text
Preguntas 1–20  = 0,25 cada una
Preguntas 21–40 = 0,50 cada una
```

Primera operación: seleccionar `1 → 20` y aplicar `0,25`.

Segunda operación: seleccionar `21 → 40` y aplicar `0,50`.

Así, 40 ediciones individuales se sustituyen por dos operaciones en grupo.

---

## 30. Intentos existentes

Después de un cambio real:

```php
$gradecalculator->recompute_all_attempt_sumgrades();
$gradecalculator->recompute_all_final_grades();
quiz_update_grades($quiz, 0, true);
```

Por lo tanto, la modificación no es solo visual: los intentos y las calificaciones finales pasan por el recálculo de Moodle.

Docentes y administradores deben respetar las políticas institucionales al modificar valores después de que los estudiantes hayan realizado la evaluación.

---

## 31. Errores y notificaciones

### Ninguna pregunta seleccionada
La solicitud se rechaza con una notificación de error.

### Valor no válido
Se rechazan valores ausentes, mal formados, negativos o no finitos.

### Sin cambios reales
Se muestra una notificación informativa.

### Actualización correcta
La notificación indica cuántas preguntas se modificaron.

---

## 32. Pruebas automatizadas

Archivo principal:

```text
tests/question_value_manager_test.php
```

### 32.1 Solo slots seleccionados

Verifica que solo cambie el slot seleccionado y que el contador sea correcto.

### 32.2 Elementos no calificables

Usa un elemento Descripción para comprobar que no se modifica y que el contador permanece en cero.

### 32.3 Valores negativos

Verifica que se genere:

```php
\invalid_parameter_exception
```

---

## 33. Moodle Plugin CI

El repositorio utiliza:

```text
moodlehq/moodle-plugin-ci
```

El CI ejecuta:

- instalación;
- PHP lint;
- Moodle Code Checker;
- PHPDoc;
- validación del plugin;
- upgrade savepoint;
- lint/build de JavaScript;
- PHPUnit.

La matriz cubre Moodle 4.5, 5.0, 5.1 y 5.2, con PostgreSQL, MariaDB y múltiples versiones compatibles de PHP.

---

## 34. Build AMD

Código fuente:

```text
amd/src/
```

Archivos generados:

```text
amd/build/
```

El repositorio incluye un workflow para reconstruir los recursos AMD. Los desarrolladores deben editar `amd/src`, nunca los archivos minificados manualmente.

---

## 35. Principios de desarrollo

### Moodle como autoridad
Estructura y calificaciones se modifican mediante API de Moodle.

### Validación en el servidor
JavaScript mejora la UX, pero no define permisos.

### Persistencia mínima
El plugin no duplica datos del Cuestionario.

### Interferencia mínima con el tema
Se usan clases Moodle/Bootstrap y poco CSS propio.

### Localización por defecto
Los textos usan String API y componentes de idioma.

### Un recálculo por operación
Todos los slots se procesan antes de una única secuencia de recálculo.

---

## 36. Rendimiento

Para `N` preguntas seleccionadas:

```text
validar selección
    ↓
procesar N slots
    ↓
ejecutar una secuencia de recálculo
```

El coste final depende del volumen de intentos y del recálculo normal de Moodle. Los cuestionarios muy grandes deben modificarse con el mismo cuidado utilizado en cambios nativos de la estructura de evaluación.

---

## 37. Instalación

### ZIP

1. Descargar el ZIP.
2. Ir a **Administración del sitio → Plugins → Instalar plugins**.
3. Subir el paquete.
4. Completar la validación.
5. Finalizar la instalación.
6. Ir a **Administración del sitio → Notificaciones**, si es necesario.

Directorio esperado:

```text
local/quizbulkmarks
```

### Git

```bash
git clone <repository-url> local/quizbulkmarks
```

Después:

```text
Administración del sitio → Notificaciones
```

---

## 38. Actualizaciones

La versión 1.0.0 no posee tablas propias ni migraciones de datos del plugin.

La actualización normalmente consiste en sustituir el código por la nueva versión y permitir que Moodle detecte el nuevo número de versión.

Siga los procedimientos institucionales de copia de seguridad, mantenimiento y despliegue.

---

## 39. Desinstalación e impacto en los datos

La desinstalación elimina la interfaz de edición en grupo y el botón de integración.

Los `maxmark` ya aplicados permanecen en la configuración nativa del Cuestionario y no se revierten.

---

## 40. Solución de problemas

### El botón no aparece

Compruebe:

- instalación y habilitación;
- actividad `mod_quiz`;
- página de Preguntas;
- capacidad `mod/quiz:manage`;
- cachés;
- compatibilidad de la barra de edición del tema.

### Error de permiso

El plugin ejecuta:

```php
require_capability('mod/quiz:manage', $context);
```

### No se puede seleccionar Descripción

Es el comportamiento esperado: los elementos no calificables están deshabilitados.

### Total proyectado inesperado

La previsualización del navegador es informativa; el recálculo en el servidor es definitivo.

### Cambiaron las calificaciones

Es esperado, ya que el plugin solicita el recálculo de intentos, calificaciones finales y libro de calificaciones.

---

## 41. Seguridad para mantenedores

Al ampliar el plugin:

- mantenga `require_login()`;
- mantenga `require_capability('mod/quiz:manage', ...)`;
- mantenga `require_sesskey()`;
- valide IDs mediante las API de Moodle;
- valide la pertenencia del slot;
- valide la calificabilidad en el servidor;
- prefiera las API del Cuestionario frente a SQL directo;
- escape/formatee la salida dinámica;
- mantenga los textos traducidos en los archivos de idioma.

---

## 42. Extensiones futuras

Posibles evoluciones:

- nuevas ayudas de selección;
- mejoras de confirmación y previsualización;
- más pruebas para escenarios con intentos;
- nuevos idiomas;
- validación de futuras versiones de Moodle.

Cualquier función que modifique valores debe seguir utilizando API compatibles de Moodle.

---

## 43. Resumen del diseño técnico

```text
Integración con Cuestionario
        ↓
Botón AMD
        ↓
Editor en grupo
        ├── autenticación/capacidad
        ├── String API
        ├── UI Bootstrap accesible
        └── AMD de selección/previsualización
        ↓
question_value_manager
        ├── lee estructura
        ├── valida slots
        ├── omite elementos no calificables
        └── update_slot_maxmark()
        ↓
Calculador de calificaciones de Moodle
        ├── sumgrades
        ├── intentos
        ├── calificaciones finales
        └── libro de calificaciones
```

El plugin añade productividad al subsistema nativo de Cuestionarios sin sustituirlo ni crear un modelo paralelo de calificación.

---

## 44. Documentación relacionada

- [`README.md`](../README.md) — descripción general y uso;
- [`TECHNICAL.md`](TECHNICAL.md) — documentación técnica en inglés;
- [`TESTING.md`](TESTING.md) — pruebas funcionales;
- [`MARKETPLACE.md`](MARKETPLACE.md) — publicación en Moodle Marketplace;
- [`../CONTRIBUTING.md`](../CONTRIBUTING.md) — contribuciones;
- [`../SECURITY.md`](../SECURITY.md) — seguridad;
- [`../CHANGELOG.md`](../CHANGELOG.md) — historial;
- [`../LICENSE`](../LICENSE) — licencia.

---

## 45. Mantenedor

**Isaias Mendes de Oliveira**  
Correo electrónico: **isaiasmendes@gmail.com**

---

## 46. Licencia

Bulk Quiz Question Values es software libre distribuido bajo la **GNU General Public License v3 o posterior**.

Consulte [`LICENSE`](../LICENSE).
