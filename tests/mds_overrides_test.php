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

        $scss = file_get_contents($CFG->dirroot . '/theme/learnlingo/scss/_mds_overrides.scss');
        preg_match_all('/--mds-[a-z0-9-]+\\s*:/', $scss, $matches);
        $this->assertGreaterThanOrEqual(285, count($matches[0]));

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
     * The override must load after Boost but before the site-wide custom SCSS.
     */
    public function test_overrides_are_loaded_by_the_extra_scss_callback(): void {
        global $CFG;

        $lib = file_get_contents($CFG->dirroot . '/theme/learnlingo/lib.php');
        $posbase = strpos($lib, 'scss/_poss.scss');
        $posmds = strpos($lib, 'scss/_mds_overrides.scss');
        $poscustom = strpos($lib, '$theme->settings->scss');
        $this->assertNotFalse($posbase);
        $this->assertNotFalse($posmds);
        $this->assertNotFalse($poscustom);
        $this->assertGreaterThan($posbase, $posmds);
        $this->assertGreaterThan($posmds, $poscustom);
    }
}
