<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Accessible modern colour setting for LearnLingo's administration pages.
 *
 * Extending Moodle's existing picker keeps its validation, config storage,
 * defaults, read-only flags, and update callbacks unchanged.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

defined('MOODLE_INTERNAL') || die;

// The base admin settings classes are defined in adminlib.php on Moodle 4.5.
global $CFG;
require_once($CFG->libdir . '/adminlib.php');

/**
 * Modern colour setting with native picker, presets, and editable value.
 */
class admin_setting_configcolourmodern extends \admin_setting_configcolourpicker {
    /**
     * Return an HTML colour input-compatible six-digit hexadecimal value.
     *
     * Other colours accepted by Moodle (such as rgb() and named colours) stay
     * untouched in the saved text field; the native input uses a safe fallback.
     *
     * @param mixed $value Colour value.
     * @return string|null
     */
    private static function to_hex($value): ?string {
        if (!is_string($value) || !preg_match('/^#?([0-9a-f]{3}|[0-9a-f]{6})$/iD', $value, $matches)) {
            return null;
        }
        $hex = strtoupper($matches[1]);
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        return '#' . $hex;
    }

    /**
     * Render the setting using the normal Moodle administrative form wrapper.
     *
     * @param mixed $data Saved or submitted value.
     * @param string $query Search query for Moodle's settings search.
     * @return string
     */
    public function output_html($data, $query = '') {
        global $OUTPUT;

        $default = $this->get_defaultsetting();
        $hex = self::to_hex($data) ?? self::to_hex($default) ?? '#000000';
        $defaulthex = self::to_hex($default) ?? '#000000';
        $presets = [];
        foreach ([
            '#58CC02', '#159957', '#0D9488', '#0284C7', '#2563EB', '#7C3AED',
            '#DB2777', '#EA580C', '#EAB308', '#172F47', '#334155', '#111827',
            '#64748B', '#F1F5F9', '#FFFFFF', '#000000',
        ] as $colour) {
            $presets[] = ['colour' => $colour];
        }

        $element = $OUTPUT->render_from_template('theme_learnlingo/setting_colourmodern', [
            'id' => $this->get_id(),
            'name' => $this->get_full_name(),
            'value' => (string)($data ?? $default ?? ''),
            'hex' => $hex,
            'defaulthex' => $defaulthex,
            'readonly' => $this->is_readonly(),
            'presets' => $presets,
            'presetlabel' => get_string('colourpreset', 'theme_learnlingo'),
        ]);

        return format_admin_setting(
            $this,
            $this->visiblename,
            $element,
            $this->description,
            true,
            '',
            $default,
            $query
        );
    }
}
