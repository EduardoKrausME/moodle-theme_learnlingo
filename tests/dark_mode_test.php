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
 * Optional Dark Mode plugin integration.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use advanced_testcase;

/**
 * Class dark_mode_test
 *
 * @covers \theme_learnlingo\dark_mode
 */
final class dark_mode_test extends advanced_testcase {
    /**
     * test_plugin_status_respects_version_and_enable_flag
     *
     * @return void
     */
    public function test_plugin_status_respects_version_and_enable_flag(): void {
        $this->resetAfterTest();
        unset_config('version', 'local_boost_dark');
        $this->assertFalse(dark_mode::installed());
        $this->assertFalse(dark_mode::enabled());

        set_config('version', '2026110100', 'local_boost_dark');
        set_config('enable', 0, 'local_boost_dark');
        $this->assertTrue(dark_mode::installed());
        $this->assertFalse(dark_mode::enabled());

        set_config('enable', 1, 'local_boost_dark');
        $status = dark_mode::status();
        $this->assertTrue($status['installed']);
        $this->assertTrue($status['enabled']);
        $this->assertSame('[data-bs-theme="dark"]', $status['css_selector']);
    }

    /**
     * Custom Mustache homes follow the active HTML dark attribute without a new page render.
     *
     * @return void
     */
    public function test_custom_mustache_home_inherits_light_and_dark_colours(): void {
        global $CFG;

        $light = file_get_contents($CFG->dirroot . '/theme/learnlingo/scss/_poss.scss');
        $dark = file_get_contents($CFG->dirroot . '/theme/learnlingo/scss/moodle/_dark.scss');
        // Homepage tokens are aliases of the shared theme palette in both modes.
        foreach ([
            '--bs-home-canvas: var(--bs-canvas);',
            '--bs-home-surface: var(--bs-surface);',
            '--bs-home-ink: var(--bs-ink);',
            '--bs-home-muted: var(--bs-muted);',
            '--bs-home-border: var(--bs-border);',
        ] as $declaration) {
            $this->assertStringContainsString($declaration, $light);
        }
        $this->assertStringContainsString('.learnlingo-html-home,', $light);
        $this->assertStringContainsString('.learnlingo-home-intro {', $light);
        $this->assertStringContainsString('color-scheme: light;', $light);
        $this->assertStringContainsString('[data-bs-theme="dark"]:root {', $light);
        $this->assertStringContainsString('color-scheme: dark;', $light);
        $this->assertStringContainsString('[data-bs-theme="dark"]', $dark);
        $this->assertStringContainsString('.learnlingo-home-course-card,', $dark);
        $this->assertStringContainsString('background: var(--bs-surface);', $dark);

        $schema = homepage_context::schema();
        $instructions = implode(' ', $schema['instructions']);
        $this->assertStringContainsString('[data-bs-theme="dark"]', $instructions);
        $this->assertStringContainsString('homepagescss', $instructions);
        $this->assertStringContainsString('do not require JavaScript', $instructions);
        $this->assertArrayNotHasKey('isdark', $schema['fields']);
        $this->assertStringContainsString('[data-bs-theme="dark"]', implode(' ', $schema['examples']));
    }

    /**
     * Setup and settings link to Moodle's installer without nesting forms.
     *
     * @return void
     */
    public function test_dark_mode_setup_and_settings_use_install_links(): void {
        global $CFG;
        $setup = file_get_contents($CFG->dirroot . '/theme/learnlingo/templates/setup.mustache');
        $settings = file_get_contents($CFG->dirroot . '/theme/learnlingo/templates/darkmode_settings.mustache');
        $scss = file_get_contents($CFG->dirroot . '/theme/learnlingo/scss/_poss.scss');
        $adminsettings = file_get_contents($CFG->dirroot . '/theme/learnlingo/settings.php');

        $this->assertStringContainsString('<a href="{{{darkinstallurl}}}" class="btn btn-secondary">', $setup);
        $this->assertStringNotContainsString('name="installdep" value="local_boost_dark"', $setup);
        $this->assertStringContainsString('class="ll-darkmode-settings"', $settings);
        $this->assertStringContainsString('{{#str}}darkmodeintro, theme_learnlingo, {{{installurl}}}{{/str}}', $settings);
        $this->assertStringNotContainsString('<form', $settings);
        $this->assertStringNotContainsString('ll-boost-dark-form-template', $settings);
        $this->assertStringNotContainsString("js_call_amd('theme_learnlingo/darkmode_settings'", $adminsettings);

        foreach (['en', 'pt_br'] as $lang) {
            $string = [];
            require($CFG->dirroot . '/theme/learnlingo/lang/' . $lang . '/theme_learnlingo.php');
            $this->assertStringContainsString('<a href="{$a}"', $string['darkmodeintro']);
            $this->assertStringNotContainsString('<form', $string['darkmodeintro']);
        }

        $this->assertStringContainsString('@import "moodle/dark";', $scss);
    }
}
