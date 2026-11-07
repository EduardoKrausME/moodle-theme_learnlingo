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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Visual editor data contract tests for existing theme content.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use advanced_testcase;
use invalid_parameter_exception;

/**
 * The visual editor must operate on current settings and custom-page fields.
 *
 * @coversNothing
 */
final class editor_integration_test extends advanced_testcase {
    /**
     * Home/footer target aliases are never arbitrary configuration keys.
     */
    public function test_editor_only_accepts_known_targets(): void {
        global $CFG;
        require_once($CFG->dirroot . '/theme/learnlingo/_editor/editor-lib.php');

        $this->assertSame('homepage', \theme_learnlingo_editor_target('homepage'));
        $this->assertSame('footer', \theme_learnlingo_editor_target('footer'));
        $this->assertSame('page', \theme_learnlingo_editor_target('page'));
        $this->expectException(invalid_parameter_exception::class);
        \theme_learnlingo_editor_target('other');
    }

    /**
     * Existing admin settings are read as one HTML + one SCSS pair.
     */
    public function test_editor_reads_homepage_and_footer_settings_without_modifying_them(): void {
        global $CFG;
        $this->resetAfterTest(true);
        require_once($CFG->dirroot . '/theme/learnlingo/_editor/editor-lib.php');

        set_config('homepagehtml', '<main>{{#courses}}{{fullname}}{{/courses}}</main>', 'theme_learnlingo');
        set_config('homepagescss', '$brand: #123; .page { color: $brand; }', 'theme_learnlingo');
        set_config('footerhtml', '<footer>Company</footer>', 'theme_learnlingo');
        set_config('footerscss', 'footer { padding: 10px; }', 'theme_learnlingo');

        $home = \theme_learnlingo_editor_data('homepage');
        $this->assertStringContainsString('{{#courses}}', $home['html']);
        $this->assertStringContainsString('$brand', $home['scss']);
        $this->assertSame('theme_learnlingo_homepage', $home['returnurl']->get_param('section'));

        $footer = \theme_learnlingo_editor_data('footer');
        $this->assertSame('<footer>Company</footer>', $footer['html']);
        $this->assertSame('footer { padding: 10px; }', $footer['scss']);
        $this->assertSame('theme_learnlingo_footer', $footer['returnurl']->get_param('section'));

        set_config('homepagehtml', 'config', 'theme_learnlingo');
        $this->assertSame('', \theme_learnlingo_editor_data('homepage')['html']);
    }

    /**
     * Custom pages use content/scss, not the obsolete html/info/template fields.
     */
    public function test_editor_reads_current_custom_page_columns(): void {
        global $CFG, $DB;
        $this->resetAfterTest(true);
        require_once($CFG->dirroot . '/theme/learnlingo/_editor/editor-lib.php');

        $time = time();
        $id = $DB->insert_record('theme_learnlingo_pages', (object)[
            'categoryid' => 0,
            'title' => 'About',
            'slug' => 'about',
            'content' => '<h1>About</h1>',
            'scss' => 'h1 { color: red; }',
            'status' => 0,
            'accesslevel' => 0,
            'fullwidth' => 1,
            'showinmenu' => 0,
            'menuorder' => 0,
            'sortorder' => 0,
            'usercreated' => 0,
            'usermodified' => 0,
            'timecreated' => $time,
            'timemodified' => $time,
        ]);
        $page = \theme_learnlingo_editor_data('page', $id);
        $this->assertSame('<h1>About</h1>', $page['html']);
        $this->assertSame('h1 { color: red; }', $page['scss']);
        $this->assertSame($id, (int)$page['returnurl']->get_param('id'));
    }
}
