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
 * MCP READ tool for LearnLingo homepage settings.
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
 * Return the full homepage HTML and styling for editing.
 */
final class get_homepage implements tool_interface {
    /**
     * Return the MCP tool identifier.
     *
     * @return string Result.
     */
    public function get_name(): string {
        return 'learnlingo_get_homepage';
    }

    /**
     * Return the human-readable title.
     *
     * @return string Result.
     */
    public function get_title(): string {
        return 'Read LearnLingo homepage';
    }

    /**
     * Describe this tool.
     *
     * @return string Result.
     */
    public function get_description(): string {
        return 'Read the LearnLingo homepage mode, homepagehtml, full-width setting, '
            . 'homepagescss and scss. homepagehtml appears above course cards in '
            . 'learnlingo mode, replaces homepage content in html mode and is hidden '
            . 'in moodle mode. homepagescss is compiled on every page in every homepage '
            . 'mode, including shared navigation and footer elements; it is NOT '
            . 'homepage-scoped. scss is also global. Footer mode, footerhtml and '
            . 'footerscss are read separately with learnlingo_get_settings. '
            . 'Custom HTML supports automatic bindings without scripts: '
            . '<div data-learnlingo-loop="publiccourses" data-learnlingo-limit="4">'
            . '<a href="{{url}}">{{fullname}}</a></div>. '
            . 'Other loops: courses (enrolments and progress), popular '
            . '(30-day course views) and cohorts (current user only). '
            . 'Use <span data-learnlingo-text="user.firstname"></span> for '
            . 'personal fields; data-learnlingo-if="loggedin" or "!loggedin" '
            . 'for conditional sections. frontend.js reads the current session '
            . 'from /theme/learnlingo/home_api.php; no external JS or manually '
            . 'written API calls are needed. Read frontend_bindings in the '
            . 'tool response for complete field syntax and examples. '
            . 'Returns state_hash for concurrency-safe learnlingo_update_homepage.';
    }

    /**
     * Return the input JSON schema.
     *
     * @return array Result.
     */
    public function get_input_schema(): array {
        return [
            'type' => 'object',
            'properties' => (object)[],
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
        $snapshot = home_service::snapshot();
        // Documentation does not participate in the optimistic locking hash.
        $snapshot['frontend_bindings'] = [
            'api' => '/theme/learnlingo/home_api.php',
            'loggedin' => 'Boolean in every API response',
            'sections' => [
                'user' => 'Current user: firstname, lastname, fullname, email, customfields',
                'courses' => 'Enrolled courses with progress (0-100 or null); public fallback',
                'publiccourses' => 'Courses selected in Home settings, or all visible courses',
                'popular' => 'Visible courses ranked by views in the last 30 days',
                'cohorts' => 'Cohorts belonging to the current user, [] for visitors',
            ],
            'loop_attribute' => 'data-learnlingo-loop="courses|publiccourses|popular|cohorts"',
            'paging_attributes' => 'data-learnlingo-limit="4" data-learnlingo-offset="0"',
            'loop_fields' => '{{fullname}}, {{url}}, {{imageurl}}, {{progress}}, {{views}}, {{name}}',
            'user_fields' => 'data-learnlingo-text="user.firstname" or "user.customfields.shortname"',
            'conditionals' => 'data-learnlingo-if="loggedin", "!loggedin", "hasprogress", "imageurl"',
            'progress_bar' => 'data-learnlingo-progress="progress" sets width and aria-valuenow',
            'example_html' => '<section data-learnlingo-if="loggedin"><h2>Welcome '
                . '<span data-learnlingo-text="user.firstname"></span></h2></section>'
                . '<div data-learnlingo-loop="publiccourses" data-learnlingo-limit="4">'
                . '<article><img src="{{imageurl}}" alt="">'
                . '<a href="{{url}}">{{fullname}}</a></article></div>',
            'notes' => 'Ordinary HTML and data-learnlingo-* attributes; no inline scripts '
                . 'or nested loops. Dynamic values are text, links are safe HTTP(S) URLs. '
                . 'Only the authenticated user receives profile, cohort and progress data.',
        ];
        return $snapshot;
    }
}
