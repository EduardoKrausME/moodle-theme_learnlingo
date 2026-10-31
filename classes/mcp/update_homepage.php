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
 * MCP WRITE tool to update LearnLingo homepage settings.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo\mcp;

use context;
use context_system;
use local_mcp\security\authenticated_identity;
use local_mcp\write\tool\base_tool;

/**
 * Update home settings using the theme's existing configuration validator.
 */
final class update_homepage extends base_tool {
    /**
     * Return the MCP tool identifier.
     *
     * @return string Result.
     */
    public function get_name(): string {
        return 'learnlingo_update_homepage';
    }

    /**
     * Return the human-readable title.
     *
     * @return string Result.
     */
    public function get_title(): string {
        return 'Update LearnLingo homepage';
    }

    /**
     * Describe this tool.
     *
     * @return string Result.
     */
    public function get_description(): string {
        return 'Update the LearnLingo homepage mode, responsive HTML, full-width '
            . 'layout, homepage footer HTML and optional SCSS. Reuses theme configuration validation, '
            . 'previews changes and requires explicit confirmation. '
            . 'Read the current state with learnlingo_get_homepage first.';
    }

    /**
     * Return the required capability.
     *
     * @return string Result.
     */
    public function get_required_capability(): string {
        return 'moodle/site:config';
    }

    /**
     * Return the input JSON schema.
     *
     * @return array Result.
     */
    public function get_input_schema(): array {
        return $this->object_schema([
            'mode' => ['type' => 'string', 'enum' => ['moodle', 'learnlingo', 'html'],
                'description' => 'Homepage rendering mode. HTML updates preserve LearnLingo mode.'],
            'html' => ['type' => 'string',
                'description' => 'HTML home content, validated against the LearnLingo JSON configuration rules.'],
            'fullwidth' => ['type' => 'boolean'],
            'footerhtml' => ['type' => 'string',
                'description' => 'HTML footer shown only on the LearnLingo homepage.'],
            'scss' => ['type' => 'string',
                'description' => 'Optional custom theme SCSS, validated against the theme rules.'],
            'expected_hash' => ['type' => 'string',
                'description' => 'Optional state_hash from learnlingo_get_homepage for concurrency checking.'],
        ]);
    }

    /**
     * Resolve the security context.
     *
     * @param array $arguments Input arguments.
     * @return context Result.
     */
    public function resolve_context(array $arguments): context {
        return context_system::instance();
    }

    /**
     * Indicate whether previews are supported.
     *
     * @return bool Result.
     */
    public function supports_dry_run(): bool {
        return true;
    }

    /**
     * Summarize the change without echoing its potentially long HTML or SCSS.
     *
     * @param array $arguments Input values.
     * @param authenticated_identity $identity Connected user.
     * @return array Preview summary.
     */
    public function preview(array $arguments, authenticated_identity $identity): array {
        $prepared = home_service::prepare($arguments);
        return [
            'mode_before' => $prepared['before']['mode'],
            'mode_after' => $prepared['after']['mode'],
            'fullwidth_before' => $prepared['before']['fullwidth'],
            'fullwidth_after' => $prepared['after']['fullwidth'],
            'footerhtml_bytes_before' => strlen($prepared['before']['footerhtml']),
            'footerhtml_bytes_after' => strlen($prepared['after']['footerhtml']),
            'html_bytes_before' => strlen($prepared['before']['html']),
            'html_bytes_after' => strlen($prepared['after']['html']),
            'scss_bytes_before' => strlen($prepared['before']['scss']),
            'scss_bytes_after' => strlen($prepared['after']['scss']),
            'changed_settings' => array_keys($prepared['settings']),
            'requires_confirmation' => true,
        ];
    }

    /**
     * Validate and persist exactly the specified settings.
     *
     * @param array $arguments Input values.
     * @param authenticated_identity $identity Connected user.
     * @return array Result metadata.
     */
    public function execute(array $arguments, authenticated_identity $identity): array {
        $prepared = home_service::prepare($arguments);
        home_service::apply($prepared);
        $current = home_service::snapshot();
        return [
            'success' => true,
            'mode' => $current['mode'],
            'fullwidth' => $current['fullwidth'],
            'html_bytes' => strlen($current['html']),
            'footerhtml_bytes' => strlen($current['footerhtml']),
            'scss_bytes' => strlen($current['scss']),
            'state_hash' => $current['state_hash'],
            'changed_settings' => array_keys($prepared['settings']),
        ];
    }
}
