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
 * Tests for flat categories and custom pages.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use advanced_testcase;
use context_system;
use core\navigation\views\primary;
use moodle_exception;
use moodle_url;
use navigation_node;
use stdClass;

/**
 * Test category-scoped slugs, route resolution, and menu visibility.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \theme_learnlingo\pages
 */
final class pages_test extends advanced_testcase {
    public function test_only_root_or_single_category_paths_are_valid(): void {
        $this->assertSame(['category' => '', 'slug' => 'sobre-nos'], pages::split_path('sobre-nos'));
        $this->assertSame(['category' => 'empresa', 'slug' => 'sobre-nos'],
            pages::split_path('empresa/sobre-nos'));
        $this->assertNull(pages::split_path('empresa/equipe/sobre-nos'));
        $this->assertNull(pages::split_path('empresa/page-sobre-nos/extra'));
        $this->assertNull(pages::split_path('empresa/sobre_nos'));
        $this->assertNull(pages::split_path('/empresa/sobre-nos'));
    }

    public function test_same_slug_allowed_in_distinct_categories_but_not_in_same_category(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        $cat1 = $DB->insert_record('theme_learnlingo_pagecats', (object)[
            'name' => 'Empresa', 'slug' => 'empresa', 'sortorder' => 0,
            'visible' => 1, 'timecreated' => $now, 'timemodified' => $now,
        ]);
        $cat2 = $DB->insert_record('theme_learnlingo_pagecats', (object)[
            'name' => 'Institucional', 'slug' => 'institucional', 'sortorder' => 1,
            'visible' => 1, 'timecreated' => $now, 'timemodified' => $now,
        ]);
        $page = $this->page_record($cat1, 'sobre-nos');
        $id = $DB->insert_record('theme_learnlingo_pages', $page);
        $this->assertCount(2, pages::published('empresa/sobre-nos'));
        $this->assertNull(pages::published('institucional/sobre-nos'));
        $this->assertNull(pages::published('sobre-nos'));
        pages::validate((object)['id' => 0, 'slug' => 'sobre-nos', 'categoryid' => $cat2,
            'content' => '<h1>Institutional</h1>', 'scss' => '']);
        $this->expectException(moodle_exception::class);
        pages::validate((object)['id' => 0, 'slug' => 'sobre-nos', 'categoryid' => $cat1,
            'content' => '<h1>Collision</h1>', 'scss' => '']);
    }

    public function test_drafts_are_never_public_and_urls_ignore_homepage_mode(): void {
        global $DB;
        $this->resetAfterTest(true);
        $page = $this->page_record(0, 'contato');
        $page->status = 0;
        $id = $DB->insert_record('theme_learnlingo_pages', $page);
        $this->assertNull(pages::published('contato'));
        $page->id = $id;
        set_config('homepagemode', 'moodle', 'theme_learnlingo');
        set_config('pagefriendlyurls', 0, 'theme_learnlingo');
        $this->assertSame('contato', pages::url($page)->get_param('page'));
        set_config('pagefriendlyurls', 1, 'theme_learnlingo');
        $this->assertStringContainsString('/index.php/page-contato', pages::url($page)->out(false));
        $page->status = 1;
        $DB->update_record('theme_learnlingo_pages', $page);
        $this->assertNotNull(pages::published('contato'));
    }

    public function test_menu_never_exposes_drafts(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setUser(0);
        $public = $this->page_record(0, 'publica');
        $public->showinmenu = 1;
        $public->status = 1;
        $DB->insert_record('theme_learnlingo_pages', $public);
        $draft = $this->page_record(0, 'rascunho');
        $draft->showinmenu = 1;
        $draft->status = 0;
        $DB->insert_record('theme_learnlingo_pages', $draft);
        $menu = pages::navigation();
        $this->assertCount(1, $menu);
        $this->assertSame('Pública', $menu[0]['text']);
    }

    public function test_pages_use_native_primary_navigation_without_changing_custommenuitems(): void {
        global $CFG, $DB, $PAGE;
        $this->resetAfterTest(true);
        $this->setUser(0);
        $PAGE->set_url(new moodle_url('/'));
        $PAGE->set_context(context_system::instance());

        $now = time();
        $categoryid = $DB->insert_record('theme_learnlingo_pagecats', (object)[
            'name' => 'Recursos', 'slug' => 'recursos', 'sortorder' => 0,
            'visible' => 1, 'timecreated' => $now, 'timemodified' => $now,
        ]);

        $root = $this->page_record(0, 'sobre');
        $root->title = 'Sobre';
        $root->showinmenu = 1;
        $rootid = $DB->insert_record('theme_learnlingo_pages', $root);

        $child = $this->page_record($categoryid, 'faq');
        $child->title = 'FAQ';
        $child->showinmenu = 1;
        $childid = $DB->insert_record('theme_learnlingo_pages', $child);

        $draft = $this->page_record(0, 'rascunho');
        $draft->showinmenu = 1;
        $draft->status = 0;
        $draftid = $DB->insert_record('theme_learnlingo_pages', $draft);

        $private = $this->page_record(0, 'privada');
        $private->showinmenu = 1;
        $private->accesslevel = 1;
        $privateid = $DB->insert_record('theme_learnlingo_pages', $private);

        $CFG->custommenuitems = 'Ajuda|https://example.org/help';
        $primary = new primary($PAGE);
        pages::extend_primary_navigation($primary);

        $rootnode = $primary->get('learnlingo-page-' . $rootid, navigation_node::TYPE_CUSTOM);
        $this->assertNotFalse($rootnode);
        $this->assertSame('Sobre', $rootnode->text);

        $category = $primary->get('learnlingo-category-' . (700000 + $categoryid),
            navigation_node::TYPE_CUSTOM);
        $this->assertNotFalse($category);
        $childnode = $category->get('learnlingo-page-' . $childid, navigation_node::TYPE_CUSTOM);
        $this->assertNotFalse($childnode);
        $this->assertSame('FAQ', $childnode->text);
        $this->assertTrue($category->showchildreninsubmenu);

        $this->assertFalse($primary->get('learnlingo-page-' . $draftid, navigation_node::TYPE_CUSTOM));
        $this->assertFalse($primary->get('learnlingo-page-' . $privateid, navigation_node::TYPE_CUSTOM));
        $this->assertSame('Ajuda|https://example.org/help', $CFG->custommenuitems);

        pages::extend_primary_navigation($primary);
        $this->assertCount(2, $primary->children);
        $this->assertCount(1, $category->children);
    }


    public function test_legacy_custom_menu_preserves_site_links_and_adds_visible_pages_once(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setUser(0);

        $now = time();
        $categoryid = $DB->insert_record('theme_learnlingo_pagecats', (object)[
            'name' => 'Recursos', 'slug' => 'recursos', 'sortorder' => 0,
            'visible' => 1, 'timecreated' => $now, 'timemodified' => $now,
        ]);

        $root = $this->page_record(0, 'sobre');
        $root->title = 'Sobre';
        $root->showinmenu = 1;
        $DB->insert_record('theme_learnlingo_pages', $root);

        $child = $this->page_record($categoryid, 'faq');
        $child->title = 'FAQ';
        $child->showinmenu = 1;
        $DB->insert_record('theme_learnlingo_pages', $child);

        $draft = $this->page_record(0, 'rascunho');
        $draft->status = 0;
        $draft->showinmenu = 1;
        $DB->insert_record('theme_learnlingo_pages', $draft);

        $private = $this->page_record(0, 'privada');
        $private->accesslevel = 1;
        $private->showinmenu = 1;
        $DB->insert_record('theme_learnlingo_pages', $private);

        $existing = 'Ajuda|https://example.org/help';
        $result = pages::append_custom_menu($existing);
        $this->assertStringStartsWith($existing . "\n", $result);
        $this->assertStringContainsString("Sobre|", $result);
        $this->assertStringContainsString("Recursos\n-FAQ|", $result);
        $this->assertStringNotContainsString('rascunho', $result);
        $this->assertStringNotContainsString('privada', $result);
        $this->assertSame($result, pages::append_custom_menu($result));
    }

    public function test_legacy_custom_menu_does_not_duplicate_an_existing_page_url(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setUser(0);
        $page = $this->page_record(0, 'sobre');
        $page->title = 'Sobre';
        $page->showinmenu = 1;
        $DB->insert_record('theme_learnlingo_pages', $page);

        $existing = 'Sobre a instituição|' . pages::url($page)->out(false);
        $this->assertSame($existing, pages::append_custom_menu($existing));
    }

    public function test_reordering_pages_updates_display_and_menu_order(): void {
        global $DB;
        $this->resetAfterTest(true);
        $this->setUser(0);
        $first = $this->page_record(0, 'primeira');
        $first->title = 'Primeira';
        $first->showinmenu = 1;
        $firstid = $DB->insert_record('theme_learnlingo_pages', $first);
        $second = $this->page_record(0, 'segunda');
        $second->title = 'Segunda';
        $second->showinmenu = 1;
        $secondid = $DB->insert_record('theme_learnlingo_pages', $second);
        $this->assertTrue(pages::move('page', $secondid, 'up'));
        $this->assertFalse(pages::move('page', $secondid, 'up'));
        $this->assertSame(0, (int)$DB->get_field('theme_learnlingo_pages', 'sortorder', ['id' => $secondid]));
        $this->assertSame(0, (int)$DB->get_field('theme_learnlingo_pages', 'menuorder', ['id' => $secondid]));
        $this->assertSame(1, (int)$DB->get_field('theme_learnlingo_pages', 'sortorder', ['id' => $firstid]));
        $this->assertSame($secondid, pages::navigation()[0]['id']);
    }

    public function test_reordering_categories_updates_category_list(): void {
        global $DB;
        $this->resetAfterTest(true);
        $now = time();
        $firstid = $DB->insert_record('theme_learnlingo_pagecats', (object)[
            'name' => 'First', 'slug' => 'first', 'sortorder' => 0,
            'visible' => 1, 'timecreated' => $now, 'timemodified' => $now,
        ]);
        $secondid = $DB->insert_record('theme_learnlingo_pagecats', (object)[
            'name' => 'Second', 'slug' => 'second', 'sortorder' => 0,
            'visible' => 1, 'timecreated' => $now, 'timemodified' => $now,
        ]);
        $this->assertTrue(pages::move('category', $secondid, 'up'));
        $this->assertSame($secondid, (int)pages::categories()[0]->id);
        $this->assertSame(1, (int)$DB->get_field('theme_learnlingo_pagecats', 'sortorder', ['id' => $firstid]));
    }

    /**
     * Create a valid page fixture.
     *
     * @param int $categoryid Category identifier.
     * @param string $slug Page slug.
     * @return stdClass Page data.
     */
    private function page_record(int $categoryid, string $slug): stdClass {
        return (object)[
            'categoryid' => $categoryid, 'title' => 'Pública', 'slug' => $slug,
            'content' => '<p>Hello</p>', 'scss' => '', 'description' => '',
            'metadescription' => '', 'status' => 1, 'accesslevel' => 0,
            'fullwidth' => 1, 'showinmenu' => 0, 'menutitle' => '',
            'sortorder' => 0, 'menuorder' => 0, 'usercreated' => 0,
            'usermodified' => 0, 'timecreated' => time(), 'timemodified' => time(),
        ];
    }
}
