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
 * Library callbacks for the Bulk quiz question values plugin.
 *
 * @package   local_quizbulkmarks
 * @copyright 2026 Isaias Mendes de Oliveira <isaiasmendes@gmail.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Register the integration with the quiz question-editing page.
 *
 * Local plugins can extend global navigation during page setup. The callback
 * is used only to register the AMD module that adds the plugin button to the
 * native quiz question-editing toolbar.
 *
 * @param global_navigation $navigation Global navigation instance.
 * @return void
 */
function local_quizbulkmarks_extend_navigation(global_navigation $navigation): void {
    \local_quizbulkmarks\local\edit_page_integration::register();
}

/**
 * Register the integration while settings navigation is being built.
 *
 * This provides a second supported lifecycle point for themes and Moodle
 * versions that initialise activity navigation in a different order. The
 * integration class prevents duplicate JavaScript registration.
 *
 * @param settings_navigation $settingsnav Settings navigation instance.
 * @param context $context Current context.
 * @return void
 */
function local_quizbulkmarks_extend_settings_navigation(
    settings_navigation $settingsnav,
    context $context
): void {
    \local_quizbulkmarks\local\edit_page_integration::register();
}
