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
            . 'mode, so it is NOT automatically homepage-scoped; reserve it for '
            . 'rules scoped to homepage markup. The returned menu_guide contains the '
            . 'MANDATORY complete guest/logged-in navbar selector structure and SCSS '
            . 'outline for any menu design or CSS request; also available via '
            . 'learnlingo_get_menu_guide. Menu, navbar and shared header '
            . 'styles MUST go in global scss instead. Footer mode, footerhtml, '
            . 'footerscss, images.logo and images.favicon MUST be inspected with '
            . 'learnlingo_get_settings when designing a complete homepage. '
            . 'Plan coherent home, menu, footer, logo and favicon updates; prefer '
            . 'a transparent PNG logo with artwork cropped tightly to image bounds, '
            . 'NEVER with blank/white/transparent padding around the artwork. '
            . 'Request approval for each MCP write and verify after publication. '
            . 'IMPORTANT FOR CHATGPT/CLAUDE: ONLY homepagehtml is rendered as '
            . 'Mustache by PHP. On every homepage design/edit, read the returned '
            . 'mustache.instructions, mustache.fields and mustache.examples and '
            . 'use the live placeholders instead of inventing/hardcoding users, '
            . 'courses, images or progress. Use {{#isloggedin}} and '
            . '{{^isloggedin}} for authentication conditions, {{user.firstname}} '
            . 'for the own visitor, {{#courses}} for the public catalogue, '
            . '{{#mycourses}} for own enrollments and completion, and '
            . '{{#cohorts}} for own groups. Course items include url, '
            . 'fullname, imageurl, hasimage, categoryname, hasprogress and progress. '
            . 'Only {{#mycourses}} includes per-user progress; wrap percentage '
            . 'in {{#hasprogress}}. Guest data is anonymous, never personalized. '
            . 'publiccourses selects visible course IDs; empty means all visible '
            . 'accessible courses in Moodle sort order. publiccoursesperpage '
            . '(1-12, default 4) limits ONLY guests in {{#courses}}, never '
            . 'authenticated students or {{#mycourses}}. Use the guest-only '
            . '{{coursepagination.coursecolumns}} to adapt the number of grid '
            . 'columns to courses actually available. Use '
            . '{{#coursepagination.coursepages}} for page navigation. '
            . 'Never invent courses to fill a grid. DARK MODE IS REQUIRED for '
            . 'custom HTML homes: use [data-bs-theme="dark"] .your-home '
            . 'overrides in homepagescss, and prefer --learnlingo-home-canvas, '
            . '--learnlingo-home-surface, --learnlingo-home-ink and '
            . '--learnlingo-home-border CSS tokens. Never invent {{isdark}} '
            . 'or depend on a server-side dark flag, JavaScript or reload. '
            . 'Shared MUC cache (300s) '
            . 'stores ONLY anonymous public catalogue data and invalidates on '
            . 'course/category events and settings changes, not user data or '
            . 'rendered HTML. No scripts or Mustache changes outside homepagehtml. '
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
        return home_service::snapshot();
    }
}
