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

namespace theme_learnlingo\output;

use context_system;

/**
 * Export either the existing footer columns or a custom HTML footer.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class footer_renderer {
    /**
     * Export configured content without global cross-site caches.
     *
     * @return array
     */
    public static function mustache_data(): array {
        $htmlmode = get_config('theme_learnlingo', 'footermode') === 'html';
        $data = [
            'footer_mode_html' => $htmlmode,
            'footer_mode_moodle' => !$htmlmode,
            'footercount' => 0,
            'footercontents' => [],
            'footer_show_copywriter' => get_config('theme_learnlingo', 'footer_show_copywriter') !== '0',
            'footer_custom_html' => '',
        ];
        if ($htmlmode) {
            $html = (string)(get_config('theme_learnlingo', 'footerhtml') ?: '');
            $data['footer_custom_html'] = format_text($html, FORMAT_HTML, [
                'context' => context_system::instance(),
                'filter' => false,
                'noclean' => false,
            ]);
            return $data;
        }
        $context = context_system::instance();
        for ($i = 1; $i <= 4; $i++) {
            $html = (string)(get_config('theme_learnlingo', "footer_html_{$i}") ?: '');
            $title = (string)(get_config('theme_learnlingo', "footer_title_{$i}") ?: '');
            if (trim(strip_tags($html)) === '' && !preg_match('/<(img|svg)\b/i', $html)) {
                continue;
            }
            $data['footercontents'][] = [
                'footertitle' => format_string($title, true, ['context' => $context]),
                'footerhtml' => format_text($html, FORMAT_HTML,
                    ['context' => $context, 'filter' => false, 'noclean' => false]),
            ];
        }
        $data['footercount'] = count($data['footercontents']);

        return $data;
    }
}
