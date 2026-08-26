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

/**
 * Registers the button shown on the native quiz question-editing page.
 *
 * @package   local_quizbulkmarks
 * @copyright 2026 Isaias Mendes de Oliveira <isaiasmendes@gmail.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class edit_page_integration {
    /** @var bool Whether the JavaScript was already registered in this request. */
    private static bool $registered = false;

    /**
     * Register the AMD module when the current page is the quiz editor.
     *
     * @return void
     */
    public static function register(): void {
        global $PAGE;

        if (self::$registered || during_initial_install()) {
            return;
        }

        $iseditpage = (($PAGE->pagetype ?? '') === 'mod-quiz-edit');
        if (!$iseditpage && !empty($PAGE->url)) {
            $iseditpage = str_ends_with($PAGE->url->get_path(), '/mod/quiz/edit.php');
        }
        if (!$iseditpage) {
            return;
        }

        $cmid = 0;
        if (!empty($PAGE->cm) && (($PAGE->cm->modname ?? '') === 'quiz')) {
            $cmid = (int) $PAGE->cm->id;
        }

        if ($cmid === 0) {
            $cmid = optional_param('cmid', 0, PARAM_INT);
        }
        if ($cmid <= 0) {
            return;
        }

        $cm = get_coursemodule_from_id('quiz', $cmid, 0, false, IGNORE_MISSING);
        if (!$cm) {
            return;
        }

        $context = \context_module::instance($cm->id);
        if (!has_capability('mod/quiz:manage', $context)) {
            return;
        }

        $url = new \moodle_url('/local/quizbulkmarks/index.php', ['cmid' => $cm->id]);

        $PAGE->requires->js_call_amd(
            'local_quizbulkmarks/editbutton',
            'init',
            [
                $url->out(false),
                get_string('editpagebutton', 'local_quizbulkmarks'),
                get_string('editpagebuttonaria', 'local_quizbulkmarks'),
            ]
        );

        self::$registered = true;
    }
}
