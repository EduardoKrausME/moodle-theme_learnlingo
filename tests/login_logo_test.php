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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

namespace theme_learnlingo;

use advanced_testcase;
use moodle_url;
use theme_boost\output\core_renderer as boost_core_renderer;
use theme_learnlingo\output\core_renderer;

/**
 * Regression tests for the login-only logo.
 *
 * @covers \theme_learnlingo\output\core_renderer
 */
final class login_logo_test extends advanced_testcase {
    /**
     * The login logo replaces the site logo only on the login layout.
     */
    public function test_custom_login_logo_is_limited_to_login_layout(): void {
        global $PAGE;

        $this->resetAfterTest();
        set_config('loginlogo', '/contrast-logo.png', 'theme_learnlingo');
        $PAGE->force_theme('learnlingo');
        $PAGE->set_pagelayout('login');

        $renderer = new core_renderer($PAGE, 'html');
        $url = $renderer->get_logo_url();

        $this->assertInstanceOf(moodle_url::class, $url);
        $this->assertStringContainsString('/theme_learnlingo/loginlogo/', $url->out(false));
        $this->assertStringEndsWith('/contrast-logo.png', parse_url($url->out(false), PHP_URL_PATH));

        $PAGE->set_pagelayout('standard');
        $base = new boost_core_renderer($PAGE, 'html');
        $this->assertEquals($base->get_logo_url(), $renderer->get_logo_url());
    }

    /**
     * Existing sites retain the Moodle-wide site logo when no custom logo is uploaded.
     */
    public function test_login_without_custom_logo_uses_site_logo(): void {
        global $PAGE;

        $this->resetAfterTest();
        set_config('loginlogo', '', 'theme_learnlingo');
        set_config('logo', '/site-logo.png', 'core_admin');
        $PAGE->force_theme('learnlingo');
        $PAGE->set_pagelayout('login');

        $renderer = new core_renderer($PAGE, 'html');
        $base = new boost_core_renderer($PAGE, 'html');

        $this->assertEquals($base->get_logo_url(), $renderer->get_logo_url());
    }
}
