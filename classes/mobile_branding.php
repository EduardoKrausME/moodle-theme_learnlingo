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
 * Public visual identity used by the Kopere Mobile application builder.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;


use context_system;
use moodle_url;

/**
 * Export only public brand settings, never administrator-only configuration.
 */
final class mobile_branding {
    /**
     * Read actual Moodle colour settings and the navbar token source.
     *
     * @return array
     */
    public static function export(): array {
        $defaults = [
            'brandcolor' => '#58cc02',
            'accentcolor' => '#8be24a',
            'canvascolor' => '#f7faf5',
            'textcolor' => '#263238',
        ];
        $settings = get_config('theme_learnlingo');
        foreach ($defaults as $key => $default) {
            $value = is_object($settings) ? ($settings->{$key} ?? '') : '';
            $defaults[$key] = is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/D', $value)
                ? strtoupper($value) : strtoupper($default);
        }

        // Read the definitions from the same SCSS file compiled by LearnLingo.
        // Stop at the component rules: only export the light and dark token maps.
        $navbarpath = __DIR__ . '/../scss/moodle/_navbar.scss';
        $navbarscss = '';
        if (is_readable($navbarpath)) {
            $source = file_get_contents($navbarpath);
            if ($source !== false) {
                $end = strpos($source, '// Main navbar.');
                if ($end !== false) {
                    $navbarscss = trim(substr($source, 0, $end));
                }
            }
        }

        $logourl = self::public_logo_url();

        return [
            'schema_version' => 1,
            'source' => 'theme_learnlingo',
            'brandcolor' => $defaults['brandcolor'],
            'accentcolor' => $defaults['accentcolor'],
            'canvascolor' => $defaults['canvascolor'],
            'textcolor' => $defaults['textcolor'],
            'logo_url' => $logourl,
            'navbar_scss' => $navbarscss,
        ];
    }
    /**
     * Expose only an actual public image stored in the Moodle-wide logo area.
     * The default core SVG logo is not a suitable raster app icon.
     *
     * @return string
     */
    private static function public_logo_url(): string {
        $logo = get_config('core_admin', 'logo');
        if (!is_string($logo) || trim($logo, '/') === '') {
            return '';
        }
        $filename = basename($logo);
        $contextid = context_system::instance()->id;
        $file = get_file_storage()->get_file($contextid, 'core_admin', 'logo', 0, '/', $filename);
        if (!$file || $file->is_directory() ||
                !in_array($file->get_mimetype(), ['image/png', 'image/jpeg', 'image/webp'], true)) {
            return '';
        }
        $url = moodle_url::make_pluginfile_url($contextid, 'core_admin', 'logo', 0, '/', $filename);
        return $url->out(false);
    }
}
