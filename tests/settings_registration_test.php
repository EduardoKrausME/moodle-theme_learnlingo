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
 * Administration settings registration regression tests.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use admin_settingpage;
use advanced_testcase;
use function admin_get_root;

/**
 * Ensure Moodle can find the LearnLingo page in either admin tree mode.
 *
 * @coversNothing
 */
final class settings_registration_test extends advanced_testcase {
    /**
     * The theme settings page must remain visible in the lightweight tree.
     */
    public function test_settings_page_is_registered_in_both_admin_tree_modes(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        global $CFG;
        require_once($CFG->libdir . '/adminlib.php');

        // Moodle builds the lightweight tree for navigation before full settings are needed.
        $tree = admin_get_root(true, false);
        $this->assertInstanceOf(admin_settingpage::class, $tree->locate('themesettinglearnlingo', true));

        // The same section must resolve when admin/settings.php requests the full tree.
        $tree = admin_get_root(true, true);
        $this->assertInstanceOf(admin_settingpage::class, $tree->locate('themesettinglearnlingo', true));
    }
}
