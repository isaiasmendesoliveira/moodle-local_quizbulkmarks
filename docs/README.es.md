# Bulk Quiz Question Values

<p align="center">
  <img
    src="../docs/images/bulk-quiz-question-values-logo.png"
    alt="Activity Date Status"
    width="320">
</p>

**Bulk Quiz Question Values** (`local_quizbulkmarks`) es un plugin local para Moodle LMS que permite a los docentes seleccionar varias preguntas dentro del mismo cuestionario y asignar el mismo valor máximo a todas las preguntas seleccionadas en una sola operación, reduciendo la edición manual repetitiva y manteniendo bajo el control de Moodle su lógica nativa de cuestionarios y calificaciones.

> **Versión pública:** 1.0.0  
> **Moodle:** 4.5–5.2  
> **Licencia:** GNU GPL v3 o posterior

Documentación: [English](../README.md) | [Português (Brasil)](README.pt-BR.md) | **Español**

Documentación técnica: **[Arquitectura e implementación](TECHNICAL.es.md)**

## ¿Por qué este plugin?

Moodle ya permite a los docentes establecer la calificación máxima de cada pregunta de un cuestionario. Sin embargo, cuando un cuestionario contiene muchas preguntas, asignar o modificar esos valores individualmente puede resultar una tarea repetitiva y consumir mucho tiempo.

Bulk Quiz Question Values simplifica este flujo de trabajo al permitir que los docentes seleccionen varias preguntas y apliquen el mismo valor máximo (`maxmark`) a todas ellas de una sola vez.

Por ejemplo, en un cuestionario con 40 preguntas, un docente puede querer que las preguntas 1–20 valgan **0,25 puntos cada una** y que las preguntas 21–40 valgan **0,50 puntos cada una**. En lugar de editar 40 preguntas individualmente, cada grupo puede configurarse en una sola operación.

El objetivo principal es mejorar la **productividad del docente**, reducir el trabajo repetitivo, disminuir el riesgo de errores de configuración y permitir que los docentes dediquen más tiempo al diseño de evaluaciones y a la enseñanza, en lugar de a tareas rutinarias de configuración de cuestionarios.

## Características principales

- Selección de varias preguntas dentro del mismo cuestionario.
- Selección de todas las preguntas elegibles de una sola vez.
- Selección de preguntas por rango numérico.
- Selección o deselección de una pregunta al hacer clic en cualquier parte de su fila.
- Selección de filas accesible mediante el teclado.
- Asignación del mismo valor máximo a todas las preguntas seleccionadas.
- Visualización del valor máximo actual de cada pregunta.
- Visualización de los tipos de preguntas en el idioma activo de Moodle.
- Visualización en tiempo real del número de preguntas seleccionadas.
- Visualización del valor total actual de las preguntas seleccionadas.
- Vista previa del nuevo valor total del cuestionario antes de aplicar los cambios.
- Acceso directo desde la página estándar de Moodle: **Cuestionario → Preguntas**.
- Recálculo automático de los totales y de las calificaciones del cuestionario.
- Uso de las API nativas de cuestionarios de Moodle, sin actualizaciones directas de la base de datos.
- Interfaz multilingüe con soporte para inglés, portugués de Brasil y español.
- Sin servicios externos ni dependencias adicionales en tiempo de ejecución.

## Cómo funciona

El plugin funciona con los slots de preguntas de un cuestionario específico de Moodle.

Cuando un docente aplica un nuevo valor, el plugin actualiza la calificación máxima de cada slot seleccionado utilizando la API nativa de cuestionarios de Moodle:

```php
$structure->update_slot_maxmark($slot, $newmaxmark);
```

Después de actualizar las preguntas seleccionadas, se utilizan los mecanismos nativos de calificación de Moodle para recalcular los totales del cuestionario, los intentos, las calificaciones finales y la información del libro de calificaciones.

El plugin **no** modifica directamente `quiz_slots` mediante SQL.

Moodle continúa siendo responsable de los intentos del cuestionario, la calificación, el comportamiento de las preguntas, los cálculos de las notas, los permisos y el libro de calificaciones.

## Flujo de trabajo del docente

Desde la página estándar de edición del cuestionario de Moodle:

**Cuestionario → Preguntas**

Los docentes tienen acceso a la acción **Definir valores de las preguntas en grupo**.

La interfaz del plugin proporciona:

- identificación del cuestionario;
- número total de preguntas;
- valor total actual del cuestionario;
- selección por rango de preguntas;
- seleccionar todas;
- deseleccionar todas;
- selección individual de preguntas;
- valor actual de la pregunta;
- tipo de pregunta localizado;
- campo para definir el nuevo valor máximo;
- número de preguntas seleccionadas;
- valor total de las preguntas seleccionadas;
- nuevo valor total proyectado del cuestionario;
- acción para aplicar el valor.

Solo se modifican las preguntas seleccionadas.

## Casos de uso habituales

### Diferentes valores para grupos de preguntas

Un cuestionario contiene 40 preguntas:

```text
Preguntas 1–20  → 0,25 puntos cada una
Preguntas 21–40 → 0,50 puntos cada una
```

El docente puede seleccionar cada rango y asignar su valor correspondiente en dos operaciones, en lugar de editar individualmente las 40 preguntas.

### Estandarización de los valores de las preguntas

Un docente importa o añade varias preguntas a un cuestionario y desea que todas las preguntas seleccionadas tengan el mismo valor máximo.

Las preguntas pueden seleccionarse conjuntamente y actualizarse en una sola operación.

### Corrección de valores del cuestionario

Si varias preguntas se configuraron con un valor incorrecto, el docente puede seleccionar únicamente esas preguntas y corregirlas simultáneamente.

### Grandes conjuntos de preguntas

El plugin es especialmente útil para cuestionarios con decenas o incluso cientos de preguntas, en los que la edición individual de los valores requeriría un trabajo considerable y repetitivo.

## Comportamiento con respecto al banco de preguntas

Bulk Quiz Question Values actúa sobre las preguntas **de un cuestionario específico**.

Modifica el valor máximo (`maxmark`) asignado al slot de la pregunta de ese cuestionario.

**No** modifica la pregunta original almacenada en el banco de preguntas de Moodle.

Esto significa que una misma pregunta puede tener diferentes valores en distintos cuestionarios sin modificar la pregunta original.

Por ejemplo:

```text
Cuestionario A → Valor de la pregunta: 0,25
Cuestionario B → Valor de la pregunta: 1,00
Cuestionario C → Valor de la pregunta: 2,00
```

## Instalación

### Desde un archivo ZIP

1. Descargue el archivo ZIP de la versión.
2. En Moodle, vaya a **Administración del sitio → Plugins → Instalar plugins**.
3. Suba el archivo ZIP y complete la validación.
4. Vaya a **Administración del sitio → Notificaciones** para finalizar la instalación.
5. Vacíe las cachés de Moodle si es necesario.

### Desde Git

Clone el repositorio en `local/quizbulkmarks`:

```bash
git clone <repository-url> local/quizbulkmarks
```

A continuación, vaya a:

**Administración del sitio → Notificaciones**

Para completar la instalación.

## Uso

1. Abra un cuestionario en Moodle.
2. Vaya a **Preguntas**.
3. Haga clic en **Definir valores de las preguntas en grupo**.
4. Seleccione las preguntas que desea modificar.
5. Introduzca el nuevo valor máximo.
6. Revise el nuevo valor total proyectado del cuestionario.
7. Haga clic en **Aplicar valor a las preguntas seleccionadas**.

El plugin actualiza únicamente las preguntas seleccionadas.

## Permisos

El acceso al plugin requiere la capacidad de Moodle:

```text
mod/quiz:manage
```

Los usuarios sin permiso para gestionar el cuestionario no pueden modificar los valores de las preguntas mediante el plugin.

## Compatibilidad

La versión pública 1.0.0 declara compatibilidad con Moodle **desde 4.5 hasta 5.2**.

GitHub Actions está configurado para validar las versiones compatibles de Moodle mediante Moodle Plugin CI.

El plugin está diseñado para funcionar con la actividad estándar Cuestionario (`mod_quiz`) de Moodle.

## Accesibilidad

La interfaz está diseñada para integrarse con las convenciones de accesibilidad de Moodle y de Bootstrap 5.

- La selección de preguntas utiliza casillas de verificación estándar.
- Se puede hacer clic en toda la fila de una pregunta para seleccionarla o deseleccionarla.
- Las filas de preguntas admiten la interacción mediante el teclado.
- Los elementos interactivos utilizan controles HTML semánticos.
- Se utilizan etiquetas y atributos ARIA cuando corresponde.
- El estado de selección no se comunica únicamente mediante el color.
- Se heredan la tipografía y las convenciones de la interfaz de Moodle y del tema, en lugar de sustituirlas.
- Los nombres de los tipos de preguntas se muestran utilizando las cadenas de idioma localizadas de Moodle.

## Privacidad

El plugin no crea tablas propias en la base de datos ni almacena datos personales de los usuarios.

Actúa sobre la configuración de los cuestionarios y la información de los slots de preguntas ya existentes en Moodle.

El plugin no envía información a servicios externos.

## Idiomas

La distribución de GitHub incluye:

- Inglés (`en`);
- Portugués de Brasil (`pt_br`);
- Español (`es`).

La interfaz sigue automáticamente el idioma activo de Moodle.

Los nombres de los tipos de preguntas también se obtienen de los propios paquetes de idioma de Moodle, cuando están disponibles.

## Desarrollo

El repositorio incluye la configuración de Moodle Plugin CI para la validación automatizada en las versiones compatibles de Moodle.

Los archivos JavaScript AMD se compilan mediante el flujo de trabajo estándar de Grunt de Moodle.

Consulte [CONTRIBUTING.md](../CONTRIBUTING.md) para obtener información sobre el desarrollo y las contribuciones.

## Soporte y problemas

Los informes de errores, los problemas de compatibilidad y las solicitudes de nuevas funcionalidades deben enviarse mediante el sistema de Issues del repositorio de GitHub.

Al informar de un problema, incluya:

- versión de Moodle;
- versión de PHP;
- tipo y versión de la base de datos;
- versión del plugin;
- pasos para reproducir el problema;
- información de depuración relevante.

## Licencia

GNU General Public License v3 o posterior.

Consulte [LICENSE](../LICENSE).
