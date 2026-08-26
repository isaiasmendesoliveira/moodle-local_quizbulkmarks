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

namespace local_quizbulkmarks;

use local_quizbulkmarks\local\question_value_manager;
use mod_quiz\quiz_settings;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/locallib.php');

/**
 * Tests for the question value manager.
 *
 * @package   local_quizbulkmarks
 * @category  test
 * @copyright 2026 Isaias Mendes de Oliveira <isaiasmendes@gmail.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers    \local_quizbulkmarks\local\question_value_manager
 */
final class question_value_manager_test extends \advanced_testcase {
    /**
     * Test that only selected question slots receive the new maximum mark.
     *
     * @return void
     */
    public function test_apply_value_updates_selected_slots_only(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance([
            'course' => $course->id,
            'grade' => 10.0,
        ]);

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category();
        $question1 = $questiongenerator->create_question('truefalse', null, ['category' => $category->id]);
        $question2 = $questiongenerator->create_question('shortanswer', null, ['category' => $category->id]);

        quiz_add_quiz_question($question1->id, $quiz, 0, 1.0);
        quiz_add_quiz_question($question2->id, $quiz, 0, 1.0);

        $manager = new question_value_manager(quiz_settings::create($quiz->id));
        $rows = $manager->get_question_rows();
        $this->assertCount(2, $rows);

        $changed = $manager->apply_value([$rows[0]['slotid']], 2.5);
        $this->assertSame(1, $changed);

        $manager = new question_value_manager(quiz_settings::create($quiz->id));
        $rows = $manager->get_question_rows();

        $this->assertEquals(2.5, $rows[0]['maxmark']);
        $this->assertEquals(1.0, $rows[1]['maxmark']);
    }

    /**
     * Test that description items are not changed because they are not gradable.
     *
     * @return void
     */
    public function test_apply_value_skips_description_items(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance(['course' => $course->id]);

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $category = $questiongenerator->create_question_category();
        $description = $questiongenerator->create_question('description', null, ['category' => $category->id]);
        quiz_add_quiz_question($description->id, $quiz);

        $manager = new question_value_manager(quiz_settings::create($quiz->id));
        $rows = $manager->get_question_rows();
        $this->assertCount(1, $rows);
        $this->assertFalse($rows[0]['gradable']);

        $changed = $manager->apply_value([$rows[0]['slotid']], 3.0);
        $this->assertSame(0, $changed);
    }
    /**
     * Test that invalid negative maximum marks are rejected.
     *
     * @return void
     */
    public function test_apply_value_rejects_negative_mark(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance(['course' => $course->id]);

        $manager = new question_value_manager(quiz_settings::create($quiz->id));

        $this->expectException(\invalid_parameter_exception::class);
        $manager->apply_value([], -1.0);
    }

}
