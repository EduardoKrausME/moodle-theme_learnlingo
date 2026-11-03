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
            . 'reserve it for explicitly scoped homepage-only rules. '
            . 'For any navbar/menu CSS task, MUST follow the complete menu_guide '
            . 'selector structure from learnlingo_get_homepage/get_settings or '
            . 'call learnlingo_get_menu_guide to cover guests and logged-in users. '
            . 'Navbar, menu and shared header CSS MUST go into global scss, '
            . 'not homepagescss, even though that field also compiles globally. '
            . 'Custom Mustache homes must work in light and dark modes: '
            . 'use [data-bs-theme="dark"] .your-home in homepagescss '
            . 'with dark-specific contrast, surfaces, cards and controls; '
            . 'prefer the --learnlingo-home-* CSS tokens and do not '
            . 'invent server-side {{isdark}} or add toggle scripts. '
            . 'MUSTACHE INSTRUCTIONS FOR CHATGPT/CLAUDE: only homepagehtml '
            . 'is processed server-side by PHP; native templates and footer are not. '
            . 'Call learnlingo_get_homepage first, follow the returned '
            . 'mustache.instructions/fields/examples, and use placeholders '
            . 'rather than hardcoded course/user data. {{#isloggedin}} shows '
            . 'private content, {{^isloggedin}} shows the visitor variant, '
            . '{{#courses}} loops public courses, {{#mycourses}} loops the '
            . 'current student enrolled courses and {{#cohorts}} loops own cohorts. '
            . 'Use {{#hasimage}} for {{imageurl}} and {{#hasprogress}} for '
            . '{{progress}} only in own enrolled courses. No JS/PHP, partials or '
            . 'SQL in homepagehtml. publiccourses is a comma-separated list '
            . 'of visible course IDs; empty means all public courses in sort order. '
            . 'publiccoursesperpage is a string from 1 to 12 (default 4), '
            . 'limiting ONLY guests; use coursepagination.coursecolumns '
            . 'for the actual number of courses and coursepagination.coursepages '
            . 'for navigation. Do not limit authenticated mycourses or invent '
            . 'missing courses to fill a fixed-width grid. '
            . 'Guest catalogue is MUC-cached for 300s with event invalidation; '
            . 'user data, enrollments, progress and final HTML are never shared-cached. '
            . 'For a full site/home redesign also read learnlingo_get_settings; '
            . 'coordinate footerhtml/footerscss/footermode, images.logo and '
            . 'images.favicon with the home, request separate approval using '
            . 'learnlingo_update_footer or learnlingo_update_settings, and verify '
            . 'them after execution. Do not call the redesign finished while '
            . 'branding/footer are inconsistent. The approved write executes '
            . 'Moodle purge_all_caches after saving, the same operation as the '
            . 'admin Purge all caches button; verify theme_revision '
            . 'in the result. Validates, previews and requires explicit confirmation.';
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
                    . 'Use server-side PHP Mustache for dynamic content: '
                    . '{{#isloggedin}}...{{/isloggedin}}, '
                    . '{{^isloggedin}}...{{/isloggedin}}, {{user.firstname}}, '
                    . '{{#courses}}...{{/courses}}, '
                    . '{{#mycourses}}...{{/mycourses}}, '
                    . '{{#cohorts}}...{{/cohorts}}. Course fields include '
                    . '{{fullname}}, {{url}}, {{imageurl}}, {{hasimage}}, '
                    . '{{hasprogress}} and {{progress}}. Only mycourses has '
                    . 'personal progress; guard it with {{#hasprogress}}. '
                    . 'Guest content must not rely on user or mycourses. '
                    . 'Call learnlingo_get_homepage to read mustache.instructions '
                    . 'and full field reference. Do not hardcode visitor names, '
                    . 'enrollments or percentages, and do not add scripts. '
                    . 'The same Mustache HTML must work in light and dark mode; '
                    . 'place CSS dark overrides in homepagescss, not conditional '
                    . '{{isdark}} sections in homepagehtml.'],
            'publiccoursesperpage' => ['type' => 'string', 'pattern' => '^(?:[1-9]|1[0-2])$',
                'description' => '1 to 12 course cards per page in {{#courses}} ONLY '
                    . 'for logged-out visitors, default 4. The guest-only '
                    . 'coursepagination object provides coursecolumns (actual cards, '
                    . 'not the requested count), coursepage, coursepages, '
                    . 'haspreviouscoursepage, hasnextcoursepage, '
                    . 'previouscoursepageurl and nextcoursepageurl. '
                    . 'Authenticated user mycourses and progress are not limited.'],
            'publiccourses' => ['type' => 'string',
                'description' => 'Comma-separated IDs, for example 12,14,18, to show in '
                    . '{{#courses}} for logged-out visitors. Empty string selects all '
                    . 'publicly visible courses in Moodle sort order. No enrolment changes.'],
            'fullwidth' => ['type' => 'boolean',
                'description' => 'homepagefullwidth: use full available width '
                    . 'for learnlingo and html homepage modes; irrelevant in moodle mode.'],
            'homepagescss' => ['type' => 'string',
                'description' => 'Full SCSS stylesheet compiled on every page regardless '
                    . 'of homepagemode; it could affect navbar, menu and footer but '
                    . 'MUST contain only scoped homepage-specific rules. '
                    . 'Put all shared menu/nav CSS in global scss and footer CSS in '
                    . 'footerscss. NOT automatically scoped; use explicit '
                    . 'homepage selectors for homepage-only rules. Use '
                    . '[data-bs-theme="dark"] .your-home to adapt dark colors, '
                    . 'cards, controls and accessible focus states, and prefer '
                    . '--learnlingo-home-* variables for light/dark surfaces. '
                    . 'Do not use a bare & at stylesheet root or remote CSS imports.'],
            'scss' => ['type' => 'string',
                'description' => 'Global SCSS compiled after Boost on all pages, '
                    . 'independent of homepagemode and footermode. Put shared '
                    . 'navigation, navbar, menu and header CSS here, not in '
                    . 'homepagescss; preserve existing rules and avoid duplicates. '
                    . 'MUST follow menu_guide selectors returned by learnlingo_get_homepage '
                    . 'or learnlingo_get_menu_guide for any navbar/menu CSS.'],
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
            'publiccoursesperpage_before' => $prepared['before']['publiccoursesperpage'],
            'publiccoursesperpage_after' => $prepared['after']['publiccoursesperpage'],
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
            'publiccoursesperpage' => $current['publiccoursesperpage'],
            'html_bytes' => strlen($current['html']),
            'homepagescss_bytes' => strlen($current['homepagescss']),
            'scss_bytes' => strlen($current['scss']),
            'state_hash' => $current['state_hash'],
            'changed_settings' => array_keys($prepared['settings']),
            'theme_cache_purged' => true,
            'cache_purge_scope' => 'all',
            'theme_revision' => theme_get_revision(),
        ];
    }
}
