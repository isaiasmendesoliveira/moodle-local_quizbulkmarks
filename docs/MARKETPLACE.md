# Moodle Marketplace submission material

This file contains prepared text and the remaining publication checklist for `local_quizbulkmarks`.

## Proposed listing name

Bulk quiz question values

## Frankenstyle name

`local_quizbulkmarks`

## Proposed short description

Assign the same maximum mark to multiple quiz questions at once, directly from the quiz Questions workflow.

## Proposed full description

Bulk quiz question values helps teachers configure quiz grading more efficiently. Moodle normally allows the maximum mark for each quiz question to be edited individually. This plugin adds a bulk-editing workflow where teachers can select multiple questions and assign the same maximum mark to all of them in a single operation.

The plugin integrates with the native quiz Questions page through a dedicated button. Teachers can select individual questions, click anywhere on a question row, select all gradable questions, or select a range of question slots. The responsive Bootstrap 5 interface displays quiz identification data, the current maximum mark, localised question type names, a live selected-question count, and the projected question-mark total before changes are applied.

Changes are performed through Moodle's native `mod_quiz` APIs. The plugin does not directly update `quiz_slots` using SQL. After a real change, quiz totals, existing attempt sums, final grades, and gradebook values are recalculated using the same Moodle subsystems involved in native quiz editing.

The plugin requires the existing `mod/quiz:manage` capability, validates `sesskey` on write operations, has no external service dependencies, and stores no personal data of its own.

## Suggested tags

- quiz
- questions
- grading
- marks
- bulk editing
- assessment

## License

GNU GPL v3 or later.

## Maintainer

Isaias Mendes de Oliveira <isaiasmendes@gmail.com>

## Requirements

- Moodle 4.5 or later.
- Targeted support through Moodle 5.2.

## URLs to complete after creating the public repository

Use a repository name following Moodle convention:

`moodle-local_quizbulkmarks`

Complete these Marketplace fields after the repository exists:

- Source control URL: `https://github.com/<username>/moodle-local_quizbulkmarks`
- Bug tracker URL: `https://github.com/<username>/moodle-local_quizbulkmarks/issues`
- Documentation URL: `https://github.com/<username>/moodle-local_quizbulkmarks#readme`

Do not submit placeholder URLs to Marketplace.

## Screenshots to capture from a real Moodle site

1. **Quiz Questions page** showing the **Set question values in bulk** button beside the native quiz editing controls.
2. **Bulk question values page** showing the question table with type and current value columns.
3. **Range selection** showing a selected interval such as questions 1–20.
4. **Apply new value** showing the selected count and estimated sum.

Screenshots should not contain personal student data, private course information, or credentials.

## Pre-submission checklist

- [ ] Create a public GitHub repository named `moodle-local_quizbulkmarks`.
- [ ] Enable GitHub Issues.
- [ ] Push this source tree to the repository.
- [ ] Run the GitHub Actions workflow successfully.
- [ ] Resolve all Moodle Plugin CI coding-style and validation errors.
- [ ] Test the exact ZIP on a clean Moodle 4.5 installation.
- [ ] Test the exact ZIP on Moodle 5.1 and 5.2.
- [ ] Test on PostgreSQL and MariaDB/MySQL.
- [ ] Test with developer debugging enabled.
- [ ] Verify the button with Boost and at least one common Boost-derived theme.
- [ ] Capture Marketplace screenshots.
- [ ] Replace all `<username>` URL placeholders in Marketplace fields.
- [ ] Create a Git tag for the release candidate.
- [ ] After successful validation, promote to version 1.0.0 and `MATURITY_STABLE`.


## Languages

Bundled interface languages: English (`en`), Portuguese - Brazil (`pt_br`), and Spanish (`es`). English remains the source language for Moodle translation workflows.
