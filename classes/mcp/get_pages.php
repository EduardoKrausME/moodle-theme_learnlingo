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
 * MCP READ tool to inspect LearnLingo custom pages and categories.
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
use moodle_url;
use theme_learnlingo\homepage_context;
use theme_learnlingo\pages;

/**
 * Return metadata for all pages and full content for the explicitly selected page.
 */
final class get_pages implements tool_interface {
    /** Return the unique read tool identifier. */
    public function get_name(): string {
        return 'learnlingo_get_pages';
    }

    /** Return the tool title. */
    public function get_title(): string {
        return 'List/read LearnLingo custom pages and categories';
    }

    /** Describe page editing instructions for the connected AI client. */
    public function get_description(): string {
        return 'List LearnLingo custom pages, flat categories (no subcategories), '
            . 'and page status, menu visibility, access and URLs. Pass pageid to fetch '
            . 'the complete editable HTML and SCSS of exactly one page; no HTML is '
            . 'returned in bulk. Returns state_hash per page/category for concurrency-safe '
            . 'learnlingo_update_page, learnlingo_update_page_category and learnlingo_delete_page. '
            . 'Pages use the same server-rendered Mustache context as homepagehtml, '
            . 'but work regardless of homepagemode. Page-specific SCSS is scoped '
            . 'to its own body selector. Global navbar/menu CSS belongs in the theme '
            . 'global scss, and footer CSS belongs in footerscss. '
            . 'A page can be at root or within ONE category; categories do not nest. '
            . 'Both ?page=slug and /index.php/page-slug are supported, '
            . 'with optional category segments. Only published pages appear publicly. '
            . 'Always read before writing; preview and get explicit confirmation.';
    }

    /** Return strict read input schema. */
    public function get_input_schema(): array {
        return [
            'type' => 'object',
            'properties' => [
                'pageid' => ['type' => 'integer', 'minimum' => 1,
                    'description' => 'Optional page ID to read full HTML, SCSS, and all settings.'],
                'categoryid' => ['type' => 'integer', 'minimum' => 1,
                    'description' => 'Optional category ID to inspect in addition to listing.'],
                'include_mustache_guide' => ['type' => 'boolean',
                    'description' => 'Include the homepage Mustache variable and privacy guide.'],
            ],
            'additionalProperties' => false,
        ];
    }

    /** Require the Moodle site configuration capability. */
    public function get_required_capability(): string {
        return 'moodle/site:config';
    }

    /**
     * Resolve context for access control.
     *
     * @param array $arguments Read arguments.
     * @return context Site context.
     */
    public function resolve_context(array $arguments): context {
        return context_system::instance();
    }

    /**
     * Read category and page descriptions without any side effects.
     *
     * @param array $arguments Read selection.
     * @param authenticated_identity $identity Authenticated administrator.
     * @return array Page index or detail.
     */
    public function execute(array $arguments, authenticated_identity $identity): array {
        global $DB;
        page_service::require_schema();
        $categories = [];
        foreach (pages::categories() as $category) {
            $categories[(int)$category->id] = $category;
        }
        $index = [];
        foreach (pages::all() as $page) {
            $category = $categories[(int)$page->categoryid] ?? null;
            $index[] = [
                'id' => (int)$page->id,
                'categoryid' => (int)$page->categoryid,
                'title' => $page->title,
                'slug' => $page->slug,
                'status' => (int)$page->status,
                'accesslevel' => (int)$page->accesslevel,
                'showinmenu' => (int)$page->showinmenu,
                'menutitle' => $page->menutitle,
                'menuorder' => (int)$page->menuorder,
                'sortorder' => (int)$page->sortorder,
                'url' => pages::url($page, $category)->out(false),
                'state_hash' => page_service::hash($page),
            ];
        }
        $result = [
            'pagefriendlyurls' => (int)get_config('theme_learnlingo', 'pagefriendlyurls'),
            'independent_of_homepage_mode' => true,
            'url_examples' => [
                '?page=empresa/sobre-nos',
                '/index.php/empresa/page-sobre-nos',
                '?page=sobre-nos',
                '/index.php/page-sobre-nos',
            ],
            'categories' => array_map(static fn($category): array => [
                'id' => (int)$category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'visible' => (int)$category->visible,
                'sortorder' => (int)$category->sortorder,
                'state_hash' => page_service::hash($category),
            ], array_values($categories)),
            'pages' => $index,
        ];
        if (!empty($arguments['pageid'])) {
            $page = page_service::record('page', (int)$arguments['pageid']);
            $result['selected_page'] = (array)$page;
            $result['selected_page']['state_hash'] = page_service::hash($page);
        }
        if (!empty($arguments['categoryid'])) {
            $category = page_service::record('category', (int)$arguments['categoryid']);
            $result['selected_category'] = (array)$category;
            $result['selected_category']['state_hash'] = page_service::hash($category);
        }
        if (!empty($arguments['include_mustache_guide'])) {
            $result['mustache_guide'] = homepage_context::schema();
        }
        return $result;
    }
}
