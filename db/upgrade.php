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
 * LearnLingo theme upgrades.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


/**
 * Upgrade LearnLingo.
 *
 * @param int $oldversion Previously installed version.
 * @return bool
 */
function xmldb_theme_learnlingo_upgrade(int $oldversion): bool {
    if ($oldversion < 2026101001) {
        $fs = get_file_storage();
        $context = context_system::instance();
        foreach (['logo' => 'logosvg', 'loginlogo' => 'loginlogosvg'] as $area => $legacy) {
            $svg = (string) get_config('theme_learnlingo', $legacy);
            if ($svg !== '' && \theme_learnlingo\svg_logo::is_valid($svg)) {
                $files = $fs->get_area_files($context->id, 'theme_learnlingo', $area, 0, 'id', false);
                foreach ($files as $file) {
                    $file->delete();
                }
                $fs->create_file_from_string([
                    'contextid' => $context->id,
                    'component' => 'theme_learnlingo',
                    'filearea' => $area,
                    'itemid' => 0,
                    'filepath' => '/',
                    'filename' => $area . '.svg',
                    'mimetype' => 'image/svg+xml',
                    'userid' => 0,
                ], $svg);
                unset_config($legacy, 'theme_learnlingo');
            }
        }
        upgrade_plugin_savepoint(true, 2026101001, 'theme', 'learnlingo');
    }

    if ($oldversion < 2026101002) {
        // Migrate only previously shipped values, never customised institutional branding.
        $palette = [
            'brandcolor' => ['#ef3340', '#58cc02'],
            'accentcolor' => ['#ff6670', '#8be24a'],
            'canvascolor' => ['#f5f6f8', '#f7faf5'],
            'textcolor' => ['#252a37', '#263238'],
        ];
        foreach ($palette as $setting => [$previous, $replacement]) {
            $current = get_config('theme_learnlingo', $setting);
            if (is_string($current) && strcasecmp(trim($current), $previous) === 0) {
                set_config($setting, $replacement, 'theme_learnlingo');
            }
        }

        // Clear obsolete default copy only when a site stored the exact old wording.
        $previousmessages = [
            'dashboarddescriptiontext' => [
                'Continue sua formação com aulas curtas, práticas e feitas para a rotina de quem cuida.',
                'Continue your education with short, practical lessons made for the routine of those who care.',
            ],
            'loginpaneldescriptiontext' => [
                'Experiências curtas e práticas, pensadas para a rotina de profissionais da saúde.',
                'Short, practical learning experiences designed for healthcare professionals.',
            ],
            'loginpaneltitletext' => [
                'Cuidado que vira conhecimento.',
                'Care that becomes knowledge.',
            ],
        ];
        foreach ($previousmessages as $setting => $values) {
            $current = get_config('theme_learnlingo', $setting);
            if (is_string($current) && in_array(trim($current), $values, true)) {
                unset_config($setting, 'theme_learnlingo');
            }
        }
        upgrade_plugin_savepoint(true, 2026101002, 'theme', 'learnlingo');
    }
    if ($oldversion < 2026101003) {
        // Also seed empty logo areas on sites updating from an older version.
        require_once(__DIR__ . '/install.php');
        theme_learnlingo_install_default_logos();
        upgrade_plugin_savepoint(true, 2026101003, 'theme', 'learnlingo');
    }

    if ($oldversion < 2026101004) {
        // Restore file-setting paths left stale by earlier SVG migrations.
        require_once(__DIR__ . '/install.php');
        theme_learnlingo_install_default_logos();
        upgrade_plugin_savepoint(true, 2026101004, 'theme', 'learnlingo');
    }

    return true;
}
