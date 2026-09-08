# Bulk Quiz Question Values — Technical Documentation

**Bulk Quiz Question Values** (`local_quizbulkmarks`) is a local plugin for Moodle LMS that lets teachers assign the same maximum mark to multiple questions in the same quiz in a single operation.

This document explains how the plugin works from both the teacher-facing and technical perspectives, including its integration with Moodle Quiz, data flow, validation, security, grading recalculation, JavaScript behaviour, accessibility, internationalisation, privacy, testing, and maintenance.

> **Plugin:** `local_quizbulkmarks`  
> **Public release:** 1.0.0  
> **Moodle:** 4.5–5.2  
> **License:** GNU GPL v3 or later

---

## 1. Purpose

Moodle allows a maximum mark to be configured for every question added to a quiz. In the standard quiz workflow, changing these values is normally performed question by question.

For quizzes containing many questions, this can become repetitive. Bulk Quiz Question Values adds a bulk-editing workflow that allows a teacher to:

- select several questions in the same quiz;
- select all gradable questions;
- select questions by slot-number range;
- enter one new maximum mark;
- preview the resulting quiz question-mark total;
- apply the same value to all selected questions in one operation.

The plugin is intended to improve **teacher productivity**, reduce repetitive configuration work, and reduce the likelihood of manual inconsistencies when configuring large quizzes.

---

## 2. What the plugin changes

The plugin changes the **maximum mark of a question slot inside a specific quiz**.

In Moodle terminology, the value being changed is the slot `maxmark`.

Conceptually:

```text
Question bank question
        │
        ├── Quiz A slot → maxmark = 0.25
        ├── Quiz B slot → maxmark = 1.00
        └── Quiz C slot → maxmark = 2.00
```

The plugin does **not** change the underlying question stored in the question bank.

Therefore, the same question can be reused in different quizzes with different maximum marks.

---

## 3. What the plugin does not do

Bulk Quiz Question Values does not:

- modify the default grade of a question in the question bank;
- create or edit questions;
- change question content;
- change question behaviour;
- change quiz grading methods;
- change the quiz final-grade setting directly;
- bypass Moodle permissions;
- directly update `quiz_slots` using custom SQL;
- create its own database tables;
- store personal user data;
- send data to external services.

Moodle remains the source of truth for quiz structure, attempts, grading, permissions, and gradebook integration.

---

## 4. Requirements and compatibility

The public 1.0.0 release declares:

```php
$plugin->requires = 2024100700; // Moodle 4.5.
$plugin->supported = [405, 502];
$plugin->maturity = MATURITY_STABLE;
```

Supported Moodle versions:

- Moodle 4.5;
- Moodle 5.0;
- Moodle 5.1;
- Moodle 5.2.

The plugin is specifically designed for the standard Moodle Quiz activity:

```text
mod_quiz
```

Access requires the standard Moodle capability:

```text
mod/quiz:manage
```

---

## 5. Teacher workflow

The normal teacher workflow is:

1. Open a Moodle quiz.
2. Go to **Questions**.
3. Click **Set question values in bulk**.
4. Select one or more gradable questions.
5. Enter the new maximum mark.
6. Review the live summary and projected total.
7. Apply the value.
8. Return to the standard Quiz questions page.

The plugin modifies only the selected gradable question slots.

---

## 6. User interface

The bulk editor is implemented in:

```text
index.php
```

The page uses Moodle output APIs and Bootstrap-compatible classes rather than a standalone frontend framework.

### 6.1 Introductory information

The first card explains that the operation changes the individual maximum value (`maxmark`) of selected quiz questions.

### 6.2 Quiz identification

The interface displays:

- quiz name;
- number of gradable questions;
- current total value of the quiz questions.

The current quiz sum is obtained from Moodle's quiz data and exposed to JavaScript through a `data-raw-sum` attribute for the live preview.

### 6.3 Question selection

Teachers can select questions in three ways.

#### Individual selection

Each gradable row contains a standard checkbox.

The entire row is also clickable and keyboard-selectable.

#### Select all

The **Select all** control selects all enabled question checkboxes.

#### Range selection

The teacher can specify a start and end question number, for example:

```text
From: 1
To:   20
```

The JavaScript selects every gradable quiz slot whose displayed slot number falls inside that range.

The range works in either direction. For example, `20 → 1` is normalised internally to `1 → 20`.

### 6.4 Non-gradable items

Items for which Moodle reports that the slot is not a real gradable question are displayed but cannot be selected.

For example, a **Description** item is shown as an ungraded item and its checkbox is disabled.

### 6.5 Value definition

The new maximum value field is rendered as a numeric input with:

```text
min = 0
step = 0.01
```

The value is applied individually to every selected question.

### 6.6 Live change summary

Before submission, the page dynamically displays:

- number of selected questions;
- current combined value of selected questions;
- projected new total value of the quiz.

The projected total is calculated client-side as:

```text
projected total
    = current quiz sum
    - current total of selected questions
    + (number of selected questions × new value)
```

The projected quiz total is displayed with exactly two decimal places.

This preview is informational. The authoritative update and recalculation happen on the server through Moodle APIs.

---

## 7. Integration with the native Quiz Questions page

The plugin does not replace Moodle's Quiz editing page.

Instead, it adds a button to the existing Quiz Questions toolbar.

The integration is implemented through:

```text
lib.php
classes/local/edit_page_integration.php
amd/src/editbutton.js
```

### 7.1 Moodle callbacks

`lib.php` registers the integration from two supported navigation lifecycle points:

```php
local_quizbulkmarks_extend_navigation()
```

and:

```php
local_quizbulkmarks_extend_settings_navigation()
```

Both callbacks delegate to:

```php
\local_quizbulkmarks\local\edit_page_integration::register();
```

The integration class has a static `$registered` flag so the JavaScript module is registered only once per request.

### 7.2 Page detection

The integration runs only on the quiz question editor.

It checks Moodle's page type:

```text
mod-quiz-edit
```

and also supports path detection for:

```text
/mod/quiz/edit.php
```

### 7.3 Capability check

Before registering the button, the plugin verifies:

```php
has_capability('mod/quiz:manage', $context)
```

Users without that capability do not receive the bulk-edit button.

### 7.4 AMD button injection

The PHP integration calls:

```php
$PAGE->requires->js_call_amd(
    'local_quizbulkmarks/editbutton',
    'init',
    [...]
);
```

The JavaScript module looks for Moodle's native toolbar:

```text
.mod_quiz-edit-action-buttons
```

and appends a Bootstrap-style link button.

The button uses:

```text
btn btn-secondary ms-1
```

The visible label and accessible label are passed from PHP using Moodle language strings.

### 7.5 Theme timing fallback

The standard Moodle toolbar is normally available when the script runs. However, some themes may render or move controls later.

For this reason, the AMD module includes a short-lived `MutationObserver` fallback. It watches the page for up to five seconds and stops as soon as the toolbar is found and the button has been inserted.

Duplicate insertion is prevented by the fixed element ID:

```text
local-quizbulkmarks-edit-button
```

---

## 8. Server-side request lifecycle

The main plugin endpoint is:

```text
/local/quizbulkmarks/index.php?cmid=<course-module-id>
```

### 8.1 Course-module resolution

The required `cmid` parameter is validated as an integer:

```php
$cmid = required_param('cmid', PARAM_INT);
```

The plugin then resolves:

- course module;
- course;
- quiz record;
- module context.

### 8.2 Authentication and authorisation

The page requires both:

```php
require_login($course, false, $cm);
```

and:

```php
require_capability('mod/quiz:manage', $context);
```

This means direct access to the plugin URL does not bypass Moodle permissions.

### 8.3 Read operation

For page display, the plugin creates Moodle's quiz settings object:

```php
$quizobj = quiz_settings::create($quiz->id);
```

It passes that object to:

```php
\local_quizbulkmarks\local\question_value_manager
```

The manager obtains the quiz structure and returns presentation data for every slot.

### 8.4 Write operation

When the form is submitted, `action=apply` is processed.

The write request requires a valid Moodle session key:

```php
require_sesskey();
```

The submitted slot IDs are validated with:

```php
PARAM_INT
```

The new value is read with:

```php
PARAM_LOCALISEDFLOAT
```

This allows Moodle to handle localized numeric input appropriately.

---

## 9. `question_value_manager` service

The core business logic is isolated in:

```text
classes/local/question_value_manager.php
```

Class:

```php
\local_quizbulkmarks\local\question_value_manager
```

This separates quiz manipulation from the presentation layer in `index.php`.

The class has two main responsibilities:

```text
get_question_rows()
apply_value()
```

---

## 10. Reading quiz question slots

`get_question_rows()` obtains Moodle's quiz structure:

```php
$structure = $this->quizobj->get_structure();
```

It iterates over:

```php
$structure->get_slots()
```

For each slot, the plugin obtains the related question:

```php
$question = $structure->get_question_in_slot($slot->slot);
```

The presentation data returned for each row includes:

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

### 10.1 Gradable detection

The plugin does not infer gradability from the question type name.

It asks Moodle directly:

```php
$structure->is_real_question($slot->slot)
```

This allows Moodle core to determine whether the slot represents an actual gradable question.

---

## 11. Localised question type names

The plugin stores and displays the internal question type separately from the human-readable name.

For example, Moodle may internally use:

```text
multichoice
truefalse
shortanswer
numerical
```

The plugin first attempts to obtain the question type's own `pluginname` language string:

```php
$component = 'qtype_' . $qtype;
```

If available:

```php
get_string('pluginname', $component)
```

is used.

As a fallback, the plugin calls:

```php
\question_bank::get_qtype_name($qtype)
```

If neither mechanism succeeds, the internal qtype name is returned.

As a result, question-type names follow the active Moodle language whenever the corresponding Moodle language strings are available.

---

## 12. Applying a new maximum mark

The main write operation is:

```php
$manager->apply_value($slotids, $newmaxmark);
```

The method performs several layers of validation before changing any quiz slot.

### 12.1 Maximum-mark validation

The manager rejects values that are:

- negative;
- infinite;
- not finite numeric values.

The validation is:

```php
if ($newmaxmark < 0 || !is_finite($newmaxmark)) {
    throw new \invalid_parameter_exception(...);
}
```

A value of `0` is valid.

### 12.2 Slot ID normalisation

Submitted IDs are normalised with:

```php
array_map('intval', $slotids)
array_unique(...)
array_values(...)
```

Therefore duplicate submitted slot IDs do not cause the same slot to be processed repeatedly.

### 12.3 Quiz ownership validation

The manager obtains the valid slots from the current quiz structure:

```php
$validslots = $structure->get_slots();
```

Only IDs present in that structure are processed.

An arbitrary slot ID that does not belong to the current quiz is ignored.

### 12.4 Non-gradable validation

Even if a non-gradable slot ID reaches the server, the plugin checks again:

```php
if (!$structure->is_real_question($slot->slot)) {
    continue;
}
```

Client-side disabled checkboxes therefore are not the security boundary. The server independently enforces this rule.

### 12.5 Native Moodle update API

The actual mark update is performed with:

```php
$structure->update_slot_maxmark($slot, $newmaxmark);
```

The plugin deliberately uses the quiz structure API rather than direct SQL.

The method returns whether a real change occurred. The plugin increments its changed count only when Moodle reports a changed value.

If a selected question already has the requested value, it does not count as changed.

---

## 13. Database transaction

The complete bulk operation is executed inside a Moodle delegated database transaction:

```php
$transaction = $DB->start_delegated_transaction();
```

All selected slot updates and the related recalculation sequence are performed before:

```php
$transaction->allow_commit();
```

This groups the operation into one transactional workflow at the plugin level.

---

## 14. Quiz and grade recalculation

Changing a question's maximum mark affects more than the visible value on the Questions page.

If at least one slot is actually changed, the plugin executes Moodle's recalculation workflow.

### 14.1 Delete previews

```php
quiz_delete_previews($quiz);
```

Existing quiz previews are invalidated so they do not continue to represent the old configuration.

### 14.2 Recompute quiz question-mark sum

```php
$gradecalculator->recompute_quiz_sumgrades();
```

This recalculates the sum of the maximum marks of the quiz questions.

### 14.3 Recompute existing attempt sums

```php
$gradecalculator->recompute_all_attempt_sumgrades();
```

Existing attempts are recalculated against the updated question marks.

### 14.4 Recompute final quiz grades

```php
$gradecalculator->recompute_all_final_grades();
```

Final quiz-grade calculations are refreshed.

### 14.5 Update the Moodle gradebook

```php
quiz_update_grades($quiz, 0, true);
```

The quiz grade information is then synchronised with Moodle's gradebook subsystem.

### 14.6 Recalculation frequency

The recalculation sequence is performed **once after all selected slots have been processed**, not once per question.

This is important for bulk-edit efficiency and avoids unnecessary repeated recalculation during the same operation.

---

## 15. No-change behaviour

If all selected questions already have the requested value, or if none of the submitted slots qualifies for modification, `apply_value()` returns `0`.

The user receives an informational notification instead of a success message claiming that values were modified.

When one or more slots change, the success message reports the number of changed questions.

---

## 16. Client-side JavaScript

The plugin uses Moodle AMD modules under:

```text
amd/src/
```

Compiled distribution files are stored under:

```text
amd/build/
```

The two source modules are:

```text
editbutton.js
selection.js
```

---

## 17. `editbutton.js`

`editbutton.js` is responsible only for integrating the plugin action into the standard Quiz Questions toolbar.

It:

- searches for `.mod_quiz-edit-action-buttons`;
- avoids duplicate button insertion;
- creates a normal link to the plugin page;
- applies Moodle/Bootstrap button classes;
- receives translated visible and ARIA labels from PHP;
- uses a short `MutationObserver` fallback when required by theme rendering timing.

It does not perform mark updates.

---

## 18. `selection.js`

`selection.js` handles interactive behaviour on the bulk-editor page.

Its responsibilities include:

- select all;
- clear all;
- range selection;
- row-click selection;
- keyboard row selection;
- selected-row highlighting;
- selected-question count;
- current value total for selected questions;
- projected quiz total.

### 18.1 Checkbox scope

Only enabled checkboxes are included:

```javascript
document.querySelectorAll('.quizbulkmarks-slot:not(:disabled)')
```

### 18.2 Selected-row state

Selected rows receive Bootstrap's:

```text
table-active
```

class.

### 18.3 Keyboard interaction

For gradable rows, both:

```text
Enter
Space
```

toggle the associated checkbox.

### 18.4 Localized numeric preview

For the live preview, JavaScript accepts comma or period decimal input by replacing a comma with a period before parsing.

The server remains authoritative and processes the submitted field using Moodle's `PARAM_LOCALISEDFLOAT`.

---

## 19. Progressive security model

The plugin does not rely on JavaScript for security.

JavaScript improves usability, but the write operation is protected server-side by:

- authenticated Moodle session;
- course-module resolution;
- `mod/quiz:manage` capability;
- `sesskey` validation;
- integer validation of submitted slot IDs;
- localized-float input handling;
- non-negative/finite value validation;
- validation that each slot belongs to the current quiz;
- validation that each slot is gradable.

This means manipulating form fields in the browser does not grant permission to modify another quiz or a non-gradable item.

---

## 20. CSRF protection

The form includes Moodle's session key:

```php
sesskey()
```

Any write operation requires:

```php
require_sesskey();
```

This provides Moodle-standard protection against cross-site request forgery for the bulk update action.

---

## 21. Output safety

Question and quiz names are rendered through Moodle formatting/escaping mechanisms, including:

```php
format_string(...)
```

and localised question-type output is escaped with:

```php
s(...)
```

The interface is built with Moodle's `html_writer` rather than concatenating untrusted HTML strings directly.

---

## 22. Accessibility

Accessibility was considered in both the PHP markup and JavaScript interaction.

The interface includes:

- semantic headings and sections;
- standard HTML checkboxes;
- explicit labels for inputs;
- ARIA labels where additional context is useful;
- `aria-labelledby` relationships for interface sections;
- `aria-describedby` for the new-value help text;
- `aria-live="polite"` for the dynamic change summary;
- keyboard-selectable question rows;
- visible focus styling;
- selection state represented by a checkbox, not colour alone;
- Moodle and Bootstrap typography instead of custom replacement typography.

Clickable rows receive:

```text
tabindex="0"
```

and the CSS defines a visible `:focus-visible` outline.

Non-gradable rows do not receive the clickable-row keyboard behaviour.

---

## 23. Responsive design

The page uses Bootstrap/Moodle responsive utility classes such as:

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

This allows the information cards and controls to stack on smaller screens while using multi-column layouts on larger displays.

The question table is wrapped in:

```text
table-responsive
```

so wide quiz tables remain usable on narrower viewports.

---

## 24. Internationalisation

The 1.0.0 distribution includes three interface languages:

```text
en     English
pt_br  Brazilian Portuguese
es     Spanish
```

Language files are located at:

```text
lang/en/local_quizbulkmarks.php
lang/pt_br/local_quizbulkmarks.php
lang/es/local_quizbulkmarks.php
```

Plugin interface text is retrieved using Moodle's String API:

```php
get_string(..., 'local_quizbulkmarks')
```

The button injected into the native quiz page also receives its visible and ARIA labels from Moodle language strings rather than hard-coded JavaScript text.

Question type names are localised using the active Moodle language pack.

---

## 25. Privacy

The privacy provider is implemented in:

```text
classes/privacy/provider.php
```

It implements:

```php
\core_privacy\local\metadata\null_provider
```

because the plugin stores no personal data of its own.

The provider returns the language string:

```text
privacy:metadata
```

which explains that the plugin does not store personal data.

---

## 26. Data storage

The plugin creates no custom database tables.

There is no plugin-specific persistent storage for:

- user selections;
- bulk-edit history;
- personal information;
- question values duplicated outside Moodle Quiz.

The resulting question maximum marks remain part of Moodle's native quiz configuration.

---

## 27. File structure

The main source tree is organised as follows:

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

### Key files

| File | Responsibility |
| --- | --- |
| `index.php` | Bulk-editor page, request handling, validation and UI rendering |
| `lib.php` | Moodle callbacks used to register Quiz Questions page integration |
| `classes/local/edit_page_integration.php` | Detects the quiz editor, checks capability and registers the AMD button module |
| `classes/local/question_value_manager.php` | Reads quiz slots, validates selections, updates `maxmark`, and triggers recalculation |
| `amd/src/editbutton.js` | Adds the action button to Moodle's native quiz toolbar |
| `amd/src/selection.js` | Handles selection controls and live projected totals |
| `styles.css` | Minimal clickable-row and keyboard-focus styling |
| `classes/privacy/provider.php` | Moodle Privacy API declaration |
| `tests/question_value_manager_test.php` | PHPUnit coverage for the core mark-update service |
| `version.php` | Plugin version, Moodle requirements and maturity metadata |

---

## 28. Request and data-flow overview

A normal page-access flow is:

```text
Teacher opens Quiz → Questions
          │
          ▼
Moodle builds quiz editing page
          │
          ▼
local_quizbulkmarks callbacks run
          │
          ▼
edit_page_integration checks page + capability
          │
          ▼
editbutton AMD module is registered
          │
          ▼
"Set question values in bulk" button appears
          │
          ▼
Teacher opens /local/quizbulkmarks/index.php?cmid=...
          │
          ▼
Login + capability validation
          │
          ▼
quiz_settings::create()
          │
          ▼
question_value_manager::get_question_rows()
          │
          ▼
Bulk editor is rendered
```

The write flow is:

```text
Teacher selects question slots
          │
          ▼
Teacher enters new maximum mark
          │
          ▼
JavaScript previews resulting total
          │
          ▼
POST action=apply + sesskey
          │
          ▼
Server validates session, capability, IDs and value
          │
          ▼
question_value_manager::apply_value()
          │
          ▼
Delegated DB transaction begins
          │
          ├── Validate slot belongs to quiz
          ├── Validate slot is gradable
          └── update_slot_maxmark()
          │
          ▼
If at least one slot changed
          │
          ├── delete previews
          ├── recompute quiz sumgrades
          ├── recompute attempt sumgrades
          ├── recompute final grades
          └── update gradebook
          │
          ▼
Transaction commits
          │
          ▼
Success / informational notification
```

---

## 29. Example: bulk configuration of a 40-question quiz

Suppose a teacher needs:

```text
Questions 1–20  = 0.25 each
Questions 21–40 = 0.50 each
```

### First operation

The teacher selects range:

```text
1 → 20
```

and enters:

```text
0.25
```

The plugin updates those selected quiz slots and performs the Moodle recalculation sequence once.

### Second operation

The teacher selects:

```text
21 → 40
```

and enters:

```text
0.50
```

The second group is updated in one additional operation.

This replaces 40 separate question-value edits with two bulk operations.

---

## 30. Behaviour with existing attempts

The plugin explicitly invokes Moodle grade-calculation methods after a real slot-value change:

```php
$gradecalculator->recompute_all_attempt_sumgrades();
$gradecalculator->recompute_all_final_grades();
quiz_update_grades($quiz, 0, true);
```

Therefore, changing question maximum marks is not treated as a purely visual edit. Existing quiz attempts and final grades are passed through Moodle's recalculation mechanisms, and gradebook information is updated.

Administrators and teachers should still follow their institution's assessment policies when changing quiz values after learners have already attempted an assessment.

---

## 31. Error and notification handling

The interface provides Moodle notifications for the principal outcomes.

### No questions selected

The request is rejected and the user receives an error notification.

### Invalid value

Values that are missing, malformed or negative are rejected.

The service layer additionally validates that the numeric value is finite and non-negative.

### No actual changes

If the selected questions already contain the submitted value, an informational notification is displayed.

### Successful update

When one or more values change, a success notification reports how many questions were updated.

---

## 32. Automated tests

The main PHPUnit test file is:

```text
tests/question_value_manager_test.php
```

The current test suite covers the core service behaviour.

### 32.1 Selected slots only

The test creates a quiz with multiple questions, updates only one selected slot, and verifies that:

- the selected slot changes;
- the unselected slot remains unchanged;
- the method reports the correct changed count.

### 32.2 Non-gradable items

A Description item is added and the test verifies that:

- Moodle reports it as non-gradable;
- the plugin does not change it;
- the changed count remains zero.

### 32.3 Negative maximum marks

The test verifies that a negative mark raises:

```php
\invalid_parameter_exception
```

---

## 33. Moodle Plugin CI

The repository includes a GitHub Actions workflow based on:

```text
moodlehq/moodle-plugin-ci
```

The CI workflow performs:

- Moodle/plugin installation;
- PHP lint;
- Moodle Code Checker;
- Moodle PHPDoc validation;
- plugin validation;
- upgrade-savepoint validation;
- JavaScript lint/build validation;
- PHPUnit tests.

The configured matrix includes Moodle 4.5, 5.0, 5.1 and 5.2, with PostgreSQL and MariaDB coverage across the matrix and multiple supported PHP versions.

---

## 34. AMD build workflow

Source JavaScript belongs in:

```text
amd/src/
```

Generated production assets belong in:

```text
amd/build/
```

The repository contains a dedicated GitHub Actions workflow for rebuilding Moodle AMD assets.

Developers should edit the source files rather than manually editing minified files in `amd/build`.

After source changes, Moodle's standard Grunt pipeline should be used to regenerate canonical build artifacts.

---

## 35. Development principles used by the plugin

The implementation follows several design principles.

### Moodle remains authoritative

The plugin delegates quiz structure and grading changes to Moodle APIs rather than reproducing grading logic itself.

### Server-side validation is authoritative

Client-side code improves interaction but does not decide which quiz slots are allowed to change.

### Minimal persistence

No duplicate plugin storage is introduced for data already owned by Moodle Quiz.

### Minimal theme intrusion

The interface uses Moodle/Bootstrap classes and only a small custom stylesheet.

### Localisation by default

Interface text uses Moodle's String API, and question-type names use Moodle language components.

### Recalculation once per bulk operation

Selected slots are updated first, followed by one recalculation sequence when at least one real change occurred.

---

## 36. Performance considerations

The plugin is intended for bulk operations, so its server-side design avoids recalculating the entire quiz after each selected slot.

For a bulk operation with `N` selected questions, the high-level pattern is:

```text
validate selection
    ↓
process N selected slots
    ↓
run one recalculation sequence
```

The total cost still depends on Moodle's normal quiz and grading recalculation workload, especially when the quiz already contains many attempts.

Because existing attempts and grades may be recalculated, very large production quizzes should be updated with the same operational care that would be used for native grading-structure changes in Moodle.

---

## 37. Installation

### ZIP installation

1. Download the release ZIP.
2. Go to **Site administration → Plugins → Install plugins**.
3. Upload the ZIP package.
4. Complete Moodle's plugin validation.
5. Continue the installation process.
6. Visit **Site administration → Notifications** if required.

The plugin directory must resolve to:

```text
local/quizbulkmarks
```

### Git installation

From the Moodle root directory:

```bash
git clone <repository-url> local/quizbulkmarks
```

Then visit:

```text
Site administration → Notifications
```

---

## 38. Upgrade behaviour

The plugin does not define custom database tables or plugin data migrations in the 1.0.0 release.

Normal upgrades therefore primarily involve replacing the plugin source with the newer version and allowing Moodle to detect the new version number.

Always follow standard Moodle backup, maintenance and deployment procedures before production upgrades.

---

## 39. Uninstallation and data impact

Because the plugin does not maintain its own database tables or duplicate question-mark records, uninstalling it removes the bulk-editing interface and integration button.

Maximum marks already applied to quiz slots remain part of Moodle's native quiz configuration. Uninstalling the plugin does not revert previous quiz mark changes.

---

## 40. Troubleshooting

### The button does not appear on the Quiz Questions page

Check that:

- the plugin is installed and enabled;
- the current activity is a standard Moodle Quiz;
- the current page is the Quiz Questions editor;
- the user has `mod/quiz:manage`;
- Moodle caches have been purged after installation or upgrade;
- the active theme still exposes Moodle's quiz editing toolbar in a compatible form.

### The bulk page returns a permission error

The plugin independently calls:

```php
require_capability('mod/quiz:manage', $context);
```

Confirm the user's role has this capability in that activity context.

### A Description item cannot be selected

This is expected behaviour. The plugin asks Moodle whether the slot is a real gradable question and disables non-gradable items.

### The projected total differs from what is expected

The browser preview is a convenience calculation based on the current question-mark sum, current selected values and new value. The server-side Moodle recalculation remains authoritative after submission.

### Grades changed after modifying question values

This is expected when question maximum marks are changed. The plugin explicitly asks Moodle to recompute quiz sums, existing attempt sums, final grades and gradebook values.

---

## 41. Security considerations for maintainers

When extending the plugin, preserve the following boundaries:

- do not remove `require_login()`;
- do not remove `require_capability('mod/quiz:manage', ...)`;
- do not remove `require_sesskey()` from write operations;
- continue validating IDs with Moodle parameter APIs;
- continue checking slot membership against the current quiz structure;
- continue validating gradability server-side;
- prefer Moodle quiz APIs over direct database updates;
- escape or format displayed dynamic content through Moodle output functions;
- keep translated UI text in language files rather than JavaScript literals.

---

## 42. Extension points for future development

Possible future enhancements can be implemented without changing the plugin's fundamental model, for example:

- additional selection helpers;
- improved confirmation or preview interfaces;
- more automated tests for existing-attempt scenarios;
- additional language packs;
- compatibility validation for future Moodle versions.

Any future feature that changes quiz marks should continue to use Moodle's supported Quiz APIs and grading recalculation mechanisms.

---

## 43. Summary of the technical design

Bulk Quiz Question Values is intentionally small in scope.

Its architecture can be summarised as:

```text
Moodle Quiz page integration
        │
        ▼
AMD toolbar button
        │
        ▼
Bulk editor page
        │
        ├── Moodle authentication/capability checks
        ├── Moodle String API
        ├── accessible Bootstrap-based UI
        └── AMD selection/preview helpers
        │
        ▼
question_value_manager
        │
        ├── reads Moodle quiz structure
        ├── validates current-quiz slots
        ├── skips non-gradable items
        └── update_slot_maxmark()
        │
        ▼
Moodle grade calculator
        │
        ├── quiz sumgrades
        ├── attempt sums
        ├── final grades
        └── gradebook update
```

The plugin adds productivity functionality around Moodle's existing quiz subsystem without replacing that subsystem or maintaining a parallel grading model.

---

## 44. Related repository documentation

For additional repository information, see:

- [`README.md`](../README.md) — plugin overview and user-facing documentation;
- [`TESTING.md`](TESTING.md) — functional testing guide;
- [`MARKETPLACE.md`](MARKETPLACE.md) — Moodle Marketplace publication information;
- [`../CONTRIBUTING.md`](../CONTRIBUTING.md) — contribution guidance;
- [`../SECURITY.md`](../SECURITY.md) — security reporting information;
- [`../CHANGELOG.md`](../CHANGELOG.md) — release history;
- [`../LICENSE`](../LICENSE) — GNU GPL v3 or later.

---

## 45. Maintainer

**Isaias Mendes de Oliveira**  
Email: **isaiasmendes@gmail.com**

---

## 46. License

Bulk Quiz Question Values is free software distributed under the **GNU General Public License v3 or later**.

See [`LICENSE`](../LICENSE).
