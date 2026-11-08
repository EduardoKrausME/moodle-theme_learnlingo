<?php
// This file is part of Moodle - https://moodle.org/
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
 * Tests for the HTML/SCSS source editor in admin settings.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use advanced_testcase;

/**
 * Source highlighting must not change config persistence or edit raw HTML.
 *
 * @covers \theme_learnlingo\admin_setting_configcode
 */
final class code_settings_test extends advanced_testcase {
    /**
     * HTML code is escaped for rendering and stored byte-for-byte as raw config.
     */
    public function test_html_setting_preserves_source(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $setting = new admin_setting_configcode(
            'theme_learnlingo/homepagehtml', 'Homepage', '', '', 'html'
        );

        $source = "<section class=\"hero\">\n  <h1>Olá & bem-vindo</h1>\n</section>";
        $html = $setting->output_html($source);
        $this->assertStringContainsString('data-ll-code-editor', $html);
        $this->assertStringContainsString('data-language="html"', $html);
        $this->assertStringContainsString('&lt;section', $html);
        $this->assertStringContainsString('&amp; bem-vindo', $html);
        $this->assertStringNotContainsString('<section class="hero">', $html);
        $this->assertStringContainsString('name="s_theme_learnlingo_homepagehtml"', $html);

        $this->assertSame('', $setting->write_setting($source));
        $this->assertSame($source, get_config('theme_learnlingo', 'homepagehtml'));
    }

    /**
     * Purging theme CSS must also invalidate the theme's MUC entries.
     *
     * PHPUnit may expose the install-time theme revision fallback, so verify
     * observable cache invalidation instead of assuming a revision increment.
     */
    public function test_css_purge_invalidates_muc_cache(): void {
        global $CFG;
        require_once($CFG->dirroot . '/theme/learnlingo/lib.php');
        $this->resetAfterTest();

        $cache = \cache::make('theme_learnlingo', 'publiccourses');
        $cache->set('test-css-purge', 'cached');
        $this->assertSame('cached', $cache->get('test-css-purge'));

        \theme_learnlingo_purge_css_cache();

        $cache = \cache::make('theme_learnlingo', 'publiccourses');
        $this->assertFalse($cache->get('test-css-purge'));
    }

    /**
     * SCSS is not cleaned, interpreted or converted into rich-text HTML.
     */
    public function test_scss_setting_preserves_source(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $setting = new admin_setting_configcode(
            'theme_learnlingo/scss', 'Custom SCSS', '', '', 'scss'
        );

        $source = "\$primary: #58cc02;\n.selector { content: \"<hello>\"; }";
        $html = $setting->output_html($source);
        $this->assertStringContainsString('data-language="scss"', $html);
        $this->assertStringContainsString('&lt;hello&gt;', $html);
        $this->assertSame('', $setting->write_setting($source));
        $this->assertSame($source, get_config('theme_learnlingo', 'scss'));
    }
}
