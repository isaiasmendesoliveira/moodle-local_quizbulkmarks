# Bulk quiz question values

**Frankenstyle name:** `local_quizbulkmarks`

Bulk quiz question values is a Moodle local plugin that lets teachers select multiple questions in a quiz and assign the same maximum mark (`maxmark`) to all selected questions in one operation.

The plugin adds a **Set question values in bulk** button to the native **Questions** page of a quiz. It does not modify Moodle core files and does not write directly to the `quiz_slots` table.

## Features

- Select multiple quiz questions and apply one maximum mark to all of them.
- Select all gradable questions or clear the current selection.
- Select a numeric range of question slots, for example questions 1–20.
- Select or clear a question by clicking anywhere on its table row (keyboard accessible with Enter/Space).
- See live selected-question count and projected quiz question-mark total before applying a change.
- Display the current value and the localised question type name.
- Preview the estimated sum of question marks before applying a change.
- Skip non-gradable items such as Description questions.
- Use Moodle's quiz APIs to update slot marks and recalculate quiz grades and existing attempts.
- Require the native `mod/quiz:manage` capability.
- Protect write actions with Moodle `sesskey` validation.
- Store no personal data of its own.

## Requirements

- Moodle 4.5 or later.
- Targeted support: Moodle 4.5 through 5.2.
- No third-party services or libraries are required.

## Installation

### Install from ZIP

1. Download the release ZIP.
2. In Moodle, go to **Site administration > Plugins > Install plugins**.
3. Upload the ZIP and complete the validation and installation steps.
4. Purge Moodle caches if the new button is not immediately visible.

### Manual installation

Copy the `quizbulkmarks` directory to:

```text
local/quizbulkmarks
```

Then visit **Site administration > Notifications**.

### Updating from the experimental 0.x builds

Early development versions used different integration mechanisms. If upgrading from version 0.4.0 or earlier, remove the old `local/quizbulkmarks` directory before copying the new source directory, without uninstalling the plugin in Moodle. This prevents obsolete files from remaining on disk.

## Usage

1. Open a quiz for which you have permission to manage questions.
2. Open the **Questions** page.
3. Click **Set question values in bulk**.
4. Select individual questions, select all questions, or select a question range.
5. Enter the new maximum mark.
6. Review the estimated sum and apply the change.

The plugin changes the maximum mark of each selected quiz slot. Moodle then recalculates quiz totals, attempt sums, final grades, and gradebook values using the native quiz APIs.

## Permissions

The plugin does not define a new capability. Access requires Moodle's existing:

```text
mod/quiz:manage
```

## Privacy

The plugin does not create database tables, user preferences, logs, or external integrations of its own. It changes quiz configuration and invokes Moodle's native quiz and grade APIs. Its Privacy API provider therefore declares that the plugin stores no personal data of its own.

## Security

- All write operations require a valid Moodle session key.
- User input is read through Moodle parameter APIs.
- The course module and quiz are resolved by Moodle APIs.
- The user must be logged in and have `mod/quiz:manage` in the quiz context.
- Quiz slot values are updated through `mod_quiz` APIs instead of direct SQL writes.

Security issues can be reported privately to **Isaias Mendes de Oliveira** at **isaiasmendes@gmail.com**.

## Development and testing

The repository includes a GitHub Actions workflow based on `moodlehq/moodle-plugin-ci` and PHPUnit tests for the value manager.

Recommended public repository name:

```text
moodle-local_quizbulkmarks
```

Before publishing a stable 1.0.0 release, run the CI workflow successfully for every declared Moodle version and test installation from the exact release ZIP on a clean Moodle site.

## Translation

English is the source language. A Brazilian Portuguese translation is included for development and local use. After Marketplace publication, translations can also be maintained through Moodle's translation infrastructure where applicable.

## License

GNU GPL v3 or later. See [LICENSE](LICENSE).

## Maintainer

**Isaias Mendes de Oliveira**  
Email: **isaiasmendes@gmail.com**
