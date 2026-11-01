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
 * Conditions and safe choices for the optional student landing page step.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

/**
 * Keep the wizard consistent with Moodle's enabled pages.
 *
 * @covers \theme_learnlingo\setup_steps
 */
final class setup_steps_test extends \advanced_testcase {
    /**
     * A fourth step appears if either Moodle page is enabled.
     */
    public function test_optional_step_follows_moodle_navigation(): void {
        $cfg = (object)['enabledashboard' => 0, 'enablemycourses' => 0];
        $this->assertSame(3, setup_steps::count($cfg));
        $this->assertSame(3, setup_steps::next($cfg, 3));

        $cfg->enabledashboard = 1;
        $this->assertSame(4, setup_steps::count($cfg));
        $this->assertSame(4, setup_steps::next($cfg, 3));

        $cfg->enabledashboard = 0;
        $cfg->enablemycourses = 1;
        $this->assertSame(4, setup_steps::count($cfg));

        $cfg->enabledashboard = 1;
        $this->assertSame(4, setup_steps::count($cfg));
        $this->assertSame(4, setup_steps::next($cfg, 4));

        $cfg = new \stdClass();
        $this->assertTrue(setup_steps::dashboard_enabled($cfg));
        $this->assertFalse(setup_steps::mycourses_enabled($cfg));
    }

    /**
     * Admin can select either page or both, but cannot disable both.
     */
    public function test_navigation_selection_rejects_both_disabled(): void {
        $this->assertTrue(setup_steps::valid_selection(true, false));
        $this->assertTrue(setup_steps::valid_selection(false, true));
        $this->assertTrue(setup_steps::valid_selection(true, true));
        $this->assertFalse(setup_steps::valid_selection(false, false));
    }

    /**
     * Never leave the site's login destination pointing to a disabled page.
     */
    public function test_default_homepage_follows_selected_navigation(): void {
        $this->assertSame(HOMEPAGE_MYCOURSES,
            setup_steps::default_homepage(HOMEPAGE_MY, false, true));
        $this->assertSame(HOMEPAGE_MY,
            setup_steps::default_homepage(HOMEPAGE_MYCOURSES, true, false));
        $this->assertSame(HOMEPAGE_MY,
            setup_steps::default_homepage(HOMEPAGE_MY, true, true));
        $this->assertSame(HOMEPAGE_SITE,
            setup_steps::default_homepage(HOMEPAGE_SITE, false, true));
        $this->assertSame(HOMEPAGE_USER,
            setup_steps::default_homepage(HOMEPAGE_USER, true, false));
    }
}
