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
 * Stores accessibility interaction events in Moodle's privacy-managed database.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

/**
 * Dedicated persistence service for accessibility usage events.
 */
final class accessibility_logger {
    /**
     * Store a validated interaction without storing URL query secrets.
     *
     * @param int $userid Logged-in user.
     * @param array $data Validated event.
     * @return int New record identifier.
     */
    public static function record(int $userid, array $data): int {
        global $DB;

        $pagepath = parse_url((string)$data["pageurl"], PHP_URL_PATH);
        $record = (object)[
            "userid" => $userid,
            "courseid" => (int)$data["courseid"],
            "cmid" => (int)$data["cmid"],
            "action" => (string)$data["action"],
            "item" => \core_text::substr((string)$data["item"], 0, 100),
            "status" => (string)$data["status"],
            "activeitems" => \core_text::substr((string)$data["activeitems"], 0, 1000),
            "statejson" => \core_text::substr((string)$data["statejson"], 0, 3000),
            "pageurl" => is_string($pagepath) ? \core_text::substr($pagepath, 0, 2048) : "",
            "timecreated" => time(),
        ];

        return $DB->insert_record("theme_learnlingo_accesslog", $record);
    }
}
