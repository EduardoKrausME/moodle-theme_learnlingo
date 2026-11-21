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
 * PHPUnit coverage for accessibility interaction persistence.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use advanced_testcase;

/**
 * Tests the accessibility interaction persistence service.
 *
 * @covers \theme_learnlingo\accessibility_logger
 */
final class accessibility_logger_test extends advanced_testcase {
    /**
     * Do not store URL query parameters that might contain session tokens.
     */
    public function test_record_strips_query_parameters(): void {
        global $DB;

        $this->resetAfterTest();
        $userid = (int)get_admin()->id;
        $id = accessibility_logger::record($userid, [
            "action" => "toolbar_open",
            "item" => "mic-access-tool-general-button",
            "status" => "opened",
            "courseid" => SITEID,
            "cmid" => 0,
            "pageurl" => "https://example.org/course/view.php?id=10&sesskey=secret",
            "activeitems" => "",
            "statejson" => "{}",
        ]);

        $record = $DB->get_record("theme_learnlingo_accesslog", ["id" => $id], "*", MUST_EXIST);
        $this->assertEquals($userid, $record->userid);
        $this->assertSame("/course/view.php", $record->pageurl);
        $this->assertSame("toolbar_open", $record->action);
    }
}
