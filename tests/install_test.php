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

/**
 * Verify initial LearnLingo settings.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

/**
 * Installation must persist defaults without changing configured values.
 */
final class install_test extends \advanced_testcase {
    /**
     * Verify every portable setting has a stored value after installation.
     */
    public function test_install_preserves_existing_values_and_seeds_defaults(): void {
        global $CFG;

        $this->resetAfterTest();
        require_once($CFG->dirroot . '/theme/learnlingo/db/install.php');

        $definitions = branding_exchange::definitions();
        foreach (array_keys($definitions) as $name) {
            unset_config($name, 'theme_learnlingo');
        }
        unset_config('setup_pending', 'theme_learnlingo');
        set_config('brandcolor', '#123456', 'theme_learnlingo');
        set_config('favicon', 'custom.ico', 'core_admin');

        \xmldb_theme_learnlingo_install();

        foreach ($definitions as $name => [$type, $default]) {
            $expected = $name === 'brandcolor' ? '#123456'
                : (string)($type === 'bool' ? (int)$default : $default);
            $this->assertSame($expected, (string)get_config('theme_learnlingo', $name), $name);
        }
        $this->assertSame('config', (string)get_config('theme_learnlingo', 'homepagehtml'));
        $this->assertSame('1', (string)get_config('theme_learnlingo', 'setup_pending'));
        $this->assertSame('custom.ico', get_config('core_admin', 'favicon'));
    }
}
