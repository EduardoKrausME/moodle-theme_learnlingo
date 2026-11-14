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
 * Contract tests for the LearnLingo Moodle Design System token overrides.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use advanced_testcase;

/**
 * @coversNothing
 */
final class mds_overrides_test extends advanced_testcase {
    /**
     * Ensure the design tokens preserve the MDS API and adapt to site settings.
     */
    public function test_semantic_tokens_and_dark_mode_are_present(): void {
        global $CFG;

        $scss = file_get_contents($CFG->dirroot . '/theme/learnlingo/scss/_poss.scss');
        preg_match_all('/--mds-[a-z0-9-]+\\s*:/', $scss, $matches);
        $this->assertGreaterThanOrEqual(354, count($matches[0]));

        foreach ([
            '--mds-typography-line-height-paragraph-lg:',
            '--mds-color-blue-500:',
            '--mds-font-family-base:',
            '--mds-bg-interactive-primary-default: #{$primary};',
            '--mds-text-link-primary-default: #{mix(#000, $primary, 70%)};',
            '--mds-bg-surface-default: #222c39;',
            '--mds-focus-default:',
            '--mds-activity-icon-assessment-icon:',
            ':root[data-bs-theme="dark"]',
            '[data-bs-theme="dark"]',
        ] as $declaration) {
            $this->assertStringContainsString($declaration, $scss);
        }
    }

    /**
     * Navbar-only MDS tokens must be defined by the navbar SCSS, not globally.
     */
    public function test_navbar_mds_tokens_are_scoped_to_navbar(): void {
        global $CFG;

        $global = file_get_contents($CFG->dirroot . '/theme/learnlingo/scss/_poss.scss');
        $navbar = file_get_contents($CFG->dirroot . '/theme/learnlingo/scss/moodle/_navbar.scss');

        foreach (['--mds-spacing-lg', '--mds-spacing-md'] as $token) {
            $this->assertStringNotContainsString($token . ':', $global);
            $this->assertStringContainsString($token . ':', $navbar);
            $this->assertStringContainsString('var(' . $token . ')', $navbar);
        }

        $this->assertStringContainsString('html .navbar.fixed-top {', $navbar);
        $this->assertStringContainsString('@import "moodle/navbar";', $global);
    }

    /**
     * Both light and dark token layers stay in the post-Boot stylesheet.
     */
    public function test_overrides_are_in_poss_scss_after_component_imports(): void {
        global $CFG;

        $scss = file_get_contents($CFG->dirroot . '/theme/learnlingo/scss/_poss.scss');
        $lib = file_get_contents($CFG->dirroot . '/theme/learnlingo/lib.php');

        $posimport = strpos($scss, '@import "moodle/dark";');
        $posmds = strpos($scss, '// Moodle Design System tokens for LearnLingo.');
        $posdark = strpos($scss, ':root[data-bs-theme="dark"],');
        $this->assertNotFalse($posimport);
        $this->assertNotFalse($posmds);
        $this->assertNotFalse($posdark);
        $this->assertGreaterThan($posimport, $posmds);
        $this->assertGreaterThan($posmds, $posdark);
        $this->assertStringContainsString('scss/_poss.scss', $lib);
        $this->assertStringNotContainsString('scss/_mds_overrides.scss', $lib);
    }
}
