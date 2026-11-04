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
 * MCP WRITE tool for flat LearnLingo page categories.
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
 * Create and edit only top-level categories for custom HTML pages.
 */
final class update_page_category extends base_tool {
    /**
     * Return unique MCP tool name.
     *
     * @return mixed See declared method return type.
     */
    public function get_name(): string {
        return 'learnlingo_update_page_category';
    }

    /**
     * Return administrator-facing tool title.
     *
     * @return mixed See declared method return type.
     */
    public function get_title(): string {
        return 'Create or update LearnLingo page category';
    }

    /**
     * Document strict category semantics.
     *
     * @return mixed See declared method return type.
     */
    public function get_description(): string {
        return 'Create or update a top-level category used by LearnLingo custom pages. '
            . 'Categories NEVER have subcategories, only directly assigned pages. '
            . 'Use learnlingo_get_pages to inspect existing category slugs and hashes. '
            . 'For update supply id and expected_hash; only explicitly sent fields change. '
            . 'visible=0 hides this category and its pages from the public menu but '
            . 'does not unpublish or delete them. A category slug must be unique. '
            . 'Every mutation requires dry-run preview and explicit confirmation.';
    }

    /**
     * Provide a strict schema for category operations.
     *
     * @return mixed See declared method return type.
     */
    public function get_input_schema(): array {
        return $this->object_schema([
            'operation' => ['type' => 'string', 'enum' => ['create', 'update'],
                'description' => 'Create or update a flat category.'],
            'id' => ['type' => 'integer', 'minimum' => 1,
                'description' => 'Required for update.'],
            'expected_hash' => ['type' => 'string', 'pattern' => '^[0-9a-f]{64}$',
                'description' => 'Required for update, returned by learnlingo_get_pages.'],
            'name' => ['type' => 'string', 'description' => 'Category label.'],
            'slug' => ['type' => 'string', 'pattern' => '^[a-z0-9]+(?:-[a-z0-9]+)*$',
                'description' => 'Optional unique slug; derived from name on create.'],
            'description' => ['type' => 'string', 'description' => 'Category notes.'],
            'sortorder' => ['type' => 'integer', 'description' => 'Menu and administration order.'],
            'visible' => ['type' => 'integer', 'enum' => [0, 1],
                'description' => '0 hides category from public menu; direct page URLs continue working.'],
        ], ['operation']);
    }

    /**
     * Require Moodle site configuration capability.
     *
     * @return mixed See declared method return type.
     */
    public function get_required_capability(): string {
        return 'moodle/site:config';
    }

    /**
     * Resolve the system security context.
     *
     * @param array $arguments Submitted fields.
     * @return context Site context.
     */
    public function resolve_context(array $arguments): context {
        return context_system::instance();
    }

    /**
     * Always permit a preview without writing.
     *
     * @return mixed See declared method return type.
     */
    public function supports_dry_run(): bool {
        return true;
    }

    /**
     * Preview category creation or changes.
     *
     * @param array $arguments Proposed fields.
     * @param authenticated_identity $identity Authenticated administrator.
     * @return array Proposed metadata.
     */
    public function preview(array $arguments, authenticated_identity $identity): array {
        return page_service::preview(page_service::prepare('category', $arguments));
    }

    /**
     * Confirm and apply a category modification.
     *
     * @param array $arguments Approved fields.
     * @param authenticated_identity $identity Authenticated administrator.
     * @return array Saved result.
     */
    public function execute(array $arguments, authenticated_identity $identity): array {
        return page_service::apply(page_service::prepare('category', $arguments), $identity->userid);
    }
}
