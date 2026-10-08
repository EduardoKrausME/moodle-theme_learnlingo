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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU GPL for details.

/**
 * Expose LearnLingo configuration as MCP tools.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo\mcp;

use local_mcp\extension\read_provider_interface;
use local_mcp\extension\write_provider_interface;

/**
 * Automatically discovered by local_mcp while the theme is installed and enabled.
 *
 * No lib.php callback is needed; the READ and WRITE tools retain separate scopes.
 */
final class provider implements read_provider_interface, write_provider_interface {
    /** @return \local_mcp\read\tool_interface[] */
    public function get_read_tools(): array {
        return [new get_homepage(), new get_settings()];
    }

    /** @return \local_mcp\write\tool_interface[] */
    public function get_write_tools(): array {
        return [new update_homepage(), new update_settings()];
    }
}
