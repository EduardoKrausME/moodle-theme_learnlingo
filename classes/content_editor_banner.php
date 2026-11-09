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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * HTML editing guidance shared by custom pages, homepage and footer settings.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

/**
 * Renders a single localized, HTML-only editing banner.
 */
final class content_editor_banner {
    /**
     * Return the HTML banner for one editable content area.
     *
     * @param string $target Editor target: page, homepage or footer.
     * @param int $id Existing custom page ID, or zero for a new page.
     * @return string HTML banner with properly escaped URLs.
     */
    public static function render(string $target, int $id = 0): string {
        if (!in_array($target, ['page', 'homepage', 'footer'], true)) {
            throw new \invalid_parameter_exception('Invalid LearnLingo editor target');
        }

        if ($target === 'page' && $id === 0) {
            // A new custom page must be saved before the visual editor can load it.
            // Until then, the link goes to the editable HTML field in this form.
            $editorurl = '#id_content';
        } else {
            $params = ['target' => $target];
            if ($target === 'page') {
                $params['id'] = $id;
            }
            $editorurl = (new \moodle_url('/theme/learnlingo/_editor/editor.php', $params))->out(false);
        }

        return get_string('contenteditorbanner', 'theme_learnlingo', (object)[
            'editorurl' => s($editorurl),
            'helpurl' => s('https://eduardokrausme.github.io/moodle-local_mcp/'),
        ]);
    }
}
