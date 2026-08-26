<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Bulk editor for quiz question maximum marks.
 *
 * @package   local_quizbulkmarks
 * @copyright 2026 Isaias Mendes de Oliveira <isaiasmendes@gmail.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/question/engine/bank.php');

use local_quizbulkmarks\local\question_value_manager;
use mod_quiz\quiz_settings;

$cmid = required_param('cmid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);

$cm = get_coursemodule_from_id('quiz', $cmid, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$quiz = $DB->get_record('quiz', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/quiz:manage', $context);

$pageurl = new moodle_url('/local/quizbulkmarks/index.php', ['cmid' => $cm->id]);
$editurl = new moodle_url('/mod/quiz/edit.php', ['cmid' => $cm->id]);

$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_cm($cm, $course, $quiz);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('pagetitle', 'local_quizbulkmarks', format_string($quiz->name)));
$PAGE->set_heading($course->fullname);
$PAGE->activityheader->disable();
$PAGE->requires->js_call_amd('local_quizbulkmarks/selection', 'init');

$quizobj = quiz_settings::create($quiz->id);
$manager = new question_value_manager($quizobj);

if ($action === 'apply') {
    require_sesskey();

    $slotids = optional_param_array('slotids', [], PARAM_INT);
    $newmaxmark = optional_param('newmaxmark', '', PARAM_LOCALISEDFLOAT);

    if (empty($slotids)) {
        redirect(
            $pageurl,
            get_string('errornoselection', 'local_quizbulkmarks'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    if ($newmaxmark === '' || !is_numeric($newmaxmark) || (float) $newmaxmark < 0) {
        redirect(
            $pageurl,
            get_string('errorinvalidvalue', 'local_quizbulkmarks'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    $changed = $manager->apply_value($slotids, (float) $newmaxmark);

    if ($changed === 0) {
        redirect(
            $pageurl,
            get_string('nochanges', 'local_quizbulkmarks'),
            null,
            \core\output\notification::NOTIFY_INFO
        );
    }

    redirect(
        $pageurl,
        get_string('success', 'local_quizbulkmarks', $changed),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Recreate objects so that the page always reflects the current quiz values.
$quizobj = quiz_settings::create($quiz->id);
$manager = new question_value_manager($quizobj);
$rows = $manager->get_question_rows();
$quiz = $quizobj->get_quiz();
$questioncount = count(array_filter($rows, static fn(array $row): bool => $row['gradable']));

$PAGE->navbar->add(get_string('navigationlink', 'local_quizbulkmarks'), $pageurl);

$backbutton = html_writer::link(
    $editurl,
    get_string('backtoquiz', 'local_quizbulkmarks'),
    ['class' => 'btn btn-outline-secondary']
);

echo $OUTPUT->header();

// Main form.
echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => $pageurl->out(false),
    'id' => 'quizbulkmarks-form',
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'cmid', 'value' => $cm->id]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'apply']);

// Introductory card.
$introparams = (object) [
    'value' => html_writer::tag(
        'strong',
        get_string('maximumvalue', 'local_quizbulkmarks'),
        ['class' => 'text-body']
    ),
    'maxmark' => html_writer::tag('code', 'maxmark'),
];

echo html_writer::start_tag('section', [
    'class' => 'card shadow-sm pb-3 mb-4',
    'aria-labelledby' => 'titulo-valores-questoes',
]);
echo html_writer::start_div('card-body p-3 p-md-4');
echo html_writer::tag('h2', get_string('heading', 'local_quizbulkmarks'), [
    'id' => 'titulo-valores-questoes',
    'class' => 'mb-2',
]);
echo html_writer::tag('p', get_string('intro', 'local_quizbulkmarks', $introparams), [
    'class' => 'mb-0',
]);
echo html_writer::end_div();
echo html_writer::end_tag('section');

// Header: quiz identification and selection tools.
echo html_writer::start_div('row g-3 mb-4');
echo html_writer::start_div('col-12');
echo html_writer::start_tag('section', [
    'class' => 'card shadow-sm',
    'aria-labelledby' => 'titulo-identificacao-selecao',
]);
echo html_writer::start_div('card-body p-3 p-md-4');
echo html_writer::tag('h3', get_string('identificationandselection', 'local_quizbulkmarks'), [
    'id' => 'titulo-identificacao-selecao',
    'class' => 'visually-hidden',
]);

// Quiz identification.
echo html_writer::start_tag('section', [
    'class' => 'mb-4',
    'aria-labelledby' => 'titulo-identificacao',
]);
echo html_writer::tag('h4', get_string('quizidentification', 'local_quizbulkmarks'), [
    'id' => 'titulo-identificacao',
    'class' => 'fs-6 fw-semibold mb-3',
]);
echo html_writer::start_div('row g-3');

echo html_writer::start_div('col-12 col-lg-4');
echo html_writer::start_div('bg-body-tertiary rounded p-3 h-100');
echo html_writer::span(get_string('quizlabel', 'local_quizbulkmarks'), 'small text-body-secondary d-block mb-1');
echo html_writer::span(format_string($quiz->name), 'fw-semibold');
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('col-6 col-lg-2');
echo html_writer::start_div('border rounded p-3 text-center h-100');
echo html_writer::span(
    get_string('questioncountlabel', 'local_quizbulkmarks'),
    'small text-body-secondary d-block'
);
echo html_writer::span((string) $questioncount, 'display-6 fw-semibold');
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('col-6 col-lg-2');
echo html_writer::start_div('border rounded p-3 text-center h-100');
echo html_writer::span(
    get_string('quiztotalvalue', 'local_quizbulkmarks'),
    'small text-body-secondary d-block'
);
echo html_writer::span(quiz_format_grade($quiz, $quiz->sumgrades), 'display-6 fw-semibold', [
    'id' => 'quizbulkmarks-current-sum',
    'data-raw-sum' => (float) $quiz->sumgrades,
]);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('col-lg-4 d-none d-lg-block', ['aria-hidden' => 'true']);
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_tag('section');

if (empty($rows)) {
    echo html_writer::end_div();
    echo html_writer::end_tag('section');
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_tag('form');
    echo $OUTPUT->notification(get_string('noquestions', 'local_quizbulkmarks'), 'info');
    echo $OUTPUT->footer();
    exit;
}

// Question selection.
echo html_writer::start_tag('section', ['aria-labelledby' => 'titulo-selecao']);
echo html_writer::start_div('mb-3');
echo html_writer::tag('h4', get_string('selectionheading', 'local_quizbulkmarks'), [
    'id' => 'titulo-selecao',
    'class' => 'fs-6 fw-semibold mb-1',
]);
echo html_writer::tag('p', get_string('selectionhelp', 'local_quizbulkmarks'), [
    'class' => 'small text-body-secondary mb-0',
]);
echo html_writer::end_div();

echo html_writer::start_div('row g-3');

// Range selection.
echo html_writer::start_div('col-12 col-lg-4');
echo html_writer::start_div('bg-body-tertiary rounded p-3 h-100');
echo html_writer::tag('h5', get_string('rangeselection', 'local_quizbulkmarks'), [
    'class' => 'fs-6 fw-semibold mb-3',
]);
echo html_writer::start_div('input-group mb-3');
echo html_writer::tag('label', get_string('rangefrom', 'local_quizbulkmarks'), [
    'for' => 'quizbulkmarks-range-from',
    'class' => 'input-group-text',
]);
echo html_writer::empty_tag('input', [
    'type' => 'number',
    'min' => 1,
    'max' => count($rows),
    'id' => 'quizbulkmarks-range-from',
    'class' => 'form-control',
    'placeholder' => get_string('rangestartplaceholder', 'local_quizbulkmarks'),
    'aria-label' => get_string('rangefromaria', 'local_quizbulkmarks'),
]);
echo html_writer::tag('label', get_string('rangeto', 'local_quizbulkmarks'), [
    'for' => 'quizbulkmarks-range-to',
    'class' => 'input-group-text',
]);
echo html_writer::empty_tag('input', [
    'type' => 'number',
    'min' => 1,
    'max' => count($rows),
    'id' => 'quizbulkmarks-range-to',
    'class' => 'form-control',
    'placeholder' => get_string('rangeendplaceholder', 'local_quizbulkmarks'),
    'aria-label' => get_string('rangetoaria', 'local_quizbulkmarks'),
]);
echo html_writer::end_div();
echo html_writer::start_div('d-grid');
echo html_writer::tag('button', get_string('selectrange', 'local_quizbulkmarks'), [
    'type' => 'button',
    'class' => 'btn btn-primary',
    'id' => 'quizbulkmarks-select-range',
]);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// General selection.
echo html_writer::start_div('col-12 col-lg-4');
echo html_writer::start_div('bg-body-tertiary rounded p-3 h-100');
echo html_writer::tag('h5', get_string('generalselection', 'local_quizbulkmarks'), [
    'class' => 'fs-6 fw-semibold mb-3',
]);
echo html_writer::start_div('d-grid gap-2');
echo html_writer::tag('button', get_string('selectall', 'local_quizbulkmarks'), [
    'type' => 'button',
    'class' => 'btn btn-primary',
    'id' => 'quizbulkmarks-select-all',
]);
echo html_writer::tag('button', get_string('selectnone', 'local_quizbulkmarks'), [
    'type' => 'button',
    'class' => 'btn btn-outline-secondary',
    'id' => 'quizbulkmarks-select-none',
]);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('col-lg-4 d-none d-lg-block', ['aria-hidden' => 'true']);
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::empty_tag('hr', ['class' => 'my-4']);
echo html_writer::start_div('mt-4');
echo $backbutton;
echo html_writer::end_div();
echo html_writer::end_tag('section');

echo html_writer::end_div();
echo html_writer::end_tag('section');
echo html_writer::end_div();
echo html_writer::end_div();

// Questions table.
echo html_writer::start_div('table-responsive mb-4');
echo html_writer::start_tag('table', [
    'class' => 'generaltable table table-striped table-hover align-middle',
    'id' => 'quizbulkmarks-table',
]);
echo html_writer::start_tag('thead');
echo html_writer::start_tag('tr');
$headings = [
    ['text' => get_string('select', 'local_quizbulkmarks'), 'class' => 'text-center'],
    ['text' => get_string('number', 'local_quizbulkmarks'), 'class' => 'text-center'],
    ['text' => get_string('question', 'local_quizbulkmarks'), 'class' => 'text-start'],
    ['text' => get_string('questiontype', 'local_quizbulkmarks'), 'class' => 'text-start'],
    ['text' => get_string('page', 'local_quizbulkmarks'), 'class' => 'text-center'],
    ['text' => get_string('currentvalue', 'local_quizbulkmarks'), 'class' => 'text-center'],
];
foreach ($headings as $heading) {
    echo html_writer::tag('th', $heading['text'], ['scope' => 'col', 'class' => $heading['class']]);
}
echo html_writer::end_tag('tr');
echo html_writer::end_tag('thead');
echo html_writer::start_tag('tbody');

foreach ($rows as $row) {
    $checkboxid = 'quizbulkmarks-slot-' . $row['slotid'];
    $label = get_string('selectquestion', 'local_quizbulkmarks', $row['slotnumber']);
    $rowattributes = [];

    if ($row['gradable']) {
        $rowattributes = [
            'class' => 'quizbulkmarks-clickable-row',
            'data-checkbox-id' => $checkboxid,
            'tabindex' => '0',
            'title' => $label,
        ];
    }

    echo html_writer::start_tag('tr', $rowattributes);
    echo html_writer::start_tag('td', ['class' => 'text-center']);
    $checkboxattributes = [
        'type' => 'checkbox',
        'name' => 'slotids[]',
        'value' => $row['slotid'],
        'id' => $checkboxid,
        'class' => 'form-check-input quizbulkmarks-slot',
        'data-slot-number' => $row['slotnumber'],
        'data-current-mark' => $row['maxmark'],
        'aria-label' => $label,
    ];
    if (!$row['gradable']) {
        $checkboxattributes['disabled'] = 'disabled';
    }
    echo html_writer::empty_tag('input', $checkboxattributes);
    echo html_writer::end_tag('td');

    echo html_writer::tag('td', (string) $row['slotnumber'], ['class' => 'text-center']);
    echo html_writer::tag('td', format_string($row['name']), ['class' => 'text-start fw-semibold']);
    echo html_writer::tag('td', s($row['qtypename'] ?? $row['qtype'] ?? ''), ['class' => 'text-start']);
    echo html_writer::tag('td', (string) $row['page'], ['class' => 'text-center']);

    $valuetext = $row['gradable']
        ? quiz_format_question_grade($quiz, $row['maxmark'])
        : get_string('notgradable', 'local_quizbulkmarks');
    echo html_writer::tag('td', $valuetext, ['class' => 'text-center']);
    echo html_writer::end_tag('tr');
}

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');
echo html_writer::end_div();

// Footer: new value definition and live change summary.
echo html_writer::start_div('row g-3');
echo html_writer::start_div('col-12');
echo html_writer::start_tag('section', [
    'class' => 'card shadow-sm',
    'aria-labelledby' => 'titulo-definicao-resumo',
]);
echo html_writer::start_div('card-body p-3 p-md-4');
echo html_writer::tag('h3', get_string('definitionandsummary', 'local_quizbulkmarks'), [
    'id' => 'titulo-definicao-resumo',
    'class' => 'visually-hidden',
]);
echo html_writer::start_div('row g-4');

// New value definition.
echo html_writer::start_div('col-12 col-lg-4');
echo html_writer::start_tag('section', ['aria-labelledby' => 'titulo-novo-valor']);
echo html_writer::tag('h4', get_string('newvaluesheading', 'local_quizbulkmarks'), [
    'id' => 'titulo-novo-valor',
    'class' => 'fs-6 fw-semibold mb-3',
]);
echo html_writer::start_div('bg-body-tertiary rounded p-3');
echo html_writer::start_div('input-group mb-2');
echo html_writer::tag('label', get_string('newvalue', 'local_quizbulkmarks'), [
    'for' => 'quizbulkmarks-new-value',
    'class' => 'input-group-text',
]);
echo html_writer::empty_tag('input', [
    'type' => 'number',
    'name' => 'newmaxmark',
    'id' => 'quizbulkmarks-new-value',
    'class' => 'form-control',
    'min' => '0',
    'step' => '0.01',
    'autocomplete' => 'off',
    'required' => 'required',
    'placeholder' => get_string('newvalueexample', 'local_quizbulkmarks'),
    'aria-label' => get_string('newvaluearia', 'local_quizbulkmarks'),
    'aria-describedby' => 'quizbulkmarks-new-value-help',
]);
echo html_writer::end_div();
echo html_writer::tag('div', get_string('newvaluehelp', 'local_quizbulkmarks'), [
    'id' => 'quizbulkmarks-new-value-help',
    'class' => 'form-text',
]);
echo html_writer::start_div('d-grid mt-3');
echo html_writer::tag('button', get_string('applybutton', 'local_quizbulkmarks'), [
    'type' => 'submit',
    'class' => 'btn btn-success',
    'id' => 'quizbulkmarks-apply',
]);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_tag('section');
echo html_writer::end_div();

// Change summary.
echo html_writer::start_div('col-12 col-lg-4');
echo html_writer::start_tag('section', ['aria-labelledby' => 'titulo-resumo-alteracao']);
echo html_writer::tag('h4', get_string('changesummary', 'local_quizbulkmarks'), [
    'id' => 'titulo-resumo-alteracao',
    'class' => 'fs-6 fw-semibold mb-3',
]);
echo html_writer::start_div('row g-3', [
    'aria-live' => 'polite',
    'aria-atomic' => 'true',
]);

echo html_writer::start_div('col-6 col-md-4');
echo html_writer::start_div('border rounded p-3 text-center h-100');
echo html_writer::span(
    get_string('selectedquantitylabel', 'local_quizbulkmarks'),
    'small text-body-secondary d-block'
);
echo html_writer::span('0', 'display-6 fw-semibold', ['id' => 'quizbulkmarks-selected-count']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('col-6 col-md-4');
echo html_writer::start_div('border rounded p-3 text-center h-100');
echo html_writer::span(
    get_string('selectedtotallabel', 'local_quizbulkmarks'),
    'small text-body-secondary d-block'
);
echo html_writer::span('0', 'display-6 fw-semibold', ['id' => 'quizbulkmarks-selected-total']);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('col-12 col-md-4');
echo html_writer::start_div(
    'bg-success-subtle border border-success-subtle rounded p-3 text-center h-100'
);
echo html_writer::span(
    get_string('newquiztotallabel', 'local_quizbulkmarks'),
    'small text-success-emphasis d-block'
);
echo html_writer::span('—', 'display-6 fw-semibold text-success-emphasis', [
    'id' => 'quizbulkmarks-projected-sum',
]);
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_tag('section');
echo html_writer::end_div();

echo html_writer::start_div('col-lg-4 d-none d-lg-block', ['aria-hidden' => 'true']);
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::empty_tag('hr', ['class' => 'my-4']);
echo html_writer::start_div('d-flex justify-content-start');
echo $backbutton;
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_tag('section');
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::end_tag('form');

echo $OUTPUT->footer();
