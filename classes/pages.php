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
 * Custom pages storage, routing, and navigation.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use context_system;
use core\navigation\views\primary;
use moodle_exception;
use moodle_url;
use navigation_node;
use stdClass;

/**
 * Storage, routing and navigation for custom LearnLingo pages.
 *
 * Never coupled to homepagemode/homepagehtml.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class pages {
    /**
     * Return whether the schema is installed, including during upgrade.
     *
     * @return mixed See method declaration.
     */
    public static function ready(): bool {
        global $DB;
        return $DB->get_manager()->table_exists('theme_learnlingo_pages')
            && $DB->get_manager()->table_exists('theme_learnlingo_pagecats');
    }

    /**
     * URL path to category/page, without a leading slash.
     *
     * @return mixed See method declaration.
     */
    public static function path(stdClass $page, ?stdClass $category = null): string {
        return ($category ? $category->slug . '/' : '') . $page->slug;
    }

    /**
     * Public URL, with friendly PATH_INFO only if explicitly configured.
     *
     * @return mixed See method declaration.
     */
    public static function url(stdClass $page, ?stdClass $category = null): moodle_url {
        $path = self::path($page, $category);
        return new moodle_url('/', ['page' => $path]);
    }

    /**
     * A request must have GET ?page= or a terminal /page-{slug} segment.
     *
     * @return mixed See method declaration.
     */
    public static function request_path(): ?string {
        global $CFG;

        if (PHP_SAPI === 'cli' || (defined('CLI_SCRIPT') && CLI_SCRIPT) || empty($_SERVER['REQUEST_URI'])) {
            return null;
        }
        $requestpath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $basepath = (string)(parse_url($CFG->wwwroot, PHP_URL_PATH) ?: '');
        $relative = substr((string)$requestpath, strlen($basepath));
        if ($basepath !== '' && !str_starts_with((string)$requestpath, rtrim($basepath, '/') . '/')
            && (string)$requestpath !== $basepath) {
            return null;
        }
        $relative = '/' . ltrim($relative, '/');
        // Only intercept Moodle's real front-page entry point, not other PHP scripts.
        if (($relative === '/' || $relative === '/index.php') && array_key_exists('page', $_GET)) {
            return is_string($_GET['page']) ? (string)$_GET['page'] : '';
        }
        if (preg_match('~^/index\.php/(?:(?<category>[a-z0-9-]+)/)?page-(?<slug>[a-z0-9-]+)$~D',
            $relative, $matches)) {
            return (!empty($matches['category']) ? $matches['category'] . '/' : '') . $matches['slug'];
        }
        return null;
    }

    /**
     * Validate a category/page path, rejecting nesting and malformed slugs.
     *
     * @return mixed See method declaration.
     */
    public static function split_path(string $path): ?array {
        if (!preg_match('~^(?:([a-z0-9-]+)/)?([a-z0-9-]+)$~D', $path, $matches)) {
            return null;
        }
        return ['category' => $matches[1] ?? '', 'slug' => $matches[2]];
    }

    /**
     * Resolve an existing published page. Hidden categories remain directly accessible.
     *
     * @return mixed See method declaration.
     */
    public static function published(string $path): ?array {
        global $DB;
        $parts = self::split_path($path);
        if (!$parts || !self::ready()) {
            return null;
        }
        if ($parts['category'] !== '') {
            $category = $DB->get_record('theme_learnlingo_pagecats', ['slug' => $parts['category']]);
            if (!$category) {
                return null;
            }
            $categoryid = (int)$category->id;
        } else {
            $category = null;
            $categoryid = 0;
        }
        $page = $DB->get_record('theme_learnlingo_pages', [
            'categoryid' => $categoryid, 'slug' => $parts['slug'], 'status' => 1,
        ]);
        return $page ? [$page, $category] : null;
    }

    /**
     * Dispatch a custom page directly from the Moodle front-page request.
     *
     * @return mixed See method declaration.
     */
    public static function dispatch(string $path): void {
        global $PAGE, $OUTPUT;

        $found = self::published($path);
        // The after_config hook catches thrown exceptions. Render an actual 404
        // instead of throwing here, which would silently fall back to the Home.
        if (!$found) {
            http_response_code(404);
            $PAGE->set_url(new moodle_url('/', ['page' => $path]));
            $PAGE->set_context(context_system::instance());
            $PAGE->set_pagelayout('standard');
            $PAGE->set_pagetype('theme-learnlingo-custom-page');
            $PAGE->set_title(get_string('custompagesnotfound', 'theme_learnlingo'));
            $PAGE->set_heading(get_string('custompagesnotfound', 'theme_learnlingo'));
            echo $OUTPUT->header();
            echo $OUTPUT->notification(get_string('custompagesnotfound', 'theme_learnlingo'), 'notifyproblem');
            echo $OUTPUT->footer();
            exit;
        }
        [$page, $category] = $found;
        self::render($page, $category);
    }

    /**
     * Render an existing page; draft preview is allowed only by a separately
     * authenticated, site-configuration-capable administrative entry point.
     */
    public static function render(stdClass $page, ?stdClass $category = null, bool $preview = false): void {
        global $PAGE, $OUTPUT, $CFG;

        $pageurl = $preview
            ? new moodle_url('/theme/learnlingo/admin/preview.php', ['id' => $page->id])
            : self::url($page, $category);
        $PAGE->set_url($pageurl);
        $PAGE->set_context(context_system::instance());
        $PAGE->set_pagelayout('standard');
        $PAGE->set_pagetype('theme-learnlingo-custom-page');
        $PAGE->set_title(format_string($page->title));
        $PAGE->set_heading(format_string($page->title));
        if ((int)$page->accesslevel === 1 && !$preview) {
            require_login();
        }
        $PAGE->add_body_class('learnlingo-custom-page');
        $PAGE->add_body_class('learnlingo-page-' . (int)$page->id);
        if ((int)$page->fullwidth === 1) {
            $PAGE->add_body_class('learnlingo-html-home-fullwidth');
        }
        if ($preview) {
            $CFG->additionalhtmlhead = (string)($CFG->additionalhtmlhead ?? '') .
                '<meta name="robots" content="noindex,nofollow">';
        } else {
            $CFG->additionalhtmlhead = (string)($CFG->additionalhtmlhead ?? '') .
                '<link rel="canonical" href="' . s($pageurl->out(false)) . '">';
            if (trim((string)$page->metadescription) !== '') {
                $CFG->additionalhtmlhead .= '<meta name="description" content="' .
                    s($page->metadescription) . '">';
            }
        }
        echo $OUTPUT->header();
        if ($preview) {
            echo $OUTPUT->notification(get_string('custompagespreviewnotice', 'theme_learnlingo'),
                'notifymessage');
        }
        echo '<main class="learnlingo-custom-page-content" id="learnlingo-custom-content">';
        echo homepage_context::render((string)$page->content);
        echo '</main>';
        echo $OUTPUT->footer();
        exit;
    }

    /**
     * Renderable hierarchy: root pages and one category level, never nested categories.
     *
     * @return mixed See method declaration.
     */
    public static function tree_data(): array {
        $categories = [];
        $root = [];
        $haspages = false;
        $moveurl = static function(string $type, int $id, string $direction): string {
            return (new moodle_url('/theme/learnlingo/admin/reorder_pages.php', [
                'type' => $type, 'id' => $id, 'direction' => $direction, 'sesskey' => sesskey(),
            ]))->out(false);
        };
        $row = static function(stdClass $page, ?stdClass $category = null) use ($moveurl): array {
            return [
                'id' => (int)$page->id,
                'title' => $page->title,
                'status' => (bool)$page->status,
                'showinmenu' => (bool)($page->status && $page->showinmenu),
                'editurl' => (new moodle_url('/theme/learnlingo/admin/page.php',
                    ['id' => $page->id]))->out(false),
                'viewurl' => (int)$page->status === 1 ? self::url($page, $category)->out(false) :
                    (new moodle_url('/theme/learnlingo/admin/preview.php', ['id' => $page->id]))->out(false),
                'deleteurl' => (new moodle_url('/theme/learnlingo/admin/manage_pages.php',
                    ['deletepage' => $page->id]))->out(false),
                'upurl' => $moveurl('page', (int)$page->id, 'up'),
                'downurl' => $moveurl('page', (int)$page->id, 'down'),
            ];
        };
        foreach (self::categories() as $category) {
            $categories[$category->id] = [
                'id' => (int)$category->id,
                'name' => $category->name,
                'pages' => [],
                'newpageurl' => (new moodle_url('/theme/learnlingo/admin/page.php',
                    ['categoryid' => $category->id]))->out(false),
                'editurl' => (new moodle_url('/theme/learnlingo/admin/category.php',
                    ['id' => $category->id]))->out(false),
                'deleteurl' => (new moodle_url('/theme/learnlingo/admin/manage_pages.php',
                    ['deletecategory' => $category->id]))->out(false),
                'upurl' => $moveurl('category', (int)$category->id, 'up'),
                'downurl' => $moveurl('category', (int)$category->id, 'down'),
            ];
        }
        foreach (self::all() as $page) {
            $haspages = true;
            if ((int)$page->categoryid !== 0 && isset($categories[$page->categoryid])) {
                $category = (object)['slug' => self::category_slug($page->categoryid)];
                $categories[$page->categoryid]['pages'][] = $row($page, $category);
            } else {
                $root[] = $row($page);
            }
        }
        foreach ($categories as &$category) {
            $category['count'] = count($category['pages']);
        }
        unset($category);
        return [
            'ready' => self::ready(),
            'haspages' => $haspages || (bool)$categories,
            'rootpages' => $root,
            'categories' => array_values($categories),
            'newpageurl' => (new moodle_url('/theme/learnlingo/admin/page.php'))->out(false),
            'newcategoryurl' => (new moodle_url('/theme/learnlingo/admin/category.php'))->out(false),
        ];
    }

    /**
     * Category slug by its id; used only in administrative previews.
     *
     * @return mixed See method declaration.
     */
    private static function category_slug(int $id): string {
        global $DB;
        return (string)$DB->get_field('theme_learnlingo_pagecats', 'slug', ['id' => $id], MUST_EXIST);
    }

    /**
     * Existing categories, ordered for the editor.
     *
     * @return mixed See method declaration.
     */
    public static function categories(): array {
        global $DB;
        return self::ready() ? array_values($DB->get_records('theme_learnlingo_pagecats',
            null, 'sortorder ASC, id ASC')) : [];
    }

    /**
     * Existing pages, ordered for the editor.
     *
     * @return mixed See method declaration.
     */
    public static function all(): array {
        global $DB;
        return self::ready() ? array_values($DB->get_records('theme_learnlingo_pages',
            null, 'sortorder ASC, id ASC')) : [];
    }

    /**
     * Normalize ASCII slug; accents and punctuation are stripped.
     *
     * @return mixed See method declaration.
     */
    public static function slug(string $value): string {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $value = strtolower($ascii === false ? $value : $ascii);
        return trim((string)preg_replace('/[^a-z0-9]+/', '-', $value), '-');
    }

    /**
     * Validate names and collisions in a way shared by administration and MCP.
     *
     * @return mixed See method declaration.
     */
    public static function validate(stdClass $data, bool $category = false): void {
        global $DB;
        $data->slug = trim((string)$data->slug);
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $data->slug)) {
            throw new moodle_exception('custompagesinvalidslug', 'theme_learnlingo');
        }
        if (!$category) {
            $html = (string)($data->content ?? '');
            $scss = (string)($data->scss ?? '');
            if (str_contains($html, '<?') || str_contains($html, "\0") ||
                preg_match('~<\s*(script|iframe|object|embed|form)\b~i', $html)) {
                throw new moodle_exception('jsonvalidatehtml', 'theme_learnlingo');
            }
            if (str_contains($scss, '<?') || str_contains($scss, "\0") ||
                preg_match('~@import\s+(?:url\s*\(|[\x22\x27]https?://)~i', $scss)) {
                throw new moodle_exception('jsonvalidateremoteimport', 'theme_learnlingo');
            }
        }
        $table = $category ? 'theme_learnlingo_pagecats' : 'theme_learnlingo_pages';
        $conditions = $category ? ['slug' => $data->slug] :
            ['categoryid' => (int)$data->categoryid, 'slug' => $data->slug];
        $existing = $DB->get_record($table, $conditions, 'id');
        if ($existing && (int)$existing->id !== (int)($data->id ?? 0)) {
            throw new moodle_exception('custompagesduplicateslug', 'theme_learnlingo');
        }
        if (!$category && (int)$data->categoryid !== 0 &&
            !$DB->record_exists('theme_learnlingo_pagecats', ['id' => (int)$data->categoryid])) {
            throw new moodle_exception('custompagesinvalidcategory', 'theme_learnlingo');
        }
    }

    /**
     * Move a page within its category or reorder flat categories.
     * Menu and administrative page orders are kept in sync after a move.
     *
     * @param string $type page or category.
     * @param int $id Record identifier.
     * @param string $direction up or down.
     * @return bool Whether the record moved.
     */
    public static function move(string $type, int $id, string $direction): bool {
        global $DB;
        if (!in_array($type, ['page', 'category'], true) ||
            !in_array($direction, ['up', 'down'], true) || $id <= 0) {
            throw new moodle_exception('invalidrequest');
        }
        $table = $type === 'page' ? 'theme_learnlingo_pages' : 'theme_learnlingo_pagecats';
        $record = $DB->get_record($table, ['id' => $id], '*', MUST_EXIST);
        $conditions = $type === 'page' ? ['categoryid' => (int)$record->categoryid] : [];
        $siblings = array_values($DB->get_records($table, $conditions, 'sortorder ASC, id ASC', 'id,sortorder'));
        $index = null;
        foreach ($siblings as $position => $sibling) {
            if ((int)$sibling->id === $id) {
                $index = $position;
                break;
            }
        }
        if ($index === null) {
            throw new moodle_exception('invalidrequest');
        }
        $next = $index + ($direction === 'up' ? -1 : 1);
        if ($next < 0 || $next >= count($siblings)) {
            return false;
        }
        [$siblings[$index], $siblings[$next]] = [$siblings[$next], $siblings[$index]];
        $transaction = $DB->start_delegated_transaction();
        foreach ($siblings as $position => $sibling) {
            $update = (object)['id' => (int)$sibling->id, 'sortorder' => $position];
            if ($type === 'page') {
                $update->menuorder = $position;
            }
            $DB->update_record($table, $update);
        }
        $transaction->allow_commit();
        theme_reset_all_caches();
        return true;
    }

    /**
     * Save one page. All callers must check moodle/site:config first.
     *
     * @return mixed See method declaration.
     */
    public static function save_page(stdClass $data, ?int $actorid = null): int {
        global $DB, $USER;
        self::validate($data);
        $now = time();
        $data->timemodified = $now;
        $data->usermodified = $actorid ?? (int)$USER->id;
        if (!empty($data->id)) {
            $DB->update_record('theme_learnlingo_pages', $data);
            $id = (int)$data->id;
        } else {
            unset($data->id);
            if (!isset($data->sortorder)) {
                $data->sortorder = (int)$DB->get_field_sql(
                    'SELECT COALESCE(MAX(sortorder), -1) + 1 FROM {theme_learnlingo_pages} WHERE categoryid = ?',
                    [(int)$data->categoryid]
                );
            }
            if (!isset($data->menuorder)) {
                $data->menuorder = (int)$data->sortorder;
            }
            $data->timecreated = $now;
            $data->usercreated = $actorid ?? (int)$USER->id;
            $id = (int)$DB->insert_record('theme_learnlingo_pages', $data);
        }
        theme_reset_all_caches();
        return $id;
    }

    /**
     * Save one category; categories cannot nest.
     *
     * @return mixed See method declaration.
     */
    public static function save_category(stdClass $data): int {
        global $DB;
        self::validate($data, true);
        $now = time();
        $data->timemodified = $now;
        if (!empty($data->id)) {
            $DB->update_record('theme_learnlingo_pagecats', $data);
            $id = (int)$data->id;
        } else {
            unset($data->id);
            if (!isset($data->sortorder)) {
                $data->sortorder = (int)$DB->get_field_sql(
                    'SELECT COALESCE(MAX(sortorder), -1) + 1 FROM {theme_learnlingo_pagecats}'
                );
            }
            $data->timecreated = $now;
            $id = (int)$DB->insert_record('theme_learnlingo_pagecats', $data);
        }
        theme_reset_all_caches();
        return $id;
    }

    /**
     * Navigation items shown on desktop and mobile. The menu contains only
     * published, permitted pages; an unpublished page never leaks into menus.
     */
    public static function navigation(): array {
        global $DB;
        if (!self::ready()) {
            return [];
        }
        $loggedin = isloggedin() && !isguestuser();
        $where = 'p.status = 1 AND p.showinmenu = 1 AND (p.accesslevel = 0';
        $where .= $loggedin ? ' OR p.accesslevel = 1)' : ')';
        $records = $DB->get_records_sql('SELECT p.*, c.name AS catname, c.slug AS catslug,
                    c.visible AS catvisible, c.sortorder AS catsort
                FROM {theme_learnlingo_pages} p
                LEFT JOIN {theme_learnlingo_pagecats} c ON c.id = p.categoryid
                WHERE ' . $where . '
                ORDER BY p.menuorder ASC, p.title ASC');
        $groups = [];
        $root = [];
        foreach ($records as $page) {
            $category = null;
            if ((int)$page->categoryid !== 0) {
                if (empty($page->catvisible)) {
                    continue;
                }
                $category = (object)['slug' => $page->catslug];
            }
            $entry = [
                'id' => (int)$page->id,
                'text' => s($page->menutitle ?: $page->title),
                'url' => self::url($page, $category)->out(false),
                'isactive' => false,
                'haschildren' => false,
            ];
            if (!$category) {
                $root[] = $entry;
            } else {
                $groups[$page->categoryid]['text'] = s($page->catname);
                $groups[$page->categoryid]['sort'] = 700000 + (int)$page->categoryid;
                $groups[$page->categoryid]['catsort'] = (int)$page->catsort;
                $groups[$page->categoryid]['children'][] = $entry;
                $groups[$page->categoryid]['haschildren'] = true;
            }
        }
        uasort($groups, static fn($a, $b) => $a['catsort'] <=> $b['catsort']);
        return array_merge($root, array_values($groups));
    }
    /**
     * Append visible pages to Moodle's custom menu on older releases lacking
     * the primary_extend hook. Never persist this request-only menu in config.
     *
     * @param string $current Existing administrator-defined custom menu.
     * @return string Menu with LearnLingo pages added, without duplicate URLs.
     */
    public static function append_custom_menu(string $current): string {
        $lines = preg_split('/\r\n|\r|\n/', $current);
        $knownurls = [];
        foreach ($lines as $line) {
            $parts = explode('|', trim($line), 3);
            if (isset($parts[1]) && trim($parts[1]) !== '') {
                $knownurls[html_entity_decode(trim($parts[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8')] = true;
            }
        }

        $added = [];
        foreach (self::navigation() as $item) {
            if (!empty($item['haschildren'])) {
                $children = [];
                foreach ($item['children'] as $child) {
                    $url = $child['url'];
                    if (isset($knownurls[$url])) {
                        continue;
                    }
                    $children[] = '-' . self::custom_menu_label($child['text']) . '|' . $url;
                    $knownurls[$url] = true;
                }
                if ($children) {
                    // A category is an unlinked parent in the native custom-menu syntax.
                    $added[] = self::custom_menu_label($item['text']);
                    array_push($added, ...$children);
                }
            } else {
                $url = $item['url'];
                if (!isset($knownurls[$url])) {
                    $added[] = self::custom_menu_label($item['text']) . '|' . $url;
                    $knownurls[$url] = true;
                }
            }
        }

        if (!$added) {
            return $current;
        }
        return rtrim($current, "\r\n") . ($current === '' ? '' : "\n") . implode("\n", $added);
    }

    /**
     * Strip custom-menu syntax characters from labels, including stored HTML entities.
     *
     * @param string $text Already escaped menu label from navigation().
     * @return string Plain text safe for Moodle's pipe-delimited menu definition.
     */
    private static function custom_menu_label(string $text): string {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(str_replace(['|', "\r", "\n"], [' ', ' ', ' '], $text));
    }

    /**
     * Add published LearnLingo pages to Moodle's native primary navigation.
     *
     * The core primary_extend hook calls this before navigation is rendered, so
     * Moodle handles the More menu, responsive layout and active items itself.
     *
     * @param primary $primaryview Moodle primary navigation.
     */
    public static function extend_primary_navigation(primary $primaryview): void {
        foreach (self::navigation() as $item) {
            if (!empty($item['haschildren'])) {
                $key = 'learnlingo-category-' . (int)($item['sort'] ?? 0);
                $category = $primaryview->get($key, navigation_node::TYPE_CUSTOM);
                if (!$category) {
                    $category = $primaryview->add($item['text'], null,
                        navigation_node::TYPE_CUSTOM, null, $key);
                    $category->showchildreninsubmenu = true;
                }
                foreach ($item['children'] as $child) {
                    $childkey = 'learnlingo-page-' . (int)$child['id'];
                    if (!$category->get($childkey, navigation_node::TYPE_CUSTOM)) {
                        $category->add($child['text'], new moodle_url($child['url']),
                            navigation_node::TYPE_CUSTOM, null, $childkey);
                    }
                }
            } else {
                $key = 'learnlingo-page-' . (int)$item['id'];
                if (!$primaryview->get($key, navigation_node::TYPE_CUSTOM)) {
                    $primaryview->add($item['text'], new moodle_url($item['url']),
                        navigation_node::TYPE_CUSTOM, null, $key);
                }
            }
        }
    }

}
