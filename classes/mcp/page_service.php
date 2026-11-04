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
 * MCP page and category editing service with optimistic concurrency checking.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo\mcp;

use local_mcp\exception\api_exception;
use stdClass;
use theme_learnlingo\pages;

/**
 * Prepare and apply independent, confirmed page and category operations.
 *
 * No operations change homepagemode, homepagehtml or the site's global menu.
 */
final class page_service {
    /** Fields which MCP may change on existing pages. */
    public const PAGE_FIELDS = [
        'title', 'slug', 'categoryid', 'content', 'scss', 'description',
        'metadescription', 'status', 'accesslevel', 'fullwidth', 'showinmenu',
        'menutitle', 'menuorder', 'sortorder',
    ];

    /** Fields which MCP may change on existing categories. */
    public const CATEGORY_FIELDS = ['name', 'slug', 'description', 'sortorder', 'visible'];

    /** Compute a hash of the exact persisted record to detect concurrent changes. */
    public static function hash(stdClass $record): string {
        return hash('sha256', json_encode((array)$record,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    /** Check the database was upgraded before exposing/editing custom pages. */
    public static function require_schema(): void {
        if (!pages::ready()) {
            throw new api_exception('theme_upgrade_required', 409,
                'Upgrade the LearnLingo database schema before managing custom pages.');
        }
    }

    /** Read a saved page or category by numeric ID. */
    public static function record(string $type, int $id): stdClass {
        global $DB;
        self::require_schema();
        if ($id <= 0) {
            throw new api_exception('invalid_id', 400, 'Provide a positive record id.');
        }
        if ($type !== 'page' && $type !== 'category') {
            throw new api_exception('invalid_type', 400, 'Record type must be page or category.');
        }
        $table = $type === 'page' ? 'theme_learnlingo_pages' : 'theme_learnlingo_pagecats';
        $record = $DB->get_record($table, ['id' => $id]);
        if (!$record) {
            throw new api_exception('record_not_found', 404, 'Requested page or category does not exist.');
        }
        return $record;
    }

    /** Validate the expected SHA-256 hash of the saved record. */
    public static function check_hash(stdClass $record, string $expected): void {
        if (!preg_match('/^[0-9a-f]{64}$/D', $expected)) {
            throw new api_exception('expected_hash_required', 400,
                'Read learnlingo_get_pages first and supply the 64-character state_hash.');
        }
        if (!hash_equals(self::hash($record), $expected)) {
            throw new api_exception('stale_page', 409,
                'Page or category changed since it was read. Read again and prepare a new preview.');
        }
    }

    /** Sanitize and prepare a create or update, without writing anything. */
    public static function prepare(string $type, array $arguments): array {
        self::require_schema();
        $operation = (string)($arguments['operation'] ?? '');
        if (!in_array($operation, ['create', 'update'], true)) {
            throw new api_exception('invalid_operation', 400,
                'Operation must be create or update.');
        }
        if ($type !== 'page' && $type !== 'category') {
            throw new api_exception('invalid_type', 400, 'Unknown editing type.');
        }
        $fields = $type === 'page' ? self::PAGE_FIELDS : self::CATEGORY_FIELDS;
        $id = (int)($arguments['id'] ?? 0);
        $before = null;
        if ($operation === 'update') {
            $before = self::record($type, $id);
            self::check_hash($before, (string)($arguments['expected_hash'] ?? ''));
            $after = clone $before;
        } else {
            if ($id !== 0) {
                throw new api_exception('invalid_id', 400, 'New pages/categories cannot have a saved id.');
            }
            $after = $type === 'page'
                ? (object)[
                    'categoryid' => 0, 'title' => '', 'slug' => '', 'content' => '',
                    'scss' => '', 'description' => '', 'metadescription' => '',
                    'status' => 0, 'accesslevel' => 0, 'fullwidth' => 1,
                    'showinmenu' => 0, 'menutitle' => '', 'menuorder' => 0,
                    'sortorder' => 0,
                ]
                : (object)[
                    'name' => '', 'slug' => '', 'description' => '', 'sortorder' => 0, 'visible' => 1,
                ];
        }

        $changed = [];
        foreach ($fields as $name) {
            if (!array_key_exists($name, $arguments)) {
                continue;
            }
            $value = $arguments[$name];
            if (in_array($name, ['categoryid', 'status', 'accesslevel', 'fullwidth',
                'showinmenu', 'menuorder', 'sortorder', 'visible'], true)) {
                if (!is_int($value)) {
                    throw new api_exception('invalid_field', 400, $name . ' must be an integer.');
                }
                if (in_array($name, ['status', 'accesslevel', 'fullwidth', 'showinmenu', 'visible'], true)
                    && !in_array($value, [0, 1], true)) {
                    throw new api_exception('invalid_field', 400, $name . ' must be 0 or 1.');
                }
                if ($name === 'categoryid' && $value < 0) {
                    throw new api_exception('invalid_field', 400, 'categoryid cannot be negative.');
                }
            } else if (!is_string($value)) {
                throw new api_exception('invalid_field', 400, $name . ' must be a string.');
            } else if (in_array($name, ['slug', 'name', 'title', 'menutitle'], true) &&
                strlen($value) > 255) {
                throw new api_exception('invalid_field', 400, $name . ' exceeds 255 bytes.');
            }
            if (($after->$name ?? null) !== $value) {
                $after->$name = $value;
                $changed[] = $name;
            }
        }
        if ($operation === 'create' && !isset($arguments['slug'])) {
            $after->slug = pages::slug($type === 'page' ? $after->title : $after->name);
            $changed[] = 'slug';
        }
        if ($operation === 'create' && !$changed) {
            throw new api_exception('no_changes', 400, 'Provide fields for the new record.');
        }
        if ($operation === 'update' && !$changed) {
            throw new api_exception('no_changes', 400, 'Provide at least one changed field.');
        }
        $label = trim((string)($type === 'page' ? $after->title : $after->name));
        if ($label === '') {
            throw new api_exception('invalid_field', 400, 'Title or category name cannot be empty.');
        }
        if ($type === 'page' && trim((string)$after->content) === '') {
            throw new api_exception('invalid_field', 400, 'Page HTML cannot be empty.');
        }
        if ($type === 'page' && strlen((string)$after->metadescription) > 2000) {
            throw new api_exception('invalid_field', 400, 'Meta description is too long.');
        }
        pages::validate($after, $type === 'category');

        return [
            'type' => $type,
            'operation' => $operation,
            'id' => $id,
            'before' => $before,
            'after' => $after,
            'changed_fields' => array_values(array_unique($changed)),
        ];
    }

    /** Produce a non-executing preview without leaking long HTML/SCSS. */
    public static function preview(array $prepared): array {
        $after = $prepared['after'];
        return [
            'type' => $prepared['type'],
            'operation' => $prepared['operation'],
            'id' => $prepared['id'] ?: null,
            'name' => $prepared['type'] === 'page' ? $after->title : $after->name,
            'slug' => $after->slug,
            'categoryid' => $prepared['type'] === 'page' ? $after->categoryid : null,
            'changed_fields' => $prepared['changed_fields'],
            'published' => $prepared['type'] === 'page' ? (bool)$after->status : null,
            'show_in_menu' => $prepared['type'] === 'page' ? (bool)$after->showinmenu : null,
            'html_bytes' => $prepared['type'] === 'page' ? strlen($after->content) : null,
            'scss_bytes' => $prepared['type'] === 'page' ? strlen($after->scss) : null,
            'requires_confirmation' => true,
        ];
    }

    /** Apply a freshly revalidated operation, returning identifiers and revision. */
    public static function apply(array $prepared, int $userid): array {
        global $DB;
        $id = $prepared['type'] === 'page'
            ? pages::save_page($prepared['after'], $userid)
            : pages::save_category($prepared['after']);
        $record = self::record($prepared['type'], $id);
        return [
            'success' => true,
            'type' => $prepared['type'],
            'operation' => $prepared['operation'],
            'id' => $id,
            'state_hash' => self::hash($record),
            'changed_fields' => $prepared['changed_fields'],
            'theme_revision' => theme_get_revision(),
        ];
    }

    /** Prepare deletion; category deletion requires moving its pages first. */
    public static function prepare_delete(array $arguments): array {
        global $DB;
        $type = (string)($arguments['type'] ?? '');
        $id = (int)($arguments['id'] ?? 0);
        $record = self::record($type, $id);
        self::check_hash($record, (string)($arguments['expected_hash'] ?? ''));
        $pagecount = 0;
        if ($type === 'category') {
            $pagecount = $DB->count_records('theme_learnlingo_pages', ['categoryid' => $id]);
            if ($pagecount > 0) {
                throw new api_exception('category_not_empty', 409,
                    'Move all pages to another category or the root before deleting this category.');
            }
        }
        return [
            'type' => $type,
            'id' => $id,
            'record' => $record,
            'name' => $type === 'page' ? $record->title : $record->name,
            'requires_confirmation' => true,
            'is_destructive' => true,
        ];
    }

    /** Delete only the exact record and revalidate the category before deletion. */
    public static function apply_delete(array $prepared): array {
        global $DB;
        $type = $prepared['type'];
        $id = (int)$prepared['id'];
        if ($type === 'category' && $DB->record_exists('theme_learnlingo_pages', ['categoryid' => $id])) {
            throw new api_exception('category_not_empty', 409,
                'Move all pages out of this category before deleting it.');
        }
        $DB->delete_records($type === 'page' ? 'theme_learnlingo_pages' :
            'theme_learnlingo_pagecats', ['id' => $id]);
        theme_reset_all_caches();
        return ['success' => true, 'type' => $type, 'id' => $id, 'deleted' => true,
            'theme_revision' => theme_get_revision()];
    }
}
