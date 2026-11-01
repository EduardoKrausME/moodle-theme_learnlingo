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
 * Available LearnLingo onboarding steps, following Moodle's core navigation flags.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

/**
 * The dashboard and My courses are enabled/disabled by Moodle, not this theme.
 */
final class setup_steps {
    /**
     * Whether Moodle currently offers the Dashboard page.
     *
     * @param object $cfg Moodle configuration.
     * @return bool
     */
    public static function dashboard_enabled(object $cfg): bool {
        return !isset($cfg->enabledashboard) || (bool)$cfg->enabledashboard;
    }

    /**
     * Whether Moodle currently offers the My courses page.
     *
     * @param object $cfg Moodle configuration.
     * @return bool
     */
    public static function mycourses_enabled(object $cfg): bool {
        return !isset($cfg->enablemycourses) || (bool)$cfg->enablemycourses;
    }

    /**
     * Include the last step if either learning landing page is enabled.
     *
     * @param object $cfg Moodle configuration.
     * @return int
     */
    public static function count(object $cfg): int {
        return (self::dashboard_enabled($cfg) || self::mycourses_enabled($cfg)) ? 4 : 3;
    }

    /**
     * Find the next step without allowing inaccessible steps.
     *
     * @param object $cfg Moodle configuration.
     * @param int $completed Last saved step.
     * @return int
     */
    public static function next(object $cfg, int $completed): int {
        return min(self::count($cfg), max(1, $completed + 1));
    }
}
