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
 * MCP read tool for images stored by LearnLingo.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo\mcp;

use context;
use context_system;
use local_mcp\read\tool_interface;
use local_mcp\security\authenticated_identity;
use theme_learnlingo\editor_assets;

/**
 * Expose existing editor image metadata without embedding image bytes.
 */
final class get_editor_images implements tool_interface {
    /**
     * Return the name of the MCP image-listing tool.
     *
     * @return string Tool name.
     */
    public function get_name(): string {
        return 'learnlingo_get_editor_images';
    }

    /**
     * Return the title displayed for this MCP tool.
     *
     * @return string Title.
     */
    public function get_title(): string {
        return 'List LearnLingo images';
    }

    /**
     * Describe the MCP image-listing operation.
     *
     * @return string Description.
     */
    public function get_description(): string {
        return 'List images stored by the LearnLingo MCP upload tool '
            . 'with the file id, original filename, size and public pluginfile URL. '
            . 'Use a file.src URL in homepage, footer or custom-page HTML.';
    }

    /**
     * Declare that this MCP tool accepts no arguments.
     *
     * @return array No inputs.
     */
    public function get_input_schema(): array {
        return [
            'type' => 'object',
            'properties' => (object)[],
            'additionalProperties' => false,
        ];
    }

    /**
     * Identify the capability required to list stored images.
     *
     * @return string Required site capability.
     */
    public function get_required_capability(): string {
        return 'moodle/site:config';
    }

    /**
     * Resolve the system context used to validate permissions.
     *
     * @param array $arguments Tool inputs.
     * @return context Context.
     */
    public function resolve_context(array $arguments): context {
        return context_system::instance();
    }

    /**
     * List accessible LearnLingo image metadata.
     *
     * @param array $arguments No parameters expected.
     * @param authenticated_identity $identity Connected administrator.
     * @return array Asset inventory.
     */
    public function execute(array $arguments, authenticated_identity $identity): array {
        $files = editor_assets::list_files();
        return ['count' => count($files), 'files' => $files];
    }
}
