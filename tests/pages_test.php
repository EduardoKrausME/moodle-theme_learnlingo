<?php
// This file is part of Moodle - http://moodle.org/
// @package theme_learnlingo
// @copyright 2026 Eduardo Kraus
// @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

namespace theme_learnlingo;

defined('MOODLE_INTERNAL') || die;

/** Tests for flat category routing, collisions and visibility. */
final class pages_test extends \advanced_testcase {
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
        $this->expectException(\moodle_exception::class);
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

    private function page_record(int $categoryid, string $slug): \stdClass {
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
