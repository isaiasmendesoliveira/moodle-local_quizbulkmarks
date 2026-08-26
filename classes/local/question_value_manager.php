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

namespace local_quizbulkmarks\local;

use mod_quiz\quiz_settings;

/**
 * Service for reading and changing quiz question maximum marks.
 *
 * @package   local_quizbulkmarks
 * @copyright 2026 Isaias Mendes de Oliveira <isaiasmendes@gmail.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class question_value_manager {
    /** @var quiz_settings Quiz settings object. */
    private quiz_settings $quizobj;

    /**
     * Constructor.
     *
     * @param quiz_settings $quizobj Quiz settings object.
     */
    public function __construct(quiz_settings $quizobj) {
        $this->quizobj = $quizobj;
    }

    /**
     * Return quiz slots with the presentation data required by the page.
     *
     * @return array<int, array<string, mixed>>
     */
    public function get_question_rows(): array {
        $structure = $this->quizobj->get_structure();
        $rows = [];

        foreach ($structure->get_slots() as $slot) {
            $question = $structure->get_question_in_slot($slot->slot);

            $rows[] = [
                'slotid' => (int) $slot->id,
                'slotnumber' => (int) $slot->slot,
                'page' => (int) $slot->page,
                'questionid' => (int) $slot->questionid,
                'name' => $question->name ?? get_string('unknownquestion', 'local_quizbulkmarks'),
                'qtype' => $question->qtype ?? '',
                'qtypename' => $this->get_localised_qtype_name($question->qtype ?? ''),
                'maxmark' => (float) $slot->maxmark,
                'gradable' => $structure->is_real_question($slot->slot),
            ];
        }

        return $rows;
    }

    /**
     * Return the human-readable question type name in the current language.
     *
     * @param string $qtype Internal question type name, for example multichoice.
     * @return string Localised question type name.
     */
    private function get_localised_qtype_name(string $qtype): string {
        if ($qtype === '') {
            return '';
        }

        $component = 'qtype_' . $qtype;
        $stringmanager = get_string_manager();

        if ($stringmanager->string_exists('pluginname', $component)) {
            return get_string('pluginname', $component);
        }

        try {
            return \question_bank::get_qtype_name($qtype);
        } catch (\Throwable) {
            return $qtype;
        }
    }

    /**
     * Apply one maximum mark to the selected quiz slots.
     *
     * The same recalculation sequence used by the quiz activity after a mark
     * change is executed once after all selected slots have been processed.
     *
     * @param int[] $slotids Quiz slot record IDs selected by the user.
     * @param float $newmaxmark New maximum mark for each selected question.
     * @return int Number of slots whose value was changed.
     */
    public function apply_value(array $slotids, float $newmaxmark): int {
        global $DB;

        if ($newmaxmark < 0 || !is_finite($newmaxmark)) {
            throw new \invalid_parameter_exception(
                get_string('exceptioninvalidmaxmark', 'local_quizbulkmarks')
            );
        }

        $structure = $this->quizobj->get_structure();
        $validslots = $structure->get_slots();
        $quiz = $this->quizobj->get_quiz();
        $gradecalculator = $this->quizobj->get_grade_calculator();

        $slotids = array_values(array_unique(array_map('intval', $slotids)));
        $changed = 0;

        $transaction = $DB->start_delegated_transaction();

        foreach ($slotids as $slotid) {
            if (!isset($validslots[$slotid])) {
                continue;
            }

            $slot = $validslots[$slotid];
            if (!$structure->is_real_question($slot->slot)) {
                continue;
            }

            if ($structure->update_slot_maxmark($slot, $newmaxmark)) {
                $changed++;
            }
        }

        if ($changed > 0) {
            quiz_delete_previews($quiz);
            $gradecalculator->recompute_quiz_sumgrades();
            $gradecalculator->recompute_all_attempt_sumgrades();
            $gradecalculator->recompute_all_final_grades();
            quiz_update_grades($quiz, 0, true);
        }

        $transaction->allow_commit();

        return $changed;
    }
}
