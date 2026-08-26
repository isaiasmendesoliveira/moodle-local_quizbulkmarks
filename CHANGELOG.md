# Changelog

All notable changes to this project are documented in this file.

## 0.9.4 - 2026-08-25

- Fixed Moodle Code Checker failure in the PHPUnit test class formatting.
- Reordered English and Portuguese language strings according to Moodle coding standards.
- Removed an unnecessary `MOODLE_INTERNAL` check from `lib.php` as reported by the code checker.
- Added a manual GitHub Actions workflow to rebuild canonical Moodle AMD build artifacts when needed.
- No changes to the plugin's user-facing functionality.

## 0.9.3 - 2026-08-25

- Added the introductory information card before quiz identification.
- Formatted the projected new total quiz value with exactly two decimal places.
- Preserved all other 0.9.2 interface and bulk-editing behaviour.

## 0.9.2 - 2026-08-25

- Redesigned the quiz identification and question selection header.
- Redesigned the value definition and change summary footer.
- Added live display of the current total value of selected questions.
- Preserved the question table and existing bulk mark editing behaviour.

## 0.9.1 - 2026-08-25

- Redesigned the bulk value page using Bootstrap 5 cards and responsive layout.
- Added identification, selection, new-value and live-information panels.
- Added explicit column alignment for the question table.
- Made gradable question rows clickable and keyboard-selectable.
- Added visual highlighting for selected question rows.
- Improved Portuguese and English interface strings and accessibility labels.

## 0.9.0 - 2026-08-23

Release candidate prepared for public distribution.

- Added maintainer and GPL metadata to source files.
- Raised the minimum supported Moodle version to 4.5.
- Declared targeted support for Moodle 4.5 through 5.2.
- Changed maturity to `MATURITY_RC`.
- Removed direct request-method superglobal handling from the plugin page.
- Added an explicit form action and Moodle parameter validation.
- Added validation for finite, non-negative maximum marks.
- Added PHPUnit coverage for selected slots and non-gradable description items.
- Added GitHub Actions configuration using Moodle Plugin CI.
- Added README, LICENSE, SECURITY, CONTRIBUTING, Marketplace preparation notes, and issue templates.
- Retained the working integration button on the native quiz Questions page.
- Retained localised question type names.

## 0.5.1 - 2026-08-23

- Added a dedicated question type column.
- Displayed question type names using the active Moodle language.
- Retained the working **Set question values in bulk** button integration.

## 0.5.0 - 2026-08-23

- Added the working integration button to the native quiz Questions toolbar.
- Registered the AMD integration through local plugin navigation callbacks.
- Retained the standalone bulk-editing page.

## 0.4.0 and earlier

Experimental integration builds. The standalone bulk value editor was already functional.

