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
 * MCP WRITE tool for custom LearnLingo pages.
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
 * Create and update page HTML and settings after an explicit MCP preview.
 */
final class update_page extends base_tool {
    /**
     * Return MCP tool name.
     *
     * @return mixed See declared method return type.
     */
    public function get_name(): string {
        return 'learnlingo_update_page';
    }

    /**
     * Return human-readable tool title.
     *
     * @return mixed See declared method return type.
     */
    public function get_title(): string {
        return 'Create or update a LearnLingo custom page';
    }

    /**
     * Describe page editing semantics and confirmation requirements.
     *
     * @return mixed See declared method return type.
     */
    public function get_description(): string {
        return 'Create or update one custom HTML page with optional page-specific SCSS, '
            . 'categoryid (0 = root), status (0 = draft, 1 = published), '
            . 'accesslevel (0 = public, 1 = logged-in only), showinmenu, '
            . 'menutitle, menuorder, sortorder, fullwidth, description and metadescription. '
            . 'First use learnlingo_get_pages to inspect pages/categories, '
            . 'HTML, Mustache instructions and state_hash. For update, send id and '
            . 'the expected_hash of that exact page; absent fields retain their values. '
            . 'For create, provide title and HTML; slug defaults to a generated title slug. '
            . 'Categories are flat, never nested. Independent of homepagemode. '
            . 'HTML accepts the same safe server-rendered Mustache variables as the Home '
            . 'but never PHP scripts, JS or unsafe embedded forms. Page SCSS is individually '
            . 'scoped; shared navbar CSS belongs in global scss. '
            . 'Always review dry-run preview, request explicit approval, then confirm '
            . 'the write. An intervening edit invalidates expected_hash.';
    }

    /**
     * Describe supported page fields with a strict JSON schema.
     *
     * @return mixed See declared method return type.
     */
    public function get_input_schema(): array {
        return $this->object_schema([
            'operation' => ['type' => 'string', 'enum' => ['create', 'update'],
                'description' => 'Create a new page or update selected fields on an existing page.'],
            'id' => ['type' => 'integer', 'minimum' => 1,
                'description' => 'Required for update; do not send for create.'],
            'expected_hash' => ['type' => 'string', 'pattern' => '^[0-9a-f]{64}$',
                'description' => 'state_hash from learnlingo_get_pages; required for update.'],
            'title' => ['type' => 'string', 'description' => 'Public page title.'],
            'slug' => ['type' => 'string', 'pattern' => '^[a-z0-9]+(?:-[a-z0-9]+)*$',
                'description' => 'Optional lowercase URL slug; title is slugified on create if omitted.'],
            'categoryid' => ['type' => 'integer', 'minimum' => 0,
                'description' => '0 = root; otherwise the ID of one category.'],
            'content' => ['type' => 'string', 'description' => 'Complete HTML with Moodle Mustache placeholders.'],
            'scss' => ['type' => 'string', 'description' => 'Page-local scoped SCSS; global nav belongs in scss.'],
            'description' => ['type' => 'string', 'description' => 'Internal descriptive notes.'],
            'metadescription' => ['type' => 'string', 'description' => 'SEO meta description.'],
            'status' => ['type' => 'integer', 'enum' => [0, 1],
                'description' => '0 = draft, 1 = published; draft cannot be visited by the public.'],
            'accesslevel' => ['type' => 'integer', 'enum' => [0, 1],
                'description' => '0 = public, 1 = authenticated users only.'],
            'fullwidth' => ['type' => 'integer', 'enum' => [0, 1],
                'description' => 'Page content width 1 = full width.'],
            'showinmenu' => ['type' => 'integer', 'enum' => [0, 1],
                'description' => 'Whether to show a published page in the main navigation.'],
            'menutitle' => ['type' => 'string', 'description' => 'Optional navigation label.'],
            'menuorder' => ['type' => 'integer', 'description' => 'Menu item display order.'],
            'sortorder' => ['type' => 'integer', 'description' => 'Administration tree order.'],
        ], ['operation']);
    }

    /**
     * Require configuration permissions for all page writes.
     *
     * @return mixed See declared method return type.
     */
    public function get_required_capability(): string {
        return 'moodle/site:config';
    }

    /**
     * Resolve site-level security scope.
     *
     * @param array $arguments Submitted fields.
     * @return context System context.
     */
    public function resolve_context(array $arguments): context {
        return context_system::instance();
    }

    /**
     * Enable safe dry-run previews before every page write.
     *
     * @return mixed See declared method return type.
     */
    public function supports_dry_run(): bool {
        return true;
    }

    /**
     * Summarize exactly what would change without echoing HTML content.
     *
     * @param array $arguments Requested edits.
     * @param authenticated_identity $identity Authenticated administrator.
     * @return array Proposed changes and confirmation flag.
     */
    public function preview(array $arguments, authenticated_identity $identity): array {
        return page_service::preview(page_service::prepare('page', $arguments));
    }

    /**
     * Revalidate and save only approved fields.
     *
     * @param array $arguments Approved edits.
     * @param authenticated_identity $identity Authenticated administrator.
     * @return array Saved identity, hash and theme revision.
     */
    public function execute(array $arguments, authenticated_identity $identity): array {
        return page_service::apply(page_service::prepare('page', $arguments), $identity->userid);
    }
}
