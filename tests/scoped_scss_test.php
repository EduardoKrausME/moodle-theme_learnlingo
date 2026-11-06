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
 * Component-specific SCSS regression tests.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use advanced_testcase;
use theme_config;

/**
 * Check that home and footer styles stay inside their respective DOM roots.
 *
 * @coversNothing
 */
final class scoped_scss_test extends advanced_testcase {
    public function test_home_and_footer_are_scoped_but_global_scss_is_not(): void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/theme/learnlingo/lib.php');

        set_config('homepagemode', 'html', 'theme_learnlingo');
        set_config('footermode', 'html', 'theme_learnlingo');
        set_config('homepagescss', '.hero{color:red}nav.navbar{display:none}', 'theme_learnlingo');
        set_config('footerscss', '.links{color:blue}', 'theme_learnlingo');
        set_config('scss', 'nav.navbar{background:black}', 'theme_learnlingo');
        theme_reset_all_caches();

        $scss = theme_learnlingo_get_extra_scss(theme_config::load('learnlingo'));
        $this->assertStringContainsString("\nnav.navbar{background:black}", $scss);
        $this->assertStringContainsString(
            "body#page-site-index .learnlingo-home {\n.hero{color:red}nav.navbar{display:none}", $scss);
        $this->assertStringContainsString(
            "#page-footer.learnlingo-site-footer.learnlingo-footer-html-mode {\n.links{color:blue}", $scss);
    }

    public function test_moodle_home_mode_keeps_component_scope_without_html_footer(): void {
        global $CFG;
        $this->resetAfterTest();
        require_once($CFG->dirroot . '/theme/learnlingo/lib.php');

        set_config('homepagemode', 'moodle', 'theme_learnlingo');
        set_config('footermode', 'moodle', 'theme_learnlingo');
        set_config('homepagescss', '.hero{color:red}', 'theme_learnlingo');
        set_config('footerscss', '.footer-links{color:blue}', 'theme_learnlingo');
        theme_reset_all_caches();

        $scss = theme_learnlingo_get_extra_scss(theme_config::load('learnlingo'));
        $this->assertStringContainsString("body#page-site-index .learnlingo-home {\n.hero{color:red}", $scss);
        $this->assertStringNotContainsString('.footer-links{color:blue}', $scss);
    }

    public function test_root_media_and_dark_mode_selectors_are_scoped(): void {
        global $CFG;
        require_once($CFG->dirroot . '/theme/learnlingo/lib.php');

        $home = theme_learnlingo_scope_scss(
            ':root{--ink:#123}body#page-site-index .hero{color:var(--ink)}'
            . '.learnlingo-home{padding:1rem}@media(max-width:600px){body#page-site-index .hero{padding:0}}'
            . '[data-bs-theme="dark"] .card{color:red}html[data-bs-theme="light"] .card{color:black}',
            'body#page-site-index .learnlingo-home',
            ['body#page-site-index .learnlingo-home', 'body#page-site-index', '.learnlingo-home', ':root', 'body', 'html']);
        $this->assertStringContainsString('&{--ink:#123}', $home);
        $this->assertStringContainsString('& .hero{color:var(--ink)}', $home);
        $this->assertStringContainsString('&{padding:1rem}', $home);
        $this->assertStringContainsString('@media(max-width:600px){& .hero{padding:0}}', $home);
        $this->assertStringContainsString('[data-bs-theme="dark"] & .card{color:red}', $home);
        $this->assertStringContainsString('html[data-bs-theme="light"] & .card{color:black}', $home);

        $footer = theme_learnlingo_scope_scss('#page-footer{background:#123}#page-footer .links{color:white}',
            '#page-footer.learnlingo-site-footer.learnlingo-footer-html-mode', ['#page-footer']);
        $this->assertStringContainsString('&{background:#123}& .links{color:white}', $footer);
    }

    public function test_unsafe_sass_directives_are_blocked(): void {
        global $CFG;
        require_once($CFG->dirroot . '/theme/learnlingo/lib.php');
        $this->assertFalse(theme_learnlingo_safe_component_scss('@at-root .navbar{display:none}'));
        $this->assertFalse(theme_learnlingo_safe_component_scss('@import "https://example.com/test.css";'));
        $this->assertTrue(theme_learnlingo_safe_component_scss('@media(max-width:600px){.card{padding:0}}'));
    }
}
