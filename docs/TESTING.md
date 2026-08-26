# Testing guide

## Functional test cases

### Installation

- Install from the release ZIP using Moodle's plugin installer.
- Confirm the plugin reports `local_quizbulkmarks` and version 0.9.3.
- Purge caches and verify there are no PHP warnings with developer debugging enabled.

### Access control

- Confirm an editing teacher with `mod/quiz:manage` sees the bulk-value button.
- Confirm a student does not see the button and cannot open the plugin page.

### Basic bulk update

1. Create a quiz with 40 gradable questions.
2. Set questions 1–20 to 0.25.
3. Set questions 21–40 to 0.50.
4. Verify every selected slot has the expected maximum mark.
5. Verify unselected questions are unchanged.
6. Verify the quiz question-mark sum is recalculated.

### Existing attempts

- Create a quiz attempt before changing question values.
- Change one or more maximum marks using the plugin.
- Verify attempt sums, final quiz grades, and gradebook values are recalculated consistently.

### Non-gradable items

- Add a Description question.
- Verify it cannot be selected and its value is not changed.

### Input validation

Verify the following are handled safely:

- no selected question;
- zero as a maximum mark;
- decimal values using the active locale;
- negative values;
- malformed numeric input;
- duplicate slot IDs;
- slot IDs from another quiz;
- missing or invalid `sesskey`.

### Compatibility

Run on:

- Moodle 4.5;
- Moodle 5.0;
- Moodle 5.1;
- Moodle 5.2;
- PostgreSQL;
- MariaDB/MySQL;
- Boost;
- a Boost-derived theme.
