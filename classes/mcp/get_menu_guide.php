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
 * MCP READ tool exposing LearnLingo navbar variable customization rules.
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
 * Give MCP clients navbar variable customization instructions and an allowlist.
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
        return 'LearnLingo navbar CSS variable guide';
    }

    /**
     * Describe the menu-structure tool output.
     *
     * @return string Tool output.
     */
    public function get_description(): string {
        return 'MANDATORY reference for LearnLingo menu/navbar styling. '
            . 'For ordinary navbar changes, override ONLY the names in allowed_variables '
            . 'using :root and html[data-bs-theme="dark"] in global scss, never homepagescss. '
            . 'DO NOT add height, min-height, padding or other layout rules unless the user '
            . 'explicitly requests the exact structural change. --navbar-height may only '
            . 'change on an explicit height request. Preserve existing controls and styles. '
            . 'Read learnlingo_get_settings first; preview and request approval before writing.';
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
