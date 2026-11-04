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
 * Functional tests for LearnLingo MCP page tools.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use advanced_testcase;
use local_mcp\exception\api_exception;
use local_mcp\security\authenticated_identity;
use theme_learnlingo\mcp\delete_page;
use theme_learnlingo\mcp\get_pages;
use theme_learnlingo\mcp\page_service;
use theme_learnlingo\mcp\update_page;
use theme_learnlingo\mcp\update_page_category;

/**
 * Exercise read/preview/write workflows and concurrency protection.
 *
 * @covers \theme_learnlingo\mcp\page_service
 */
final class mcp_pages_test extends advanced_testcase {
    /**
     * Create an administrative authenticated MCP identity for test calls.
     *
     * @return authenticated_identity Simulated connected administrator.
     */
    private function identity(): authenticated_identity {
        $this->setAdminUser();
        return new authenticated_identity((int)get_admin()->id, 'test', ['mcp:read', 'mcp:write']);
    }

    /**
     * Preview, create, publish, and remove a page without cascading categories.
     *
     * @return void
     */
    public function test_page_crud_through_mcp(): void {
        global $DB;
        $this->resetAfterTest(true);
        $identity = $this->identity();
        $reader = new get_pages();
        $categorytool = new update_page_category();
        $pagetool = new update_page();
        $deletetool = new delete_page();

        $this->assertSame('moodle/site:config', $pagetool->get_required_capability());
        $this->assertTrue($pagetool->supports_dry_run());
        $this->assertTrue($pagetool->requires_confirmation());
        $this->assertTrue($deletetool->is_destructive());
        $this->assertTrue($deletetool->requires_confirmation());

        $categoryargs = ['operation' => 'create', 'name' => 'Empresa', 'slug' => 'empresa'];
        $categorypreview = $categorytool->preview($categoryargs, $identity);
        $this->assertTrue($categorypreview['requires_confirmation']);
        $this->assertSame(0, $DB->count_records('theme_learnlingo_pagecats'));
        $category = $categorytool->execute($categoryargs, $identity);
        $catid = $category['id'];
        $this->assertGreaterThan(0, $catid);

        $pageargs = [
            'operation' => 'create', 'title' => 'Sobre nós',
            'categoryid' => $catid, 'content' => '<h1>{{user.firstname}}</h1>',
        ];
        $preview = $pagetool->preview($pageargs, $identity);
        $this->assertTrue($preview['requires_confirmation']);
        $this->assertSame(0, $DB->count_records('theme_learnlingo_pages'));
        $created = $pagetool->execute($pageargs, $identity);
        $pageid = $created['id'];
        $detail = $reader->execute(['pageid' => $pageid], $identity)['selected_page'];
        $this->assertSame('sobre-nos', $detail['slug']);
        $this->assertSame(0, (int)$detail['status']);
        $this->assertSame('<h1>{{user.firstname}}</h1>', $detail['content']);
        $this->assertNull(pages::published('empresa/sobre-nos'));

        $update = [
            'operation' => 'update', 'id' => $pageid, 'expected_hash' => $created['state_hash'],
            'status' => 1, 'showinmenu' => 1,
        ];
        $pagetool->preview($update, $identity);
        $pagetool->execute($update, $identity);
        $published = pages::published('empresa/sobre-nos');
        $this->assertNotNull($published);
        $this->assertSame(1, (int)$published[0]->showinmenu);
        $this->assertSame(1, (int)$published[0]->status);

        $index = $reader->execute([], $identity);
        $this->assertCount(1, $index['categories']);
        $this->assertCount(1, $index['pages']);
        $deletetarget = [
            'type' => 'page', 'id' => $pageid,
            'expected_hash' => $index['pages'][0]['state_hash'],
        ];
        $deletepreview = $deletetool->preview($deletetarget, $identity);
        $this->assertSame('page', $deletepreview['type']);
        $this->assertTrue($deletepreview['is_destructive']);
        $this->assertSame(1, $DB->count_records('theme_learnlingo_pages'));
        $deletetool->execute($deletetarget, $identity);
        $this->assertSame(0, $DB->count_records('theme_learnlingo_pages'));

        $deletecategory = [
            'type' => 'category', 'id' => $catid,
            'expected_hash' => $category['state_hash'],
        ];
        $deletetool->execute($deletecategory, $identity);
        $this->assertSame(0, $DB->count_records('theme_learnlingo_pagecats'));
    }

    /**
     * A concurrent modification must invalidate the old page state hash.
     *
     * @return void
     */
    public function test_stale_state_hash_rejected(): void {
        $this->resetAfterTest(true);
        $identity = $this->identity();
        $tool = new update_page();
        $created = $tool->execute([
            'operation' => 'create', 'title' => 'Contato',
            'content' => '<p>Contato</p>',
        ], $identity);
        $tool->execute([
            'operation' => 'update', 'id' => $created['id'],
            'expected_hash' => $created['state_hash'], 'title' => 'Fale conosco',
        ], $identity);
        $this->expectException(api_exception::class);
        $tool->preview([
            'operation' => 'update', 'id' => $created['id'],
            'expected_hash' => $created['state_hash'], 'title' => 'Outro título',
        ], $identity);
    }

    /**
     * An embedded script cannot be published through the MCP page write tool.
     *
     * @return void
     */
    public function test_unsafe_html_rejected(): void {
        $this->resetAfterTest(true);
        $identity = $this->identity();
        $this->expectException(\moodle_exception::class);
        (new update_page())->preview([
            'operation' => 'create', 'title' => 'Insegura',
            'content' => '<script>alert(1)</script>',
        ], $identity);
    }
}
