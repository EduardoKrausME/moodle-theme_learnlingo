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
 * Native primary navigation for custom LearnLingo pages.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo\hooks;

use core\hook\navigation\primary_extend as primary_extend_hook;
use theme_learnlingo\pages;

/**
 * Insert custom pages only when LearnLingo is the active theme.
 */
final class primary_extend {
    /**
     * Extend the core primary navigation with published LearnLingo pages.
     *
     * @param primary_extend_hook $hook Native Moodle hook.
     */
    public static function callback(primary_extend_hook $hook): void {
        global $PAGE;

        // Hooks are registered for installed plugins, not only the active theme.
        if (!$PAGE || $PAGE->theme->name !== 'learnlingo') {
            return;
        }

        pages::extend_primary_navigation($hook->get_primaryview());
    }
}
