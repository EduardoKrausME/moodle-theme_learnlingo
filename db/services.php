<?php
// This file is part of Moodle - http://moodle.org/
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
 * AJAX services for LearnLingo editor assets.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'theme_learnlingo_list_editor_files' => [
        'classname' => 'theme_learnlingo\\external\\editor_files',
        'methodname' => 'list_files',
        'description' => 'List image assets stored by the LearnLingo visual editor.',
        'type' => 'read',
        'capabilities' => 'moodle/site:config',
        'ajax' => true,
    ],
    'theme_learnlingo_delete_editor_file' => [
        'classname' => 'theme_learnlingo\\external\\editor_files',
        'methodname' => 'delete_file',
        'description' => 'Delete an image asset stored by the LearnLingo visual editor.',
        'type' => 'write',
        'capabilities' => 'moodle/site:config',
        'ajax' => true,
    ],
];
