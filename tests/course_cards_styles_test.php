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
 * Regression checks for native course section and activity card styling.
 *
 * @package   theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use advanced_testcase;

/**
 * CSS must work with both the heading and headingless native section markup.
 *
 * @coversNothing
 */
final class course_cards_styles_test extends advanced_testcase {
    /**
     * The card styles are loaded after Boost and the existing dark overrides.
     */
    public function test_course_cards_are_compiled_in_theme_scss(): void {
        global $CFG;

        $root = file_get_contents($CFG->dirroot . '/theme/learnlingo/scss/_poss.scss');
        $scss = file_get_contents($CFG->dirroot . '/theme/learnlingo/scss/_learnlingo-moodle.scss');
        $this->assertStringContainsString('@import "learnlingo-moodle";', $root);
        $this->assertStringContainsString('@import "moodle/dark";', $scss);
        $this->assertStringContainsString('@import "moodle/coursecards";', $scss);
        $this->assertGreaterThan(
            strpos($scss, '@import "moodle/dark";'),
            strpos($scss, '@import "moodle/coursecards";')
        );
    }

    /**
     * Native activity controls, module-type names and both section variants remain usable.
     */
    public function test_native_markup_and_localised_activity_types_are_preserved(): void {
        global $CFG;

        $scss = file_get_contents($CFG->dirroot . '/theme/learnlingo/scss/moodle/_coursecards.scss');
        $this->assertStringContainsString('.course-section-header:has(.sectionname)', $scss);
        $this->assertStringContainsString('.section-content-inner', $scss);
        $this->assertStringContainsString('li.activity > .activity-item:not(.activityinline)', $scss);
        $this->assertStringContainsString('li.activity.modtype_forum', $scss);
        $this->assertStringContainsString('li.activity.modtype_mcp', $scss);
        $this->assertStringNotContainsString('.instancename > .accesshide', $scss);
        $this->assertStringContainsString('.learnlingo-activity-type', $scss);
        $this->assertStringContainsString('.learnlingo-simple-activity-card', $scss);
        $this->assertStringContainsString('body.path-course-view.editing', $scss);
        $this->assertStringContainsString('[data-bs-theme="dark"]', $scss);
        $this->assertStringContainsString('@media (max-width: 767.98px)', $scss);
        $this->assertStringContainsString('prefers-reduced-motion', $scss);
        $this->assertStringNotContainsString('grid-template-areas:', $scss);
        $this->assertStringNotContainsString('content: "Fórum"', $scss);
    }

    /**
     * Moodle's translated accessibility label remains hidden and is copied
     * to a visual, screen-reader-ignored badge only in non-editing view.
     */
    public function test_visual_type_badge_preserves_accessibility_and_native_controls(): void {
        global $CFG;

        $template = file_get_contents($CFG->dirroot . '/theme/learnlingo/templates/drawers.mustache');
        $this->assertStringContainsString("modulelabel.textContent.trim()", $template);
        $this->assertStringContainsString("badge.setAttribute('aria-hidden', 'true')", $template);
        $this->assertStringContainsString("classList.contains('editing')", $template);
        $this->assertStringContainsString("grid.children.length === 2", $template);
        $this->assertStringContainsString("new MutationObserver(decorateCourseContent)", $template);
    }
}
