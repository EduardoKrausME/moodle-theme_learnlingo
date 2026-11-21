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
 * AJAX controller for accessibility toolbar interaction logging.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define("AJAX_SCRIPT", true);

require_once(__DIR__ . "/../../config.php");

require_sesskey();
header("Content-Type: application/json; charset=utf-8");

if (!isloggedin() || isguestuser()) {
    echo json_encode(["success" => true, "stored" => false, "reason" => "guest"]);
    die;
}

if (!get_config("theme_learnlingo", "enable_accessibility") &&
        !get_config("theme_learnlingo", "enable_vlibras")) {
    echo json_encode(["success" => true, "stored" => false, "reason" => "disabled"]);
    die;
}

$action = required_param("action", PARAM_ALPHANUMEXT);
$status = optional_param("status", "", PARAM_ALPHANUMEXT);
if (!in_array($action, ["vlibras_tab_click", "toolbar_open", "toolbar_item", "toolbar_reset"], true) ||
        !in_array($status, ["", "clicked", "opened", "enabled", "disabled"], true)) {
    echo json_encode(["success" => false, "stored" => false, "reason" => "invalid"]);
    die;
}

$statejson = optional_param("statejson", "", PARAM_RAW_TRIMMED);
if ($statejson !== "" && !is_array(json_decode($statejson, true))) {
    echo json_encode(["success" => false, "stored" => false, "reason" => "invalid"]);
    die;
}

$recordid = \theme_learnlingo\accessibility_logger::record((int)$USER->id, [
    "action" => $action,
    "item" => optional_param("item", "", PARAM_TEXT),
    "status" => $status,
    "courseid" => optional_param("courseid", 0, PARAM_INT),
    "cmid" => optional_param("cmid", 0, PARAM_INT),
    "pageurl" => optional_param("pageurl", "", PARAM_RAW_TRIMMED),
    "activeitems" => optional_param("activeitems", "", PARAM_RAW_TRIMMED),
    "statejson" => $statejson,
]);

echo json_encode(["success" => true, "stored" => true, "id" => $recordid]);
