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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tests for the LearnLingo course overview image URL.
 *
 * @package   theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use advanced_testcase;
use context_course;
use core_course_list_element;
use theme_learnlingo\output\core\course_renderer;

/**
 * The course image URL must follow Moodle's course/overviewfiles routing.
 *
 * @covers \theme_learnlingo\output\core\course_renderer
 */
final class course_renderer_test extends advanced_testcase {
    /**
     * Course images use itemid 0 in storage but omit it from pluginfile URLs.
     */
    public function test_course_overview_image_url_does_not_include_itemid(): void {
        global $CFG, $PAGE;

        // The legacy course renderer is loaded by course/lib.php during web
        // requests, but PHPUnit must load it before instantiating the theme renderer.
        require_once($CFG->dirroot . '/course/renderer.php');

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $context = context_course::instance($course->id);
        $PAGE->set_course($course);

        $file = get_file_storage()->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'course',
            'filearea' => 'overviewfiles',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'cover.png',
        ], base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL/nwAAAABJRU5ErkJggg=='));

        $renderer = new class($PAGE, 'html') extends course_renderer {
            /**
             * Expose the protected image URL helper for this integration test.
             *
             * @param core_course_list_element $course Course overview.
             * @return string
             */
            public function overview_image_url(core_course_list_element $course): string {
                return $this->getcourseimageurl($course);
            }
        };
        $url = $renderer->overview_image_url(new core_course_list_element($course));

        $this->assertSame(0, (int)$file->get_itemid());
        $this->assertStringEndsWith(
            '/' . $context->id . '/course/overviewfiles/cover.png',
            parse_url($url, PHP_URL_PATH)
        );
        $this->assertStringNotContainsString('/overviewfiles/0/', $url);
    }
}

