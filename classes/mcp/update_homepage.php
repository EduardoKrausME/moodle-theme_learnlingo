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
        return 'Update homepage mode (moodle, learnlingo or html), homepagehtml, '
            . 'full-width layout, homepagescss or global scss. homepagehtml is '
            . 'shown above course cards in learnlingo mode and is the main content '
            . 'in html mode. homepagescss is compiled on every page in all modes; '
            . 'it can style the homepage, navbar, menus and shared footer. '
            . 'Use explicit homepage selectors for homepage-only rules. '
            . 'The homepagehtml field alone is processed using server-side PHP '
            . 'Mustache and can loop {{#courses}}, {{#mycourses}} and {{#cohorts}}, '
            . 'and access {{user.firstname}}, {{isloggedin}} and course progress. '
            . 'Use learnlingo_get_homepage for the complete context reference. '
            . 'publiccourses selects visible course IDs for visitors, and an empty '
            . 'selection includes all public courses in Moodle sort order. '
            . 'For footermode, footerhtml and footer-scoped footerscss use '
            . 'learnlingo_update_settings. Validates changes, previews them and '
            . 'requires explicit confirmation. Read learnlingo_get_homepage first.';
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
                'description' => 'moodle: native homepage; learnlingo: theme homepage; '
                    . 'html: custom homepage. Independent of footermode.'],
            'html' => ['type' => 'string',
                'description' => 'homepagehtml: HTML above course cards in learnlingo mode, '
                    . 'main homepage content in html mode, hidden in moodle mode. '
                    . 'If mode is omitted, updating HTML preserves learnlingo mode '
                    . 'or activates html mode when the previous mode is different. '
                    . 'Use HTML markup with server-side Mustache loops/variables '
                    . 'documented by learnlingo_get_homepage, without scripts.'],
            'publiccourses' => ['type' => 'string',
                'description' => 'Comma-separated IDs, for example 12,14,18, to show in '
                    . '{{#courses}} for logged-out visitors. Empty string selects all '
                    . 'publicly visible courses in Moodle sort order. No enrolment changes.'],
            'fullwidth' => ['type' => 'boolean',
                'description' => 'homepagefullwidth: use full available width '
                    . 'for learnlingo and html homepage modes; irrelevant in moodle mode.'],
            'homepagescss' => ['type' => 'string',
                'description' => 'Full SCSS stylesheet compiled on every page regardless '
                    . 'of homepagemode; also styles the navbar, menu and footer. '
                    . 'NOT automatically scoped to the homepage. Use explicit '
                    . 'homepage selectors for homepage-only rules. Do not use a '
                    . 'bare & at stylesheet root or remote CSS imports.'],
            'scss' => ['type' => 'string',
                'description' => 'Global SCSS compiled after Boost on all pages, '
                    . 'independent of homepagemode and footermode. '
                    . 'homepagescss is also global, not homepage-only.'],
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
            'publiccourses_before' => $prepared['before']['publiccourses'],
            'publiccourses_after' => $prepared['after']['publiccourses'],
            'html_bytes_before' => strlen($prepared['before']['html']),
            'html_bytes_after' => strlen($prepared['after']['html']),
            'homepagescss_bytes_before' => strlen($prepared['before']['homepagescss']),
            'homepagescss_bytes_after' => strlen($prepared['after']['homepagescss']),
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
            'publiccourses' => $current['publiccourses'],
            'html_bytes' => strlen($current['html']),
            'homepagescss_bytes' => strlen($current['homepagescss']),
            'scss_bytes' => strlen($current['scss']),
            'state_hash' => $current['state_hash'],
            'changed_settings' => array_keys($prepared['settings']),
        ];
    }
}
