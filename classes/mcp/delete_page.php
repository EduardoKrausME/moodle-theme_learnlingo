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
 * MCP WRITE tool for confirmed deletion of pages and empty categories.
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
 * Delete one page or empty category after an explicitly approved preview.
 */
final class delete_page extends base_tool {
    /** Return tool identifier. */
    public function get_name(): string {
        return 'learnlingo_delete_page';
    }

    /** Return tool title. */
    public function get_title(): string {
        return 'Delete LearnLingo page or empty page category';
    }

    /** Describe the destructive operation and safeguards. */
    public function get_description(): string {
        return 'Permanently remove ONE custom HTML page or ONE EMPTY page category. '
            . 'Required type=page or category, id and exact expected_hash from '
            . 'learnlingo_get_pages. Nonempty categories are NEVER cascade-deleted; '
            . 'first move their pages to another category or the root. '
            . 'Read the current resource, present a destructive dry-run preview, '
            . 'request separate explicit confirmation and then delete. '
            . 'No content is deleted during preview.';
    }

    /** Describe required deletion parameters. */
    public function get_input_schema(): array {
        return $this->object_schema([
            'type' => ['type' => 'string', 'enum' => ['page', 'category'],
                'description' => 'Select a page or a category.'],
            'id' => ['type' => 'integer', 'minimum' => 1,
                'description' => 'Page or category ID.'],
            'expected_hash' => ['type' => 'string', 'pattern' => '^[0-9a-f]{64}$',
                'description' => 'Current state_hash from learnlingo_get_pages.'],
        ], ['type', 'id', 'expected_hash']);
    }

    /** Require administrator-level Moodle access. */
    public function get_required_capability(): string {
        return 'moodle/site:config';
    }

    /**
     * Resolve site context for authorization.
     *
     * @param array $arguments Deletion identity.
     * @return context System context.
     */
    public function resolve_context(array $arguments): context {
        return context_system::instance();
    }

    /** Always preview before deletion. */
    public function supports_dry_run(): bool {
        return true;
    }

    /** Mark this tool as destructive. */
    public function is_destructive(): bool {
        return true;
    }

    /**
     * Return deletion target for explicit approval.
     *
     * @param array $arguments Target and hash.
     * @param authenticated_identity $identity Authenticated administrator.
     * @return array Deletion metadata.
     */
    public function preview(array $arguments, authenticated_identity $identity): array {
        $prepared = page_service::prepare_delete($arguments);
        unset($prepared['record']);
        return $prepared;
    }

    /**
     * Revalidate the hash and delete the approved record.
     *
     * @param array $arguments Target and hash.
     * @param authenticated_identity $identity Authenticated administrator.
     * @return array Deletion result.
     */
    public function execute(array $arguments, authenticated_identity $identity): array {
        return page_service::apply_delete(page_service::prepare_delete($arguments));
    }
}
