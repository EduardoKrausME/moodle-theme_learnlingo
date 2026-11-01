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
 * Optional Dark Mode plugin integration.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

/**
 * @covers \theme_learnlingo\dark_mode
 */
final class dark_mode_test extends \advanced_testcase {
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

    public function test_installation_form_is_a_separate_post_request(): void {
        global $CFG;
        $setup = file_get_contents($CFG->dirroot . '/theme/learnlingo/templates/setup.mustache');
        $settings = file_get_contents($CFG->dirroot . '/theme/learnlingo/templates/darkmode_settings.mustache');
        $scss = file_get_contents($CFG->dirroot . '/theme/learnlingo/scss/_poss.scss');

        $this->assertStringContainsString('form="ll-boost-dark-install"', $setup);
        $this->assertStringContainsString('method="post" action="{{{darkinstallurl}}}"', $setup);
        $this->assertStringContainsString('name="installdep" value="local_boost_dark"', $setup);
        $this->assertStringContainsString('name="sesskey" value="{{sesskey}}"', $setup);
        $this->assertStringContainsString('formaction="{{{installurl}}}" formmethod="post"', $settings);
        $this->assertStringContainsString('@import "moodle/dark";', $scss);
    }
}
