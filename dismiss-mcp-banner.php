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
 * Persistent MCP promotion banner.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use theme_learnlingo\mcp_banner;

require_once(__DIR__ . "/../../config.php");

require_login();
require_capability("moodle/site:config", context_system::instance());

$returnurl = new moodle_url("/admin/settings.php", ["section" => "themesettinglearnlingo"]);
mcp_banner::dismiss_if_requested($returnurl);
redirect($returnurl);
