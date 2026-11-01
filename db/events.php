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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Purge public homepage catalogue after committed course/category changes.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$observers = [];
foreach ([
    '\\core\\event\\course_created',
    '\\core\\event\\course_updated',
    '\\core\\event\\course_deleted',
    '\\core\\event\\course_category_created',
    '\\core\\event\\course_category_updated',
    '\\core\\event\\course_category_deleted',
    '\\core\\event\\course_restored',
] as $eventname) {
    $observers[] = [
        'eventname' => $eventname,
        'callback' => '\\theme_learnlingo\\observer::public_courses_changed',
        'internal' => false,
    ];
}
