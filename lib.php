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
 * Theme callbacks.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Returns SCSS inserted before Boost's main SCSS.
 *
 * Use scss/pre.scss for variables that need to exist before Bootstrap is compiled.
 *
 * @param theme_config $theme Theme configuration.
 * @return string
 */
function theme_learnlingo_get_pre_scss(theme_config $theme): string {
    global $CFG;

    $brandcolor = !empty($theme->settings->brandcolor) ? $theme->settings->brandcolor : "#ef3340";
    $accentcolor = !empty($theme->settings->accentcolor) ? $theme->settings->accentcolor : "#ff6670";

    $scss = file_get_contents("{$CFG->dirroot}/theme/learnlingo/scss/_pre.scss");
    $scss .= "\$primary: {$brandcolor};\n";
    $scss .= "\$pulsomed-accent: {$accentcolor};\n";

    if (!empty($theme->settings->scsspre)) {
        $scss .= PHP_EOL . $theme->settings->scsspre;
    }

    return $scss;
}

/**
 * Returns SCSS inserted after Boost's main SCSS.
 *
 * Use scss/post.scss for LearnLingo component styles and visual overrides.
 *
 * @param theme_config $theme Theme configuration.
 * @return string
 */
function theme_learnlingo_get_extra_scss(theme_config $theme): string {
    global $CFG;

    $scss = file_get_contents("{$CFG->dirroot}/theme/learnlingo/scss/_post.scss");

    if (!empty($theme->settings->scss)) {
        $scss .= "\n{$theme->settings->scss}";
    }

    return $scss;
}

