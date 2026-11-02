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
 * MCP read access to all LearnLingo theme settings.
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
use theme_learnlingo\homepage_context;

/**
 * Return all portable settings and the uploaded raster branding image metadata.
 */
final class get_settings implements tool_interface {
    /**
     * Return the MCP tool identifier.
     *
     * @return string Result.
     */
    public function get_name(): string {
        return 'learnlingo_get_settings';
    }

    /**
     * Return the human-readable title.
     *
     * @return string Result.
     */
    public function get_title(): string {
        return 'Read all LearnLingo settings';
    }

    /**
     * Describe this tool.
     *
     * @return string Result.
     */
    public function get_description(): string {
        return 'Read all LearnLingo settings: colors, homepagehtml, global '
            . 'homepagescss (active on every page), scsspre (before Boost), '
            . 'scss (global after Boost), independent footermode, footerhtml '
            . 'and footerscss (only inside the HTML footer), traditional footer '
            . 'columns, login/dashboard/course options, shared site logo, login-only logo and favicon. '
            . 'Only homepagehtml supports PHP server-side Mustache: '
            . '{{#isloggedin}}, {{user.firstname}}, {{#courses}}, '
            . '{{#mycourses}}, {{#cohorts}} and course progress. The result '
            . 'homepage_mustache includes AI instructions, all variables, '
            . 'examples, public course selection, anonymous-only page size '
            . '(homepagepubliccoursesperpage), responsive columns, pagination, '
            . 'privacy and caching rules; '
            . 'follow it when generating the homepage. Returns file metadata '
            . 'and state_hash for learnlingo_update_settings; include_images '
            . 'also returns small uploaded branding images. '
            . 'Also returns dark_mode status for optional local_boost_dark: '
            . 'custom CSS and HTML must support both [data-bs-theme="dark"] and light mode; '
            . 'use homepage Mustache with CSS variables and dark overrides rather than {{isdark}}.';
    }

    /**
     * Return the input JSON schema.
     *
     * @return array Result.
     */
    public function get_input_schema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'include_images' => [
                    'type' => 'boolean',
                    'description' => 'Attach PNG/JPEG/WebP branding images of up to 2 MiB; '
                        . 'ICO favicons are returned as metadata only.',
                ],
            ],
            'additionalProperties' => false,
        ];
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
     * Resolve the security context.
     *
     * @param array $arguments Input arguments.
     * @return context Result.
     */
    public function resolve_context(array $arguments): context {
        return context_system::instance();
    }

    /**
     * Execute the validated operation.
     *
     * @param array $arguments Input arguments.
     * @param authenticated_identity $identity Connected user.
     * @return array Result.
     */
    public function execute(array $arguments, authenticated_identity $identity): array {
        $result = settings_service::snapshot(!empty($arguments['include_images']));
        // Send the complete field guide alongside settings, without changing state_hash.
        $result['homepage_mustache'] = homepage_context::schema();
        return $result;
    }
}
