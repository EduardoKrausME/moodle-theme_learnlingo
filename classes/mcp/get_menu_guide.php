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
 * MCP READ tool exposing the full LearnLingo navbar structure.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo\mcp;

use context;
use context_system;
use local_mcp\read\tool_interface;
use local_mcp\security\authenticated_identity;

/**
 * Give MCP clients the actual logged-in and guest navbar selector hierarchy.
 */
final class get_menu_guide implements tool_interface {
    /**
     * Describe the menu-structure tool output.
     *
     * @return string Tool output.
     */
    public function get_name(): string {
        return 'learnlingo_get_menu_guide';
    }

    /**
     * Describe the menu-structure tool output.
     *
     * @return string Tool output.
     */
    public function get_title(): string {
        return 'LearnLingo menu structure and SCSS guide';
    }

    /**
     * Describe the menu-structure tool output.
     *
     * @return string Tool output.
     */
    public function get_description(): string {
        return 'MANDATORY reference for creating, editing or explaining the LearnLingo menu, '
            . 'navbar or its CSS/SCSS. Returns the complete minimal selector hierarchy '
            . 'for guest and logged-in navigation: navbar toggler, brand/logo, main links, '
            . 'More dropdown, notification/message popovers and counts, dark-mode switch, '
            . 'guest login, user avatar/dropdown and edit-mode switch. '
            . 'Follow the returned instructions and scss_outline; style shared navigation '
            . 'in global scss, never in homepagescss. Before writing use learnlingo_get_settings '
            . 'to read current styles, and preview and request approval.';
    }

    /**
     * Describe the menu-structure tool output.
     *
     * @return array Tool output.
     */
    public function get_input_schema(): array {
        return [
            'type' => 'object',
            'properties' => (object)[],
            'additionalProperties' => false,
        ];
    }

    /**
     * Describe the menu-structure tool output.
     *
     * @return string Tool output.
     */
    public function get_required_capability(): string {
        return 'moodle/site:config';
    }

    /**
     * Resolve the system security context.
     *
     * @param array $arguments Request arguments.
     * @return context System context.
     */
    public function resolve_context(array $arguments): context {
        return context_system::instance();
    }

    /**
     * Return the complete navigation styling guide.
     *
     * @param array $arguments Request arguments.
     * @param authenticated_identity $identity Authenticated administrator.
     * @return array Guide data.
     */
    public function execute(array $arguments, authenticated_identity $identity): array {
        return menu_guide::schema();
    }
}
