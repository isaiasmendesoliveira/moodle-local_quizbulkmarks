# Bulk Quiz Question Values

<p align="center">
  <img
    src="docs/images/bulk-quiz-question-values-logo.png"
    alt="Activity Date Status"
    width="320">
</p>

**Bulk Quiz Question Values** (`local_quizbulkmarks`) is a local plugin for Moodle LMS that allows teachers to select multiple questions within the same quiz and assign the same maximum mark to all selected questions in a single operation, reducing repetitive manual editing while keeping Moodle's native quiz and grading logic in control.

> **Public release:** 1.0.0  
> **Moodle:** 4.5–5.2  
> **License:** GNU GPL v3 or later

Documentation: **English** | [Português (Brasil)](docs/README.pt-BR.md) | [Español](docs/README.es.md)

## Why this plugin?

Moodle already lets teachers define the maximum mark for each question in a quiz. However, when a quiz contains many questions, assigning or changing those values individually can become repetitive and time-consuming.

Bulk Quiz Question Values simplifies this workflow by allowing teachers to select several questions and apply the same maximum mark (`maxmark`) to all of them at once.

For example, in a quiz with 40 questions, a teacher may want questions 1–20 to be worth **0.25 points each** and questions 21–40 to be worth **0.50 points each**. Instead of editing 40 questions individually, each group can be configured in a single operation.

The main goal is to improve **teacher productivity**, reduce repetitive work, lower the risk of configuration errors, and allow teachers to spend more time on assessment design and teaching rather than routine quiz configuration.

## Main features

- Select multiple questions within the same quiz.
- Select all eligible questions at once.
- Select questions by number range.
- Click anywhere on a question row to select or deselect it.
- Keyboard-accessible row selection.
- Assign the same maximum mark to all selected questions.
- Display the current maximum mark of each question.
- Display question types using Moodle's active language.
- Show the number of selected questions in real time.
- Show the current total value of selected questions.
- Preview the new total quiz value before applying changes.
- Direct access from the standard Moodle **Quiz → Questions** page.
- Automatic recalculation of quiz totals and grades.
- Uses Moodle's native quiz APIs rather than direct database updates.
- Multilingual interface with English, Brazilian Portuguese, and Spanish.
- No external services or runtime dependencies.

## How it works

The plugin works with the question slots of a specific Moodle quiz.

When a teacher applies a new value, the plugin updates the maximum mark of each selected quiz slot using Moodle's native quiz API:

```php
$structure->update_slot_maxmark($slot, $newmaxmark);
```

After the selected questions are updated, Moodle's native grading mechanisms recalculate quiz totals, attempts, final grades, and gradebook information.

The plugin does **not** directly modify `quiz_slots` through SQL.

Moodle remains responsible for quiz attempts, grading, question behavior, grade calculations, permissions, and the gradebook.

## Teacher workflow

From the standard Moodle quiz editing page:

**Quiz → Questions**

Teachers have access to the **Set question values in bulk** action.

The plugin interface provides:

- quiz identification;
- total number of questions;
- current total quiz value;
- selection by question range;
- select all;
- deselect all;
- individual question selection;
- current question value;
- localized question type;
- new maximum mark field;
- number of selected questions;
- total value of selected questions;
- projected total quiz value;
- apply value action.

Only the selected questions are modified.

## Common use cases

### Different values for groups of questions

A quiz contains 40 questions:

```text
Questions 1–20  → 0.25 points each
Questions 21–40 → 0.50 points each
```

The teacher can select each range and assign its value in two operations instead of editing all 40 questions individually.

### Standardizing question values

A teacher imports or adds several questions to a quiz and wants all selected questions to have the same maximum mark.

The teacher can select the questions together and update them in a single operation.

### Correcting quiz values

If several questions were configured with an incorrect value, the teacher can select only those questions and correct them at the same time.

### Large question sets

The plugin is particularly useful for quizzes with dozens or hundreds of questions, where editing values individually would require significant repetitive work.

## Question bank behavior

Bulk Quiz Question Values operates on questions **within a specific quiz**.

It changes the maximum mark (`maxmark`) assigned to the question slot in that quiz.

It does **not** modify the underlying question stored in the Moodle question bank.

This means that the same question may have different values in different quizzes without changing the original question.

For example:

```text
Quiz A → Question value: 0.25
Quiz B → Question value: 1.00
Quiz C → Question value: 2.00
```

## Installation

### From ZIP

1. Download the release ZIP.
2. In Moodle, go to **Site administration → Plugins → Install plugins**.
3. Upload the ZIP and complete validation.
4. Visit **Site administration → Notifications** to finish installation.
5. Purge Moodle caches if necessary.

### From Git

Clone the repository into `local/quizbulkmarks`:

```bash
git clone <repository-url> local/quizbulkmarks
```

Then visit:

**Site administration → Notifications**

to complete the installation.

## Usage

1. Open a Moodle quiz.
2. Go to **Questions**.
3. Click **Set question values in bulk**.
4. Select the questions to modify.
5. Enter the new maximum mark.
6. Review the projected quiz total.
7. Click **Apply value to selected questions**.

The plugin updates only the selected questions.

## Permissions

Access to the plugin requires the Moodle capability:

```text
mod/quiz:manage
```

Users without permission to manage the quiz cannot change question values through the plugin.

## Compatibility

The public 1.0.0 release supports Moodle **4.5 through 5.2**.

GitHub Actions validate supported Moodle branches using Moodle Plugin CI.

The plugin is designed to work with Moodle's standard Quiz activity (`mod_quiz`).

## Accessibility

The interface is designed to integrate with Moodle and Bootstrap 5 accessibility conventions.

- Question selection is available through standard checkboxes.
- Entire question rows can be clicked to select or deselect questions.
- Question rows support keyboard interaction.
- Interactive elements use semantic HTML controls.
- Labels and ARIA attributes are used where appropriate.
- Selection state is not communicated by color alone.
- Moodle/theme typography and interface conventions are inherited rather than replaced.
- Question type names are displayed using Moodle's localized language strings.

## Privacy

The plugin does not create its own database tables or store personal user data.

It operates on existing Moodle quiz configuration and question-slot information.

The plugin does not send information to external services.

## Languages

The GitHub distribution includes:

- English (`en`);
- Brazilian Portuguese (`pt_br`);
- Spanish (`es`).

The interface automatically follows the active Moodle language.

Question type names are also obtained from Moodle's own language packs whenever available.

## Development

The repository includes Moodle Plugin CI configuration for automated validation against supported Moodle versions.

JavaScript AMD assets are built using Moodle's standard Grunt workflow.

See [CONTRIBUTING.md](CONTRIBUTING.md) for development and contribution information.

## Support and issues

Submit bug reports, compatibility issues, and feature requests through the GitHub repository issue tracker.

When reporting an issue, please include:

- Moodle version;
- PHP version;
- database type and version;
- plugin version;
- steps to reproduce the problem;
- relevant debugging information.

## License

GNU General Public License v3 or later.

See [LICENSE](LICENSE).
