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
 * Verify that the onboarding marker cannot leak to the site homepage.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use advanced_testcase;
use theme_config;
use function theme_learnlingo_homepage_html;

/**
 * Tests for the site-home routing and reserved HTML value.
 *
 * @coversNothing
 */
final class setup_routing_test extends advanced_testcase {
    /**
     * Never confuse Moodle's admin, courses, or user dashboards with the home.
     */
    public function test_only_site_index_matches_homepage_route(): void {
        $this->assertTrue(hook_listener::is_site_frontpage_request(
            '/index.php', 'https://example.edu'
        ));
        $this->assertTrue(hook_listener::is_site_frontpage_request(
            '/moodle/index.php', 'https://example.edu/moodle/'
        ));
        $this->assertFalse(hook_listener::is_site_frontpage_request(
            '/admin/index.php', 'https://example.edu'
        ));
        $this->assertFalse(hook_listener::is_site_frontpage_request(
            '/moodle/admin/index.php', 'https://example.edu/moodle'
        ));
        $this->assertFalse(hook_listener::is_site_frontpage_request(
            '/moodle/course/index.php', 'https://example.edu/moodle'
        ));
        $this->assertFalse(hook_listener::is_site_frontpage_request(
            '/moodle/my/index.php', 'https://example.edu/moodle'
        ));
    }

    /**
     * Treat the exact sentinel as private configuration, not user-facing HTML.
     */
    public function test_homepage_html_config_is_never_rendered_as_content(): void {
        global $CFG;

        $this->resetAfterTest();
        require_once($CFG->dirroot . '/theme/learnlingo/lib.php');

        $theme = theme_config::load('learnlingo');
        $theme->settings->homepagehtml = 'config';
        $this->assertSame('', theme_learnlingo_homepage_html($theme));

        $theme->settings->homepagehtml = '<section>Real homepage</section>';
        $this->assertSame('<section>Real homepage</section>', theme_learnlingo_homepage_html($theme));
    }
}
