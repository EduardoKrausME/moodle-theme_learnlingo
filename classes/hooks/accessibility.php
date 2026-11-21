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
 * Optional accessibility toolbar and VLibras output hook.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo\hooks;

use core\hook\output\before_footer_html_generation;

/**
 * Loads accessible tools using Moodle's existing footer hook.
 */
final class accessibility {
    /**
     * Add the accessibility toolbar loader and optional VLibras widget.
     *
     * @param before_footer_html_generation $hook Footer output hook.
     * @return void
     */
    public static function before_footer_html_generation(before_footer_html_generation $hook): void {
        global $OUTPUT, $PAGE;

        if (get_config("theme_learnlingo", "enable_accessibility")) {
            $PAGE->requires->strings_for_js(["acctoolbar_image_without_alt"], "theme_learnlingo");
            $PAGE->requires->js_call_amd("theme_learnlingo/acctoolbar", "init");
        }

        if (!get_config("theme_learnlingo", "enable_vlibras") || current_language() !== "pt_br") {
            return;
        }

        // These values are inserted into the VLibras template JavaScript.
        $position = get_config("theme_learnlingo", "vlibras_position") ?: "R";
        $position = in_array($position, ["L", "R"], true) ? $position : "R";
        $avatar = get_config("theme_learnlingo", "vlibras_avatar") ?: "icaro";
        $avatar = in_array($avatar, ["icaro", "hosana", "guga", "random"], true) ? $avatar : "icaro";

        $PAGE->requires->js_call_amd("theme_learnlingo/acctoolbar", "track_vlibras");
        $hook->add_html($OUTPUT->render_from_template("theme_learnlingo/settings/vlibras", [
            "position" => $position,
            "avatar" => $avatar,
        ]));
    }
}
