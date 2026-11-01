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
 * Footer renderer compatibility checks.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use advanced_testcase;
use theme_learnlingo\output\footer_renderer;

/**
 * Ensure the two footer rendering modes remain independent.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \theme_learnlingo\output\footer_renderer
 */
final class footer_renderer_test extends advanced_testcase {
    /**
     * The default mode continues to render the configured footer columns.
     */
    public function test_moodle_mode_keeps_current_footer_columns(): void {
        $this->resetAfterTest();
        set_config('footermode', 'moodle', 'theme_learnlingo');
        set_config('footer_title_1', 'Contact', 'theme_learnlingo');
        set_config('footer_html_1', '<p>Contact details</p>', 'theme_learnlingo');
        set_config('footerhtml', '<p>Custom footer</p>', 'theme_learnlingo');

        $data = footer_renderer::mustache_data();

        $this->assertTrue($data['footer_mode_moodle']);
        $this->assertFalse($data['footer_mode_html']);
        $this->assertSame(1, $data['footercount']);
        $this->assertSame('Contact', $data['footercontents'][0]['footertitle']);
        $this->assertStringContainsString('Contact details', $data['footercontents'][0]['footerhtml']);
        $this->assertSame('', $data['footer_custom_html']);
    }

    /**
     * HTML mode substitutes the entire footer while preserving column settings.
     */
    public function test_html_mode_replaces_columns_with_html(): void {
        $this->resetAfterTest();
        set_config('footermode', 'html', 'theme_learnlingo');
        set_config('footer_html_1', '<p>Original column</p>', 'theme_learnlingo');
        set_config('footerhtml', '<section><p>Custom footer</p></section>', 'theme_learnlingo');

        $data = footer_renderer::mustache_data();

        $this->assertFalse($data['footer_mode_moodle']);
        $this->assertTrue($data['footer_mode_html']);
        $this->assertSame(0, $data['footercount']);
        $this->assertSame([], $data['footercontents']);
        $this->assertStringContainsString('Custom footer', $data['footer_custom_html']);
        $this->assertSame('<p>Original column</p>', get_config('theme_learnlingo', 'footer_html_1'));
    }
}
