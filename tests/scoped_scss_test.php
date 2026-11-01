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
 * Homepage SCSS isolation checks.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use advanced_testcase;
use theme_config;

/**
 * Check the global homepage SCSS and the HTML-footer-only SCSS.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class scoped_scss_test extends advanced_testcase {
    /**
     * Homepage and global SCSS apply everywhere while footer SCSS remains scoped.
     */
    public function test_homepage_is_global_and_footer_remains_scoped(): void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/theme/learnlingo/lib.php');

        set_config('homepagemode', 'learnlingo', 'theme_learnlingo');
        set_config('footermode', 'html', 'theme_learnlingo');
        set_config('homepagescss', '.homepage-marker { padding: 1rem; }', 'theme_learnlingo');
        set_config('footerscss', '.footer-marker { margin: 2rem; }', 'theme_learnlingo');
        set_config('scss', '.global-marker { display: block; }', 'theme_learnlingo');
        theme_reset_all_caches();

        $scss = theme_learnlingo_get_extra_scss(theme_config::load('learnlingo'));
        $this->assertStringContainsString('.global-marker { display: block; }', $scss);
        $this->assertStringContainsString("\n.homepage-marker { padding: 1rem; }", $scss);
        $this->assertStringNotContainsString("body#page-site-index {\n.homepage-marker", $scss);
        $this->assertStringContainsString(
            "#page-footer.learnlingo-site-footer.learnlingo-footer-html-mode {\n.footer-marker", $scss
        );
    }

    /**
     * Homepage styles remain active in Moodle homepage mode; footer styles still follow its mode.
     */
    public function test_moodle_mode_keeps_global_homepage_scss_and_disables_html_footer_scss(): void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/theme/learnlingo/lib.php');

        set_config('homepagemode', 'moodle', 'theme_learnlingo');
        set_config('footermode', 'moodle', 'theme_learnlingo');
        set_config('homepagescss', '.homepage-marker { padding: 1rem; }', 'theme_learnlingo');
        set_config('footerscss', '.footer-marker { margin: 2rem; }', 'theme_learnlingo');
        theme_reset_all_caches();

        $scss = theme_learnlingo_get_extra_scss(theme_config::load('learnlingo'));
        $this->assertStringContainsString('.homepage-marker { padding: 1rem; }', $scss);
        $this->assertStringNotContainsString('.footer-marker { margin: 2rem; }', $scss);
        $this->assertSame('.homepage-marker { padding: 1rem; }',
            get_config('theme_learnlingo', 'homepagescss'));
        $this->assertSame('.footer-marker { margin: 2rem; }',
            get_config('theme_learnlingo', 'footerscss'));
    }

    /**
     * Full CSS selectors including root variables, menu, footer, and media queries remain untouched.
     */
    public function test_homepage_css_works_across_the_entire_site(): void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/theme/learnlingo/lib.php');

        set_config('homepagemode', 'html', 'theme_learnlingo');
        set_config('homepagescss',
            ':root{--home-ink:#123456}body#page-site-index .hero{color:var(--home-ink)}'
                . 'nav.navbar{background:#101f38}#page-footer{color:#fff}'
                . '@media(max-width:600px){nav.navbar{padding:0}}', 'theme_learnlingo');
        theme_reset_all_caches();

        $scss = theme_learnlingo_get_extra_scss(theme_config::load('learnlingo'));

        $this->assertStringContainsString(':root{--home-ink:#123456}', $scss);
        $this->assertStringContainsString('body#page-site-index .hero{color:var(--home-ink)}', $scss);
        $this->assertStringContainsString('nav.navbar{background:#101f38}', $scss);
        $this->assertStringContainsString('#page-footer{color:#fff}', $scss);
        $this->assertStringContainsString('@media(max-width:600px){nav.navbar{padding:0}}', $scss);
        $this->assertStringNotContainsString("body#page-site-index {\n:root", $scss);
    }

    /**
     * Footer CSS with an explicit footer root must not target a nonexistent nested footer.
     */
    public function test_html_footer_full_stylesheet_root_selectors_are_rebased(): void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/theme/learnlingo/lib.php');

        set_config('footermode', 'html', 'theme_learnlingo');
        set_config('footerscss',
            '#page-footer{background:#101f38}#page-footer .o90-footer{color:#fff}'
                . '@media(max-width:600px){#page-footer .o90-footer{padding:1rem}}',
            'theme_learnlingo');
        theme_reset_all_caches();

        $scss = theme_learnlingo_get_extra_scss(theme_config::load('learnlingo'));

        $this->assertStringContainsString(
            "#page-footer.learnlingo-site-footer.learnlingo-footer-html-mode {\n&{background:#101f38}"
                . '& .o90-footer{color:#fff}', $scss
        );
        $this->assertStringContainsString(
            '@media(max-width:600px){& .o90-footer{padding:1rem}}', $scss
        );
        $this->assertStringNotContainsString('#page-footer #page-footer', $scss);
    }
}
